<?php

namespace App\Command;

use App\Repository\ImageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'images:deletenewerthan',
    description: 'Deletes images newer than a certain id.'
)]
class DeleteImagesNewerThanCommand extends Command
{
    /** @var ImageRepository */
    private $imageRepository;

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(ImageRepository $imageRepository, EntityManagerInterface $entityManager)
    {
        $this->imageRepository = $imageRepository;
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Deletes specific Image entities and their associated uploaded files.')
            ->addArgument('id', InputArgument::REQUIRED, 'Image ID. Images with IDs from this ID onward will be deleted.');
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');
        $id = filter_var($input->getArgument('id'), FILTER_VALIDATE_INT, ['min_range' => 0]);
        if ($id === false) {
            $output->writeln('id must be an integer.');
            return Command::FAILURE;
        }

        $images = $this->imageRepository->findFromIdOnwards($id);
        $count = count($images);

        $question = new ConfirmationQuestion('Are you sure you want to delete the ' . $count . ' images from image ' . $id . ' onwards? ', false);
        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('Aborting.');
            return Command::SUCCESS; // Well, technically I think it's not a failure.
        }
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
