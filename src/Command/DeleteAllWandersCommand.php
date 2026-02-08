<?php

namespace App\Command;

use App\Repository\WanderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'wanders:delete', description: 'Deletes all wanders.')]
class DeleteAllWandersCommand extends Command
{
    public function __construct(private readonly WanderRepository $wanderRepository, private readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp('Deletes all Wander entities and their associated uploaded files.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Are you sure you want to delete ALL wanders? ', false);
        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('Aborting.');
            return Command::SUCCESS;
        }

        $wanders = $this->wanderRepository->findAll();
        $count = count($wanders);
        $output->writeln('Deleting ' . $count . ' images');

        $progressBar = new ProgressBar($output, $count);
        $progressBar->start();

        foreach ($wanders as $wander) {
            $this->entityManager->remove($wander);
            $progressBar->advance();
        }
        $this->entityManager->flush();
        $progressBar->finish();

        return Command::SUCCESS;

    }
}
