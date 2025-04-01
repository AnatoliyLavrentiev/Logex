<?php
// src/Controller/DeliveryController.php
namespace App\Controller;

use App\Entity\Delivery;
use App\Entity\Order;
use App\Entity\Inventory;
use App\Entity\StoreProduct;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;


#[Route('/delivery')]
class DeliveryController extends AbstractController
{
    #[Route('/', name: 'delivery_index', methods: ['GET'])]
    public function index(
        EntityManagerInterface $entityManager,
        Request $request,
        AuthorizationCheckerInterface $auth
    ): Response {
        if (
            !$auth->isGranted('ROLE_SUPER_ADMIN') &&
            !$auth->isGranted('ROLE_ADMIN') &&
            !$auth->isGranted('ROLE_MAGASIN')
        ) {
            if ($request->isXmlHttpRequest()) {
                return new Response(
                    '<div class="alert alert-danger text-center m-3 shadow">❌ Vous n\'avez pas le droit d\'accéder aux livraisons.</div>',
                    403
                );
            }
    
            return $this->render('error/403.html.twig', [], new Response('', 403));
        }
    
        $deliveries = $entityManager->getRepository(Delivery::class)->findAll();
    
        return $this->render('delivery/index.html.twig', [
            'deliveries' => $deliveries,
        ]);
    }

    #[Route('/{id}/recuperate', name: 'delivery_recuperate', methods: ['POST'])]
    public function recuperate(Delivery $delivery, EntityManagerInterface $entityManager, AuthorizationCheckerInterface $auth): Response
    {
        if (!$auth->isGranted('ROLE_ORDER_CREATE')) {
            return new Response('<div class="alert alert-danger">Vous n\'avez pas accès à cette fonctionnalité.</div>', 403);
        }

        if ($delivery->getStatus() !== 'En Cours') {
            throw $this->createNotFoundException('La livraison ne peut pas être récupérée');
        }

        $delivery->setStatus('Livré');
        $delivery->setDeliveredAt(new \DateTime());

        $order = $delivery->getOrder();
        if ($order) {
            if ($order->getStatus() === 'executed') {
                $order->setStatus('shipped');
            }

            $storeProductRepo = $entityManager->getRepository(\App\Entity\StoreProduct::class);
            $inventoryRepo = $entityManager->getRepository(\App\Entity\Inventory::class);

            foreach ($order->getOrderItems() as $orderItem) {
                $product = $orderItem->getInventory()->getProduct();
                $quantity = $orderItem->getQuantity();

                $inventory = $inventoryRepo->findOneBy([
                    'product' => $product,
                    'location' => 'warehouse',
                ]);

                $unitPrice = $inventory?->getPrice() ?? $product->getPrice();
                $unitWeight = $inventory?->getWeight() ?? $product->getWeight();

                $storeProduct = $storeProductRepo->findOneBy(['product' => $product]);
                if ($storeProduct) {
                    $storeProduct->setQuantity($storeProduct->getQuantity() + $quantity);
                    $storeProduct->setUpdatedAt(new \DateTime());
                } else {
                    $storeProduct = new \App\Entity\StoreProduct();
                    $storeProduct->setProduct($product);
                    $storeProduct->setQuantity($quantity);
                    $storeProduct->setUpdatedAt(new \DateTime());
                    $storeProduct->setUnitPrice($unitPrice);
                    $storeProduct->setUnitWeight($unitWeight);
                    $entityManager->persist($storeProduct);
                }
            }
        }

        $entityManager->flush();
        $this->addFlash('success', 'La livraison a été récupérée avec succès. Les produits ont été ajoutés au magasin.');

        return $this->redirectToRoute('delivery_index');
    }
    

    #[Route('/{id}/delete', name: 'delivery_delete', methods: ['POST'])]
    public function delete(Request $request, Delivery $delivery, EntityManagerInterface $em, AuthorizationCheckerInterface $auth): Response
    {
        if (!$auth->isGranted('ROLE_ORDER_CREATE')) {
            return new Response('<div class="alert alert-danger">Vous n\'avez pas accès à cette fonctionnalité.</div>', 403);
        }

        if ($this->isCsrfTokenValid('delete' . $delivery->getId(), $request->request->get('_token'))) {
            $em->remove($delivery);
            $em->flush();

            $this->addFlash('success', 'La livraison a été supprimée avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide. La livraison n\'a pas été supprimée.');
        }

        return $this->redirectToRoute('delivery_index');
    }
}

