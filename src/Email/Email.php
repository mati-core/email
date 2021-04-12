<?php

declare(strict_types=1);

namespace MatiCore\Email\Email;

use MatiCore\Email\EmailException;

/**
 * Interface Email
 * @package MatiCore\Email\Email
 */
interface Email
{

	/**
	 * @return string
	 */
	public function getName(): string;

	/**
	 * @return string|null
	 */
	public function getDescription(): ?string;

	/**
	 * @return EmailSchema
	 */
	public function getSchema(): EmailSchema;

	/**
	 * @param string|null $lang
	 * @return string|null
	 * @throws EmailException
	 */
	public function getTemplate(?string $lang): ?string;

}