<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-950">
    <flux:sidebar sticky collapsible
        class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 border lg:w-90">
        <flux:sidebar.header>
            <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
            {{-- <flux:sidebar.collapse class="lg:hidden" /> --}}

            <flux:sidebar.collapse
                class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
        </flux:sidebar.header>

        <flux:sidebar.nav>
            <flux:sidebar.group :heading="__('Platform')" class="grid">
                <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')"
                    wire:navigate>
                    {{ __('Dashboard') }}
                </flux:sidebar.item>
            </flux:sidebar.group>

            {{-- purchasing --}}
            <flux:sidebar.group icon="credit-card" href="#" expandable heading="Pembelian" class="grid"
                :expanded="request()->routeIs('purchases.*')">
                <flux:sidebar.group href="#" expandable heading="Master" class="grid"
                    :expanded="request()->routeIs('purchases.master.*')">
                    <flux:sidebar.item href="{{ route('purchases.master.supplier') }}"
                        :current="request()->routeIs('purchases.master.supplier')" wire:navigate>Supplier
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group href="#" expandable heading="Transaksi" class="grid"
                    :expanded="request()->routeIs('purchases.transaction.*')">
                    <flux:sidebar.item href="{{ route('purchases.transaction.purchase-order') }}" wire:navigate>Pesanan
                        Pembelian</flux:sidebar.item>
                    <flux:sidebar.item href="{{ route('purchases.transaction.good-receive') }}" wire:navigate>Penerimaan
                        Barang</flux:sidebar.item>
                    <flux:sidebar.item href="{{ route('purchases.transaction.purchase-invoice') }}" wire:navigate>Faktur
                        Pembelian</flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group href="#" expandable heading="Retur" class="grid"
                    :expanded="request()->routeIs('purchases.return.*')">
                    <flux:sidebar.item href="{{ route('purchases.return.purchase-return') }}" :current="request()->routeIs('purchases.return.purchase-return*')" wire:navigate>Retur Pembelian</flux:sidebar.item>
                    <flux:sidebar.item href="{{ route('purchases.return.purchase-return-invoice') }}" :current="request()->routeIs('purchases.return.purchase-return-invoice*')" wire:navigate>Faktur Retur Pembelian</flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group href="#" expandable heading="Laporan" class="grid"
                    :expanded="request()->routeIs('purchases.report.*')">
                    <flux:sidebar.item href="{{ route('purchases.report.unfinished-purchase-order') }}"
                        :current="request()->routeIs('purchases.report.unfinished-purchase-order')" wire:navigate>PO Belum Selesai</flux:sidebar.item>
                    <flux:sidebar.item href="{{ route('purchases.report.unfinished-purchase-invoice') }}"
                        :current="request()->routeIs('purchases.report.unfinished-purchase-invoice')" wire:navigate>Faktur Pembelian Belum Lunas</flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.group>

            {{-- inventory --}}
            <flux:sidebar.group icon="rectangle-stack" href="#" expandable heading="Persediaan" class="grid"
                :expanded="request()->routeIs('inventory.*')">
                <flux:sidebar.group href="#" expandable heading="Master Persediaan" class="grid"
                    :expanded="request()->routeIs('inventory.product.*')">
                    <flux:sidebar.item href="{{ route('inventory.product.productMaster') }}" wire:navigate>Master
                        Produk
                    </flux:sidebar.item>
                    <flux:sidebar.item href="{{ route('inventory.product.productCategory') }}" wire:navigate>Kategori
                        Produk
                    </flux:sidebar.item>
                    <flux:sidebar.item href="{{ route('inventory.product.uom') }}" wire:navigate>Satuan
                    </flux:sidebar.item>
                    <flux:sidebar.item href="{{ route('inventory.product.warehouse') }}" wire:navigate>Gudang
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group href="#" expandable heading="Transaksi Persediaan" class="grid"
                    :expanded="request()->routeIs('inventory.transaction.*')">
                    <flux:sidebar.item href="{{ route('inventory.transaction.transfer-stock') }}" wire:navigate>
                        Transfer Stok</flux:sidebar.item>
                    <flux:sidebar.item href="{{ route('inventory.transaction.adjustment-in') }}" wire:navigate>
                        Penyesuaian Stok Masuk</flux:sidebar.item>
                    <flux:sidebar.item href="{{ route('inventory.transaction.adjustment-out') }}" wire:navigate>
                        Penyesuaian Stok Keluar</flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group href="#" expandable heading="Laporan" class="grid"
                    :expanded="request()->routeIs('inventory.report.*')">
                    <flux:sidebar.item href="{{ route('inventory.report.stock-balance') }}" wire:navigate>Saldo Stok
                    </flux:sidebar.item>
                    <flux:sidebar.item href="{{ route('inventory.report.stock-card') }}"
                        :current="request()->routeIs('inventory.report.stock-card')" wire:navigate>Kartu Stok</flux:sidebar.item>
                    <flux:sidebar.item href="{{ route('inventory.report.stock-movement') }}"
                        :current="request()->routeIs('inventory.report.stock-movement')" wire:navigate>Pergerakan Stok</flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.group>

            {{-- sales --}}
            <flux:sidebar.group icon="presentation-chart-line" href="#" expandable heading="Penjualan"
                class="grid" :expanded="request()->routeIs('sales.*')">
                <flux:sidebar.group href="#" expandable heading="Master" class="grid"
                    :expanded="request()->routeIs('sales.master.*')">
                    <flux:sidebar.item href="{{ route('sales.master.customer') }}" wire:navigate>Pelanggan
                    </flux:sidebar.item>
                    <flux:sidebar.item href="{{ route('sales.master.customer-address-code') }}" wire:navigate>Kode Alamat
                    </flux:sidebar.item>
                    <flux:sidebar.item href="{{ route('sales.master.salesman') }}" wire:navigate>Tenaga Penjualan</flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group href="#" expandable heading="Transaksi" class="grid"
                    :expanded="request()->routeIs('sales.transaction.*')">
                    <flux:sidebar.item href="{{ route('sales.transaction.salesCanvas') }}" wire:navigate>Penjualan Kanvas</flux:sidebar.item>
                    <flux:sidebar.item href="{{ route('sales.transaction.salesPreOrder') }}" wire:navigate>Pesanan Awal</flux:sidebar.item>
                    <flux:sidebar.item href="{{ route('sales.transaction.salesOrder') }}" wire:navigate>Pesanan Penjualan</flux:sidebar.item>
                    <flux:sidebar.item href="{{ route('sales.transaction.deliveryOrder') }}" wire:navigate>Surat Jalan</flux:sidebar.item>
                    <flux:sidebar.item href="{{ route('sales.transaction.salesInvoice') }}" wire:navigate>Faktur Penjualan</flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group href="#" expandable heading="Retur" class="grid"
                    :expanded="request()->routeIs('sales.return.*')">
                    <flux:sidebar.item href="{{ route('sales.return.sales-return') }}" :current="request()->routeIs('sales.return.sales-return*')" wire:navigate>Retur Penjualan</flux:sidebar.item>
                    <flux:sidebar.item href="{{ route('sales.return.sales-return-invoice') }}" :current="request()->routeIs('sales.return.sales-return-invoice*')" wire:navigate>Faktur Retur Penjualan</flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group href="#" expandable heading="Laporan" class="grid"
                    :expanded="request()->routeIs('sales.report.*')">
                    <flux:sidebar.item href="{{ route('sales.report.po-outstanding') }}" :current="request()->routeIs('sales.report.po-outstanding')" wire:navigate>SO Belum Selesai</flux:sidebar.item>
                    <flux:sidebar.item href="{{ route('sales.report.invoice-outstanding') }}" :current="request()->routeIs('sales.report.invoice-outstanding')" wire:navigate>Faktur Penjualan Belum Lunas</flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.group>

            {{-- finance --}}
            <flux:sidebar.group icon="presentation-chart-line" href="#" expandable heading="Keuangan"
                class="grid" :expanded="request()->routeIs('finance.*')">
                <flux:sidebar.group href="#" expandable heading="Master" class="grid"
                    :expanded="request()->routeIs('finance.master.*')">
                    <flux:sidebar.item href="{{ route('finance.master.chart-of-accounts') }}" wire:navigate>Daftar Akun
                    </flux:sidebar.item>
                    <flux:sidebar.item href="{{ route('finance.master.bank-accounts') }}" wire:navigate>Rekening Bank
                    </flux:sidebar.item>
                    <flux:sidebar.item href="#" wire:navigate>Termin Pembayaran</flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group href="#" expandable heading="Transaksi" class="grid"
                    :expanded="request()->routeIs('finance.transaction.*')">
                    <flux:sidebar.item href="{{ route('finance.transaction.ap-payment') }}" wire:navigate>Pembayaran
                        Utang
                    </flux:sidebar.item>
                    <flux:sidebar.item href="{{ route('finance.transaction.expense') }}"
                        :current="request()->routeIs('finance.transaction.expense')" wire:navigate>Pengeluaran
                    </flux:sidebar.item>
                    <flux:sidebar.item href="{{ route('finance.transaction.ar-dp-payment') }}"
                        :current="request()->routeIs('finance.transaction.ar-dp-payment')" wire:navigate>Penerimaan DP Pelanggan
                    </flux:sidebar.item>
                    <flux:sidebar.item href="{{ route('finance.transaction.ar-payment') }}" :current="request()->routeIs('finance.transaction.ar-payment')" wire:navigate>Pembayaran Piutang</flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group href="#" expandable heading="Laporan" class="grid"
                    :expanded="request()->routeIs('finance.report.*')">
                    <flux:sidebar.item href="{{ route('finance.report.journal-entry') }}" wire:navigate>Entri Jurnal
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.group>

            {{-- user --}}
            <flux:sidebar.group icon="presentation-chart-line" href="#" expandable heading="Pengguna"
                class="grid" :expanded="request()->routeIs('user.*')">
                <flux:sidebar.group href="#" expandable heading="Aksi" class="grid"
                    :expanded="request()->routeIs('user.action.*')">
                    <flux:sidebar.item href="{{ route('user.action.user') }}"
                        :current="request()->routeIs('user.action.user')" wire:navigate>Pengguna</flux:sidebar.item>
                    <flux:sidebar.item href="{{ route('user.action.role') }}"
                        :current="request()->routeIs('user.action.role')" wire:navigate>Peran Pengguna
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.group>
        </flux:sidebar.nav>

        <flux:spacer />

        <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
    </flux:sidebar>


    <!-- Mobile User Menu -->
    <flux:header class="lg:hidden">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        <flux:spacer />

        <flux:dropdown position="top" align="end">
            <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />

            <flux:menu>
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <flux:avatar :name="auth()->user()->name" :initials="auth()->user()->initials()" />

                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.radio.group>
                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                        {{ __('Settings') }}
                    </flux:menu.item>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                        class="w-full cursor-pointer" data-test="logout-button">
                        {{ __('Log Out') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:header>

    {{ $slot }}

    @fluxScripts
</body>

</html>
