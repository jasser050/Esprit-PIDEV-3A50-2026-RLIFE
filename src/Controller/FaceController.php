<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[Route('/face', name: 'app_face_')]
class FaceController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $em,
        private TokenStorageInterface $tokenStorage,
    ) {}

    /**
     * Save face descriptor for the currently logged-in user
     * OR for a user who just registered (by email in session)
     */
    #[Route('/save', name: 'save', methods: ['POST'])]
    public function save(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $descriptor = $data['descriptor'] ?? null;
        $email = $data['email'] ?? null;

        if (!$descriptor || !is_array($descriptor)) {
            return new JsonResponse(['success' => false, 'message' => 'No face data received.'], 400);
        }

        // Try to find user by email (from registration) or current user
        $user = null;

        if ($email) {
            $user = $this->userRepository->findOneBy(['email' => $email]);
        }

        if (!$user && $this->getUser()) {
            $user = $this->getUser();
        }

        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'User not found.'], 404);
        }

        $user->setFaceDescriptor($descriptor);
        $this->em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Face registered successfully!']);
    }

    /**
     * Compare face descriptor against all registered faces and authenticate
     */
    #[Route('/login', name: 'login', methods: ['POST'])]
    public function faceLogin(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $inputDescriptor = $data['descriptor'] ?? null;

        if (!$inputDescriptor || !is_array($inputDescriptor)) {
            return new JsonResponse(['success' => false, 'message' => 'No face data received.'], 400);
        }

        // Fetch all users with a registered face
        $users = $this->userRepository->createQueryBuilder('u')
            ->where('u.faceDescriptor IS NOT NULL')
            ->getQuery()
            ->getResult();

        if (empty($users)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'No registered faces found in the system.'
            ], 404);
        }

        $threshold = 0.55; // Euclidean distance threshold (lower = stricter)
        $bestMatch = null;
        $bestDistance = PHP_FLOAT_MAX;

        foreach ($users as $user) {
            $storedDescriptor = $user->getFaceDescriptor();
            if (!$storedDescriptor || count($storedDescriptor) !== count($inputDescriptor)) {
                continue;
            }

            // Calculate Euclidean distance between descriptors
            $distance = $this->euclideanDistance($inputDescriptor, $storedDescriptor);

            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $bestMatch = $user;
            }
        }

        if ($bestMatch && $bestDistance < $threshold) {
            // Check if user is verified and not banned
            if (!$bestMatch->isVerified()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Your account is not verified yet. Please check your email.'
                ], 403);
            }

            if ($bestMatch->isBanned()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Your account has been suspended.'
                ], 403);
            }

            // Authenticate the user via Symfony session
            $token = new UsernamePasswordToken($bestMatch, 'main', $bestMatch->getRoles());
            $this->tokenStorage->setToken($token);
            $request->getSession()->set('_security_main', serialize($token));

            return new JsonResponse([
                'success' => true,
                'message' => 'Face recognized! Welcome back, ' . $bestMatch->getFirstName() . '!',
                'redirect' => $this->generateUrl('app_dashboard'),
                'distance' => round($bestDistance, 4),
            ]);
        }

        return new JsonResponse([
            'success' => false,
            'message' => 'Face not recognized. Please try again or use your password.',
            'distance' => $bestMatch ? round($bestDistance, 4) : null,
        ], 401);
    }

    private function euclideanDistance(array $a, array $b): float
    {
        $sum = 0.0;
        $count = min(count($a), count($b));
        for ($i = 0; $i < $count; $i++) {
            $diff = (float)$a[$i] - (float)$b[$i];
            $sum += $diff * $diff;
        }
        return sqrt($sum);
    }
}
