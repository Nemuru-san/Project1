<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-950">
    <flux:sidebar sticky collapsible
        class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 border lg:w-70">
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
            <flux:sidebar.group icon="credit-card" href="#" expandable heading="Purchasing" class="grid"
                :expanded="request()->routeIs('purchases.*')">
                <flux:sidebar.group href="#" expandable heading="Master" class="grid"
                    :expanded="request()->routeIs('purchases.master.*')">
                    <flux:sidebar.item href="{{ route('purchases.master.supplier') }}"
                        :current="request()->routeIs('purchases.master.supplier')" wire:navigate>Supplier
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group href="#" expandable heading="Transaction" class="grid"
                    :expanded="request()->routeIs('purchases.transaction.*')">
                    <flux:sidebar.item href="{{ route('purchases.transaction.purchase-order') }}" wire:navigate>Purchase
                        Order</flux:sidebar.item>
                    <flux:sidebar.item href="{{ route('purchases.transaction.good-receive') }}" wire:navigate>Good
                        Receive</flux:sidebar.item>
                    <flux:sidebar.item href="{{ route('purchases.transaction.purchase-invoice') }}" wire:navigate>
                        Purchase Invoice</flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group href="#" expandable heading="Return" class="grid"
                    :expanded="request()->routeIs('purchases.transaction.*')">
                    <flux:sidebar.item href="#" wire:navigate>Purchase Return</flux:sidebar.item>
                    <flux:sidebar.item href="#" wire:navigate>Purchase Return Invoice</flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group href="#" expandable heading="Report" class="grid"
                    :expanded="request()->routeIs('purchases.report.*')">
                    <flux:sidebar.item href="#">PO Outstanding</flux:sidebar.item>
                    <flux:sidebar.item href="#">Invoice Outstanding</flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.group>

            {{-- inventory --}}
            <flux:sidebar.group icon="rectangle-stack" href="#" expandable heading="Inventory" class="grid"
                :expanded="request()->routeIs('inventory.*')">
                <flux:sidebar.group href="#" expandable heading="Inventory Master" class="grid"
                    :expanded="request()->routeIs('inventory.product.*')">
                    <flux:sidebar.item href="{{ route('inventory.product.productMaster') }}" wire:navigate>Product
                        Master
                    </flux:sidebar.item>
                    <flux:sidebar.item href="{{ route('inventory.product.productCategory') }}" wire:navigate>Product
                        Category
                    </flux:sidebar.item>
                    <flux:sidebar.item href="{{ route('inventory.product.uom') }}" wire:navigate>UOM</flux:sidebar.item>
                    <flux:sidebar.item href="{{ route('inventory.product.warehouse') }}" wire:navigate>Warehouse
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group href="#" expandable heading="Inventory Transaction" class="grid"
                    :expanded="request()->routeIs('inventory.transaction.*')">
                    <flux:sidebar.item href="#" wire:navigate>Transfer Stock</flux:sidebar.item>
                    <flux:sidebar.item href="#" wire:navigate>Adjustment In</flux:sidebar.item>
                    <flux:sidebar.item href="#" wire:navigate>Adjustment Out</flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group href="#" expandable heading="Report" class="grid"
                    :expanded="request()->routeIs('inventory.report.*')">
                    <flux:sidebar.item href="#" wire:navigate>Stock Balance</flux:sidebar.item>
                    <flux:sidebar.item href="#" wire:navigate>Stock Card</flux:sidebar.item>
                    <flux:sidebar.item href="#" wire:navigate>Stock Movement</flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.group>

            {{-- sales --}}
            <flux:sidebar.group icon="presentation-chart-line" href="#" expandable heading="Sales" class="grid"
                :expanded="request()->routeIs('sales.*')">
                <flux:sidebar.group href="#" expandable heading="Master" class="grid"
                    :expanded="request()->routeIs('sales.master.*')">
                    <flux:sidebar.item href="#" wire:navigate>Suplier Category</flux:sidebar.item>
                    <flux:sidebar.item href="#" wire:navigate>Supplier</flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group href="#" expandable heading="Transaction" class="grid"
                    :expanded="request()->routeIs('sales.transaction.*')">
                    <flux:sidebar.item href="#" wire:navigate>Sales Order</flux:sidebar.item>
                    <flux:sidebar.item href="#" wire:navigate>Delivery Order</flux:sidebar.item>
                    <flux:sidebar.item href="#" wire:navigate>Sales Invoice</flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group href="#" expandable heading="Report" class="grid"
                    :expanded="request()->routeIs('sales.report.*')">
                    <flux:sidebar.item href="#" wire:navigate>PO Outstanding</flux:sidebar.item>
                    <flux:sidebar.item href="#" wire:navigate>Invoice Outstanding</flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.group>

            {{-- finance --}}
            <flux:sidebar.group icon="presentation-chart-line" href="#" expandable heading="Finance"
                class="grid" :expanded="request()->routeIs('finance.*')">
                <flux:sidebar.group href="#" expandable heading="Transaction" class="grid"
                    :expanded="request()->routeIs('finance.transaction.*')">
                    <flux:sidebar.item href="#" wire:navigate>AP Payment</flux:sidebar.item>
                    <flux:sidebar.item href="#" wire:navigate>AR Payment</flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.group>

            {{-- user --}}
            <flux:sidebar.group icon="presentation-chart-line" href="#" expandable heading="User" class="grid"
                :expanded="request()->routeIs('user.*')">
                <flux:sidebar.group href="#" expandable heading="Action" class="grid"
                    :expanded="request()->routeIs('user.action.*')">
                    <flux:sidebar.item href="{{ route('user.action.user') }}"
                        :current="request()->routeIs('user.action.user')" wire:navigate>Users</flux:sidebar.item>
                    <flux:sidebar.item href="{{ route('user.action.role') }}"
                        :current="request()->routeIs('user.action.role')" wire:navigate>
                        Role User
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
