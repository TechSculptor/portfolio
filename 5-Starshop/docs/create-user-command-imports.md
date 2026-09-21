# Imports of [CreateUserCommand.php](../src/Command/CreateUserCommand.php)

ORM = Object-Relational Mapping
DQL = Data Query Language

- `App\Entity\User` — the Doctrine entity representing a user, instantiated to create the new record.
- `App\Repository\UserRepository` — the Doctrine repository used to check that no user already exists with the same email.
- `Doctrine\ORM\EntityManagerInterface` — the Doctrine entity manager, used to persist and save (`flush`) the new user in the database.
- `Symfony\Component\Console\Attribute\AsCommand` — the PHP attribute that declares this class as a console command (name, description).
- `Symfony\Component\Console\Command\Command` — the base class of Symfony commands, providing `execute()`, `configure()` and the `SUCCESS`/`FAILURE` constants.
- `Symfony\Component\Console\Input\InputArgument` — defines the type of a command argument (here `email`, required).
- `Symfony\Component\Console\Input\InputInterface` — the interface giving access to the arguments and options passed to the command when it runs.
- `Symfony\Component\Console\Input\InputOption` — defines the type of a command option (here `--password` and `--admin`).
- `Symfony\Component\Console\Output\OutputInterface` — the interface used to write text to the terminal.
- `Symfony\Component\Console\Style\SymfonyStyle` — a helper that nicely formats console output (success/error messages, tables, warnings).
- `Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface` — the Symfony service used to hash the plain password before storing it.
