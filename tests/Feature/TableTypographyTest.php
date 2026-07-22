<?php

use Illuminate\Support\Facades\File;

it('uses the 14px typography standard for every Livewire table', function () {
    $oversizedPattern = '/<(?:table|thead|tbody|tr|th|td)\b[^>]*class="[^"]*\btext-(?:base|lg|xl|2xl)\b[^"]*"/s';
    $tablePattern = '/<table\b[^>]*>/s';
    $tablesChecked = 0;

    foreach (File::allFiles(resource_path('views/livewire')) as $file) {
        $contents = File::get($file->getPathname());

        expect($contents)
            ->not->toMatch($oversizedPattern, "Ukuran font tabel terlalu besar di {$file->getRelativePathname()}");

        preg_match_all($tablePattern, $contents, $tables);

        foreach ($tables[0] as $table) {
            $tablesChecked++;
            expect($table)->toContain('text-sm');
        }
    }

    expect($tablesChecked)->toBeGreaterThan(0);
});
