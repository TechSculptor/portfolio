# Imports de [CreateUserCommand.php](CreateUserCommand.php)

ORM = Object-Relational Mapping
DQL = Data Query Language

- `App\Entity\User` — l'entité Doctrine représentant un utilisateur, instanciée pour créer le nouvel enregistrement.
- `App\Repository\UserRepository` — le repository Doctrine utilisé pour vérifier qu'aucun utilisateur n'existe déjà avec le même email.
- `Doctrine\ORM\EntityManagerInterface` — le gestionnaire d'entités Doctrine, utilisé pour persister et sauvegarder (`flush`) le nouvel utilisateur en base.
- `Symfony\Component\Console\Attribute\AsCommand` — l'attribut PHP qui déclare cette classe comme commande console (nom, description).
- `Symfony\Component\Console\Command\Command` — la classe de base des commandes Symfony, fournissant `execute()`, `configure()` et les constantes `SUCCESS`/`FAILURE`.
- `Symfony\Component\Console\Input\InputArgument` — définit le type d'un argument de commande (ici `email`, requis).
- `Symfony\Component\Console\Input\InputInterface` — l'interface donnant accès aux arguments et options passés à la commande lors de son exécution.
- `Symfony\Component\Console\Input\InputOption` — définit le type d'une option de commande (ici `--password` et `--admin`).
- `Symfony\Component\Console\Output\OutputInterface` — l'interface permettant d'écrire du texte dans le terminal.
- `Symfony\Component\Console\Style\SymfonyStyle` — un helper qui formate joliment la sortie console (messages de succès/erreur, tableaux, avertissements).
- `Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface` — le service Symfony utilisé pour hacher le mot de passe en clair avant de le stocker.
