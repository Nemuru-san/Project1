<div x-data="{
    toastMsg: '',
    toastType: '',

    {{-- Price rows state (synced with Livewire via priceRowsJson) --}}
    rows: [],
    nextTempId: 1,

    init() {
        this.syncFromWire();

        {{-- Watch for Livewire property changes (e.g. openEdit sets priceRowsJson) --}}
        this.$watch('$wire.priceRowsJson', () => this.syncFromWire());
        this.$watch('$wire.base_unit_id', () => {
            if (this.normalizeBaseUnitRows()) {
                this.syncToWire();
            }
        });
    },

    {{-- syncFromWire() {
        const raw = JSON.parse(this.$wire.priceRowsJson || '[]');
        this.rows = raw.map(r => ({ ...r, _id: this.nextTempId++ }));
    }, --}}

    syncFromWire() {
        const raw = JSON.parse(this.$wire.priceRowsJson || '[]');

        this.rows = raw.map(r => ({
            _id: this.nextTempId++,
            unit_id: r.unit_id ?? '',
            conversion: r.conversion || 1,
            price: r.price ?? 0,
        }));

        if (this.normalizeBaseUnitRows()) {
            this.syncToWire();
        }
    },

    syncToWire() {
        this.$wire.set('priceRowsJson', JSON.stringify(
            this.rows.map(({ unit_id, conversion, price }) => ({ unit_id, conversion, price }))
        ));
    },

    {{-- addRow() {
        this.rows.push({ _id: this.nextTempId++, unit_id: '', conversion: '', price: '' });
        this.syncToWire();
    }, --}}

    addRow() {
        this.rows.push({
            _id: this.nextTempId++,
            unit_id: '',
            conversion: '',
            price: 0
        });

        this.syncToWire();
    },

    removeRow(id) {
        this.rows = this.rows.filter(r => r._id !== id);
        this.syncToWire();
    },

    handleUnitChange(row) {
        this.normalizeBaseUnitRow(row);
        this.syncToWire();
    },

    handleConversionChange(row) {
        this.normalizeBaseUnitRow(row);
        this.syncToWire();
    },

    normalizeBaseUnitRows() {
        return this.rows.reduce((changed, row) => this.normalizeBaseUnitRow(row) || changed, false);
    },

    normalizeBaseUnitRow(row) {
        if (this.isBaseUnit(row) && Number(row.conversion) !== 1) {
            row.conversion = 1;
            return true;
        }

        return false;
    },

    isUnitSelected(unitId, currentRowId) {
        return this.rows.some(row =>
            row._id !== currentRowId &&
            String(row.unit_id) === String(unitId)
        );
    },

    isBaseUnit(row) {
        const baseUnitId = this.$wire.base_unit_id;

        return row.unit_id !== '' &&
            baseUnitId !== null &&
            baseUnitId !== '' &&
            String(row.unit_id) === String(baseUnitId);
    }
}"
    @toast.window="toastMsg = $event.detail.message; toastType = $event.detail.type; setTimeout(() => toastMsg = '', 3000)">

    {{-- ═══════════════════════ TOAST ═══════════════════════ --}}
    <div x-show="toastMsg" x-transition.opacity :class="toastType === 'success' ? 'bg-green-500' : 'bg-red-500'"
        class="fixed top-5 right-5 z-50 text-white px-4 py-2 rounded shadow-lg text-sm">
        <span x-text="toastMsg"></span>
    </div>

    {{-- ═══════════════════════ FILTER BAR ═══════════════════════ --}}
    <div
        class="flex flex-col md:flex-row items-center justify-between space-y-3 md:space-y-0 md:space-x-4 my-4 dark:bg-zinc-900">

        <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto">
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
                    placeholder="Cari SKU, nama, brand..." />
            </div>

            {{-- Per Page --}}
            <select wire:model.live="perPage"
                class="dark:bg-zinc-800 border border-gray-600 dark:text-white text-sm rounded-lg px-3 py-2.5 w-full sm:w-auto">
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

            {{-- Tambah --}}
            <button wire:click="openCreate"
                class="inline-flex items-center gap-2 text-white bg-blue-600 hover:bg-blue-700 text-sm font-medium px-4 py-2.5 rounded-lg whitespace-nowrap cursor-pointer sm:w-auto w-full justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Data
            </button>
        </div>
    </div>

    {{-- ═══════════════════════ TABLE ═══════════════════════ --}}
    <div class="overflow-x-auto dark:border-zinc-700 dark:bg-zinc-900">
        <table class="w-full text-base text-left text-gray-500 dark:text-gray-400 mt-4">
            <thead class="text-lg font-bold uppercase bg-gray-50 dark:bg-zinc-800 dark:text-white">
                <tr>
                    <th class="px-4 py-4 w-12">No.</th>
                    <th class="px-4 py-4 cursor-pointer select-none" wire:click="sortBy('sku')">
                        <div class="flex items-center gap-1">
                            SKU Produk
                            @if ($sortField === 'sku')
                                <span class="text-xs">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </div>
                    </th>
                    <th class="px-4 py-4 cursor-pointer select-none" wire:click="sortBy('name')">
                        <div class="flex items-center gap-1">
                            Nama Produk
                            @if ($sortField === 'name')
                                <span class="text-xs">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </div>
                    </th>
                    <th class="px-4 py-4">Kategori</th>
                    <th class="px-4 py-4">Satuan Dasar</th>
                    <th class="px-4 py-4">Spesifikasi</th>
                    <th class="px-4 py-4">Merek</th>
                    <th class="px-4 py-4">Status</th>
                    <th class="px-4 py-4">Aksi</th>
                </tr>
            </thead>
            <tbody class="dark:bg-zinc-950 text-base">
                @forelse($products as $index => $product)
                    <tr
                        class="border-b dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-zinc-800 {{ $product->trashed() ? 'opacity-50' : '' }}">
                        <td class="px-4 py-4 text-gray-500">{{ $products->firstItem() + $index }}</td>
                        <td class="px-4 py-4 font-mono font-medium text-gray-900 dark:text-white">{{ $product->sku }}
                        </td>
                        <td class="px-4 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                            {{ $product->name }}</td>
                        <td class="px-4 py-4">{{ $product->category?->name ?? '-' }}</td>
                        <td class="px-4 py-4">
                            {{ $product->baseUnit ? $product->baseUnit->name . ' (' . $product->baseUnit->code . ')' : '-' }}
                        </td>
                        <td class="px-4 py-4">{{ $product->specification ?: '-' }}</td>
                        <td class="px-4 py-4">{{ $product->brand ?: '-' }}</td>
                        <td class="px-4 py-4">
                            @if ($product->trashed())
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

                                    @if ($product->trashed())
                                        <div class="px-4 py-2 text-sm text-gray-400">
                                            Data sudah terhapus
                                        </div>
                                    @else
                                        <ul class="py-1 text-base text-gray-700 dark:text-gray-200">
                                            <li>
                                                <button wire:click="openDetail({{ $product->id }})"
                                                    @click="open = false"
                                                    class="flex items-center gap-2 w-full py-2 px-4 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white cursor-pointer">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>Detail
                                                </button>
                                            </li>

                                            <li>
                                                <button wire:click="openEdit({{ $product->id }})"
                                                    @click="open = false"
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
                                            <button wire:click="confirmDelete({{ $product->id }})"
                                                @click="open = false"
                                                class="flex items-center gap-2 w-full py-2 px-4 text-base text-gray-700 hover:bg-red-600 hover:text-white dark:text-gray-200 dark:hover:bg-red-600 dark:hover:text-white cursor-pointer">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
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
                        <td colspan="9" class="px-4 py-10 text-center text-gray-400 dark:text-gray-500">
                            Tidak ada data produk ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $products->links() }}
    </div>

    {{-- ═══════════════════════ CREATE / EDIT MODAL ═══════════════════════ --}}
    @if ($showModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div
                class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl w-full max-w-7xl mx-auto h-[90vh] flex flex-col overflow-hidden">

                {{-- Modal Header --}}
                <div
                    class="flex items-center justify-between px-8 py-5 border-b border-gray-200 dark:border-zinc-700 shrink-0 bg-zinc-50 dark:bg-zinc-900">
                    <h3 class="text-lg font-semibold dark:text-white">
                        {{ $editingId ? 'Ubah Produk' : 'Tambah Master Produk' }}
                    </h3>
                    <button wire:click="$set('showModal', false)"
                        class="text-gray-400 hover:text-gray-200 cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Modal Body --}}
                <div class="flex-1 overflow-y-auto px-8 pt-6 pb-10 space-y-8">

                    {{-- Validation Errors --}}
                    {{-- @if ($errors->any())
                        <div
                            class="bg-red-50 dark:bg-red-900/20 border border-red-300 dark:border-red-700 text-red-700 dark:text-red-400 rounded-lg px-4 py-3 text-sm">
                            <ul class="list-disc list-inside space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif --}}

                    {{-- ── Section: Info Produk ── --}}
                    <div>
                        <h4
                            class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-4">
                            Informasi Produk</h4>
                        <div class="grid gap-5 sm:grid-cols-2">

                            {{-- Foto Produk --}}
                            <div class="sm:col-span-2">
                                <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">
                                    Foto Produk
                                </label>

                                {{-- Preview gambar lama (edit mode, belum pilih yg baru) --}}
                                @if ($existingImage && !$image)
                                    <div class="mb-2">
                                        <img src="{{ Storage::url($existingImage) }}" alt="Foto produk"
                                            class="h-24 w-24 object-cover rounded-lg border border-gray-200 dark:border-zinc-600">
                                    </div>
                                @endif

                                {{-- Preview upload baru --}}
                                @if ($image)
                                    <div class="mb-2">
                                        <img src="{{ $image->temporaryUrl() }}" alt="Preview"
                                            class="h-24 w-24 object-cover rounded-lg border border-blue-400">
                                    </div>
                                @endif

                                <input wire:model="image" type="file" accept="image/*"
                                    class="block w-full text-sm text-gray-500 dark:text-gray-400 rounded-lg file:border-0 file:text-sm file:font-medium " />

                                <div wire:loading wire:target="image" class="mt-1 text-xs text-blue-500">
                                    Mengupload...
                                </div>

                                @error('image')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Nama Produk --}}
                            <div class="sm:col-span-2">
                                <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">
                                    Nama Produk <span class="text-red-500">*</span>
                                </label>
                                <input wire:model="name" type="text"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-zinc-700 dark:border-zinc-600 dark:text-white @error('name') border-red-500 @enderror"
                                    placeholder="Masukkan nama produk" />
                                @error('name')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- SKU --}}
                            <div>
                                <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">
                                    SKU
                                </label>
                                <input wire:model="sku" type="text"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-zinc-700 dark:border-zinc-600 dark:text-white @error('sku') border-red-500 @enderror"
                                    placeholder="Gelas plastik" />
                                @error('sku')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Specification --}}
                            <div>
                                <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">
                                    Specification
                                </label>
                                <input wire:model="specification" type="text"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-zinc-700 dark:border-zinc-600 dark:text-white @error('specification') border-red-500 @enderror"
                                    placeholder="Contoh: 30 CM X 24 ROLL" />
                                @error('specification')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Kategori --}}
                            <div>
                                <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">
                                    Kategori <span class="text-red-500">*</span>
                                </label>
                                <select wire:model="category_id"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-zinc-700 dark:border-zinc-600 dark:text-white @error('category_id') border-red-500 @enderror">
                                    <option value="">-- Pilih Kategori --</option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat['id'] }}">{{ $cat['code'] }} - {{ $cat['name'] }}</option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Base Unit --}}
                            <div>
                                <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Satuan Dasar <span class="text-red-500">*</span>
                                </label>
                                <select wire:model.live="base_unit_id"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-zinc-700 dark:border-zinc-600 dark:text-white @error('base_unit_id') border-red-500 @enderror">
                                    <option value="">-- Pilih Base Unit --</option>
                                    @foreach ($units as $unit)
                                        <option value="{{ $unit['id'] }}">{{ $unit['name'] }} ({{ $unit['code'] }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('base_unit_id')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Brand --}}
                            <div>
                                <label
                                    class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Merek</label>
                                <input wire:model="brand" type="text"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-zinc-700 dark:border-zinc-600 dark:text-white"
                                    placeholder="Nama brand (opsional)" />
                            </div>

                            {{-- Deskripsi --}}
                            <div class="sm:col-span-2">
                                <label
                                    class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Deskripsi</label>
                                <textarea wire:model="desc" rows="2"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-zinc-700 dark:border-zinc-600 dark:text-white"
                                    placeholder="Deskripsi produk (opsional)"></textarea>
                            </div>

                        </div>
                    </div>

                    {{-- ── Section: Harga / Unit ── --}}
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <h4
                                class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Satuan & Harga
                            </h4>
                            <button type="button" @click="addRow()"
                                class="inline-flex items-center gap-1.5 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 px-3 py-1.5 rounded-lg">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                Tambah Baris
                            </button>
                        </div>

                        @error('priceRowsJson')
                            <p class="mb-3 text-xs text-red-500">{{ $message }}</p>
                        @enderror

                        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-zinc-700">
                            <table class="w-full text-sm text-left text-gray-900 dark:text-white border-collapse">
                                <thead
                                    class="text-sm font-bold uppercase bg-gray-100 dark:bg-zinc-700 dark:text-gray-300">
                                    <tr>
                                        <th
                                            class="border border-gray-200 dark:border-zinc-600 px-3 py-2.5 w-12 text-center">
                                            #</th>
                                        <th
                                            class="border border-gray-200 dark:border-zinc-600 px-3 py-2.5 w-14 text-center">No.</th>
                                        <th class="border border-gray-200 dark:border-zinc-600 px-3 py-2.5">Satuan</th>
                                        <th class="border border-gray-200 dark:border-zinc-600 px-3 py-2.5 w-36">
                                            Konversi</th>
                                        <th class="border border-gray-200 dark:border-zinc-600 px-3 py-2.5 w-44">Retail
                                            Harga (Rp)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(row, index) in rows" :key="row._id">
                                        <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700/40">
                                            <td
                                                class="border border-gray-200 dark:border-zinc-600 px-3 py-2 text-center">
                                                <button x-show="rows.length > 1" type="button" @click="removeRow(row._id)"
                                                    class="text-red-500 hover:text-red-700">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </td>
                                            <td class="border border-gray-200 dark:border-zinc-600 px-3 py-2 text-center font-medium"
                                                x-text="index + 1"></td>
                                            <td class="border border-gray-200 dark:border-zinc-600 px-3 py-2">
                                                <select x-model="row.unit_id" @change="handleUnitChange(row)"
                                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2 dark:bg-zinc-800 dark:border-zinc-600 dark:text-white">
                                                    <option value="">-- Pilih Unit --</option>
                                                    @foreach ($units as $unit)
                                                        <option value="{{ $unit['id'] }}"
                                                            :hidden="isUnitSelected({{ $unit['id'] }}, row._id)"
                                                            :disabled="isUnitSelected({{ $unit['id'] }}, row._id)">
                                                            {{ $unit['name'] }} ({{ $unit['code'] }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <p x-show="isBaseUnit(row)"
                                                    class="mt-1 text-[11px] text-blue-600 dark:text-blue-400">
                                                    Base unit
                                                </p>
                                            </td>
                                            <td class="border border-gray-200 dark:border-zinc-600 px-3 py-2">
                                                <input x-model="row.conversion" @change="handleConversionChange(row)"
                                                    type="number" min="1" :readonly="isBaseUnit(row)"
                                                    :class="isBaseUnit(row) ? 'cursor-not-allowed opacity-70' : ''"
                                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2 dark:bg-zinc-800 dark:border-zinc-600 dark:text-white" />
                                            </td>
                                            <td class="border border-gray-200 dark:border-zinc-600 px-3 py-2">
                                                <input x-model="row.price_display" x-init="if (row.price === 0) row.price = null;
                                                
                                                row.price_display =
                                                    row.price === null || row.price === undefined || row.price === '' ?
                                                    '' :
                                                    Number(row.price).toLocaleString('id-ID');"
                                                    @input="
                                                        let raw = row.price_display.replace(/\D/g, '');

                                                        row.price = raw === '' ? null : Number(raw);

                                                        row.price_display = raw === ''
                                                            ? ''
                                                            : Number(raw).toLocaleString('id-ID');
                                                    "
                                                    @blur="syncToWire()" type="text" inputmode="numeric"
                                                    autocomplete="off" placeholder="e.g. 15.000"
                                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2 dark:bg-zinc-800 dark:border-zinc-600 dark:text-white" />
                                            </td>
                                        </tr>
                                    </template>
                                    <template x-if="rows.length === 0">
                                        <tr>
                                            <td colspan="5"
                                                class="border border-gray-200 dark:border-zinc-600 px-4 py-6 text-center text-gray-400 dark:text-gray-500 text-sm">
                                                Belum ada baris harga. Klik "Tambah Baris" untuk menambahkan.
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>{{-- end modal body --}}

                {{-- Modal Footer --}}
                <div
                    class="flex justify-end gap-2 px-8 py-5 border-t border-gray-200 dark:border-zinc-700 shrink-0 bg-white dark:bg-zinc-900">
                    <button wire:click="$set('showModal', false)"
                        class="px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-zinc-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-zinc-700">
                        Batal
                    </button>
                    <button wire:click="save" wire:loading.attr="disabled"
                        class="px-5 py-2 text-sm rounded-lg bg-blue-600 hover:bg-blue-700 text-white disabled:opacity-50 cursor-pointer">
                        <span wire:loading.remove wire:target="save">Simpan</span>
                        <span wire:loading wire:target="save">Menyimpan...</span>
                    </button>
                </div>

            </div>
        </div>
    @endif

    {{-- ═══════════════════════ DELETE CONFIRM MODAL ═══════════════════════ --}}
    @if ($showDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl w-full max-w-md p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div
                        class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold dark:text-white">Konfirmasi Hapus</h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                    Apakah Anda yakin ingin menghapus produk ini? Data akan dipindahkan ke tempat sampah dan masih bisa
                    dipulihkan.
                </p>
                <div class="flex justify-end gap-2">
                    <button wire:click="$set('showDeleteModal', false)"
                        class="px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-zinc-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-zinc-700">
                        Batal
                    </button>
                    <button wire:click="delete" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm rounded-lg bg-red-600 hover:bg-red-700 text-white disabled:opacity-50">
                        <span wire:loading.remove wire:target="delete">Ya, Hapus</span>
                        <span wire:loading wire:target="delete">Menghapus...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif


    {{-- ═══════════════════════ DETAIL MODAL ═══════════════════════ --}}
    @if ($showDetailModal && $detailProduct)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div
                class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl w-full max-w-2xl flex flex-col max-h-[90vh] overflow-hidden">

                {{-- Header --}}
                <div
                    class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900 shrink-0">
                    <h3 class="text-base font-semibold dark:text-white">Detail Produk</h3>
                    <button wire:click="$set('showDetailModal', false)"
                        class="text-gray-400 hover:text-gray-200 cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="overflow-y-auto px-6 py-5 space-y-6">

                    {{-- Image + Info utama --}}
                    <div class="flex gap-5 items-start">
                        {{-- Image --}}
                        <div class="shrink-0">
                            @if ($detailProduct['image'])
                                <img src="{{ Storage::url($detailProduct['image']) }}"
                                    alt="{{ $detailProduct['name'] }}"
                                    class="w-24 h-24 object-cover rounded-xl border border-gray-200 dark:border-zinc-600">
                            @else
                                <div
                                    class="w-24 h-24 rounded-xl border border-gray-200 dark:border-zinc-600 bg-gray-100 dark:bg-zinc-700 flex items-center justify-center">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            @endif
                        </div>

                        {{-- Info --}}
                        <div class="flex-1 space-y-1.5">
                            <p class="text-lg font-semibold dark:text-white">{{ $detailProduct['name'] }}</p>
                            <p class="text-sm font-mono text-gray-500 dark:text-gray-400">{{ $detailProduct['sku'] }}
                            </p>
                            <div class="flex items-center gap-2 flex-wrap mt-1">
                                <span
                                    class="px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400">
                                    {{ $detailProduct['category'] ?? '-' }}
                                </span>
                                @if ($detailProduct['base_unit'])
                                    <span
                                        class="px-2 py-0.5 text-xs rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                                        {{ $detailProduct['base_unit'] }}
                                    </span>
                                @endif
                                @if ($detailProduct['brand'])
                                    <span
                                        class="px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-600 dark:bg-zinc-700 dark:text-gray-300">
                                        {{ $detailProduct['brand'] }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Deskripsi --}}
                    @if ($detailProduct['desc'])
                        <div>
                            <p
                                class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-1">
                                Deskripsi</p>
                            <p class="text-sm text-gray-700 dark:text-gray-300">{{ $detailProduct['desc'] }}</p>
                        </div>
                    @endif

                    {{-- Tanggal --}}
                    <div>
                        <p
                            class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-1">
                            Tanggal Dibuat</p>
                        <p class="text-sm text-gray-700 dark:text-gray-300">{{ $detailProduct['created_at'] }}</p>
                    </div>

                    {{-- Unit & Harga --}}
                    <div>
                        <p
                            class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-3">
                            Satuan & Harga</p>
                        @if (count($detailProduct['prices']) > 0)
                            <div class="rounded-lg border border-gray-200 dark:border-zinc-700 overflow-hidden">
                                <table class="w-full text-sm text-left text-gray-900 dark:text-white">
                                    <thead
                                        class="text-xs font-bold uppercase bg-gray-100 dark:bg-zinc-700 dark:text-gray-300">
                                        <tr>
                                            <th class="px-4 py-2.5">Satuan</th>
                                            <th class="px-4 py-2.5 text-center">Dasar</th>
                                            <th class="px-4 py-2.5 text-center">Konversi</th>
                                            <th class="px-4 py-2.5 text-right">Harga Eceran</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-zinc-700">
                                        @foreach ($detailProduct['prices'] as $p)
                                            <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700/40">
                                                <td class="px-4 py-2.5">{{ $p['unit'] }}</td>
                                                <td class="px-4 py-2.5 text-center">
                                                    @if ($p['is_base_unit'])
                                                        <span
                                                            class="px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400">
                                                            Ya
                                                        </span>
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td class="px-4 py-2.5 text-center">{{ $p['conversion'] }}</td>
                                                <td class="px-4 py-2.5 text-right font-medium">
                                                    Rp {{ number_format($p['price'], 0, ',', '.') }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-sm text-gray-400 dark:text-gray-500 italic">Belum ada data harga.</p>
                        @endif
                    </div>

                </div>

                {{-- Footer --}}
                <div
                    class="flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-zinc-700 shrink-0 bg-white dark:bg-zinc-900">
                    <button wire:click="openEdit({{ $detailProduct['id'] }})"
                        class="px-4 py-2 text-sm rounded-lg bg-blue-600 hover:bg-blue-700 text-white cursor-pointer">
                        Edit Produk
                    </button>
                    <button wire:click="$set('showDetailModal', false)"
                        class="px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-zinc-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-zinc-700">
                        Tutup
                    </button>
                </div>

            </div>
        </div>
    @endif
</div>
