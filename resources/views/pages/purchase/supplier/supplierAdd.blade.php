<x-layouts::app :title="__('Supplier Categories Add')">
    <div class="flex flex-col items-center justify-between min-h-full gap-6 w-full">
        {{-- content --}}
        <div class="flex flex-col gap-6 w-full">
            {{-- header --}}
            <x-layouts::page-header :title="__('Supplier')"
                description="Kelola data supplier secara terpusat untuk mendukung proses operasional dan pencatatan yang rapi.">
                <x-slot:breadcrumbs>
                    <li class="inline-flex items-center">
                        <a href="#" class="inline-flex items-center text-base font-medium hover:text-fg-brand">
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
                                class="inline-flex items-center text-base font-medium hover:text-fg-brand">Master</a>
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
                            <span class="inline-flex items-center text-base font-medium">Supplier</span>
                        </div>
                    </li>
                </x-slot:breadcrumbs>
            </x-layouts::page-header>

            {{-- form --}}
            <div
                class="w-full overflow-hidden rounded-md shadow dark:border bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 p-4 items-center justify-between gap-4">
                <div class="w-full">
                    <h2 class="mb-6 text-xl font-bold text-gray-900 dark:text-white">Tambah Data Supplier</h2>
                    <form action="#">
                        <div class="grid gap-5 sm:grid-cols-2 sm:gap-x-12 sm:gap-y-6">
                            <div class="w-full">
                                <label for="brand"
                                    class="block mb-3 text-lg font-medium text-gray-900 dark:text-white">Supplier
                                    Code</label>
                                <input type="text" name="brand" id="brand"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-md focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-zinc-800 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                    placeholder="supplier code" required="">
                            </div>
                            <div class="w-full">
                                <label for="price"
                                    class="block mb-3 text-lg font-medium text-gray-900 dark:text-white">Supplier
                                    Name</label>
                                <input type="text" name="price" id="price"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-md focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-zinc-800 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                    placeholder="supplier name" required="">
                            </div>
                            <div class="w-full">
                                <label for="price"
                                    class="block mb-3 text-lg font-medium text-gray-900 dark:text-white">Phone
                                    Number</label>
                                <input type="number" name="price" id="price"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-md focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-zinc-800 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                    placeholder="0813..." required="">
                            </div>
                            <div>
                                <label for="category"
                                    class="block mb-3 text-lg font-medium text-gray-900 dark:text-white">Status</label>
                                <label class="inline-flex items-center cursor-pointer">
                                    <span class="select-none text-base font-medium text-heading">Inactive</span>
                                    <input type="checkbox" value="" class="sr-only peer">
                                    <div
                                        class="relative mx-3 w-9 h-5 bg-neutral-quaternary peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-brand-soft dark:peer-focus:ring-brand-soft rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-buffer after:content-[''] after:absolute after:top-0.5 after:start-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-brand">
                                    </div>
                                    <span class="select-none text-base font-medium text-heading">Active</span>
                                </label>
                            </div>
                        </div>
                        <div class="w-full">
                            <label for="alamat"
                                class="block mb-3 mt-6 text-lg font-medium text-gray-900 dark:text-white">Detail
                                Alamat</label>
                            <textarea name="alamat" id="alamat" rows="4"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-md focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-zinc-800 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                placeholder="Masukkan detail alamat lengkap..." required=""></textarea>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- footer --}}
        <div
            class="grid grid-cols-1 w-full overflow-hidden rounded-md dark:border bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 p-4 gap-4">
            <div class="flex flex-col sm:flex-row items-center justify-end gap-4 w-full">
                <button type="button"
                    class=" order-last sm:order-first text-white bg-[#0f1419] hover:bg-[#0f1419]/90 focus:ring-4 focus:outline-none focus:ring-[#0f1419]/50 box-border border border-transparent font-medium leading-5 rounded-base text-base px-6 py-4 text-center inline-flex items-center gap-2 dark:hover:bg-[#24292F] dark:focus:ring-[#24292F]/55 w-full sm:w-32">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>

                    Cancel
                </button>

                <button type="button" onclick="location.href='{{ Route('purchases.master.supplier') }}'"
                    class="text-white bg-[#0f1419] hover:bg-[#0f1419]/90 focus:ring-4 focus:outline-none focus:ring-[#0f1419]/50 box-border border border-transparent font-medium leading-5 rounded-base text-base px-6 py-4 text-center inline-flex items-center gap-2 dark:hover:bg-[#24292F] dark:focus:ring-[#24292F]/55 w-full sm:w-32">
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
