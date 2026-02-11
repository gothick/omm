<?php

namespace App\Command;

use App\Entity\Image;
use App\Message\RecogniseImage;
use App\Repository\WanderRepository;
use App\Service\ImaggaService;
use Doctrine\ORM\EntityManagerInterface;
use ErrorException;
use Exception;
use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'images:tag', description: 'Retrieve and apply imagga tags to all images in a Wander')]
class ImagesTagCommand extends Command
{
    public function __construct(
        //Client $imaggaClient,
        private readonly WanderRepository $wanderRepository,
        private readonly MessageBusInterface $messageBus
        )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('id', InputArgument::REQUIRED, 'Wander ID')
            // TODO: Add option to overwrite existing tags
            ->addOption('overwrite', null, InputOption::VALUE_NONE, 'Overwrite existing tags')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $overwrite = (bool) $input->getOption('overwrite');

        $id = filter_var($input->getArgument('id'), FILTER_VALIDATE_INT, ['min_range' => 0]);
        if ($id === false) {
            $output->writeln('id must be an integer.');
            return Command::FAILURE;
        }

        $wander = $this->wanderRepository->find($id);
        if ($wander == null) {
            $io->error("Failed to find wander $id");
            return Command::FAILURE;
        }

        $images = $wander->getImages();
        $progressBar = new ProgressBar($output, count($images));
        $progressBar->start();
        foreach ($images as $image) {
            $imageId = $image->getId();
            if ($imageId === null) {
                $progressBar->advance();
                continue;
            }

            $this->messageBus->dispatch(new RecogniseImage($imageId, $overwrite));
            $progressBar->advance();
        }

        $progressBar->finish();

        $io->success("Tagged the wander's images.");

        return Command::SUCCESS;
    }
}
