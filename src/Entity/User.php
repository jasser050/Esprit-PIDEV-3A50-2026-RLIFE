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
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    private ?string $lastName = null;

    #[ORM\Column(length: 50)]
    private ?string $username = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $profilePic = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phoneNumber = null;

    #[ORM\Column(length: 10)]
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

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $avatarType = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isVerified = false;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $verificationToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $verificationTokenExpiresAt = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $faceDescriptor = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $resetPasswordToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $resetPasswordTokenExpiresAt = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $coins = 0;

    /**
     * @var Collection<int, Career>
     */
    #[ORM\OneToMany(targetEntity: Career::class, mappedBy: 'user', orphanRemoval: true)]
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

    /**
     * @var Collection<int, Pet>
     */
    #[ORM\OneToMany(targetEntity: Pet::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $pets;

    /**
     * @var Collection<int, Notification>
     */
    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'user', orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $notifications;

    public function __construct()
    {
        $this->careers = new ArrayCollection();
        $this->matieres = new ArrayCollection();
        $this->evaluations = new ArrayCollection();
        $this->projects = new ArrayCollection();
        $this->assignments = new ArrayCollection();
        $this->decks = new ArrayCollection();
        $this->pets = new ArrayCollection();
        $this->notifications = new ArrayCollection();
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

    public function getCoins(): int
    {
        return $this->coins;
    }

    public function setCoins(int $coins): static
    {
        $this->coins = max(0, $coins);

        return $this;
    }

    public function getCareers(): Collection
    {
        return $this->careers;
    }

    public function addCareer(Career $career): static
    {
        if (!$this->careers->contains($career)) {
            $this->careers->add($career);
            $career->setUser($this);
        }

        return $this;
    }

    public function removeCareer(Career $career): static
    {
        if ($this->careers->removeElement($career)) {
            if ($career->getUser() === $this) {
                $career->setUser(null);
            }
        }

        return $this;
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

    public function addProject(Project $project): static
    {
        if (!$this->projects->contains($project)) {
            $this->projects->add($project);
            $project->setUser($this);
        }

        return $this;
    }

    public function removeProject(Project $project): static
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

    public function addAssignment(Assignment $assignment): static
    {
        if (!$this->assignments->contains($assignment)) {
            $this->assignments->add($assignment);
            $assignment->setUser($this);
        }

        return $this;
    }

    public function removeAssignment(Assignment $assignment): static
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

    public function addDeck(Deck $deck): static
    {
        if (!$this->decks->contains($deck)) {
            $this->decks->add($deck);
            $deck->setUser($this);
        }

        return $this;
    }

    public function removeDeck(Deck $deck): static
    {
        if ($this->decks->removeElement($deck)) {
            if ($deck->getUser() === $this) {
                $deck->setUser(null);
            }
        }

        return $this;
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
public function calculateOverallAverage(): float{
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

// ==========================================
// 🤖 AI STUDY COACH - DÉBUT
// ==========================================

/**
 * 🤖 Obtenir les recommandations IA personnalisées
 */
public function getAIRecommendations(): array
{
    $recommendations = [];
    
    // 1. Analyser les matières à risque
    $atRiskSubjects = $this->identifyAtRiskSubjects();
    
    // 2. Analyser les opportunités d'amélioration
    $improvementOpportunities = $this->identifyImprovementOpportunities();
    
    // 3. Recommandations de planning
    $studySchedule = $this->generateOptimalStudySchedule();
    
    // 4. Stratégies d'apprentissage personnalisées
    $learningStrategies = $this->recommendLearningStrategies();
    
    // 5. Objectifs SMART
    $smartGoals = $this->generateSmartGoals();
    
    return [
        'priority_level' => $this->calculateOverallPriorityLevel(),
        'at_risk_subjects' => $atRiskSubjects,
        'improvement_opportunities' => $improvementOpportunities,
        'study_schedule' => $studySchedule,
        'learning_strategies' => $learningStrategies,
        'smart_goals' => $smartGoals,
        'motivational_message' => $this->generateMotivationalMessage(),
    ];
}

/**
 * 🚨 Identifier les matières à risque
 */
private function identifyAtRiskSubjects(): array
{
    $atRisk = [];
    $streaksByMatiere = $this->getStreaksByMatiere();
    
    foreach ($streaksByMatiere as $id => $data) {
        $matiere = $data['matiere'];
        $recentEvals = $this->getRecentEvaluations($matiere, 3);
        
        if (empty($recentEvals)) {
            continue;
        }
        
        // Calculer la moyenne récente
        $recentAverage = 0;
        foreach ($recentEvals as $eval) {
            $recentAverage += $eval->getPercentage();
        }
        $recentAverage = $recentAverage / count($recentEvals);
        
        // Détecter la tendance
        $trend = $this->calculateTrend($recentEvals);
        
        // Score de risque (0-100)
        $riskScore = 0;
        
        // Facteur 1 : Moyenne faible
        if ($recentAverage < 60) {
            $riskScore += 40;
        } elseif ($recentAverage < 75) {
            $riskScore += 20;
        }
        
        // Facteur 2 : Tendance négative
        if ($trend < -2) {
            $riskScore += 30;
        } elseif ($trend < 0) {
            $riskScore += 15;
        }
        
        // Facteur 3 : Streak perdu
        if ($data['high_score']['current'] == 0 && $data['high_score']['longest'] > 0) {
            $riskScore += 20;
        }
        
        // Facteur 4 : Évaluations urgentes à venir
        $upcomingUrgent = $this->hasUpcomingUrgentEvaluations($matiere);
        if ($upcomingUrgent) {
            $riskScore += 10;
        }
        
        if ($riskScore > 0) {
            $atRisk[] = [
                'matiere' => $matiere,
                'risk_score' => min(100, $riskScore),
                'recent_average' => round($recentAverage, 1),
                'trend' => $trend,
                'recommendation' => $this->generateRiskRecommendation($riskScore, $recentAverage, $trend),
                'urgency' => $this->calculateUrgency($riskScore),
            ];
        }
    }
    
    // Trier par score de risque décroissant
    usort($atRisk, fn($a, $b) => $b['risk_score'] - $a['risk_score']);
    
    return $atRisk;
}

/**
 * 💡 Identifier les opportunités d'amélioration
 */
private function identifyImprovementOpportunities(): array
{
    $opportunities = [];
    $streaksByMatiere = $this->getStreaksByMatiere();
    
    foreach ($streaksByMatiere as $id => $data) {
        $matiere = $data['matiere'];
        $recentEvals = $this->getRecentEvaluations($matiere, 5);
        
        if (empty($recentEvals)) {
            continue;
        }
        
        $recentAverage = 0;
        foreach ($recentEvals as $eval) {
            $recentAverage += $eval->getPercentage();
        }
        $recentAverage = $recentAverage / count($recentEvals);
        
        $trend = $this->calculateTrend($recentEvals);
        
        // Opportunité 1 : Tendance positive mais pas encore excellent
        if ($trend > 0 && $recentAverage >= 60 && $recentAverage < 90) {
            $opportunities[] = [
                'matiere' => $matiere,
                'type' => 'positive_momentum',
                'current_average' => round($recentAverage, 1),
                'trend' => round($trend, 2),
                'message' => "You're on the right track! Keep the momentum going.",
                'action' => "Aim for {$this->calculateNextTarget($recentAverage)}% in your next evaluation.",
                'icon' => '📈',
            ];
        }
        
        // Opportunité 2 : Proche du palier supérieur
        if ($recentAverage >= 70 && $recentAverage < 75) {
            $opportunities[] = [
                'matiere' => $matiere,
                'type' => 'near_threshold',
                'current_average' => round($recentAverage, 1),
                'message' => "You're very close to the 75% threshold!",
                'action' => "Just " . round(75 - $recentAverage, 1) . "% more to start a new streak.",
                'icon' => '🎯',
            ];
        }
        
        // Opportunité 3 : Streak en construction
        if ($data['high_score']['current'] >= 2 && $data['high_score']['current'] < 5) {
            $opportunities[] = [
                'matiere' => $matiere,
                'type' => 'streak_building',
                'current_streak' => $data['high_score']['current'],
                'message' => "Building a great streak!",
                'action' => "Keep it up to unlock the 'Unstoppable ⚡' badge.",
                'icon' => '🔥',
            ];
        }
    }
    
    return $opportunities;
}

/**
 * 📅 Générer un planning d'étude optimal
 */
private function generateOptimalStudySchedule(): array
{
    $schedule = [];
    $atRisk = $this->identifyAtRiskSubjects();
    
    // Prioriser les matières à risque
    $priorityDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    $dayIndex = 0;
    
    foreach ($atRisk as $risk) {
        if ($dayIndex < count($priorityDays)) {
            $studyTime = $this->calculateOptimalStudyTime($risk['risk_score']);
            
            $schedule[] = [
                'day' => $priorityDays[$dayIndex],
                'matiere' => $risk['matiere'],
                'duration_minutes' => $studyTime,
                'priority' => 'high',
                'reason' => $risk['recommendation'],
            ];
            
            $dayIndex++;
        }
    }
    
    return $schedule;
}

/**
 * 🧠 Recommander des stratégies d'apprentissage
 */
private function recommendLearningStrategies(): array
{
    $strategies = [];
    $overall = $this->calculateOverallAverage();
    $evaluationCount = $this->evaluations->count();
    
    // Stratégie selon la performance globale
    if ($overall < 60) {
        $strategies[] = [
            'title' => 'Back to Basics',
            'icon' => '📚',
            'description' => 'Focus on understanding core concepts before moving forward.',
            'techniques' => [
                'Use the Feynman Technique: Explain concepts in simple terms',
                'Create mind maps to visualize connections',
                'Practice active recall instead of passive reading',
            ],
        ];
    } elseif ($overall < 75) {
        $strategies[] = [
            'title' => 'Consistency is Key',
            'icon' => '⏰',
            'description' => 'Build regular study habits to maintain progress.',
            'techniques' => [
                'Use the Pomodoro Technique (25 min focus, 5 min break)',
                'Review notes within 24 hours of each class',
                'Create a weekly study schedule',
            ],
        ];
    } else {
        $strategies[] = [
            'title' => 'Excellence Mode',
            'icon' => '🎯',
            'description' => 'Challenge yourself to reach mastery level.',
            'techniques' => [
                'Teach concepts to others (study groups)',
                'Solve advanced practice problems',
                'Create your own exam questions',
            ],
        ];
    }
    
    // Stratégie selon le nombre d'évaluations
    if ($evaluationCount < 5) {
        $strategies[] = [
            'title' => 'Build Data',
            'icon' => '📊',
            'description' => 'Complete more evaluations to get better AI insights.',
            'techniques' => [
                'Take practice tests regularly',
                'Track all your scores',
                'Build a performance baseline',
            ],
        ];
    }
    
    return $strategies;
}

/**
 * 🎯 Générer des objectifs SMART
 */
private function generateSmartGoals(): array
{
    $goals = [];
    $overall = $this->calculateOverallAverage();
    $streak = $this->getHighScoreStreak();
    
    // Objectif de moyenne
    $targetAverage = min(100, $overall + 10);
    $goals[] = [
        'type' => 'average',
        'current' => round($overall, 1),
        'target' => round($targetAverage, 1),
        'deadline' => '1 month',
        'specific' => "Increase overall average from {$overall}% to {$targetAverage}%",
        'measurable' => "Track after each evaluation",
        'achievable' => "Focus on weak subjects",
        'relevant' => "Better grades = better opportunities",
        'time_bound' => date('F d, Y', strtotime('+1 month')),
    ];
    
    // Objectif de streak
    $targetStreak = max(5, $streak['longest'] + 3);
    $goals[] = [
        'type' => 'streak',
        'current' => $streak['current'],
        'target' => $targetStreak,
        'deadline' => '2 weeks',
        'specific' => "Build a {$targetStreak}-evaluation streak",
        'measurable' => "Get {$targetStreak} consecutive scores ≥75%",
        'achievable' => "Review before each evaluation",
        'relevant' => "Consistency leads to mastery",
        'time_bound' => date('F d, Y', strtotime('+2 weeks')),
    ];
    
    return $goals;
}

/**
 * 💪 Générer un message motivationnel personnalisé
 */
private function generateMotivationalMessage(): array
{
    $overall = $this->calculateOverallAverage();
    $streak = $this->getHighScoreStreak();
    $atRisk = $this->identifyAtRiskSubjects();
    
    $messages = [];
    
    if ($overall >= 90) {
        $messages[] = "🌟 Outstanding performance! You're in the top tier!";
    } elseif ($overall >= 75) {
        $messages[] = "🎯 Great work! You're maintaining strong performance!";
    } elseif ($overall >= 60) {
        $messages[] = "📈 You're making progress! Keep pushing forward!";
    } else {
        $messages[] = "💪 Every expert was once a beginner. You've got this!";
    }
    
    if ($streak['current'] >= 5) {
        $messages[] = "🔥 Your {$streak['current']}-evaluation streak is impressive!";
    }
    
    if (count($atRisk) > 0) {
        $messages[] = "⚠️ Focus on " . count($atRisk) . " subject(s) that need attention.";
    }
    
    return [
        'main' => $messages[0],
        'additional' => array_slice($messages, 1),
        'quote' => $this->getMotivationalQuote($overall),
    ];
}

// ==========================================
// 🤖 MÉTHODES UTILITAIRES
// ==========================================

private function getRecentEvaluations($matiere, int $count): array
{
    $evaluations = [];
    
    foreach ($this->evaluations as $eval) {
        foreach ($eval->getEvalMats() as $evalMat) {
            if ($evalMat->getMatiere() === $matiere) {
                $evaluations[] = $eval;
                break;
            }
        }
    }
    
    usort($evaluations, fn($a, $b) => $b->getDateEvaluation() <=> $a->getDateEvaluation());
    
    return array_slice($evaluations, 0, $count);
}

private function calculateTrend(array $evaluations): float
{
    if (count($evaluations) < 2) {
        return 0;
    }
    
    $scores = array_map(fn($e) => $e->getPercentage(), $evaluations);
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
    
    return ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
}

private function hasUpcomingUrgentEvaluations($matiere): bool
{
    $now = new \DateTime();
    $twoWeeks = new \DateTime('+2 weeks');
    
    foreach ($this->evaluations as $eval) {
        if ($eval->getPrioriteE() === 'urgent') {
            $evalDate = $eval->getDateEvaluation();
            if ($evalDate >= $now && $evalDate <= $twoWeeks) {
                foreach ($eval->getEvalMats() as $evalMat) {
                    if ($evalMat->getMatiere() === $matiere) {
                        return true;
                    }
                }
            }
        }
    }
    
    return false;
}

private function generateRiskRecommendation(int $riskScore, float $average, float $trend): string
{
    if ($riskScore >= 70) {
        return "🚨 URGENT: Dedicate 2-3 hours daily. Consider tutoring.";
    } elseif ($riskScore >= 50) {
        return "⚠️ HIGH PRIORITY: Schedule 1-2 hours daily review sessions.";
    } elseif ($riskScore >= 30) {
        return "⚡ ATTENTION NEEDED: Increase study time by 30 minutes daily.";
    }
    return "📌 MONITOR: Keep consistent practice to maintain progress.";
}

private function calculateUrgency(int $riskScore): string
{
    if ($riskScore >= 70) return 'critical';
    if ($riskScore >= 50) return 'high';
    if ($riskScore >= 30) return 'medium';
    return 'low';
}

private function calculateOptimalStudyTime(int $riskScore): int
{
    // Minutes par session
    if ($riskScore >= 70) return 120; // 2 heures
    if ($riskScore >= 50) return 90;  // 1.5 heures
    if ($riskScore >= 30) return 60;  // 1 heure
    return 45; // 45 minutes
}

private function calculateNextTarget(float $current): int
{
    if ($current < 75) return 75;
    if ($current < 80) return 80;
    if ($current < 85) return 85;
    if ($current < 90) return 90;
    return 95;
}

private function calculateOverallPriorityLevel(): string
{
    $atRisk = $this->identifyAtRiskSubjects();
    
    if (count($atRisk) == 0) return 'relaxed';
    
    $avgRisk = 0;
    foreach ($atRisk as $risk) {
        $avgRisk += $risk['risk_score'];
    }
    $avgRisk = $avgRisk / count($atRisk);
    
    if ($avgRisk >= 70) return 'critical';
    if ($avgRisk >= 50) return 'high';
    if ($avgRisk >= 30) return 'moderate';
    return 'low';
}

private function getMotivationalQuote(float $average): string
{
    $quotes = [
        "Success is the sum of small efforts repeated day in and day out. - Robert Collier",
        "The expert in anything was once a beginner. - Helen Hayes",
        "Education is not preparation for life; education is life itself. - John Dewey",
        "The beautiful thing about learning is that no one can take it away from you. - B.B. King",
        "Success is not final, failure is not fatal: it is the courage to continue that counts. - Winston Churchill",
    ];
    
    return $quotes[array_rand($quotes)];
}

// ==========================================
// 🤖 AI STUDY COACH - FIN
// ==========================================

    public function getAvatarType(): ?string
    {
        return $this->avatarType;
    }

    public function setAvatarType(?string $avatarType): self
    {
        $this->avatarType = $avatarType;
        return $this;
    }

    public function removePet(Pet $pet): static
    {
        if ($this->pets->removeElement($pet)) {
            if ($pet->getUser() === $this) {
                $pet->setUser(null);
            }
        }

        return $this;
    }

    public function getMainPet(): ?Pet
    {
        if ($this->pets->isEmpty()) {
            return null;
        }

        return $this->pets->first() ?: null;
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->setUser($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            if ($notification->getUser() === $this) {
                $notification->setUser(null);
            }
        }

        return $this;
    }
    /**
     * @return list<EvaluationMatiere>
     */
    private function getSortedEvaluations(): array
    {
        $evaluations = $this->evaluations->toArray();
        usort($evaluations, static function (EvaluationMatiere $a, EvaluationMatiere $b): int {
            $aTs = $a->getDateEvaluation()?->getTimestamp() ?? 0;
            $bTs = $b->getDateEvaluation()?->getTimestamp() ?? 0;
            return $aTs <=> $bTs;
        });

        return $evaluations;
    }

    private function percentageFromEvaluation(EvaluationMatiere $evaluation): float
    {
        $score = (float) ($evaluation->getScoreEval() ?? 0.0);
        $max = (float) ($evaluation->getNoteMaximaleEval() ?? 0.0);

        if ($max <= 0.0) {
            return 0.0;
        }

        return round(($score / $max) * 100, 2);
    }

    /**
     * @return array{current:int,longest:int,status:array{emoji:string,message:string},badges:array<int,string>}
     */
    private function computeStreakFromBooleanSeries(array $series, string $label): array
    {
        $current = 0;
        for ($i = count($series) - 1; $i >= 0; --$i) {
            if ($series[$i] === true) {
                ++$current;
                continue;
            }
            break;
        }

        $longest = 0;
        $running = 0;
        foreach ($series as $ok) {
            if ($ok === true) {
                ++$running;
                $longest = max($longest, $running);
            } else {
                $running = 0;
            }
        }

        return [
            'current' => $current,
            'longest' => $longest,
            'status' => $this->buildStreakStatus($current),
            'badges' => $this->buildBadgesForStreak($label, $longest),
        ];
    }

    /**
     * @return array{emoji:string,message:string}
     */
    private function buildStreakStatus(int $current): array
    {
        if ($current >= 10) {
            return ['emoji' => '🏆', 'message' => 'Legendary streak'];
        }
        if ($current >= 5) {
            return ['emoji' => '🔥', 'message' => 'Excellent consistency'];
        }
        if ($current >= 3) {
            return ['emoji' => '⚡', 'message' => 'Great momentum'];
        }
        if ($current >= 1) {
            return ['emoji' => '🙂', 'message' => 'Good start'];
        }

        return ['emoji' => '💤', 'message' => 'Start your streak'];
    }

    /**
     * @return array<int,string>
     */
    private function buildBadgesForStreak(string $label, int $value): array
    {
        $badges = [];
        if ($value >= 3) {
            $badges[] = sprintf('%s x3', $label);
        }
        if ($value >= 5) {
            $badges[] = sprintf('%s x5', $label);
        }
        if ($value >= 10) {
            $badges[] = sprintf('%s x10', $label);
        }

        return $badges;
    }

    private function getMatiereForEvaluation(EvaluationMatiere $evaluation): ?Matiere
    {
        $evalMats = $evaluation->getEvalMats();
        foreach ($evalMats as $evalMat) {
            $matiere = $evalMat->getMatiere();
            if ($matiere !== null && $matiere->getUser()?->getId() === $this->getId()) {
                return $matiere;
            }
        }

        return null;
    }
}
