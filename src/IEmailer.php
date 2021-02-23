<?php

declare(strict_types=1);

namespace MatiCore\Email;


use Nette\Mail\IMailer;
use Nette\Mail\Message as NetteMessage;
use MatiCore\Email\Entity\Email;

interface IEmailer extends IMailer
{

	/**
	 * @param NetteMessage $message
	 * @param string $templateSlug
	 * @param string $sendEarliestAt
	 * @return Email|null
	 */
	public function insertMessageToQueue(NetteMessage $message, string $templateSlug, string $sendEarliestAt = 'now'): ?Email;

}