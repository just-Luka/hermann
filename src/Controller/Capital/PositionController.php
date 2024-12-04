<?php

declare(strict_types=1);

namespace App\Controller\Capital;

use App\Service\Capital\Trading\PositionsCapitalService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class PositionController extends AbstractController
{
    public function __construct(
        private readonly PositionsCapitalService $positionsCapital
    ) {}

    #[Route('/position/create', methods: ['POST'])]
    public function create(Request $request): void
    {
       # maintenance
    }

    #[Route('/position/show/{id}', methods: ['GET'])]
    public function show($id): void
    {
        # maintenance
    }
}