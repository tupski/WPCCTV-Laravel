@extends('layouts.admin')
@use('App\Support\Format')

@section('title', 'Analitik | WP CCTV Indonesia')
@section('page_title', 'Analitik')
@section('page_subtitle', 'Rentang {{ $rangeLabel }}. Angka berasal dari rekap harian yang diperbarui setiap dini hari.')

@section('page_actions')
    <a href="{{ route('admin.analytics-ui.whatsapp-clicks', ['days' => $rangeDays]) }}" class="inline-flex min-h-10 items-center text-sm font-medium text-brand-blue">Rincian klik WhatsApp</a>
@endsection

@section('content')
    <nav aria-label="Pilih rentang analitik" class="border-b border-slate-200">
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

    @if($includesToday)
        <p class="mt-3 text-sm text-slate-600">
            Baris hari ini belum lengkap karena rekap harian berjalan setelah tengah malam.
        </p>
    @endif

    <section class="mt-6" aria-labelledby="total-heading">
        <h2 id="total-heading" class="text-base font-semibold text-brand-navy">Total {{ $rangeLabel }}</h2>

        <dl class="mt-3 border-t border-slate-200">
            @foreach($totals as $total)
                <div class="flex flex-wrap items-baseline justify-between gap-x-6 gap-y-1 border-b border-slate-200 py-2">
                    <dt class="text-sm text-slate-700">{{ $total['label'] }}</dt>
                    <dd class="text-right">
                        <span class="text-lg font-semibold tabular-nums text-brand-navy">{{ Format::number($total['value']) }}</span>
                        <span class="ml-2 block text-xs text-slate-600">{{ $total['note'] }}</span>
                    </dd>
                </div>
            @endforeach
        </dl>
    </section>

    <section class="mt-10" aria-labelledby="harian-heading">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h2 id="harian-heading" class="text-base font-semibold text-brand-navy">Klik WhatsApp per hari</h2>
            <p class="text-sm text-slate-600">Panjang batang sebanding dengan hari teramai pada rentang ini.</p>
        </div>

        @if($bars === [])
            @include('admin.partials.empty-state', [
                'message' => 'Belum ada data harian pada rentang ini. Pilih rentang yang lebih panjang atau pastikan pelacakan WhatsApp aktif di halaman publik.',
                'actionUrl' => route('admin.analytics-ui.index', ['days' => 90]),
                'actionLabel' => 'Coba rentang 90 hari',
            ])
        @else
            <table class="mt-3 w-full border-collapse text-sm">
                <caption class="sr-only">Jumlah klik WhatsApp dan lead per tanggal</caption>
                <thead>
                    <tr class="border-b border-slate-300 text-left text-slate-600">
                        <th scope="col" class="py-2 pr-3 font-medium">Tanggal</th>
                        <th scope="col" class="py-2 px-3 font-medium">Klik WhatsApp</th>
                        <th scope="col" class="py-2 px-3 text-right font-medium">Klik</th>
                        <th scope="col" class="py-2 pl-3 text-right font-medium">Lead</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bars as $bar)
                        <tr class="border-b border-slate-200">
                            <th scope="row" class="whitespace-nowrap py-2 pr-3 text-left font-normal text-slate-700">{{ $bar['date'] }}</th>
                            <td class="py-2 px-3">
                                <span class="block h-2 w-full bg-slate-200" aria-hidden="true">
                                    <span class="block h-2 bg-brand-blue" style="width: {{ $bar['width'] }}%"></span>
                                </span>
                            </td>
                            <td class="py-2 px-3 text-right tabular-nums text-slate-800">{{ Format::number($bar['value']) }}</td>
                            <td class="py-2 pl-3 text-right tabular-nums text-slate-800">{{ Format::number($bar['leads']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>

    <section class="mt-10" aria-labelledby="corong-heading">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h2 id="corong-heading" class="text-base font-semibold text-brand-navy">Corong konversi</h2>
            <p class="text-sm text-slate-600">Persentase dihitung dari langkah sebelumnya; tanda {{ Format::MISSING }} berarti penyebutnya nol.</p>
        </div>

        <div class="mt-3 overflow-x-auto">
            <table class="w-full min-w-[36rem] border-collapse text-sm">
                <caption class="sr-only">Klik WhatsApp sampai lead deal beserta konversi antar langkah</caption>
                <thead>
                    <tr class="border-b border-slate-300 text-left text-slate-600">
                        <th scope="col" class="py-2 pr-3 font-medium">Langkah</th>
                        <th scope="col" class="py-2 px-3 text-right font-medium">Jumlah</th>
                        <th scope="col" class="py-2 px-3 text-right font-medium">Dari langkah sebelumnya</th>
                        <th scope="col" class="py-2 pl-3 text-right font-medium">Penyebut</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($funnel as $step)
                        <tr class="border-b border-slate-200">
                            <th scope="row" class="py-2 pr-3 text-left font-normal text-slate-700">{{ $step['label'] }}</th>
                            <td class="py-2 px-3 text-right font-semibold tabular-nums text-brand-navy">{{ Format::number($step['count']) }}</td>
                            <td class="py-2 px-3 text-right tabular-nums text-slate-800">{{ Format::percent($step['ratio']) }}</td>
                            <td class="py-2 pl-3 text-right tabular-nums text-slate-600">
                                {{ $step['denominator'] === null ? Format::MISSING : Format::number($step['denominator']) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection
