@extends('layouts.admin')
@use('App\Support\Format')

@section('title', 'Dasbor | WP CCTV Indonesia')
@section('page_title', 'Dasbor')
@section('page_subtitle', 'Aktivitas WhatsApp, lead, dan pekerjaan yang menunggu tindak lanjut.')

@section('content')
    <section aria-labelledby="ringkasan-heading">
        <h2 id="ringkasan-heading" class="text-base font-semibold text-brand-navy">Ringkasan</h2>
        <p class="mt-1 text-sm text-slate-600">
            Hari ini dihitung sampai {{ $todayLabel }} dan diperbarui paling lambat 5 menit lalu; pekan berjalan mencakup {{ $weekLabel }}.
        </p>

        <div class="mt-3 overflow-x-auto">
            <table class="w-full min-w-[32rem] border-collapse text-sm">
                <caption class="sr-only">Klik WhatsApp, lead dibuat, dan lead deal hari ini dibandingkan tujuh hari terakhir</caption>
                <thead>
                    <tr class="border-b border-slate-300 text-left text-slate-600">
                        <th scope="col" class="py-2 pr-3 font-medium">Ukuran</th>
                        <th scope="col" class="py-2 px-3 text-right font-medium">Hari ini</th>
                        <th scope="col" class="py-2 pl-3 text-right font-medium">7 hari sampai {{ $todayLabel }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($metrics as $metric)
                        <tr class="border-b border-slate-200">
                            <th scope="row" class="py-2 pr-3 text-left font-normal text-slate-700">{{ $metric['label'] }}</th>
                            <td class="py-2 px-3 text-right font-semibold tabular-nums text-brand-navy">{{ Format::number($metric['today']) }}</td>
                            <td class="py-2 pl-3 text-right tabular-nums text-slate-700">{{ Format::number($metric['week']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="mt-10" aria-labelledby="tindak-lanjut-heading">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h2 id="tindak-lanjut-heading" class="text-base font-semibold text-brand-navy">Perlu ditindaklanjuti</h2>
            <p class="text-sm text-slate-600">Tenggat {{ $todayLabel }} atau lebih awal, maksimal 8 lead terdekat.</p>
        </div>

        @if($needsFollowUp === [])
            @include('admin.partials.empty-state', [
                'message' => 'Tidak ada lead yang jatuh tempo hari ini. Tetapkan tanggal tindak lanjut pada lead yang masih berjalan agar pekerjaan berikutnya tidak terlewat.',
                'actionUrl' => route('admin.leads-ui.index'),
                'actionLabel' => 'Buka daftar lead',
            ])
        @else
            <div class="mt-3 overflow-x-auto">
                <table class="w-full min-w-[48rem] border-collapse text-sm">
                    <caption class="sr-only">Lead yang menunggu tindak lanjut</caption>
                    <thead>
                        <tr class="border-b border-slate-300 text-left text-slate-600">
                            <th scope="col" class="py-2 pr-3 font-medium">Pelanggan</th>
                            <th scope="col" class="py-2 px-3 font-medium">Status</th>
                            <th scope="col" class="py-2 px-3 font-medium">Kebutuhan</th>
                            <th scope="col" class="py-2 px-3 font-medium">Penanggung jawab</th>
                            <th scope="col" class="py-2 pl-3 font-medium">Tindak lanjut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($needsFollowUp as $lead)
                            <tr class="border-b border-slate-200 align-top">
                                <th scope="row" class="py-2 pr-3 text-left font-normal">
                                    <a href="{{ route('admin.leads-ui.show', $lead['id']) }}" class="font-medium text-brand-navy">{{ $lead['customer'] }}</a>
                                    @if($lead['phone'])
                                        <span class="mt-0.5 block text-xs text-slate-600">{{ $lead['phone'] }}</span>
                                    @endif
                                </th>
                                <td class="py-2 px-3">
                                    @include('admin.partials.status-badge', ['label' => $lead['status'], 'tone' => $lead['status_tone']])
                                </td>
                                <td class="py-2 px-3 text-slate-700">{{ $lead['type'] }}</td>
                                <td class="py-2 px-3 text-slate-700">{{ $lead['assignee'] ?? 'Belum ditugaskan' }}</td>
                                <td class="py-2 pl-3 text-slate-700">
                                    <span class="block font-medium">{{ $lead['deadline'] }}</span>
                                    <span class="block text-xs text-slate-600">{{ $lead['deadline_at'] }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="mt-10" aria-labelledby="klik-terbaru-heading">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h2 id="klik-terbaru-heading" class="text-base font-semibold text-brand-navy">Klik WhatsApp terbaru</h2>
            <p class="text-sm text-slate-600">Lima klik terakhir, tanpa batas tanggal.</p>
        </div>

        @if($latestClicks === [])
            @include('admin.partials.empty-state', [
                'message' => 'Belum ada klik WhatsApp yang tercatat. Pastikan tombol WhatsApp terpasang di halaman produk dan paket agar kunjungan dapat dihubungkan ke lead.',
                'actionUrl' => route('admin.analytics-ui.whatsapp-clicks'),
                'actionLabel' => 'Lihat rekap klik WhatsApp',
            ])
        @else
            <div class="mt-3 overflow-x-auto">
                <table class="w-full min-w-[44rem] border-collapse text-sm">
                    <caption class="sr-only">Klik WhatsApp terbaru beserta halaman asal dan perangkat</caption>
                    <thead>
                        <tr class="border-b border-slate-300 text-left text-slate-600">
                            <th scope="col" class="py-2 pr-3 font-medium">Waktu</th>
                            <th scope="col" class="py-2 px-3 font-medium">Halaman asal</th>
                            <th scope="col" class="py-2 px-3 font-medium">Jenis halaman</th>
                            <th scope="col" class="py-2 px-3 font-medium">Perangkat</th>
                            <th scope="col" class="py-2 pl-3 font-medium">Tindak lanjut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($latestClicks as $click)
                            <tr class="border-b border-slate-200">
                                <th scope="row" class="py-2 pr-3 text-left font-normal text-slate-700">
                                    <span class="block">{{ $click['when'] }}</span>
                                    <span class="block text-xs text-slate-600">{{ $click['relative'] }}</span>
                                </th>
                                <td class="py-2 px-3 text-slate-700">{{ $click['title'] }}</td>
                                <td class="py-2 px-3 text-slate-700">{{ $click['page_type'] }}</td>
                                <td class="py-2 px-3 text-slate-700">{{ $click['device'] }}</td>
                                <td class="py-2 pl-3">
                                    @if($click['converted'])
                                        <span class="text-slate-700">Sudah menjadi lead</span>
                                    @else
                                        <a href="{{ route('admin.leads-ui.convert.form', $click['id']) }}" class="font-medium text-brand-blue">Buat lead dari klik ini</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
