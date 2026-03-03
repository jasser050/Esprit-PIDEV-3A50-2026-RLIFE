<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/settings')]
class SettingsController extends AbstractController
{
    #[Route('', name: 'app_settings', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        // Get current logged-in user
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        // Handle form submission
        if ($request->isMethod('POST')) {
            // Get form data
            $firstName = $request->request->get('first_name');
            $lastName = $request->request->get('last_name');
            $username = $request->request->get('username');
            $email = $request->request->get('email');
            $phoneNumber = $request->request->get('phone_number');
            $bio = $request->request->get('bio');
            $university = $request->request->get('university');
            $studentId = $request->request->get('student_id');
            $gender = $request->request->get('gender');
            
            // Password change fields
            $currentPassword = $request->request->get('current_password');
            $newPassword = $request->request->get('new_password');
            
            // Handle profile picture upload
            $profilePic = $request->files->get('profile_pic');
            if ($profilePic && $profilePic->getSize() > 0) {
                // Validate file
                $allowedMimes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
                $mimeType = $profilePic->getMimeType();
                
                if (!in_array($mimeType, $allowedMimes)) {
                    $this->addFlash('error', 'Invalid file type. Only JPG, PNG, and WEBP are allowed.');
                    return $this->redirectToRoute('app_settings');
                }
                
                if ($profilePic->getSize() > 2 * 1024 * 1024) {
                    $this->addFlash('error', 'File size must be less than 2MB.');
                    return $this->redirectToRoute('app_settings');
                }
                
                // Delete old profile pic if exists
                if ($user->getProfilePic()) {
                    $oldFile = $this->getParameter('kernel.project_dir') . '/public/uploads/profiles/' . $user->getProfilePic();
                    if (file_exists($oldFile)) {
                        unlink($oldFile);
                    }
                }
                
                // Generate unique filename
                $fileName = md5(uniqid()) . '.' . $profilePic->guessExtension();
                
                // Move file to uploads directory
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/profiles';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $profilePic->move($uploadDir, $fileName);
                $user->setProfilePic($fileName);
            }
            
            // Validate required fields
            if (empty($firstName) || empty($lastName) || empty($username) || empty($email)) {
                $this->addFlash('error', 'Please fill in all required fields.');
                return $this->redirectToRoute('app_settings');
            }
            
            // Check if email is being changed and if it's already taken
            if ($email !== $user->getEmail()) {
                $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
                if ($existingUser) {
                    $this->addFlash('error', 'This email is already taken by another user.');
                    return $this->redirectToRoute('app_settings');
                }
            }
            
            // Check if username is being changed and if it's already taken
            if ($username !== $user->getUsername()) {
                $existingUsername = $entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
                if ($existingUsername) {
                    $this->addFlash('error', 'This username is already taken.');
                    return $this->redirectToRoute('app_settings');
                }
            }
            
            // Update user information
            $user->setFirstName($firstName);
            $user->setLastName($lastName);
            $user->setUsername($username);
            $user->setEmail($email);
            $user->setPhoneNumber($phoneNumber ?: null);
            $user->setBio($bio ?: null);
            $user->setUniversity($university ?: null);
            $user->setStudentId($studentId ?: null);
            $user->setGender($gender);
            
            // Handle password change
            if (!empty($currentPassword) && !empty($newPassword)) {
                // Verify current password
                if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                    $this->addFlash('error', 'Current password is incorrect.');
                    return $this->redirectToRoute('app_settings');
                }
                
                // Hash and set new password
                $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
                $user->setPassword($hashedPassword);
                
                $this->addFlash('success', 'Password changed successfully!');
            }
            
            // Save changes
            $entityManager->flush();
            
            $this->addFlash('success', 'Profile updated successfully!');
            return $this->redirectToRoute('app_settings');
        }
        
        // Calculate statistics for the profile - Using count() for reliability
        // Only count modules that exist in user branch
        $totalCourses = count($user->getMatieres());
        
        // Set other stats to 0 for now (these modules will be added later from integration)
        $totalProjects = 0;
        $totalDecks = 0;
        $totalAssignments = 0;
        
        return $this->render('pages/settings/index.html.twig', [
            'total_projects' => $totalProjects,
            'total_courses' => $totalCourses,
            'total_decks' => $totalDecks,
            'total_assignments' => $totalAssignments,
        ]);
    }

    #[Route('/delete', name: 'app_settings_delete', methods: ['POST'])]
    public function deleteAccount(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        // Get password confirmation
        $confirmPassword = $request->request->get('confirm_password');
        
        if (empty($confirmPassword)) {
            $this->addFlash('error', 'Please enter your password to confirm account deletion.');
            return $this->redirectToRoute('app_settings');
        }
        
        // Verify password
        if (!$passwordHasher->isPasswordValid($user, $confirmPassword)) {
            $this->addFlash('error', 'Incorrect password. Account deletion cancelled.');
            return $this->redirectToRoute('app_settings');
        }
        
        // Log out the user first (before deleting to avoid errors)
        $this->container->get('security.token_storage')->setToken(null);
        
        // Delete profile picture if exists
        if ($user->getProfilePic()) {
            $profilePicPath = $this->getParameter('kernel.project_dir') . '/public/uploads/profiles/' . $user->getProfilePic();
            if (file_exists($profilePicPath)) {
                unlink($profilePicPath);
            }
        }

        // Delete all related data first (foreign key order matters)
        $conn = $entityManager->getConnection();
        $userId = $user->getId();

        // 1. Logs referencing user as admin
        $conn->executeStatement('DELETE FROM admin_audit_log WHERE admin_user_id = ?', [$userId]);
        $conn->executeStatement('DELETE FROM admin_email_log WHERE admin_user_id = ?', [$userId]);

        // 2. Project shares (both sides)
        $conn->executeStatement('DELETE FROM project_share WHERE shared_by_user_id = ? OR shared_with_user_id = ?', [$userId, $userId]);

        // 3. Assignment collaborators (both sides)
        $conn->executeStatement('DELETE FROM assignment_collaborator WHERE user_id = ? OR assigned_by_user_id = ?', [$userId, $userId]);

        // 4. Comments
        $conn->executeStatement('DELETE FROM comment WHERE user_id = ?', [$userId]);

        // 5. Flashcards in user's decks, then by created_by
        $conn->executeStatement('DELETE f FROM flashcard f JOIN deck d ON f.id_deck = d.id_deck WHERE d.user_id = ?', [$userId]);
        $conn->executeStatement('DELETE FROM flashcard WHERE created_by = ?', [$userId]);

        // 6. Decks
        $conn->executeStatement('DELETE FROM deck WHERE user_id = ?', [$userId]);

        // 7. Coping sessions
        $conn->executeStatement('DELETE FROM coping_session WHERE user_id = ?', [$userId]);

        // 8. Google tokens
        $conn->executeStatement('DELETE FROM google_token WHERE user_id = ?', [$userId]);

        // 9. Evaluation matieres
        $conn->executeStatement('DELETE FROM evaluation_matiere WHERE user_id = ?', [$userId]);

        // 10. Seances (planning must be deleted first since planning has seance_id FK)
        $conn->executeStatement('DELETE FROM planning WHERE user_id = ?', [$userId]);

        // 11. Seances
        $conn->executeStatement('DELETE FROM seance WHERE user_id = ?', [$userId]);

        // 12. Assignments
        $conn->executeStatement('DELETE FROM assignment WHERE user_id = ?', [$userId]);

        // 13. Matieres
        $conn->executeStatement('DELETE FROM matiere WHERE user_id = ?', [$userId]);

        // 14. Projects
        $conn->executeStatement('DELETE FROM project WHERE user_id = ?', [$userId]);

        // 15. User settings
        $conn->executeStatement('DELETE FROM user_settings WHERE user_id = ?', [$userId]);

        // Remove user entity
        $entityManager->remove($user);
        $entityManager->flush();

        // Invalidate session
        $request->getSession()->invalidate();
        
        $this->addFlash('success', 'Your account has been permanently deleted.');
        return $this->redirectToRoute('app_landing');
    }

    #[Route('/accessibility/save', name: 'app_settings_accessibility_save', methods: ['POST'])]
    public function saveAccessibility(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['success' => false, 'error' => 'Not authenticated'], 401);
        }
        
        $data = json_decode($request->getContent(), true);
        
        $settings = $user->getSettings();
        if (!$settings) {
            $settings = new \App\Entity\UserSettings();
            $settings->setUser($user);
            $entityManager->persist($settings);
        }
        
        if (isset($data['font_size'])) {
            $settings->setFontSize($data['font_size']);
        }
        if (isset($data['font_family'])) {
            $settings->setFontFamily($data['font_family']);
        }
        if (isset($data['accent_color'])) {
            $settings->setAccentColor($data['accent_color']);
        }
        if (isset($data['reduce_motion'])) {
            $settings->setReduceMotion((bool) $data['reduce_motion']);
        }
        if (isset($data['high_contrast'])) {
            $settings->setHighContrast((bool) $data['high_contrast']);
        }
        if (isset($data['line_height'])) {
            $settings->setLineHeight($data['line_height']);
        }
        if (isset($data['letter_spacing'])) {
            $settings->setLetterSpacing($data['letter_spacing']);
        }
        if (isset($data['zoom_level'])) {
            $settings->setZoomLevel((int) $data['zoom_level']);
        }
        if (isset($data['language'])) {
            $settings->setLanguage($data['language']);
        }
        if (isset($data['theme_preference'])) {
            $settings->setThemePreference($data['theme_preference']);
        }
        
        $entityManager->flush();
        
        return $this->json(['success' => true, 'message' => 'Accessibility settings saved']);
    }
}
