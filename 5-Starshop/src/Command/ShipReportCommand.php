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
        // Seule dépendance nécessaire : le repository 
        // StarshipRepository sait construire la requête
        // d'agrégation (COUNT/SUM) qui produit les données du rapport.
        private readonly StarshipRepository $starshipRepository,
    ) {
        parent::__construct();
    }

    // Déclare une unique option facultative --status=<valeur>, utilisée pour
    // filtrer le rapport sur un seul statut de vaisseau 
    // (ex: "active", "docked", "destroyed").
    // Le message d'aide liste dynamiquement les valeurs possibles de l'enum
    // StarshipStatusEnum, pour rester à jour si l'enum change.
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

        // Étape 1 : valider l'option --status si elle est fournie.
        // tryFrom() (contrairement à from()) ne lève pas d'exception : il renvoie
        // null si la chaîne saisie ne correspond à aucun cas de l'enum, ce qui
        // permet d'afficher une erreur propre plutôt qu'un crash.
        $status = null;
        if ($statusOption = $input->getOption('status')) {
            $status = StarshipStatusEnum::tryFrom($statusOption);
            if (!$status) {
                $io->error(sprintf('Unknown status "%s".', $statusOption));

                return Command::FAILURE;
            }
        }

        // Étape 2 : récupérer les données déjà agrégées en base.
        // findAllForReport() exécute une requête DQL qui, pour chaque vaisseau,
        // fait un COUNT des pièces (parts), un COUNT des droïdes embarqués, et un
        // SUM du prix des pièces (groupBy sur l'id du vaisseau) — donc chaque ligne
        // de $rows contient déjà les totaux calculés côté base de données, pas de
        // calcul supplémentaire à faire ici en PHP.
        $rows = $this->starshipRepository->findAllForReport($status);

        // Étape 3 : cas où aucun vaisseau ne correspond au filtre — on sort tôt
        // avec un simple avertissement plutôt qu'un tableau vide.
        if (!$rows) {
            $io->warning('No ships match this report.');

            return Command::SUCCESS;
        }

        // Étape 4 : afficher le rapport sous forme de tableau dans le terminal.
        // array_map() transforme chaque ligne brute ($row, un tableau associatif
        // venant de Doctrine) en un tableau simple de valeurs affichables, dans le
        // même ordre que les en-têtes de colonnes ci-dessus.
        $io->table(
            ['Id', 'Name', 'Status', 'Parts', 'Droids', 'Parts value'],
            array_map(static fn (array $row) => [
                $row['id'],
                $row['name'],
                // $row['status'] est un cas d'enum StarshipStatusEnum, ->value en
                // extrait la chaîne brute (ex: "active") pour l'affichage.
                $row['status']->value,
                $row['partsCount'],
                $row['droidsCount'],
                // Formate le nombre avec un séparateur de milliers "espace" et
                // aucune décimale, puis ajoute le suffixe "credits".
                number_format((float) $row['partsValue'], 0, ',', ' ').' credits',
            ], $rows),
        );

        // Étape 5 : message récapitulatif final (nombre de vaisseaux rapportés).
        $io->success(sprintf('%d ship(s) reported.', count($rows)));

        return Command::SUCCESS;
    }
}
