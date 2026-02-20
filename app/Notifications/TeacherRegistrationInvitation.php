<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeacherRegistrationInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    private string $token;
    private string $registrationUrl;

    /**
     * Create a new notification instance.
     *
     * Captures the tenant domain eagerly since this notification is queued
     * and the tenant context would be lost when the job executes.
     */
    public function __construct(string $token)
    {
        $this->token = $token;

        // Build the registration URL using the current request domain
        // This must happen before queueing since tenant context is lost in the queue worker
        $scheme = request()->getScheme();
        $host = request()->getHost();
        $this->registrationUrl = "{$scheme}://{$host}/teacher/register/{$token}";
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Complete Your Teacher Registration')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('You have been invited to join our school as a teacher.')
            ->line('Please click the button below to complete your registration and set up your account.')
            ->action('Complete Registration', $this->registrationUrl)
            ->line('This link will expire in 3 days.')
            ->line('If you did not expect this invitation, please ignore this email.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'token_expires_at' => now()->addDays(3)->toDateTimeString(),
        ];
    }
}
