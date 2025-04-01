<?php
// src/Controller/ReportsController.php

namespace App\Controller;

use App\Repository\WarehouseRepository;
use App\Repository\ProductRepository;
use App\Repository\InventoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/reports')]
class ReportsController extends AbstractController
{
    #[Route('/', name: 'reports_index', methods: ['GET'])]
    public function index(
        WarehouseRepository $warehouseRepository,
        ProductRepository $productRepository,
        InventoryRepository $inventoryRepository
    ): Response {
        $warehouses = $warehouseRepository->findAll();
        $products = $productRepository->findAll();
        $inventories = $inventoryRepository->findAll();

        // 📦 Répartition des produits par catégorie
        $categoryData = [];
        foreach ($products as $product) {
            $category = $product->getCategory();
            if (!isset($categoryData[$category])) {
                $categoryData[$category] = 0;
            }
            $categoryData[$category]++;
        }

        // 📍 Répartition des entrepôts par ville
        $warehouseLocationData = [];
        foreach ($warehouses as $warehouse) {
            $location = $warehouse->getLocation();
            if (!isset($warehouseLocationData[$location])) {
                $warehouseLocationData[$location] = 0;
            }
            $warehouseLocationData[$location]++;
        }

        // 💰 Valeur totale du stock par entrepôt (исправлен подсчет)
        $warehouseValueData = array_fill_keys(array_map(fn($w) => $w->getName(), $warehouses), 0);
        foreach ($inventories as $inventory) {
            if ($inventory->getWarehouse()) {
                $warehouse = $inventory->getWarehouse()->getName();
                $warehouseValueData[$warehouse] += $inventory->getQuantity() * floatval($inventory->getPrice());
            }
        }

        // 🛍️ Nombre de produits par entrepôt (исправлен подсчет)
        $productPerWarehouseData = array_fill_keys(array_map(fn($w) => $w->getName(), $warehouses), 0);
        foreach ($inventories as $inventory) {
            if ($inventory->getWarehouse()) {
                $warehouse = $inventory->getWarehouse()->getName();
                $productPerWarehouseData[$warehouse] += $inventory->getQuantity();
            }
        }

        return $this->render('reports/index.html.twig', [
            'categoryLabels'            => json_encode(array_keys($categoryData)),
            'categoryValues'            => json_encode(array_values($categoryData)),
            'warehouseLocationLabels'   => json_encode(array_keys($warehouseLocationData)),
            'warehouseLocationValues'   => json_encode(array_values($warehouseLocationData)),
            'warehouseValueLabels'      => json_encode(array_keys($warehouseValueData)),
            'warehouseValueValues'      => json_encode(array_values($warehouseValueData)),
            'productPerWarehouseLabels' => json_encode(array_keys($productPerWarehouseData)),
            'productPerWarehouseValues' => json_encode(array_values($productPerWarehouseData)),
        ]);
    }
}
