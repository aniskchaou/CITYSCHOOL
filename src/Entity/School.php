<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'school')]
class School
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $email;

    #[ORM\Column(type: 'string', length: 255)]
    private string $passwordHash;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $country = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $timezone = null;

    /** Basic | Pro | Enterprise */
    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $plan = null;

    /** monthly | yearly */
    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $billingCycle = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $stripeCustomerId = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $stripeSubscriptionId = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $stripeCheckoutSessionId = null;

    /** pending | active | cancelled | trialing */
    #[ORM\Column(type: 'string', length: 30, options: ['default' => 'pending'])]
    private string $status = 'pending';

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $activatedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $trialStartsAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $trialEndsAt = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $trialReminderStage = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): void { $this->name = $name; }
    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): void { $this->email = $email; }
    public function getPasswordHash(): string { return $this->passwordHash; }
    public function setPasswordHash(string $passwordHash): void { $this->passwordHash = $passwordHash; }
    public function getAddress(): ?string { return $this->address; }
    public function setAddress(?string $address): void { $this->address = $address; }
    public function getCountry(): ?string { return $this->country; }
    public function setCountry(?string $country): void { $this->country = $country; }
    public function getTimezone(): ?string { return $this->timezone; }
    public function setTimezone(?string $timezone): void { $this->timezone = $timezone; }
    public function getPlan(): ?string { return $this->plan; }
    public function setPlan(?string $plan): void { $this->plan = $plan; }
    public function getBillingCycle(): ?string { return $this->billingCycle; }
    public function setBillingCycle(?string $cycle): void { $this->billingCycle = $cycle; }
    public function getStripeCustomerId(): ?string { return $this->stripeCustomerId; }
    public function setStripeCustomerId(?string $id): void { $this->stripeCustomerId = $id; }
    public function getStripeSubscriptionId(): ?string { return $this->stripeSubscriptionId; }
    public function setStripeSubscriptionId(?string $id): void { $this->stripeSubscriptionId = $id; }
    public function getStripeCheckoutSessionId(): ?string { return $this->stripeCheckoutSessionId; }
    public function setStripeCheckoutSessionId(?string $id): void { $this->stripeCheckoutSessionId = $id; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): void { $this->status = $status; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getActivatedAt(): ?\DateTimeImmutable { return $this->activatedAt; }
    public function setActivatedAt(?\DateTimeImmutable $dt): void { $this->activatedAt = $dt; }
    public function getTrialStartsAt(): ?\DateTimeImmutable { return $this->trialStartsAt; }
    public function setTrialStartsAt(?\DateTimeImmutable $trialStartsAt): void { $this->trialStartsAt = $trialStartsAt; }
    public function getTrialEndsAt(): ?\DateTimeImmutable { return $this->trialEndsAt; }
    public function setTrialEndsAt(?\DateTimeImmutable $trialEndsAt): void { $this->trialEndsAt = $trialEndsAt; }
    public function getTrialReminderStage(): ?int { return $this->trialReminderStage; }
    public function setTrialReminderStage(?int $trialReminderStage): void { $this->trialReminderStage = $trialReminderStage; }

    public function isTrialing(): bool
    {
        return $this->status === 'trialing' && $this->trialEndsAt instanceof \DateTimeImmutable;
    }

    public function isTrialExpired(?\DateTimeImmutable $reference = null): bool
    {
        if (!$this->trialEndsAt instanceof \DateTimeImmutable) {
            return false;
        }

        return $this->trialEndsAt < ($reference ?? new \DateTimeImmutable());
    }

    public function getTrialDaysRemaining(?\DateTimeImmutable $reference = null): ?int
    {
        if (!$this->trialEndsAt instanceof \DateTimeImmutable) {
            return null;
        }

        $now = ($reference ?? new \DateTimeImmutable())->setTime(0, 0);
        $end = $this->trialEndsAt->setTime(0, 0);

        return (int) $now->diff($end)->format('%r%a');
    }
}
