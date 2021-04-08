<?php

namespace App\Command;

use App\Entity\Image;
use App\Repository\WanderRepository;
use Doctrine\ORM\EntityManagerInterface;
use ErrorException;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ImagesTagCommand extends Command
{
    protected static $defaultName = 'images:tag';
    /** @var HttpClientInterface */
    private $imagga;

    /** @var WanderRepository */
    private $wanderRepository;

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(HttpClientInterface $imagga, WanderRepository $wanderRepository, EntityManagerInterface $entityManager)
    {
        $this->imagga = $imagga;
        $this->wanderRepository = $wanderRepository;
        $this->entityManager = $entityManager;
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

        $images = $wander->getImages();
        $progressBar = new ProgressBar($output, count($images));
        $progressBar->start();
        foreach ($images as $image) {
            $this->tagImage($image, $input, $output);
            $progressBar->advance();
        }
        $this->entityManager->flush();
        $progressBar->finish();

        $io->success("Tagged the wander's images.");

        return Command::SUCCESS;
    }

    protected function tagImage(Image $image, InputInterface $input, OutputInterface $output): void
    {
        $response = $this->imagga->request('GET', 'https://api.imagga.com/v2/tags', [
            'query' => [
                'image_url' => $image->getMediumImageUri(),
                'threshold' => 15.0
            ]
        ]);

        $content = $response->getContent(false);
        $imagga_result = json_decode($content);

        if ($imagga_result->status->type != 'success') {
            $output->writeln('<error>Error returned from imagga:</error>');
            $output->writeln('<error> Type: ' . $imagga_result->status->type . '</error>');
            $output->writeln('<error> Text: ' . $imagga_result->status->text . '</error>');
            throw new ErrorException("Error returned from imagga");
        }

        $tags = [];
        foreach($imagga_result->result->tags as $tag) {
            $tags[] = $tag->tag->en;
        }
        $image->setAutoTags($tags);
        $this->entityManager->persist($image);
    }
}
