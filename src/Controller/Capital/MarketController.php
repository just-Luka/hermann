<?php

declare(strict_types=1);

namespace App\Controller\Capital;

use App\Service\Capital\Market\MarketCapitalService;
use App\Service\Capital\Trading\PositionsCapitalService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;

final class MarketController extends AbstractController
{
    private MarketCapitalService $marketCapital;

    public function __construct(MarketCapitalService $marketCapital)
    {
        $this->marketCapital = $marketCapital;
    }

    #[Route('/market/{epic}', methods: ['GET'])]
    public function read(Request $request, string $epic): JsonResponse
    {
        $res = $this->marketCapital->singleMarketInfo($epic);

        return $this->json($res);
    }
    
    #[Route('/market-search', methods: ['GET'])]
    public function searchPairs(Request $request): JsonResponse
    {
       $keyword = $request->query->get('keyword');

       if (!isset($keyword)) {
           throw new HttpException(400, 'Keyword is required');
       }

       $res = $this->marketCapital->pairsSearch($keyword);

       return $this->json($res);
    }
}