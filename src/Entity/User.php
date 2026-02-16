<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_USERNAME', fields: ['username'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
#[UniqueEntity(fields: ['username'], message: 'This username is already taken')]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Please enter a valid email address')]
    #[Assert\Length(
        max: 180,
        maxMessage: 'Email cannot be longer than {{ limit }} characters'
    )]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true, unique: true)]
    private ?string $googleId = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'First name is required')]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'First name must be at least {{ limit }} characters',
        maxMessage: 'First name cannot be longer than {{ limit }} characters'
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z\s\-\']+$/',
        message: 'First name can only contain letters, spaces, hyphens and apostrophes'
    )]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Last name is required')]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'Last name must be at least {{ limit }} characters',
        maxMessage: 'Last name cannot be longer than {{ limit }} characters'
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z\s\-\']+$/',
        message: 'Last name can only contain letters, spaces, hyphens and apostrophes'
    )]
    private ?string $lastName = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Username is required')]
    #[Assert\Length(
        min: 3,
        max: 50,
        minMessage: 'Username must be at least {{ limit }} characters',
        maxMessage: 'Username cannot be longer than {{ limit }} characters'
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z0-9_]+$/',
        message: 'Username can only contain letters, numbers and underscores'
    )]
    private ?string $username = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $profilePic = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phoneNumber = null;

    #[ORM\Column(length: 10)]
    #[Assert\NotBlank(message: 'Gender is required')]
    #[Assert\Choice(
        choices: ['male', 'female', 'other'],
        message: 'Please select a valid gender'
    )]
    private ?string $gender = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $bio = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $studentId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $university = null;

    #[ORM\Column]
    private bool $isBanned = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $bannedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $banReason = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     
     */
       private Collection $careers;

    #[ORM\OneToOne(targetEntity: UserSettings::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?UserSettings $settings = null;

    /**
     * @var Collection<int, Matiere>
     */
    #[ORM\OneToMany(targetEntity: Matiere::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $matieres;

    /**
     * @var Collection<int, EvaluationMatiere>
     */
    #[ORM\OneToMany(targetEntity: EvaluationMatiere::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $evaluations;

    /**
     * @var Collection<int, Project>
     */
    #[ORM\OneToMany(targetEntity: Project::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $projects;

    /**
     * @var Collection<int, Assignment>
     */
    #[ORM\OneToMany(targetEntity: Assignment::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $assignments;

    /**
     * @var Collection<int, Deck>
     */
    #[ORM\OneToMany(targetEntity: Deck::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $decks;

    public function __construct()
    {
        $this->careers = new ArrayCollection();
        $this->matieres = new ArrayCollection();
        $this->evaluations = new ArrayCollection();
        $this->projects = new ArrayCollection();
        $this->assignments = new ArrayCollection();
        $this->decks = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->roles = ['ROLE_USER'];
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getProfilePic(): ?string
    {
        return $this->profilePic;
    }

    public function setProfilePic(?string $profilePic): static
    {
        $this->profilePic = $profilePic;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): static
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(string $gender): static
    {
        $this->gender = $gender;

        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): static
    {
        $this->bio = $bio;

        return $this;
    }

    public function getStudentId(): ?string
    {
        return $this->studentId;
    }

    public function setStudentId(?string $studentId): static
    {
        $this->studentId = $studentId;

        return $this;
    }

    public function getUniversity(): ?string
    {
        return $this->university;
    }

    public function setUniversity(?string $university): static
    {
        $this->university = $university;

        return $this;
    }

    public function isBanned(): bool
    {
        return $this->isBanned;
    }

    public function setIsBanned(bool $isBanned): static
    {
        $this->isBanned = $isBanned;

        return $this;
    }

    public function getBannedAt(): ?\DateTimeImmutable
    {
        return $this->bannedAt;
    }

    public function setBannedAt(?\DateTimeImmutable $bannedAt): static
    {
        $this->bannedAt = $bannedAt;

        return $this;
    }

    public function getBanReason(): ?string
    {
        return $this->banReason;
    }

    public function setBanReason(?string $banReason): static
    {
        $this->banReason = $banReason;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     
     */
    public function getCareers(): Collection
    {
        return $this->careers;
    }

    
      
        

    

    public function getSettings(): ?UserSettings
    {
        return $this->settings;
    }

    public function setSettings(?UserSettings $settings): static
    {
        // unset the owning side of the relation if necessary
        if ($settings === null && $this->settings !== null) {
            $this->settings->setUser(null);
        }

        // set the owning side of the relation if necessary
        if ($settings !== null && $settings->getUser() !== $this) {
            $settings->setUser($this);
        }

        $this->settings = $settings;

        return $this;
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function isAdmin(): bool
    {
        return in_array('ROLE_ADMIN', $this->roles, true);
    }

    /**
     * @return Collection<int, Matiere>
     */
    public function getMatieres(): Collection
    {
        return $this->matieres;
    }

    public function addMatiere(Matiere $matiere): self
    {
        if (!$this->matieres->contains($matiere)) {
            $this->matieres->add($matiere);
            $matiere->setUser($this);
        }

        return $this;
    }

    public function removeMatiere(Matiere $matiere): self
    {
        if ($this->matieres->removeElement($matiere)) {
            if ($matiere->getUser() === $this) {
                $matiere->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, EvaluationMatiere>
     */
    public function getEvaluations(): Collection
    {
        return $this->evaluations;
    }

    public function addEvaluation(EvaluationMatiere $evaluation): self
    {
        if (!$this->evaluations->contains($evaluation)) {
            $this->evaluations->add($evaluation);
            $evaluation->setUser($this);
        }

        return $this;
    }

    public function removeEvaluation(EvaluationMatiere $evaluation): self
    {
        if ($this->evaluations->removeElement($evaluation)) {
            if ($evaluation->getUser() === $this) {
                $evaluation->setUser(null);
            }
        }

        return $this;
    }

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): self
    {
        $this->googleId = $googleId;
        return $this;
    }

    /**
     * @return Collection<int, Project>
     */
    public function getProjects(): Collection
    {
        return $this->projects;
    }

    public function addProject(Project $project): self
    {
        if (!$this->projects->contains($project)) {
            $this->projects->add($project);
            $project->setUser($this);
        }

        return $this;
    }

    public function removeProject(Project $project): self
    {
        if ($this->projects->removeElement($project)) {
            if ($project->getUser() === $this) {
                $project->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Assignment>
     */
    public function getAssignments(): Collection
    {
        return $this->assignments;
    }

    public function addAssignment(Assignment $assignment): self
    {
        if (!$this->assignments->contains($assignment)) {
            $this->assignments->add($assignment);
            $assignment->setUser($this);
        }

        return $this;
    }

    public function removeAssignment(Assignment $assignment): self
    {
        if ($this->assignments->removeElement($assignment)) {
            if ($assignment->getUser() === $this) {
                $assignment->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Deck>
     */
    public function getDecks(): Collection
    {
        return $this->decks;
    }

    public function addDeck(Deck $deck): self
    {
        if (!$this->decks->contains($deck)) {
            $this->decks->add($deck);
            $deck->setUser($this);
        }

        return $this;
    }

    public function removeDeck(Deck $deck): self
    {
        if ($this->decks->removeElement($deck)) {
            if ($deck->getUser() === $this) {
                $deck->setUser(null);
            }
        }

        return $this;
    }

// ==========================================
// 🔥 SYSTÈME DE STREAK - DÉBUT
// ==========================================

/**
 * 🔥 Calculer le streak de notes élevées (> 75%)
 */
public function getHighScoreStreak(?Matiere $matiere = null): array
{
    $evaluations = $this->getEvaluationsOrderedByDate($matiere);
    
    $currentStreak = 0;
    $longestStreak = 0;
    $badges = [];
    
    foreach ($evaluations as $eval) {
        if ($eval->getPercentage() >= 75) {
            $currentStreak++;
            if ($currentStreak > $longestStreak) {
                $longestStreak = $currentStreak;
            }
        } else {
            $currentStreak = 0;
        }
    }
    
    // Attribution des badges selon le streak actuel
    if ($currentStreak >= 10) {
        $badges[] = '👑 Legend';
    } elseif ($currentStreak >= 7) {
        $badges[] = '🔥 On Fire';
    } elseif ($currentStreak >= 5) {
        $badges[] = '⚡ Unstoppable';
    } elseif ($currentStreak >= 3) {
        $badges[] = '⭐ Great Streak';
    }
    
    return [
        'current' => $currentStreak,
        'longest' => $longestStreak,
        'badges' => $badges,
        'status' => $this->getStreakStatus($currentStreak)
    ];
}

/**
 * 🎯 Calculer le streak de notes parfaites (> 90%)
 */
public function getPerfectScoreStreak(?Matiere $matiere = null): array
{
    $evaluations = $this->getEvaluationsOrderedByDate($matiere);
    
    $currentStreak = 0;
    $longestStreak = 0;
    
    foreach ($evaluations as $eval) {
        if ($eval->getPercentage() >= 90) {
            $currentStreak++;
            if ($currentStreak > $longestStreak) {
                $longestStreak = $currentStreak;
            }
        } else {
            $currentStreak = 0;
        }
    }
    
    $badges = [];
    if ($currentStreak >= 5) {
        $badges[] = '🌟 Master';
    } elseif ($currentStreak >= 3) {
        $badges[] = '💎 Perfectionist';
    } elseif ($currentStreak >= 2) {
        $badges[] = '✨ Excellent';
    }
    
    return [
        'current' => $currentStreak,
        'longest' => $longestStreak,
        'badges' => $badges,
        'status' => $this->getStreakStatus($currentStreak)
    ];
}

/**
 * 📈 Calculer le streak de progression
 */
public function getProgressionStreak(?Matiere $matiere = null): array
{
    $evaluations = $this->getEvaluationsOrderedByDate($matiere);
    
    $currentStreak = 0;
    $longestStreak = 0;
    $previousPercentage = null;
    
    foreach ($evaluations as $eval) {
        if ($previousPercentage !== null && $eval->getPercentage() > $previousPercentage) {
            $currentStreak++;
            if ($currentStreak > $longestStreak) {
                $longestStreak = $currentStreak;
            }
        } else {
            $currentStreak = 0;
        }
        $previousPercentage = $eval->getPercentage();
    }
    
    $badges = [];
    if ($currentStreak >= 7) {
        $badges[] = '🎖️ Excellence Path';
    } elseif ($currentStreak >= 5) {
        $badges[] = '🚀 Momentum';
    } elseif ($currentStreak >= 3) {
        $badges[] = '📈 Rising Star';
    }
    
    return [
        'current' => $currentStreak,
        'longest' => $longestStreak,
        'badges' => $badges,
        'status' => $this->getStreakStatus($currentStreak)
    ];
}

/**
 * 🔴 Streak de priorités urgentes bien gérées
 */
public function getUrgentPriorityStreak(): array
{
    $urgentEvals = array_filter(
        $this->getEvaluationsOrderedByDate()->toArray(),
        fn($eval) => $eval->getPrioriteE() === 'urgent'
    );
    
    $currentStreak = 0;
    $longestStreak = 0;
    
    foreach ($urgentEvals as $eval) {
        if ($eval->getPercentage() >= 70) {
            $currentStreak++;
            if ($currentStreak > $longestStreak) {
                $longestStreak = $currentStreak;
            }
        } else {
            $currentStreak = 0;
        }
    }
    
    $badges = [];
    if ($currentStreak >= 10) {
        $badges[] = '💪 Clutch Player';
    } elseif ($currentStreak >= 5) {
        $badges[] = '🎯 Crisis Manager';
    } elseif ($currentStreak >= 3) {
        $badges[] = '⏰ Pressure Master';
    }
    
    return [
        'current' => $currentStreak,
        'longest' => $longestStreak,
        'badges' => $badges,
        'status' => $this->getStreakStatus($currentStreak)
    ];
}

/**
 * 📊 Obtenir toutes les statistiques de streak
 */
public function getAllStreakStats(?Matiere $matiere = null): array
{
    return [
        'high_score' => $this->getHighScoreStreak($matiere),
        'perfect_score' => $this->getPerfectScoreStreak($matiere),
        'progression' => $this->getProgressionStreak($matiere),
        'urgent_priority' => $this->getUrgentPriorityStreak(),
    ];
}

/**
 * 🏆 Obtenir tous les badges gagnés
 */
public function getAllBadges(): array
{
    $allStats = $this->getAllStreakStats();
    $allBadges = [];
    
    foreach ($allStats as $type => $stats) {
        foreach ($stats['badges'] as $badge) {
            $allBadges[] = [
                'name' => $badge,
                'type' => $type,
                'streak' => $stats['current']
            ];
        }
    }
    
    return $allBadges;
}

/**
 * Obtenir le statut visuel du streak
 */
private function getStreakStatus(int $streak): array
{
    if ($streak >= 10) {
        return ['emoji' => '👑', 'message' => 'LEGENDARY!', 'color' => 'gold', 'class' => 'warning'];
    } elseif ($streak >= 7) {
        return ['emoji' => '🔥', 'message' => 'ON FIRE!', 'color' => 'red', 'class' => 'danger'];
    } elseif ($streak >= 5) {
        return ['emoji' => '⚡', 'message' => 'Unstoppable!', 'color' => 'orange', 'class' => 'warning'];
    } elseif ($streak >= 3) {
        return ['emoji' => '⭐', 'message' => 'Great!', 'color' => 'blue', 'class' => 'primary'];
    } elseif ($streak >= 1) {
        return ['emoji' => '✨', 'message' => 'Keep Going!', 'color' => 'lightblue', 'class' => 'info'];
    }
    
    return ['emoji' => '💤', 'message' => 'Start Now!', 'color' => 'gray', 'class' => 'secondary'];
}

/**
 * Obtenir les évaluations triées par date
 */
private function getEvaluationsOrderedByDate(?Matiere $matiere = null): Collection
{
    $evaluations = $this->evaluations->toArray();
    
    // Filtrer par matière si spécifié
    if ($matiere !== null) {
        $evaluations = array_filter($evaluations, function($eval) use ($matiere) {
            foreach ($eval->getEvalMats() as $evalMat) {
                if ($evalMat->getMatiere() === $matiere) {
                    return true;
                }
            }
            return false;
        });
    }
    
    // Trier par date
    usort($evaluations, function($a, $b) {
        return $a->getDateEvaluation() <=> $b->getDateEvaluation();
    });
    
    return new ArrayCollection($evaluations);
}

/**
 * 🌍 Streak global (toutes matières confondues)
 */
public function getGlobalStreak(): array
{
    return $this->getHighScoreStreak();
}

/**
 * 📚 Streaks par matière
 */
public function getStreaksByMatiere(): array
{
    $matieres = [];
    
    foreach ($this->evaluations as $eval) {
        foreach ($eval->getEvalMats() as $evalMat) {
            $matiere = $evalMat->getMatiere();
            if ($matiere && !in_array($matiere, $matieres, true)) {
                $matieres[] = $matiere;
            }
        }
    }
    
    $streaksByMatiere = [];
    foreach ($matieres as $matiere) {
        $streaksByMatiere[$matiere->getId()] = [
            'matiere' => $matiere,
            'high_score' => $this->getHighScoreStreak($matiere),
            'perfect_score' => $this->getPerfectScoreStreak($matiere),
            'progression' => $this->getProgressionStreak($matiere),
        ];
    }
    
    return $streaksByMatiere;
}

// ==========================================
// 🔥 SYSTÈME DE STREAK - FIN
// ==========================================
// ==========================================
// 📊 STATISTIQUES AVANCÉES - DÉBUT
// ==========================================

/**
 * 📈 Obtenir l'historique des streaks pour graphique
 */
public function getStreakHistory(int $days = 30): array
{
    $history = [];
    $evaluations = $this->getEvaluationsOrderedByDate();
    
    $currentStreak = 0;
    $startDate = new \DateTime();
    $startDate->modify("-{$days} days");
    
    foreach ($evaluations as $eval) {
        if ($eval->getDateEvaluation() >= $startDate) {
            $dateStr = $eval->getDateEvaluation()->format('Y-m-d');
            $percentage = $eval->getPercentage();
            
            if ($percentage >= 75) {
                $currentStreak++;
            } else {
                $currentStreak = 0;
            }
            
            $history[] = [
                'date' => $dateStr,
                'streak' => $currentStreak,
                'percentage' => round($percentage, 2),
                'score' => $eval->getScoreEval(),
                'max' => $eval->getNoteMaximaleEval(),
            ];
        }
    }
    
    return $history;
}

/**
 * 📊 Statistiques par période (semaine, mois, année)
 */
public function getPerformanceByPeriod(string $period = 'month'): array
{
    $evaluations = $this->getEvaluationsOrderedByDate();
    $stats = [];
    
    foreach ($evaluations as $eval) {
        $date = $eval->getDateEvaluation();
        
        switch ($period) {
            case 'week':
                $key = $date->format('Y-W');
                $label = 'Week ' . $date->format('W, Y');
                break;
            case 'month':
                $key = $date->format('Y-m');
                $label = $date->format('F Y');
                break;
            case 'year':
                $key = $date->format('Y');
                $label = $date->format('Y');
                break;
            default:
                $key = $date->format('Y-m-d');
                $label = $date->format('d/m/Y');
        }
        
        if (!isset($stats[$key])) {
            $stats[$key] = [
                'label' => $label,
                'total' => 0,
                'count' => 0,
                'success' => 0,
                'perfect' => 0,
                'evaluations' => [],
            ];
        }
        
        $percentage = $eval->getPercentage();
        $stats[$key]['total'] += $percentage;
        $stats[$key]['count']++;
        $stats[$key]['evaluations'][] = $percentage;
        
        if ($percentage >= 75) {
            $stats[$key]['success']++;
        }
        if ($percentage >= 90) {
            $stats[$key]['perfect']++;
        }
    }
    
    // Calculer les moyennes et taux de réussite
    foreach ($stats as $key => $data) {
        $stats[$key]['average'] = round($data['total'] / $data['count'], 2);
        $stats[$key]['success_rate'] = round(($data['success'] / $data['count']) * 100, 1);
        $stats[$key]['perfect_rate'] = round(($data['perfect'] / $data['count']) * 100, 1);
        
        // Calculer min et max
        $stats[$key]['min'] = min($data['evaluations']);
        $stats[$key]['max'] = max($data['evaluations']);
    }
    
    return $stats;
}

/**
 * 🤖 Prédire la prochaine note basée sur la tendance (Régression linéaire simple)
 */
public function predictNextScore(?Matiere $matiere = null): array
{
    $evaluations = $this->getEvaluationsOrderedByDate($matiere);
    
    if ($evaluations->count() < 3) {
        return [
            'prediction' => null,
            'confidence' => 0,
            'trend' => 'insufficient_data',
            'message' => 'Need at least 3 evaluations for prediction',
            'slope' => 0,
        ];
    }
    
    // Prendre les dernières évaluations (maximum 10 pour ne pas surajuster)
    $recentCount = min(10, $evaluations->count());
    $recent = array_slice($evaluations->toArray(), -$recentCount);
    $scores = array_map(fn($e) => $e->getPercentage(), $recent);
    
    // Régression linéaire simple : y = mx + b
    $n = count($scores);
    $sumX = 0;
    $sumY = 0;
    $sumXY = 0;
    $sumX2 = 0;
    
    for ($i = 0; $i < $n; $i++) {
        $x = $i + 1;
        $y = $scores[$i];
        $sumX += $x;
        $sumY += $y;
        $sumXY += $x * $y;
        $sumX2 += $x * $x;
    }
    
    // Calcul de la pente (m) et de l'ordonnée à l'origine (b)
    $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
    $intercept = ($sumY - $slope * $sumX) / $n;
    
    // Prédiction pour la prochaine évaluation
    $prediction = $slope * ($n + 1) + $intercept;
    $prediction = max(0, min(100, $prediction)); // Limiter entre 0 et 100
    
    // Déterminer la tendance
    $trend = 'stable';
    $trendEmoji = '➡️';
    $message = 'Your performance is stable';
    
    if ($slope > 2) {
        $trend = 'improving';
        $trendEmoji = '📈';
        $message = 'Great! Your scores are improving';
    } elseif ($slope < -2) {
        $trend = 'declining';
        $trendEmoji = '📉';
        $message = 'Warning: Your scores are declining';
    }
    
    // Calculer la confiance basée sur :
    // 1. Nombre de données (plus = mieux)
    // 2. Cohérence des données (variance faible = mieux)
    $variance = 0;
    $mean = array_sum($scores) / $n;
    foreach ($scores as $score) {
        $variance += pow($score - $mean, 2);
    }
    $variance = $variance / $n;
    $stdDev = sqrt($variance);
    
    // Confiance : augmente avec le nombre de données, diminue avec la variance
    $dataConfidence = min(100, $n * 10); // Max 100% avec 10 données
    $varianceConfidence = max(0, 100 - ($stdDev * 2)); // Diminue si variance élevée
    $confidence = round(($dataConfidence + $varianceConfidence) / 2, 1);
    
    return [
        'prediction' => round($prediction, 1),
        'confidence' => $confidence,
        'trend' => $trend,
        'trend_emoji' => $trendEmoji,
        'message' => $message,
        'slope' => round($slope, 2),
        'data_points' => $n,
        'current_average' => round($mean, 2),
        'standard_deviation' => round($stdDev, 2),
    ];
}

/**
 * 📊 Obtenir les statistiques complètes
 */
public function getCompleteStatistics(): array
{
    return [
        'overall' => [
            'total_evaluations' => $this->evaluations->count(),
            'average' => $this->calculateOverallAverage(),
            'perfect_count' => $this->getPerfectScoresCount(),
            'high_score_count' => $this->getHighScoresCount(),
        ],
        'by_month' => $this->getPerformanceByPeriod('month'),
        'by_week' => $this->getPerformanceByPeriod('week'),
        'history_30_days' => $this->getStreakHistory(30),
        'prediction' => $this->predictNextScore(),
    ];
}

/**
 * Calculer la moyenne générale
 */
private function calculateOverallAverage(): float
{
    if ($this->evaluations->isEmpty()) {
        return 0;
    }
    
    $total = 0;
    foreach ($this->evaluations as $eval) {
        $total += $eval->getPercentage();
    }
    
    return round($total / $this->evaluations->count(), 2);
}

/**
 * Compter les notes parfaites (>= 90%)
 */
private function getPerfectScoresCount(): int
{
    $count = 0;
    foreach ($this->evaluations as $eval) {
        if ($eval->getPercentage() >= 90) {
            $count++;
        }
    }
    return $count;
}

/**
 * Compter les bonnes notes (>= 75%)
 */
private function getHighScoresCount(): int
{
    $count = 0;
    foreach ($this->evaluations as $eval) {
        if ($eval->getPercentage() >= 75) {
            $count++;
        }
    }
    return $count;
}

// ==========================================
// 📊 STATISTIQUES AVANCÉES - FIN
// ==========================================
}
