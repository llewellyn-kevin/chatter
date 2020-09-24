<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Form\PostType;
use App\Repository\PostRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/post")
 */
class PostController extends AbstractController
{
    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * @Route("/", name="post_index", methods={"GET"})
     */
    public function index(PostRepository $postRepository): Response
    {
        // Verify the user is authenticated before showing the feed
        $authenticated = $this->session->get('authenticated');
        if (!isset($authenticated) || !$authenticated) {
            return $this->render('post/unauthorized.html.twig');
        }
        $user = $this->session->get('user');

        return $this->render('post/index.html.twig', [
            'posts' => $postRepository->findBy([], array('created_at' => 'DESC')),
            'user' => $user,
        ]);
    }

    /**
     * @Route("/new", name="post_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        // Verify the user is authenticated before showing the feed
        $authenticated = $this->session->get('authenticated');
        if (!isset($authenticated) || !$authenticated) {
            return $this->render('post/unauthorized.html.twig');
        }
        $uid = $this->session->get('user')['id'];

        // Determine who the user is for Post Entity FK
        $user = $this->getDoctrine()
            ->getRepository(User::class)
            ->find($uid);

        $post = new Post();
        $post->setUser($user);
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        // Handle POST
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($post);
            $entityManager->flush();

            return $this->redirectToRoute('post_index');
        }

        // Handle GET
        return $this->render('post/new.html.twig', [
            'post' => $post,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="post_show", methods={"GET"})
     */
    public function show(Post $post): Response
    {
        // Verify the user is authenticated before showing the feed
        $authenticated = $this->session->get('authenticated');
        if (!isset($authenticated) || !$authenticated) {
            return $this->render('post/unauthorized.html.twig');
        }

        return $this->render('post/show.html.twig', [
            'post' => $post,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="post_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Post $post): Response
    {
        // Verify the user is authenticated before showing the feed
        $authenticated = $this->session->get('authenticated');
        if (!isset($authenticated) || !$authenticated) {
            return $this->render('post/unauthorized.html.twig');
        }

        // Verify the user is the owner of the post
        if ($this->session->get('user')['id'] != $post->getUser()->getId()) {
            return $this->render('post/wrong_post.html.twig');
        }

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        // Handle POST
        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('post_index');
        }

        // Handle GET
        return $this->render('post/edit.html.twig', [
            'post' => $post,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="post_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Post $post): Response
    {
        // Verify the user is authenticated before showing the feed
        $authenticated = $this->session->get('authenticated');
        if (!isset($authenticated) || !$authenticated) {
            return $this->render('post/unauthorized.html.twig');
        }

        // Verify the user is the owner of the post
        if ($this->session->get('user')['id'] != $post->getUser()->getId()) {
            return $this->render('post/wrong_post.html.twig');
        }

        if ($this->isCsrfTokenValid('delete'.$post->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($post);
            $entityManager->flush();
        }

        return $this->redirectToRoute('post_index');
    }
}
