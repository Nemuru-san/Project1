<?php

use Illuminate\Support\Facades\File;

it('uses 16 by 9 inch continuous form paper for every print template', function () {
    $sharedStyle = File::get(resource_path('views/prints/partials/nota-style.blade.php'));

    expect($sharedStyle)
        ->toContain('size: 16in 9in;')
        ->not->toContain('A4 portrait');

    collect(File::files(resource_path('views/prints')))
        ->filter(fn (SplFileInfo $file) => $file->getExtension() === 'php')
        ->each(function (SplFileInfo $file): void {
            $template = File::get($file->getPathname());

            if ($file->getFilename() === 'direct-sale-receipt.blade.php') {
                expect($template)->toContain('size: 80mm auto;');

                return;
            }

            $usesSharedStyle = str_contains($template, "@include('prints.partials.nota-style')");

            expect($usesSharedStyle || str_contains($template, 'size: 16in 9in;'))->toBeTrue()
                ->and($template)->not->toContain('A4 portrait')
                ->and($template)->not->toContain('size: 210mm 140mm');
        });
});
