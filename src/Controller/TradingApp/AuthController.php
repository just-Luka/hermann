<?php

declare(strict_types=1);

namespace App\Controller\TradingApp;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\Telegram\Bot\TradingBotService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

final class AuthController extends AbstractController
{
    /**
     * @param Request $request
     * @param JWTTokenManagerInterface $jwtManager
     * @param UserRepository $userRepository
     * @param EntityManagerInterface $entityManager
     * @param TradingBotService $tradingBotService
     * @return JsonResponse
     */
    #[Route('/tg-auth', methods: ['POST'])]
    public function authorization(
        Request $request,
        JWTTokenManagerInterface $jwtManager,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        TradingBotService $tradingBotService
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
       
        if (! $tradingBotService->isValidTelegramAuth($data)) {
            $this->json('Bad request', 400);
        }
            
        $user = $userRepository->findByTelegramId($data['id']);
        if (! $user) { // Register new user
            $user = (new User())
                ->setTelegramId($data['id'])
                ->setLastName($data['last_name'] ?? null)
                ->setUsername($data['username'] ?? null)
                ->setTelegramId((string) $data['id'])
                ->setPhotoUrl($data['photo_url'] ?? null)
                ->setTelegramAuthDate((string) $data['auth_date'])
                ->setTelegramHash($data['hash'])
                ->setCreatedAt(new DateTimeImmutable())
                ->setUpdatedAt(new DateTimeImmutable());

            $entityManager->persist($user);
            $entityManager->flush();
        }

        $token = $jwtManager->create($user);

        return $this->json([
            'status' => 'ok',
            'token' => $token,
        ]);
    }
}