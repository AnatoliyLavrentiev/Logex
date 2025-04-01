<?php
// src/Controller/ProductController.php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[Route('/product')]
class ProductController extends AbstractController
{
    #[Route('/', name: 'app_product', methods: ['GET'])]
    public function index(
        Request $request,
        ProductRepository $productRepository,
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
                    '<div class="alert alert-danger text-center m-3 shadow">❌ Vous n\'avez pas accès à cette page.</div>',
                    403
                );
            }
    
            return $this->render('error/403.html.twig', [], new Response('', 403));
        }
    
        $query = trim($request->query->get('q', ''));
    
        $sort = $request->query->get('sort', 'p.prodname');
        $direction = strtolower($request->query->get('direction', 'asc')) === 'desc' ? 'DESC' : 'ASC';
    
        $allowedSortFields = [
            'p.prodname',
            'p.reference',
            'p.price',
            'p.weight',
            'p.category'
        ];
    
        if (!in_array($sort, $allowedSortFields)) {
            $sort = 'p.prodname';
        }
    
        $qb = $productRepository->createQueryBuilder('p');
    
        if (!empty($query)) {
            $qb->where('LOWER(p.prodname) LIKE :query')
               ->orWhere('LOWER(p.category) LIKE :query')
               ->orWhere('LOWER(CAST(p.reference AS string)) LIKE :query')
               ->setParameter('query', '%' . strtolower($query) . '%');
        }
    
        $qb->orderBy($sort, $direction);
    
        $pagination = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            20,
            ['disableSorting' => true]
        );
    
        return $this->render('product/index.html.twig', [
            'pagination' => $pagination,
            'query' => $query,
            'sort' => $sort,
            'direction' => strtolower($direction),
        ]);
    }
    

    #[Route('/new', name: 'product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, AuthorizationCheckerInterface $auth): Response {
        if (!$auth->isGranted('ROLE_ENTREPOT') && !$auth->isGranted('ROLE_PRODUCT_CREATE')) {
            return new Response('<div class="alert alert-danger text-center m-3 shadow">❌ Vous n\'avez pas le droit de créer un produit.</div>', 403);
        }
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Обработка загрузки изображения через поле imageFile
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                try {
                    $imageFile->move(
                        $this->getParameter('uploads_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors du téléchargement de l\'image.');
                }
                $product->setImage($newFilename);
            }

            $entityManager->persist($product);
            $entityManager->flush();

            return $this->redirectToRoute('app_product', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/new.html.twig', [
            'product' => $product,
            'form'    => $form->createView(),
        ]);
    }

    #[Route('/new-modal', name: 'product_new_modal', methods: ['GET', 'POST'])]
    public function newModal(Request $request, EntityManagerInterface $entityManager, AuthorizationCheckerInterface $auth): Response {
        if (!$auth->isGranted('ROLE_ENTREPOT') && !$auth->isGranted('ROLE_PRODUCT_CREATE')) {
            return new Response('<div class="alert alert-danger text-center m-3 shadow">❌ Vous n\'avez pas le droit de créer un produit.</div>', 403);
        }
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                try {
                    $imageFile->move(
                        $this->getParameter('uploads_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors du téléchargement de l\'image.');
                }
                $product->setImage($newFilename);
            }

            $entityManager->persist($product);
            $entityManager->flush();

            return $this->redirectToRoute('app_product');
        }

        return $this->render('product/_new_modal.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // Для всех маршрутов с {id} добавляем требование, чтобы id был числом
    #[Route('/{id}', name: 'product_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Product $product): Response {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/edit', name: 'product_edit', methods: ['GET', 'POST'], requirements: ['id' => '\\d+'])]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager, AuthorizationCheckerInterface $auth): Response {
        if (!$auth->isGranted('ROLE_ENTREPOT') && !$auth->isGranted('ROLE_PRODUCT_EDIT')) {
            return new Response('<div class="alert alert-danger text-center m-3 shadow">❌ Vous n\'avez pas le droit de modifier ce produit.</div>', 403);
        }
        $form = $this->createForm(ProductType::class, $product, [
            'action' => $this->generateUrl('product_edit', ['id' => $product->getId()]),
        ]);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
    
            if ($imageFile) {
                // Удаляем старую картинку, если она была
                if ($product->getImage()) {
                    $oldImagePath = $this->getParameter('uploads_directory') . '/' . $product->getImage();
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
    
                // Генерируем новое имя файла
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                try {
                    $imageFile->move(
                        $this->getParameter('uploads_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors du téléchargement de l\'image.');
                }
    
                // Обновляем путь к файлу
                $product->setImage($newFilename);
            }
    
            // Сохраняем изменения
            $entityManager->flush();
    
            return $this->redirectToRoute('app_product', [], Response::HTTP_SEE_OTHER);
        }
    
        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form'    => $form->createView(),
        ]);
    }
    

    #[Route('/{id}/edit-modal', name: 'product_edit_modal', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function modalEdit(Product $product, AuthorizationCheckerInterface $auth, Request $request): Response {
        if (!$auth->isGranted('ROLE_ENTREPOT') && !$auth->isGranted('ROLE_PRODUCT_EDIT')) {
            return new Response('<div class="alert alert-danger text-center m-3 shadow">❌ Vous n\'avez pas le droit de modifier ce produit.</div>', 403);
        }
        $form = $this->createForm(ProductType::class, $product, [
            'action' => $this->generateUrl('product_edit', ['id' => $product->getId()]),
        ]);

        return $this->render('product/_edit_modal.html.twig', [
            'product' => $product,
            'form'    => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'product_delete', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager, AuthorizationCheckerInterface $auth): Response {
        if (!$auth->isGranted('ROLE_ENTREPOT') && !$auth->isGranted('ROLE_PRODUCT_DELETE')) {
            if ($request->isXmlHttpRequest()) {
                return new Response('<div class="alert alert-danger text-center m-3 shadow">❌ Vous n\'avez pas le droit de supprimer ce produit.</div>', 403);
            }
            return new Response("Accès refusé", 403);
        }
        if ($this->isCsrfTokenValid('delete' . $product->getId(), $request->request->get('_token'))) {
            $entityManager->remove($product);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_product', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/modal', name: 'product_modal', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function modalShow(Product $product): Response {
        return $this->render('product/_show_modal.html.twig', [
            'product' => $product,
        ]);
    }

    // ---------- Функции экспорта, импорта и печати для продуктов ----------

    #[Route('/export-excel', name: 'product_export_excel', methods: ['GET'])]
    public function exportExcel(EntityManagerInterface $entityManager, AuthorizationCheckerInterface $auth): Response {
        if (!$auth->isGranted('ROLE_ENTREPOT') && !$auth->isGranted('ROLE_PRODUCT_EXPORT')) {
            return new Response('<div class="alert alert-danger text-center m-3 shadow">❌ Vous n\'avez pas le droit d\'exporter les produits.</div>', 403);
        }

        $products = $entityManager->getRepository(Product::class)->findAll();
    
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
    
        // ❌ Убрали колонку 'Image'
        $sheet->setCellValue('A1', 'Nom du produit');
        $sheet->setCellValue('B1', 'Référence du Produit');
        $sheet->setCellValue('C1', 'Description');
        $sheet->setCellValue('D1', 'Prix');
        $sheet->setCellValue('E1', 'Poids');
        $sheet->setCellValue('F1', 'Catégorie');
    
        $row = 2;
        foreach ($products as $product) {
            $sheet->setCellValue('A' . $row, $product->getProdname());
            $sheet->setCellValue('B' . $row, $product->getReference());
            $sheet->setCellValue('C' . $row, $product->getDescription());
            $sheet->setCellValue('D' . $row, $product->getPrice());
            $sheet->setCellValue('E' . $row, $product->getWeight());
            $sheet->setCellValue('F' . $row, $product->getCategory());
            $row++;
        }
    
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $tempFile = tempnam(sys_get_temp_dir(), 'export_product');
        $writer->save($tempFile);
    
        return $this->file($tempFile, 'export_products.xlsx', ResponseHeaderBag::DISPOSITION_INLINE);
    }
    

    #[Route('/import-excel', name: 'product_import_excel', methods: ['GET', 'POST'])]
    public function importExcel(Request $request, EntityManagerInterface $entityManager, AuthorizationCheckerInterface $auth): Response {
        if (!$auth->isGranted('ROLE_ENTREPOT') && !$auth->isGranted('ROLE_PRODUCT_IMPORT')) {
            return new Response('<div class="alert alert-danger text-center m-3 shadow">❌ Vous n\'avez pas le droit d\'importer les produits.</div>', 403);
        }
        if ($request->isMethod('POST')) {
            $file = $request->files->get('excel_file');
            if ($file) {
                $spreadsheet = IOFactory::load($file->getPathname());
                $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
    
                $header = array_shift($sheetData);
                $countCreated = 0;
    
                foreach ($sheetData as $row) {
                    // A - Nom du produit, B - Référence
                    if (!isset($row['A']) || !isset($row['B'])) {
                        continue;
                    }
    
                    $prodname = trim($row['A']);
                    $reference = trim($row['B']);
                    $description = isset($row['C']) ? trim($row['C']) : '';
                    $price = isset($row['D']) ? (float)$row['D'] : null;
                    $weight = isset($row['E']) ? (float)$row['E'] : null;
                    $category = isset($row['F']) ? trim($row['F']) : '';
    
                    // Найдём по Référence
                    $product = $entityManager->getRepository(Product::class)
                        ->findOneBy(['reference' => $reference]);
    
                    if (!$product) {
                        $product = new Product();
                        $product->setProdname($prodname);
                        $product->setReference($reference);
                        $product->setDescription($description);
                        $product->setPrice($price);
                        $product->setWeight($weight);
                        $product->setCategory($category);
    
                        // ❌ Не обрабатываем колонку G (image)
                        $entityManager->persist($product);
                        $countCreated++;
                    }
                }
    
                $entityManager->flush();
    
                $this->addFlash('success', "Import Excel effectué avec succès. $countCreated nouveaux produits ajoutés.");
                return $this->redirectToRoute('app_product');
            }
        }
    
        return $this->render('product/import_excel.html.twig');
    }

    #[Route('/print', name: 'product_print', methods: ['GET'])]
    public function printTable(EntityManagerInterface $entityManager): Response {
        $products = $entityManager->getRepository(Product::class)->findAll();
        return $this->render('product/_print_table.html.twig', [
            'products' => $products,
        ]);
    }
}
