<?php
// src/Controller/OrderController.php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Inventory;
use App\Entity\Delivery;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\FormError;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[Route('/order')]
class OrderController extends AbstractController
{
    #[Route('/', name: 'order_index', methods: ['GET'])]
    public function index(
        EntityManagerInterface $entityManager,
        Request $request,
        AuthorizationCheckerInterface $auth
    ): Response {
        if (
            !$auth->isGranted('ROLE_ENTREPOT') &&
            !$auth->isGranted('ROLE_MAGASIN') &&
            !$auth->isGranted('ROLE_ADMIN') &&
            !$auth->isGranted('ROLE_SUPER_ADMIN')
        ) {
            if ($request->isXmlHttpRequest()) {
                return new Response(
                    '<div class="alert alert-danger text-center m-3 shadow">❌ Vous n\'avez pas le droit d\'accéder à la liste des commandes.</div>',
                    403
                );
            }
    
            return $this->render('error/403.html.twig', [], new Response('', 403));
        }
    
        $orders = $entityManager->getRepository(Order::class)->findBy([], ['createdAt' => 'DESC']);
    
        return $this->render('order/index.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/{id}/execute', name: 'order_execute', methods: ['POST'])]
    public function execute(Order $order, EntityManagerInterface $entityManager, Request $request, AuthorizationCheckerInterface $auth): Response
    {
        if (!$auth->isGranted('ROLE_ORDER_CREATE')) {
            if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                return new Response('<div class="alert alert-danger text-center m-3 shadow">❌ Vous n\'avez pas le droit d\'exécuter cette commande.</div>', 403);
            }
    
            // Иначе обычный редирект на страницу с сообщением
            $this->addFlash('danger', 'Vous n\'avez pas le droit d\'exécuter cette commande.');
            return $this->redirectToRoute('order_index');
        }
    
        // обычная логика
        foreach ($order->getOrderItems() as $orderItem) {
            $inventory = $orderItem->getInventory();
            if ($inventory && $inventory->getQuantity() >= $orderItem->getQuantity()) {
                $inventory->setQuantity($inventory->getQuantity() - $orderItem->getQuantity());
            } else {
                throw $this->createNotFoundException(
                    'Stock insuffisant pour le produit ' . $inventory->getProduct()->getProdname()
                );
            }
        }
    
        $order->setStatus('executed');
    
        $delivery = new Delivery();
        $delivery->setOrder($order);
        $delivery->setAddress('Adresse de livraison par défaut');
        $delivery->setStatus('En Cours');
        $delivery->setShippedAt(new \DateTime());
        $delivery->setTrackingNumber('TRK' . rand(100000, 999999));
        $order->setDelivery($delivery);
    
        $entityManager->persist($order);
        $entityManager->flush();
    
        return $this->redirectToRoute('order_index');
    }

    #[Route('/{id}/delete', name: 'order_delete', methods: ['POST'])]
    public function delete(Order $order, Request $request, EntityManagerInterface $entityManager, AuthorizationCheckerInterface $auth): Response
    {
        if (!$auth->isGranted('ROLE_ORDER_CREATE')) {
            if ($request->isXmlHttpRequest()) {
                return new Response(
                    '<div class="alert alert-danger text-center m-3 shadow">❌ Vous n\'avez pas le droit de supprimer cette commande.</div>',
                    403
                );
            }
    
            return new Response("Vous n'avez pas accès à cette fonctionnalité.", 403);
        }
    
        if ($this->isCsrfTokenValid('delete' . $order->getId(), $request->request->get('_token'))) {
            $entityManager->remove($order);
            $entityManager->flush();
            $this->addFlash('success', 'Commande supprimée avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide. La commande n\'a pas été supprimée.');
        }
    
        return $this->redirectToRoute('order_index');
    }

    #[Route('/{id}/modal', name: 'order_modal', methods: ['GET'])]
public function showModal(Order $order): Response
{
    return $this->render('order/_show_modal.html.twig', [
        'order' => $order,
    ]);
}
}