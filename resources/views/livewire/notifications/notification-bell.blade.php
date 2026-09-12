<div wire:poll.60s>
    <flux:dropdown position="top" align="start">
        <button type="button"
            class="relative flex w-full items-center gap-2 rounded-lg px-2 py-1.5 text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800">
            <flux:icon.bell class="size-5" />
            <span class="in-data-flux-sidebar-collapsed-desktop:hidden">Notifikasi</span>
            @if ($unreadCount > 0)
                <span
                    class="absolute left-5 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-semibold text-white">
                    {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                </span>
            @endif
        </button>

        <flux:menu class="w-80 max-w-[90vw]">
            <div class="flex items-center justify-between px-2 py-1.5">
                <flux:heading size="sm">Notifikasi</flux:heading>
                @if ($unreadCount > 0)
                    <button type="button" wire:click="markAllAsRead"
                        class="text-xs text-blue-600 hover:underline dark:text-blue-400">
                        Tandai semua dibaca
                    </button>
                @endif
            </div>

            <flux:menu.separator />

            <div class="max-h-96 overflow-y-auto">
                @forelse ($notifications as $notification)
                    <div wire:key="notif-{{ $notification->id }}"
                        class="border-b border-zinc-100 px-2 py-2 last:border-b-0 dark:border-zinc-800 {{ $notification->read_at ? 'opacity-60' : 'bg-blue-50/60 dark:bg-blue-950/30' }}">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $notification->data['title'] ?? 'Notifikasi' }}
                                </p>
                                <p class="mt-0.5 text-xs text-zinc-600 dark:text-zinc-400">
                                    {{ $notification->data['message'] ?? '' }}
                                </p>
                                <p class="mt-1 text-[11px] text-zinc-400">
                                    {{ $notification->created_at->diffForHumans() }}
                                </p>
                            </div>
                            @unless ($notification->read_at)
                                <button type="button" wire:click="markAsRead('{{ $notification->id }}')"
                                    title="Tandai dibaca"
                                    class="mt-0.5 shrink-0 text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200">
                                    <flux:icon.check class="size-4" />
                                </button>
                            @endunless
                        </div>
                    </div>
                @empty
                    <p class="px-2 py-4 text-center text-sm text-zinc-500">Belum ada notifikasi.</p>
                @endforelse
            </div>
        </flux:menu>
    </flux:dropdown>
</div>
