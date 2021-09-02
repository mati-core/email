<?php

declare(strict_types=1);

namespace MatiCore\Email;


use Baraja\Doctrine\EntityManager;
use Baraja\Doctrine\EntityManagerException;
use MatiCore\Email\Entity\Log;
use Tracy\Debugger;

/**
 * Class EmailerLogger
 * @package MatiCore\Email
 */
class EmailerLogger
{

	/**
	 * @var EntityManager
	 */
	private $entityManager;

	/**
	 * @param EntityManager $entityManager
	 */
	public function __construct(EntityManager $entityManager)
	{
		$this->entityManager = $entityManager;
	}

	/**
	 * @param string|int $level
	 * @param string $message
	 */
	public function log($level, string $message): void
	{
		$log = new Log($level, $message);

		try {
			$this->entityManager->persist($log)->getUnitOfWork()->commit($log);
		} catch (EntityManagerException $e) {
			Debugger::log($e);
		}
	}

}