<?php

declare(strict_types=1);

namespace MatiCore\Email\Entity;


use Baraja\Doctrine\UUID\UuidIdentifier;
use Doctrine\ORM\Mapping as ORM;
use Nette\HtmlStringable;
use Nette\Mail\Message;
use Nette\SmartObject;
use MatiCore\Email\Attachment;

/**
 * @ORM\Entity()
 * @ORM\Table(name="email__emailer_raw")
 */
class EmailRaw implements HtmlStringable
{

	use SmartObject;
	use UuidIdentifier;

	/**
	 * @var Email
	 * @ORM\OneToOne(targetEntity="Email", mappedBy="raw")
	 */
	private Email $email;

	/**
	 * @var string
	 * @ORM\Column(type="string", name="`from`")
	 */
	private string $from;

	/**
	 * @var string
	 * @ORM\Column(type="string", name="`to`")
	 */
	private string $to;

	/**
	 * @var array<string>
	 * @ORM\Column(type="json_array")
	 */
	private array $cc = [];

	/**
	 * @var array<string>
	 * @ORM\Column(type="json_array")
	 */
	private array $bcc = [];

	/**
	 * @var string|null
	 * @ORM\Column(type="string", nullable=true)
	 */
	private string|null $replyTo;

	/**
	 * @var string|null
	 * @ORM\Column(type="string", nullable=true)
	 */
	private string|null $returnPath;

	/**
	 * @var int
	 * @ORM\Column(type="integer")
	 */
	private int $priority = Message::NORMAL;

	/**
	 * @var string
	 * @ORM\Column(type="string")
	 */
	private string $subject;

	/**
	 * @var string|null
	 * @ORM\Column(type="text", nullable=true)
	 */
	private string|null $htmlBody;

	/**
	 * @var string|null
	 * @ORM\Column(type="text", nullable=true)
	 */
	private string|null $textBody;

	/**
	 * Format:
	 * [
	 *   {
	 *     "file": "hello.txt",        // path in storage system
	 *     "content": "Hello world!"   // available string version of file (for example used for .txt files)
	 *   },
	 *   {
	 *     "file": "photo.jpg",
	 *     "content": null
	 *   }
	 * ]
	 *
	 * @var string[]|string[][]
	 * @ORM\Column(type="json_array")
	 */
	private array $attachments = [];

	/**
	 * @param string $from
	 * @param string $to
	 * @param string $subject
	 * @param string|null $htmlBody
	 * @param string|null $textBody
	 */
	public function __construct(
		string $from,
		string $to,
		string $subject,
		?string $htmlBody = null,
		?string $textBody = null
	)
	{
		$this->from = $from;
		$this->to = $to;
		$this->subject = $subject;
		$this->htmlBody = $htmlBody;
		$this->textBody = $textBody;
	}

	/**
	 * @return string
	 */
	public function __toString(): string
	{
		return $this->getHtmlBody() ?? $this->getTextBody() ?? '';
	}

	/**
	 * @return Email
	 */
	public function getEmail(): Email
	{
		return $this->email;
	}

	/**
	 * @param Email $email
	 */
	public function setEmail(Email $email): void
	{
		$this->email = $email;
	}

	/**
	 * @return string
	 */
	public function getFrom(): string
	{
		return $this->from;
	}

	/**
	 * @param string $from
	 */
	public function setFrom(string $from): void
	{
		$this->from = $from;
	}

	/**
	 * @return string
	 */
	public function getTo(): string
	{
		return $this->to;
	}

	/**
	 * @param string $to
	 */
	public function setTo(string $to): void
	{
		$this->to = $to;
	}

	/**
	 * @return string[]
	 */
	public function getCc(): array
	{
		return $this->cc;
	}

	/**
	 * @param string $cc
	 */
	public function addCc(string $cc): void
	{
		$this->cc[] = $cc;
	}

	/**
	 * @return string[]
	 */
	public function getBcc(): array
	{
		return $this->bcc;
	}

	/**
	 * @param string $bcc
	 */
	public function addBcc(string $bcc): void
	{
		$this->bcc[] = $bcc;
	}

	/**
	 * @return string|null
	 */
	public function getReplyTo(): ?string
	{
		return $this->replyTo;
	}

	/**
	 * @param string|null $replyTo
	 */
	public function setReplyTo(?string $replyTo): void
	{
		$this->replyTo = $replyTo;
	}

	/**
	 * @return string|null
	 */
	public function getReturnPath(): ?string
	{
		return $this->returnPath;
	}

	/**
	 * @param string|null $returnPath
	 */
	public function setReturnPath(?string $returnPath): void
	{
		$this->returnPath = $returnPath;
	}

	/**
	 * @return int
	 */
	public function getPriority(): int
	{
		return $this->priority;
	}

	/**
	 * @param int $priority
	 */
	public function setPriority(int $priority): void
	{
		if ($priority < 0) {
			$priority = 0;
		}

		if ($priority > 100) {
			$priority = 100;
		}

		$this->priority = $priority;
	}

	/**
	 * @return string
	 */
	public function getSubject(): string
	{
		return $this->subject;
	}

	/**
	 * @param string $subject
	 */
	public function setSubject(string $subject): void
	{
		$this->subject = $subject;
	}

	/**
	 * @return string|null
	 */
	public function getHtmlBody(): ?string
	{
		return $this->htmlBody;
	}

	/**
	 * @param string|null $htmlBody
	 */
	public function setHtmlBody(?string $htmlBody): void
	{
		$this->htmlBody = $htmlBody;
	}

	/**
	 * @return string|null
	 */
	public function getTextBody(): ?string
	{
		return $this->textBody;
	}

	/**
	 * @param string|null $textBody
	 */
	public function setTextBody(?string $textBody): void
	{
		$this->textBody = $textBody;
	}

	/**
	 * @return bool
	 */
	public function isAttachments(): bool
	{
		return $this->attachments !== [];
	}

	/**
	 * @return Attachment[]
	 */
	public function getAttachments(): array
	{
		$return = [];

		foreach ($this->attachments as $attachment) {
			$return[] = new Attachment(
				$attachment['path'] ?? null,
				is_file($attachment['path']) ? file_get_contents($attachment['path']) : null
			);
		}

		return $return;
	}

	/**
	 * @param string $path
	 * @param string $fileName
	 */
	public function addAttachment(string $path, string $fileName): void
	{
		$this->attachments[] = [
			'path' => $path,
			'fileName' => $fileName,
		];
	}

}