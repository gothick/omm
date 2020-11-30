<?php

namespace App\Controller\Admin;

use App\Form\SettingsType;
use App\Repository\SettingsRepository;
use App\Service\SettingsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/settings", name="admin_settings_")
 */
class SettingsController extends AbstractController
{
    /**
     * @Route("/", name="index")
     */
    public function index(SettingsService $settingsService): Response
    {
        $settings = $settingsService->getSettings();
        return $this->render('admin/settings/index.html.twig', [
            'settings' => $settings,
        ]);
    }

    /**
     * @Route("/edit", name="edit")
     */
    public function edit(Request $request, SettingsService $settingsService): Response
    {
        $settings = $settingsService->getSettings();

        $form = $this->createForm(SettingsType::class, $settings);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            // It seems to be safe to redirect to show with an ID even after
            // deletion.
            return $this->redirectToRoute('admin_settings_index');
        }

        return $this->render('admin/settings/edit.html.twig', [
            'settings' => $settings,
            'form' => $form->createView(),
        ]);
    }
}
