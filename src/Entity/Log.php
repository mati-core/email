<?php

declare(strict_types=1);

namespace MatiCore\Email\Entity;


use Baraja\Doctrine\UUID\UuidIdentifier;
use Doctrine\ORM\Mapping as ORM;
use Nette\SmartObject;
use Nette\Utils\Strings;

/**
 * @ORM\Entity()
 * @ORM\Table(name="email__emailer_log")
 */
class Log
{

	use SmartObject;
	use UuidIdentifier;

	/**
	 * @var string
	 * @ORM\Column(type="string")
	 */
	private string $level;

	/**
	 * @var string
	 * @ORM\Column(type="string")
	 */
	private string $message;

	/**
	 * @param string $level
	 * @param string $message
	 */
	public function __construct(string $level, string $message)
	{
		$this->level = Strings::upper($level);
		$this->message = $message;
	}

	/**
	 * @return string
	 */
	public function getLevel(): string
	{
		return $this->level;
	}

	/**
	 * @return string
	 */
	public function getMessage(): string
	{
		return $this->message;
	}

}