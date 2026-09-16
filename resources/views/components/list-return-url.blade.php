@props(['listRoute'])
<input type="hidden" name="return_url" value="{{ app(\App\Services\LegacyListNavigation::class)->returnUrl(request(), $listRoute) }}">
