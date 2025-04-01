<?php
// src/Controller/InventoryController.php

namespace App\Controller;

use App\Entity\Inventory;
use App\Entity\Warehouse;
use App\Form\InventoryType;
use App\Form\InventoryTransferType;
use App\Repository\InventoryRepository;
use App\Repository\ProductRepository;
use App\Repository\WarehouseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[Route('/inventory')]
class InventoryController extends AbstractController
{
    #[Route('/', name: 'inventory_index', methods: ['GET'])]
    public function index(
        Request $request,
        InventoryRepository $inventoryRepository,
        PaginatorInterface $paginator,
        AuthorizationCheckerInterface $auth
    ): Response {
        if (
            !$auth->isGranted('ROLE_ENTREPOT') &&
            !$auth->isGranted('ROLE_ADMIN') &&
            !$auth->isGranted('ROLE_SUPER_ADMIN')
        ) {
            if ($request->isXmlHttpRequest()) {
                return new Response(
                    '<div class="alert alert-danger text-center m-3 shadow">❌ Vous n\'avez pas le droit d\'accéder à la page d\'inventaire.</div>',
                    403
                );
            }
    
            return $this->render('error/403.html.twig', [], new Response('', 403));
        }
    
        $query = $request->query->get('q');
        $sort = $request->query->get('sort', 'p.prodname');
        $direction = strtolower($request->query->get('direction', 'asc')) === 'desc' ? 'DESC' : 'ASC';
    
        $allowedSorts = [
            'p.prodname', 'i.quantity', 'i.updatedAt', 'i.price', 'i.weight', 'i.category'
        ];
    
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'p.prodname';
        }
    
        $qb = $inventoryRepository->createQueryBuilder('i')
            ->leftJoin('i.product', 'p')
            ->addSelect('p')
            ->leftJoin('i.warehouse', 'w')
            ->addSelect('w');
    
        if ($query) {
            $qb->andWhere('LOWER(p.prodname) LIKE :query OR LOWER(i.category) LIKE :query OR LOWER(w.name) LIKE :query')
               ->setParameter('query', '%' . strtolower($query) . '%');
        }
    
        $qb->orderBy($sort, $direction);
    
        $pagination = $paginator->paginate(
            $qb->getQuery(),
            $request->query->getInt('page', 1),
            20,
            ['disableSort' => true]
        );
    
        return $this->render('inventory/index.html.twig', [
            'pagination' => $pagination,
            'query' => $query,
            'sort' => $sort,
            'direction' => strtolower($direction),
        ]);
    }
    
    
    
    

    #[Route('/new-modal', name: 'inventory_new_modal', methods: ['GET', 'POST'])]
public function newModal(Request $request, EntityManagerInterface $em, ProductRepository $productRepository, AuthorizationCheckerInterface $auth): Response
{
    if (!$auth->isGranted('ROLE_ENTREPOT')) {
        return new Response('<div class="alert alert-danger">Vous n\'avez pas accès à cette fonctionnalité.</div>', 403);
    }

    $inventory = new Inventory();
    $form = $this->createForm(InventoryType::class, $inventory);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $em->persist($inventory);
        $em->flush();

        return $this->redirectToRoute('inventory_index');
    }

    $products = $productRepository->findAll();

    return $this->render('inventory/_new_modal.html.twig', [
        'form'     => $form->createView(),
        'products' => $products,
    ]);
}

#[Route('/{id}/edit-modal', name: 'inventory_edit_modal', methods: ['GET'], requirements: ['id' => '\d+'])]
public function modalEdit(Inventory $inventory, AuthorizationCheckerInterface $auth): Response
{
    if (!$auth->isGranted('ROLE_ENTREPOT')) {
        return new Response('<div class="alert alert-danger">Vous n\'avez pas accès à cette fonctionnalité.</div>', 403);
    }

    $form = $this->createForm(InventoryType::class, $inventory, [
        'action' => $this->generateUrl('inventory_edit', ['id' => $inventory->getId()]),
    ]);

    return $this->render('inventory/_edit_modal.html.twig', [
        'inventory' => $inventory,
        'form' => $form->createView(),
    ]);
}

#[Route('/import-excel', name: 'inventory_import_excel', methods: ['GET', 'POST'])]
public function importExcel(Request $request, EntityManagerInterface $em, ProductRepository $productRepo, InventoryRepository $inventoryRepo): Response
{
    $this->denyAccessUnlessGranted('ROLE_ENTREPOT');

    if ($request->isMethod('POST')) {
        $file = $request->files->get('excel_file');

        if ($file) {
            $spreadsheet = IOFactory::load($file->getPathname());
            $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
            array_shift($sheetData); // удаляем заголовки

            $created = 0;
            $updated = 0;

            foreach ($sheetData as $row) {
                $productId = (int)($row['A'] ?? 0);
                $warehouseName = trim($row['C'] ?? '');
                $quantity = (int)($row['D'] ?? 0);
                $price = isset($row['E']) ? (float)str_replace(',', '.', $row['E']) : null;
                $weight = isset($row['F']) ? (float)str_replace(',', '.', $row['F']) : null;
                $category = isset($row['I']) ? trim((string)$row['I']) : null;
                $updatedAtStr = trim($row['J'] ?? '');

                if (!$productId || !$warehouseName || $quantity <= 0) {
                    continue;
                }

                if (is_numeric($category)) {
                    $category = null;
                }

                $product = $productRepo->find($productId);
                $warehouse = $em->getRepository(Warehouse::class)->findOneBy(['name' => $warehouseName]);

                if (!$product || !$warehouse) {
                    continue;
                }

                $inventory = $inventoryRepo->findOneBy([
                    'product' => $product,
                    'warehouse' => $warehouse,
                ]);

                $updatedAt = \DateTime::createFromFormat('d/m/Y H:i', $updatedAtStr) ?: new \DateTime();

                if ($inventory) {
                    $inventory->setQuantity($inventory->getQuantity() + $quantity);
                    $inventory->setUpdatedAt($updatedAt);
                    if ($price !== null) $inventory->setPrice($price);
                    if ($weight !== null) $inventory->setWeight($weight);
                    $updated++;
                } else {
                    $inventory = new Inventory();
                    $inventory->setProduct($product);
                    $inventory->setWarehouse($warehouse);
                    $inventory->setQuantity($quantity);
                    $inventory->setUpdatedAt($updatedAt);
                    if ($price !== null) $inventory->setPrice($price);
                    if ($weight !== null) $inventory->setWeight($weight);
                    if ($category) $inventory->setCategory($category);
                    $em->persist($inventory);
                    $created++;
                }
            }

            $em->flush();
            $this->addFlash('success', "$created ajout(s), $updated mise(s) à jour.");
            return $this->redirectToRoute('inventory_index');
        }
    }

    return $this->render('inventory/import_excel.html.twig');
}


#[Route('/export-excel', name: 'inventory_export_excel', methods: ['GET'])]
public function exportExcel(EntityManagerInterface $em): Response
{
    $this->denyAccessUnlessGranted('ROLE_ENTREPOT');

    $inventories = $em->getRepository(Inventory::class)->findAll();
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Обновлённая шапка с ID продукта
    $sheet->fromArray([
        ['ID Produit', 'Produit', 'Entrepôt', 'Quantité', 'Prix unitaire', 'Poids unitaire', 'Prix total', 'Poids total', 'Catégorie', 'Date MAJ']
    ], null, 'A1');

    $row = 2;
    foreach ($inventories as $inv) {
        $price = $inv->getPrice();
        $weight = $inv->getWeight();
        $quantity = $inv->getQuantity();

        $sheet->fromArray([
            $inv->getProduct()?->getId(),
            $inv->getProduct()?->getProdname(),
            $inv->getWarehouse()?->getName(),
            $quantity,
            $price !== null ? number_format($price, 2, ',', ' ') : '',
            $weight !== null ? number_format($weight, 2, ',', ' ') : '',
            $price !== null ? number_format($price * $quantity, 2, ',', ' ') : '',
            $weight !== null ? number_format($weight * $quantity, 2, ',', ' ') : '',
            $inv->getCategory(),
            $inv->getUpdatedAt()?->format('d/m/Y H:i')
        ], null, 'A' . $row);

        $row++;
    }

    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
    $tmp = tempnam(sys_get_temp_dir(), 'inv');
    $writer->save($tmp);

    return $this->file($tmp, 'inventaire.xlsx', ResponseHeaderBag::DISPOSITION_INLINE);
}


    #[Route('/print', name: 'inventory_print', methods: ['GET'])]
public function printTable(EntityManagerInterface $entityManager): Response
{
    $inventories = $entityManager->getRepository(\App\Entity\Inventory::class)->findAll();
    return $this->render('inventory/_print_table.html.twig', [
        'inventories' => $inventories,
    ]);
}



#[Route('/{id}/modal', name: 'inventory_modal', methods: ['GET'], requirements: ['id' => '\d+'])]
public function modalShow(Inventory $inventory): Response
    {
        return $this->render('inventory/_show_modal.html.twig', [
            'inventory' => $inventory,
        ]);
    }



    #[Route('/new', name: 'inventory_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ENTREPOT');
        $inventory = new Inventory();
        $form = $this->createForm(InventoryType::class, $inventory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($inventory);
            $em->flush();
            return $this->redirectToRoute('inventory_index');
        }

        return $this->render('inventory/new.html.twig', [
            'form' => $form->createView(),
            'inventory' => $inventory,
        ]);
    }

    #[Route('/{id}/edit', name: 'inventory_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Inventory $inventory, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ENTREPOT');
        $form = $this->createForm(InventoryType::class, $inventory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('inventory_index');
        }

        return $this->render('inventory/edit.html.twig', [
            'form' => $form->createView(),
            'inventory' => $inventory,
        ]);
    }

    #[Route('/{id}/transfer', name: 'inventory_transfer', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
public function transfer(
    Inventory $inventory,
    Request $request,
    EntityManagerInterface $em,
    WarehouseRepository $warehouseRepository,
    InventoryRepository $inventoryRepository,
    AuthorizationCheckerInterface $auth
): Response {
    if (!$auth->isGranted('ROLE_ENTREPOT')) {
        $this->addFlash('error', "Vous n'avez pas accès à cette fonctionnalité.");
        return $this->redirectToRoute('inventory_index');
    }

    $allWarehouses = $warehouseRepository->findAll();
    $destinationWarehouses = array_filter($allWarehouses, function($warehouse) use ($inventory) {
        return $warehouse->getId() !== $inventory->getWarehouse()->getId();
    });

    $form = $this->createForm(InventoryTransferType::class, null, [
        'warehouses' => $destinationWarehouses,
    ]);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $data = $form->getData();
        $transferQuantity = $data['quantity'];

        if ($transferQuantity > $inventory->getQuantity()) {
            $this->addFlash('error', 'Недостаточно товара для переноса.');
        } else {
            $inventory->setQuantity($inventory->getQuantity() - $transferQuantity);
            $inventory->setUpdatedAt(new \DateTime());

            $destinationWarehouse = $data['destinationWarehouse'];
            $destinationInventory = $inventoryRepository->findOneBy([
                'warehouse' => $destinationWarehouse,
                'product'   => $inventory->getProduct()
            ]);

            if (!$destinationInventory) {
                $destinationInventory = new Inventory();
                $destinationInventory->setWarehouse($destinationWarehouse);
                $destinationInventory->setProduct($inventory->getProduct());
                $destinationInventory->setQuantity(0);
                $destinationInventory->setUpdatedAt(new \DateTime());
                $em->persist($destinationInventory);
            }

            $destinationInventory->setQuantity($destinationInventory->getQuantity() + $transferQuantity);
            $destinationInventory->setUpdatedAt(new \DateTime());

            $em->flush();

            $this->addFlash('success', 'Перенос успешно выполнен.');
            return $this->redirectToRoute('inventory_index');
        }
    }

    return $this->render('inventory/transfer.html.twig', [
        'inventory'    => $inventory,
        'transferForm' => $form->createView(),
    ]);
} 
 
#[Route('/{id}', name: 'inventory_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
public function delete(Request $request, Inventory $inventory, EntityManagerInterface $em): Response
{
    $this->denyAccessUnlessGranted('ROLE_ENTREPOT');

    if ($this->isCsrfTokenValid('delete' . $inventory->getId(), $request->request->get('_token'))) {
        try {
            $em->remove($inventory);
            $em->flush();
        } catch (\Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException $e) {
            return new Response('Impossible de supprimer cette entrée : elle est liée à une commande.', 500);
        }
    }

    return $this->redirectToRoute('inventory_index');
}


#[Route('/{id}', name: 'inventory_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Inventory $inventory): Response
    {
        return $this->render('inventory/show.html.twig', [
            'inventory' => $inventory,
        ]);
    }



}
