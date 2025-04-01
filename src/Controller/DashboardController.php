<?php
// src/Controller/DashboardController.php

namespace App\Controller;

use App\Repository\WarehouseRepository;
use App\Repository\ProductRepository;
use App\Repository\InventoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function index(
        WarehouseRepository $warehouseRepository,
        ProductRepository $productRepository,
        InventoryRepository $inventoryRepository
    ): Response {
        // 📊 Получаем статистику
        $warehouseCount = count($warehouseRepository->findAll());
        $productCount = count($productRepository->findAll());

        // 🔢 Считаем количество всех товаров в инвентаре
        $inventories = $inventoryRepository->findAll();
        $totalQuantity = 0;
        $totalStockValue = 0.0; // 💰 Общая стоимость

        foreach ($inventories as $inventory) {
            $totalQuantity += $inventory->getQuantity();
            $totalStockValue += $inventory->getQuantity() * floatval($inventory->getPrice()); // 🔥 Исправленный расчет
        }

        return $this->render('dashboard/index.html.twig', [
            'warehouseCount'  => $warehouseCount,
            'productCount'    => $productCount,
            'totalQuantity'   => $totalQuantity,
            'totalStockValue' => $totalStockValue, // Передаем в шаблон без форматирования
        ]);
    }
}
