<?php

declare(strict_types=1);

namespace MatiCore\Email;


/**
 * Interface TemplateRenderer
 * @package MatiCore\Email
 */
interface TemplateRenderer
{

	/**
	 * @param string $templatePath
	 * @param array $parameters
	 * @return string
	 */
	public function render(string $templatePath, array $parameters = []): string;

}