<?php

use Illuminate\Support\Facades\File;

/*
 * Epson LX-310 (narrow carriage) memakai continuous form 9.5in x 5.5in (~14cm tinggi).
 * @page tidak boleh dikunci ke ukuran tertentu: lebar mengikuti kertas di driver printer,
 * tinggi dipatok 14cm agar selalu satu lembar.
 */
it('follows the printer driver paper width and fixes the height to 14cm for every print template', function () {
    $sharedStyle = File::get(resource_path('views/prints/partials/nota-style.blade.php'));

    expect($sharedStyle)
        ->toContain('size: auto;')
        ->toContain('height: 14cm;')
        ->not->toContain('16in')
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

            expect($usesSharedStyle || (str_contains($template, 'size: auto;') && str_contains($template, 'height: 14cm;')))
                ->toBeTrue("Template {$file->getFilename()} belum memakai ukuran kertas LX-310")
                ->and($template)->not->toContain('16in')
                ->and($template)->not->toContain('A4 portrait');
        });
});
