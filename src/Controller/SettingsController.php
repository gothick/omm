<?php

namespace App\Controller;

use App\Form\SettingsType;
use App\Repository\SettingsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SettingsController extends AbstractController
{
    /**
     * @Route("/settings", name="settings_index")
     */
    public function index(SettingsRepository $settingsRepository): Response
    {
        $settings = $settingsRepository->getTheSingleRow();
        return $this->render('settings/index.html.twig', [
            'settings' => $settings,
        ]);
    }

    /**
     * @Route("/settings/edit", name="settings_edit")
     */
    public function edit(Request $request, SettingsRepository $settingsRepository): Response
    {
        $settings = $settingsRepository->getTheSingleRow();

        $form = $this->createForm(SettingsType::class, $settings);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            // It seems to be safe to redirect to show with an ID even after
            // deletion.
            return $this->redirectToRoute('settings_index');
        }

        return $this->render('settings/edit.html.twig', [
            'settings' => $settings,
            'form' => $form->createView(),
        ]);
    }
}
