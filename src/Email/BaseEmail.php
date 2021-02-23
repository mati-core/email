<?php

declare(strict_types=1);

namespace MatiCore\Email\Email;


use MatiCore\Core\Constant\ConstantManagerAccessor;
use MatiCore\Core\Constant\Exception\ConstantException;
use MatiCore\Email\EmailerAccessor;
use MatiCore\Email\EmailException;
use MatiCore\Email\Message;

/**
 * Class BaseEmail
 * @package MatiCore\Email\Email
 */
abstract class BaseEmail implements Email
{

	/**
	 * @var EmailerAccessor
	 */
	protected EmailerAccessor $emailEngine;

	/**
	 * @var ConstantManagerAccessor
	 */
	protected ConstantManagerAccessor $constant;

	/**
	 * BaseEmail constructor.
	 * @param EmailerAccessor $emailEngine
	 * @param ConstantManagerAccessor $constant
	 */
	public function __construct(EmailerAccessor $emailEngine, ConstantManagerAccessor $constant)
	{
		$this->emailEngine = $emailEngine;
		$this->constant = $constant;
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return __CLASS__;
	}

	/**
	 * @return string|null
	 */
	public function getDescription(): ?string
	{
		return null;
	}

	/**
	 * @return EmailSchema
	 * @throws ConstantException
	 */
	public function getSchema(): EmailSchema
	{
		$schema = new EmailSchema(new Message);

		$schema->addSystemParameters([
			'projectUrl' => $this->getCredential('base-url'),
			'projectName' => $this->getCredential('name'),
			'projectPhone' => $this->getCredential('phone'),
			'projectEmail' => $this->getCredential('admin-email'),
			'from' => $this->getCredential('from'),
		]);

		return $schema;
	}

	/**
	 * @param Message $message
	 * @throws EmailException
	 */
	protected function sender(Message $message): void
	{
		$this->emailEngine->get()->send($message);
	}

	/**
	 * @param string $key
	 * @return string|null
	 * @throws ConstantException
	 */
	protected function getCredential(string $key): ?string
	{
		return $this->constant->get()->get($key, 'project');
	}

}