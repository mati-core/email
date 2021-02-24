<?php

declare(strict_types=1);

namespace MatiCore\Email;


use Nette\FileNotFoundException;
use Nette\Mail\Message as NetteMessage;
use Nette\Utils\FileSystem;
use Nette\Utils\Finder;
use Nette\Utils\Strings;
use MatiCore\Email\Entity\Email;
use MatiCore\Email\Entity\EmailRaw;

/**
 * Class MessageToDatabaseSerializer
 * @package MatiCore\Email
 */
class MessageToDatabaseSerializer
{

	/**
	 * @var array(array<string>)
	 */
	private array $config;

	/**
	 * @var string
	 */
	private string $dataDir;

	/**
	 * MessageToDatabaseSerializer constructor.
	 * @param array<string> $config
	 * @param string $dataDir
	 */
	public function __construct(array $config, string $dataDir)
	{
		$this->config = $config;
		$this->dataDir = rtrim($dataDir, '/');
	}

	/**
	 * @param NetteMessage $message
	 * @return EmailRaw
	 * @throws EmailException
	 */
	public function messageToRaw(NetteMessage $message): EmailRaw
	{
		$from = $message->getFrom();
		$from = $from !== [] && $from !== null
			? $this->formatHeader($from)
			: $this->config['fromName'] . ' <' . $this->config['from'] . '>';
		$to = $message->getHeader('To');

		if (!$from) {
			EmailException::missingParameterFrom();
		}

		if (!$to) {
			EmailException::missingParameterTo();
		}

		$raw = new EmailRaw(
			$from,
			$this->formatHeader($to),
			$message->getSubject() ?? Strings::truncate(
				trim(str_replace('*', '', strip_tags($message->getBody() ? : ''))), 128
			),
			$message->getHtmlBody() ? : null,
			$message->getBody() ? : null
		);

		foreach ($message->getHeader('Cc') ?? [] as $ccMail => $ccName) {
			$raw->addCc($this->formatHeader([$ccMail => $ccName]));
		}

		foreach ($message->getHeader('Bcc') ?? [] as $bccMail => $bccName) {
			$raw->addBcc($this->formatHeader([$bccMail => $bccName]));
		}

		foreach ($this->config['bcc'] ?? [] as $bcc) {
			$raw->addBcc($bcc);
		}

		if ($message instanceof Message) {
			foreach ($message->getAttachmentPaths() as $attachment) {
				$raw->addAttachment($attachment['path'], $attachment['fileName']);
			}
		}

		// TODO: Reply-to

		$raw->setReturnPath($message->getHeader('Return-Path'));
		$raw->setPriority((int) $message->getHeader('X-Priority'));

		return $raw;
	}

	/**
	 * @param EmailRaw $raw
	 * @return NetteMessage
	 */
	public function rawToMessage(EmailRaw $raw): NetteMessage
	{
		$message = (new NetteMessage)
			->setFrom($raw->getFrom())
			->addTo($raw->getTo())
			->setSubject($raw->getSubject())
			->setHtmlBody($this->processHtmlMail($raw))
			->setBody($raw->getTextBody());

		foreach ($raw->getCc() as $cc) {
			$message->addCc($cc);
		}

		foreach ($raw->getBcc() as $bcc) {
			$message->addBcc($bcc);
		}

		$this->setAttachmentsIfExists($raw->getEmail(), $message);

		return $message;
	}

	/**
	 * @param mixed[]|null $header
	 * @return string
	 */
	private function formatHeader(?array $header): string
	{
		if ($header === null) {
			return '';
		}

		foreach ($header as $mail => $name) {
			if ($mail !== null) {
				return $name === null
					? $mail
					: $name . ' <' . $mail . '>';
			}
		}

		return '';
	}

	/**
	 * @param Email $email
	 * @param NetteMessage $message
	 */
	private function setAttachmentsIfExists(Email $email, NetteMessage $message): void
	{
		$tempDir = $this->dataDir . '/email-temporary-attachments/' . $email->getId();

		if (is_dir($tempDir) === false) {
			return;
		}

		$finder = Finder::find('*')->in($tempDir);

		foreach ($finder as $attachmentPath => $fileInfo) {
			if (is_file($attachmentPath)) {
				$message->addAttachment($attachmentPath);
			} else {
				throw new FileNotFoundException('Attachment "' . $attachmentPath . '" not found');
			}

			FileSystem::delete($attachmentPath);
		}

		FileSystem::delete($tempDir);
	}

	/**
	 * @param EmailRaw $raw
	 * @return string
	 */
	private function processHtmlMail(EmailRaw $raw): string
	{
		$body = $raw->getHtmlBody();
		$pairHtml = '<div style="color:white;font-size:1pt" id="pair__token">'
			. $raw->getEmail()->getId() . '_' . date('Y-m-d')
			. '</div>';

		if (Strings::contains($body, '</body>')) {
			return (string) str_replace('</body>', $pairHtml . '</body>', $body);
		}

		return $body . $pairHtml;
	}

}