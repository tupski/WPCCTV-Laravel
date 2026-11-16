@extends('layouts.admin')
@use('App\Support\Format')
@use('App\Support\Labels')

@section('title', 'Klik WhatsApp | WP CCTV Indonesia')
@section('page_title', 'Klik WhatsApp')
@section('page_subtitle', 'Rentang {{ $rangeLabel }}. Total {{ Format::number($total) }} klik tercatat.')

@section('page_actions')
    <a href="{{ route('admin.analytics-ui.index', ['days' => $rangeDays]) }}" class="inline-flex min-h-10 items-center text-sm font-medium text-brand-blue">Kembali ke analitik</a>
@endsection

@section('content')
    <nav aria-label="Pilih rentang klik WhatsApp" class="border-b border-slate-200">
        <ul class="flex flex-wrap gap-1">
            @foreach($rangeLinks as $link)
                <li>
                    <a
                        href="{{ $link['url'] }}"
                        class="inline-flex min-h-10 items-center border-b-2 px-3 text-sm {{ $link['current'] ? 'border-brand-blue font-semibold text-brand-navy' : 'border-transparent text-slate-600 hover:text-brand-navy' }}"
                        @if($link['current']) aria-current="true" @endif
                    >{{ $link['label'] }}</a>
                </li>
            @endforeach
        </ul>
    </nav>

    <p class="mt-3 text-sm text-slate-600">
        Hari ini belum ikut terhitung penuh sampai rekap harian berjalan.
    </p>

    <div class="mt-6 grid gap-8 sm:grid-cols-2">
        <section aria-labelledby="jenis-halaman-heading">
            <h2 id="jenis-halaman-heading" class="text-base font-semibold text-brand-navy">Menurut jenis halaman</h2>

            @if($byPageType === [])
                @include('admin.partials.empty-state', [
                    'message' => 'Belum ada klik WhatsApp pada rentang ini. Pastikan tombol WhatsApp tersedia di halaman produk, paket, dan layanan.',
                    'actionUrl' => route('admin.analytics-ui.whatsapp-clicks', ['days' => 90]),
                    'actionLabel' => 'Periksa rentang 90 hari',
                ])
            @else
                <dl class="mt-3 border-t border-slate-200">
                    @foreach($byPageType as $row)
                        <div class="flex items-baseline justify-between gap-4 border-b border-slate-200 py-2 text-sm">
                            <dt class="text-slate-700">{{ $row['label'] }}</dt>
                            <dd class="text-right">
                                <span class="font-semibold tabular-nums text-brand-navy">{{ Format::number($row['count']) }}</span>
                                <span class="ml-2 tabular-nums text-xs text-slate-600">{{ $row['share'] }}</span>
                            </dd>
                        </div>
                    @endforeach
                </dl>
            @endif
        </section>

        <section aria-labelledby="perangkat-heading">
            <h2 id="perangkat-heading" class="text-base font-semibold text-brand-navy">Menurut perangkat</h2>

            @if($byDevice === [])
                @include('admin.partials.empty-state', [
                    'message' => 'Belum ada klik WhatsApp pada rentang ini, sehingga sebaran perangkat belum bisa disimpulkan.',
                ])
            @else
                <dl class="mt-3 border-t border-slate-200">
                    @foreach($byDevice as $row)
                        <div class="flex items-baseline justify-between gap-4 border-b border-slate-200 py-2 text-sm">
                            <dt class="text-slate-700">{{ $row['label'] }}</dt>
                            <dd class="text-right">
                                <span class="font-semibold tabular-nums text-brand-navy">{{ Format::number($row['count']) }}</span>
                                <span class="ml-2 tabular-nums text-xs text-slate-600">{{ $row['share'] }}</span>
                            </dd>
                        </div>
                    @endforeach
                </dl>
            @endif
        </section>
    </div>

    <section class="mt-10" aria-labelledby="daftar-klik-heading">
        <h2 id="daftar-klik-heading" class="text-base font-semibold text-brand-navy">Daftar klik</h2>

        @if($clicks->isEmpty())
            @include('admin.partials.empty-state', [
                'message' => 'Tidak ada klik WhatsApp pada rentang ini. Buka rentang yang lebih panjang untuk melihat riwayat sebelumnya.',
                'actionUrl' => route('admin.analytics-ui.whatsapp-clicks', ['days' => 90]),
                'actionLabel' => 'Coba rentang 90 hari',
            ])
        @else
            <div class="mt-3 overflow-x-auto">
                <table class="w-full min-w-[52rem] border-collapse text-sm">
                    <caption class="sr-only">Daftar klik WhatsApp beserta halaman asal, perangkat, dan status konversi</caption>
                    <thead>
                        <tr class="border-b border-slate-300 text-left text-slate-600">
                            <th scope="col" class="py-2 pr-3 font-medium">Waktu</th>
                            <th scope="col" class="py-2 px-3 font-medium">Halaman asal</th>
                            <th scope="col" class="py-2 px-3 font-medium">Jenis halaman</th>
                            <th scope="col" class="py-2 px-3 font-medium">Perangkat</th>
                            <th scope="col" class="py-2 pl-3 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($clicks as $click)
                            <tr class="border-b border-slate-200 align-top">
                                <th scope="row" class="whitespace-nowrap py-2 pr-3 text-left font-normal text-slate-700">
                                    <span class="block">{{ Format::dateTime($click->created_at) }}</span>
                                    <span class="block text-xs text-slate-600">{{ Format::relative($click->created_at) }}</span>
                                </th>
                                <td class="py-2 px-3 text-slate-700">{{ $click->page_title ?: $click->page_url }}</td>
                                <td class="py-2 px-3 text-slate-700">{{ Labels::pageType($click->page_type) }}</td>
                                <td class="py-2 px-3 text-slate-700">{{ Labels::device($click->device_category) }}</td>
                                <td class="py-2 pl-3">
                                    @if($click->converted_lead_id !== null)
                                        @can('manage-leads')
                                            <a href="{{ route('admin.leads-ui.show', $click->converted_lead_id) }}" class="font-medium text-brand-blue">Sudah menjadi lead</a>
                                        @else
                                            <span class="text-slate-700">Sudah menjadi lead</span>
                                        @endcan
                                    @else
                                        @can('manage-leads')
                                            <a href="{{ route('admin.leads-ui.convert.form', $click->id) }}" class="font-medium text-brand-blue">Buat lead</a>
                                        @else
                                            <span class="text-slate-700">Belum dikonversi</span>
                                        @endcan
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $clicks->links() }}
            </div>
        @endif
    </section>
@endsection
