<?php

declare(strict_types=1);

namespace MatiCore\Email;


use JetBrains\PhpStorm\Pure;

/**
 * Class Attachment
 * @package MatiCore\Email
 */
class Attachment
{

	/**
	 * @var string|null
	 */
	private string|null $file;

	/**
	 * @var string|null
	 */
	private string|null $content;

	/**
	 * @var string|null
	 */
	private string|null $contentType;

	/**
	 * @param string|null $file
	 * @param string|null $content
	 * @param string|null $contentType
	 */
	public function __construct(?string $file, ?string $content, ?string $contentType = null)
	{
		$this->file = $file;
		$this->content = $content;
		$this->contentType = $contentType;
	}

	/**
	 * @return string
	 */
	public function __toString(): string
	{
		return $this->getFile() ?? '';
	}

	/**
	 * @return string
	 */
	public function getFile(): string
	{
		return $this->file;
	}

	/**
	 * @param string $file
	 */
	public function setFile(string $file): void
	{
		$this->file = $file;
	}

	/**
	 * @return string|null
	 */
	public function getContent(): ?string
	{
		return $this->content;
	}

	/**
	 * @param string|null $content
	 */
	public function setContent(?string $content): void
	{
		$this->content = $content;
	}

	/**
	 * @return string|null
	 */
	public function getContentType(): ?string
	{
		return $this->contentType;
	}

	/**
	 * @param string|null $contentType
	 */
	public function setContentType(?string $contentType): void
	{
		$this->contentType = $contentType;
	}

}
