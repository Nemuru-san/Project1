<div x-data="{ toastMsg: '', toastType: '' }"
    @toast.window="toastMsg = $event.detail.message; toastType = $event.detail.type; setTimeout(() => toastMsg = '', 3000)">
    <div x-cloak x-show="toastMsg" x-transition
        :class="toastType === 'success' ? 'bg-green-600' : 'bg-red-600'"
        class="fixed right-5 top-5 z-[70] rounded-lg px-4 py-2 text-sm text-white shadow-lg">
        <span x-text="toastMsg"></span>
    </div>

    <div class="my-4 flex flex-col items-center justify-between gap-3 md:flex-row dark:bg-zinc-900">
        <p class="text-base font-semibold dark:text-white">Data Customer</p>

        <div class="flex w-full flex-col items-center gap-3 sm:flex-row md:w-auto">
            <div class="relative w-full sm:w-72">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <input wire:model.live.debounce.300ms="search" type="text"
                    class="block w-full rounded-lg border border-gray-300 p-2.5 pl-10 text-sm dark:border-gray-600 dark:bg-zinc-800 dark:text-white"
                    placeholder="Cari kode, nama, telepon, email...">
            </div>

            <select wire:model.live="perPage"
                class="w-full rounded-lg border border-gray-300 px-8 py-2.5 text-sm sm:w-auto dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                <option value="10">10 / hal</option>
                <option value="25">25 / hal</option>
                <option value="50">50 / hal</option>
            </select>

            <label class="flex cursor-pointer items-center gap-2 whitespace-nowrap text-sm dark:text-gray-300">
                <input type="checkbox" wire:model.live="showTrashed"
                    class="h-4 w-4 rounded border-gray-600 text-blue-600 dark:bg-zinc-800">
                Tampilkan terhapus
            </label>

            <button type="button" wire:click="openCreate"
                class="inline-flex w-full cursor-pointer items-center justify-center gap-2 whitespace-nowrap rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700 sm:w-auto">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Customer
            </button>
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-zinc-700">
        <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
            <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-zinc-800 dark:text-gray-200">
                <tr>
                    <th class="cursor-pointer px-4 py-3" wire:click="sortBy('code')">Kode</th>
                    <th class="cursor-pointer px-4 py-3" wire:click="sortBy('name')">Customer</th>
                    <th class="px-4 py-3">Kontak</th>
                    <th class="px-4 py-3 text-center">PIC</th>
                    <th class="px-4 py-3 text-center">Alamat</th>
                    <th class="cursor-pointer px-4 py-3 text-center" wire:click="sortBy('is_active')">Status</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700 dark:bg-zinc-900">
                @forelse ($customers as $customer)
                    <tr wire:key="customer-{{ $customer->id }}" class="hover:bg-gray-50 dark:hover:bg-zinc-800 {{ $customer->trashed() ? 'opacity-60' : '' }}">
                        <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $customer->code }}</td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900 dark:text-white">{{ $customer->name }}</div>
                            @if ($customer->tax_number)
                                <div class="text-xs text-gray-500">NPWP: {{ $customer->tax_number }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div>{{ $customer->phone ?: '-' }}</div>
                            <div class="text-xs text-gray-500">{{ $customer->email ?: '-' }}</div>
                        </td>
                        <td class="px-4 py-3 text-center">{{ $customer->pics_count }}</td>
                        <td class="px-4 py-3 text-center">{{ $customer->addresses_count }}</td>
                        <td class="px-4 py-3 text-center">
                            @if ($customer->trashed())
                                <span class="rounded bg-red-700 px-2.5 py-1 text-xs text-white">Terhapus</span>
                            @elseif ($customer->is_active)
                                <span class="rounded bg-green-700 px-2.5 py-1 text-xs text-white">Aktif</span>
                            @else
                                <span class="rounded bg-gray-600 px-2.5 py-1 text-xs text-white">Nonaktif</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            @if ($customer->trashed())
                                <button type="button" wire:click="restore({{ $customer->id }})"
                                    class="cursor-pointer font-medium text-green-600 hover:underline">Pulihkan</button>
                            @else
                                <button type="button" wire:click="openEdit({{ $customer->id }})"
                                    class="mr-3 cursor-pointer font-medium text-blue-600 hover:underline">Edit</button>
                                <button type="button" wire:click="confirmDelete({{ $customer->id }})"
                                    class="cursor-pointer font-medium text-red-600 hover:underline">Hapus</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-gray-500">Belum ada data customer.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $customers->links() }}</div>

    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm">
            <div class="flex max-h-[92vh] w-full max-w-7xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-zinc-900">
                <div class="flex shrink-0 items-center justify-between border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-zinc-700 dark:bg-zinc-800">
                    <div>
                        <h3 class="text-lg font-semibold dark:text-white">{{ $editingId ? 'Edit Customer' : 'Tambah Customer' }}</h3>
                        <p class="text-sm text-gray-500">Isi data customer, PIC, dan alamat dalam satu form.</p>
                    </div>
                    <button type="button" wire:click="$set('showModal', false)" class="cursor-pointer text-gray-500 hover:text-gray-800 dark:hover:text-white">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="flex-1 space-y-6 overflow-y-auto px-6 py-5">
                    <section class="rounded-xl border border-gray-200 p-4 dark:border-zinc-700">
                        <h4 class="mb-4 font-semibold text-gray-900 dark:text-white">Data Customer</h4>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                            <div>
                                <label class="mb-1 block text-sm font-medium dark:text-white">Kode <span class="text-red-500">*</span></label>
                                <input wire:model="code" type="text" class="w-full rounded-lg border p-2.5 text-sm uppercase dark:border-gray-600 dark:bg-zinc-800 dark:text-white" placeholder="CUST-001">
                                @error('code') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div class="lg:col-span-2">
                                <label class="mb-1 block text-sm font-medium dark:text-white">Nama Customer <span class="text-red-500">*</span></label>
                                <input wire:model="name" type="text" class="w-full rounded-lg border p-2.5 text-sm dark:border-gray-600 dark:bg-zinc-800 dark:text-white" placeholder="PT Contoh Indonesia">
                                @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium dark:text-white">Telepon</label>
                                <input wire:model="phone" type="text" class="w-full rounded-lg border p-2.5 text-sm dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                                @error('phone') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium dark:text-white">Email</label>
                                <input wire:model="email" type="email" class="w-full rounded-lg border p-2.5 text-sm dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                                @error('email') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium dark:text-white">NPWP</label>
                                <input wire:model="tax_number" type="text" class="w-full rounded-lg border p-2.5 text-sm dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                                @error('tax_number') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div class="md:col-span-2 lg:col-span-3">
                                <label class="mb-1 block text-sm font-medium dark:text-white">Catatan</label>
                                <textarea wire:model="notes" rows="2" class="w-full rounded-lg border p-2.5 text-sm dark:border-gray-600 dark:bg-zinc-800 dark:text-white"></textarea>
                                @error('notes') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <label class="flex cursor-pointer items-center gap-2 text-sm dark:text-gray-300">
                                <input wire:model="is_active" type="checkbox" class="h-4 w-4 rounded text-blue-600">
                                Customer aktif
                            </label>
                        </div>
                    </section>

                    <section class="rounded-xl border border-gray-200 p-4 dark:border-zinc-700">
                        <div class="mb-4 flex items-center justify-between">
                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-white">PIC Customer</h4>
                                <p class="text-sm text-gray-500">Tambahkan satu atau lebih orang yang dapat dihubungi.</p>
                            </div>
                            <button type="button" wire:click="addPic" class="cursor-pointer rounded-lg bg-blue-600 px-3 py-2 text-sm text-white hover:bg-blue-700">+ Tambah PIC</button>
                        </div>
                        @error('pics') <p class="mb-3 text-xs text-red-500">{{ $message }}</p> @enderror

                        <div class="space-y-4">
                            @foreach ($pics as $index => $pic)
                                <div wire:key="pic-{{ $pic['id'] ?? 'new' }}-{{ $index }}" class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
                                    <div class="mb-3 flex items-center justify-between">
                                        <label class="flex cursor-pointer items-center gap-2 text-sm font-medium dark:text-white">
                                            <input type="radio" name="primary_pic" wire:click="setPrimaryPic({{ $index }})" @checked($pic['is_primary'])>
                                            PIC Utama
                                        </label>
                                        <button type="button" wire:click="removePic({{ $index }})" @disabled(count($pics) === 1)
                                            class="cursor-pointer text-sm text-red-600 hover:underline disabled:cursor-not-allowed disabled:opacity-40">Hapus PIC</button>
                                    </div>
                                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-4">
                                        <div>
                                            <label class="mb-1 block text-xs dark:text-gray-300">Nama <span class="text-red-500">*</span></label>
                                            <input wire:model="pics.{{ $index }}.name" type="text" class="w-full rounded-lg border p-2.5 text-sm dark:border-gray-600 dark:bg-zinc-900 dark:text-white">
                                            @error("pics.$index.name") <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs dark:text-gray-300">Jabatan</label>
                                            <input wire:model="pics.{{ $index }}.position" type="text" class="w-full rounded-lg border p-2.5 text-sm dark:border-gray-600 dark:bg-zinc-900 dark:text-white">
                                            @error("pics.$index.position") <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs dark:text-gray-300">Telepon</label>
                                            <input wire:model="pics.{{ $index }}.phone" type="text" class="w-full rounded-lg border p-2.5 text-sm dark:border-gray-600 dark:bg-zinc-900 dark:text-white">
                                            @error("pics.$index.phone") <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs dark:text-gray-300">Email</label>
                                            <input wire:model="pics.{{ $index }}.email" type="email" class="w-full rounded-lg border p-2.5 text-sm dark:border-gray-600 dark:bg-zinc-900 dark:text-white">
                                            @error("pics.$index.email") <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <section class="rounded-xl border border-gray-200 p-4 dark:border-zinc-700">
                        <div class="mb-4 flex items-center justify-between">
                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-white">Alamat Customer</h4>
                                <p class="text-sm text-gray-500">Tambahkan alamat penagihan dan/atau pengiriman.</p>
                            </div>
                            <button type="button" wire:click="addAddress" class="cursor-pointer rounded-lg bg-blue-600 px-3 py-2 text-sm text-white hover:bg-blue-700">+ Tambah Alamat</button>
                        </div>
                        @error('addresses') <p class="mb-3 text-xs text-red-500">{{ $message }}</p> @enderror

                        <div class="space-y-4">
                            @foreach ($addresses as $index => $address)
                                <div wire:key="address-{{ $address['id'] ?? 'new' }}-{{ $index }}" class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
                                    <div class="mb-3 flex items-center justify-between">
                                        <label class="flex cursor-pointer items-center gap-2 text-sm font-medium dark:text-white">
                                            <input type="radio" name="primary_address" wire:click="setPrimaryAddress({{ $index }})" @checked($address['is_primary'])>
                                            Alamat Utama
                                        </label>
                                        <button type="button" wire:click="removeAddress({{ $index }})" @disabled(count($addresses) === 1)
                                            class="cursor-pointer text-sm text-red-600 hover:underline disabled:cursor-not-allowed disabled:opacity-40">Hapus Alamat</button>
                                    </div>
                                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-4">
                                        <div>
                                            <label class="mb-1 block text-xs dark:text-gray-300">Kode <span class="text-red-500">*</span></label>
                                            <input wire:model="addresses.{{ $index }}.code" type="text" class="w-full rounded-lg border p-2.5 text-sm uppercase dark:border-gray-600 dark:bg-zinc-900 dark:text-white" placeholder="ADDR-01">
                                            @error("addresses.$index.code") <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs dark:text-gray-300">Label <span class="text-red-500">*</span></label>
                                            <input wire:model="addresses.{{ $index }}.label" type="text" class="w-full rounded-lg border p-2.5 text-sm dark:border-gray-600 dark:bg-zinc-900 dark:text-white" placeholder="Kantor Pusat">
                                            @error("addresses.$index.label") <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs dark:text-gray-300">Tipe <span class="text-red-500">*</span></label>
                                            <select wire:model="addresses.{{ $index }}.address_type" class="w-full rounded-lg border p-2.5 text-sm dark:border-gray-600 dark:bg-zinc-900 dark:text-white">
                                                <option value="both">Penagihan & Pengiriman</option>
                                                <option value="billing">Penagihan</option>
                                                <option value="shipping">Pengiriman</option>
                                            </select>
                                            @error("addresses.$index.address_type") <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs dark:text-gray-300">Negara <span class="text-red-500">*</span></label>
                                            <input wire:model="addresses.{{ $index }}.country" type="text" class="w-full rounded-lg border p-2.5 text-sm dark:border-gray-600 dark:bg-zinc-900 dark:text-white">
                                            @error("addresses.$index.country") <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs dark:text-gray-300">Provinsi</label>
                                            <input wire:model="addresses.{{ $index }}.province" type="text" class="w-full rounded-lg border p-2.5 text-sm dark:border-gray-600 dark:bg-zinc-900 dark:text-white">
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs dark:text-gray-300">Kota/Kabupaten</label>
                                            <input wire:model="addresses.{{ $index }}.city" type="text" class="w-full rounded-lg border p-2.5 text-sm dark:border-gray-600 dark:bg-zinc-900 dark:text-white">
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs dark:text-gray-300">Kecamatan</label>
                                            <input wire:model="addresses.{{ $index }}.district" type="text" class="w-full rounded-lg border p-2.5 text-sm dark:border-gray-600 dark:bg-zinc-900 dark:text-white">
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs dark:text-gray-300">Kode Pos</label>
                                            <input wire:model="addresses.{{ $index }}.postal_code" type="text" class="w-full rounded-lg border p-2.5 text-sm dark:border-gray-600 dark:bg-zinc-900 dark:text-white">
                                        </div>
                                        <div class="md:col-span-2 lg:col-span-4">
                                            <label class="mb-1 block text-xs dark:text-gray-300">Alamat Lengkap <span class="text-red-500">*</span></label>
                                            <textarea wire:model="addresses.{{ $index }}.address" rows="2" class="w-full rounded-lg border p-2.5 text-sm dark:border-gray-600 dark:bg-zinc-900 dark:text-white"></textarea>
                                            @error("addresses.$index.address") <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                </div>

                <div class="flex shrink-0 justify-end gap-2 border-t border-gray-200 bg-gray-50 p-5 dark:border-zinc-700 dark:bg-zinc-800">
                    <button type="button" wire:click="$set('showModal', false)" class="cursor-pointer rounded-lg border border-gray-300 px-4 py-2 text-sm dark:border-gray-600 dark:text-gray-200">Batal</button>
                    <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save"
                        class="cursor-pointer rounded-lg bg-blue-600 px-5 py-2 text-sm text-white hover:bg-blue-700 disabled:opacity-50">
                        <span wire:loading.remove wire:target="save">Simpan Customer</span>
                        <span wire:loading wire:target="save">Menyimpan...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if ($showDeleteModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-zinc-900">
                <h3 class="mb-2 text-lg font-semibold dark:text-white">Hapus Customer</h3>
                <p class="mb-6 text-sm text-gray-500">Customer akan dinonaktifkan dengan soft delete dan dapat dipulihkan kembali.</p>
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="$set('showDeleteModal', false)" class="cursor-pointer rounded-lg border px-4 py-2 text-sm dark:border-gray-600 dark:text-white">Batal</button>
                    <button type="button" wire:click="delete" wire:loading.attr="disabled" class="cursor-pointer rounded-lg bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700 disabled:opacity-50">Hapus</button>
                </div>
            </div>
        </div>
    @endif
</div>
