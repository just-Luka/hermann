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
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

final class AuthController extends AbstractController
{
    private $logger;

    public function __construct(
        LoggerInterface $logger, 
    )
    {
        $this->logger = $logger;
    }
    

    #[Route('/tg-auth', methods: ['POST'])]
    public function authorization(Request $request, JWTTokenManagerInterface $jwtManager, UserRepository $userRepository, EntityManagerInterface $entityManager, TradingBotService $tradingBotService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
       
        if (! $tradingBotService->isValidTelegramAuth($data)) {
            throw new BadRequestHttpException('Invalid Telegram authentication data');
        }
            
        $user = $userRepository->findByTelegramId($data['id']);
        if (! $user) { // Register new user
            $user = new User();
            $user->setFirstName($data['first_name'] ?? $data['username'] ?? 'No name');
            $user->setLastName($data['last_name'] ?? null);
            $user->setUsername($data['username'] ?? null);
            $user->setTelegramId((string) $data['id']);
            $user->setPhotoUrl($data['photo_url'] ?? null);
            $user->setTelegramAuthDate((string) $data['auth_date']);
            $user->setTelegramHash($data['hash']);
            $user->setCreatedAt(new DateTimeImmutable());
            $user->setUpdatedAt(new DateTimeImmutable());

            $entityManager->persist($user);
            $entityManager->flush();
        }

        $token = $jwtManager->create($user);

        $response = new JsonResponse([
            'status' => 'ok',
        ]);
        
        $response->headers->setCookie(
            Cookie::create('otk', $token, time() + 3600, '/', null, true, true, false, 'Strict')
        );
        $this->logger->warning($token);
        return $response;
    }
}