<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">
    <flux:sidebar sticky collapsible="mobile"
        class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 border lg:w-80">
        <flux:sidebar.header>
            <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
            <flux:sidebar.collapse class="lg:hidden" />
        </flux:sidebar.header>

        <flux:sidebar.nav>
            {{-- <flux:sidebar.group :heading="__('Platform')" class="grid">
                <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')"
                    wire:navigate>
                    {{ __('Dashboard') }}
                </flux:sidebar.item>
            </flux:sidebar.group> --}}

            {{-- purchasing --}}
            <flux:sidebar.group icon="credit-card" href="#" expandable heading="Purchasing" class="grid">
                <flux:sidebar.group href="#" expandable heading="Master" class="grid">
                    <flux:sidebar.item href="{{ Route('supplier=categories') }}">Suplier Category</flux:sidebar.item>
                    <flux:sidebar.item href="#">Supplier</flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group href="#" expandable heading="Transaction" class="grid">
                    <flux:sidebar.item href="#">Purchase Order</flux:sidebar.item>
                    <flux:sidebar.item href="#">Good Receive</flux:sidebar.item>
                    <flux:sidebar.item href="#">Purchase Invoice</flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group href="#" expandable heading="Report" class="grid">
                    <flux:sidebar.item href="#">PO Outstanding</flux:sidebar.item>
                    <flux:sidebar.item href="#">Invoice Outstanding</flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.group>

            {{-- inventory --}}
            <flux:sidebar.group icon="rectangle-stack" href="#" expandable heading="Inventory" class="grid">
                <flux:sidebar.group href="#" expandable heading="Master Security" class="grid">
                    <flux:sidebar.item href="#">Warehouse</flux:sidebar.item>
                    <flux:sidebar.item href="#">Rack</flux:sidebar.item>
                    <flux:sidebar.item href="#">Warehouse Security</flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group href="#" expandable heading="Master Product" class="grid">
                    <flux:sidebar.item href="#">Product Category</flux:sidebar.item>
                    <flux:sidebar.item href="#">Product Master</flux:sidebar.item>
                    <flux:sidebar.item href="#">UOM</flux:sidebar.item>
                    <flux:sidebar.item href="#">Color</flux:sidebar.item>
                    <flux:sidebar.item href="#">Sales Price</flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group href="#" expandable heading="Inventory Transaction" class="grid">
                    <flux:sidebar.item href="#">Transfer Stock</flux:sidebar.item>
                    <flux:sidebar.item href="#">Adjustment In</flux:sidebar.item>
                    <flux:sidebar.item href="#">Adjustment Out</flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group href="#" expandable heading="Distribution Management" class="grid">
                    <flux:sidebar.item href="#">Request Transfer</flux:sidebar.item>
                    <flux:sidebar.item href="#">Shipment</flux:sidebar.item>
                    <flux:sidebar.item href="#">Good Receive</flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group href="#" expandable heading="Stock Opname" class="grid">
                    <flux:sidebar.item href="#">Stock Opname Begin</flux:sidebar.item>
                    <flux:sidebar.item href="#">Stock Opname Entry</flux:sidebar.item>
                    <flux:sidebar.item href="#">Stock Opname Variant</flux:sidebar.item>
                    <flux:sidebar.item href="#">Stock Opname Release</flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group href="#" expandable heading="Report" class="grid">
                    <flux:sidebar.item href="#">Stock Balance</flux:sidebar.item>
                    <flux:sidebar.item href="#">Stock Card</flux:sidebar.item>
                    <flux:sidebar.item href="#">Stock Movement</flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.group>

            {{-- sales --}}
            <flux:sidebar.group icon="presentation-chart-line" href="#" expandable heading="Sales" class="grid">
                <flux:sidebar.group href="#" expandable heading="Master" class="grid">
                    <flux:sidebar.item href="#">Suplier Category</flux:sidebar.item>
                    <flux:sidebar.item href="#">Supplier</flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group href="#" expandable heading="Transaction" class="grid">
                    <flux:sidebar.item href="#">Purchase Order</flux:sidebar.item>
                    <flux:sidebar.item href="#">Good Receive</flux:sidebar.item>
                    <flux:sidebar.item href="#">Purchase Invoice</flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group href="#" expandable heading="Report" class="grid">
                    <flux:sidebar.item href="#">PO Outstanding</flux:sidebar.item>
                    <flux:sidebar.item href="#">Invoice Outstanding</flux:sidebar.item>
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
