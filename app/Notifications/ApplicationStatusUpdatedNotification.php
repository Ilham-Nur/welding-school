<?php

namespace App\Notifications;

use App\Models\TrainingApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationStatusUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly TrainingApplication $application,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $approved = $this->application->status === 'approved';

        $mail = (new MailMessage)
            ->subject($approved ? 'Pendaftaran Pelatihan Disetujui' : 'Pembaruan Pendaftaran Pelatihan')
            ->greeting('Halo, '.$notifiable->name)
            ->line(
                $approved
                    ? 'Pendaftaran pelatihan Anda telah disetujui oleh admin.'
                    : 'Pendaftaran pelatihan Anda belum dapat disetujui.',
            )
            ->line('Nomor pendaftaran: '.$this->application->registration_number)
            ->line('Program: '.$this->application->trainingProgram->title);

        if (filled($this->application->verification_notes)) {
            $mail->line('Catatan admin: '.$this->application->verification_notes);
        }

        return $mail
            ->action('Buka Dashboard', route('home').'#member-programs')
            ->line(
                $approved
                    ? 'Silakan masuk ke dashboard untuk memeriksa rincian biaya dan membuat invoice.'
                    : 'Silakan perbaiki data sesuai catatan admin atau hubungi tim kami.',
            );
    }
}
