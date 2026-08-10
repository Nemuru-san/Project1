<div x-data="{ toastMsg: '', toastType: '' }"
    @toast.window="toastMsg = $event.detail.message; toastType = $event.detail.type; setTimeout(() => toastMsg = '', 3000)">

    {{-- TOAST --}}
    <div x-show="toastMsg" x-transition :class="toastType === 'success' ? 'bg-green-500' : 'bg-red-500'"
        class="fixed top-5 right-5 z-50 text-white px-4 py-2 rounded shadow-lg text-sm">
        <span x-text="toastMsg"></span>
    </div>

    {{-- FILTER BAR --}}
    <div class="my-4 flex flex-col items-center justify-between gap-3 md:flex-row dark:bg-zinc-900">
        <h1 class="text-lg">Data Transaksi Delivery Order</h1>
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
                Tambah Delivery Order
            </button>
        </div>
    </div>

    {{-- TABLE --}}
    {{-- <div class="overflow-x-auto dark:border-zinc-700 dark:bg-zinc-900">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400 mt-4">
            <thead class="text-sm font-bold uppercase bg-gray-50 dark:bg-zinc-800 dark:text-white">
                <tr>
                    <th class="px-4 py-4 cursor-pointer select-none" wire:click="sortBy('code')">
                        <div class="flex items-center gap-1">Code
                            @if ($sortField === 'code')
                                <span class="text-xs">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </div>
                    </th>
                    <th class="px-4 py-4 cursor-pointer select-none" wire:click="sortBy('name')">
                        <div class="flex items-center gap-1">Name
                            @if ($sortField === 'name')
                                <span class="text-xs">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </div>
                    </th>
                    <th class="px-4 py-4">Status</th>
                    <th class="px-4 py-4">Aksi</th>
                </tr>
            </thead>
            <tbody class="dark:bg-zinc-950 text-sm">
                @forelse ($units as $unit)
                    <tr
                        class="border-b dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-zinc-800 {{ $unit->trashed() ? 'opacity-50' : '' }}">
                        <td class="px-4 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white uppercase">
                            {{ $unit->code }}</td>
                        <td class="px-4 py-4 uppercase">{{ $unit->name }}</td>
                        <td class="px-4 py-4">
                            @if ($unit->trashed())
                                <span class="text-sm font-normal px-2.5 py-0.5 rounded bg-red-700 text-white">
                                    Terhapus
                                </span>
                            @else
                                <span class="text-sm font-normal px-2.5 py-0.5 rounded bg-green-700 text-white">
                                    Aktif
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-4">
                            <div class="inline-block" x-data="{
                                open: false,
                                top: 0,
                                left: 0,
                                toggle($el) {
                                    const rect = $el.getBoundingClientRect();
                            
                                    this.top = rect.bottom + 6;
                                    this.left = rect.left - 128;
                            
                                    this.open = !this.open;
                                }
                            }">
                                <button @click="toggle($el)" @click.outside="open = false"
                                    class="inline-flex items-center p-0.5 text-md font-medium text-center text-gray-500 hover:text-gray-800 rounded-lg focus:outline-none dark:text-gray-400 dark:hover:text-gray-100 cursor-pointer"
                                    type="button">
                                    <svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                    </svg>
                                </button>
                                <div x-show="open" x-cloak :style="`position: fixed; top: ${top}px; left: ${left}px;`"
                                    class="z-50 w-44 bg-white rounded divide-y divide-gray-100 shadow dark:bg-gray-700 dark:divide-gray-600">
                                    @if ($unit->trashed())
                                        <div class="px-4 py-2 text-sm text-gray-400">
                                            Data sudah terhapus
                                        </div>
                                    @else
                                        <ul class="py-1 text-base text-gray-700 dark:text-gray-200">
                                            <li>
                                                <button wire:click="openEdit({{ $unit->id }})" @click="open = false"
                                                    class="flex items-center gap-2 w-full py-2 px-4 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white cursor-pointer">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>Ubah
                                                </button>
                                            </li>
                                        </ul>

                                        <div class="py-1">
                                            <button wire:click="confirmDelete({{ $unit->id }})" @disabled(! auth()->user()->isSuperAdmin())
                                                @click="open = false"
                                                class="flex items-center gap-2 w-full py-2 px-4 text-base text-gray-700 hover:bg-red-600 hover:text-white dark:text-gray-200 dark:hover:bg-red-600 dark:hover:text-white">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 011 1v3M4 7h16" />
                                                </svg>Hapus
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-8 text-gray-400 dark:text-gray-500">
                            Tidak ada data UOM.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div> --}}

    {{-- PAGINATION --}}
    {{-- <div class="mt-4">
        {{ $units->links() }}
    </div> --}}

    {{-- CREATE / EDIT MODAL --}}
    @if ($showModal)
        <div class="fixed inset-0 z-40 flex items-start justify-center overflow-hidden bg-black/50 backdrop-blur-sm p-4">
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl w-full max-w-full mx-auto max-h-[min(80vh,calc(100dvh-2rem))] flex flex-col overflow-hidden"
                @click.outside="$wire.set('showModal', false)">

                <div
                    class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-zinc-700 shrink-0 bg-zinc-50 dark:bg-zinc-900">
                    <h3 class="text-lg font-semibold dark:text-white">
                        Delivery Order
                    </h3>
                    <button wire:click="$set('showModal', false)"
                        class="text-gray-400 hover:text-white cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-8 py-6">
                    <div class="grid gap-5 sm:grid-cols-2 sm:gap-x-12 sm:gap-y-6">
                        <div>
                            <label for="delivery-order-no" class="mb-3 block text-base font-medium text-gray-900 dark:text-white">DO No</label>
                            <input id="delivery-order-no" type="text" value="Autogenerated" disabled
                                class="block w-full cursor-not-allowed rounded-lg border border-gray-300 bg-gray-100 p-2.5 text-sm text-gray-500 dark:border-gray-600 dark:bg-zinc-700 dark:text-gray-400">
                        </div>

                        <div>
                            <label for="delivery-date" class="mb-3 block text-base font-medium text-gray-900 dark:text-white">Delivery Date</label>
                            <input id="delivery-date" type="date"
                                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                        </div>

                        <div>
                            <label for="sales-order-no" class="mb-3 block text-base font-medium text-gray-900 dark:text-white">No SO</label>
                            <select id="sales-order-no"
                                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                                <option value="">-- Pilih No SO --</option>
                                <option value="SO-001">SO-001</option>
                                <option value="SO-002">SO-002</option>
                            </select>
                        </div>

                        <div>
                            <label for="delivery-order-reference" class="mb-3 block text-base font-medium text-gray-900 dark:text-white">No DO</label>
                            <select id="delivery-order-reference"
                                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                                <option value="">-- Pilih No DO --</option>
                                <option value="DO-001">DO-001</option>
                                <option value="DO-002">DO-002</option>
                            </select>
                        </div>

                        <div>
                            <label for="customer" class="mb-3 block text-base font-medium text-gray-900 dark:text-white">Customer</label>
                            <select id="customer"
                                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                                <option value="">-- Pilih Customer --</option>
                                <option value="customer-1">Customer Contoh 1</option>
                                <option value="customer-2">Customer Contoh 2</option>
                            </select>
                        </div>

                        <div>
                            <label for="delivery-address" class="mb-3 block text-base font-medium text-gray-900 dark:text-white">Delivery Address</label>
                            <select id="delivery-address"
                                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                                <option value="">-- Pilih Alamat Pengiriman --</option>
                                <option value="address-1">Alamat Pengiriman Contoh 1</option>
                                <option value="address-2">Alamat Pengiriman Contoh 2</option>
                            </select>
                        </div>

                        <div>
                            <label for="top" class="mb-3 block text-base font-medium text-gray-900 dark:text-white">TOP</label>
                            <select id="top"
                                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                                <option value="">-- Pilih TOP --</option>
                                <option value="cash">Cash</option>
                                <option value="7-days">7 Hari</option>
                                <option value="30-days">30 Hari</option>
                            </select>
                        </div>

                        <div class="w-full">
                            <label class="mb-3 block text-base font-medium text-gray-900 dark:text-white">Pajak (PPN 11%)</label>
                            <label class="inline-flex cursor-pointer items-center">
                                <span class="select-none text-base font-medium text-gray-600 dark:text-gray-400">Tidak</span>
                                <span class="relative mx-3">
                                    <input type="checkbox" aria-label="Aktifkan pajak" class="peer sr-only">
                                    <span class="block h-5 w-9 rounded-full bg-red-200 after:absolute after:start-0.5 after:top-0.5 after:h-4 after:w-4 after:rounded-full after:bg-red-500 after:transition-all after:content-[''] peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 peer-checked:bg-primary-600 peer-checked:after:translate-x-full peer-checked:after:border-red-500 dark:peer-focus:ring-red-800"></span>
                                </span>
                                <span class="select-none text-base font-medium text-gray-600 dark:text-gray-400">Ya</span>
                            </label>
                        </div>

                        <div class="sm:col-span-2">
                            <label for="notes" class="mb-3 block text-base font-medium text-gray-900 dark:text-white">Notes</label>
                            <textarea id="notes" rows="3" placeholder="Masukkan catatan atau keterangan tambahan..."
                                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-zinc-800 dark:text-white"></textarea>
                        </div>
                    </div>

                    <div class="mt-10">
                        <h4 class="mb-4 text-lg font-bold text-gray-900 dark:text-white">Detail Produk</h4>
                        <div class="overflow-x-auto rounded-xl border border-gray-300 dark:border-zinc-600">
                            <table class="w-full min-w-max border-collapse text-left text-sm text-gray-700 dark:text-gray-200">
                                <thead class="bg-gray-100 text-xs font-bold uppercase text-gray-700 dark:bg-zinc-700 dark:text-white">
                                    <tr>
                                        <th class="whitespace-nowrap border border-gray-300 px-4 py-3 dark:border-zinc-600">Prd Code</th>
                                        <th class="whitespace-nowrap border border-gray-300 px-4 py-3 dark:border-zinc-600">Prd Desc</th>
                                        <th class="whitespace-nowrap border border-gray-300 px-4 py-3 dark:border-zinc-600">Kategori</th>
                                        <th class="whitespace-nowrap border border-gray-300 px-4 py-3 dark:border-zinc-600">UOM</th>
                                        <th class="whitespace-nowrap border border-gray-300 px-4 py-3 text-right dark:border-zinc-600">Qty Order</th>
                                        <th class="whitespace-nowrap border border-gray-300 px-4 py-3 dark:border-zinc-600">Qty Deliver</th>
                                        <th class="whitespace-nowrap border border-gray-300 px-4 py-3 dark:border-zinc-600">Warehouse</th>
                                        <th class="whitespace-nowrap border border-gray-300 px-4 py-3 dark:border-zinc-600">Harga</th>
                                        <th class="whitespace-nowrap border border-gray-300 px-4 py-3 dark:border-zinc-600">Disc</th>
                                        <th class="whitespace-nowrap border border-gray-300 px-4 py-3 dark:border-zinc-600">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-zinc-900">
                                    <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800">
                                        <td class="border border-gray-300 px-4 py-3 font-medium dark:border-zinc-600">PRD-001</td>
                                        <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600">Produk Contoh A</td>
                                        <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600">Produk Jadi</td>
                                        <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600">PCS</td>
                                        <td class="border border-gray-300 px-4 py-3 text-right dark:border-zinc-600">100</td>
                                        <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600"><input type="number" min="0" max="100" placeholder="0" class="w-28 rounded-lg border border-gray-300 bg-gray-50 p-2 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-zinc-800 dark:text-white"></td>
                                        <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600"><select class="w-44 rounded-lg border border-gray-300 bg-gray-50 p-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-zinc-800 dark:text-white"><option value="warehouse-main" selected>Gudang Utama</option><option value="warehouse-branch">Gudang Cabang</option></select></td>
                                        <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600"><input type="text" inputmode="numeric" value="125000" class="w-32 rounded-lg border border-gray-300 bg-gray-50 p-2 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-zinc-800 dark:text-white"></td>
                                        <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600"><input type="text" inputmode="numeric" value="250000" class="w-28 rounded-lg border border-gray-300 bg-gray-50 p-2 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-zinc-800 dark:text-white"></td>
                                        <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600"><input type="text" value="Rp 12.500.000" disabled class="w-36 cursor-not-allowed rounded-lg border border-gray-300 bg-gray-100 p-2 text-sm text-gray-500 dark:border-gray-600 dark:bg-zinc-700 dark:text-gray-400"></td>
                                    </tr>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800">
                                        <td class="border border-gray-300 px-4 py-3 font-medium dark:border-zinc-600">PRD-002</td>
                                        <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600">Produk Contoh B</td>
                                        <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600">Produk Jadi</td>
                                        <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600">BOX</td>
                                        <td class="border border-gray-300 px-4 py-3 text-right dark:border-zinc-600">50</td>
                                        <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600"><input type="number" min="0" max="30" placeholder="0" class="w-28 rounded-lg border border-gray-300 bg-gray-50 p-2 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-zinc-800 dark:text-white"></td>
                                        <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600"><select class="w-44 rounded-lg border border-gray-300 bg-gray-50 p-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-zinc-800 dark:text-white"><option value="warehouse-main">Gudang Utama</option><option value="warehouse-branch" selected>Gudang Cabang</option></select></td>
                                        <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600"><input type="text" inputmode="numeric" value="85000" class="w-32 rounded-lg border border-gray-300 bg-gray-50 p-2 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-zinc-800 dark:text-white"></td>
                                        <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600"><input type="text" inputmode="numeric" value="0" class="w-28 rounded-lg border border-gray-300 bg-gray-50 p-2 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-zinc-800 dark:text-white"></td>
                                        <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600"><input type="text" value="Rp 4.250.000" disabled class="w-36 cursor-not-allowed rounded-lg border border-gray-300 bg-gray-100 p-2 text-sm text-gray-500 dark:border-gray-600 dark:bg-zinc-700 dark:text-gray-400"></td>
                                    </tr>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800">
                                        <td class="border border-gray-300 px-4 py-3 font-medium dark:border-zinc-600">PRD-003</td>
                                        <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600">Produk Contoh C</td>
                                        <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600">Aksesoris</td>
                                        <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600">PCS</td>
                                        <td class="border border-gray-300 px-4 py-3 text-right dark:border-zinc-600">24</td>
                                        <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600"><input type="number" min="0" max="12" placeholder="0" class="w-28 rounded-lg border border-gray-300 bg-gray-50 p-2 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-zinc-800 dark:text-white"></td>
                                        <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600"><select class="w-44 rounded-lg border border-gray-300 bg-gray-50 p-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-zinc-800 dark:text-white"><option value="warehouse-main" selected>Gudang Utama</option><option value="warehouse-branch">Gudang Cabang</option></select></td>
                                        <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600"><input type="text" inputmode="numeric" value="50000" class="w-32 rounded-lg border border-gray-300 bg-gray-50 p-2 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-zinc-800 dark:text-white"></td>
                                        <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600"><input type="text" inputmode="numeric" value="0" class="w-28 rounded-lg border border-gray-300 bg-gray-50 p-2 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-zinc-800 dark:text-white"></td>
                                        <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600"><input type="text" value="Rp 1.200.000" disabled class="w-36 cursor-not-allowed rounded-lg border border-gray-300 bg-gray-100 p-2 text-sm text-gray-500 dark:border-gray-600 dark:bg-zinc-700 dark:text-gray-400"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-6 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                            <h4 class="mb-4 font-bold text-gray-900 dark:text-white">Detail Harga</h4>
                            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Bruto</label>
                                    <input type="text" value="Rp 17.950.000" disabled class="block w-full cursor-not-allowed rounded-lg border border-gray-300 bg-gray-100 p-2.5 text-sm text-gray-500 dark:border-gray-600 dark:bg-zinc-700 dark:text-gray-400">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Total Diskon</label>
                                    <input type="text" value="Rp 250.000" disabled class="block w-full cursor-not-allowed rounded-lg border border-gray-300 bg-gray-100 p-2.5 text-sm text-gray-500 dark:border-gray-600 dark:bg-zinc-700 dark:text-gray-400">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">PPN (11%)</label>
                                    <input type="text" value="Rp 1.947.000" disabled class="block w-full cursor-not-allowed rounded-lg border border-gray-300 bg-gray-100 p-2.5 text-sm text-gray-500 dark:border-gray-600 dark:bg-zinc-700 dark:text-gray-400">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Neto</label>
                                    <input type="text" value="Rp 19.647.000" disabled class="block w-full cursor-not-allowed rounded-lg border border-gray-300 bg-gray-100 p-2.5 text-sm text-gray-500 dark:border-gray-600 dark:bg-zinc-700 dark:text-gray-400">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="flex justify-end gap-2 p-6 border-t border-gray-200 dark:border-zinc-700 shrink-0 bg-white dark:bg-zinc-900">
                    <button wire:click="$set('showModal', false)"
                        class="px-4 py-2 text-sm rounded-lg border border-gray-600 dark:text-gray-300 hover:bg-zinc-700">
                        Batal
                    </button>
                    <button type="button"
                        class="px-4 py-2 text-sm rounded-lg bg-blue-600 hover:bg-blue-700 text-white cursor-pointer">
                        Simpan
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- DELETE CONFIRM MODAL --}}
    {{-- @if ($showDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl w-full max-w-sm p-6">
                <h3 class="text-lg font-semibold dark:text-white mb-2">Konfirmasi Hapus</h3>
                <p class="text-sm text-gray-400 mb-6">Yakin ingin menghapus data ini?</p>
                <div class="flex justify-end gap-2">
                    <button wire:click="$set('showDeleteModal', false)"
                        class="px-4 py-2 text-sm rounded-lg border border-gray-600 dark:text-gray-300 hover:bg-zinc-700">
                        Batal
                    </button>
                    <button wire:click="delete" wire:loading.attr="disabled" @disabled(! auth()->user()->isSuperAdmin())
                        class="px-4 py-2 text-sm rounded-lg bg-red-600 hover:bg-red-700 text-white disabled:opacity-50">
                        <span wire:loading.remove wire:target="delete">Hapus</span>
                        <span wire:loading wire:target="delete">Menghapus...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif --}}

</div>
