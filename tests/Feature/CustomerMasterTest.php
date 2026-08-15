<?php

use App\Livewire\Sales\SalesMaster\CustomerMaster;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerPic;
use App\Models\Role;
use App\Models\User;
use Livewire\Livewire;

function validCustomerForm(array $overrides = []): array
{
    return array_replace_recursive([
        'code' => 'cust-001',
        'name' => 'PT Contoh Indonesia',
        'phone' => '021-555000',
        'email' => 'office@example.test',
        'tax_number' => '01.234.567.8-999.000',
        'notes' => 'Customer utama',
        'is_active' => true,
        'pics' => [
            [
                'id' => null,
                'name' => 'Budi',
                'position' => 'Purchasing Manager',
                'phone' => '081200000001',
                'email' => 'budi@example.test',
                'notes' => '',
                'is_primary' => true,
            ],
            [
                'id' => null,
                'name' => 'Sari',
                'position' => 'Finance',
                'phone' => '081200000002',
                'email' => 'sari@example.test',
                'notes' => '',
                'is_primary' => false,
            ],
        ],
        'addresses' => [
            [
                'id' => null,
                'code' => 'hq',
                'label' => 'Kantor Pusat',
                'address_type' => 'both',
                'address' => 'Jl. Contoh No. 1',
                'province' => 'DKI Jakarta',
                'city' => 'Jakarta Selatan',
                'district' => 'Setiabudi',
                'postal_code' => '12910',
                'country' => 'Indonesia',
                'is_primary' => true,
            ],
            [
                'id' => null,
                'code' => 'wh-jkt',
                'label' => 'Gudang Jakarta',
                'address_type' => 'shipping',
                'address' => 'Jl. Gudang No. 2',
                'province' => 'DKI Jakarta',
                'city' => 'Jakarta Utara',
                'district' => '',
                'postal_code' => '14310',
                'country' => 'Indonesia',
                'is_primary' => false,
            ],
        ],
    ], $overrides);
}

it('opens the create customer modal from the add customer action', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(CustomerMaster::class)
        ->assertSet('showModal', false)
        ->call('openCreate')
        ->assertSet('showModal', true)
        ->assertSee('Tambah Pelanggan')
        ->assertSeeHtml('data-testid="customer-modal-scroll-container"')
        ->assertSeeHtml('h-[80vh]')
        ->assertSeeHtml('items-start')
        ->assertSeeHtml('overflow-y-auto')
        ->assertDontSeeHtml('wire:model="email"')
        ->assertDontSeeHtml('wire:model="tax_number"')
        ->assertDontSeeHtml('wire:model="pics.0.email"');
});

it('creates a customer with multiple pics and addresses in one form', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $form = validCustomerForm();

    Livewire::test(CustomerMaster::class)
        ->set('code', $form['code'])
        ->set('name', $form['name'])
        ->set('phone', $form['phone'])
        ->set('email', $form['email'])
        ->set('tax_number', $form['tax_number'])
        ->set('notes', $form['notes'])
        ->set('is_active', $form['is_active'])
        ->set('pics', $form['pics'])
        ->set('addresses', $form['addresses'])
        ->call('save')
        ->assertHasNoErrors();

    $customer = Customer::with(['pics', 'addresses'])->sole();

    expect($customer->code)->toBe('CUST-00001')
        ->and($customer->created_by)->toBe($user->id)
        ->and($customer->pics)->toHaveCount(2)
        ->and($customer->addresses)->toHaveCount(2)
        ->and($customer->primaryPic->name)->toBe('Budi')
        ->and($customer->addresses->pluck('code')->sort()->values()->all())->toBe(['hq', 'wh-jkt']);
});

it('updates nested rows and soft deletes rows removed from the form', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->for($user, 'creator')->create();
    $primaryPic = CustomerPic::factory()->for($customer)->create(['name' => 'PIC Lama', 'is_primary' => true]);
    $removedPic = CustomerPic::factory()->for($customer)->create(['name' => 'PIC Dihapus']);
    $primaryAddress = CustomerAddress::factory()->for($customer)->create(['code' => 'OLD', 'is_primary' => true]);
    $removedAddress = CustomerAddress::factory()->for($customer)->create(['code' => 'REMOVE']);
    $this->actingAs($user);

    Livewire::test(CustomerMaster::class)
        ->call('openEdit', $customer->id)
        ->set('name', 'Customer Diperbarui')
        ->set('pics', [[
            'id' => $primaryPic->id,
            'name' => 'PIC Baru',
            'position' => 'Director',
            'phone' => '',
            'email' => '',
            'notes' => '',
            'is_primary' => true,
        ]])
        ->set('addresses', [[
            'id' => $primaryAddress->id,
            'code' => 'MAIN',
            'label' => 'Alamat Utama',
            'address_type' => 'both',
            'address' => 'Alamat yang diperbarui',
            'province' => '',
            'city' => '',
            'district' => '',
            'postal_code' => '',
            'country' => 'Indonesia',
            'is_primary' => true,
        ]])
        ->call('save')
        ->assertHasNoErrors();

    expect($customer->fresh()->name)->toBe('Customer Diperbarui')
        ->and($primaryPic->fresh()->name)->toBe('PIC Baru')
        ->and($primaryAddress->fresh()->code)->toBe('MAIN')
        ->and($removedPic->fresh()->trashed())->toBeTrue()
        ->and($removedAddress->fresh()->trashed())->toBeTrue();
});

it('generates an address code only for rows left empty and allows an empty address label', function () {
    $this->actingAs(User::factory()->create());
    $form = validCustomerForm([
        'addresses' => [
            ['code' => 'GUDANG-A', 'label' => ''],
            ['code' => ''],
        ],
    ]);

    Livewire::test(CustomerMaster::class)
        ->set('code', $form['code'])
        ->set('name', $form['name'])
        ->set('pics', $form['pics'])
        ->set('addresses', $form['addresses'])
        ->call('save')
        ->assertHasNoErrors();

    $customer = Customer::with('addresses')->sole();

    expect($customer->code)->toBe('CUST-00001')
        ->and($customer->addresses->pluck('code')->sort()->values()->all())->toBe(['ADDR-002', 'GUDANG-A'])
        ->and($customer->addresses->pluck('label'))->toContain('');
});

it('rejects duplicate address codes within the same customer', function () {
    $this->actingAs(User::factory()->create());
    $form = validCustomerForm([
        'addresses' => [
            ['code' => 'SAME'],
            ['code' => 'same'],
        ],
    ]);

    Livewire::test(CustomerMaster::class)
        ->set('code', $form['code'])
        ->set('name', $form['name'])
        ->set('pics', $form['pics'])
        ->set('addresses', $form['addresses'])
        ->call('save')
        ->assertHasErrors('addresses.1.code');

    expect(Customer::count())->toBe(0);
});

it('automatically assigns a primary pic and address when none is selected', function () {
    $this->actingAs(User::factory()->create());
    $form = validCustomerForm();
    $form['pics'][0]['is_primary'] = false;
    $form['addresses'][0]['is_primary'] = false;

    Livewire::test(CustomerMaster::class)
        ->set('code', $form['code'])
        ->set('name', $form['name'])
        ->set('pics', $form['pics'])
        ->set('addresses', $form['addresses'])
        ->call('save')
        ->assertHasNoErrors();

    $customer = Customer::firstOrFail();
    expect($customer->pics()->where('is_primary', true)->count())->toBe(1)
        ->and($customer->addresses()->where('is_primary', true)->count())->toBe(1);
});

it('soft deletes and restores a customer', function () {
    $superAdminRole = Role::create(['name' => 'Super Admin', 'permissions' => ['*']]);
    $user = User::factory()->for($superAdminRole)->create();
    $customer = Customer::factory()->for($user, 'creator')->create();
    $this->actingAs($user);

    Livewire::test(CustomerMaster::class)
        ->call('confirmDelete', $customer->id)
        ->call('delete');

    expect($customer->fresh()->trashed())->toBeTrue();

    Livewire::test(CustomerMaster::class)
        ->call('restore', $customer->id);

    expect($customer->fresh()->trashed())->toBeFalse();
});

it('shows customer detail from the action menu', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->for($user, 'creator')->create([
        'name' => 'PT Detail Customer',
    ]);
    CustomerPic::factory()->for($customer)->create([
        'name' => 'PIC Detail',
        'is_primary' => true,
    ]);
    CustomerAddress::factory()->for($customer)->create([
        'label' => 'Gudang Detail',
        'address' => 'Jl. Detail No. 1',
        'is_primary' => true,
    ]);
    $this->actingAs($user);

    Livewire::test(CustomerMaster::class)
        ->assertSeeHtml('wire:click="openDetail('.$customer->id.')"')
        ->call('openDetail', $customer->id)
        ->assertSet('showDetailModal', true)
        ->assertSet('detailCustomer.name', 'PT Detail Customer')
        ->assertSee('Rincian Pelanggan')
        ->assertSee('PIC Detail')
        ->assertSee('Gudang Detail')
        ->assertSee('Jl. Detail No. 1');
});

it('keeps only the customer master route', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('sales.master.customer'))->assertOk();
    $this->get('/sales/master/customer-delivery-address')->assertNotFound();
});
