<?php

test('monitoring presensi PDF download uses the same primary button style as Excel', function () {
    $view = file_get_contents(resource_path('views/admin/presensi/index.blade.php'));

    preg_match(
        '/href="\{\{ route\(\'admin\.presensi\.pdf\'.*?<\/a>/s',
        $view,
        $pdfLink
    );

    expect($pdfLink[0] ?? '')
        ->toContain('class="btn-primary"')
        ->not->toContain('class="btn-outline"');
});

test('admin dosen and mahasiswa layouts use the shared link style without underlines', function () {
    $css = file_get_contents(public_path('assets/css/style.css'));

    expect($css)
        ->toMatch('/a,\s*a:hover,\s*a:focus,\s*a:active,\s*a:visited\s*\{\s*text-decoration:\s*none;/')
        ->toMatch('/a\.btn-primary,[^{]+\{[^}]*color:\s*var\(--white\);[^}]*text-decoration:\s*none;/s');

    foreach (['admin', 'dosen', 'mahasiswa'] as $layout) {
        $view = file_get_contents(resource_path("views/layouts/{$layout}.blade.php"));

        expect($view)->toContain("asset('assets/css/style.css')");
    }
});
