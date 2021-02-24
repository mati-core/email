<?php

declare(strict_types=1);

namespace MatiCore\Email;


use Nette\Mail\Message as NetteMessage;
use Nette\Utils\Strings;


/**
 * Class Message
 * @package MatiCore\Email
 */
class Message extends NetteMessage
{

	/**
	 * @var array(array<string>)
	 */
	protected array $attachmentsPaths = [];

	/**
	 * @var string
	 */
	protected string $sendEarliestAt = 'now';

	/**
	 * @param string $email
	 * @param string|null $name
	 * @return Message
	 */
	public function addTo(string $email, string $name = null): self
	{
		if ($this->getHeader('To') === null) {
			parent::addTo($email, $name);
		} else {
			$this->addCc($email, $name);
		}

		return $this;
	}

	/**
	 * @return Message
	 */
	public function deleteAllAttachments(): self
	{
		$this->attachmentsPaths = [];

		return $this;
	}

	/**
	 * @return array(array<string>)
	 */
	public function getAttachmentPaths(): array
	{
		return $this->attachmentsPaths;
	}

	/**
	 * @param string $filePath
	 * @param string|null $fileName
	 * @return Message
	 */
	public function addAttachmentPath(string $filePath, ?string $fileName = null): self
	{
		if ($fileName === null) {
			$fileName = (string) preg_replace('/^.*\/([^\/]+)$/', '$1', $filePath);
		}

		$this->attachmentsPaths[] = [
			'path' => $filePath,
			'fileName' => (string) preg_replace_callback('/^(.+)(\.[^\.]+)$/', static function (array $match): string {
				return Strings::webalize($match[1]) . $match[2];
			}, $fileName),
		];

		return $this;
	}

	/**
	 * @param int $key
	 * @param string $newPath
	 * @return Message
	 * @throws EmailException
	 */
	public function updateAttachmentPath(int $key, string $newPath): self
	{
		if (!isset($this->attachmentsPaths[$key])) {
			EmailException::unknownAttachmentKey((string) $key);
		}

		$this->attachmentsPaths[$key] = [
			'path' => $newPath,
		];

		return $this;
	}

	/**
	 * @param string $html
	 * @return string
	 */
	protected function buildText(string $html): string
	{
		return Html2Text::convertHTMLToPlainText($html);
	}

	/**
	 * @return string
	 */
	public function getSendEarliestAt(): string
	{
		return $this->sendEarliestAt;
	}

	/**
	 * @param string $sendEarliestAt
	 */
	public function setSendEarliestAt(string $sendEarliestAt): void
	{
		$this->sendEarliestAt = $sendEarliestAt;
	}

}
