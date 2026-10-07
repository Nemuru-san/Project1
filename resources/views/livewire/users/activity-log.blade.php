<div>
    <x-filter.card title="Filter Log Aktivitas" description="Riwayat siapa membuat, mengubah, menghapus, atau memulihkan data, beserta nilai sebelum dan sesudahnya.">
        <x-filter.search placeholder="Cari nomor dokumen atau nama data..." />
        <x-filter.select model="userFilter" label="Pengguna">
            <option value="">Semua pengguna</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}">{{ $user->name }}</option>
            @endforeach
        </x-filter.select>
        <x-filter.select model="subjectFilter" label="Jenis data">
            <option value="">Semua data</option>
            @foreach ($subjectLabels as $type => $label)
                <option value="{{ $type }}">{{ $label }}</option>
            @endforeach
        </x-filter.select>
        <x-filter.select model="eventFilter" label="Aksi">
            <option value="">Semua aksi</option>
            @foreach (\App\Models\ActivityLog::EVENT_LABELS as $event => $label)
                <option value="{{ $event }}">{{ $label }}</option>
            @endforeach
        </x-filter.select>
        <x-filter.date-range />
        <x-filter.per-page :options="[25, 50, 100]" :show-reset="filled($search) || filled($userFilter) || filled($subjectFilter) || filled($eventFilter)" />
    </x-filter.card>

    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-zinc-700">
        <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
            <thead class="bg-gray-50 text-xs font-semibold uppercase text-gray-700 dark:bg-zinc-800 dark:text-gray-200">
                <tr>
                    <th class="px-4 py-3">Waktu</th>
                    <th class="px-4 py-3">Pengguna</th>
                    <th class="px-4 py-3">Aksi</th>
                    <th class="px-4 py-3">Jenis Data</th>
                    <th class="px-4 py-3">Dokumen / Data</th>
                    <th class="px-4 py-3">Perubahan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700 dark:bg-zinc-900">
                @forelse ($logs as $log)
                    <tr wire:key="log-{{ $log->id }}" wire:click="openDetail({{ $log->id }})" class="cursor-pointer hover:bg-gray-50 dark:hover:bg-zinc-800">
                        <td class="whitespace-nowrap px-4 py-3">{{ $log->created_at?->timezone(config('session.weekly_reset_timezone'))->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">{{ $log->user?->name ?? 'Sistem' }}</td>
                        <td class="px-4 py-3">
                            @php $eventClass = ['created' => 'bg-green-100 text-green-700', 'updated' => 'bg-blue-100 text-blue-700', 'deleted' => 'bg-red-100 text-red-700'][$log->event] ?? 'bg-gray-200 text-gray-700'; @endphp
                            <span class="rounded-full px-2.5 py-1 text-xs {{ $eventClass }}">{{ \App\Models\ActivityLog::EVENT_LABELS[$log->event] ?? $log->event }}</span>
                        </td>
                        <td class="px-4 py-3">{{ $subjectLabels[$log->subject_type] ?? $log->subject_type }}</td>
                        <td class="px-4 py-3 font-mono text-gray-900 dark:text-white">{{ $log->subject_label ?? '#'.$log->subject_id }}</td>
                        <td class="px-4 py-3 text-xs text-gray-500">
                            @if ($log->event === 'updated')
                                {{ collect($log->changes)->keys()->take(4)->implode(', ') }}{{ count($log->changes ?? []) > 4 ? ', …' : '' }}
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400">Belum ada aktivitas pada filter ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $logs->links() }}</div>

    @if ($selectedLog)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-hidden bg-black/60 p-4 backdrop-blur-sm">
            <div class="flex max-h-[min(85vh,calc(100dvh-2rem))] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-zinc-900">
                <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-zinc-700">
                    <div>
                        <h3 class="text-lg font-semibold dark:text-white">
                            {{ \App\Models\ActivityLog::EVENT_LABELS[$selectedLog->event] ?? $selectedLog->event }}
                            {{ $subjectLabels[$selectedLog->subject_type] ?? $selectedLog->subject_type }}
                        </h3>
                        <p class="mt-0.5 text-sm text-gray-400">
                            {{ $selectedLog->subject_label ?? '#'.$selectedLog->subject_id }} · {{ $selectedLog->user?->name ?? 'Sistem' }} ·
                            {{ $selectedLog->created_at?->timezone(config('session.weekly_reset_timezone'))->format('d/m/Y H:i:s') }} · IP {{ $selectedLog->ip_address ?? '-' }}
                        </p>
                    </div>
                    <button wire:click="closeDetail" type="button" class="cursor-pointer text-gray-400 hover:text-white"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg></button>
                </div>
                <div class="min-h-0 flex-1 overflow-y-auto p-6">
                    @if (empty($selectedLog->changes))
                        <p class="text-sm text-gray-400">Tidak ada rincian perubahan untuk aksi ini.</p>
                    @else
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-xs uppercase dark:bg-zinc-800">
                                <tr>
                                    <th class="px-3 py-2 text-left">Kolom</th>
                                    @if ($selectedLog->event === 'updated')
                                        <th class="px-3 py-2 text-left">Sebelum</th>
                                        <th class="px-3 py-2 text-left">Sesudah</th>
                                    @else
                                        <th class="px-3 py-2 text-left">Nilai</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                                @foreach ($selectedLog->changes as $column => $value)
                                    <tr>
                                        <td class="px-3 py-2 font-mono text-xs dark:text-white">{{ $column }}</td>
                                        @if ($selectedLog->event === 'updated')
                                            <td class="break-all px-3 py-2 text-red-600">{{ is_scalar($value['old'] ?? null) ? $value['old'] : json_encode($value['old'] ?? null) }}</td>
                                            <td class="break-all px-3 py-2 text-green-600">{{ is_scalar($value['new'] ?? null) ? $value['new'] : json_encode($value['new'] ?? null) }}</td>
                                        @else
                                            <td class="break-all px-3 py-2 dark:text-gray-200">{{ is_scalar($value) ? $value : json_encode($value) }}</td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
                <div class="flex justify-end border-t px-6 py-4 dark:border-zinc-700">
                    <button wire:click="closeDetail" type="button" class="cursor-pointer rounded-lg bg-zinc-700 px-5 py-2 text-sm font-medium text-white hover:bg-zinc-600">Tutup</button>
                </div>
            </div>
        </div>
    @endif
</div>
