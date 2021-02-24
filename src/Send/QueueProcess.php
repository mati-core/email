<?php

declare(strict_types=1);

namespace MatiCore\Email;


use Baraja\Doctrine\EntityManager;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Nette\Mail\SmtpException;
use Nette\SmartObject;
use Nette\Utils\DateTime;
use MatiCore\Email\Entity\Email;
use MatiCore\Utils\Date;
use MatiCore\Utils\Time;
use Tracy\Debugger;

/**
 * Class QueueProcess
 * @package MatiCore\Email
 */
class QueueProcess
{

	use SmartObject;

	/**
	 * @var EntityManager
	 */
	private EntityManager $entityManager;

	/**
	 * @var SenderAccessor
	 */
	private SenderAccessor $sender;

	/**
	 * @var MessageToDatabaseSerializer
	 */
	private MessageToDatabaseSerializer $serializer;

	/**
	 * @var EmailerLogger
	 */
	private EmailerLogger $logger;

	/**
	 * Po kolika sekundach ma dojit k ukonceni procesu?
	 *
	 * @var int
	 */
	private int $timeout = 60;

	/**
	 * Prodleva mezi odeslanim jednotlivych emailu v sekundach.
	 * Je mozne zadat i "0.5", coz znamena pul sekundy. Interne se pouziva usleep misto sleep.
	 *
	 * @var float
	 */
	private float $emailDelay = 0.5;

	/**
	 * Prodleva mezi iteracemi kontrolujicimi zda je neco ve fronte.
	 *
	 * @var int
	 */
	private int $checkIterationDelay = 3;

	/**
	 * @param EntityManager $entityManager
	 * @param SenderAccessor $sender
	 * @param MessageToDatabaseSerializer $serializer
	 * @param EmailerLogger $logger
	 */
	public function __construct(
		EntityManager $entityManager,
		SenderAccessor $sender,
		MessageToDatabaseSerializer $serializer,
		EmailerLogger $logger
	)
	{
		$this->entityManager = $entityManager;
		$this->sender = $sender;
		$this->serializer = $serializer;
		$this->logger = $logger;
	}

	/**
	 * @param int $timeout
	 * @param float $emailDelay
	 * @param int $checkIterationDelay
	 */
	public function setup(int $timeout, float $emailDelay, int $checkIterationDelay): void
	{
		$this->timeout = $timeout;
		$this->emailDelay = $emailDelay;
		$this->checkIterationDelay = $checkIterationDelay;
	}

	/**
	 * @return int
	 * @throws \Exception
	 */
	public function run(): int
	{
		$result = 0;
		$startTime = microtime(true);

		while (true) {
			echo '.';

			if (time() - $startTime > $this->timeout) {
				break;
			}

			/** @var Email[] $emails */
			$emails = (new Paginator(
				$this->entityManager->getRepository(Email::class)
					->createQueryBuilder('email')
					->select('email, template, raw')
					->leftJoin('email.template', 'template')
					->leftJoin('email.raw', 'raw')
					->where('
					(
						email.status = :statusInQueue
						OR (
							email.status = :statusWaitingForNextAttempt
							AND (
								email.sendEarliestNextAttemptAt IS NULL
								OR email.sendEarliestNextAttemptAt <= :now
							)
						)
					) AND (
						email.sendEarliestAt IS NULL
						OR email.sendEarliestAt <= :now
					)
					')
					->setParameters(new ArrayCollection([
						'statusInQueue' => Email::STATUS_IN_QUEUE,
						'statusWaitingForNextAttempt' => Email::STATUS_WAITING_FOR_NEXT_ATTEMPT,
						'now' => DateTime::from('now')->format('Y-m-d H:i:s'),
					]))
					->orderBy('raw.priority', 'ASC')
					->setMaxResults(1)
			))->getIterator();

			if (isset($emails[0])) {
				$email = $emails[0];
			} else {
				usleep($this->checkIterationDelay * 1000 * 1000);
				continue;
			}

			try {
				echo 'M';
				$this->process($email);
				$result++;
			} catch (SmtpException $e) {
				echo 'E';
				Debugger::log($e);
				$template = $email->getTemplate();

				$this->logger->log('ERROR', 'E-mail #' . $email->getId() . ' failed to send: ' . $e->getMessage() . ', details on Tracy logger.');

				if ($template !== null && $email->getFailedAttemptsCount() > $template->getMaxAllowedAttempts()) {
					$email->setStatus(Email::STATUS_SENDING_ERROR);
					$email->addNote(Date::getDateTimeIso() . ' - ' . $e->getMessage());
				} else {
					// odeslat znovu zkousime nejdriv za nekolik minut
					$email->setStatus(Email::STATUS_WAITING_FOR_NEXT_ATTEMPT);
					$email->setSendEarliestNextAttemptAt(DateTime::from('+15 minutes'));
					$email->incrementFailedAttemptsCount();
				}

				$this->entityManager->flush($email);
			} catch (\Throwable $e) {
				echo 'E';
				Debugger::log($e);
				$template = $email->getTemplate();

				$this->logger->log('ERROR', 'E-mail #' . $email->getId() . ' failed to send: ' . $e->getMessage() . ', details on Tracy logger.');

				if ($template !== null && $email->getFailedAttemptsCount() > $template->getMaxAllowedAttempts()) {
					$email->setStatus(Email::STATUS_PREPARING_ERROR);
					$email->addNote(Date::getDateTimeIso() . ' - ' . $e->getMessage());
				} else {
					// odeslat znovu zkousime nejdriv za nekolik minut
					$email->setStatus(Email::STATUS_WAITING_FOR_NEXT_ATTEMPT);
					$email->setSendEarliestNextAttemptAt(DateTime::from('+15 minutes'));
					$email->incrementFailedAttemptsCount();
				}

				$this->entityManager->flush($email);
			}

			usleep((int) ($this->emailDelay * 1000 * 1000));
		}

		$this->logger->log(
			'INFO',
			'FINISHED: sender was running for ' . Time::formatDurationFrom((int) $startTime) . ' and it sent ' . $result . ' e-mails'
		);

		return $result;
	}

	/**
	 * @param Email $email
	 * @throws \Exception
	 */
	private function process(Email $email): void
	{
		// build Message instance
		$builderStartTime = microtime(true);
		$message = $this->serializer->rawToMessage($email->getRaw());
		$builderDuration = microtime(true) - $builderStartTime;

		if (trim($message->getHtmlBody()) === '' && trim($message->getBody()) === '') {
			$email->setStatus(Email::STATUS_PREPARING_ERROR);
			$email->addNote(Date::getDateTimeIso() . ' - E-mail was not sent (empty body)');
			$this->entityManager->flush($email);

			return;
		}

		// send Message
		$mailerStartTime = microtime(true);
		$this->sender->get()->send($message);
		$mailerDuration = microtime(true) - $mailerStartTime;

		$email->setStatus(Email::STATUS_SENT);
		$email->setPreparingDuration($builderDuration);
		$email->setSendingDuration($mailerDuration);
		$email->setDatetimeSent(DateTime::from('now'));
		$this->entityManager->flush($email);

		$this->logger->log(
			'INFO',
			'E-mail "' . $email->getId() . '" was successfully sent to '
			. '"' . ($email->getRaw() === null ? '???' : $email->getRaw()->getTo()) . '" '
			. 'with subject "' . $message->getSubject() . '". '
			. 'Preparation took "' . Time::formatMicroTime((int) $email->getPreparingDuration()) . '" '
			. 'and sending took "' . Time::formatMicroTime((int) $email->getSendingDuration()) . '"'
		);
	}

}