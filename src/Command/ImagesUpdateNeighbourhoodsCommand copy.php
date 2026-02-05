<?php

namespace App\Command;

use App\Repository\ImageRepository;
use App\Service\NeighbourhoodService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ImagesUpdateNeighbourhoodsCommand extends Command
{
    protected static $defaultName = 'images:updateneighbourhoods';
    protected static $defaultDescription = "Updates images whose neighbourhoods aren't set using our neighbourhood service.";

    /** @var ImageRepository */
    private $imageRepository;

    /** @var NeighbourhoodService */
    private $neighbourhoodService;

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(
        ImageRepository $imageRepository,
        NeighbourhoodService $neighbourhoodService,
        EntityManagerInterface $entityManager
    )
    {
        $this->imageRepository = $imageRepository;
        $this->neighbourhoodService = $neighbourhoodService;
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

        $io->info("Attempting to add neighbourhoods to images missing neighbourhoods.");

        $images = $this->imageRepository->findWithNoNeighbourhoodButHasLatLng();
        $total = count($images);
        $success = 0;
        $failure = 0;

        $progressBar = new ProgressBar($output, $total);
        $progressBar->start();
        foreach ($images as $image) {
            $neighbourhood = $this->neighbourhoodService->getNeighbourhood($image->getLatitude(), $image->getLongitude());
            if ($neighbourhood !== null) {
                $image->setNeighbourhood($neighbourhood);
                $this->entityManager->persist($image);
                $this->entityManager->flush(); // It actually seems faster to flush in the loop, rather than afterwards. Odd.
                $success++;
            } else {
                $failure++;
            }
            $progressBar->advance();
        }
        $progressBar->finish();

        $io->success("Tried to update $total images with neighbourhoods. Success: $success. Failure: $failure.");

        return Command::SUCCESS;
    }
}
