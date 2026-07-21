<div x-data="{ toastMsg: '', toastType: '' }"
    @toast.window="toastMsg = $event.detail.message; toastType = $event.detail.type; setTimeout(() => toastMsg = '', 3000)">

    {{-- TOAST --}}
    <div x-show="toastMsg" x-transition :class="toastType === 'success' ? 'bg-green-500' : 'bg-red-500'"
        class="fixed top-5 right-5 z-50 text-white px-4 py-2 rounded shadow-lg text-sm">
        <span x-text="toastMsg"></span>
    </div>

    {{-- FILTER BAR --}}
    <div
        class="flex flex-col md:flex-row items-center justify-between space-y-3 md:space-y-0 md:space-x-4 my-4 dark:bg-zinc-900">
        <h1 class="text-lg">Data Tabel Master Supplier</h1>
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 w-full md:w-auto">
            {{-- Search --}}
            <div class="relative w-full sm:w-72">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <svg aria-hidden="true" class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <input wire:model.live.debounce.300ms="search" type="text"
                    class="dark:bg-zinc-800 border border-gray-600 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-2.5 placeholder-gray-400"
                    placeholder="Cari kode, nama, kontak..." />
            </div>

            {{-- Per Page --}}
            <select wire:model.live="perPage"
                class="dark:bg-zinc-800 border border-gray-600 dark:text-white text-sm rounded-lg px-8 py-2.5 w-full sm:w-auto">
                <option value="10">10 / hal</option>
                <option value="25">25 / hal</option>
                <option value="50">50 / hal</option>
            </select>
            {{-- Show Trashed --}}
            <label class="flex items-center gap-2 text-sm dark:text-gray-300 cursor-pointer whitespace-nowrap">
                <input type="checkbox" wire:model.live="showTrashed"
                    class="w-4 h-4 rounded border-gray-600 dark:bg-zinc-800 text-blue-600">
                Tampilkan Terhapus
            </label>
            {{-- Export --}}
            {{-- <button wire:click="export"
                class="inline-flex items-center gap-2 text-white bg-indigo-600 hover:bg-indigo-700 border border-transparent text-sm font-medium px-4 py-2.5 rounded-lg whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1M12 12v6m0 0l-3-3m3 3l3-3M12 3v9" />
                </svg>
                Export CSV
            </button> --}}
            {{-- Tambah --}}
            <button wire:click="openCreate"
                class="inline-flex items-center gap-2 text-white bg-blue-600 hover:bg-blue-700 border border-transparent text-sm font-medium px-4 py-2.5 rounded-lg whitespace-nowrap cursor-pointer sm:ml-auto sm:w-auto w-full justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Pemasok
            </button>
        </div>
    </div>

    {{-- TABLE --}}
    {{-- <div class="overflow-x-auto dark:border-zinc-700 dark:bg-zinc-900">
        <table class="w-full text-base text-left text-gray-500 dark:text-gray-400 mt-4">
            <thead class="text-lg font-bold uppercase bg-gray-50 dark:bg-zinc-800 dark:text-white">
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
<th class="px-4 py-4">Alamat</th>
<th class="px-4 py-4">Kontak</th>
<th class="px-4 py-4">Status</th>
<th class="px-4 py-4">Dibuat Oleh</th>
<th class="px-4 py-4">Aksi</th>
</tr>
</thead>
<tbody class="dark:bg-zinc-950 text-base">
    @forelse ($suppliers as $supplier)
    <tr
        class="border-b dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-zinc-800 {{ $supplier->trashed() ? 'opacity-60' : '' }}">
        <th scope="row"
            class="px-4 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white uppercase">
            {{ $supplier->code }}
        </th>
        <td class="px-4 py-4 uppercase">{{ $supplier->name }}</td>
        <td class="px-4 py-4 max-w-xs truncate">{{ $supplier->address }}</td>
        <td class="px-4 py-4">{{ $supplier->contact }}</td>
        <td class="px-4 py-4">
            @if ($supplier->trashed())
            <span class="text-sm font-normal px-2.5 py-0.5 rounded bg-red-700 text-white">
                Terhapus
            </span>
            @else
            <span class="text-sm font-normal px-2.5 py-0.5 rounded bg-green-700 text-white">
                Aktif
            </span>
            @endif
        </td>
        <td class="px-4 py-4">{{ $supplier->created_by }}</td>
        <td class="px-4 py-4">
            @if ($supplier->trashed())
            <div class="px-4 py-2 text-sm text-gray-400">
                Data sudah terhapus
            </div>
            @else
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

                <div x-show="open" x-cloak
                    :style="`position: fixed; top: ${top}px; left: ${left}px;`"
                    class="z-50 w-44 bg-white rounded divide-y divide-gray-100 shadow dark:bg-gray-700 dark:divide-gray-600">
                    <ul class="py-1 text-base text-gray-700 dark:text-gray-200">
                        <li>
                            <button wire:click="openEdit({{ $supplier->id }})" @click="open = false"
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
                        <button wire:click="confirmDelete({{ $supplier->id }})"
                            @click="open = false"
                            class="flex items-center gap-2 w-full py-2 px-4 text-base text-gray-700 hover:bg-red-600 hover:text-white dark:text-gray-200 dark:hover:bg-red-600 dark:hover:text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>Hapus
                        </button>
                    </div>
                </div>
            </div>
            @endif
        </td>
    </tr>
    @empty
    <tr>
        <td colspan="7" class="text-center py-8 text-gray-400 dark:text-gray-500">
            Tidak ada data supplier.
        </td>
    </tr>
    @endforelse
</tbody>
</table>
</div> --}}

{{-- PAGINATION --}}
{{-- <div class="mt-4">
        {{ $suppliers->links() }}
</div> --}}

{{-- MODAL CREATE/EDIT --}}
@if ($showModal)
<div class="fixed inset-0 z-40 flex items-center justify-center overflow-hidden bg-black/50 p-4 backdrop-blur-sm">
    <div class="mx-auto flex w-full max-w-5xl h-[85vh] max-h-[750px] flex-col overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-zinc-800"
        x-data="{ 
            showAddProductModal: false, 
            items: [{ code: 'PRD-001', product: 'SEDOTAN STERIL HITAM 6MM', category: 'Sedotan', warehouse_id: '', unit: 'PCS', qty: 1, price: 0, disc: 0 }], 
            addItem(productCode, productName, category, unit, qty, disc) { 
                this.items.push({ code: productCode, product: productName, category: category, warehouse_id: '', unit: unit, qty: qty, price: 0, disc: disc }); 
                this.showAddProductModal = false; 
            }, 
            removeItem(index) { 
                this.items.splice(index, 1); 
            }, 
            total() { 
                return this.grandTotal(); 
            },
            tax: false,
            nett() {
                return this.items.reduce((sum, item) => sum + ((item.qty || 0) * (item.price || 0)), 0);
            },
            totalDisc() {
                return this.items.reduce((sum, item) => sum + (item.disc || 0), 0);
            },
            taxAmount() {
                return this.tax ? Math.round((this.nett() - this.totalDisc()) * 0.11) : 0;
            },
            grandTotal() {
                return this.nett() - this.totalDisc() + this.taxAmount();
            },
            searchQuery: '',
            selectedCategory: '',
            selectedProduct: null,
            modalUnit: 'PCS',
            modalQty: 1,
            modalDisc: 0,
            availableProducts: [
                { code: 'PRD-001', name: 'SEDOTAN STERIL HITAM 6MM', category: 'Sedotan' },
                { code: 'PRD-002', name: 'HD SAMPAH HITAM 50 X 75 TEBAL', category: 'Plastik Sampah' },
                { code: 'PRD-003', name: 'HD BENING FINO 15', category: 'Kantong Plastik' }
            ],
            filteredProducts() {
                return this.availableProducts.filter(p => {
                    const matchesSearch = !this.searchQuery || 
                        p.name.toLowerCase().includes(this.searchQuery.toLowerCase()) || 
                        p.code.toLowerCase().includes(this.searchQuery.toLowerCase());
                    const matchesCategory = !this.selectedCategory || p.category === this.selectedCategory;
                    return matchesSearch && matchesCategory;
                });
            },
            resetModalFields() {
                this.searchQuery = '';
                this.selectedCategory = '';
                this.selectedProduct = null;
                this.modalUnit = 'PCS';
                this.modalQty = 1;
                this.modalDisc = 0;
            }
        }">
        <div class="flex shrink-0 items-center justify-between border-b border-gray-200 bg-zinc-50 px-8 py-6 dark:border-zinc-700 dark:bg-zinc-900">
            <h3 class="text-lg font-semibold dark:text-white">Sales Canvas</h3>
            <button type="button" wire:click="$set('showModal', false)" class="cursor-pointer text-gray-500 hover:text-gray-800 dark:hover:text-white">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="min-h-0 flex-1 touch-pan-y overflow-y-auto px-4 py-4 sm:px-6 sm:py-5 lg:px-8 lg:py-6"
            data-testid="customer-modal-scroll-container"
            style="overscroll-behavior: contain; scrollbar-gutter: stable; -webkit-overflow-scrolling: touch; touch-action: pan-y;">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium dark:text-white">Canvas No</label>
                    <input type="text" value="CAN-001" readonly
                        class="w-full cursor-not-allowed rounded-lg border border-gray-300 bg-gray-100 p-2.5 text-sm text-gray-700 dark:border-gray-600 dark:bg-zinc-700 dark:text-white">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium dark:text-white">Date</label>
                    <input type="date" value="2026-07-19"
                        class="w-full rounded-lg border border-gray-300 p-2.5 text-sm dark:border-gray-600 dark:bg-zinc-700 dark:text-white">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium dark:text-white">Customer</label>
                    <select class="w-full rounded-lg border border-gray-300 p-2.5 text-sm dark:border-gray-600 dark:bg-zinc-700 dark:text-white">
                        <option value="">Pilih customer</option>
                        <option value="1">PT. ABC</option>
                        <option value="2">PT. XYZ</option>
                        <option value="3">CV. Maju</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium dark:text-white">Customer Delivery Address</label>
                    <select class="w-full rounded-lg border border-gray-300 p-2.5 text-sm dark:border-gray-600 dark:bg-zinc-700 dark:text-white">
                        <option value="">Pilih alamat delivery</option>
                        <option value="1">Jl. Merdeka No. 10</option>
                        <option value="2">Jl. Sudirman No. 20</option>
                        <option value="3">Jl. Pahlawan No. 30</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium dark:text-white">Tax</label>
                    <label class="inline-flex items-center cursor-pointer">
                        <span
                            class="select-none text-base font-medium text-gray-600 dark:text-gray-400">Tidak</span>
                        <div class="relative mx-3">
                            <input type="checkbox" x-model="tax" wire:model.live="tax" class="sr-only peer">
                            <div
                                class="w-9 h-5 bg-red-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-red-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-red-500 after:content-[''] after:absolute after:top-0.5 after:start-0.5 after:bg-red-500 after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary-600">
                            </div>
                        </div>
                        <span
                            class="select-none text-base font-medium text-gray-600 dark:text-gray-400">Ya</span>
                    </label>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium dark:text-white">Note</label>
                    <input type="text" placeholder="Catatan transaksi"
                        class="w-full rounded-lg border border-gray-300 p-2.5 text-sm dark:border-gray-600 dark:bg-zinc-700 dark:text-white">
                </div>
            </div>

            <div class="mt-6 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="mb-4">
                    <h4 class="text-base font-semibold text-gray-900 dark:text-white">Detail Produk</h4>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse text-sm">
                        <thead class="bg-white text-gray-700 dark:bg-zinc-800 dark:text-gray-300">
                            <tr>
                                <th class="border border-gray-200 px-3 py-2 text-left dark:border-zinc-700">
                                    <button type="button" @click="resetModalFields(); showAddProductModal = true"
                                        class="inline-flex items-center justify-center w-10 h-10 rounded-sm bg-blue-600 text-white hover:bg-blue-700">
                                        <span class="text-xl font-semibold">+</span>
                                    </button>
                                </th>
                                <th class="border border-gray-200 px-3 py-2 text-left dark:border-zinc-700">Kode Produk</th>
                                <th class="border border-gray-200 px-3 py-2 text-left dark:border-zinc-700">Product Name</th>
                                <th class="border border-gray-200 px-3 py-2 text-left dark:border-zinc-700">Kategori</th>
                                <th class="border border-gray-200 px-3 py-2 text-left dark:border-zinc-700">Gudang</th>
                                <th class="border border-gray-200 px-3 py-2 text-left dark:border-zinc-700">Satuan</th>
                                <th class="border border-gray-200 px-3 py-2 text-left dark:border-zinc-700">Qty Pesanan</th>
                                <th class="border border-gray-200 px-3 py-2 text-left dark:border-zinc-700">Harga</th>
                                <th class="border border-gray-200 px-3 py-2 text-left dark:border-zinc-700">Disc (Amount)</th>
                                <th class="border border-gray-200 px-3 py-2 text-left dark:border-zinc-700">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(item, index) in items" :key="index">
                                <tr class="bg-white dark:bg-zinc-900">
                                    <td class="border border-gray-200 px-3 py-2 dark:border-zinc-700">
                                        <button type="button" @click="removeItem(index)"
                                            class="text-red-500 hover:text-red-700">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </td>
                                    <td class="border border-gray-200 px-3 py-2 dark:border-zinc-700" x-text="item.code"></td>
                                    <td class="border border-gray-200 px-3 py-2 dark:border-zinc-700" x-text="item.product"></td>
                                    <td class="border border-gray-200 px-3 py-2 dark:border-zinc-700" x-text="item.category"></td>
                                    <td class="border border-gray-200 px-3 py-2 dark:border-zinc-700">
                                        <select x-model="item.warehouse_id" class="w-full rounded border border-gray-300 px-2 py-1 text-sm dark:border-zinc-600 dark:bg-zinc-800">
                                            <option value="">Pilih Gudang</option>
                                            @forelse ($warehouses as $warehouse)
                                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                            @empty
                                            <option value="1">Gudang Utama</option>
                                            <option value="2">Gudang Jakarta</option>
                                            <option value="3">Gudang Surabaya</option>
                                            @endforelse
                                        </select>
                                    </td>
                                    <td class="border border-gray-200 px-3 py-2 dark:border-zinc-700">
                                        <select x-model="item.unit" class="w-full rounded border border-gray-300 px-2 py-1 text-sm dark:border-zinc-600 dark:bg-zinc-800">
                                            <option value="PCS">PCS</option>
                                            <option value="BOX">BOX</option>
                                            <option value="UNIT">UNIT</option>
                                        </select>
                                    </td>
                                    <td class="border border-gray-200 px-3 py-2 dark:border-zinc-700">
                                        <input type="number" x-model.number="item.qty" class="w-full rounded border border-gray-300 px-2 py-1 text-sm dark:border-zinc-600 dark:bg-zinc-800">
                                    </td>
                                    <td class="border border-gray-200 px-3 py-2 dark:border-zinc-700">
                                        <input type="number" x-model.number="item.price" class="w-full rounded border border-gray-300 px-2 py-1 text-sm dark:border-zinc-600 dark:bg-zinc-800">
                                    </td>
                                    <td class="border border-gray-200 px-3 py-2 dark:border-zinc-700">
                                        <input type="number" x-model.number="item.disc" class="w-full rounded border border-gray-300 px-2 py-1 text-sm dark:border-zinc-600 dark:bg-zinc-800">
                                    </td>
                                    <td class="border border-gray-200 px-3 py-2 dark:border-zinc-700">
                                        <div class="rounded border border-gray-200 bg-gray-50 px-2 py-1 text-sm text-gray-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-gray-200" x-text="((item.qty || 0) * (item.price || 0)).toLocaleString('id-ID')"></div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-4 bg-gray-50 p-4 rounded-xl border border-gray-200 dark:border-zinc-700 dark:bg-zinc-900">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Nett</label>
                        <input type="text" readonly disabled :value="nett().toLocaleString('id-ID')"
                            class="w-full rounded-lg border border-gray-300 bg-gray-100 p-2.5 text-sm text-gray-700 dark:border-gray-600 dark:bg-zinc-700 dark:text-white cursor-not-allowed">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Total Disc</label>
                        <input type="text" readonly disabled :value="totalDisc().toLocaleString('id-ID')"
                            class="w-full rounded-lg border border-gray-300 bg-gray-100 p-2.5 text-sm text-gray-700 dark:border-gray-600 dark:bg-zinc-700 dark:text-white cursor-not-allowed">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Tax</label>
                        <input type="text" readonly disabled :value="taxAmount().toLocaleString('id-ID')"
                            class="w-full rounded-lg border border-gray-300 bg-gray-100 p-2.5 text-sm text-gray-700 dark:border-gray-600 dark:bg-zinc-700 dark:text-white cursor-not-allowed">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Total</label>
                        <input type="text" readonly disabled :value="grandTotal().toLocaleString('id-ID')"
                            class="w-full rounded-lg border border-gray-300 bg-gray-100 p-2.5 text-sm font-semibold text-gray-900 dark:border-gray-600 dark:bg-zinc-700 dark:text-white cursor-not-allowed">
                    </div>
                </div>
            </div>

            <div x-show="showAddProductModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
                <div class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-xl dark:bg-zinc-800">
                    <div class="mb-4 flex items-center justify-between">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Tambah Produk</h4>
                        <button type="button" @click="showAddProductModal = false" class="text-gray-500 hover:text-gray-800 dark:hover:text-white">✕</button>
                    </div>

                    <!-- Search & Filter Row -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 mb-4">
                        <div class="relative sm:col-span-2">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            <input type="text" x-model="searchQuery" placeholder="Cari produk (nama / kode)..."
                                class="w-full pl-10 rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-zinc-700 dark:text-white dark:placeholder-gray-400">
                        </div>
                        <div>
                            <select x-model="selectedCategory"
                                class="w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-zinc-700 dark:text-white">
                                <option value="">Semua Kategori</option>
                                <option value="Sedotan">Sedotan</option>
                                <option value="Plastik Sampah">Plastik Sampah</option>
                                <option value="Kantong Plastik">Kantong Plastik</option>
                            </select>
                        </div>
                    </div>

                    <!-- Product Table -->
                    <div class="overflow-hidden border border-gray-200 dark:border-zinc-700 rounded-lg mb-4">
                        <div class="max-h-60 overflow-y-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700 text-sm">
                                <thead class="bg-gray-50 dark:bg-zinc-700 text-gray-700 dark:text-gray-300 sticky top-0 z-10">
                                    <tr>
                                        <th class="px-4 py-3 text-center font-semibold uppercase tracking-wider w-16">Pilih</th>
                                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wider">Kode</th>
                                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wider">Nama Produk</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                                    <template x-for="prod in filteredProducts()" :key="prod.code">
                                        <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700/50 cursor-pointer" @click="selectedProduct = prod">
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <div class="flex items-center justify-center">
                                                    <input type="radio" name="selected_prod" :value="prod.code" :checked="selectedProduct && selectedProduct.code === prod.code"
                                                        class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500 dark:border-gray-600 dark:bg-zinc-700">
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-900 dark:text-white" x-text="prod.code"></td>
                                            <td class="px-4 py-3 whitespace-nowrap text-gray-500 dark:text-gray-300" x-text="prod.name"></td>
                                        </tr>
                                    </template>
                                    <template x-if="filteredProducts().length === 0">
                                        <tr>
                                            <td colspan="3" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                                                Produk tidak ditemukan.
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-5 flex justify-end gap-2">
                        <button type="button" @click="showAddProductModal = false" class="rounded-lg border border-gray-300 px-4 py-2 text-sm dark:border-gray-600 dark:text-gray-200">Batal</button>
                        <button type="button"
                            :disabled="!selectedProduct"
                            @click="if (selectedProduct) { addItem(selectedProduct.code, selectedProduct.name, selectedProduct.category, modalUnit, modalQty, modalDisc) }"
                            class="rounded-lg bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                            Tambah
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex shrink-0 justify-end gap-2 border-t border-gray-200 bg-white px-4 py-4 sm:px-6 lg:px-8 dark:border-zinc-700 dark:bg-zinc-900">
            <button type="button" wire:click="$set('showModal', false)" class="cursor-pointer rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:text-gray-200">Batal</button>
            <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save"
                class="cursor-pointer rounded-lg bg-blue-600 px-4 py-2.5 text-sm text-white hover:bg-blue-700 disabled:opacity-50">
                <span wire:loading.remove wire:target="save">Simpan</span>
                <span wire:loading wire:target="save">Menyimpan...</span>
            </button>
        </div>
    </div>
</div>
@endif

{{-- MODAL DELETE CONFIRM --}}
{{-- @if ($showDeleteModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-xl w-full max-w-sm mx-4 p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-2 bg-red-900 rounded-full">
                        <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold dark:text-white">Hapus Pemasok?</h3>
                </div>
                <p class="text-sm text-gray-400 mb-5">Data akan dipindahkan ke tempat sampah.</p>
                <div class="flex justify-end gap-2">
                    <button wire:click="$set('showDeleteModal', false)"
                        class="px-4 py-2 text-sm rounded-lg border border-gray-600 dark:text-gray-300 hover:bg-zinc-700">
                        Batal
                    </button>
                    <button wire:click="delete"
                        class="px-4 py-2 text-sm rounded-lg bg-red-700 text-white hover:bg-red-800 cursor-pointer">
                        Ya, Hapus
                    </button>
                </div>
            </div>
        </div>
    @endif --}}
</div>