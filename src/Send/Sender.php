<?php

declare(strict_types=1);

namespace MatiCore\Email;


use Nette\Mail\IMailer;
use Nette\Mail\Message as NetteMessage;
use Nette\Mail\SendmailMailer;
use Nette\Mail\SmtpMailer;
use Nette\SmartObject;

/**
 * Class Sender
 * @package MatiCore\Email
 */
class Sender
{

	use SmartObject;

	/**
	 * @var mixed[]
	 */
	private static $defaults = [
		'smtp' => false,
		'host' => null,
		'port' => null,
		'username' => null,
		'password' => null,
		'secure' => null,
		'timeout' => null,
		'context' => null,
		'clientHost' => null,
		'persistent' => false,
	];

	/**
	 * @var IMailer
	 */
	private $mailer;

	/**
	 * @param mixed[] $config
	 */
	public function __construct(array $config)
	{
		if (empty($config['smtp'])) {
			$mailer = new SendmailMailer;
		} else {
			$mailer = new SmtpMailer(array_merge(self::$defaults, $config));
		}

		$this->mailer = $mailer;
	}

	/**
	 * @param NetteMessage $message
	 */
	public function send(NetteMessage $message): void
	{
		$this->mailer->send($message);
	}

}
