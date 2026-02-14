<?php

namespace App\Command;

use App\Repository\ImageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'images:delete', description: 'Deletes all images.', help: <<<'TXT'
Deletes all Image entities and their associated uploaded files.
TXT)]
class DeleteAllImagesCommand extends Command
{
    public function __construct(private readonly ImageRepository $imageRepository, private readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Are you sure you want to delete ALL images? ', false);
        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('Aborting.');
            return Command::SUCCESS; // Well, technically I think it's not a failure.
        }

        $images = $this->imageRepository->findAll();
        $count = count($images);
        $output->writeln('Deleting ' . $count . ' images');

        $progressBar = new ProgressBar($output, $count);
        $progressBar->start();

        foreach ($images as $image) {
            $this->entityManager->remove($image);
            $progressBar->advance();
        }

        $this->entityManager->flush();
        $progressBar->finish();

        return Command::SUCCESS;

    }
}
