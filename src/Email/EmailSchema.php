<?php

declare(strict_types=1);

namespace MatiCore\Email\Email;


use Nette\SmartObject;
use MatiCore\Email\Message;

/**
 * Class EmailSchema
 * @package MatiCore\Email\Email
 */
class EmailSchema
{

	use SmartObject;

	/**
	 * @var Message
	 */
	private Message $message;

	/**
	 * @var array<string>
	 */
	private array $systemParameters = [];

	/**
	 * @var array<string|null>
	 */
	private array $userParameters = [
		'from' => null,
		'to' => null,
		'cc' => null,
		'subject' => null,
	];

	/**
	 * @param Message $message
	 */
	public function __construct(Message $message)
	{
		$this->message = $message;
	}

	/**
	 * @return Message
	 */
	public function getMessage(): Message
	{
		return $this->message;
	}

	/**
	 * @return array<string>
	 */
	public function getSystemParameters(): array
	{
		return $this->systemParameters;
	}

	/**
	 * @param string $parameter
	 * @param mixed|null $value
	 * @return EmailSchema
	 */
	public function addSystemParameter(string $parameter, $value = null): self
	{
		$this->systemParameters[$parameter] = $value;

		return $this;
	}

	/**
	 * @param mixed[] $parameters
	 */
	public function addSystemParameters(array $parameters): void
	{
		foreach ($parameters as $key => $value) {
			$this->addSystemParameter($key, $value);
		}
	}

	/**
	 * @return array<?string>
	 */
	public function getUserParameters(): array
	{
		return $this->userParameters;
	}

	/**
	 * @param string $parameter
	 * @param mixed|null $defaultValue
	 * @return EmailSchema
	 */
	public function addUserParameter(string $parameter, $defaultValue = null): self
	{
		$this->userParameters[$parameter] = $defaultValue;

		return $this;
	}

}