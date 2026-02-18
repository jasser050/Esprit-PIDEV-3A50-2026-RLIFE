<?php

namespace App\Controller;

use App\Entity\Flashcard;
use App\Entity\FlashcardTranslation;
use App\Repository\FlashcardTranslationRepository;
use App\Service\TranslationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

#[Route('/api/translation')]
class TranslationController extends AbstractController
{
    public function __construct(
        private TranslationService $translationService,
        private EntityManagerInterface $em,
        private LoggerInterface $logger
    ) {
    }

    #[Route('/languages', name: 'api_supported_languages', methods: ['GET'])]
    public function getSupportedLanguages(): JsonResponse
    {
        try {
            // ⚠️ TEMPORAIRE : Pas de vérification d'authentification
            $languages = $this->translationService->getSupportedLanguages();
            
            return $this->json([
                'success' => true,
                'languages' => $languages,
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Error in getSupportedLanguages', [
                'error' => $e->getMessage()
            ]);
            
            return $this->json([
                'success' => false,
                'error' => 'Erreur serveur: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/flashcard/{id}/list', name: 'api_list_translations', methods: ['GET'])]
    public function listTranslations(
        Flashcard $flashcard,
        FlashcardTranslationRepository $translationRepo
    ): JsonResponse {
        try {
            // ⚠️ TEMPORAIRE : Pas de vérification de propriétaire
            $translations = $translationRepo->findBy(['flashcard' => $flashcard]);

            $result = array_map(function(FlashcardTranslation $t) {
                return [
                    'id' => $t->getId(),
                    'language' => $t->getLanguage(),
                    'language_name' => $t->getLanguageName(),
                    'language_flag' => $t->getLanguageFlag(),
                    'titre' => $t->getTitre(),
                    'question' => $t->getQuestion(),
                    'reponse' => $t->getReponse(),
                    'description' => $t->getDescription(),
                    'difficulty' => $t->getDifficultyLevel(),
                    'created_at' => $t->getCreatedAt()?->format('Y-m-d H:i:s'),
                    'updated_at' => $t->getUpdatedAt()?->format('Y-m-d H:i:s'),
                    'is_verified' => $t->isVerified(),
                ];
            }, $translations);

            return $this->json([
                'success' => true,
                'count' => count($result),
                'translations' => $result,
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Error in listTranslations', [
                'error' => $e->getMessage()
            ]);
            
            return $this->json([
                'success' => false,
                'error' => 'Erreur serveur: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/flashcard/{id}', name: 'api_translate_flashcard', methods: ['POST'])]
    public function translateFlashcard(
        Flashcard $flashcard,
        Request $request,
        FlashcardTranslationRepository $translationRepo
    ): JsonResponse {
        try {
            // ⚠️ TEMPORAIRE : Pas de vérification de propriétaire pour tester
            
            $data = json_decode($request->getContent(), true);
            
            if (!$data) {
                return $this->json(['success' => false, 'error' => 'Requête invalide'], 400);
            }

            $targetLanguages = $data['languages'] ?? null;
            $shouldSave = $data['save'] ?? false;
            $sourceLang = $data['source_lang'] ?? 'fr';

            if (!$targetLanguages || !is_array($targetLanguages) || count($targetLanguages) === 0) {
                return $this->json(['success' => false, 'error' => 'Langues cibles manquantes'], 400);
            }

            $flashcardData = [
                'titre' => $flashcard->getTitre() ?? '',
                'question' => $flashcard->getQuestion() ?? '',
                'reponse' => $flashcard->getReponse() ?? '',
                'description' => $flashcard->getDescription(),
            ];

            $translations = $this->translationService->translateFlashcard(
                $flashcardData,
                $targetLanguages,
                $sourceLang,
                $flashcard->getNiveauDifficulte() ?? 2
            );

            if ($shouldSave) {
                foreach ($translations as $lang => $result) {
                    if (!$result['success']) continue;

                    $existing = $translationRepo->findOneBy([
                        'flashcard' => $flashcard,
                        'language' => $lang,
                    ]);

                    if ($existing) {
                        $existing->setTitre($result['translation']['titre']);
                        $existing->setQuestion($result['translation']['question']);
                        $existing->setReponse($result['translation']['reponse']);
                        $existing->setDescription($result['translation']['description'] ?? null);
                        $existing->setDifficultyLevel($result['difficulty']);
                        $existing->setTranslatorNotes($result['translation']['notes'] ?? null);
                        $existing->setUpdatedAt(new \DateTime());
                    } else {
                        $translation = new FlashcardTranslation();
                        $translation->setFlashcard($flashcard);
                        $translation->setLanguage($lang);
                        $translation->setTitre($result['translation']['titre']);
                        $translation->setQuestion($result['translation']['question']);
                        $translation->setReponse($result['translation']['reponse']);
                        $translation->setDescription($result['translation']['description'] ?? null);
                        $translation->setDifficultyLevel($result['difficulty']);
                        $translation->setTranslatorNotes($result['translation']['notes'] ?? null);
                        $this->em->persist($translation);
                    }
                }
                
                $this->em->flush();
            }

            return $this->json([
                'success' => true,
                'translations' => $translations,
                'saved' => $shouldSave,
                'count' => count($translations),
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error in translateFlashcard', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->json([
                'success' => false,
                'error' => 'Erreur serveur: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}', name: 'api_delete_translation', methods: ['DELETE'])]
    public function deleteTranslation(FlashcardTranslation $translation): JsonResponse
    {
        try {
            $this->em->remove($translation);
            $this->em->flush();

            return $this->json([
                'success' => true,
                'message' => 'Traduction supprimée avec succès'
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Error in deleteTranslation', [
                'error' => $e->getMessage()
            ]);
            
            return $this->json([
                'success' => false,
                'error' => 'Erreur serveur: ' . $e->getMessage()
            ], 500);
        }
    }
}