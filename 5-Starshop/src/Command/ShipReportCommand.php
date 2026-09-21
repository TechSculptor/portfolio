<?php

namespace App\Command;

use App\Model\StarshipStatusEnum;
use App\Repository\StarshipRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:ship-report',
    description: 'Prints a summary report of ships: parts count, droid count and total parts value',
)]
class ShipReportCommand extends Command
{
    public function __construct(
        // Only dependency needed: the repository
        // StarshipRepository knows how to build the
        // aggregation query (COUNT/SUM) that produces the report data.
        private readonly StarshipRepository $starshipRepository,
    ) {
        parent::__construct();
    }

    // Declares a single optional --status=<value> option, used to
    // filter the report on a single starship status
    // (e.g. "waiting", "in progress", "completed").
    // The help message dynamically lists the possible values of the enum
    // StarshipStatusEnum, so that it stays up to date if the enum changes.
    protected function configure(): void
    {
        $this->addOption(
            'status',
            null,
            InputOption::VALUE_REQUIRED,
            sprintf('Filter by status (%s)', implode(', ', array_map(static fn (StarshipStatusEnum $s) => $s->value, StarshipStatusEnum::cases()))),
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Step 1: validate the --status option if it is provided.
        // tryFrom() (unlike from()) does not throw an exception: it returns
        // null if the given string matches no case of the enum, which
        // lets us display a clean error instead of crashing.
        $status = null;
        if ($statusOption = $input->getOption('status')) {
            $status = StarshipStatusEnum::tryFrom($statusOption);
            if (!$status) {
                $io->error(sprintf('Unknown status "%s".', $statusOption));

                return Command::FAILURE;
            }
        }

        // Step 2: fetch the data already aggregated in the database.
        // findAllForReport() runs a DQL query that, for each starship,
        // does a COUNT of the parts, a COUNT of the onboard droids, and a
        // SUM of the parts' price (groupBy on the starship id) — so each row
        // of $rows already contains the totals computed on the database side, no
        // extra computation is needed here in PHP.
        $rows = $this->starshipRepository->findAllForReport($status);

        // Step 3: no starship matches the filter — exit early
        // with a simple warning rather than an empty table.
        if (!$rows) {
            $io->warning('No ships match this report.');

            return Command::SUCCESS;
        }

        // Step 4: display the report as a table in the terminal.
        // array_map() turns each raw row ($row, an associative array
        // coming from Doctrine) into a simple array of displayable values, in the
        // same order as the column headers above.
        $io->table(
            ['Id', 'Name', 'Status', 'Parts', 'Droids', 'Parts value'],
            array_map(static fn (array $row) => [
                $row['id'],
                $row['name'],
                // $row['status'] is a StarshipStatusEnum case, ->value extracts
                // the raw string (e.g. "waiting") for display.
                $row['status']->value,
                $row['partsCount'],
                $row['droidsCount'],
                // Formats the number with a "space" thousands separator and
                // no decimals, then appends the "credits" suffix.
                number_format((float) $row['partsValue'], 0, ',', ' ').' credits',
            ], $rows),
        );

        // Step 5: final summary message (number of starships reported).
        $io->success(sprintf('%d ship(s) reported.', count($rows)));

        return Command::SUCCESS;
    }
}
