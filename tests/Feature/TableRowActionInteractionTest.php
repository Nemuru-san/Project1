<?php

use Illuminate\Support\Facades\File;

it('provides one global row-click interaction for every alpine table action menu', function () {
    $script = File::get(resource_path('js/app.js'));
    $styles = File::get(resource_path('css/app.css'));

    expect($script)
        ->toContain("row.querySelectorAll('[x-data]')")
        ->toContain("element.querySelector(':scope > [x-show=\"open\"]')")
        ->toContain("target?.closest('tbody > tr')")
        ->toContain('closeOtherActionMenus(row)')
        ->toContain("context.trigger.click()")
        ->toContain("context.row.classList.add('erp-table-row-active')")
        ->toContain("context.menu.style.setProperty('position', 'fixed', 'important')")
        ->toContain('const top = rowRect.bottom + 6')
        ->and($styles)
        ->toContain('tr.erp-table-row-active')
        ->toContain('.erp-action-menu-centered');
});

it('keeps all current table action menu variants compatible with the global interaction', function () {
    $views = collect(File::allFiles(resource_path('views/livewire')))
        ->filter(fn (SplFileInfo $file) => str_ends_with($file->getFilename(), '.blade.php'))
        ->filter(fn (SplFileInfo $file) => str_contains(File::get($file->getPathname()), 'x-show="open"'));

    expect($views->count())->toBeGreaterThanOrEqual(20);

    $views->each(function (SplFileInfo $file) {
        $contents = File::get($file->getPathname());

        expect($contents)
            ->toContain('x-data=')
            ->toContain('x-show="open"');

        expect(preg_match('/\bopen\s*:\s*false/', $contents))->toBe(1);
    });
});
