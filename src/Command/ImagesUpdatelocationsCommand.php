<?php

namespace App\Command;

use App\Repository\ImageRepository;
use App\Service\LocationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ImagesUpdatelocationsCommand extends Command
{
    protected static $defaultName = 'images:updatelocations';
    protected static $defaultDescription = "Updates images whose locations aren't set using our location service.";

    /** @var ImageRepository */
    private $imageRepository;

    /** @var LocationService */
    private $locationService;

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(
        ImageRepository $imageRepository,
        LocationService $locationService,
        EntityManagerInterface $entityManager
    )
    {
        $this->imageRepository = $imageRepository;
        $this->locationService = $locationService;
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->info("Attempting to add locations to images missing locations.");

        $images = $this->imageRepository->findWithNoLocation();
        $total = count($images);
        $success = 0;
        $failure = 0;

        $progressBar = new ProgressBar($output, $total);
        $progressBar->start();
        foreach ($images as $image) {
            $result = $this->locationService->setImageLocation($image, true);
            if ($result) {
                $success++;
            } else {
                $failure++;
            }
            $this->entityManager->persist($image);
            $this->entityManager->flush(); // It actually seems faster to flush in the loop, rather than afterwards. Odd.
            $progressBar->advance();
        }
        $progressBar->finish();

        $io->success("Tried to locate $total images. Success: $success. Failure: $failure.");

        return Command::SUCCESS;
    }
}
