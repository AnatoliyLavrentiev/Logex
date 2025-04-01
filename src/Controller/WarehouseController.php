<?php
// src/Controller/WarehouseController.php

namespace App\Controller;

use App\Entity\Warehouse;
use App\Form\WarehouseType;
use App\Repository\WarehouseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface; // Ajoutez cette ligne !
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[Route('/warehouse')]
class WarehouseController extends AbstractController
{
    #[Route('/', name: 'warehouse_index', methods: ['GET'])]
    public function index(
        Request $request,
        WarehouseRepository $warehouseRepository,
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
                    '<div class="alert alert-danger text-center m-3 shadow">❌ Vous n\'avez pas le droit d\'accéder à la liste des entrepôts.</div>',
                    403
                );
            }
    
            return $this->render('error/403.html.twig', [], new Response('', 403));
        }
    
        $query = $request->query->get('q');
        
        $queryBuilder = $warehouseRepository->createQueryBuilder('w');
    
        if ($query) {
            $queryBuilder->where('LOWER(w.name) LIKE :query')
                ->orWhere('LOWER(w.location) LIKE :query')
                ->orWhere('LOWER(w.adresse) LIKE :query')
                ->setParameter('query', '%' . strtolower($query) . '%');
        }
    
        $queryBuilder->orderBy('w.id', 'ASC');
    
        $pagination = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            20
        );
    
        return $this->render('warehouse/index.html.twig', [
            'pagination' => $pagination,
            'query'      => $query,
        ]);
    }

    #[Route('/new', name: 'warehouse_new', methods: ['GET','POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ENTREPOT');


        $warehouse = new Warehouse();
        $form = $this->createForm(WarehouseType::class, $warehouse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($warehouse);
            $entityManager->flush();

            return $this->redirectToRoute('warehouse_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('warehouse/new.html.twig', [
            'warehouse' => $warehouse,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/new-modal', name: 'warehouse_new_modal', methods: ['GET', 'POST'])]
    public function newModal(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ENTREPOT');

        $warehouse = new Warehouse();
        $form = $this->createForm(WarehouseType::class, $warehouse);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Получаем не связанные с сущностью поля
            $zone = $form->get('zoneStockage')->getData();
            $hauteur = $form->get('hauteurStockage')->getData();
    
            // Если заданы оба значения, рассчитываем capacité
            if ($zone && $hauteur) {
                $warehouse->setCapaciteStockage($this->calculateCapaciteStockage($zone, $hauteur));
            }
    
            $entityManager->persist($warehouse);
            $entityManager->flush();
    
            return $this->redirectToRoute('warehouse_index', [], Response::HTTP_SEE_OTHER);
        }
    
        return $this->render('warehouse/_new_modal.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    


    #[Route('/{id}', name: 'warehouse_show', methods: ['GET'])]
    public function show(Warehouse $warehouse): Response
    {
        return $this->render('warehouse/show.html.twig', [
            'warehouse' => $warehouse,
        ]);
    }

    #[Route('/{id}/edit', name: 'warehouse_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Warehouse $warehouse, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ENTREPOT');

        $form = $this->createForm(WarehouseType::class, $warehouse);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Получаем не связанные с сущностью поля из формы
            $zone = $form->get('zoneStockage')->getData();
            $hauteur = $form->get('hauteurStockage')->getData();
            if ($zone && $hauteur) {
                $warehouse->setCapaciteStockage($this->calculateCapaciteStockage($zone, $hauteur));
            }
    
            $entityManager->flush();
    
            return $this->redirectToRoute('warehouse_index', [], Response::HTTP_SEE_OTHER);
        }
    
        return $this->render('warehouse/edit.html.twig', [
            'warehouse' => $warehouse,
            'form' => $form->createView(),
        ]);
    }
    

    #[Route('/{id}', name: 'warehouse_delete', methods: ['POST'])]
    public function delete(Request $request, Warehouse $warehouse, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ENTREPOT')) {
            return new JsonResponse(['message' => "Vous n'avez pas le droit de supprimer cet entrepôt."], 403);
        }
    
        if ($this->isCsrfTokenValid('delete'.$warehouse->getId(), $request->request->get('_token'))) {
            try {
                $entityManager->remove($warehouse);
                $entityManager->flush();
                return new JsonResponse(['success' => true]);
            } catch (\Exception $e) {
                return new JsonResponse(['message' => "Impossible de supprimer cet entrepôt : il est peut-être utilisé ailleurs."], 500);
            }
        }
    
        return new JsonResponse(['message' => "Jeton CSRF invalide."], 400);
    }

    #[Route('/{id}/modal', name: 'warehouse_modal', methods: ['GET'])]
public function modalShow(Warehouse $warehouse): Response
{
    return $this->render('warehouse/_show_modal.html.twig', [
        'warehouse' => $warehouse,
    ]);
}

    #[Route('/{id}/edit-modal', name: 'warehouse_edit_modal', methods: ['GET'])]
    public function modalEdit(Warehouse $warehouse): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ENTREPOT');
        
        $form = $this->createForm(WarehouseType::class, $warehouse, [
            'action' => $this->generateUrl('warehouse_edit', ['id' => $warehouse->getId()]),
        ]);

        return $this->render('warehouse/_edit_modal.html.twig', [
            'warehouse' => $warehouse,
            'form' => $form->createView(),
        ]);
    }

    public function calculateCapaciteStockage(float $zone, float $hauteur): int
    {
        return (int) ($zone * $hauteur);
    }
}
