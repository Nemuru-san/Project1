<x-layouts::app :title="__('Supplier Categories Add')">

    <div class="container flex flex-col items-center justify-between min-h-full gap-6">
        {{-- content --}}
        <div class="flex flex-col gap-6 w-full">
            {{-- header --}}
            <div
                class="grid sm:grid-cols-1 lg:grid-cols-2 w-full overflow-hidden rounded-md shadow dark:border bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 p-4 items-center justify-between gap-4">
                <div class="flex flex-col gap-2">
                    <nav class="flex" aria-label="Breadcrumb">
                        <ol
                            class="inline-flex items-center space-x-1 md:space-x-2 rtl:space-x-reverse text-sm font-light text-body-subtle">
                            <li class="inline-flex items-center">
                                <a href="#"
                                    class="inline-flex items-center text-sm font-medium hover:text-fg-brand">
                                    Purchasing
                                </a>
                            </li>
                            <li>
                                <div class="flex items-center space-x-1.5">
                                    <svg class="w-3.5 h-3.5 rtl:rotate-180" aria-hidden="true"
                                        xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                                        viewBox="0 0 24 24">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                            stroke-width="2" d="m9 5 7 7-7 7" />
                                    </svg>
                                    <a href="#"
                                        class="inline-flex items-center text-sm font-medium hover:text-fg-brand">Master</a>
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
                                    <span class="inline-flex items-center text-sm font-medium">Supplier Category</span>
                                </div>
                            </li>
                        </ol>
                    </nav>

                    <p class="lg:text-2xl font-bold">Supplier Categories</p>
                    <p class="font-light text-body-subtle">Atur kategori supplier agar data lebih rapi, mudah dicari,
                        dan
                        terkelola dengan baik.
                    </p>
                </div>

                {{-- <div class="flex flex-col sm:flex-row items-center justify-end gap-4">
                    <button type="button"
                        class="text-white bg-[#0f1419] hover:bg-[#0f1419]/90 focus:ring-4 focus:outline-none focus:ring-[#0f1419]/50 box-border border border-transparent font-medium leading-5 rounded-base text-sm px-4 py-2.5 text-center inline-flex items-center justify-center gap-2 dark:hover:bg-[#24292F] dark:focus:ring-[#24292F]/55 w-full sm:w-42"
                        onclick="location.href='{{ Route('purchasesupplier-categories-add') }}'">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Create New
                    </button>

                    <button type="button"
                        class="text-white bg-[#0f1419] hover:bg-[#0f1419]/90 focus:ring-4 focus:outline-none focus:ring-[#0f1419]/50 box-border border border-transparent font-medium leading-5 rounded-base text-sm px-4 py-2.5 text-center inline-flex items-center justify-center gap-2 dark:hover:bg-[#24292F] dark:focus:ring-[#24292F]/55 w-full sm:w-42">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        Refresh
                    </button>
                </div> --}}
            </div>

            {{-- form --}}
            <div
                class="w-full overflow-hidden rounded-md shadow dark:border bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 p-4 items-center justify-between gap-4">
                <div class="w-full">
                    <h2 class="mb-4 text-xl font-bold text-gray-900 dark:text-white">Tambah Data Supplier Category</h2>
                    <form action="#">
                        <div class="grid gap-4 sm:grid-cols-2 sm:gap-6">
                            <div class="w-full">
                                <label for="brand"
                                    class="block mb-3 text-14 font-medium text-gray-900 dark:text-white">Supplier
                                    Category Code</label>
                                <input type="text" name="brand" id="brand"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-md focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                    placeholder="supplier cateogory code" required="">
                            </div>
                            <div class="w-full">
                                <label for="price"
                                    class="block mb-3 text-14 font-medium text-gray-900 dark:text-white">Supplier
                                    Category Name</label>
                                <input type="text" name="price" id="price"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-md focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                    placeholder="supplier category name" required="">
                            </div>
                            <div>
                                <label for="category"
                                    class="block mb-3 text-14 font-medium text-gray-900 dark:text-white">Payable
                                    Account</label>
                                <select id="category"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-md focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                    <option selected="">BCA 1</option>
                                    <option value="TV">BCA 2</option>
                                    <option value="PC">BCA 3</option>
                                </select>
                            </div>
                            <div>
                                <label for="category"
                                    class="block mb-3 text-14 font-medium text-gray-900 dark:text-white">Tax
                                    Account</label>
                                <select id="category"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-md focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                    <option selected="">Tax 1</option>
                                    <option value="TV">Tax 2</option>
                                    <option value="PC">Tax 3</option>
                                </select>
                            </div>

                            <div>
                                <label for="category"
                                    class="block mb-3 text-14 font-medium text-gray-900 dark:text-white">Cash
                                    Account</label>
                                <select id="category"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-md focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                    <option selected="">Cash 1</option>
                                    <option value="TV">Cash 2</option>
                                    <option value="PC">Cash 3</option>
                                </select>
                            </div>

                            <div>
                                <label for="category"
                                    class="block mb-3 text-14 font-medium text-gray-900 dark:text-white">Status</label>
                                <label class="inline-flex items-center cursor-pointer">
                                    <span class="select-none text-sm font-medium text-heading">Inactive</span>
                                    <input type="checkbox" value="" class="sr-only peer">
                                    <div
                                        class="relative mx-3 w-9 h-5 bg-neutral-quaternary peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-brand-soft dark:peer-focus:ring-brand-soft rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-buffer after:content-[''] after:absolute after:top-0.5 after:start-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-brand">
                                    </div>
                                    <span class="select-none text-sm font-medium text-heading">Active</span>
                                </label>

                            </div>
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
                    class=" order-last sm:order-first text-white bg-[#0f1419] hover:bg-[#0f1419]/90 focus:ring-4 focus:outline-none focus:ring-[#0f1419]/50 box-border border border-transparent font-medium leading-5 rounded-base text-sm px-6 py-4 text-center inline-flex items-center gap-2 dark:hover:bg-[#24292F] dark:focus:ring-[#24292F]/55 w-full sm:w-32">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>

                    Cancel
                </button>

                <button type="button" onclick="location.href='{{ Route('supplier-categories') }}'"
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
