# Documentation Technique - RLIFE (StudyFlow)
## Rapport Technique pour la Présentation au Professeur

---

## Table des Matières

1. [Introduction](#introduction)
2. [Intégration de la Base de Données](#intégration-de-la-base-de-données)
3. [Système d'Authentification](#système-dauthentification)
4. [Gestion des Comptes Utilisateurs](#gestion-des-comptes-utilisateurs)
5. [Interface Administrateur](#interface-administrateur)
6. [Système de Bannissement](#système-de-bannissement)
7. [Promotion d'Utilisateurs en Administrateurs](#promotion-dutilisateurs-en-administrateurs)
8. [Architecture Technique](#architecture-technique)

---

## Introduction

**Projet:** RLIFE (anciennement StudyFlow)  
**Framework:** Symfony 7.4  
**Base de données:** MySQL/MariaDB (rlife)  
**Équipe:** 6 personnes  
**Module personnel:** Gestion des Utilisateurs (User Management)

---

## 1. Intégration de la Base de Données

### 1.1 Structure de la Base de Données

La base de données `rlife` contient 15 tables principales :

```sql
- user                      (Comptes utilisateurs)
- user_settings            (Paramètres utilisateurs)
- planning                 (Plannings)
- seance                   (Séances de planning)
- matiere                  (Matières/Cours)
- evaluation_matiere       (Évaluations)
- eval_mat                 (Table de jonction)
- project                  (Projets)
- assignment               (Tâches/Devoirs)
- deck                     (Decks de flashcards)
- flashcard                (Flashcards)
- revision_flashcard       (Révisions)
- doctrine_migration_versions
```

### 1.2 Processus d'Intégration - Étape par Étape

#### Étape 1: Configuration de la connexion à la base de données

**Fichier:** `.env`

```env
DATABASE_URL="mysql://root:@127.0.0.1:3306/rlife?serverVersion=10.4.32-MariaDB&charset=utf8mb4"
```

**Explication technique:**
- `root` : utilisateur MySQL
- `@127.0.0.1:3306` : serveur local MySQL
- `rlife` : nom de la base de données
- `serverVersion=10.4.32-MariaDB` : version du serveur
- `charset=utf8mb4` : encodage pour supporter les caractères spéciaux

#### Étape 2: Création de la table User principale

**Commande SQL exécutée:**

```sql
CREATE TABLE IF NOT EXISTS `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(180) NOT NULL,
  `roles` longtext NOT NULL COMMENT '(DC2Type:json)',
  `password` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `gender` varchar(10) NOT NULL,
  `bio` longtext DEFAULT NULL,
  `student_id` varchar(100) DEFAULT NULL,
  `university` varchar(255) DEFAULT NULL,
  `is_banned` tinyint(1) NOT NULL DEFAULT 0,
  `banned_at` datetime DEFAULT NULL,
  `ban_reason` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_IDENTIFIER_EMAIL` (`email`),
  UNIQUE KEY `UNIQ_IDENTIFIER_USERNAME` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Points clés:**
- `AUTO_INCREMENT` : génération automatique d'ID unique
- `UNIQUE KEY` sur `email` et `username` : empêche les doublons
- `roles` en JSON : stockage flexible des rôles (ROLE_USER, ROLE_ADMIN)
- Champs de bannissement : `is_banned`, `banned_at`, `ban_reason`
- Timestamps : `created_at`, `updated_at` pour traçabilité

#### Étape 3: Création de l'entité Symfony User

**Fichier:** `src/Entity/User.php`

**Concepts Symfony utilisés:**

1. **ORM (Object-Relational Mapping)** avec Doctrine
   - Mappage objet-relationnel entre la classe PHP et la table SQL
   - Annotations `#[ORM\...]` pour définir la structure

2. **Implémentation des interfaces de sécurité**
   ```php
   class User implements UserInterface, PasswordAuthenticatedUserInterface
   ```

3. **Relations entre entités**
   ```php
   #[ORM\OneToOne(targetEntity: UserSettings::class, mappedBy: 'user')]
   private ?UserSettings $settings = null;
   
   #[ORM\OneToMany(targetEntity: Matiere::class, mappedBy: 'user')]
   private Collection $matieres;
   ```

#### Étape 4: Intégration des modules (Planning, Matière, Assignment, Revision)

**Processus répété pour chaque module:**

1. **Lecture du fichier SQL fourni par les collègues**
   ```bash
   Fichiers source:
   - planning.sql
   - matiere.sql
   - assignement.sql
   - revision.sql
   ```

2. **Modification du SQL pour ajouter la clé étrangère `user_id`**
   
   Exemple avec la table `matiere`:
   
   ```sql
   -- SQL ORIGINAL (fourni par le collègue)
   CREATE TABLE `matiere` (
     `id_matiere` int(11) NOT NULL AUTO_INCREMENT,
     `nom_matiere` varchar(255) NOT NULL,
     PRIMARY KEY (`id_matiere`)
   );
   
   -- SQL MODIFIÉ (avec intégration user_id)
   CREATE TABLE `matiere` (
     `id_matiere` int(11) NOT NULL AUTO_INCREMENT,
     `user_id` int(11) NOT NULL,  -- ← AJOUTÉ
     `nom_matiere` varchar(255) NOT NULL,
     PRIMARY KEY (`id_matiere`),
     KEY `IDX_MATIERE_USER` (`user_id`),  -- ← AJOUTÉ (index)
     CONSTRAINT `FK_MATIERE_USER` FOREIGN KEY (`user_id`) 
       REFERENCES `user` (`id`) ON DELETE CASCADE  -- ← AJOUTÉ (FK)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
   ```

3. **Exécution du SQL modifié**
   ```bash
   php bin/console dbal:run-sql "CREATE TABLE ..."
   ```

4. **Création de l'entité Symfony correspondante**
   
   Exemple: `src/Entity/Matiere.php`
   
   ```php
   #[ORM\Entity(repositoryClass: MatiereRepository::class)]
   #[ORM\Table(name: 'matiere')]
   class Matiere
   {
       #[ORM\Id]
       #[ORM\GeneratedValue]
       #[ORM\Column(name: 'id_matiere', type: Types::INTEGER)]
       private ?int $id = null;

       // Relation ManyToOne avec User
       #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'matieres')]
       #[ORM\JoinColumn(name: 'user_id', nullable: false, onDelete: 'CASCADE')]
       private ?User $user = null;
       
       // ... autres propriétés
   }
   ```

5. **Création du Repository pour les requêtes personnalisées**
   
   `src/Repository/MatiereRepository.php`
   
   ```php
   public function findByUser(User $user): array
   {
       return $this->createQueryBuilder('m')
           ->andWhere('m.user = :user')
           ->setParameter('user', $user)
           ->getQuery()
           ->getResult();
   }
   ```

6. **Mise à jour de l'entité User**
   
   ```php
   // Dans src/Entity/User.php
   
   #[ORM\OneToMany(targetEntity: Matiere::class, mappedBy: 'user')]
   private Collection $matieres;
   
   public function getMatieres(): Collection
   {
       return $this->matieres;
   }
   ```

#### Étape 5: Vérification de l'intégration

**Commande pour vérifier les entités:**
```bash
php bin/console doctrine:mapping:info
```

**Résultat attendu:**
```
Found 11 mapped entities:
 [OK] App\Entity\Assignment
 [OK] App\Entity\Deck
 [OK] App\Entity\EvalMat
 [OK] App\Entity\EvaluationMatiere
 [OK] App\Entity\Flashcard
 [OK] App\Entity\Matiere
 [OK] App\Entity\Planning
 [OK] App\Entity\Project
 [OK] App\Entity\Seance
 [OK] App\Entity\User
 [OK] App\Entity\UserSettings
```

**Vérification des clés étrangères:**
```bash
php bin/console dbal:run-sql "SELECT TABLE_NAME, COLUMN_NAME, 
  REFERENCED_TABLE_NAME FROM information_schema.KEY_COLUMN_USAGE 
  WHERE TABLE_SCHEMA = 'rlife' AND REFERENCED_TABLE_NAME IS NOT NULL"
```

### 1.3 Concept de CASCADE DELETE

**Principe:** Quand un utilisateur est supprimé, toutes ses données associées sont automatiquement supprimées.

**Exemple concret:**
```sql
-- Si l'utilisateur avec id=2 est supprimé
DELETE FROM user WHERE id = 2;

-- Ces suppressions se font AUTOMATIQUEMENT grâce à CASCADE DELETE:
-- - Toutes ses matières sont supprimées
-- - Tous ses plannings sont supprimés
-- - Tous ses projets sont supprimés
-- - Tous ses decks sont supprimés
-- - Etc.
```

**Avantage:** Intégrité des données garantie, pas de données orphelines.

---

## 2. Système d'Authentification

### 2.1 Création de Compte - Processus Complet

#### Étape 1: Formulaire d'inscription

**Fichier:** `src/Form/RegistrationFormType.php`

```php
public function buildForm(FormBuilderInterface $builder, array $options): void
{
    $builder
        ->add('username', TextType::class)
        ->add('email', EmailType::class)
        ->add('firstName', TextType::class)
        ->add('lastName', TextType::class)
        ->add('plainPassword', PasswordType::class)
        ->add('confirmPassword', PasswordType::class)
        ->add('gender', ChoiceType::class, [
            'choices' => [
                'Male' => 'male',
                'Female' => 'female',
            ]
        ])
        ->add('agreeTerms', CheckboxType::class);
}
```

**Validations côté serveur:**
```php
#[Assert\NotBlank]
#[Assert\Email]
private ?string $email = null;

#[Assert\Length(min: 8)]
#[Assert\Regex(pattern: '/[A-Z]/', message: 'Doit contenir une majuscule')]
#[Assert\Regex(pattern: '/[0-9]/', message: 'Doit contenir un chiffre')]
private ?string $plainPassword = null;
```

#### Étape 2: Traitement dans le contrôleur

**Fichier:** `src/Controller/RegistrationController.php`

**Méthode `register()`:**

```php
#[Route('/register', name: 'app_register')]
public function register(
    Request $request,
    UserPasswordHasherInterface $passwordHasher,
    EntityManagerInterface $entityManager
): Response {
    // 1. Créer une nouvelle instance User
    $user = new User();
    
    // 2. Créer et traiter le formulaire
    $form = $this->createForm(RegistrationFormType::class, $user);
    $form->handleRequest($request);
    
    if ($form->isSubmitted() && $form->isValid()) {
        // 3. Hasher le mot de passe (sécurité)
        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $form->get('plainPassword')->getData()
        );
        $user->setPassword($hashedPassword);
        
        // 4. Définir le rôle par défaut
        $user->setRoles(['ROLE_USER']);
        
        // 5. Définir les timestamps
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setUpdatedAt(new \DateTimeImmutable());
        
        // 6. Créer les paramètres utilisateur par défaut
        $settings = new UserSettings();
        $settings->setUser($user);
        $settings->setTheme('light');
        $settings->setLanguage('fr');
        $settings->setNotificationsEnabled(true);
        
        // 7. Persister en base de données
        $entityManager->persist($user);
        $entityManager->persist($settings);
        $entityManager->flush(); // ← INSERTION EN BDD ICI
        
        // 8. Rediriger vers la page de connexion
        return $this->redirectToRoute('app_login');
    }
    
    return $this->render('registration/register.html.twig', [
        'registrationForm' => $form,
    ]);
}
```

**Explication détaillée:**

1. **Hashage du mot de passe:**
   - Le mot de passe n'est JAMAIS stocké en clair
   - Utilisation de l'algorithme bcrypt (par défaut Symfony)
   - Exemple: "password123" → "$2y$13$xC3..."

2. **EntityManager:**
   - `persist()` : marque l'objet pour sauvegarde
   - `flush()` : exécute réellement l'INSERT SQL
   
   SQL généré automatiquement:
   ```sql
   INSERT INTO user (email, password, username, first_name, last_name, 
                     gender, roles, is_banned, created_at, updated_at)
   VALUES ('user@email.com', '$2y$13$...', 'username', 'John', 'Doe',
           'male', '["ROLE_USER"]', 0, NOW(), NOW());
   ```

3. **Relation UserSettings:**
   - Création automatique lors de l'inscription
   - Relation OneToOne avec cascade persist

#### Étape 3: Configuration de sécurité

**Fichier:** `config/packages/security.yaml`

```yaml
security:
    password_hashers:
        Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface: 'auto'
    
    providers:
        app_user_provider:
            entity:
                class: App\Entity\User
                property: email  # Login par email
    
    firewalls:
        main:
            lazy: true
            provider: app_user_provider
            form_login:
                login_path: app_login
                check_path: app_login
                enable_csrf: true
            logout:
                path: app_logout
```

### 2.2 Connexion (Login) - Processus Complet

#### Étape 1: Affichage du formulaire de connexion

**Fichier:** `src/Controller/SecurityController.php`

```php
#[Route('/login', name: 'app_login')]
public function login(AuthenticationUtils $authenticationUtils): Response
{
    // Vérifier si déjà connecté
    if ($this->getUser()) {
        return $this->redirectToRoute('app_home');
    }
    
    // Récupérer les erreurs de connexion
    $error = $authenticationUtils->getLastAuthenticationError();
    
    // Récupérer le dernier email saisi
    $lastUsername = $authenticationUtils->getLastUsername();
    
    return $this->render('security/login.html.twig', [
        'last_username' => $lastUsername,
        'error' => $error,
    ]);
}
```

**Template:** `templates/security/login.html.twig`

```twig
<form method="post" action="{{ path('app_login') }}">
    <input type="email" name="_username" value="{{ last_username }}">
    <input type="password" name="_password">
    <input type="hidden" name="_csrf_token" value="{{ csrf_token('authenticate') }}">
    <button type="submit">Se connecter</button>
</form>
```

#### Étape 2: Processus d'authentification Symfony

**Quand le formulaire est soumis:**

1. **Symfony intercepte la requête** (grâce à `check_path: app_login`)

2. **Récupération de l'utilisateur depuis la BDD:**
   ```sql
   SELECT * FROM user WHERE email = 'user@email.com' LIMIT 1;
   ```

3. **Vérification du mot de passe:**
   ```php
   // Symfony compare automatiquement:
   $passwordHasher->verify(
       $user->getPassword(),        // Hash en BDD: $2y$13$...
       $submittedPassword            // Mot de passe saisi: "password123"
   );
   ```

4. **Si les identifiants sont corrects:**
   - Création d'une session PHP
   - Stockage de l'utilisateur dans la session
   - Token de sécurité généré
   - Redirection vers la page d'accueil

5. **Si les identifiants sont incorrects:**
   - Message d'erreur
   - Aucune session créée

#### Étape 3: Gestion de la session

**Symfony stocke dans `$_SESSION`:**
```php
$_SESSION['_security_main'] = serialize([
    'user_id' => 2,
    'email' => 'user@email.com',
    'roles' => ['ROLE_USER'],
    'authenticated' => true
]);
```

**Accès à l'utilisateur connecté:**
```php
// Dans un contrôleur
$user = $this->getUser();

// Dans Twig
{{ app.user.email }}
{{ app.user.fullName }}
```

### 2.3 Déconnexion (Logout)

**Configuration dans `security.yaml`:**
```yaml
logout:
    path: app_logout
    target: app_home  # Redirection après logout
```

**Contrôleur:**
```php
#[Route('/logout', name: 'app_logout')]
public function logout(): void
{
    // Symfony gère automatiquement:
    // - Destruction de la session
    // - Suppression des cookies d'authentification
    // - Redirection vers 'target'
}
```

---

## 3. Gestion des Comptes Utilisateurs

### 3.1 Suppression de Compte - Processus Technique

#### Méthode 1: Auto-suppression (utilisateur supprime son propre compte)

**Contrôleur:** `src/Controller/ProfileController.php`

```php
#[Route('/profile/delete', name: 'app_profile_delete', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
public function deleteAccount(
    Request $request,
    EntityManagerInterface $entityManager
): Response {
    $user = $this->getUser();
    
    // 1. Vérification du token CSRF (sécurité)
    if (!$this->isCsrfTokenValid('delete-account', $request->request->get('_token'))) {
        throw new InvalidCsrfTokenException();
    }
    
    // 2. Vérification du mot de passe (confirmation)
    $password = $request->request->get('password');
    if (!$this->passwordHasher->isPasswordValid($user, $password)) {
        $this->addFlash('error', 'Mot de passe incorrect');
        return $this->redirectToRoute('app_profile');
    }
    
    // 3. Déconnexion de l'utilisateur
    $this->container->get('security.token_storage')->setToken(null);
    $request->getSession()->invalidate();
    
    // 4. Suppression de l'utilisateur
    $entityManager->remove($user);
    $entityManager->flush();
    // ↑ Exécute: DELETE FROM user WHERE id = ?
    // ↑ CASCADE DELETE supprime automatiquement:
    //   - user_settings
    //   - matieres
    //   - plannings
    //   - projects
    //   - assignments
    //   - decks
    //   - etc.
    
    return $this->redirectToRoute('app_home');
}
```

**SQL généré par Doctrine:**
```sql
-- Suppression principale
DELETE FROM user WHERE id = 2;

-- Grâce à CASCADE DELETE, ces requêtes sont automatiques:
-- DELETE FROM user_settings WHERE user_id = 2;
-- DELETE FROM matiere WHERE user_id = 2;
-- DELETE FROM planning WHERE user_id = 2;
-- DELETE FROM project WHERE user_id = 2;
-- DELETE FROM assignment WHERE user_id = 2;
-- DELETE FROM deck WHERE user_id = 2;
-- etc.
```

#### Méthode 2: Suppression par l'administrateur

**Contrôleur:** `src/Controller/AdminController.php`

```php
#[Route('/admin/user/{id}/delete', name: 'admin_user_delete')]
#[IsGranted('ROLE_ADMIN')]
public function deleteUser(
    User $user,
    EntityManagerInterface $entityManager
): Response {
    // Empêcher la suppression de son propre compte
    if ($user === $this->getUser()) {
        $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte');
        return $this->redirectToRoute('admin_users');
    }
    
    // Suppression de l'utilisateur
    $entityManager->remove($user);
    $entityManager->flush();
    
    $this->addFlash('success', 'Utilisateur supprimé avec succès');
    return $this->redirectToRoute('admin_users');
}
```

**Sécurités mises en place:**
1. Vérification CSRF token
2. Vérification du mot de passe
3. Vérification du rôle ADMIN
4. Empêcher auto-suppression admin

---

## 4. Interface Administrateur

### 4.1 Injection du Compte Admin dans la Base de Données

#### Méthode 1: Commande Symfony personnalisée

**Fichier:** `src/Command/CreateAdminCommand.php`

```php
#[AsCommand(
    name: 'app:create-admin',
    description: 'Crée un compte administrateur',
)]
class CreateAdminCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Vérifier si admin existe déjà
        $existingAdmin = $this->userRepository->findOneBy(['email' => 'admin@rlife.com']);
        
        if ($existingAdmin) {
            $output->writeln('Admin déjà existant');
            return Command::FAILURE;
        }
        
        // Créer le compte admin
        $admin = new User();
        $admin->setEmail('admin@rlife.com');
        $admin->setUsername('admin');
        $admin->setFirstName('Admin');
        $admin->setLastName('RLIFE');
        $admin->setGender('male');
        
        // Hash du mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($admin, 'Admin@2024');
        $admin->setPassword($hashedPassword);
        
        // Attribution du rôle ADMIN
        $admin->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        
        $admin->setCreatedAt(new \DateTimeImmutable());
        $admin->setUpdatedAt(new \DateTimeImmutable());
        
        // Sauvegarde en BDD
        $this->entityManager->persist($admin);
        $this->entityManager->flush();
        
        $output->writeln('Compte admin créé avec succès!');
        return Command::SUCCESS;
    }
}
```

**Exécution:**
```bash
php bin/console app:create-admin
```

**SQL généré:**
```sql
INSERT INTO user (
    email, username, first_name, last_name, gender, 
    password, roles, is_banned, created_at, updated_at
) VALUES (
    'admin@rlife.com',
    'admin',
    'Admin',
    'RLIFE',
    'male',
    '$2y$13$abcd1234...', -- Hash de "Admin@2024"
    '["ROLE_ADMIN","ROLE_USER"]',
    0,
    NOW(),
    NOW()
);
```

#### Méthode 2: Insertion SQL directe

**Commande:**
```bash
php bin/console dbal:run-sql "INSERT INTO user (...) VALUES (...)"
```

### 4.2 Intégration du Template Admin

#### Structure des templates

```
templates/
├── base.html.twig           (Template de base utilisateur)
├── admin/
│   ├── base.html.twig       (Template de base admin)
│   ├── dashboard.html.twig  (Tableau de bord)
│   ├── users.html.twig      (Liste des utilisateurs)
│   └── ...
├── security/
│   ├── login.html.twig      (Formulaire de connexion unique)
└── home/
    └── index.html.twig      (Page d'accueil)
```

#### Template Admin de Base

**Fichier:** `templates/admin/base.html.twig`

```twig
<!DOCTYPE html>
<html>
<head>
    <title>{% block title %}Admin - RLIFE{% endblock %}</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body>
    <!-- Sidebar admin -->
    <aside class="admin-sidebar">
        <h2>Admin Panel</h2>
        <nav>
            <a href="{{ path('admin_dashboard') }}">Dashboard</a>
            <a href="{{ path('admin_users') }}">Utilisateurs</a>
            <a href="{{ path('admin_statistics') }}">Statistiques</a>
        </nav>
    </aside>
    
    <!-- Contenu principal -->
    <main class="admin-content">
        {% block body %}{% endblock %}
    </main>
</body>
</html>
```

#### Template Utilisateur de Base

**Fichier:** `templates/base.html.twig`

```twig
<!DOCTYPE html>
<html>
<head>
    <title>{% block title %}RLIFE{% endblock %}</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
    <!-- Navigation utilisateur -->
    <nav class="user-navbar">
        <a href="{{ path('app_home') }}">Accueil</a>
        <a href="{{ path('app_profile') }}">Profil</a>
        {% if app.user %}
            <a href="{{ path('app_logout') }}">Déconnexion</a>
        {% endif %}
    </nav>
    
    {% block body %}{% endblock %}
</body>
</html>
```

### 4.3 Système de Redirection Basé sur les Rôles

**Contrôleur:** `src/Controller/SecurityController.php`

```php
#[Route('/login', name: 'app_login')]
public function login(AuthenticationUtils $authenticationUtils): Response
{
    // Si déjà connecté, rediriger selon le rôle
    if ($this->getUser()) {
        // Vérifier si ADMIN
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('admin_dashboard');
        }
        
        // Sinon, utilisateur normal
        return $this->redirectToRoute('app_home');
    }
    
    // ... reste du code de connexion
}
```

**Event Subscriber pour redirection automatique:**

**Fichier:** `src/EventSubscriber/LoginSuccessSubscriber.php`

```php
class LoginSuccessSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }
    
    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        
        // Récupérer les rôles de l'utilisateur
        $roles = $user->getRoles();
        
        // Si c'est un admin, rediriger vers le dashboard admin
        if (in_array('ROLE_ADMIN', $roles, true)) {
            $response = new RedirectResponse($this->router->generate('admin_dashboard'));
            $event->setResponse($response);
        }
        // Sinon, redirection par défaut vers l'accueil utilisateur
    }
}
```

### 4.4 Protection des Routes Admin

**Méthode 1: Annotation sur le contrôleur**

```php
#[Route('/admin', name: 'admin_')]
#[IsGranted('ROLE_ADMIN')]  // ← Sécurité: seuls les ADMIN peuvent accéder
class AdminController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(): Response
    {
        // Accessible uniquement si ROLE_ADMIN
        return $this->render('admin/dashboard.html.twig');
    }
}
```

**Méthode 2: Configuration dans `security.yaml`**

```yaml
access_control:
    - { path: ^/admin, roles: ROLE_ADMIN }
    - { path: ^/profile, roles: ROLE_USER }
```

**Explication technique:**
1. Symfony intercepte TOUTES les requêtes vers `/admin/*`
2. Vérifie si l'utilisateur connecté possède `ROLE_ADMIN`
3. Si OUI → accès autorisé
4. Si NON → Erreur 403 Forbidden (Accès refusé)

**Processus de vérification:**
```php
// Dans la session:
$_SESSION['_security_main'] = [
    'roles' => ['ROLE_ADMIN', 'ROLE_USER'],
];

// Symfony vérifie:
if (in_array('ROLE_ADMIN', $user->getRoles())) {
    // Accès autorisé
} else {
    throw new AccessDeniedException();
}
```

### 4.5 Interface Admin - Page Liste des Utilisateurs

**Contrôleur:** `src/Controller/AdminController.php`

```php
#[Route('/admin/users', name: 'admin_users')]
#[IsGranted('ROLE_ADMIN')]
public function listUsers(UserRepository $userRepository): Response
{
    // Récupérer tous les utilisateurs
    $users = $userRepository->findAll();
    
    // Statistiques
    $totalUsers = count($users);
    $bannedUsers = count(array_filter($users, fn($u) => $u->isBanned()));
    $admins = count(array_filter($users, fn($u) => $u->isAdmin()));
    
    return $this->render('admin/users.html.twig', [
        'users' => $users,
        'stats' => [
            'total' => $totalUsers,
            'banned' => $bannedUsers,
            'admins' => $admins,
        ],
    ]);
}
```

**Template:** `templates/admin/users.html.twig`

```twig
{% extends 'admin/base.html.twig' %}

{% block body %}
<h1>Gestion des Utilisateurs</h1>

<div class="stats">
    <div>Total: {{ stats.total }}</div>
    <div>Bannis: {{ stats.banned }}</div>
    <div>Admins: {{ stats.admins }}</div>
</div>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Email</th>
            <th>Nom</th>
            <th>Rôles</th>
            <th>Statut</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        {% for user in users %}
        <tr>
            <td>{{ user.id }}</td>
            <td>{{ user.email }}</td>
            <td>{{ user.fullName }}</td>
            <td>
                {% if 'ROLE_ADMIN' in user.roles %}
                    <span class="badge-admin">ADMIN</span>
                {% else %}
                    <span class="badge-user">USER</span>
                {% endif %}
            </td>
            <td>
                {% if user.isBanned %}
                    <span class="badge-banned">Banni</span>
                {% else %}
                    <span class="badge-active">Actif</span>
                {% endif %}
            </td>
            <td>
                {% if not user.isBanned %}
                    <a href="{{ path('admin_user_ban', {id: user.id}) }}">Bannir</a>
                {% else %}
                    <a href="{{ path('admin_user_unban', {id: user.id}) }}">Débannir</a>
                {% endif %}
                
                {% if 'ROLE_ADMIN' not in user.roles %}
                    <a href="{{ path('admin_user_promote', {id: user.id}) }}">Promouvoir Admin</a>
                {% endif %}
                
                <a href="{{ path('admin_user_delete', {id: user.id}) }}">Supprimer</a>
            </td>
        </tr>
        {% endfor %}
    </tbody>
</table>
{% endblock %}
```

---

## 5. Système de Bannissement

### 5.1 Structure de Données pour le Bannissement

**Champs dans la table `user`:**
```sql
is_banned TINYINT(1) DEFAULT 0,        -- 0 = actif, 1 = banni
banned_at DATETIME DEFAULT NULL,        -- Date du bannissement
ban_reason TEXT DEFAULT NULL            -- Raison du bannissement
```

**Dans l'entité User:**
```php
#[ORM\Column(type: 'boolean')]
private bool $isBanned = false;

#[ORM\Column(type: 'datetime', nullable: true)]
private ?\DateTimeImmutable $bannedAt = null;

#[ORM\Column(type: 'text', nullable: true)]
private ?string $banReason = null;
```

### 5.2 Processus de Bannissement

**Contrôleur:** `src/Controller/AdminController.php`

```php
#[Route('/admin/user/{id}/ban', name: 'admin_user_ban', methods: ['POST'])]
#[IsGranted('ROLE_ADMIN')]
public function banUser(
    User $user,
    Request $request,
    EntityManagerInterface $entityManager
): Response {
    // 1. Empêcher le bannissement d'un admin
    if ($user->isAdmin()) {
        $this->addFlash('error', 'Impossible de bannir un administrateur');
        return $this->redirectToRoute('admin_users');
    }
    
    // 2. Empêcher de se bannir soi-même
    if ($user === $this->getUser()) {
        $this->addFlash('error', 'Vous ne pouvez pas vous bannir vous-même');
        return $this->redirectToRoute('admin_users');
    }
    
    // 3. Récupérer la raison du bannissement
    $reason = $request->request->get('ban_reason', 'Violation des conditions d\'utilisation');
    
    // 4. Appliquer le bannissement
    $user->setIsBanned(true);
    $user->setBannedAt(new \DateTimeImmutable());
    $user->setBanReason($reason);
    
    // 5. Sauvegarder en BDD
    $entityManager->flush();
    
    // SQL généré:
    // UPDATE user 
    // SET is_banned = 1, 
    //     banned_at = NOW(), 
    //     ban_reason = 'Violation des conditions...'
    // WHERE id = ?
    
    // 6. Déconnecter l'utilisateur banni (si connecté)
    $this->disconnectUser($user);
    
    $this->addFlash('success', sprintf('L\'utilisateur %s a été banni', $user->getEmail()));
    return $this->redirectToRoute('admin_users');
}
```

**Méthode pour déconnecter l'utilisateur:**
```php
private function disconnectUser(User $user): void
{
    // Invalider toutes les sessions de cet utilisateur
    // (nécessite une implémentation custom ou un bundle)
    
    // Alternative simple: l'utilisateur sera déconnecté à sa prochaine requête
    // grâce au UserChecker (voir section suivante)
}
```

### 5.3 Vérification du Bannissement à la Connexion

**UserChecker personnalisé:**

**Fichier:** `src/Security/UserChecker.php`

```php
class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }
        
        // Vérifier si l'utilisateur est banni
        if ($user->isBanned()) {
            throw new CustomUserMessageAccountStatusException(
                sprintf(
                    'Votre compte a été banni le %s. Raison: %s',
                    $user->getBannedAt()->format('d/m/Y H:i'),
                    $user->getBanReason()
                )
            );
        }
    }
    
    public function checkPostAuth(UserInterface $user): void
    {
        // Vérifications post-authentification
    }
}
```

**Configuration dans `security.yaml`:**
```yaml
security:
    firewalls:
        main:
            user_checker: App\Security\UserChecker
```

**Fonctionnement:**
1. Utilisateur banni tente de se connecter
2. Email/password sont corrects
3. Symfony appelle `UserChecker::checkPreAuth()`
4. Exception levée si banni
5. Message d'erreur affiché
6. Connexion refusée

### 5.4 Débannissement

**Contrôleur:**
```php
#[Route('/admin/user/{id}/unban', name: 'admin_user_unban', methods: ['POST'])]
#[IsGranted('ROLE_ADMIN')]
public function unbanUser(
    User $user,
    EntityManagerInterface $entityManager
): Response {
    // 1. Vérifier que l'utilisateur est effectivement banni
    if (!$user->isBanned()) {
        $this->addFlash('error', 'Cet utilisateur n\'est pas banni');
        return $this->redirectToRoute('admin_users');
    }
    
    // 2. Retirer le bannissement
    $user->setIsBanned(false);
    $user->setBannedAt(null);
    $user->setBanReason(null);
    
    // 3. Sauvegarder
    $entityManager->flush();
    
    // SQL généré:
    // UPDATE user 
    // SET is_banned = 0, 
    //     banned_at = NULL, 
    //     ban_reason = NULL 
    // WHERE id = ?
    
    $this->addFlash('success', 'L\'utilisateur a été débanni');
    return $this->redirectToRoute('admin_users');
}
```

### 5.5 Interface de Bannissement

**Formulaire modal dans `templates/admin/users.html.twig`:**

```twig
<!-- Modal de bannissement -->
<div id="banModal" class="modal">
    <form method="POST" action="{{ path('admin_user_ban', {id: user.id}) }}">
        <h3>Bannir {{ user.email }}</h3>
        
        <label>Raison du bannissement:</label>
        <select name="ban_reason">
            <option value="Violation des conditions d'utilisation">
                Violation des conditions d'utilisation
            </option>
            <option value="Comportement abusif">
                Comportement abusif
            </option>
            <option value="Spam">Spam</option>
            <option value="Autre">Autre</option>
        </select>
        
        <textarea name="custom_reason" placeholder="Précisez..."></textarea>
        
        <input type="hidden" name="_csrf_token" 
               value="{{ csrf_token('ban-user') }}">
        
        <button type="submit">Confirmer le bannissement</button>
        <button type="button" onclick="closeModal()">Annuler</button>
    </form>
</div>
```

---

## 6. Promotion d'Utilisateurs en Administrateurs

### 6.1 Système de Rôles Symfony

**Hiérarchie des rôles:**
```yaml
# config/packages/security.yaml
security:
    role_hierarchy:
        ROLE_ADMIN: ROLE_USER  # Admin hérite des droits USER
```

**Signification:**
- `ROLE_USER` : utilisateur normal (accès profil, plannings, etc.)
- `ROLE_ADMIN` : administrateur (accès panel admin + tout ce que USER peut faire)

**Stockage en BDD:**
```sql
-- Utilisateur normal:
roles = '["ROLE_USER"]'

-- Administrateur:
roles = '["ROLE_ADMIN","ROLE_USER"]'
```

### 6.2 Processus de Promotion

**Contrôleur:** `src/Controller/AdminController.php`

```php
#[Route('/admin/user/{id}/promote', name: 'admin_user_promote', methods: ['POST'])]
#[IsGranted('ROLE_ADMIN')]
public function promoteToAdmin(
    User $user,
    Request $request,
    EntityManagerInterface $entityManager
): Response {
    // 1. Vérifier que l'utilisateur n'est pas déjà admin
    if ($user->isAdmin()) {
        $this->addFlash('error', 'Cet utilisateur est déjà administrateur');
        return $this->redirectToRoute('admin_users');
    }
    
    // 2. Vérifier le token CSRF (sécurité)
    if (!$this->isCsrfTokenValid('promote-user', $request->request->get('_token'))) {
        throw new InvalidCsrfTokenException();
    }
    
    // 3. Récupérer les rôles actuels
    $currentRoles = $user->getRoles();
    
    // 4. Ajouter le rôle ADMIN
    if (!in_array('ROLE_ADMIN', $currentRoles)) {
        $currentRoles[] = 'ROLE_ADMIN';
    }
    
    // 5. Mettre à jour les rôles
    $user->setRoles($currentRoles);
    
    // 6. Sauvegarder en BDD
    $entityManager->flush();
    
    // SQL généré:
    // UPDATE user 
    // SET roles = '["ROLE_ADMIN","ROLE_USER"]' 
    // WHERE id = ?
    
    // 7. Envoyer une notification par email (optionnel)
    $this->sendPromotionEmail($user);
    
    $this->addFlash('success', 
        sprintf('%s a été promu administrateur', $user->getEmail())
    );
    
    return $this->redirectToRoute('admin_users');
}
```

**Méthode dans l'entité User:**
```php
public function isAdmin(): bool
{
    return in_array('ROLE_ADMIN', $this->roles, true);
}

public function promoteToAdmin(): void
{
    if (!$this->isAdmin()) {
        $this->roles[] = 'ROLE_ADMIN';
    }
}

public function demoteFromAdmin(): void
{
    $this->roles = array_diff($this->roles, ['ROLE_ADMIN']);
    // Garde ROLE_USER
}
```

### 6.3 Rétrogradation (Démote)

**Contrôleur:**
```php
#[Route('/admin/user/{id}/demote', name: 'admin_user_demote', methods: ['POST'])]
#[IsGranted('ROLE_ADMIN')]
public function demoteFromAdmin(
    User $user,
    EntityManagerInterface $entityManager
): Response {
    // 1. Empêcher de se rétrograder soi-même
    if ($user === $this->getUser()) {
        $this->addFlash('error', 'Vous ne pouvez pas vous rétrograder vous-même');
        return $this->redirectToRoute('admin_users');
    }
    
    // 2. Vérifier que l'utilisateur est admin
    if (!$user->isAdmin()) {
        $this->addFlash('error', 'Cet utilisateur n\'est pas administrateur');
        return $this->redirectToRoute('admin_users');
    }
    
    // 3. Retirer le rôle ADMIN
    $user->demoteFromAdmin();
    
    // 4. Sauvegarder
    $entityManager->flush();
    
    // SQL généré:
    // UPDATE user 
    // SET roles = '["ROLE_USER"]' 
    // WHERE id = ?
    
    $this->addFlash('success', 'L\'utilisateur a été rétrogradé');
    return $this->redirectToRoute('admin_users');
}
```

### 6.4 Confirmation de Promotion (Modal)

**Template:** `templates/admin/users.html.twig`

```twig
<!-- Bouton pour promouvoir -->
{% if 'ROLE_ADMIN' not in user.roles %}
    <button onclick="showPromoteModal({{ user.id }}, '{{ user.email }}')">
        Promouvoir Admin
    </button>
{% else %}
    <button onclick="showDemoteModal({{ user.id }}, '{{ user.email }}')">
        Rétrograder
    </button>
{% endif %}

<!-- Modal de confirmation -->
<div id="promoteModal" class="modal hidden">
    <div class="modal-content">
        <h3>Confirmation de promotion</h3>
        <p>Êtes-vous sûr de vouloir promouvoir <strong id="userEmail"></strong> en administrateur ?</p>
        <p class="warning">Cette action donnera à l'utilisateur un accès complet au panel d'administration.</p>
        
        <form id="promoteForm" method="POST">
            <input type="hidden" name="_csrf_token" value="{{ csrf_token('promote-user') }}">
            
            <button type="submit" class="btn-confirm">Confirmer la promotion</button>
            <button type="button" onclick="closeModal()" class="btn-cancel">Annuler</button>
        </form>
    </div>
</div>

<script>
function showPromoteModal(userId, userEmail) {
    document.getElementById('userEmail').textContent = userEmail;
    document.getElementById('promoteForm').action = '/admin/user/' + userId + '/promote';
    document.getElementById('promoteModal').classList.remove('hidden');
}
</script>
```

### 6.5 Notification Email de Promotion

**Service d'email:**

```php
// src/Service/EmailService.php

public function sendPromotionEmail(User $user): void
{
    $email = (new TemplatedEmail())
        ->from('noreply@rlife.com')
        ->to($user->getEmail())
        ->subject('Vous avez été promu administrateur - RLIFE')
        ->htmlTemplate('emails/promotion.html.twig')
        ->context([
            'user' => $user,
            'promotedAt' => new \DateTime(),
        ]);
    
    $this->mailer->send($email);
}
```

**Template email:** `templates/emails/promotion.html.twig`

```twig
<h1>Félicitations {{ user.firstName }} !</h1>

<p>Vous avez été promu administrateur de la plateforme RLIFE.</p>

<p>Vous avez maintenant accès au panel d'administration à l'adresse :</p>
<a href="{{ url('admin_dashboard') }}">{{ url('admin_dashboard') }}</a>

<p><strong>Vos nouvelles responsabilités :</strong></p>
<ul>
    <li>Gérer les utilisateurs</li>
    <li>Modérer les contenus</li>
    <li>Consulter les statistiques</li>
    <li>Promouvoir/Bannir des utilisateurs</li>
</ul>
```

---

## 7. Architecture Technique

### 7.1 Structure MVC (Modèle-Vue-Contrôleur)

**Symfony utilise le pattern MVC:**

```
┌─────────────┐
│  REQUÊTE    │ (Utilisateur accède à /admin/users)
└──────┬──────┘
       │
       ▼
┌─────────────┐
│  ROUTEUR    │ (config/routes.yaml + Annotations)
└──────┬──────┘
       │
       ▼
┌──────────────────┐
│  CONTRÔLEUR      │ (src/Controller/AdminController.php)
│  - Logique métier│
│  - Sécurité      │
└────┬───────┬─────┘
     │       │
     │       └────────────────┐
     │                        │
     ▼                        ▼
┌──────────┐          ┌────────────┐
│  MODÈLE  │          │    VUE     │
│ (Entity) │◄─────────│  (Twig)    │
│  - User  │          │            │
│  - Projet│          │ templates/ │
└────┬─────┘          └─────┬──────┘
     │                      │
     ▼                      ▼
┌──────────┐          ┌──────────┐
│   BDD    │          │ RÉPONSE  │
│  MySQL   │          │  HTML    │
└──────────┘          └──────────┘
```

### 7.2 Flux d'une Requête Complète

**Exemple: Bannir un utilisateur**

```
1. L'admin clique sur "Bannir" pour l'utilisateur ID=7
   URL: POST /admin/user/7/ban

2. ROUTEUR Symfony
   - Trouve la route correspondante
   - Route: admin_user_ban
   - Contrôleur: AdminController::banUser()
   - Paramètre: {id: 7}

3. FIREWALL Symfony
   - Vérifie l'authentification (session existe ?)
   - Vérifie l'autorisation (ROLE_ADMIN ?)
   - Si OK → continue
   - Si NON → 403 Forbidden

4. CONTRÔLEUR (AdminController)
   - Récupère l'utilisateur avec ID=7 depuis BDD
   - Vérifie les conditions (pas admin, pas soi-même)
   - Applique le bannissement
   - Appelle EntityManager->flush()

5. DOCTRINE ORM
   - Convertit les objets PHP en SQL
   - Exécute: UPDATE user SET is_banned=1 WHERE id=7
   - Retourne le résultat

6. CONTRÔLEUR
   - Ajoute un message flash
   - Redirige vers la liste des utilisateurs

7. RÉPONSE HTTP
   - Code 302 (Redirection)
   - Location: /admin/users

8. NOUVELLE REQUÊTE
   - GET /admin/users
   - Affiche la liste avec le message "Utilisateur banni"
```

### 7.3 Sécurité - Multiples Couches

**Couche 1: Firewall (security.yaml)**
```yaml
access_control:
    - { path: ^/admin, roles: ROLE_ADMIN }
```
→ Bloque TOUTES les routes /admin/* si pas ROLE_ADMIN

**Couche 2: Annotations sur les contrôleurs**
```php
#[IsGranted('ROLE_ADMIN')]
class AdminController { }
```
→ Protection supplémentaire au niveau de la classe

**Couche 3: Vérifications manuelles**
```php
if ($user === $this->getUser()) {
    throw new AccessDeniedException();
}
```
→ Logique métier spécifique

**Couche 4: CSRF Tokens**
```php
if (!$this->isCsrfTokenValid('ban-user', $token)) {
    throw new InvalidCsrfTokenException();
}
```
→ Protection contre les attaques CSRF

**Couche 5: Validation des données**
```php
#[Assert\Email]
#[Assert\NotBlank]
private ?string $email = null;
```
→ Validation automatique avant sauvegarde

### 7.4 Base de Données - Relations

**Diagramme des relations principales:**

```
┌─────────────────┐
│      USER       │
│  - id           │
│  - email        │
│  - password     │
│  - roles        │
│  - is_banned    │
└────────┬────────┘
         │
         │ 1
         │
         │
    ┌────┴──────┬──────────┬──────────┬──────────┐
    │           │          │          │          │
    │ *         │ *        │ *        │ *        │ *
    ▼           ▼          ▼          ▼          ▼
┌─────────┐ ┌────────┐ ┌─────────┐ ┌──────┐ ┌──────────┐
│USER_SET │ │MATIERE │ │PLANNING │ │PROJET│ │   DECK   │
│TINGS    │ │        │ │         │ │      │ │          │
└─────────┘ └────────┘ └─────────┘ └───┬──┘ └─────┬────┘
                                       │          │
                                       │ 1        │ 1
                                       │          │
                                       │ *        │ *
                                       ▼          ▼
                                  ┌──────────┐ ┌──────────┐
                                  │ASSIGNMENT│ │FLASHCARD │
                                  └──────────┘ └──────────┘
```

**Légende:**
- `1` : un
- `*` : plusieurs
- Exemple: Un USER peut avoir plusieurs MATIERE (1 → *)
- Exemple: Un PROJET peut avoir plusieurs ASSIGNMENT (1 → *)

### 7.5 Technologies et Outils Utilisés

**Backend:**
- **Symfony 7.4** : Framework PHP
- **Doctrine ORM** : Mapping objet-relationnel
- **Twig** : Moteur de templates
- **Symfony Security** : Authentification et autorisation

**Base de Données:**
- **MySQL/MariaDB 10.4.32** : Stockage des données
- **phpMyAdmin** : Interface de gestion BDD

**Frontend:**
- **Tailwind CSS 3** : Framework CSS
- **JavaScript Vanilla** : Interactivité
- **Dark Mode** : 192 classes générées

**Outils de développement:**
- **Composer** : Gestionnaire de dépendances PHP
- **Symfony Console** : Commandes CLI
- **Git** : Contrôle de version (potentiel)

### 7.6 Commandes Console Utilisées

```bash
# Vérifier les entités Doctrine
php bin/console doctrine:mapping:info

# Exécuter du SQL brut
php bin/console dbal:run-sql "SELECT * FROM user"

# Créer un admin
php bin/console app:create-admin

# Vider le cache
php bin/console cache:clear

# Lister les routes
php bin/console debug:router

# Voir la configuration de sécurité
php bin/console debug:config security
```

---

## 8. Résumé des Fonctionnalités Implémentées

### ✅ Gestion des Utilisateurs
- Inscription avec validation complète
- Connexion sécurisée (hashage bcrypt)
- Profils utilisateurs avec photo
- Paramètres personnalisables
- Suppression de compte (auto et admin)

### ✅ Intégration Base de Données
- 15 tables intégrées
- 11 entités Symfony
- Relations OneToMany et ManyToOne
- CASCADE DELETE fonctionnel
- Clés étrangères sur toutes les tables

### ✅ Système d'Administration
- Panel admin dédié
- Dashboard avec statistiques
- Liste complète des utilisateurs
- Template admin séparé
- Accès restreint (ROLE_ADMIN)

### ✅ Gestion des Rôles
- Système de rôles hiérarchique
- Promotion en admin
- Rétrogradation
- Protection multi-couches

### ✅ Système de Bannissement
- Bannissement avec raison
- Date de bannissement
- Débannissement
- Vérification à la connexion
- Blocage d'accès immédiat

### ✅ Sécurité
- CSRF Protection
- Password Hashing
- Access Control
- User Checker
- Email/Username unique

---

## Conclusion

Ce document présente l'architecture technique complète du système de gestion des utilisateurs de RLIFE, incluant :

1. **L'intégration de la base de données** avec toutes les tables des différents modules
2. **Le système d'authentification** complet (inscription, connexion, déconnexion)
3. **La gestion des comptes** (création, suppression, modification)
4. **L'interface administrateur** avec template dédié et accès restreint
5. **Le système de bannissement** avec raisons et traçabilité
6. **La promotion d'utilisateurs** en administrateurs

Toutes ces fonctionnalités ont été implémentées en suivant les meilleures pratiques Symfony, avec une sécurité multi-couches et une architecture MVC propre.

---

**Document préparé pour la présentation au professeur**  
**Projet RLIFE - Module Gestion des Utilisateurs**  
**Date:** 05 Février 2026
