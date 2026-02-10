<?php

namespace App\Controller;

use App\Form\UserChangePasswordType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/user', name: 'user_')]
class UserController extends AbstractController
{
    #[Route(path: '/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        return $this->render('user/index.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route(path: '/changepassword', name: 'changepassword', methods: ['GET', 'POST'])]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $encoder,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        /** @var \App\Entity\User | null */
        $user = $this->getUser();
        if ($user === null) {
            throw new \Exception("Coudn't get user even though I seem to be authenticated");
        }

        $userInfo = ['plainPassword' => null];

        $form = $this->createForm(UserChangePasswordType::class, $userInfo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $info = $form->getData();
            $plainPassword = $info['plainPassword'];
            // TODO: Password strength validation?
            $password = $encoder->hashPassword($user, $plainPassword);
            $user->setPassword($password);
            $entityManager->flush();

            $this->addFlash(
                'notice',
                'Password changed!'
            );
            return $this->redirectToRoute('user_index');
        }

        return $this->render('user/changepassword.html.twig', [
            'user' => $user,
            'form' => $form
        ]);
    }
}
