<?php

declare(strict_types=1);

namespace MatiCore\Email;

/**
 * Class EmailException
 * @package MatiCore\Email
 */
class EmailException extends \Exception
{

	/**
	 * @param string $key
	 * @throws EmailException
	 */
	public static function unknownAttachmentKey(string $key): void
	{
		throw new self('Unknown attachment key "' . $key . '".');
	}

	/**
	 * @throws EmailException
	 */
	public static function missingParameterFrom(): void
	{
		throw new self('Parameter "from" is required.');
	}

	/**
	 * @throws EmailException
	 */
	public static function missingParameterTo(): void
	{
		throw new self('Parameter "to" is required.');
	}

	/**
	 * @param string $typeName
	 * @param string $appDir
	 * @throws EmailException
	 */
	public static function missingTemplate(string $typeName, string $appDir): void
	{
		throw new self(
			'Email template to mail "' . $typeName . '" does not exist.' . "\n"
			. 'Missing template "' . $appDir . '/templates/' . $typeName . '.mjml".'
		);
	}

	/**
	 * @param string $templatePath
	 * @throws EmailException
	 */
	public static function missingTemplateFile(string $templatePath): void
	{
		throw new self('File template not found in "' . $templatePath . '"');
	}

	/**
	 * @param string $fileType
	 * @param string $templatePath
	 * @throws EmailException
	 */
	public static function missingRendererForFile(string $fileType, string $templatePath): void
	{
		throw new self('Renderer for "' . $fileType . '" does not exist.' . "\n"
			. 'Template file "' . $templatePath . '".');
	}

	/**
	 * @param string $fileType
	 * @throws EmailException
	 */
	public static function missingRenderer(string $fileType): void
	{
		throw new self('Renderer "' . $fileType . '" does not exist. '
			. 'Did you install package?');
	}

	/**
	 * @param string $fileType
	 * @param \Exception $e
	 * @throws EmailException
	 */
	public static function rendererNotExists(string $fileType, \Exception $e):void
	{
		throw new self('Renderer for "' . $fileType . '" does not exist.', $e->getCode(), $e);
	}

}