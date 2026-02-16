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
        
        // Hard delete: Remove user from database completely
        $entityManager->remove($user);
        $entityManager->flush();
        
        // Invalidate session
        $request->getSession()->invalidate();
        
        $this->addFlash('success', 'Your account has been permanently deleted.');
        return $this->redirectToRoute('app_landing');
    }
}
