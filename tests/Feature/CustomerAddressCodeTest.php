<?php

use App\Livewire\Sales\SalesMaster\CustomerAddressCode;
use App\Livewire\Sales\SalesMaster\CustomerMaster;
use App\Models\AddressCode;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Role;
use App\Models\User;
use Livewire\Livewire;

it('shows the separate address code catalog', function () {
    $this->actingAs(User::factory()->create());
    AddressCode::create([
        'code' => 'GUDANG-JKT',
        'description' => 'Gudang Jakarta',
        'is_active' => true,
    ]);

    $this->get(route('sales.master.customer-address-code'))
        ->assertOk()
        ->assertSee('Kode Alamat Pelanggan')
        ->assertSee('GUDANG-JKT')
        ->assertSee('Gudang Jakarta');
});

it('creates an address code and normalizes it to uppercase', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(CustomerAddressCode::class)
        ->call('openCreate')
        ->assertSet('showModal', true)
        ->set('code', ' cabang-sby ')
        ->set('description', 'Cabang Surabaya')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showModal', false);

    expect(AddressCode::sole())
        ->code->toBe('CABANG-SBY')
        ->description->toBe('Cabang Surabaya');
});

it('updates assigned customer addresses when a master code changes', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->for($user, 'creator')->create();
    $masterCode = AddressCode::create(['code' => 'ADDR-LAMA', 'is_active' => true]);
    $address = CustomerAddress::factory()->for($customer)->create(['code' => 'ADDR-LAMA']);
    $this->actingAs($user);

    Livewire::test(CustomerAddressCode::class)
        ->call('openEdit', $masterCode->id)
        ->set('code', 'ADDR-BARU')
        ->call('save')
        ->assertHasNoErrors();

    expect($address->fresh()->code)->toBe('ADDR-BARU');
});

it('rejects duplicate master address codes case insensitively', function () {
    $this->actingAs(User::factory()->create());
    AddressCode::create(['code' => 'GUDANG-A', 'is_active' => true]);

    Livewire::test(CustomerAddressCode::class)
        ->call('openCreate')
        ->set('code', 'gudang-a')
        ->call('save')
        ->assertHasErrors('code');

    expect(AddressCode::count())->toBe(1);
});

it('lets users select an active address code in the customer form', function () {
    $this->actingAs(User::factory()->create());
    AddressCode::create([
        'code' => 'TOKO-BDG',
        'description' => 'Toko Bandung',
        'is_active' => true,
    ]);
    AddressCode::create(['code' => 'NONAKTIF', 'is_active' => false]);

    Livewire::test(CustomerMaster::class)
        ->call('openCreate')
        ->assertSeeHtml('wire:model.live="addresses.0.code"')
        ->assertSee('TOKO-BDG - Toko Bandung')
        ->assertDontSee('NONAKTIF');
});

it('positions customer and address code modals like the sales order modal', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(CustomerMaster::class)
        ->call('openCreate')
        ->assertSeeHtml('items-start')
        ->assertSeeHtml('h-[80vh]');

    Livewire::test(CustomerAddressCode::class)
        ->call('openCreate')
        ->assertSeeHtml('items-start')
        ->assertSeeHtml('bg-zinc-50')
        ->assertSeeHtml('touch-pan-y');
});

it('shows edit and delete actions through the standard row action menu', function () {
    $this->actingAs(User::factory()->create());
    $addressCode = AddressCode::create(['code' => 'AKSI-TEST', 'is_active' => true]);

    Livewire::test(CustomerAddressCode::class)
        ->assertSeeHtml('aria-label="Buka aksi kode alamat"')
        ->assertSeeHtml('x-show="open"')
        ->assertSeeHtml('wire:click="openEdit('.$addressCode->id.')"')
        ->assertSeeHtml('wire:click="confirmDelete('.$addressCode->id.')"');
});

it('soft deletes and restores an address code', function () {
    $role = Role::create(['name' => 'Super Admin', 'permissions' => ['*']]);
    $this->actingAs(User::factory()->for($role)->create());
    $addressCode = AddressCode::create(['code' => 'HAPUS-TEST', 'is_active' => true]);

    Livewire::test(CustomerAddressCode::class)
        ->call('confirmDelete', $addressCode->id)
        ->assertSet('showDeleteModal', true)
        ->call('delete')
        ->assertSet('showDeleteModal', false)
        ->set('showTrashed', true)
        ->assertSee('Terhapus')
        ->call('restore', $addressCode->id);

    expect($addressCode->fresh()->trashed())->toBeFalse();
});
