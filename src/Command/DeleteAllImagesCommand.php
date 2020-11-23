<?php

namespace App\Command;

use App\Repository\ImageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class DeleteAllImagesCommand extends Command
{
    protected static $defaultName = 'images:delete';
    
    /** @var $imageRepository App\Repository\ImageRepository */
    private $imageRepository;

    /** @var $entityManager Doctrine\ORM\EntityManagerInterface */
    private $entityManager;

    public function __construct(ImageRepository $imageRepository, EntityManagerInterface $entityManager)
    {
        $this->imageRepository = $imageRepository;
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Deletes all Images.')
            ->setHelp('Deletes all Image entities and their associated uploaded files.');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Are you sure you want to delete ALL images? ', false);
        if (!$helper->ask($input, $output, $question)) {
            return Command::SUCCESS;
            $output->writeln('Aborting.');
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