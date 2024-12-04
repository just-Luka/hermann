<?php

declare(strict_types=1);

namespace App\Controller\TradingApp;

use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

final class UserController extends AbstractController
{
    #[Route('/me', methods: ['GET'])]
    public function me(Request $request, JWTTokenManagerInterface $jwtManager, UserRepository $userRepository): JsonResponse
    {
        $token = $this->extractTokenFromRequest($request);

        if (!$token) {
            throw new AuthenticationException('No Bearer token found in Authorization header');
        }
    
        // Decode and validate the JWT token
        try {
            $data = $jwtManager->parse($token);
        } catch (\Exception $e) {
            throw new AuthenticationException('Invalid JWT token');
        }

        $user = $userRepository->findOneBy(['username' => $data['username']]);
        if (! $user) {
            throw $this->createNotFoundException('User no longer exists!');
        }

        return $this->json([
            'id' => $user->getId(),
            'created_at' => $user->getCreatedAt(),
            'updated_at' => $user->getUpdatedAt(),
            'first_name' => $user->getFirstName(),
            'username' => $user->getUsername(),
        ]);
    }

    public function extractTokenFromRequest(Request $request): ?string
    {
        $authHeader = $request->headers->get('Authorization');

        if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $matches[1]; // Return the extracted token
        }

        return null; // No token found
    }
}