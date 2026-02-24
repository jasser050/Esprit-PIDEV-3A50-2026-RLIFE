<?php

namespace App\Controller;

use App\Entity\ChatMessage;
use App\Entity\User;
use App\Repository\ChatMessageRepository;
use App\Service\GeminiService;
use App\Service\UserCommandExecutor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/ai-terminal')]
#[IsGranted('ROLE_ADMIN')]
class AITerminalController extends AbstractController
{
    public function __construct(
        private GeminiService $geminiService,
        private UserCommandExecutor $commandExecutor,
        private EntityManagerInterface $entityManager,
        private ChatMessageRepository $chatMessageRepository
    ) {}

    /**
     * Display the AI Terminal page
     */
    #[Route('', name: 'app_admin_ai_terminal', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/ai_terminal/index.html.twig');
    }

    /**
     * Handle chat messages
     */
    #[Route('/chat', name: 'app_admin_ai_terminal_chat', methods: ['POST'])]
    public function chat(Request $request): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        
        $data = json_decode($request->getContent(), true);
        $userMessage = $data['message'] ?? '';

        if (empty($userMessage)) {
            return $this->json([
                'success' => false,
                'message' => 'Message cannot be empty',
            ], 400);
        }

        try {
            // Don't save user message yet - wait until command executes successfully
            // This prevents foreign key conflicts if user tries to delete themselves or related entities
            
            // Get conversation history (without current message)
            $history = $this->chatMessageRepository->getRecentHistory($currentUser, 10);
            $history = array_reverse($history); // Oldest first

            // Build messages for AI (including current message in context only)
            $messages = $this->buildAIMessages($history, $userMessage);

            // Call Gemini AI
            try {
                $aiResponse = $this->geminiService->chat($messages);
            } catch (\Exception $e) {
                // Return detailed error for debugging
                return $this->json([
                    'success' => false,
                    'message' => 'Gemini API Error: ' . $e->getMessage(),
                    'debug' => [
                        'error' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]
                ], 500);
            }

            if (!$aiResponse) {
                return $this->json([
                    'success' => false,
                    'message' => 'AI service returned empty response. Please try again.',
                ], 500);
            }

            // Parse AI response
            $parsedCommand = $this->parseAIResponse($aiResponse);

            if (!$parsedCommand) {
                // AI couldn't parse the command, return the raw response
                $assistantMessage = new ChatMessage();
                $assistantMessage->setUser($currentUser);
                $assistantMessage->setRole('assistant');
                $assistantMessage->setContent($aiResponse);
                $this->entityManager->persist($assistantMessage);
                $this->entityManager->flush();

                return $this->json([
                    'success' => false,
                    'message' => $aiResponse,
                    'action' => null,
                    'result' => null,
                ]);
            }

            // Execute the command
            $executionResult = $this->commandExecutor->execute($parsedCommand);

            // Command executed successfully - now save chat history
            // Save user message (now that command is done and won't conflict)
            $userChatMessage = new ChatMessage();
            $userChatMessage->setUser($currentUser);
            $userChatMessage->setRole('user');
            $userChatMessage->setContent($userMessage);
            $this->entityManager->persist($userChatMessage);

            // Save assistant response
            $assistantMessage = new ChatMessage();
            $assistantMessage->setUser($currentUser);
            $assistantMessage->setRole('assistant');
            $assistantMessage->setContent($executionResult['message']);
            $this->entityManager->persist($assistantMessage);
            
            // Flush chat history to database
            try {
                $this->entityManager->flush();
            } catch (\Exception $e) {
                // Chat history save failed, but command succeeded
                // This is non-critical, just log and continue
                error_log('Failed to save chat history: ' . $e->getMessage());
            }

            return $this->json([
                'success' => $executionResult['success'],
                'message' => $executionResult['message'],
                'action' => $parsedCommand['action'] ?? null,
                'result' => $executionResult['result'],
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get chat history
     */
    #[Route('/history', name: 'app_admin_ai_terminal_history', methods: ['GET'])]
    public function history(): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        
        $history = $this->chatMessageRepository->getRecentHistory($currentUser, 50);
        $history = array_reverse($history); // Oldest first

        $messages = array_map(function(ChatMessage $msg) {
            return [
                'id' => $msg->getId(),
                'role' => $msg->getRole(),
                'content' => $msg->getContent(),
                'createdAt' => $msg->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }, $history);

        return $this->json(['messages' => $messages]);
    }

    /**
     * Clear chat history
     */
    #[Route('/clear', name: 'app_admin_ai_terminal_clear', methods: ['POST'])]
    public function clear(): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        
        $this->chatMessageRepository->clearHistoryForUser($currentUser);

        return $this->json([
            'success' => true,
            'message' => 'Chat history cleared',
        ]);
    }

    /**
     * Build messages array for AI
     */
    private function buildAIMessages(array $history, string $currentMessage): array
    {
        $systemPrompt = $this->getSystemPrompt();

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt]
        ];

        // Add history (excluding system messages and limiting to last 10)
        $historyCount = 0;
        foreach ($history as $msg) {
            if ($historyCount >= 10) break;
            $messages[] = [
                'role' => $msg->getRole(),
                'content' => $msg->getContent()
            ];
            $historyCount++;
        }

        // Add current message
        $messages[] = [
            'role' => 'user',
            'content' => $currentMessage
        ];

        return $messages;
    }

    /**
     * Get the system prompt for AI
     */
    private function getSystemPrompt(): string
    {
        return <<<PROMPT
You are an AI assistant that manages the User database for RLIFE, a student learning platform.

**User Fields (all stored in database):**
REQUIRED for new users:
- firstName, lastName, email

OPTIONAL (with smart defaults):
- username (auto: firstname+lastname in lowercase)
- password (default: "password123")
- gender ("male"|"female"|"other", default: "other")
- avatarType (3D avatar model):
  
  **Male Avatars:**
  - "classic" or "default" → male-avatar.glb
  - "explorer" → male-avatar1.glb
  - "scholar" → male-avatar2.glb
  - "athlete" or "sporty" → male-avatar3.glb
  - "artist" or "artistic" → male-avatar4.glb
  - "tech" or "developer" or "coder" → male-avatar5.glb
  - "creative" → male-avatar6.glb
  - "leader" or "professional" → male-avatar7.glb
  
  **Female Avatars:**
  - "classic" or "default" → female-avatar.glb
  - "elegant" → female-avatar2.glb
  - "professional" → female-avatar3.glb
  
  **Numbers work too:**
  - "avatar 3" or "style 3" → male-avatar3.glb (if male) or female-avatar3.glb (if female)
  
- phoneNumber, bio, studentId, university

ADVANCED (UserSettings - optional):
- studyLevel: "beginner"|"intermediate"|"advanced" (default: "beginner")
- weeklyGoal: number of study hours (default: 5)
- interests: array like ["programming", "math", "physics"]
- notifications: true|false (default: true)

**Your Task:**
Parse natural language and respond with ONLY valid JSON:

{
  "action": "CREATE|READ|UPDATE|DELETE",
  "params": {...},
  "message": "friendly response"
}

**CREATE Examples:**

User: "Add student John Doe with email john@test.com"
{
  "action": "CREATE",
  "params": {
    "firstName": "John",
    "lastName": "Doe",
    "email": "john@test.com"
  },
  "message": "Creating John Doe with default settings..."
}

User: "Add a new user with email sarah@mit.edu password securePass123 and avatar classic and she is a female from MIT university"
{
  "action": "CREATE",
  "params": {
    "firstName": "Sarah",
    "lastName": "Smith",
    "email": "sarah@mit.edu",
    "password": "securePass123",
    "gender": "female",
    "avatarType": "female-avatar.glb",
    "university": "MIT"
  },
  "message": "Creating Sarah Smith with classic female avatar from MIT..."
}

User: "Create account for Mike Johnson, email mike@test.com, he's male with avatar style 3, beginner level, wants to study 10 hours weekly, interested in programming and math"
{
  "action": "CREATE",
  "params": {
    "firstName": "Mike",
    "lastName": "Johnson",
    "email": "mike@test.com",
    "gender": "male",
    "avatarType": "male-avatar3.glb",
    "studyLevel": "beginner",
    "weeklyGoal": 10,
    "interests": ["programming", "math"]
  },
  "message": "Creating Mike Johnson with custom avatar and study preferences..."
}

User: "i want to add a new user named mohamed brahem with a password : jasserQ0* and an email : mohamed@gmail.com , i want it to be a male and i want it to have as a profile picture the classic avatar"
{
  "action": "CREATE",
  "params": {
    "firstName": "Mohamed",
    "lastName": "Brahem",
    "email": "mohamed@gmail.com",
    "password": "jasserQ0*",
    "gender": "male",
    "avatarType": "male-avatar.glb"
  },
  "message": "Creating Mohamed Brahem with classic male avatar..."
}

User: "please create a user, his name is Alex Turner, email alex@test.com, password securePass, he wants the second male avatar"
{
  "action": "CREATE",
  "params": {
    "firstName": "Alex",
    "lastName": "Turner",
    "email": "alex@test.com",
    "password": "securePass",
    "gender": "male",
    "avatarType": "male-avatar2.glb"
  },
  "message": "Creating Alex Turner with male avatar #2..."
}

**READ Examples:**

User: "Show all users"
{"action": "READ", "params": {"filter": "all"}, "message": "Fetching all users..."}

User: "Find users from Harvard"
{"action": "READ", "params": {"university": "Harvard"}, "message": "Searching Harvard users..."}

**UPDATE Examples:**

User: "Change user 5's email to new@test.com"
{"action": "UPDATE", "params": {"id": 5, "email": "new@test.com"}, "message": "Updating email..."}

User: "Update user 10 to use male avatar style 5"
{"action": "UPDATE", "params": {"id": 10, "avatarType": "male-avatar5.glb"}, "message": "Changing avatar..."}

User: "change user 3's avatar from classic to athlete"
{"action": "UPDATE", "params": {"id": 3, "avatarType": "male-avatar3.glb"}, "message": "Updating avatar to athlete style..."}

User: "i want to change the avatar of user 7 to the tech one"
{"action": "UPDATE", "params": {"id": 7, "avatarType": "male-avatar5.glb"}, "message": "Switching to tech avatar..."}

User: "update user 2 to elegant female avatar"
{"action": "UPDATE", "params": {"id": 2, "avatarType": "female-avatar2.glb"}, "message": "Changing to elegant avatar..."}

**DELETE Examples:**

User: "Delete user 8"
{"action": "DELETE", "params": {"id": 8}, "message": "Deleting user..."}

User: "delete mohamed brahem"
{"action": "DELETE", "params": {"firstName": "Mohamed", "lastName": "Brahem"}, "message": "Deleting Mohamed Brahem..."}

User: "remove the user named john doe"
{"action": "DELETE", "params": {"firstName": "John", "lastName": "Doe"}, "message": "Removing John Doe..."}

User: "delete user with email test@test.com"
{"action": "DELETE", "params": {"email": "test@test.com"}, "message": "Deleting user..."}

**Intelligence Rules:**
1. Understand natural human language - "I want to", "Can you", "Please", "Could you" all mean the same action
2. Infer missing names from context (email john.doe@test.com = John Doe)
3. Extract data from anywhere in sentence:
   - "password jasserQ0*" or "password: jasserQ0*" or "with password jasserQ0*" all work
   - "profile picture classic" or "avatar classic" or "classic avatar" all work
   - "he is male" or "male" or "it's a male" all work
4. **Avatar Name Mapping** (IMPORTANT):
   - Map descriptive names to filenames: "athlete" → male-avatar3.glb, "tech" → male-avatar5.glb
   - "from classic to athlete" means change avatarType from male-avatar.glb to male-avatar3.glb
   - Always convert avatar names to .glb filenames
   - If gender not specified in UPDATE, guess from existing avatar or default to male
5. Avatar numbers: "avatar 3" or "style 3" → male-avatar3.glb (if male) or female-avatar3.glb (if female)
6. Gender detection: "he/his/him" = male, "she/her/hers" = female, pronouns override explicit gender
7. Flexible field names: "profile picture"="avatar", "photo"="avatar", "pic"="avatar"
8. For UPDATE: only include fields that are being changed
9. For DELETE: accept ID, full name (firstName + lastName), or email
   - "delete mohamed brahem" → firstName: "Mohamed", lastName: "Brahem"
   - "remove john doe" → firstName: "John", lastName: "Doe"
   - "delete user 5" → id: 5
10. If unclear, make reasonable assumptions (default password, avatar, etc.)

CRITICAL: Respond with ONLY pure JSON. No markdown, no explanation!
PROMPT;
    }

    /**
     * Parse AI response to extract JSON command
     */
    private function parseAIResponse(string $response): ?array
    {
        // Try to parse as JSON directly
        $decoded = json_decode($response, true);
        if ($decoded !== null && isset($decoded['action'])) {
            return $decoded;
        }

        // Try to extract JSON from markdown code blocks
        if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $response, $matches)) {
            $decoded = json_decode($matches[1], true);
            if ($decoded !== null && isset($decoded['action'])) {
                return $decoded;
            }
        }

        // Try to find any JSON object in the response
        if (preg_match('/\{[^{}]*(?:\{[^{}]*\}[^{}]*)*\}/s', $response, $matches)) {
            $decoded = json_decode($matches[0], true);
            if ($decoded !== null && isset($decoded['action'])) {
                return $decoded;
            }
        }

        return null;
    }
}
