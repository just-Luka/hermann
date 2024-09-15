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
    private $logger;
    private $user;

    public function __construct(LoggerInterface $logger, UserRepository $user)
    {
        $this->logger = $logger;
        $this->user = $user;
    }
    

    #[Route('/me', methods: ['GET'])]
    public function show(Request $request, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $token = $this->extractTokenFromRequest($request);

        if (!$token) {
            throw new AuthenticationException('No Bearer token found in Authorization header');
        }
    
        // Decode and validate the JWT token
        try {
            $decodedData = $jwtManager->parse($token);
        } catch (\Exception $e) {
            throw new AuthenticationException('Invalid JWT token');
        }

        $this->logger->warning(json_encode($decodedData));
     
        
        return $this->json([]);
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