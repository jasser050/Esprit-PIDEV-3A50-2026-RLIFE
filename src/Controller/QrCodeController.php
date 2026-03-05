<?php

namespace App\Controller;

use App\Entity\Deck;
use App\Repository\DeckRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/qr')]
class QrCodeController extends AbstractController
{
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    // ─────────────────────────────────────────────────────────────
    //  GENERATE QR CODE IMAGE  –  /qr/deck/{id}
    // ─────────────────────────────────────────────────────────────

    #[Route('/deck/{id}', name: 'app_qrcode_generate', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function generateQrCode(Deck $deck, Request $request): Response
    {
        // Vérifier que l'utilisateur est propriétaire
        if ($deck->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Access denied.');
        }

        // Token signé HMAC
        $token = $this->makeToken($deck->getId());

        // ✅ Construire l'URL d'import (avec vraie IP réseau)
        $importUrl = $this->buildNetworkUrl($request, $token);

        // 🎨 OPTION 1 : QRCode Monkey API (Plus beau, personnalisable)
        // Documentation: https://www.qrcode-monkey.com/qr-code-api-with-logo/
        try {
            $response = $this->httpClient->request('POST', 'https://api.qrcode-monkey.com/qr/custom', [
                'json' => [
                    'data' => $importUrl,
                    'config' => [
                        'body' => 'square',
                        'eye' => 'frame0',
                        'eyeBall' => 'ball0',
                        'erf1' => [],
                        'erf2' => [],
                        'erf3' => [],
                        'brf1' => [],
                        'brf2' => [],
                        'brf3' => [],
                        'bodyColor' => '#000000',
                        'bgColor' => '#FFFFFF',
                        'eye1Color' => '#000000',
                        'eye2Color' => '#000000',
                        'eye3Color' => '#000000',
                        'eyeBall1Color' => '#000000',
                        'eyeBall2Color' => '#000000',
                        'eyeBall3Color' => '#000000',
                        'gradientColor1' => '',
                        'gradientColor2' => '',
                        'gradientType' => 'linear',
                        'gradientOnEyes' => false,
                    ],
                    'size' => 600,
                    'download' => false,
                    'file' => 'png',
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]);

            $imageData = $response->getContent();
            
            return new Response($imageData, 200, [
                'Content-Type' => 'image/png',
                'Cache-Control' => 'public, max-age=3600',
            ]);

        } catch (\Throwable $e) {
            // 🔥 FALLBACK : API qrserver.com (si QRCode Monkey échoue)
            $qrApiUrl = sprintf(
                'https://api.qrserver.com/v1/create-qr-code/?size=600x600&color=000000&bgcolor=ffffff&margin=20&data=%s',
                urlencode($importUrl)
            );
            
            return $this->redirect($qrApiUrl);
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  SHARE PAGE  –  /qr/share/{id}
    // ─────────────────────────────────────────────────────────────

    #[Route('/share/{id}', name: 'app_qrcode_share', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function shareDeck(Deck $deck, Request $request): Response
    {
        if ($deck->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Access denied.');
        }

        $token = $this->makeToken($deck->getId());
        $importUrl = $this->buildNetworkUrl($request, $token);

        // 🔥 URL du QR Code via notre endpoint
        $qrCodeUrl = $this->generateUrl('app_qrcode_generate', ['id' => $deck->getId()], true);

        // URL de téléchargement haute résolution (via qrserver en fallback)
        $qrDownloadUrl = sprintf(
            'https://api.qrserver.com/v1/create-qr-code/?size=1000x1000&format=png&margin=20&data=%s',
            urlencode($importUrl)
        );

        return $this->render('qrcode/share.html.twig', [
            'deck'            => $deck,
            'shareToken'      => $token,
            'importUrl'       => $importUrl,
            'qrCodeUrl'       => $qrCodeUrl,
            'qrDownloadUrl'   => $qrDownloadUrl,
            'flashcardsCount' => count($deck->getFlashcards()),
            'detectedIp'      => $this->detectNetworkIp(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    //  IMPORT  –  /qr/import/{token}
    //  GET  = prévisualisation (sans compte)
    //  POST = import (connexion requise)
    // ─────────────────────────────────────────────────────────────

    #[Route('/import/{token}', name: 'app_qrcode_import', methods: ['GET', 'POST'])]
    public function importDeck(
        string $token,
        DeckRepository $deckRepository,
        EntityManagerInterface $em,
        Request $request
    ): Response {
        // Vérifier la signature du token
        $deckId = $this->verifyToken($token);

        if (!$deckId) {
            $this->addFlash('error', '❌ Invalid or expired sharing link.');
            return $this->redirectToRoute('app_revisions');
        }

        $originalDeck = $deckRepository->find($deckId);

        if (!$originalDeck) {
            $this->addFlash('error', '❌ Deck not found.');
            return $this->redirectToRoute('app_revisions');
        }

        // GET : afficher la preview (accessible sans compte)
        if ($request->isMethod('GET')) {
            return $this->render('qrcode/import.html.twig', [
                'deck'            => $originalDeck,
                'flashcardsCount' => count($originalDeck->getFlashcards()),
                'token'           => $token,
                'isLoggedIn'      => $this->getUser() !== null,
            ]);
        }

        // POST : importer → connexion obligatoire
        if (!$this->getUser()) {
            $this->addFlash('warning', '⚠️ Please log in to import this deck.');
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isCsrfTokenValid('import_deck', $request->request->get('_token'))) {
            $this->addFlash('error', '❌ Invalid CSRF token.');
            return $this->redirectToRoute('app_qrcode_import', ['token' => $token]);
        }

        // Vérifier si l'utilisateur a déjà ce deck
        $existingDeck = $deckRepository->findOneBy([
            'user' => $this->getUser(),
            'titre' => $originalDeck->getTitre() . ' (Copy)',
        ]);

        if ($existingDeck) {
            $this->addFlash('warning', '⚠️ You already have a copy of this deck.');
            return $this->redirectToRoute('app_revisions_deck_study', ['id' => $existingDeck->getId()]);
        }

        // Copier le deck
        $newDeck = new \App\Entity\Deck();
        $newDeck->setUser($this->getUser());
        $newDeck->setTitre($originalDeck->getTitre() . ' (Copy)');
        $newDeck->setDescription($originalDeck->getDescription());
        $newDeck->setImage($originalDeck->getImage());
        $newDeck->setPdf($originalDeck->getPdf());
        $newDeck->setDateCreation(new \DateTime());
        $newDeck->setMatiere($originalDeck->getMatiere());
        $newDeck->setNiveau($originalDeck->getNiveau());
        $em->persist($newDeck);

        // Copier les flashcards
        foreach ($originalDeck->getFlashcards() as $orig) {
            $fc = new \App\Entity\Flashcard();
            $fc->setDeck($newDeck);
            $fc->setCreatedBy($this->getUser());
            $fc->setTitre($orig->getTitre());
            $fc->setQuestion($orig->getQuestion());
            $fc->setReponse($orig->getReponse());
            $fc->setDescription($orig->getDescription());
            $fc->setImage($orig->getImage());
            $fc->setPdf($orig->getPdf());
            $fc->setNiveauDifficulte($orig->getNiveauDifficulte());
            $fc->setEtat($orig->getEtat());
            $em->persist($fc);
        }

        $em->flush();

        $n = count($originalDeck->getFlashcards());
        $this->addFlash('success', sprintf(
            '✅ Deck "%s" imported successfully! (%d flashcard%s)',
            $newDeck->getTitre(), $n, $n > 1 ? 's' : ''
        ));

        return $this->redirectToRoute('app_revisions_deck_study', ['id' => $newDeck->getId()]);
    }

    // ─────────────────────────────────────────────────────────────
    //  API INFO  –  /qr/api/deck-info/{token}
    // ─────────────────────────────────────────────────────────────

    #[Route('/api/deck-info/{token}', name: 'app_qrcode_api_info', methods: ['GET'])]
    public function getDeckInfo(string $token, DeckRepository $deckRepository): JsonResponse
    {
        $deckId = $this->verifyToken($token);
        if (!$deckId) {
            return $this->json(['error' => 'Invalid token'], 400);
        }

        $deck = $deckRepository->find($deckId);
        if (!$deck) {
            return $this->json(['error' => 'Deck not found'], 404);
        }

        return $this->json([
            'id'              => $deck->getId(),
            'titre'           => $deck->getTitre(),
            'matiere'         => $deck->getMatiere(),
            'niveau'          => $deck->getNiveau(),
            'flashcardsCount' => count($deck->getFlashcards()),
            'author'          => $deck->getUser()?->getEmail(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    //  GENERATE CUSTOM QR CODE WITH COLORS
    // ─────────────────────────────────────────────────────────────

    #[Route('/deck/{id}/custom', name: 'app_qrcode_generate_custom', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function generateCustomQrCode(
        Deck $deck, 
        Request $request,
        string $color = '000000',
        string $bgColor = 'FFFFFF'
    ): Response {
        if ($deck->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Access denied.');
        }

        $token = $this->makeToken($deck->getId());
        $importUrl = $this->buildNetworkUrl($request, $token);

        // Récupérer les couleurs depuis les paramètres GET
        $color = $request->query->get('color', '000000');
        $bgColor = $request->query->get('bgcolor', 'FFFFFF');

        try {
            $response = $this->httpClient->request('POST', 'https://api.qrcode-monkey.com/qr/custom', [
                'json' => [
                    'data' => $importUrl,
                    'config' => [
                        'body' => 'square',
                        'eye' => 'frame0',
                        'eyeBall' => 'ball0',
                        'bodyColor' => '#' . $color,
                        'bgColor' => '#' . $bgColor,
                        'eye1Color' => '#' . $color,
                        'eye2Color' => '#' . $color,
                        'eye3Color' => '#' . $color,
                        'eyeBall1Color' => '#' . $color,
                        'eyeBall2Color' => '#' . $color,
                        'eyeBall3Color' => '#' . $color,
                    ],
                    'size' => 600,
                    'download' => false,
                    'file' => 'png',
                ],
            ]);

            $imageData = $response->getContent();
            
            return new Response($imageData, 200, [
                'Content-Type' => 'image/png',
                'Cache-Control' => 'public, max-age=3600',
            ]);

        } catch (\Throwable $e) {
            // Fallback vers qrserver
            $qrApiUrl = sprintf(
                'https://api.qrserver.com/v1/create-qr-code/?size=600x600&color=%s&bgcolor=%s&margin=20&data=%s',
                $color,
                $bgColor,
                urlencode($importUrl)
            );
            
            return $this->redirect($qrApiUrl);
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  HELPERS PRIVÉS
    // ─────────────────────────────────────────────────────────────

    /**
     * Génère un token HMAC signé : idDeck + expiration 30 jours.
     */
    private function makeToken(int $deckId): string
    {
        $secret    = $this->getParameter('kernel.secret');
        $expires   = time() + (30 * 24 * 3600); // 30 jours
        $payload   = $deckId . ':' . $expires;
        $signature = hash_hmac('sha256', $payload, $secret);
        return rtrim(base64_encode($payload . ':' . $signature), '=');
    }

    /**
     * Vérifie le token HMAC et retourne l'idDeck ou null si invalide.
     */
    private function verifyToken(string $token): ?int
    {
        try {
            $decoded = base64_decode(str_pad($token, strlen($token) + (4 - strlen($token) % 4) % 4, '='));
            if (!$decoded) return null;

            $lastColon = strrpos($decoded, ':');
            if ($lastColon === false) return null;

            $signature = substr($decoded, $lastColon + 1);
            $payload   = substr($decoded, 0, $lastColon);

            $parts = explode(':', $payload);
            if (count($parts) !== 2) return null;

            [$deckId, $expires] = $parts;

            if (time() > (int)$expires) return null;

            $secret      = $this->getParameter('kernel.secret');
            $expected    = hash_hmac('sha256', $payload, $secret);
            if (!hash_equals($expected, $signature)) return null;

            return (int)$deckId;

        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Construit l'URL d'import accessible depuis le réseau.
     */
    private function buildNetworkUrl(Request $request, string $token): string
    {
        $path = $this->generateUrl('app_qrcode_import', ['token' => $token]);

        // 🔥 PRIORITÉ 1 : URL publique depuis .env
        $publicUrl = $_ENV['APP_PUBLIC_URL'] ?? null;
        if (!empty($publicUrl)) {
            return rtrim($publicUrl, '/') . $path;
        }

        // 🔥 PRIORITÉ 2 : Détection IP réseau locale
        $scheme = $request->getScheme();
        $port   = $request->getPort();
        $host   = $request->getHost();

        if (in_array($host, ['localhost', '127.0.0.1', '::1'])) {
            $networkIp = $this->detectNetworkIp();
            if ($networkIp) {
                $host = $networkIp;
            }
        }

        $portSuffix = '';
        if (($scheme === 'http' && $port !== 80) || ($scheme === 'https' && $port !== 443)) {
            $portSuffix = ':' . $port;
        }

        return sprintf('%s://%s%s%s', $scheme, $host, $portSuffix, $path);
    }

    /**
     * 🔥 Détecte l'IP réseau locale (pas 127.0.0.1).
     */
    private function detectNetworkIp(): ?string
    {
        // Méthode 1 : Socket UDP
        if (function_exists('socket_create')) {
            try {
                $sock = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
                if ($sock !== false) {
                    if (@socket_connect($sock, '8.8.8.8', 80)) {
                        if (@socket_getsockname($sock, $ip)) {
                            @socket_close($sock);
                            if ($this->isValidNetworkIp($ip)) {
                                return $ip;
                            }
                        }
                    }
                    @socket_close($sock);
                }
            } catch (\Throwable $e) {}
        }

        // Méthode 2 : Hostname DNS
        try {
            $hostname = gethostname();
            if ($hostname && $hostname !== 'localhost') {
                $ip = gethostbyname($hostname);
                if ($ip !== $hostname && $this->isValidNetworkIp($ip)) {
                    return $ip;
                }
            }
        } catch (\Throwable $e) {}

        // Méthode 3 : Variables serveur
        foreach (['SERVER_ADDR', 'LOCAL_ADDR'] as $key) {
            $ip = $_SERVER[$key] ?? null;
            if ($ip && $this->isValidNetworkIp($ip)) {
                return $ip;
            }
        }

        return null;
    }

    /**
     * Valide qu'une IP est utilisable.
     */
    private function isValidNetworkIp(?string $ip): bool
    {
        if (empty($ip)) {
            return false;
        }

        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }

        if (in_array($ip, ['127.0.0.1', '0.0.0.0', '::1'])) {
            return false;
        }

        return true;
    }
}
