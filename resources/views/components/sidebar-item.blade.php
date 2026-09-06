@props(['route' => null, 'active' => false, 'icon', 'badge' => null])

<a href="{{ $route ? route($route) : '#' }}" @class(['nav-item', 'active' => $active]) @if($active) aria-current="page" @endif>
    <span class="nav-icon"><x-layout-icon :name="$icon" /></span>
    <span class="nav-label">{{ $slot }}</span>
    @if($badge)
        <span class="nav-badge">{{ $badge }}</span>
    @endif
</a>
