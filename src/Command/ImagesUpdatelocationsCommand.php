<?php

namespace App\Command;

use App\Entity\Image;
use App\Message\GeolocateImage;
use App\Repository\ImageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\RouterInterface;

class ImagesUpdateLocationsCommand extends Command
{
    protected static $defaultName = 'images:updatelocations';

    /** @var ImageRepository */
    private $imageRepository;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var RouterInterface */
    private $router;

    /** @var MessageBusInterface */
    private $messageBus;

    public function __construct(
        ImageRepository $imageRepository,
        EntityManagerInterface $entityManager,
        RouterInterface $router,
        MessageBusInterface $messageBus
        )
    {
        $this->imageRepository = $imageRepository;
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->messageBus = $messageBus;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Add location information to any images missing it, using our location service. This work will be queued, not done immediately.')
            // TODO: Add option to overwrite existing tags
            ->addOption('overwrite', null, InputOption::VALUE_NONE, 'Overwrite existing locations')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $overwrite = (bool) $input->getOption('overwrite');

        $images = $this->imageRepository->findWithHasLatLng();
        $total = count($images);

        $progressBar = new ProgressBar($output, $total);
        $progressBar->start();
        foreach ($images as $image) {
            $imageid = $image->getId();
            if ($imageid === null) {
                throw new \RuntimeException("Image has no ID");
            }
            $this->messageBus->dispatch(new GeolocateImage($imageid, $overwrite));
            $progressBar->advance();
        }
        $progressBar->finish();
        $io->newLine();

        $io->success("Queued work to locate $total images.");
        return Command::SUCCESS;
    }
}
