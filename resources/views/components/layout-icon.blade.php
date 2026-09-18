@props(['name'])

@php
    $paths = [
        'dashboard' => 'M3 3h7v7H3z M14 3h7v7h-7z M3 14h7v7H3z M14 14h7v7h-7z',
        'graduation' => 'm2 9 10-5 10 5-10 5z M6 11v6q6 5 12 0v-6 M22 9v7',
        'users' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M9 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8 M17 4a4 4 0 0 1 0 8 M22 21v-2a4 4 0 0 0-3-3.87',
        'layers' => 'm12 3 10 5-10 5L2 8z M2 12l10 5 10-5 M2 16l10 5 10-5',
        'book' => 'M12 5v16 M12 5C8 3 5 3 2 4v15c3-1 6-1 10 2 4-3 7-3 10-2V4c-3-1-6-1-10 1',
        'building' => 'M4 21V3h12v18 M16 9h4v12 M2 21h20 M8 7h4 M8 11h4 M8 15h4 M9 21v-3h2v3',
        'school' => 'm3 10 9-7 9 7 M5 9v12h14V9 M10 21v-6h4v6 M10 10h4',
        'door' => 'M3 21h18 M6 21V3h12v18 M14 12h.01',
        'calendar' => 'M4 5h16v16H4z M16 3v4 M8 3v4 M4 11h16 M8 15h2 M14 15h2',
        'period' => 'M4 5h16v7 M4 5v16h7 M8 3v4 M16 3v4 M4 11h16 M17 13a4 4 0 1 0 0 8 4 4 0 0 0 0-8 M17 15v2l1 1',
        'file-check' => 'M14 2H5v20h14V7z M14 2v5h5 M8 14l3 3 5-6',
        'file-chart' => 'M14 2H5v20h14V7z M14 2v5h5 M8 17v-3 M12 17v-6 M16 17v-4',
        'chart' => 'M3 3v18h18 M7 17v-6 M12 17V7 M17 17V4',
        'attendance' => 'M4 5h16v16H4z M8 3v4 M16 3v4 M4 10h16 M8 15l3 3 5-5',
        'clipboard' => 'M9 4H5v18h14V4h-4 M9 2h6v4H9z M8 14l3 3 5-6',
        'file' => 'M14 2H5v20h14V7z M14 2v5h5 M8 12h8 M8 16h8',
        'user' => 'M12 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8 M4 21v-2a5 5 0 0 1 5-5h6a5 5 0 0 1 5 5v2',
        'research' => 'M9 3h6 M10 3v7L4 20q0 1 1 1h14q1 0 1-1l-6-10V3 M7 15h10',
        'services' => 'M4 5h16 M4 12h16 M4 19h16 M8 3v4 M16 10v4 M10 17v4',
        'wallet' => 'M20 8V4H5a2 2 0 0 0 0 4h16v13H5a2 2 0 0 1-2-2V6 M21 12h-6v5h6 M17 14.5h.01',
        'menu' => 'M4 6h16 M4 12h16 M4 18h16',
        'bell' => 'M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9 M10 21h4',
        'help' => 'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20 M9 9a3 3 0 1 1 5 2c-2 1-2 2-2 3 M12 17h.01',
        'logout' => 'M9 3H3v18h6 M9 12h12 M17 8l4 4-4 4',
        'plus' => 'M12 5v14 M5 12h14',
        'check' => 'M20 6 9 17l-5-5',
        'x' => 'M18 6 6 18 M6 6l12 12',
        'edit' => 'M12 20h9 M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4z',
        'trash' => 'M3 6h18 M8 6V4h8v2 M19 6l-1 15H6L5 6 M10 11v6 M14 11v6',
        'search' => 'M21 21l-4.35-4.35 M19 11a8 8 0 1 1-16 0 8 8 0 0 1 16 0',
        'save' => 'M5 3h12l2 2v16H5z M8 3v6h8V3 M8 21v-7h8v7',
        'lock' => 'M6 10h12v11H6z M8 10V7a4 4 0 0 1 8 0v3',
        'refresh' => 'M20 7v5h-5 M4 17v-5h5 M6.1 9a7 7 0 0 1 11.7-2L20 12 M4 12l2.2 5a7 7 0 0 0 11.7-2',
        'inbox' => 'M4 4h16v16H4z M4 14h5l2 3h2l2-3h5',
        'star' => 'm12 2 3 6 7 .9-5 4.8 1.2 6.8-6.2-3.3-6.2 3.3 1.2-6.8-5-4.8 7-.9z',
        'megaphone' => 'M3 11v2h4l9 4z M7 13l2 7h3l-2-6',
        'download' => 'M12 3v12 M7 10l5 5 5-5 M5 21h14',
        'arrow-left' => 'M19 12H5 M11 18l-6-6 6-6',
    ];
@endphp

<svg {{ $attributes->class(['layout-icon']) }} xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    <path d="{{ $paths[$name] ?? $paths['file'] }}" />
</svg>
