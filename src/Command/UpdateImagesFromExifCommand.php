<?php

namespace App\Command;

use App\Repository\ImageRepository;
use App\Service\ImageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class UpdateImagesFromExifCommand extends Command
{
    protected static $defaultName = 'images:updatefromexif';

    /** @var ImageRepository */
    private $imageRepository;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var ImageService */
    private $imageService;

    public function __construct(ImageRepository $imageRepository, EntityManagerInterface $entityManager, ImageService $imageService)
    {
        $this->imageRepository = $imageRepository;
        $this->entityManager = $entityManager;
        $this->imageService = $imageService;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Updates all images based on their EXIF information.')
            ->setHelp('Updates image properties based on their EXIF information, for all images. Overwrites existing data, except for related wanders.')
            ->addOption('update-wanders', null, InputOption::VALUE_NONE, 'Find related wanders by matching times, and add relationships.');
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $updateWanders = $input->getOption('update-wanders');

        $helper = $this->getHelper('question');

        if ($updateWanders) {
            $questionText = 'Are you sure you want to update properties (*including* related Wanders) for all images based on their EXIF data? ';
        } else {
            $questionText = 'Are you sure you want to update properties (except related Wanders) for all images based on their EXIF data? ';
        }

        $question = new ConfirmationQuestion($questionText, false);
        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('Aborting.');
            return Command::SUCCESS;
        }

        $images = $this->imageRepository->findAll();
        $count = count($images);
        $output->writeln('Updating ' . $count . ' images');

        $progressBar = new ProgressBar($output, $count);
        $progressBar->start();

        foreach ($images as $image) {
            $this->imageService->setPropertiesFromEXIF($image, $updateWanders);
            $this->entityManager->persist($image);
            $progressBar->advance();
        }
        $this->entityManager->flush();
        $progressBar->finish();
        $output->writeln("\nImages updated.");
        return Command::SUCCESS;
    }
}