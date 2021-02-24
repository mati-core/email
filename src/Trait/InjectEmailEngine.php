<?php

declare(strict_types=1);

namespace MatiCore\Email;

/**
 * Trait InjectEmailEngine
 * @package MatiCore\Email
 */
trait InjectEmailEngine
{

	/**
	 * @var EmailerAccessor
	 * @inject
	 */
	public $emailEngine;

}