<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:user:create',
    description: 'Create a user, generating a strong password if none is given',
)]
class CreateUserCommand extends Command
{
    public function __construct(
        // pour la vérification de l'unicité de l'email
        private UserRepository $userRepository,
        // pour la persistance de l'utilisateur
        private EntityManagerInterface $entityManager,
        // pour le hachage du mot de passe
        private UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    // void : type de retour, la fonction ne retourne rien
    // Déclare les arguments/options que Symfony Console doit parser depuis la
    // ligne de commande, avant que execute() ne soit appelée.
    // La méthode configure() est là pour configurer la commande,
    // mais elle ne fait pas partie de l'exécution de la commande.
    protected function configure(): void
    {
        $this
            // Argument positionnel obligatoire, ex: bin/console app:user:create test@test.com
            ->addArgument('email', InputArgument::REQUIRED, 'The email/username for the new user')
            // Option avec valeur, ex: --password=secret (sinon un mot de passe est généré)
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Plain password to use instead of generating one')
            // Option "flag" sans valeur, ex: --admin (juste présente ou absente)
            ->addOption('admin', null, InputOption::VALUE_NONE, 'Grant ROLE_ADMIN to this user')
        ;
    }

    // Justement c'est cette méthode qui est appelée pour exécuter la commande.
    protected function execute(
        // InputInterface $input : contient les arguments et options 
        // fournis par l'utilisateur
        // OutputInterface $output : permet d'écrire dans la console
        InputInterface $input, 
        OutputInterface $output
        ): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');

        if ($this->userRepository->findOneBy(['email' => $email])) {
            $io->error(sprintf('A user with email "%s" already exists.', $email));

            return Command::FAILURE;
        }

        $plainPassword = $input->getOption('password') ?? bin2hex(random_bytes(12));

        $user = new User();
        $user->setEmail($email);
        $user->setRoles($input->getOption('admin') ? ['ROLE_ADMIN'] : []);
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));

        // persist() prépare $user pour l'insertion (Doctrine le suit désormais en mémoire),
        // mais n'envoie encore aucune requête SQL.
        $this->entityManager->persist($user);
        // flush() compare l'état suivi par Doctrine à la base de données, puis exécute
        // réellement les requêtes SQL nécessaires (ici un INSERT) dans une transaction.
        // Sans cet appel, $user ne serait jamais écrit en base.
        $this->entityManager->flush();

        $io->success(sprintf('User "%s" created.', $email));
        $io->table(['Email', 'Password', 'Roles'], [[$email, $plainPassword, implode(', ', $user->getRoles())]]);
        $io->warning('Save this password now, it will not be shown again.');

        return Command::SUCCESS;
    }
}
