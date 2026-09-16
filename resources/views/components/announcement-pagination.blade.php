@props(['paginator'])
{{ $paginator->appends(request()->query())->onEachSide(1)->links('pagination.default') }}
