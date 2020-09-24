<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/user")
 */
class UserController extends AbstractController
{
    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * @Route("/sign-in", name="sign_in", methods={"GET","POST"})
     */
    public function signIn(Request $request): Response
    {
        // Abbreviate reference to request object
        $u = $request->request->get('user');
        if(isset($u)) {
            $username = $u['username'];

            $user = $this->getDoctrine()
                ->getRepository(User::class)
                ->findOneBy(['username' => $username]);

            // Check if valid authentication
            if (isset($user)) {
                dump($request->request->get('user')['password']);
                $pass = password_hash(
                    $request->request->get('user')['password'], 
                    PASSWORD_DEFAULT
                );
                dump($pass);
                $hash = $user->getPassword();
                dump($hash);

                if (password_verify($request->request->get('user')['password'], $hash)) {
                    // Authenticate session and send to feed
                    $this->session->set('authenticated', true);
                    $this->session->set('user', [
                        'id' => $user->getId(),
                        'username' => $user->getUsername(),
                    ]);

                    return $this->redirectToRoute('post_index');
                } else {
                    // Bad password
                    $this->addFlash(
                        'error',
                        'Incorrect username or password. Try again.'
                    );
                }
            } else {
                // Bad username
                $this->addFlash(
                    'error',
                    'Incorrect username or password. Try again.'
                );
            }
        }

        // Show the form
        $user = new User();
        $form = $this->createForm(UserType::class, $user);

        return $this->render('user/login.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/sign-up", name="sign_up", methods={"GET","POST"})
     */
    public function signUp(Request $request): Response
    {
        // Handle valid POST
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $hash = password_hash($user->getPassword(), PASSWORD_DEFAULT);
            $user->setPassword($hash);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            $this->session->set('authenticated', true);
            $this->session->set('user', [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
            ]);

            return $this->redirectToRoute('post_index');
        }

        // Handle GET
        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/sign-out", name="sign_out", methods={"GET"})
     */
    public function signOut(Request $request): Response
    {
        $this->session->set('authenticated', false);
        return $this->redirectToRoute('home');
    }
}
