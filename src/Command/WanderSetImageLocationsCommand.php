<?php

namespace App\Command;

use App\Repository\WanderRepository;
use App\Service\ImageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class WanderSetImageLocationsCommand extends Command
{
    protected static $defaultName = 'wander:set-image-locations';
    protected static $defaultDescription = 'Updates Location field from Exif information for all images for a given wander';

    /** @var WanderRepository */
    private $wanderRepository;

    /** @var ImageService */
    private $imageService;

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(
        WanderRepository $wanderRepository,
        ImageService $imageService,
        EntityManagerInterface $entityManager
    )
    {
        $this->wanderRepository = $wanderRepository;
        $this->imageService = $imageService;
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('id', InputArgument::REQUIRED, 'Wander ID')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $id = filter_var($input->getArgument('id'), FILTER_VALIDATE_INT, ['min_range' => 0]);
        if ($id === false) {
            $output->writeln('id must be an integer.');
            return Command::FAILURE;
        }

        $wander = $this->wanderRepository->find($id);
        if ($wander === null) {
            $io->error("Failed to find wander $id");
            return Command::FAILURE;
        }

        $images = $wander->getImages();
        $progressBar = new ProgressBar($output, count($images));
        $progressBar->start();

        foreach ($images as $image) {
            $this->imageService->setLocationFromEXIF($image);
            $this->entityManager->persist($image);
            $this->entityManager->flush();
            $progressBar->advance();
        }
        $progressBar->finish();

        $io->success('Image locations updated.');

        return Command::SUCCESS;
    }
}
