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
    private $positionsCapital;

    public function __construct(PositionsCapitalService $positionsCapital)
    {
        $this->positionsCapital = $positionsCapital;
    }

    #[Route('/position/create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $input = $request->request->all();

        $res = $this->positionsCapital->create();
        return $this->json($res);
    }

    #[Route('/position/show/{id}', methods: ['GET'])]
    public function show($id): JsonResponse
    {
        $res = $this->positionsCapital->singlePosition($id);

        return $this->json($res);
    }
}