<?php

declare(strict_types=1);

namespace MatiCore\Email\Email;

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

}