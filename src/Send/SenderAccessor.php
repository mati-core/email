<?php

declare(strict_types=1);

namespace MatiCore\Email;


/**
 * Interface SenderAccessor
 * @package MatiCore\Email
 */
interface SenderAccessor
{

	/**
	 * @return Sender
	 */
	public function get(): Sender;

}