<?php

declare(strict_types=1);

namespace MatiCore\Email;


interface EmailerAccessor
{

	/**
	 * @return Emailer
	 */
	public function get(): Emailer;

}