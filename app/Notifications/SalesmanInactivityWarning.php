<?php

namespace App\Notifications;

use App\Models\Salesman;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SalesmanInactivityWarning extends Notification
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
        $isSelf = $notifiable instanceof User && $notifiable->id === $this->salesman->user_id;
        $days = (int) now()->startOfDay()->diffInDays($this->deadline, false);

        return (new MailMessage)
            ->subject('Peringatan: Salesman '.$this->salesman->name.' akan dinonaktifkan')
            ->greeting('Halo '.$notifiable->name.',')
            ->line($isSelf
                ? "Akun Anda akan dinonaktifkan pada {$this->deadline->translatedFormat('d F Y')} ({$days} hari lagi) karena tidak ada Penjualan Kanvas terverifikasi sejak {$this->lastActivity->translatedFormat('d F Y')}."
                : "Salesman {$this->salesman->name} ({$this->salesman->code}) akan dinonaktifkan pada {$this->deadline->translatedFormat('d F Y')} ({$days} hari lagi) karena tidak ada Penjualan Kanvas terverifikasi sejak {$this->lastActivity->translatedFormat('d F Y')}.")
            ->line('Buat dan konfirmasi Penjualan Kanvas sebelum tanggal tersebut agar akun tetap aktif.');
    }

    public function toArray(object $notifiable): array
    {
        $isSelf = $notifiable instanceof User && $notifiable->id === $this->salesman->user_id;

        return [
            'type' => 'salesman_inactivity_warning',
            'salesman_id' => $this->salesman->id,
            'salesman_name' => $this->salesman->name,
            'salesman_code' => $this->salesman->code,
            'last_activity' => $this->lastActivity->toDateString(),
            'deadline' => $this->deadline->toDateString(),
            'title' => $isSelf
                ? 'Akun Anda akan dinonaktifkan'
                : 'Salesman '.$this->salesman->name.' akan dinonaktifkan',
            'message' => ($isSelf ? 'Akun Anda' : 'Salesman '.$this->salesman->name)
                .' akan dinonaktifkan pada '.$this->deadline->translatedFormat('d F Y')
                .' karena tidak ada Penjualan Kanvas terverifikasi sejak '.$this->lastActivity->translatedFormat('d F Y').'.',
        ];
    }
}
