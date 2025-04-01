<?php
// src/Controller/SettingsController.php

namespace App\Controller;

use App\Entity\Configuration;
use App\Form\ConfigurationType;
use App\Entity\Utilisateur;
use App\Form\GestionRoleType;
use App\Form\UtilisateurCreateType;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/settings')]
class SettingsController extends AbstractController
{
    private function denyAccessUnlessAdmin(Request $request): ?Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            if ($request->isXmlHttpRequest()) {
                return new Response('<div class="alert alert-danger text-center m-3 shadow">❌ Vous n\'avez pas accès à cette fonctionnalité.</div>', 403);
            }
            return $this->render('error/403.html.twig', [], new Response('', 403));
        }
        return null;
    }

    #[Route('/', name: 'settings_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        if ($resp = $this->denyAccessUnlessAdmin($request)) return $resp;
        return $this->render('settings/index.html.twig');
    }

    #[Route('/general', name: 'settings_general', methods: ['GET', 'POST'])]
    public function general(Request $request, EntityManagerInterface $em): Response
    {
        if ($resp = $this->denyAccessUnlessAdmin($request)) return $resp;

        $configuration = $em->getRepository(Configuration::class)->findOneBy([]) ?? new Configuration();
        $form = $this->createForm(ConfigurationType::class, $configuration);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($configuration);
            $em->flush();
            $this->addFlash('success', 'La configuration a été mise à jour.');
            return $this->redirectToRoute('settings_general');
        }

        return $this->render('settings/general.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/users', name: 'settings_users', methods: ['GET'])]
    public function users(Request $request, UtilisateurRepository $utilisateurRepository): Response
    {
        if ($resp = $this->denyAccessUnlessAdmin($request)) return $resp;
        $utilisateurs = $utilisateurRepository->findAll();
        return $this->render('settings/users.html.twig', [
            'utilisateurs' => $utilisateurs,
        ]);
    }

    #[Route('/users/create', name: 'settings_utilisateur_create', methods: ['GET', 'POST'])]
    public function createUtilisateur(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        if ($resp = $this->denyAccessUnlessAdmin($request)) return $resp;

        $utilisateur = new Utilisateur();
        $form = $this->createForm(UtilisateurCreateType::class, $utilisateur, [
            'current_user' => $this->getUser(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('password')->getData();
            $hashedPassword = $hasher->hashPassword($utilisateur, $plainPassword);
            $utilisateur->setPassword($hashedPassword);

            $em->persist($utilisateur);
            $em->flush();

            $this->addFlash('success', 'Utilisateur créé avec succès.');
            return $this->redirectToRoute('settings_users');
        }

        return $this->render('settings/utilisateur_create.html.twig', [
            'form' => $form->createView(),
            'utilisateur' => $utilisateur,
        ]);
    }

    #[Route('/users/{id}/edit', name: 'settings_utilisateur_edit', methods: ['GET', 'POST'])]
    public function editUtilisateur(Utilisateur $utilisateur, Request $request, EntityManagerInterface $em): Response
    {
        if ($resp = $this->denyAccessUnlessAdmin($request)) return $resp;

        $currentUser = $this->getUser();

        if (!$this->isGranted('ROLE_SUPER_ADMIN') && $utilisateur->hasRole('ROLE_SUPER_ADMIN')) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier un super administrateur.');
            return $this->redirectToRoute('settings_users');
        }

        if (!$this->isGranted('ROLE_SUPER_ADMIN') && $utilisateur === $currentUser) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier vos propres rôles.');
            return $this->redirectToRoute('settings_users');
        }

        $form = $this->createForm(GestionRoleType::class, $utilisateur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($utilisateur);
            $em->flush();

            $this->addFlash('success', 'Les rôles ont été mis à jour.');
            return $this->redirectToRoute('settings_users');
        }

        return $this->render('settings/utilisateur_edit.html.twig', [
            'form' => $form->createView(),
            'utilisateur' => $utilisateur,
        ]);
    }

    #[Route('/users/{id}/delete', name: 'settings_users_delete', methods: ['POST'])]
    public function deleteUtilisateur(Request $request, Utilisateur $utilisateur, EntityManagerInterface $em): Response
    {
        if ($resp = $this->denyAccessUnlessAdmin($request)) return $resp;

        if ($utilisateur === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            return $this->redirectToRoute('settings_users');
        }

        if ($utilisateur->hasRole('ROLE_SUPER_ADMIN') && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer un super administrateur.');
            return $this->redirectToRoute('settings_users');
        }

        if ($this->isCsrfTokenValid('delete_user_' . $utilisateur->getId(), $request->request->get('_token'))) {
            $em->remove($utilisateur);
            $em->flush();
            $this->addFlash('success', 'Utilisateur supprimé avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide. La suppression a échoué.');
        }

        return $this->redirectToRoute('settings_users');
    }
}
