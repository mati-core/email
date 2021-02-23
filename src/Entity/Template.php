<?php

declare(strict_types=1);

namespace MatiCore\Email\Entity;


use Baraja\Doctrine\UUID\UuidIdentifier;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Nette\SmartObject;
use Nette\Utils\DateTime;

/**
 * @ORM\Entity()
 * @ORM\Table(name="email__emailer_template")
 */
class Template
{

	use SmartObject;
	use UuidIdentifier;

	/**
	 * @var array<Email>|ArrayCollection|PersistentCollection|Collection
	 * @ORM\OneToMany(targetEntity="Email", mappedBy="template")
	 */
	private array $emails;

	/**
	 * @var string
	 * @ORM\Column(type="string", unique=true)
	 */
	private string $slug;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=2048)
	 */
	private string $path;

	/**
	 * @var int
	 * @ORM\Column(type="integer")
	 */
	private int $maxAllowedAttempts;

	/**
	 * @var string|null
	 * @ORM\Column(type="string", nullable=true)
	 */
	private string|null $note;

	/**
	 * @var \DateTime
	 * @ORM\Column(type="datetime")
	 */
	private \DateTime $dateTimeInserted;

	/**
	 * Template constructor.
	 * @param string $slug
	 * @param string $path
	 * @param int $maxAllowedAttempts
	 * @throws \Exception
	 */
	public function __construct(string $slug, string $path, int $maxAllowedAttempts)
	{
		$this->slug = $slug;
		$this->path = $path;
		$this->maxAllowedAttempts = $maxAllowedAttempts;
		$this->dateTimeInserted = DateTime::from('now');
		$this->emails = new ArrayCollection;
	}

	/**
	 * @return array<Email>
	 */
	public function getEmails(): array
	{
		return $this->emails;
	}

	/**
	 * @param Email $email
	 */
	public function addEmail(Email $email): void
	{
		$this->emails[] = $email;
	}

	/**
	 * @return string
	 */
	public function getSlug(): string
	{
		return $this->slug;
	}

	/**
	 * @param string $slug
	 */
	public function setSlug(string $slug): void
	{
		$this->slug = $slug;
	}

	/**
	 * @return string
	 */
	public function getPath(): string
	{
		return $this->path;
	}

	/**
	 * @param string $path
	 */
	public function setPath(string $path): void
	{
		$this->path = $path;
	}

	/**
	 * @return int
	 */
	public function getMaxAllowedAttempts(): int
	{
		return $this->maxAllowedAttempts ?? 5;
	}

	/**
	 * @param int $maxAllowedAttempts
	 */
	public function setMaxAllowedAttempts(int $maxAllowedAttempts): void
	{
		$this->maxAllowedAttempts = $maxAllowedAttempts;
	}

	/**
	 * @return string|null
	 */
	public function getNote(): ?string
	{
		return $this->note;
	}

	/**
	 * @param string|null $note
	 */
	public function setNote(?string $note): void
	{
		$this->note = $note;
	}

	/**
	 * @return \DateTime
	 */
	public function getDateTimeInserted(): \DateTime
	{
		return $this->dateTimeInserted;
	}

}