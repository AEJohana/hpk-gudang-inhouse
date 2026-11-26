@props([
    'paginator',
    'position' => 'bottom',
    'wrapperClass' => null,
])

@php
    $isTop = $position === 'top';
    $hasTotal = method_exists($paginator, 'total') ? $paginator->total() > 0 : $paginator->count() > 0;
    $hasPages = method_exists($paginator, 'hasPages') ? $paginator->hasPages() : false;
    $shouldRender = $isTop ? $hasTotal : $hasPages;
    $resolvedClass = $wrapperClass ?? ($isTop
        ? 'mb-4 p-3 sm:px-4 sm:py-3 bg-white rounded-2xl border border-slate-200/80 shadow-sm'
        : 'p-4 bg-white rounded-2xl border border-slate-200/80 shadow-sm');
@endphp

@if ($shouldRender)
    <div {{ $attributes->merge(['class' => $resolvedClass]) }}>
        {{ $paginator->links('vendor.pagination.tailwind', ['showPerPage' => $isTop]) }}
    </div>
@endif
