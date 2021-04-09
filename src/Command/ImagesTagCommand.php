<?php

namespace App\Command;

use App\Entity\Image;
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
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ImagesTagCommand extends Command
{
    protected static $defaultName = 'images:tag';
    /** @var ImaggaService */
    private $imaggaService;

    /** @var WanderRepository */
    private $wanderRepository;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var RouterInterface */
    private $router;

    public function __construct(
        //Client $imaggaClient,
        ImaggaService $imaggaService,
        WanderRepository $wanderRepository,
        EntityManagerInterface $entityManager,
        RouterInterface $router
        )
    {
        $this->imaggaService = $imaggaService;
        $this->wanderRepository = $wanderRepository;
        $this->entityManager = $entityManager;
        $this->router = $router;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Retrieve and apply imagga tags to all images in a Wander')
            ->addArgument('id', InputArgument::REQUIRED, 'Wander ID')
            // TODO: Add option to overwrite existing tags
            //->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');
        $io = new SymfonyStyle($input, $output);

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

        // TODO: As we're a Command, we don't have a Request to set these up.
        // Bodge it manually for now. You can configure these in config/services.yml;
        // see https://symfony.com/doc/4.1/console/request_context.html and
        // do that later.

        $context = $this->router->getContext();
        $context->setHost('omm.gothick.org.uk');
        $context->setScheme('https');

        $images = $wander->getImages();
        $progressBar = new ProgressBar($output, count($images));
        $progressBar->start();
        foreach ($images as $image) {
            $this->imaggaService->tagImage($image);
            $progressBar->advance();
        }
        $this->entityManager->flush();
        $progressBar->finish();

        $io->success("Tagged the wander's images.");

        return Command::SUCCESS;
    }
}
