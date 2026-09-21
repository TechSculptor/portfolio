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
        // to check that the email is unique
        private UserRepository $userRepository,
        // to persist the user
        private EntityManagerInterface $entityManager,
        // to hash the password
        private UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    // void: return type, the function returns nothing
    // Declares the arguments/options that Symfony Console must parse from the
    // command line, before execute() is called.
    // The configure() method is there to configure the command,
    // but it is not part of the command's execution.
    protected function configure(): void
    {
        $this
            // Required positional argument, e.g. bin/console app:user:create test@test.com
            ->addArgument('email', InputArgument::REQUIRED, 'The email/username for the new user')
            // Option with a value, e.g. --password=secret (otherwise a password is generated)
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Plain password to use instead of generating one')
            // "Flag" option without a value, e.g. --admin (simply present or absent)
            ->addOption('admin', null, InputOption::VALUE_NONE, 'Grant ROLE_ADMIN to this user')
        ;
    }

    // This is the method that is called to run the command.
    protected function execute(
        // InputInterface $input: contains the arguments and options
        // provided by the user
        // OutputInterface $output: lets us write to the console
        InputInterface $input,
        OutputInterface $output,
    ): int {
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

        // persist() prepares $user for insertion (Doctrine now tracks it in memory),
        // but does not send any SQL query yet.
        $this->entityManager->persist($user);
        // flush() compares the state tracked by Doctrine with the database, then actually
        // runs the required SQL queries (here an INSERT) in a transaction.
        // Without this call, $user would never be written to the database.
        $this->entityManager->flush();

        $io->success(sprintf('User "%s" created.', $email));
        $io->table(['Email', 'Password', 'Roles'], [[$email, $plainPassword, implode(', ', $user->getRoles())]]);
        $io->warning('Save this password now, it will not be shown again.');

        return Command::SUCCESS;
    }
}
