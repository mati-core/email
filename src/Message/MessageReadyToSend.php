<?php

declare(strict_types=1);

namespace MatiCore\Email;

/**
 * Class MessageReadyToSend
 * @package MatiCore\Email
 */
final class MessageReadyToSend
{

	/**
	 * @var Message
	 */
	private Message $message;

	/**
	 * @var Emailer
	 */
	private Emailer $emailEngine;

	/**
	 * MessageReadyToSend constructor.
	 * @param Message $message
	 * @param Emailer $emailEngine
	 */
	public function __construct(Message $message, Emailer $emailEngine)
	{
		$this->message = $message;
		$this->emailEngine = $emailEngine;
	}

	/**
	 * @throws EmailException
	 */
	public function send(): void
	{
		$this->emailEngine->send($this->message);
	}

	/**
	 * @internal
	 * @return Message
	 */
	public function getMessage(): Message
	{
		return $this->message;
	}

}