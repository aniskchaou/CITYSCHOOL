<?php

namespace App\Command;

use App\Entity\School;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(name: 'app:trial-reminders', description: 'Send reminder emails before free trials expire.')]
class SendTrialReminderCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MailerInterface $mailer,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $schools = $this->entityManager->getRepository(School::class)->findBy(['status' => 'trialing']);
        $today = new \DateTimeImmutable();
        $stages = [7, 3, 1, 0];
        $sentCount = 0;

        foreach ($schools as $school) {
            if (!$school instanceof School || !$school->getTrialEndsAt()) {
                continue;
            }

            $daysRemaining = $school->getTrialDaysRemaining($today);
            if ($daysRemaining === null || !in_array($daysRemaining, $stages, true)) {
                continue;
            }

            if ($school->getTrialReminderStage() !== null && $school->getTrialReminderStage() <= $daysRemaining) {
                continue;
            }

            $subject = $daysRemaining > 0
                ? sprintf('Your City School trial ends in %d day%s', $daysRemaining, $daysRemaining === 1 ? '' : 's')
                : 'Your City School trial has ended';

            $body = $daysRemaining > 0
                ? sprintf('Your free trial for %s ends in %d day%s. Upgrade now to keep using the platform without interruption.', $school->getName(), $daysRemaining, $daysRemaining === 1 ? '' : 's')
                : sprintf('Your free trial for %s has ended. Upgrade now to continue using the platform.', $school->getName());

            $email = (new Email())
                ->from($_ENV['TRIAL_REMINDER_FROM_EMAIL'] ?? 'no-reply@cityschool.edu')
                ->to($school->getEmail())
                ->subject($subject)
                ->text($body . PHP_EOL . PHP_EOL . 'Visit the pricing page to upgrade: http://localhost:8000/pricing');

            try {
                $this->mailer->send($email);
                $school->setTrialReminderStage($daysRemaining);
                ++$sentCount;
            } catch (\Throwable) {
                continue;
            }
        }

        $this->entityManager->flush();
        $output->writeln(sprintf('Sent %d trial reminder email(s).', $sentCount));

        return Command::SUCCESS;
    }
}