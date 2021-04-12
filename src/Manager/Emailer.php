<?php

declare(strict_types=1);

namespace MatiCore\Email;


use Baraja\Doctrine\EntityManager;
use Baraja\Doctrine\EntityManagerException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use MatiCore\Constant\Exception\ConstantException;
use MatiCore\Email\Mjml\MjmlRenderer;
use Nette\DI\Container;
use Nette\DI\MissingServiceException;
use Nette\FileNotFoundException;
use Nette\Localization\Translator;
use Nette\Mail\Message as NetteMessage;
use Nette\SmartObject;
use Nette\Utils\DateTime;
use Nette\Utils\FileSystem;
use Nette\Utils\Finder;
use Nette\Utils\Strings;
use Nette\Utils\Validators;
use MatiCore\Constant\ConstantManagerAccessor;
use MatiCore\Email\Email\Email as EmailService;
use MatiCore\Email\Entity\Email;
use Tracy\Debugger;

/**
 * Class Emailer
 * @package MatiCore\Email
 */
class Emailer implements IEmailer
{

	use SmartObject;

	/**
	 * @var Container
	 */
	private Container $container;

	/**
	 * @var EntityManager
	 */
	private EntityManager $entityManager;

	/**
	 * @var ConstantManagerAccessor
	 */
	private ConstantManagerAccessor $constantManager;

	/**
	 * @var SenderAccessor
	 */
	private SenderAccessor $sender;

	/**
	 * @var string
	 */
	private string $dataDir;

	/**
	 * @var bool
	 */
	private bool $useQueue;

	/**
	 * @var EmailerLogger
	 */
	private EmailerLogger $logger;

	/**
	 * @var MessageToDatabaseSerializer
	 */
	private MessageToDatabaseSerializer $serializer;

	/**
	 * @var string
	 */
	public string $language;

	/**
	 * Emailer constructor.
	 * @param string $dataDir
	 * @param bool $useQueue
	 * @param Container $container
	 * @param EntityManager $entityManager
	 * @param ConstantManagerAccessor $constantManager
	 * @param SenderAccessor $sender
	 * @param EmailerLogger $logger
	 * @param MessageToDatabaseSerializer $messageToDatabaseSerializer
	 * @param Translator $translator
	 */
	public function __construct(
		string $dataDir,
		bool $useQueue,
		Container $container,
		EntityManager $entityManager,
		ConstantManagerAccessor $constantManager,
		SenderAccessor $sender,
		EmailerLogger $logger,
		MessageToDatabaseSerializer $messageToDatabaseSerializer,
		Translator $translator
	)
	{
		$this->container = $container;
		$this->entityManager = $entityManager;
		$this->constantManager = $constantManager;
		$this->sender = $sender;
		$this->dataDir = rtrim($dataDir, '/');
		$this->useQueue = $useQueue;
		$this->logger = $logger;
		$this->serializer = $messageToDatabaseSerializer;

		if ($translator instanceof \Contributte\Translation\Translator) {
			$this->language = $translator->getLocale();

			if ($this->language === null || $this->language === '') {
				$this->language = $translator->getDefaultLocale();
			}
		} else {
			$this->language = 'cs';
		}
	}

	/**
	 * @param string $type
	 * @param array $parameters
	 * @return MessageReadyToSend
	 * @throws EmailException
	 * @throws EntityManagerException
	 * @throws ConstantException
	 */
	public function getEmailServiceByType(string $type, array $parameters = []): MessageReadyToSend
	{
		if (preg_match('/\\\\(?<name>[A-Z0-9a-z]\w*?)Email$/', $type, $typeParser)) {
			$typeName = Strings::firstLower($typeParser['name']);
		} else {
			throw new EmailException('Type "' . $type . '" does now match class format <Name>Email.');
		}

		/** @var EmailService $email */
		$email = $this->container->getByType($type);
		$schema = $email->getSchema();
		$parameters = array_merge($schema->getSystemParameters(), $parameters);

		$namespace = 'email-' . $typeName;

		foreach ($schema->getUserParameters() as $userParameterName => $userParameterValue) {
			$constantValue = $this->constantManager->get()->get($userParameterName, $namespace);
			$parameterValue = $constantValue ?? $userParameterValue;
			if (!($parameterValue === null && isset($parameters[$userParameterName]) && $parameters[$userParameterName] !== null)) {
				$parameters[$userParameterName] = $parameterValue;
			}

			if ($constantValue === null && $parameterValue !== null) {
				$this->constantManager->get()->set($userParameterName, $parameterValue, $namespace);
			}
		}

		$message = $email->getSchema()->getMessage();

		$message->setSubject(
			$parameters['subject'] ?? $message->getSubject()
		);

		if (isset($parameters['from'])) {
			$message->setFrom($parameters['from']);
		}

		if (isset($parameters['to'])) {
			$message->clearHeader('To');
			$message->addTo($parameters['to']);
		}

		if (isset($parameters['cc'])) {
			$message->clearHeader('Cc');
			foreach (explode(';', $parameters['cc']) as $cc) {
				if (Validators::isEmail($cc)) {
					$message->addCc($cc);
				}
			}
		}

		if (isset($parameters['bcc'])) {
			$message->clearHeader('Bcc');
			foreach (explode(';', $parameters['bcc']) as $bcc) {
				if (Validators::isEmail($bcc)) {
					$message->addBcc($bcc);
				}
			}
		}

		if (isset($parameters['replyTo']) && Validators::isEmail($parameters['replyTo'])) {
			$message->addReplyTo($parameters['replyTo']);
		}

		if (isset($parameters['sendEarliestAt'])) {
			$message->setSendEarliestAt($parameters['sendEarliestAt']);
		}

		$finalTemplate = $email->getTemplate($this->language);

		if ($finalTemplate === null) {
			foreach (Finder::find($typeName . '.*')->in($this->dataDir . '/templates') as $path => $info) {
				preg_match('/\/(?<name>\w+)(?:\.(?<language>\w+))?\.(?<format>\w+)$/', str_replace('\\', '/', $path), $pathParser);

				if (isset($pathParser['language'])) {
					$templateLanguage = $pathParser['language'] ?: null;
				} else {
					$templateLanguage = null;
				}

				if ($pathParser['name'] === $typeName) {
					if ($templateLanguage === $this->language) {
						$finalTemplate = $path;
						break;
					}

					if ($finalTemplate === null && $templateLanguage === null) {
						$finalTemplate = $path;
					}
				}
			}
		}

		if ($finalTemplate === null) {
			EmailException::missingTemplate($typeName, $this->dataDir);
		} else {
			$message->setHtmlBody(
				$this->renderTemplate($finalTemplate, $parameters)
			);
		}

		return new MessageReadyToSend($message, $this);
	}

	/**
	 * @param string $templatePath
	 * @param array $parameters
	 * @return string
	 * @throws EmailException
	 */
	public function renderTemplate(string $templatePath, array $parameters = []): string
	{
		clearstatcache();

		$renderer = null;

		if (!is_file($templatePath)) {
			EmailException::missingTemplateFile($templatePath);
		}

		preg_match('/\.([^\.]+)$/', $templatePath, $suffix);
		$fileType = $suffix[1] ?? 'latte';

		$types = [
			'mjml' => MjmlRenderer::class,
			'latte' => LatteRenderer::class,
		];

		try {
			if (isset($types[$fileType]) === false) {
				EmailException::missingRendererForFile($fileType, $templatePath);
			}

			if (class_exists($types[$fileType]) === false) {
				EmailException::missingRenderer($types[$fileType]);
			}

			/** @var TemplateRenderer $renderer */
			$renderer = $this->container->getByType($types[$fileType]);
		} catch (MissingServiceException $e) {
			EmailException::rendererNotExists($fileType, $e);
		}

		if ($renderer === null) {
			EmailException::missingRendererForFile($fileType, $templatePath);
		}

		return $renderer->render($templatePath, $parameters);
	}

	/**
	 * @return SenderAccessor
	 */
	public function getSender(): SenderAccessor
	{
		return $this->sender;
	}

	/**
	 * @return MessageToDatabaseSerializer
	 */
	public function getSerializer(): MessageToDatabaseSerializer
	{
		return $this->serializer;
	}

	/**
	 * @param NetteMessage $mail
	 * @throws EmailException
	 */
	public function send(NetteMessage $mail): void
	{
		if ($this->useQueue === true) {
			try {
				$this->insertMessageToQueue($mail);
			} catch (EntityManagerException $e) {
				Debugger::log($e);
				throw new EmailException($e->getMessage(), $e->getCode(), $e);
			}
		} else {
			$this->sendNow($mail);
		}
	}

	/**
	 * @param NetteMessage $message
	 * @param string $sendEarliestAt
	 * @return Email|null
	 * @throws EmailException
	 */
	public function insertMessageToQueue(NetteMessage $message, string $sendEarliestAt = 'now'): ?Email
	{
		if (trim($message->getBody()) === '' && trim($message->getHtmlBody()) === '') {
			$this->logger->log('WARNING', __METHOD__ . ': Empty mail (no body)');

			return null;
		}

		if ($sendEarliestAt === 'now' && \count($message->getAttachments()) > 0) {
			$sendEarliestAt = '+ 1 minutes';
		}

		$raw = $this->serializer->messageToRaw($message);
		$this->entityManager->persist($raw);
		$email = new Email($raw);
		$raw->setEmail($email);

		$email->setStatus(
			$message instanceof Message && $message->getAttachmentPaths()
				? Email::STATUS_NOT_READY_TO_QUEUE
				: Email::STATUS_IN_QUEUE
		);

		if (!$message instanceof Message || ($sendEarliestAt && $sendEarliestAt !== 'now')) {
			$email->setSendEarliestAt(DateTime::from($sendEarliestAt));
		} else {
			$email->setSendEarliestAt(DateTime::from($message->getSendEarliestAt()));
		}


		$this->entityManager->persist($email)->flush([$email, $raw]);

		if ($message instanceof Message && $message->getAttachmentPaths()) {
			$this->setAttachmentsToMessage($email, $message);
			$email->setStatus(Email::STATUS_IN_QUEUE);
			$this->entityManager->flush($email);
		}

		return $email;
	}

	/**
	 * @param Email $email
	 * @param Message $message
	 * @throws EmailException
	 */
	private function setAttachmentsToMessage(Email $email, Message $message): void
	{
		$attachmentDir = $this->dataDir . '/temp/email-temporary-attachments/' . $email->getId();
		FileSystem::createDir($attachmentDir);

		foreach ($message->getAttachmentPaths() as $key => $attachment) {
			if (is_file($attachment['path'])) {
				FileSystem::copy($attachment['path'], $attachmentDir . '/' . $attachment['fileName']);
				$message->updateAttachmentPath($key, $attachment['fileName']);
			} else {
				throw new FileNotFoundException('Attachment "' . $attachment . '" not found');
			}
		}
	}

	/**
	 * @param NetteMessage $message
	 * @throws EmailException
	 */
	public function sendNow(NetteMessage $message): void
	{
		try {
			$email = $this->insertMessageToQueue($message);

			try {
				if ($email !== null && $email->getStatus() !== Email::STATUS_SENT) {
					$this->sender->get()->send(
						$this->serializer->rawToMessage($email->getRaw())
					);
					$email->setStatus(Email::STATUS_SENT);
					$email->setDatetimeSent(DateTime::from('now'));

					$this->entityManager->flush($email);
				}
			} catch (\Throwable $e) {
				Debugger::log($e);
			}
		} catch (EntityManagerException $e) {
			Debugger::log($e);
			throw new EmailException($e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * @param string $id
	 * @return Email
	 * @throws NoResultException|NonUniqueResultException
	 */
	public function getEmailById(string $id): Email
	{
		static $cache = [];

		return $cache[$id] ?? ($cache[$id] = $this->entityManager->getRepository(Email::class)
				->createQueryBuilder('email')
				->select('email, template, raw')
				->leftJoin('email.template', 'template')
				->leftJoin('email.raw', 'raw')
				->where('email.id = :id')
				->setParameter('id', $id)
				->getQuery()
				->getSingleResult()
			);
	}

	/**
	 * @param string $from
	 * @param string $to
	 * @param string $subject
	 * @param string $text
	 * @throws ConstantException
	 * @throws EmailException
	 */
	public function sendEmail(string $from, string $to, string $subject, string $text): void
	{
		$this->getEmailServiceByType(TextEmail::class, [
			'from' => $from,
			'to' => $to,
			'subject' => $subject,
			'text' => $text,
		])->send();
	}

}