<?php
namespace App\Notifications;
use Illuminate\Bus\Queueable; use Illuminate\Notifications\Messages\MailMessage; use Illuminate\Notifications\Notification;
class OperationAlertNotification extends Notification { use Queueable; public function __construct(private string $title, private string $severity, private string $message) {}
public function via(object $notifiable): array { return ['mail']; }
public function toMail(object $notifiable): MailMessage { return (new MailMessage)->subject('[Noticiario] '.$this->title)->line('Severity: '.$this->severity)->line($this->message)->line('Do not include secrets in alerts.')->action('Operations Dashboard', url('/admin/operations')); }}
