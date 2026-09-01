<?php

namespace App\Notifications;

use App\Services\BrandingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeacherPortalActivated extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct() {}

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
        $schoolName = app(BrandingService::class)->getAppName();
        $loginUrl = url('/teacher/login');

        return (new MailMessage)
            ->subject("Welcome to {$schoolName} Teacher Portal")
            ->greeting("Hello {$notifiable->name},")
            ->line("Great news! Your teacher portal account at {$schoolName} has been activated by the school administration.")
            ->line("You can now log into your teacher portal using your registered email address:")
            ->line("**Email:** {$notifiable->email}")
            ->action('Log In to Teacher Portal', $loginUrl)
            ->line("Once logged in, you can manage your assigned classes, subjects, score entries, and lesson notes.")
            ->line("If you have any questions or need assistance, please contact the administrator.");
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'activated_at' => now()->toDateTimeString(),
        ];
    }
}
