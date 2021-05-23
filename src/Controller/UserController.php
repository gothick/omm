<?php

namespace App\Controller;

use App\Form\UserChangePasswordType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * @Route("/user", name="user_")
 */
class UserController extends AbstractController
{
    /**
     * @Route("/", name="index", methods={"GET"})
     */
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        return $this->render('user/index.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * @Route("/changepassword", name="changepassword", methods={"GET", "POST"})
     */
    public function changePassword(
        Request $request,
        UserPasswordEncoderInterface $encoder,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
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
            $password = $encoder->encodePassword($user, $plainPassword);
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
            'form' => $form->createView()
        ]);
    }
}
