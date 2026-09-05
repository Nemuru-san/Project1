<?php

use App\Livewire\Sales\SalesMaster\SalesMan;
use App\Models\Role;
use App\Models\Salesman as SalesmanModel;
use App\Models\SalesmanTarget;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

it('positions the create and edit salesman modal at the top like other forms', function () {
    $admin = User::factory()->create();
    $this->actingAs($admin);

    Livewire::test(SalesMan::class)
        ->call('openCreate')
        ->assertSet('showModal', true)
        ->assertSeeHtml('items-start justify-center overflow-y-auto')
        ->assertSeeHtml('max-h-[calc(100dvh-2rem)]')
        ->assertSeeHtml('class="flex min-h-0 flex-1 flex-col"');

    $salesman = SalesmanModel::create(['code' => 'SM-EDIT', 'name' => 'Salesman Edit']);

    Livewire::test(SalesMan::class)
        ->call('openEdit', $salesman->id)
        ->assertSet('showModal', true)
        ->assertSee('Ubah Salesman')
        ->assertSeeHtml('items-start justify-center overflow-y-auto');
});

it('creates a salesman together with an ERP login and salesman role', function () {
    $admin = User::factory()->create();
    $this->actingAs($admin);

    Livewire::test(SalesMan::class)
        ->set('code', 'sm-001')
        ->set('name', 'Budi Santoso')
        ->set('login', 'budi.sales@example.test')
        ->set('password', 'password-sales')
        ->set('passwordConfirmation', 'password-sales')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    $salesman = SalesmanModel::sole();
    $account = User::where('email', 'budi.sales@example.test')->sole();

    expect($salesman->code)->toBe('SM-001')
        ->and($salesman->user->is($account))->toBeTrue()
        ->and($account->role->name)->toBe('Salesman')
        ->and(Hash::check('password-sales', $account->password))->toBeTrue()
        ->and($salesman->creator->is($admin))->toBeTrue();
});

it('does not allow duplicate ERP login credentials', function () {
    $admin = User::factory()->create();
    User::factory()->create(['email' => 'sales@example.test']);
    $this->actingAs($admin);

    Livewire::test(SalesMan::class)
        ->set('code', 'SM-002')
        ->set('name', 'Salesman Kedua')
        ->set('login', 'sales@example.test')
        ->set('password', 'password-sales')
        ->set('passwordConfirmation', 'password-sales')
        ->call('save')
        ->assertHasErrors(['login' => 'unique']);

    expect(SalesmanModel::count())->toBe(0);
});

it('updates soft deletes and restores a salesman', function () {
    $superAdminRole = Role::create(['name' => 'Super Admin', 'permissions' => ['*']]);
    $admin = User::factory()->for($superAdminRole)->create();
    $role = Role::create(['name' => 'Salesman', 'permissions' => ['dashboard']]);
    $account = User::factory()->for($role)->create([
        'name' => 'Nama Lama',
        'email' => 'salesman@example.test',
    ]);
    $oldPassword = $account->password;
    $salesman = SalesmanModel::create([
        'code' => 'SM-001',
        'name' => 'Nama Lama',
        'user_id' => $account->id,
    ]);
    $this->actingAs($admin);

    Livewire::test(SalesMan::class)
        ->call('openEdit', $salesman->id)
        ->set('name', 'Nama Baru')
        ->set('login', 'salesman.baru@example.test')
        ->call('save')
        ->assertHasNoErrors()
        ->call('confirmDelete', $salesman->id)
        ->call('delete');

    expect($salesman->fresh()->name)->toBe('Nama Baru')
        ->and($salesman->fresh()->trashed())->toBeTrue()
        ->and($account->fresh()->name)->toBe('Nama Baru')
        ->and($account->fresh()->email)->toBe('salesman.baru@example.test')
        ->and($account->fresh()->password)->toBe($oldPassword)
        ->and($account->fresh()->trashed())->toBeTrue();

    Livewire::test(SalesMan::class)
        ->call('restore', $salesman->id)
        ->assertHasNoErrors();

    expect($salesman->fresh()->trashed())->toBeFalse()
        ->and($account->fresh()->trashed())->toBeFalse();
});

it('requires password confirmation when creating the ERP login', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(SalesMan::class)
        ->set('code', 'SM-001')
        ->set('name', 'Budi Santoso')
        ->set('login', 'budi@example.test')
        ->set('password', 'password-sales')
        ->set('passwordConfirmation', 'berbeda-password')
        ->call('save')
        ->assertHasErrors(['password' => 'same']);

    expect(SalesmanModel::count())->toBe(0)
        ->and(User::where('email', 'budi@example.test')->exists())->toBeFalse();
});

it('disables and enables the ERP login together with salesman status', function () {
    $admin = User::factory()->create();
    $role = Role::create(['name' => 'Salesman', 'permissions' => ['dashboard']]);
    $account = User::factory()->for($role)->create();
    $salesman = SalesmanModel::create([
        'code' => 'SM-001',
        'name' => 'Budi Santoso',
        'user_id' => $account->id,
    ]);
    $this->actingAs($admin);

    Livewire::test(SalesMan::class)
        ->call('openEdit', $salesman->id)
        ->set('isActive', false)
        ->call('save')
        ->assertHasNoErrors();

    expect($salesman->fresh()->is_active)->toBeFalse()
        ->and($account->fresh()->trashed())->toBeTrue();

    Livewire::test(SalesMan::class)
        ->call('openEdit', $salesman->id)
        ->set('isActive', true)
        ->call('save')
        ->assertHasNoErrors();

    expect($salesman->fresh()->is_active)->toBeTrue()
        ->and($account->fresh()->trashed())->toBeFalse();
});

it('renders the salesman master page for authenticated users', function () {
    $admin = User::factory()->create();
    $role = Role::create(['name' => 'Salesman', 'permissions' => ['dashboard']]);
    $account = User::factory()->for($role)->create();
    SalesmanModel::create([
        'code' => 'SM-001',
        'name' => 'Budi Santoso',
        'user_id' => $account->id,
    ]);

    $this->actingAs($admin)
        ->get(route('sales.master.salesman'))
        ->assertOk()
        ->assertSee('Data Tenaga Penjualan')
        ->assertSee('Buka aksi salesman')
        ->assertSee('Ubah')
        ->assertSee('Hapus');
});

it('creates updates and deletes one monthly target per salesman', function () {
    $admin = User::factory()->create();
    $salesman = SalesmanModel::create(['code' => 'SM-TARGET', 'name' => 'Salesman Target']);
    $this->actingAs($admin);

    $component = Livewire::test(SalesMan::class)
        ->set('targetMonth', '2026-09')
        ->call('openTarget', $salesman->id)
        ->assertSet('showTargetModal', true)
        ->set('targetAmount', 100000000)
        ->call('saveTarget')
        ->assertHasNoErrors()
        ->assertDispatched('toast', type: 'success');

    expect(SalesmanTarget::sole()->target_month->toDateString())->toBe('2026-09-01')
        ->and(SalesmanTarget::sole()->target_amount)->toBe(100000000);

    $component->call('openTarget', $salesman->id)
        ->assertSet('targetAmount', 100000000)
        ->set('targetAmount', 125000000)
        ->call('saveTarget');

    expect(SalesmanTarget::count())->toBe(1)
        ->and(SalesmanTarget::sole()->target_amount)->toBe(125000000);

    $component->call('openTarget', $salesman->id)
        ->call('deleteTarget');

    expect(SalesmanTarget::count())->toBe(0);
});
