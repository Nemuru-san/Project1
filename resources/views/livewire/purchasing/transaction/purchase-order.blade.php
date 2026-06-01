<div x-data="{ toastMsg: '', toastType: '', showAddProductModal: false }"
    @toast.window="toastMsg = $event.detail.message; toastType = $event.detail.type; setTimeout(() => toastMsg = '', 3000)">

    {{-- TOAST --}}
    <div x-show="toastMsg" x-transition :class="toastType === 'success' ? 'bg-green-500' : 'bg-red-500'"
        class="fixed top-5 right-5 z-50 text-white px-4 py-2 rounded shadow-lg text-sm">
        <span x-text="toastMsg"></span>
    </div>

    {{-- FILTER BAR --}}
    <div
        class="flex flex-col md:flex-row items-center justify-between space-y-3 md:space-y-0 md:space-x-4 my-4 dark:bg-zinc-900">
        <p class="dark:text-white text-base font-semibold">Data Tabel Purchase Order</p>
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
                <option value="0">Approved</option>
                <option value="0">Partially Received</option>
                <option value="0">Received</option>
                <option value="0">Canceled</option>
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
                <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl w-full max-w-full mx-auto h-[90vh] flex flex-col overflow-hidden"
                    @click.outside="$wire.showModal = false">
                    <div class="flex items-center justify-between px-8 py-6 border-b border-gray-200 dark:border-zinc-700 shrink-0 bg-zinc-50 dark:bg-zinc-900">
                        <h3 class="text-lg font-semibold dark:text-white">
                            Tambah Purchase Order
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
                                        class="block mb-3 text-base font-medium text-gray-900 dark:text-white">PO No</label>
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
                                    <label for="tax"
                                        class="block mb-3 text-base font-medium text-gray-900 dark:text-white">Tax</label>
                                    <label class="inline-flex items-center cursor-pointer">
                                        <span
                                            class="select-none text-base font-medium text-gray-600 dark:text-gray-400">No</span>
                                        <input type="checkbox" name="tax" id="tax" value=""
                                            class="sr-only peer">
                                        <div
                                            class="relative mx-3 w-9 h-5 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:start-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary-600">
                                        </div>
                                        <span
                                            class="select-none text-base font-medium text-gray-600 dark:text-gray-400">Yes</span>
                                    </label>
                                </div>
                            </div>
                            <div class="w-full mt-6">
                                <label for="note" class="block mb-3 text-base font-medium text-gray-900 dark:text-white">Note</label>
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
                                            <th scope="col" class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-center w-14">
                                                    <button type="button" @click.prevent="showAddProductModal = true"
                                                        class="inline-flex items-center justify-center w-10 h-10 rounded-sm bg-blue-600 text-white hover:bg-blue-700">
                                                        <span class="text-xl font-semibold">+</span>
                                                    </button>
                                            </th>
                                                <th scope="col" class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">No</th>
                                                <th scope="col" class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">Product Code</th>
                                            <th scope="col" class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">Product Name</th>
                                            <th scope="col" class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">Category</th>
                                            <th scope="col" class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">Satuan</th>
                                            <th scope="col" class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">Qty Order</th>
                                            <th scope="col" class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">Price</th>
                                            <th scope="col" class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">Disc Amount</th>
                                            <th scope="col" class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">Sub Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="hover:bg-gray-100 dark:hover:bg-zinc-800 text-sm">
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-center text-gray-500 dark:text-gray-400">
                                                <button type="button" class="text-red-500 hover:text-red-700">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3 font-medium text-gray-900 dark:text-white">2</td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">PROD-002</td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">Produk B</td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">Pipet</td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                <select class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-zinc-800 dark:border-gray-600 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                                    <option value="1">Per Pak</option>
                                                    <option value="2" selected>Per Kotak</option>
                                                    <option value="3">Per PCS</option>
                                                </select>
                                            </td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                <input type="number" min="0" step="1" value="2"
                                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-24 p-2 dark:bg-zinc-800 dark:border-gray-600 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                            </td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                <div class="flex gap-2">
                                                    <input type="number" min="0" step="0.01" value="5.00"
                                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-20 p-2 dark:bg-zinc-800 dark:border-gray-600 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                                </div>
                                            </td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                <input type="text" value="75.000"
                                                    class="bg-gray-100 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-24 p-2 dark:bg-zinc-700 dark:border-gray-600 dark:text-white cursor-not-allowed" disabled>
                                            </td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-gray-900 dark:text-white">750.000</td>
                                        </tr>
                                        <tr class="hover:bg-gray-100 dark:hover:bg-zinc-800 text-sm">
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-center text-gray-500 dark:text-gray-400">
                                                <button type="button" class="text-red-500 hover:text-red-700">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3 font-medium text-gray-900 dark:text-white">3</td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">PROD-003</td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">Produk C</td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">Tutup Botol</td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                <select class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-zinc-800 dark:border-gray-600 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                                    <option value="1" selected>Per Pak</option>
                                                    <option value="2">Per Kotak</option>
                                                    <option value="3">Per PCS</option>
                                                </select>
                                            </td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                <input type="number" min="0" step="1" value="3"
                                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-24 p-2 dark:bg-zinc-800 dark:border-gray-600 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                            </td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                <div class="flex gap-2">
                                                    <input type="number" min="0" step="0.01" value="10.00"
                                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-20 p-2 dark:bg-zinc-800 dark:border-gray-600 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                                </div>
                                            </td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                <input type="text" value="100.000"
                                                    class="bg-gray-100 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-24 p-2 dark:bg-zinc-700 dark:border-gray-600 dark:text-white cursor-not-allowed" disabled>
                                            </td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-gray-900 dark:text-white">1.500.000</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <nav class="flex flex-col md:flex-row justify-between items-start md:items-center space-y-3 md:space-y-0 p-4 mt-4 dark:border-zinc-700 dark:bg-zinc-900"
                            aria-label="Table navigation">
                            <span class="text-base font-normal text-gray-500 dark:text-gray-400">
                                Showing
                                <span class="font-semibold text-gray-900 dark:text-white">1-10</span>
                                of
                                <span class="font-semibold text-gray-900 dark:text-white">1000</span>
                            </span>
                            <ul class="inline-flex items-stretch -space-x-px">
                                <li>
                                    <a href="#"
                                        class="flex items-center justify-center h-full py-1.5 px-3 ml-0 text-gray-500 bg-white rounded-l-lg border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                                        <span class="sr-only">Previous</span>
                                        <svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewbox="0 0 20 20"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd"
                                                d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </a>
                                </li>
                                <li>
                                    <a href="#"
                                        class="flex items-center justify-center text-base py-2 px-3 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">1</a>
                                </li>
                                <li>
                                    <a href="#"
                                        class="flex items-center justify-center text-base py-2 px-3 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">2</a>
                                </li>
                                <li>
                                    <a href="#" aria-current="page"
                                        class="flex items-center justify-center text-base z-10 py-2 px-3 leading-tight text-primary-600 bg-primary-50 border border-primary-300 hover:bg-primary-100 hover:text-primary-700 dark:border-gray-700 dark:bg-gray-700 dark:text-white">3</a>
                                </li>
                                <li>
                                    <a href="#"
                                        class="flex items-center justify-center text-base py-2 px-3 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">...</a>
                                </li>
                                <li>
                                    <a href="#"
                                        class="flex items-center justify-center text-base py-2 px-3 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">100</a>
                                </li>
                                <li>
                                    <a href="#"
                                        class="flex items-center justify-center h-full py-1.5 px-3 leading-tight text-gray-500 bg-white rounded-r-lg border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                                        <span class="sr-only">Next</span>
                                        <svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewbox="0 0 20 20"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd"
                                                d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </a>
                                </li>
                            </ul>
                        </nav>

                        <div
                            class="mt-6 p-4 bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-gray-700">
                            <h4 class="mb-4 text-base font-bold text-gray-900 dark:text-white">Detail Harga</h4>
                            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                <div>
                                    <label for="gross"
                                        class="block mb-4 text-base font-medium text-gray-900 dark:text-white">Gross</label>
                                    <input type="text" id="gross" value="0"
                                        class="bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-zinc-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-gray-400 cursor-not-allowed"
                                        disabled>
                                </div>

                                <div>
                                    <label for="total_disc"
                                        class="block mb-4 text-base font-medium text-gray-900 dark:text-white">Total
                                        Diskon</label>
                                    <input type="text" id="total_disc" value="0"
                                        class="bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-zinc-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-gray-400 cursor-not-allowed"
                                        disabled>
                                </div>

                                <div>
                                    <label for="ppn"
                                        class="block mb-4 text-base font-medium text-gray-900 dark:text-white">PPN
                                        (10%)</label>
                                    <input type="text" id="ppn" value="0"
                                        class="bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-zinc-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-gray-400 cursor-not-allowed"
                                        disabled>
                                </div>

                                <div>
                                    <label for="nett"
                                        class="block mb-4 text-base font-medium text-gray-900 dark:text-white">Nett</label>
                                    <input type="text" id="nett" value="0"
                                        class="bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-zinc-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-gray-400 cursor-not-allowed"
                                        disabled>
                                </div>
                            </div>
                        </div>

                        <div x-show="showAddProductModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm px-4 py-6">
                            <div class="w-full max-w-7xl overflow-hidden rounded-2xl bg-white dark:bg-zinc-900 shadow-xl h-[85vh] flex flex-col">
                                <div class="flex items-center justify-between border-b border-gray-200 dark:border-zinc-700 px-6 py-4 shrink-0">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Tambah Detail Produk</h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Cari produk dan pilih dari daftar.</p>
                                    </div>
                                    <button type="button" @click="showAddProductModal = false" class="cursor-pointer">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                                    </button>
                                </div>
                                <div class="flex-1 overflow-y-auto p-6 space-y-5">
                                    <div class="grid gap-4 sm:grid-cols-[1fr_auto] items-end">
                                        <div class="relative">
                                            <input type="text" placeholder="Cari produk..."
                                                class="w-full bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-600 focus:border-primary-600 p-2.5 pl-10 dark:bg-zinc-800 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500 text-xs">
                                            <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400 dark:text-gray-500"
                                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.5 5.5a7.5 7.5 0 0 0 10.5 10.5Z" />
                                            </svg>
                                        </div>
                                        <select class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-600 focus:border-primary-600 py-2.5 w-full sm:w-72 dark:bg-zinc-800 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500 text-xs">
                                            <option value="">Filter Kategori</option>
                                            <option value="gelas">Gelas</option>
                                            <option value="pipet">Pipet</option>
                                            <option value="tutup-botol">Tutup Botol</option>
                                        </select>
                                    </div>
                                    <div class="overflow-x-auto rounded-xl border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 mt-6">
                                        <table class="w-full text-left text-gray-900 dark:text-white border-collapse">
                                            <thead class="bg-gray-100 text-sm font-semibold uppercase text-gray-900 dark:bg-zinc-800 dark:text-gray-200">
                                                <tr>
                                                    <th class="border border-gray-300 dark:border-zinc-700 px-4 py-3">
                                                        <input type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                    </th>
                                                    <th class="border border-gray-300 dark:border-zinc-700 px-4 py-3">Kode Barang</th>
                                                    <th class="border border-gray-300 dark:border-zinc-700 px-4 py-3">Nama Produk</th>
                                                    <th class="border border-gray-300 dark:border-zinc-700 px-4 py-3">Qty Order</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800 text-sm">
                                                    <td class="border border-gray-300 dark:border-zinc-700 px-4 py-3">
                                                        <input type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                    </td>
                                                    <td class="border border-gray-300 dark:border-zinc-700 px-4 py-3">PROD-001</td>
                                                    <td class="border border-gray-300 dark:border-zinc-700 px-4 py-3">Produk A</td>
                                                    <td class="border border-gray-300 dark:border-zinc-700 px-4 py-3">
                                                        <input type="number" min="1" step="1" value="1"
                                                            class="w-20 bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-primary-600 focus:border-primary-600 block p-2 dark:bg-zinc-800 dark:border-gray-600 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                                    </td>
                                                </tr>
                                                <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800 text-sm">
                                                    <td class="border border-gray-300 dark:border-zinc-700 px-4 py-3">
                                                        <input type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                    </td>
                                                    <td class="border border-gray-300 dark:border-zinc-700 px-4 py-3">PROD-002</td>
                                                    <td class="border border-gray-300 dark:border-zinc-700 px-4 py-3">Produk B</td>
                                                    <td class="border border-gray-300 dark:border-zinc-700 px-4 py-3">
                                                        <input type="number" min="1" step="1" value="1"
                                                            class="w-20 bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-primary-600 focus:border-primary-600 block p-2 dark:bg-zinc-800 dark:border-gray-600 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                                    </td>
                                                </tr>
                                                <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800 text-sm">
                                                    <td class="border border-gray-300 dark:border-zinc-700 px-4 py-3">
                                                        <input type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                    </td>
                                                    <td class="border border-gray-300 dark:border-zinc-700 px-4 py-3">PROD-003</td>
                                                    <td class="border border-gray-300 dark:border-zinc-700 px-4 py-3">Produk C</td>
                                                    <td class="border border-gray-300 dark:border-zinc-700 px-4 py-3">
                                                        <input type="number" min="1" step="1" value="1"
                                                            class="w-20 bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-primary-600 focus:border-primary-600 block p-2 dark:bg-zinc-800 dark:border-gray-600 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="flex justify-end gap-3 p-6 border-t border-gray-200 dark:border-zinc-700 shrink-0 bg-zinc-50 dark:bg-zinc-900">
                                    <button type="button" @click="showAddProductModal = false"
                                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:border-zinc-700 dark:bg-zinc-800 dark:text-gray-300 dark:hover:bg-zinc-700">
                                        Batal
                                    </button>
                                    <button type="button" @click="showAddProductModal = false"
                                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                                        Pilih Produk
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <div class="flex justify-end gap-2 p-6 border-t border-gray-200 dark:border-zinc-700 shrink-0 bg-white dark:bg-zinc-900">
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
