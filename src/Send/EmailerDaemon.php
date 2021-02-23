<?php

declare(strict_types=1);

namespace MatiCore\Email\Command;


use MatiCore\Email\QueueProcess;
use MatiCore\Utils\Date;
use MatiCore\Utils\Time;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tracy\Debugger;

/**
 * Class EmailerDaemon
 * @package MatiCore\Email\Command
 */
class EmailerDaemon extends Command
{

	/**
	 * @var QueueProcess
	 */
	private $emailerQueueProcess;

	/**
	 * @param QueueProcess $emailerQueueProcess
	 */
	public function __construct(QueueProcess $emailerQueueProcess)
	{
		parent::__construct();
		$this->emailerQueueProcess = $emailerQueueProcess;
	}

	protected function configure(): void
	{
		$this->setName('app:emailer-daemon')->setDescription('Run daemon for sending mails from queue.');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		try {
			$start = microtime(true);

			$this->emailerQueueProcess->setup(
				295, // timeout
				0.3, // emailDelay
				2 // checkIterationDelay
			);
			$this->emailerQueueProcess->run();

			$output->writeln(Date::getDateTimeIso() . ' [' . Time::formatDurationFrom((int) $start) . ']');
			$output->writeln('');

			return 0;
		} catch (\Throwable $e) {
			Debugger::log($e);
			$output->writeln('<error>' . $e->getMessage() . '</error>');

			return 1;
		}
	}

}
