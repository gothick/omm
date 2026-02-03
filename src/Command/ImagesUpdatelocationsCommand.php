<?php

namespace App\Command;

use App\Entity\Image;
use App\Message\GeolocateImage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;
use App\Repository\ImageRepository;

class ImagesUpdateLocationsCommand extends Command
{
    protected static $defaultName = 'images:updatelocations';

    /** @var MessageBusInterface */
    private $messageBus;

    /** @var ImageRepository */
    private $imageRepository;

    public function __construct(
        MessageBusInterface $messageBus,
        ImageRepository $imageRepository
        )
    {
        $this->messageBus = $messageBus;
        $this->imageRepository = $imageRepository;

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

        $images = $overwrite ? $this->imageRepository->findWithHasLatLng() : $this->imageRepository->findWithNoStreetButHasLatLng();
        $total = count($images);

        $progressBar = new ProgressBar($output, $total);
        $progressBar->start();
        /** @var Image $image */
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
