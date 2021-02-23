<?php

declare(strict_types=1);

namespace MatiCore\Email;

/**
 * Trait InjectEmailer
 * @package MatiCore\Email
 */
trait InjectEmailer
{

	/**
	 * @var Emailer
	 * @inject
	 */
	public $emailer;

}