<?php

declare(strict_types=1);

namespace MatiCore\Email;


use Nette\Mail\Mailer;
use Nette\Mail\Message as NetteMessage;
use MatiCore\Email\Entity\Email;

/**
 * Interface IEmailer
 * @package MatiCore\Email
 */
interface IEmailer extends Mailer
{

	/**
	 * @param NetteMessage $message
	 * @param string $templateSlug
	 * @param string $sendEarliestAt
	 * @return Email|null
	 */
	public function insertMessageToQueue(NetteMessage $message, string $templateSlug, string $sendEarliestAt = 'now'): ?Email;

}