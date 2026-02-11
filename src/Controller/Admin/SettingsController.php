<?php

namespace App\Controller\Admin;

use App\Form\SettingsType;
use App\Repository\SettingsRepository;
use App\Service\SettingsService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/admin/settings', name: 'admin_settings_')]
class SettingsController extends AbstractController
{
    public function __construct(private readonly \App\Service\SettingsService $settingsService, private readonly \Doctrine\Persistence\ManagerRegistry $managerRegistry)
    {
    }

    #[Route(path: '/', name: 'index')]
    public function index(): Response
    {
        $settings = $this->settingsService->getSettings();
        return $this->render('admin/settings/index.html.twig', [
            'settings' => $settings,
        ]);
    }

    #[Route(path: '/edit', name: 'edit')]
    public function edit(
        Request $request
    ): Response {
        $settings = $this->settingsService->getSettings();

        $form = $this->createForm(SettingsType::class, $settings);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->managerRegistry->getManager()->flush();

            // It seems to be safe to redirect to show with an ID even after
            // deletion.
            return $this->redirectToRoute('admin_settings_index');
        }

        return $this->render('admin/settings/edit.html.twig', [
            'settings' => $settings,
            'form' => $form,
        ]);
    }
}
