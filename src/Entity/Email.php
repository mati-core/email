<?php

declare(strict_types=1);

namespace MatiCore\Email\Entity;


use Baraja\Doctrine\UUID\UuidIdentifier;
use Doctrine\ORM\Mapping as ORM;
use Nette\SmartObject;
use Nette\Utils\DateTime;
use MatiCore\Utils\Http;

/**
 * @ORM\Entity()
 * @ORM\Table(name="email__email")
 */
class Email
{

	use SmartObject;
	use UuidIdentifier;

	public const STATUS_IN_QUEUE = 'in-queue';
	public const STATUS_NOT_READY_TO_QUEUE = 'not-ready-to-queue';
	public const STATUS_WAITING_FOR_NEXT_ATTEMPT = 'waiting-for-next-attempt';
	public const STATUS_SENT = 'sent';
	public const STATUS_PREPARING_ERROR = 'preparing-error';
	public const STATUS_SENDING_ERROR = 'sending-error';

	/**
	 * @var Template|null
	 * @ORM\ManyToOne(targetEntity="Template", inversedBy="emails")
	 */
	private Template|null $template;

	/**
	 * @var EmailRaw|null
	 * @ORM\OneToOne(targetEntity="EmailRaw", inversedBy="email")
	 */
	private EmailRaw|null $raw;

	/**
	 * @var string
	 * @ORM\Column(
	 *    type="string",
	 *    columnDefinition="ENUM('in-queue','not-ready-to-queue','waiting-for-next-attempt','sent','preparing-error','sending-error')"
	 * )
	 */
	private string $status = self::STATUS_IN_QUEUE;

	/**
	 * If value will be bigger than limit status is changed to 'sending-error'.
	 *
	 * @var int
	 * @ORM\Column(type="smallint")
	 */
	private int $failedAttemptsCount = 0;

	/**
	 * Kolik sekund (s presnosti na ms) trvalo odesilani e-mailu
	 * (jak dlouho trvalo volani $mailer->send($mail), takze spojeni se SMTP atp.).
	 * Slouzi k rychlemu odhaleni potizi s mail serverem.
	 *
	 * @var float|string|null
	 * @ORM\Column(type="decimal", nullable=true)
	 */
	private float|string|null $sendingDuration;

	/**
	 * Kolik sekund (s presnosti na ms) trvala priprava/generovani e-mailu.
	 * Slouzi pro rychle odhaleni problemovych situaci,
	 * kdy generovani nejakych e-mailu muze brutalne brzdit celou frontu
	 *
	 * @var float|string|null
	 * @ORM\Column(type="decimal", nullable=true)
	 */
	private float|string|null $preparingDuration;

	/**
	 * @var string
	 * @ORM\Column(type="string")
	 */
	private string $ip;

	/**
	 * @var array<string>|null
	 * @ORM\Column(type="json_array", nullable=true)
	 */
	private array|null $note;

	/**
	 * Datum, kdy je nejdrive mozne zpravu odeslat (NULL = odeslat hned, jak to bude mozne)
	 *
	 * @var \DateTime|null
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	private \DateTime|null $sendEarliestAt;

	/**
	 * Datum a cas, kdy nejdriv muze dojit k dalsimu pokusu o odeslani e-mailu (v pripade opakovanych pokusu)
	 *
	 * @var \DateTime|null
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	private \DateTime|null $sendEarliestNextAttemptAt;

	/**
	 * @var \DateTime
	 * @ORM\Column(type="datetime")
	 */
	private \DateTime $datetimeInserted;

	/**
	 * Datum, kdy byla zprava opravdu odeslana (NULL = zprava nebyla odeslana)
	 *
	 * @var \DateTime|null
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	private \DateTime|null $datetimeSent;

	/**
	 * Email constructor.
	 * @param EmailRaw $raw
	 * @param Template|null $template
	 * @throws \Exception
	 */
	public function __construct(EmailRaw $raw, ?Template $template = null)
	{
		$this->raw = $raw;
		$this->template = $template;
		$this->ip = Http::userIp();
		$this->datetimeInserted = DateTime::from('now');
	}

	/**
	 * @return array<string>
	 */
	public static function getStatuses(): array
	{
		return [
			self::STATUS_IN_QUEUE,
			self::STATUS_NOT_READY_TO_QUEUE,
			self::STATUS_PREPARING_ERROR,
			self::STATUS_SENDING_ERROR,
			self::STATUS_SENT,
			self::STATUS_WAITING_FOR_NEXT_ATTEMPT,
		];
	}

	/**
	 * @return Template|null
	 */
	public function getTemplate(): ?Template
	{
		return $this->template;
	}

	/**
	 * @return EmailRaw|null
	 */
	public function getRaw(): ?EmailRaw
	{
		return $this->raw;
	}

	/**
	 * @return string
	 */
	public function getStatus(): string
	{
		return $this->status;
	}

	/**
	 * @param string $status
	 */
	public function setStatus(string $status): void
	{
		$this->status = \in_array($status, self::getStatuses(), true)
			? $status
			: self::STATUS_PREPARING_ERROR;
	}

	/**
	 * @return int
	 */
	public function getFailedAttemptsCount(): int
	{
		return $this->failedAttemptsCount;
	}

	/**
	 * @param int $count
	 */
	public function incrementFailedAttemptsCount(int $count = 1): void
	{
		$this->failedAttemptsCount += $count;
	}

	/**
	 * @return float|null
	 */
	public function getSendingDuration(): ?float
	{
		return $this->sendingDuration === null
			? null
			: (float) $this->sendingDuration;
	}

	/**
	 * @param float $sendingDuration
	 */
	public function setSendingDuration(float $sendingDuration): void
	{
		$this->sendingDuration = $sendingDuration;
	}

	/**
	 * @return float|null
	 */
	public function getPreparingDuration(): ?float
	{
		return $this->preparingDuration === null
			? null
			: (float) $this->preparingDuration;
	}

	/**
	 * @param float $preparingDuration
	 */
	public function setPreparingDuration(float $preparingDuration): void
	{
		$this->preparingDuration = $preparingDuration;
	}

	/**
	 * @return string
	 */
	public function getIp(): string
	{
		return $this->ip;
	}

	/**
	 * @return string|null
	 */
	public function getNote(): ?string
	{
		return $this->note === null
			? null
			: implode("\n", $this->note);
	}

	/**
	 * @param string $note
	 */
	public function addNote(string $note): void
	{
		if ($this->note === null) {
			$this->note = [];
		}

		$this->note[] = $note;
	}

	/**
	 * @return \DateTime|null
	 */
	public function getSendEarliestAt(): ?\DateTime
	{
		return $this->sendEarliestAt;
	}

	/**
	 * @param \DateTime|null $sendEarliestAt
	 */
	public function setSendEarliestAt(?\DateTime $sendEarliestAt): void
	{
		$this->sendEarliestAt = $sendEarliestAt;
	}

	/**
	 * @return \DateTime|null
	 */
	public function getSendEarliestNextAttemptAt(): ?\DateTime
	{
		return $this->sendEarliestNextAttemptAt;
	}

	/**
	 * @param \DateTime|null $sendEarliestNextAttemptAt
	 */
	public function setSendEarliestNextAttemptAt(?\DateTime $sendEarliestNextAttemptAt): void
	{
		$this->sendEarliestNextAttemptAt = $sendEarliestNextAttemptAt;
	}

	/**
	 * @return \DateTime
	 */
	public function getDatetimeInserted(): \DateTime
	{
		return $this->datetimeInserted;
	}

	/**
	 * @return \DateTime|null
	 */
	public function getDatetimeSent(): ?\DateTime
	{
		return $this->datetimeSent;
	}

	/**
	 * @param \DateTime|null $datetimeSent
	 */
	public function setDatetimeSent(?\DateTime $datetimeSent): void
	{
		$this->datetimeSent = $datetimeSent;
	}

}