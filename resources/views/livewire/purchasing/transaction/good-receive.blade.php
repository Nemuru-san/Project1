<div x-data="{ toastMsg: '', toastType: '', showAddProductModal: false }"
    @toast.window="toastMsg = $event.detail.message; toastType = $event.detail.type; setTimeout(() => toastMsg = '', 3000)">

    {{-- TOAST --}}
    <div x-show="toastMsg" x-transition :class="toastType === 'success' ? 'bg-green-500' : 'bg-red-500'"
        class="fixed top-5 right-5 z-50 text-white px-4 py-2 rounded shadow-lg text-sm">
        <span x-text="toastMsg"></span>
    </div>

    {{-- FILTER BAR --}}
        <div
        class="flex flex-col md:flex-row items-center justify-between space-y-2 md:space-y-0 md:space-x-2 my-2 dark:bg-zinc-900">
        <p class="dark:text-white text-base font-semibold">Data Tabel Good Receive</p>
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
                    placeholder="Cari kode, nama, kontak..." />
            </div>
            {{-- Status Filter --}}
            <select wire:model.live="statusFilter"
                class="dark:bg-zinc-800 border border-gray-600 dark:text-white text-sm rounded-lg px-8 py-2.5 focus:ring-primary-500 w-full sm:w-auto">
                <option value="">Semua Status</option>
                <option value="1">Draft</option>
                <option value="0">Confirm</option>
                <option value="0">Partial Paid</option>
                <option value="0">Paid</option>
            </select>
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
                class="inline-flex items-center gap-2 text-white bg-blue-600 hover:bg-blue-700 border border-transparent text-sm font-medium px-4 py-2.5 rounded-lg whitespace-nowrap cursor-pointer sm:w-auto w-full justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Transaksi
            </button>
        </div>
    </div>

    {{-- TABLE --}}

    {{-- SHOW MODAL --}}
    @if ($showModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="fixed inset-0 z-40 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
                <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl w-full max-w-7xl mx-auto h-[90vh] flex flex-col overflow-hidden"
                    @click.outside="$wire.showModal = false">
                    <div class="flex items-center justify-between px-8 py-6 border-b border-gray-200 dark:border-zinc-700 shrink-0 bg-zinc-50 dark:bg-zinc-900">
                        <h3 class="text-lg font-semibold dark:text-white">
                            Buat Transaksi Good Receive
                        </h3>
                        <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-white cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto px-8 py-6">
                        <form action="#">
                            <div class="grid gap-5 sm:grid-cols-2 sm:gap-x-12 sm:gap-y-6">
                                <div class="w-full">
                                    <label for="po_no"
                                        class="block mb-3 text-base font-medium text-gray-900 dark:text-white">GR No</label>
                                    <input type="text" name="po_no" id="po_no"
                                        class="bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-zinc-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-gray-400 cursor-not-allowed"
                                        placeholder="Auto Generated" disabled>
                                </div>

                                <div class="w-full">
                                    <label for="po_date"
                                        class="block mb-3 text-base font-medium text-gray-900 dark:text-white">Date</label>
                                    <input type="date" name="po_date" id="po_date"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-zinc-800 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                        required="">
                                </div>

                                <div class="w-full">
                                    <label for="supplier"
                                        class="block mb-3 text-base font-medium text-gray-900 dark:text-white">Supplier</label>
                                    <select id="supplier" name="supplier"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-zinc-800 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                        required="">
                                        <option value="">-- Pilih Supplier --</option>
                                        <option value="SUP-001">PT Sumber Makmur Abadi</option>
                                        <option value="SUP-002">PT Berkah Jaya Sentosa</option>
                                        <option value="SUP-003">CV Mitra Sejahtera</option>
                                        <option value="SUP-004">PT Citra Utama</option>
                                        <option value="SUP-005">PT Sinar Jaya</option>
                                    </select>
                                </div>

                                <div class="w-full">
                                    <label for="supplier"
                                        class="block mb-3 text-base font-medium text-gray-900 dark:text-white">PO No</label>
                                    <select id="supplier" name="supplier"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-zinc-800 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                        required="">
                                        <option value="">-- Pilih PO --</option>
                                        <option value="PO-001">PO-001</option>
                                        <option value="PO-002">PO-002</option>
                                        <option value="PO-003">PO-003</option>
                                        <option value="PO-004">PO-004</option>
                                        <option value="PO-005">PO-005</option>
                                    </select>
                                </div>

                                <div class="w-full">
                                    <label for="po_no"
                                        class="block mb-3 text-base font-medium text-gray-900 dark:text-white">SJ No</label>
                                    <input type="text" name="po_no" id="po_no"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-zinc-800 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                        placeholder="SJ - 00001">
                                </div>

                                <div class="w-full">
                                    <label for="po_date"
                                        class="block mb-3 text-base font-medium text-gray-900 dark:text-white">SJ Date</label>
                                    <input type="date" name="po_date" id="po_date"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-zinc-800 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                        required="">
                                </div>
                            </div>
                            <div class="w-full mt-6">
                                    <label for="note"
                                        class="block mb-3 text-base font-medium text-gray-900 dark:text-white">Note</label>
                                    <textarea name="note" id="note" rows="4"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-zinc-800 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                        placeholder="Masukkan catatan atau keterangan tambahan...">
                                    </textarea>
                            </div>
                        </form>

                        {{-- tabel --}}
                        <div class="mt-12">
                            <div class="w-full grid grid-cols-1 sm:grid-cols-2  gap-6 mb-4 items-center">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Detail Produk</h3>
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full text-base text-left text-gray-900 dark:text-white my-2 min-w-425 whitespace-nowrap border-collapse border border-gray-300 dark:border-zinc-600">
                                    <thead
                                        class="text-base font-bold text-gray-900 uppercase bg-gray-200 dark:bg-zinc-700 dark:text-white">
                                        <tr>
                                            <th scope="col" class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">No</th>
                                            <th scope="col" class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">Product Code</th>
                                            <th scope="col" class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">Product Name</th>
                                            <th scope="col" class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">Category</th>
                                            <th scope="col" class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">Satuan</th>
                                            <th scope="col" class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">Rasio</th>
                                            <th scope="col" class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">Qty Order</th>
                                            <th scope="col" class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">Warehouse</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="hover:bg-gray-100 dark:hover:bg-zinc-800 text-sm">
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3 font-medium text-gray-900 dark:text-white">1</td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">PROD-001</td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">Produk A</td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">Gelas</td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">Per PCS</td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                <input type="number" min="0" step="1" value="1" class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-20 p-2 dark:bg-zinc-800 dark:border-gray-600 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                            </td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                <input type="number" min="0" step="1" value="1" class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-20 p-2 dark:bg-zinc-800 dark:border-gray-600 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                            </td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                <select class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2 dark:bg-zinc-800 dark:border-gray-600 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                                    <option value="">-- Pilih Warehouse --</option>
                                                    <option value="WH-001" selected>Warehouse A</option>
                                                    <option value="WH-002">Warehouse B</option>
                                                    <option value="WH-003">Warehouse C</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr class="hover:bg-gray-100 dark:hover:bg-zinc-800 text-sm">
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3 font-medium text-gray-900 dark:text-white">2</td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">PROD-002</td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">Produk B</td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">Botol</td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">Per PCS</td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                <input type="number" min="0" step="1" value="2" class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-20 p-2 dark:bg-zinc-800 dark:border-gray-600 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                            </td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                <input type="number" min="0" step="1" value="2" class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-20 p-2 dark:bg-zinc-800 dark:border-gray-600 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                            </td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                <select class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2 dark:bg-zinc-800 dark:border-gray-600 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                                    <option value="">-- Pilih Warehouse --</option>
                                                    <option value="WH-001">Warehouse A</option>
                                                    <option value="WH-002" selected>Warehouse B</option>
                                                    <option value="WH-003">Warehouse C</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr class="hover:bg-gray-100 dark:hover:bg-zinc-800 text-sm">
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3 font-medium text-gray-900 dark:text-white">3</td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">PROD-003</td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">Produk C</td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">Kotak</td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">Per Pak</td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                <input type="number" min="0" step="1" value="1" class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-20 p-2 dark:bg-zinc-800 dark:border-gray-600 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                            </td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                <input type="number" min="0" step="1" value="3" class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-20 p-2 dark:bg-zinc-800 dark:border-gray-600 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                            </td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                <select class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2 dark:bg-zinc-800 dark:border-gray-600 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                                    <option value="">-- Pilih Warehouse --</option>
                                                    <option value="WH-001">Warehouse A</option>
                                                    <option value="WH-002">Warehouse B</option>
                                                    <option value="WH-003" selected>Warehouse C</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr class="hover:bg-gray-100 dark:hover:bg-zinc-800 text-sm">
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3 font-medium text-gray-900 dark:text-white">4</td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">PROD-004</td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">Produk D</td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">Dus</td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">Per Kotak</td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                <input type="number" min="0" step="1" value="5" class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-20 p-2 dark:bg-zinc-800 dark:border-gray-600 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                            </td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                <input type="number" min="0" step="1" value="4" class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-20 p-2 dark:bg-zinc-800 dark:border-gray-600 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                            </td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                <select class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2 dark:bg-zinc-800 dark:border-gray-600 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                                    <option value="">-- Pilih Warehouse --</option>
                                                    <option value="WH-001" selected>Warehouse A</option>
                                                    <option value="WH-002">Warehouse B</option>
                                                    <option value="WH-003">Warehouse C</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr class="hover:bg-gray-100 dark:hover:bg-zinc-800 text-sm">
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3 font-medium text-gray-900 dark:text-white">5</td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">PROD-005</td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">Produk E</td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">Sak</td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">Per PCS</td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                <input type="number" min="0" step="1" value="1" class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-20 p-2 dark:bg-zinc-800 dark:border-gray-600 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                            </td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                <input type="number" min="0" step="1" value="5" class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-20 p-2 dark:bg-zinc-800 dark:border-gray-600 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                            </td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                <select class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2 dark:bg-zinc-800 dark:border-gray-600 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                                    <option value="">-- Pilih Warehouse --</option>
                                                    <option value="WH-001">Warehouse A</option>
                                                    <option value="WH-002" selected>Warehouse B</option>
                                                    <option value="WH-003">Warehouse C</option>
                                                </select>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                </div>
                <div class="flex justify-end gap-2 p-4 border-t border-gray-200 dark:border-zinc-700 shrink-0 bg-white dark:bg-zinc-900">
                    <button wire:click="$set('showModal', false)"
                                class="px-4 py-2 text-sm rounded-lg border border-gray-600 dark:text-gray-300 hover:bg-zinc-700">
                                Batal
                    </button>
                    <button wire:click="save" wire:loading.attr="disabled"
                                class="px-4 py-2 text-sm rounded-lg bg-blue-600 hover:bg-blue-700 text-white disabled:opacity-50 cursor-pointer">
                                <span wire:loading.remove wire:target="save">Simpan</span>
                                <span wire:loading wire:target="save">Menyimpan...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
