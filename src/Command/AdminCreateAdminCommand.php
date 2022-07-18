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
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminCreateAdminCommand extends Command
{
    protected static $defaultName = 'admin:create-admin';
    protected static $defaultDescription = 'Create your admin user.';


    /** @var UserRepository */
    private $userRepository;

    /** @var UserPasswordHasherInterface */
    private $passwordHasher;

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ) {
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('username', InputArgument::REQUIRED, 'Username');
        //     ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        // ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $username = $input->getArgument('username');

        $user = $this->userRepository->createQueryBuilder('u')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($user !== null) {
            $io->warning("A valid user ({$user->getUsername()}) already exists.");
            return Command::FAILURE;
        }
        $question = new Question("Please enter a password for new user '{$username}': ", 'AcmeDemoBundle');
        $question->setHidden(true);
        $helper = $this->getHelper('question');
        $password = $helper->ask($input, $output, $question);

        try {
            $user = new User();
            $user->setUsername($username);
            $user->setPassword($this->passwordHasher->hashPassword($user, $password));
            $user->setRoles(['ROLE_ADMIN']);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $io->error("Error creating user: {$e->getMessage()}");
            return Command::FAILURE;
        }
        $io->success('Successfully created new user.');

        return Command::SUCCESS;
    }
}
