<?php

namespace App\Command;

use App\Repository\ImageRepository;
use App\Repository\WanderRepository;
use App\Service\GpxService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class WanderUpdateFromGpxCommand extends Command
{
    protected static $defaultName = 'wander:updatefromgpx';

    /** @var WanderRepository */
    private $wanderRepository;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var GpxService */
    private $gpxService;

    public function __construct(WanderRepository $wanderRepository, EntityManagerInterface $entityManager, GpxService $gpxService)
    {
        $this->wanderRepository = $wanderRepository;
        $this->entityManager = $entityManager;
        $this->gpxService = $gpxService;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Updates Wander data (including Google Polyline cache) with GPX information on all wanders.')
            ->setHelp('Updates Wander from GPX data on all Wanders. Overwrites all existing data.');
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Are you sure you want to update all wanders based on their GPX track? ', false);
        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('Aborting.');
            return Command::SUCCESS;
        }

        $wanders = $this->wanderRepository->findAll();
        $count = count($wanders);
        $output->writeln('Updating ' . $count . ' wanders');

        $progressBar = new ProgressBar($output, $count);
        $progressBar->start();

        foreach ($wanders as $wander) {
            $this->gpxService->updateWanderFromGpx($wander);
            $this->entityManager->persist($wander);
            $progressBar->advance();
        }
        $this->entityManager->flush();
        $progressBar->finish();

        return Command::SUCCESS;

    }
}