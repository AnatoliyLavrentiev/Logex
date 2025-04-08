<?php
// src/Controller/StoreController.php

namespace App\Controller;

use App\Entity\Shop;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\StoreProduct; // Utilisez StoreProduct désormais
use App\Form\InventoryType;
use App\Form\OrderType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[Route('/store')]
class StoreController extends AbstractController
{
    #[Route('/', name: 'store_index', methods: ['GET'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        AuthorizationCheckerInterface $auth
    ): Response {
        if (
            !$auth->isGranted('ROLE_SUPER_ADMIN') &&
            !$auth->isGranted('ROLE_ADMIN') &&
            !$auth->isGranted('ROLE_MAGASIN')
        ) {
            if ($request->isXmlHttpRequest()) {
                return new Response(
                    '<div class="alert alert-danger text-center m-3 shadow">❌ Vous n\'avez pas le droit d\'accéder au magasin.</div>',
                    403
                );
            }
    
            return $this->render('error/403.html.twig', [], new Response('', 403));
        }
        $query = trim($request->query->get('q', ''));
        $sort = $request->query->get('sort', 'prodname');
        $direction = strtolower($request->query->get('direction', 'asc')) === 'desc' ? 'DESC' : 'ASC';
    
        $allowedSortFields = ['prodname', 'reference', 'price', 'category', 'quantity'];
        if (!in_array($sort, $allowedSortFields)) {
            $sort = 'prodname';
        }
    
        $sortField = $sort === 'quantity' ? 's.quantity' : "p.$sort";
    
        $queryBuilder = $entityManager->getRepository(StoreProduct::class)
            ->createQueryBuilder('s')
            ->join('s.product', 'p')
            ->orderBy($sortField, $direction);
    
        if (!empty($query)) {
            $queryBuilder
                ->where("LOWER(p.prodname) LIKE LOWER(:query)")
                ->orWhere("LOWER(CONCAT('', p.reference)) LIKE LOWER(:query)")
                ->orWhere("LOWER(p.category) LIKE LOWER(:query)")
                ->setParameter('query', '%' . strtolower($query) . '%');
        }
    
        $storeItems = $queryBuilder->getQuery()->getResult();
    
        return $this->render('store/index.html.twig', [
            'storeItems' => $storeItems,
            'query' => $query,
            'sort' => $sort,
            'direction' => strtolower($direction),
            'orderUrl' => $this->generateUrl('store_order_new'),
        ]);
    }
    
    
    
    
    
    
    #[Route('/order/new', name: 'store_order_new', methods: ['GET', 'POST'])]
    public function newOrder(Request $request, EntityManagerInterface $entityManager, AuthorizationCheckerInterface $auth): Response
    {
        if (!$auth->isGranted('ROLE_ORDER_CREATE')) {
            return new Response('<div class="alert alert-danger">Vous n\'avez pas accès à cette fonctionnalité.</div>', 403);
        }

        $order = new Order();
        $order->setStatus('new');
        $order->setOrderNumber($this->generateOrderNumber($entityManager));

        $defaultShop = $entityManager->getRepository(Shop::class)->findOneBy(['default' => true]);
        if (!$defaultShop) {
            throw $this->createNotFoundException('Magasin par défaut non trouvé');
        }
        $order->setShop($defaultShop);

        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($request->isMethod('POST')) {
            if ($form->isSubmitted() && $form->isValid()) {
                foreach ($order->getOrderItems() as $item) {
                    $inventory = $item->getInventory();
                    if (!$inventory) {
                        return new JsonResponse([
                            'success' => false,
                            'errors' => ["Produit introuvable dans l'inventaire."]
                        ], 400);
                    }

                    $available = $inventory->getQuantity();
                    $requested = $item->getQuantity();

                    if ($requested > $available) {
                        return new JsonResponse([
                            'success' => false,
                            'errors' => ["Quantité insuffisante pour le produit « " . $inventory->getProduct()->getProdname() . " » (stock actuel : $available)."]
                        ], 400);
                    }

                    // Не списываем сразу — только сохраняем цену и связь
                    $item->setUnitPrice($inventory->getPrice());
                    $item->setOrder($order);
                }

                $total = array_reduce($order->getOrderItems()->toArray(), fn($carry, $item) => $carry + ($item->getUnitPrice() * $item->getQuantity()), 0);
                $order->setTotalAmount($total);

                $entityManager->persist($order);
                $entityManager->flush();

                return new JsonResponse([
                    'success' => true,
                    'redirect' => $this->generateUrl('store_index')
                ]);
            }

            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }

            return new JsonResponse([
                'success' => false,
                'errors' => $errors
            ], 400);
        }

        return $this->render('store/_order_form.html.twig', [
            'orderForm' => $form->createView(),
            'inventories' => $entityManager->getRepository(\App\Entity\Inventory::class)->findAll(),
        ]);
    }
    
    
    // Affichage des détails dans une modale
    #[Route('/inventory/{id}/show', name: 'store_inventory_show', methods: ['GET'])]
    public function showInventory(StoreProduct $storeProduct): Response
    {
        return $this->render('store/_show_modal.html.twig', [
            'inventory' => $storeProduct,
        ]);
    }

    #[Route('/inventory/{id}/edit', name: 'store_inventory_edit', methods: ['GET', 'POST'])]
    public function editInventory(Request $request, StoreProduct $storeProduct, EntityManagerInterface $em, AuthorizationCheckerInterface $auth): Response
    {
        if (!$auth->isGranted('ROLE_ORDER_CREATE')) {
            return new Response('<div class="alert alert-danger">Vous n\'avez pas accès à cette fonctionnalité.</div>', 403);
        }

        $form = $this->createForm(\App\Form\StoreProductEditType::class, $storeProduct, [
            'action' => $this->generateUrl('store_inventory_edit', ['id' => $storeProduct->getId()])
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if (!$form->isValid() && $request->isXmlHttpRequest()) {
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }
                return new JsonResponse(['success' => false, 'errors' => $errors], 400);
            }

            if ($form->isValid()) {
                $product = $storeProduct->getProduct();
                $imageFile = $form->get('product')->get('imageFile')->getData();

                if ($imageFile) {
                    $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                    $imageFile->move($this->getParameter('products_directory'), $newFilename);
                    $product->setImage($newFilename);
                }

                $storeProduct->setUpdatedAt(new \DateTime());
                $em->flush();

                return $request->isXmlHttpRequest()
                    ? new JsonResponse(['success' => true])
                    : $this->redirectToRoute('store_index');
            }
        }

        return $this->render('store/_edit_modal.html.twig', [
            'form' => $form->createView(),
            'inventory' => $storeProduct,
        ]);
    }
    
    

    // Impression d'un produit via modale
    #[Route('/inventory/{id}/print', name: 'store_inventory_print', methods: ['GET'])]
    public function printInventory(StoreProduct $storeProduct): Response
    {
        return $this->render('store/_print_modal.html.twig', [
            'inventory' => $storeProduct,
        ]);
    }

    // Suppression d'un produit
    #[Route('/inventory/{id}/delete', name: 'store_inventory_delete', methods: ['POST'])]
    public function deleteInventory(StoreProduct $storeProduct, Request $request, EntityManagerInterface $entityManager, AuthorizationCheckerInterface $auth): Response
    {
        if (!$auth->isGranted('ROLE_ORDER_CREATE')) {
            return new Response('<div class="alert alert-danger">Vous n\'avez pas accès à cette fonctionnalité.</div>', 403);
        }

        if ($this->isCsrfTokenValid('delete' . $storeProduct->getId(), $request->request->get('_token'))) {
            $entityManager->remove($storeProduct);
            $entityManager->flush();
            $this->addFlash('success', 'Produit supprimé avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide. Le produit n\'a pas été supprimé.');
        }
        return $this->redirectToRoute('store_index');
    }

    #[Route('/export-excel', name: 'store_export_excel', methods: ['GET'])]
    public function exportExcel(EntityManagerInterface $entityManager, AuthorizationCheckerInterface $auth): Response
    {
        if (!$auth->isGranted('ROLE_ORDER_CREATE')) {
            return new Response('<div class="alert alert-danger">Vous n\'avez pas accès à cette fonctionnalité.</div>', 403);
        }

        $storeProducts = $entityManager->getRepository(\App\Entity\StoreProduct::class)->findAll();
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->fromArray([
            ['Référence', 'Nom du Produit', 'Prix unitaire (€)', 'Poids unitaire (kg)', 'Catégorie', 'Quantité', 'Prix total (€)', 'Poids total (kg)']
        ], null, 'A1');

        $row = 2;
        foreach ($storeProducts as $item) {
            $product = $item->getProduct();
            $sheet->fromArray([
                $product->getReference(),
                $product->getProdname(),
                $item->getUnitPrice(),
                $item->getUnitWeight(),
                $product->getCategory(),
                $item->getQuantity(),
                $item->getUnitPrice() * $item->getQuantity(),
                $item->getUnitWeight() * $item->getQuantity(),
            ], null, 'A' . $row);
            $row++;
        }

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $tempFile = tempnam(sys_get_temp_dir(), 'export');
        $writer->save($tempFile);

        return $this->file($tempFile, 'export_store_products.xlsx', \Symfony\Component\HttpFoundation\ResponseHeaderBag::DISPOSITION_INLINE);
    }

    #[Route('/import-excel', name: 'store_import_excel', methods: ['GET', 'POST'])]
    public function importExcel(Request $request, EntityManagerInterface $entityManager, AuthorizationCheckerInterface $auth): Response
    {
        if (!$auth->isGranted('ROLE_ORDER_CREATE')) {
            return new Response('<div class="alert alert-danger">Vous n\'avez pas accès à cette fonctionnalité.</div>', 403);
        }

        if ($request->isMethod('POST')) {
            $file = $request->files->get('excel_file');
            if ($file) {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
                $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

                array_shift($sheetData);
                $countCreated = 0;
                $countUpdated = 0;

                foreach ($sheetData as $row) {
                    if (!isset($row['A']) || !isset($row['F'])) continue;

                    $reference = trim((string)$row['A']);
                    $nomProduit = trim((string)$row['B']);
                    $prix = (float)$row['C'];
                    $poids = (float)$row['D'];
                    $categorie = trim((string)$row['E']);
                    $quantity = (int)$row['F'];

                    $product = $entityManager->getRepository(\App\Entity\Product::class)->findOneBy(['reference' => $reference]);

                    if (!$product) {
                        $product = new \App\Entity\Product();
                        $product->setProdname($nomProduit);
                        $product->setReference($reference);
                        $product->setPrice($prix);
                        $product->setWeight($poids);
                        $product->setCategory($categorie);
                        $product->setDescription('');
                        $entityManager->persist($product);
                    }

                    $storeProduct = $entityManager->getRepository(\App\Entity\StoreProduct::class)->findOneBy(['product' => $product]);

                    if ($storeProduct) {
                        $storeProduct->setQuantity($storeProduct->getQuantity() + $quantity);
                        $storeProduct->setUpdatedAt(new \DateTime());
                        $countUpdated++;
                    } else {
                        $storeProduct = new \App\Entity\StoreProduct();
                        $storeProduct->setProduct($product);
                        $storeProduct->setUnitPrice($prix);
                        $storeProduct->setUnitWeight($poids);
                        $storeProduct->setQuantity($quantity);
                        $storeProduct->setUpdatedAt(new \DateTime());
                        $entityManager->persist($storeProduct);
                        $countCreated++;
                    }
                }

                $entityManager->flush();
                $this->addFlash('success', "Import terminé : $countCreated ajoutés, $countUpdated mis à jour.");
                return $this->redirectToRoute('store_index');
            }
        }

        return $this->render('store/import_excel.html.twig');
    }

    
    

    

    #[Route('/print', name: 'store_print', methods: ['GET'])]
    public function printTable(EntityManagerInterface $entityManager): Response
    {
    $storeItems = $entityManager->getRepository(\App\Entity\StoreProduct::class)->findAll();
    return $this->render('store/_print_table.html.twig', [
        'storeItems' => $storeItems,
    ]);
    }

    #[Route('/store/shops/new', name: 'store_shop_new')]
    public function newShop(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $shop = new Shop();
        $form = $this->createForm(ShopType::class, $shop);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                $imageFile->move(
                    $this->getParameter('shops_directory'),
                    $newFilename
                );

                $shop->setImage($newFilename);
            }

            $em->persist($shop);
            $em->flush();

            $this->addFlash('success', 'Magasin ajouté avec image.');
            return $this->redirectToRoute('store_index');
        }

        return $this->render('store/shop_form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/store/shops/{id}/edit', name: 'store_shop_edit')]
    public function editShop(Request $request, Shop $shop, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(ShopType::class, $shop);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                $imageFile->move(
                    $this->getParameter('shops_directory'),
                    $newFilename
                );

                $shop->setImage($newFilename);
            }

            $em->flush();
            $this->addFlash('success', 'Magasin mis à jour avec succès.');
            return $this->redirectToRoute('store_index');
        }

        return $this->render('store/shop_form.html.twig', [
            'form' => $form->createView(),
            'shop' => $shop,
        ]);
    }

    public function generateOrderNumber(EntityManagerInterface $em): string
{
    $lastOrder = $em->getRepository(Order::class)
        ->createQueryBuilder('o')
        ->orderBy('o.id', 'DESC')
        ->setMaxResults(1)
        ->getQuery()
        ->getOneOrNullResult();

    $lastId = $lastOrder ? $lastOrder->getId() : 0;
    $nextId = $lastId + 1;

    return 'CMD-' . str_pad((string)$nextId, 6, '0', STR_PAD_LEFT);
}
     
}


