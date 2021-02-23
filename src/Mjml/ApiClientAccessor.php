<?php

declare(strict_types=1);

namespace MatiCore\Email\Mjml;


/**
 * Interface ApiClientAccessor
 * @package MatiCore\Email\Mjml
 */
interface ApiClientAccessor
{

	/**
	 * @return ApiClient
	 */
	public function get(): ApiClient;

}