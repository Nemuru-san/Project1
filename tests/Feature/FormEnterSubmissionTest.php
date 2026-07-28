<?php

use Illuminate\Support\Facades\File;

it('prevents enter on input fields from submitting Livewire forms', function () {
    $unguardedForms = [];

    foreach (File::allFiles(resource_path('views/livewire')) as $file) {
        preg_match_all('/<form\b[^>]*>/s', $file->getContents(), $matches);

        foreach ($matches[0] as $formTag) {
            if (! str_contains($formTag, 'x-on:keydown.enter=') || ! str_contains($formTag, "tagName === 'INPUT'")) {
                $unguardedForms[] = $file->getRelativePathname();
            }
        }
    }

    expect($unguardedForms, 'Form tanpa pelindung Enter: '.implode(', ', $unguardedForms))->toBeEmpty();
});
