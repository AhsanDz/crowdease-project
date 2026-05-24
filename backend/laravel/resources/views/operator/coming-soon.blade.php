@extends('operator.layouts.app')

@section('title', $title . ' · Operator')
@section('page-title', $title)
@section('page-subtitle', $subtitle)

@php $activePage = $pageKey; @endphp

@section('content')

<div class="bg-white border flex flex-col items-center justify-center text-center"
     style="border-color: var(--ink-100); border-radius: 14px;
            padding: 64px 24px">

    {{-- Icon --}}
    <div class="flex items-center justify-center"
         style="width: 56px; height: 56px; border-radius: 14px;
                background: var(--brand-50); color: var(--brand);
                margin-bottom: 18px">
        @include('operator.partials.icon', ['name' => 'refresh', 'size' => 28])
    </div>

    {{-- Title --}}
    <div class="font-extrabold"
         style="font-size: 22px; letter-spacing: -0.5px; margin-bottom: 6px">
        Segera Hadir
    </div>

    <div style="font-size: 14px; color: var(--ink-500); max-width: 480px; line-height: 1.55">
        Halaman <strong>{{ $title }}</strong> akan tersedia di
        <strong>{{ $phase }}</strong> pengembangan. Backend endpoint-nya
        sudah jadi — UI-nya menyusul.
    </div>

    {{-- Backend endpoint info --}}
    @php
        $endpoints = match ($pageKey) {
            'routes'   => 'GET/POST/PUT/DELETE /api/v1/admin/routes',
            'vehicles' => 'GET/POST/PUT/DELETE /api/v1/admin/vehicles',
            'stops'    => 'GET/POST/PUT/DELETE /api/v1/admin/stops',
            'apikeys'  => '(belum ada)',
            'webhooks' => '(belum ada)',
            default    => '',
        };
    @endphp
    @if ($endpoints && $endpoints !== '(belum ada)')
        <div class="mono"
             style="margin-top: 18px; padding: 10px 14px;
                    background: var(--ink-25); border: 1px solid var(--ink-100);
                    border-radius: 8px; font-size: 12px; color: var(--ink-500)">
            Backend siap: <span style="color: var(--ink-700); font-weight: 600">{{ $endpoints }}</span>
        </div>
    @endif

    {{-- Back to dashboard --}}
    <a href="{{ route('operator.dashboard') }}"
       class="font-semibold mt-6"
       style="font-size: 13px; color: var(--brand)">
        ← Kembali ke Dashboard
    </a>
</div>

@endsection
