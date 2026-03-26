<x-layouts::app :title="__('Good Receive Add')">
    <div class="flex flex-col items-center justify-between min-h-full gap-6 w-full">
        {{-- content --}}
        <div class="flex flex-col gap-6 w-full">
            {{-- header --}}
            <x-layouts::page-header :title="__('Good Receive')"
                description="Kelola data supplier secara terpusat untuk mendukung proses operasional dan pencatatan yang rapi.">
                <x-slot:breadcrumbs>
                    <li class="inline-flex items-center">
                        <a href="#" class="inline-flex items-center text-sm font-medium hover:text-fg-brand">
                            Purchasing
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center space-x-1.5">
                            <svg class="w-3.5 h-3.5 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="m9 5 7 7-7 7" />
                            </svg>
                            <a href="#"
                                class="inline-flex items-center text-sm font-medium hover:text-fg-brand">Transaction</a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center space-x-1.5">
                            <svg class="w-3.5 h-3.5 rtl:rotate-180 " aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                                viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="m9 5 7 7-7 7" />
                            </svg>
                            <span class="inline-flex items-center text-sm font-medium">Good Receive</span>
                        </div>
                    </li>
                </x-slot:breadcrumbs>
            </x-layouts::page-header>
        </div>

        <div
            class="w-full overflow-hidden rounded-md shadow dark:border bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 p-4 items-center justify-between gap-4">
            <div class="w-full">
                <h2 class="mb-6 text-xl font-bold text-gray-900 dark:text-white">Buat Purchase Order Baru
                </h2>
                <form action="#">
                    <div class="grid gap-5 sm:grid-cols-2 sm:gap-x-12 sm:gap-y-6">
                        <div class="w-full">
                            <label for="po_no"
                                class="block mb-3 text-lg font-medium text-gray-900 dark:text-white">GR No</label>
                            <input type="text" name="po_no" id="po_no"
                                class="bg-gray-100 border border-gray-300 text-gray-900 text-base rounded-md focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-zinc-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-gray-400 cursor-not-allowed"
                                placeholder="Auto Generated" disabled>
                        </div>

                        <div class="w-full">
                            <label for="po_date"
                                class="block mb-3 text-lg font-medium text-gray-900 dark:text-white">Date</label>
                            <input type="date" name="po_date" id="po_date"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-md focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-zinc-800 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                required="">
                        </div>

                        <div class="w-full">
                            <label for="supplier"
                                class="block mb-3 text-lg font-medium text-gray-900 dark:text-white">Supplier</label>
                            <select id="supplier" name="supplier"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-md focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-zinc-800 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
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
                                class="block mb-3 text-lg font-medium text-gray-900 dark:text-white">PO No</label>
                            <select id="supplier" name="supplier"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-md focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-zinc-800 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                required="">
                                <option value="">-- Pilih PO No --</option>
                                <option value="PO-001">PO-001</option>
                                <option value="PO-002">PO-002</option>
                                <option value="PO-003">PO-003</option>
                                <option value="PO-004">PO-004</option>
                                <option value="PO-005">PO-005</option>
                            </select>
                        </div>

                        <div class="w-full">
                            <label for="warehouse"
                                class="block mb-3 text-lg font-medium text-gray-900 dark:text-white">Warehouse</label>
                            <select id="warehouse" name="warehouse"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-md focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-zinc-800 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                required="">
                                <option value="">-- Pilih Gudang --</option>
                                <option value="WH-001">Gudang Pusat</option>
                                <option value="WH-002">Gudang Cabang 1</option>
                                <option value="WH-003">Gudang Cabang 2</option>
                                <option value="WH-004">Gudang Distribusi</option>
                            </select>
                        </div>

                        <div class="w-full">
                            <label for="tax"
                                class="block mb-3 text-lg font-medium text-gray-900 dark:text-white">Tax</label>
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
                    <div>
                        <label for="note"
                            class="block mb-3 mt-6 text-lg font-medium text-gray-900 dark:text-white">Note</label>
                        <textarea name="note" id="note" rows="4"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-md focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-zinc-800 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                            placeholder="Masukkan catatan atau keterangan tambahan..."></textarea>
                    </div>
                </form>

                {{-- tabel --}}
                <div class="mt-12">
                    <div class="w-full grid grid-cols-1 sm:grid-cols-2  gap-6 mb-4">
                        <!-- Kolom kiri: Judul -->
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">Detail Produk</h3>
                        </div>

                        <!-- Kolom kanan: Selected items dan search -->
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-end gap-4">
                            <span class="text-base font-normal text-gray-500 dark:text-gray-400">
                                Selected
                                <span class="font-semibold text-gray-900 dark:text-white text-base">5 Item</span>
                                of
                                <span class="font-semibold text-gray-900 dark:text-white text-base">1000
                                    Items</span>
                            </span>
                            <div class="w-full sm:w-md relative">
                                <input type="text" placeholder="Cari produk..."
                                    class="w-full bg-gray-50 border border-gray-300 text-gray-900 rounded-md focus:ring-primary-600 focus:border-primary-600 p-2.5 pl-10 dark:bg-zinc-800 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500 text-base"
                                    id="searchInput">
                                <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400 dark:text-gray-500"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.5 5.5a7.5 7.5 0 0 0 10.5 10.5Z" />
                                </svg>
                            </div>
                        </div>
                    </div>


                    <div class="overflow-x-auto">
                        <table class="w-full text-base text-left text-gray-900 dark:text-white my-2">
                            <thead
                                class="text-lg font-bold text-gray-900 uppercase bg-gray-200 dark:bg-zinc-700 dark:text-white">
                                <tr>
                                    <th scope="col" class="px-4 py-3">No</th>
                                    <th scope="col" class="px-4 py-3">Nama Produk</th>
                                    <th scope="col" class="px-4 py-3">Barcode Produk</th>
                                    <th scope="col" class="px-4 py-3">Kategori Produk</th>
                                    <th scope="col" class="px-4 py-3">Satuan</th>
                                    <th scope="col" class="px-4 py-3">Qty PO</th>
                                    <th scope="col" class="px-4 py-3">Qty Outs</th>
                                    <th scope="col" class="px-4 py-3">Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="border-b dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-zinc-800">
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">1</td>
                                    <td class="px-4 py-3">Produk A</td>
                                    <td class="px-4 py-3">PROD-001</td>
                                    <td class="px-4 py-3">Elektronik</td>
                                    <td class="px-4 py-3">PCS</td>
                                    <td class="px-4 py-3">10</td>
                                    <td class="px-4 py-3">2</td>
                                    <td class="px-4 py-3">
                                        <input type="text" name="qty_1" id="qty_1"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-md focus:ring-primary-600 focus:border-primary-600 block w-20 p-2 dark:bg-zinc-800 dark:border-gray-600 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                            placeholder="0">
                                    </td>
                                </tr>
                                <tr class="border-b dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-zinc-800">
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">2</td>
                                    <td class="px-4 py-3">Produk B</td>
                                    <td class="px-4 py-3">PROD-002</td>
                                    <td class="px-4 py-3">Alat Tulis</td>
                                    <td class="px-4 py-3">BOX</td>
                                    <td class="px-4 py-3">5</td>
                                    <td class="px-4 py-3">1</td>
                                    <td class="px-4 py-3">
                                        <input type="text" name="qty_2" id="qty_2"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-md focus:ring-primary-600 focus:border-primary-600 block w-20 p-2 dark:bg-zinc-800 dark:border-gray-600 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                            placeholder="0">
                                    </td>
                                </tr>
                                <tr class="border-b dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-zinc-800">
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">3</td>
                                    <td class="px-4 py-3">Produk C</td>
                                    <td class="px-4 py-3">PROD-003</td>
                                    <td class="px-4 py-3">Bahan Baku</td>
                                    <td class="px-4 py-3">PCS</td>
                                    <td class="px-4 py-3">15</td>
                                    <td class="px-4 py-3">3</td>
                                    <td class="px-4 py-3">
                                        <input type="text" name="qty_3" id="qty_3"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-md focus:ring-primary-600 focus:border-primary-600 block w-20 p-2 dark:bg-zinc-800 dark:border-gray-600 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                            placeholder="0">
                                    </td>
                                </tr>
                                <tr class="border-b dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-zinc-800">
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">4</td>
                                    <td class="px-4 py-3">Produk D</td>
                                    <td class="px-4 py-3">PROD-004</td>
                                    <td class="px-4 py-3">Kemasan</td>
                                    <td class="px-4 py-3">BOX</td>
                                    <td class="px-4 py-3">8</td>
                                    <td class="px-4 py-3">2</td>
                                    <td class="px-4 py-3">
                                        <input type="text" name="qty_4" id="qty_4"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-md focus:ring-primary-600 focus:border-primary-600 block w-20 p-2 dark:bg-zinc-800 dark:border-gray-600 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                            placeholder="0">
                                    </td>
                                </tr>
                                <tr class="border-b dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-zinc-800">
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">5</td>
                                    <td class="px-4 py-3">Produk E</td>
                                    <td class="px-4 py-3">PROD-005</td>
                                    <td class="px-4 py-3">Hardware</td>
                                    <td class="px-4 py-3">PCS</td>
                                    <td class="px-4 py-3">20</td>
                                    <td class="px-4 py-3">5</td>
                                    <td class="px-4 py-3">
                                        <input type="text" name="qty_5" id="qty_5"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-md focus:ring-primary-600 focus:border-primary-600 block w-20 p-2 dark:bg-zinc-800 dark:border-gray-600 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                            placeholder="0">
                                    </td>
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
            </div>
        </div>

        {{-- footer --}}
        <div
            class="grid grid-cols-1 w-full overflow-hidden rounded-md dark:border bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 p-4 gap-4">
            <div class="flex flex-col sm:flex-row items-center justify-end gap-4 w-full">
                <button type="button"
                    class=" order-last sm:order-first text-white bg-[#0f1419] hover:bg-[#0f1419]/90 focus:ring-4 focus:outline-none focus:ring-[#0f1419]/50 box-border border border-transparent font-medium leading-5 rounded-base text-sm px-6 py-4 text-center inline-flex items-center gap-2 dark:hover:bg-[#24292F] dark:focus:ring-[#24292F]/55 w-full sm:w-32">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>

                    Cancel
                </button>

                <button type="button" onclick="location.href='{{ Route('purchases.master.supplier') }}'"
                    class="text-white bg-[#0f1419] hover:bg-[#0f1419]/90 focus:ring-4 focus:outline-none focus:ring-[#0f1419]/50 box-border border border-transparent font-medium leading-5 rounded-base text-sm px-6 py-4 text-center inline-flex items-center gap-2 dark:hover:bg-[#24292F] dark:focus:ring-[#24292F]/55 w-full sm:w-32">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                    Save
                </button>
            </div>
        </div>
    </div>
</x-layouts::app>
