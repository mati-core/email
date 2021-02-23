<?php

declare(strict_types=1);

namespace MatiCore\Email\Mjml;


/**
 * Class MjmlRendererException
 * @package MatiCore\Email\Mjml
 */
class MjmlRendererException extends \Exception
{

	/**
	 * @var string
	 */
	private string $template;

	/**
	 * @param string $message
	 * @param string $template
	 * @param int $line
	 */
	public function __construct(string $message, string $template, int $line)
	{
		parent::__construct($message, 500);
		$this->template = $template;
		$this->line = $line;
	}

	/**
	 * @param string $message
	 * @param string $template
	 * @param int $line
	 * @throws MjmlRendererException
	 */
	public static function processError(string $message, string $template = '', int $line = 1): void
	{
		throw new self($message, $template, $line);
	}

	/**
	 * @return string
	 */
	public function getTemplate(): string
	{
		return $this->template;
	}

}