<?php

use App\Livewire\Supplier\SupplierManager;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;

it('allows only users with the Super Admin role to delete records', function () {
    $ordinaryRole = Role::create(['name' => 'Staff', 'permissions' => ['*']]);
    $ordinaryUser = User::factory()->for($ordinaryRole)->create();
    $supplier = Supplier::create([
        'code' => 'SUP-001',
        'name' => 'Supplier Test',
        'address' => 'Jakarta',
        'contact' => '08123456789',
        'created_by' => 'Test',
    ]);

    $this->actingAs($ordinaryUser);

    Livewire::test(SupplierManager::class)
        ->set('deleteTargetId', $supplier->id)
        ->call('delete')
        ->assertDispatched('toast', message: 'Hanya Super Admin yang dapat menghapus data.', type: 'error');

    expect($supplier->fresh()->trashed())->toBeFalse();

    $superAdminRole = Role::create(['name' => 'Super Admin', 'permissions' => ['*']]);
    $superAdmin = User::factory()->for($superAdminRole)->create();
    $this->actingAs($superAdmin);

    Livewire::test(SupplierManager::class)
        ->set('deleteTargetId', $supplier->id)
        ->call('delete');

    expect($supplier->fresh()->trashed())->toBeTrue();
});

it('guards every Livewire delete method with a Super Admin authorization check', function () {
    $unguardedComponents = collect(File::allFiles(app_path('Livewire')))
        ->filter(fn ($file) => str_contains($file->getContents(), 'public function delete(): void'))
        ->reject(fn ($file) => str_contains($file->getContents(), 'isSuperAdmin()'))
        ->map(fn ($file) => $file->getRelativePathname())
        ->values()
        ->all();

    expect($unguardedComponents)->toBeEmpty();
});

it('disables every delete button for users who are not Super Admin', function () {
    $unguardedButtons = [];

    foreach (File::allFiles(resource_path('views/livewire')) as $file) {
        $lines = preg_split('/\R/', $file->getContents());

        foreach ($lines as $index => $line) {
            if (str_contains($line, 'confirmDelete(') || str_contains($line, 'wire:click="delete"')) {
                $buttonLines = implode("\n", array_slice($lines, max(0, $index - 1), 4));

                if (! str_contains($buttonLines, '@disabled')) {
                    $unguardedButtons[] = $file->getRelativePathname().':'.($index + 1);
                }
            }
        }
    }

    expect($unguardedButtons, 'Tombol belum dilindungi: '.implode(', ', $unguardedButtons))->toBeEmpty();
});
