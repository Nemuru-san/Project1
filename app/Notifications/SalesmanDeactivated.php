<?php

namespace App\Notifications;

use App\Models\Salesman;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SalesmanDeactivated extends Notification
{
    use Queueable;

    public function __construct(
        public Salesman $salesman,
        public CarbonImmutable $lastActivity,
        public CarbonImmutable $deadline,
    ) {}

    public function via(object $notifiable): array
    {
        // Channel mail hanya bila alamat email penerima valid (user login bisa berupa username).
        return filter_var($notifiable->email ?? null, FILTER_VALIDATE_EMAIL)
            ? ['database', 'mail']
            : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Salesman '.$this->salesman->name.' telah dinonaktifkan')
            ->greeting('Halo '.$notifiable->name.',')
            ->line("Salesman {$this->salesman->name} ({$this->salesman->code}) telah dinonaktifkan otomatis karena tidak ada Penjualan Kanvas terverifikasi sejak {$this->lastActivity->translatedFormat('d F Y')}.")
            ->line('Akun dapat diaktifkan kembali melalui menu Master Salesman.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'salesman_deactivated',
            'salesman_id' => $this->salesman->id,
            'salesman_name' => $this->salesman->name,
            'salesman_code' => $this->salesman->code,
            'last_activity' => $this->lastActivity->toDateString(),
            'deadline' => $this->deadline->toDateString(),
            'title' => 'Salesman '.$this->salesman->name.' dinonaktifkan',
            'message' => 'Salesman '.$this->salesman->name.' ('.$this->salesman->code.') dinonaktifkan otomatis karena tidak ada Penjualan Kanvas terverifikasi sejak '
                .$this->lastActivity->translatedFormat('d F Y').'. Aktifkan kembali melalui Master Salesman bila diperlukan.',
        ];
    }
}
