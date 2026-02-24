<?php

namespace App\Entity;

use App\Repository\UserSettingsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserSettingsRepository::class)]
class UserSettings
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'settings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $studyLevel = null;

    #[ORM\Column(nullable: true)]
    private ?int $weeklyGoal = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $interests = [];

    #[ORM\Column]
    private bool $notificationEnabled = true;

    #[ORM\Column]
    private bool $emailNotifications = true;

    #[ORM\Column(length: 20)]
    private string $themePreference = 'light';

    #[ORM\Column(length: 10)]
    private string $language = 'en';

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $starName = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $starType = null;

    #[ORM\Column(length: 10)]
    private string $fontSize = 'normal';

    #[ORM\Column(length: 50)]
    private string $fontFamily = 'system';

    #[ORM\Column(length: 20)]
    private string $accentColor = '#6366f1';

    #[ORM\Column]
    private bool $reduceMotion = false;

    #[ORM\Column]
    private bool $highContrast = false;

    #[ORM\Column(length: 20)]
    private string $lineHeight = 'normal';

    #[ORM\Column(length: 20)]
    private string $letterSpacing = 'normal';

    #[ORM\Column]
    private int $zoomLevel = 100;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getStudyLevel(): ?string
    {
        return $this->studyLevel;
    }

    public function setStudyLevel(?string $studyLevel): static
    {
        $this->studyLevel = $studyLevel;

        return $this;
    }

    public function getWeeklyGoal(): ?int
    {
        return $this->weeklyGoal;
    }

    public function setWeeklyGoal(?int $weeklyGoal): static
    {
        $this->weeklyGoal = $weeklyGoal;

        return $this;
    }

    public function getInterests(): ?array
    {
        return $this->interests;
    }

    public function setInterests(?array $interests): static
    {
        $this->interests = $interests;

        return $this;
    }

    public function isNotificationEnabled(): bool
    {
        return $this->notificationEnabled;
    }

    public function setNotificationEnabled(bool $notificationEnabled): static
    {
        $this->notificationEnabled = $notificationEnabled;

        return $this;
    }

    public function isEmailNotifications(): bool
    {
        return $this->emailNotifications;
    }

    public function setEmailNotifications(bool $emailNotifications): static
    {
        $this->emailNotifications = $emailNotifications;

        return $this;
    }

    public function getThemePreference(): string
    {
        return $this->themePreference;
    }

    public function setThemePreference(string $themePreference): static
    {
        $this->themePreference = $themePreference;

        return $this;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language): static
    {
        $this->language = $language;

        return $this;
    }

    public function getStarName(): ?string
    {
        return $this->starName;
    }

    public function setStarName(?string $starName): static
    {
        $this->starName = $starName;

        return $this;
    }

    public function getStarType(): ?string
    {
        return $this->starType;
    }

    public function setStarType(?string $starType): static
    {
        $this->starType = $starType;

        return $this;
    }

    public function getFontSize(): string
    {
        return $this->fontSize;
    }

    public function setFontSize(string $fontSize): static
    {
        $this->fontSize = $fontSize;

        return $this;
    }

    public function getFontFamily(): string
    {
        return $this->fontFamily;
    }

    public function setFontFamily(string $fontFamily): static
    {
        $this->fontFamily = $fontFamily;

        return $this;
    }

    public function getAccentColor(): string
    {
        return $this->accentColor;
    }

    public function setAccentColor(string $accentColor): static
    {
        $this->accentColor = $accentColor;

        return $this;
    }

    public function isReduceMotion(): bool
    {
        return $this->reduceMotion;
    }

    public function setReduceMotion(bool $reduceMotion): static
    {
        $this->reduceMotion = $reduceMotion;

        return $this;
    }

    public function isHighContrast(): bool
    {
        return $this->highContrast;
    }

    public function setHighContrast(bool $highContrast): static
    {
        $this->highContrast = $highContrast;

        return $this;
    }

    public function getLineHeight(): string
    {
        return $this->lineHeight;
    }

    public function setLineHeight(string $lineHeight): static
    {
        $this->lineHeight = $lineHeight;

        return $this;
    }

    public function getLetterSpacing(): string
    {
        return $this->letterSpacing;
    }

    public function setLetterSpacing(string $letterSpacing): static
    {
        $this->letterSpacing = $letterSpacing;

        return $this;
    }

    public function getZoomLevel(): int
    {
        return $this->zoomLevel;
    }

    public function setZoomLevel(int $zoomLevel): static
    {
        $this->zoomLevel = $zoomLevel;

        return $this;
    }
}
