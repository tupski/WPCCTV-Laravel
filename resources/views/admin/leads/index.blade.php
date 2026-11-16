@extends('layouts.admin')
@use('App\Support\Format')

@section('title', 'Lead | WP CCTV Indonesia')
@section('page_title', 'Lead')
@section('page_subtitle', $canSeeAllLeads
    ? 'Semua lead masuk, diurutkan dari yang terbaru.'
    : 'Lead yang ditugaskan kepada Anda, diurutkan dari yang terbaru.')

@section('content')
    {{-- Status as segmented links with counts: the count belongs to the link. --}}
    <nav aria-label="Filter status lead" class="border-b border-slate-200">
        <ul class="flex flex-wrap gap-1">
            @foreach($statusOptions as $option)
                @php
                    $query = array_filter([
                        'status' => $option['key'],
                        'assigned_to' => $filters['assigned_to'],
                        'lead_type' => $filters['lead_type'],
                        'q' => $filters['q'],
                    ], fn ($value) => $value !== null);
                    $isCurrent = $filters['status'] === $option['key'];
                @endphp
                <li>
                    <a
                        href="{{ route('admin.leads-ui.index', $query) }}"
                        class="inline-flex min-h-10 items-center gap-2 border-b-2 px-3 text-sm {{ $isCurrent ? 'border-brand-blue font-semibold text-brand-navy' : 'border-transparent text-slate-600 hover:text-brand-navy' }}"
                        @if($isCurrent) aria-current="true" @endif
                    >
                        {{ $option['label'] }}
                        <span class="tabular-nums text-xs text-slate-600">{{ Format::number($option['count']) }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    <form method="GET" action="{{ route('admin.leads-ui.index') }}" class="mt-4 flex flex-wrap items-end gap-3">
        <div>
            <label for="filter-q" class="block text-sm font-medium text-slate-700">Nama atau nomor WhatsApp</label>
            <input
                type="search"
                id="filter-q"
                name="q"
                value="{{ $filters['q'] }}"
                class="mt-1 min-h-10 w-56 rounded-md border border-slate-300 px-3 text-sm text-slate-700"
            >
        </div>

        <div>
            <label for="filter-lead-type" class="block text-sm font-medium text-slate-700">Jenis kebutuhan</label>
            <select
                id="filter-lead-type"
                name="lead_type"
                class="mt-1 min-h-10 w-48 rounded-md border border-slate-300 px-3 text-sm text-slate-700"
            >
                <option value="">Semua jenis</option>
                @foreach($leadTypes as $value => $label)
                    <option value="{{ $value }}" @selected($filters['lead_type'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        @if($canSeeAllLeads)
            <div>
                <label for="filter-assigned" class="block text-sm font-medium text-slate-700">Penanggung jawab</label>
                <select
                    id="filter-assigned"
                    name="assigned_to"
                    class="mt-1 min-h-10 w-48 rounded-md border border-slate-300 px-3 text-sm text-slate-700"
                >
                    <option value="">Semua penanggung jawab</option>
                    <option value="unassigned" @selected($filters['assigned_to'] === 'unassigned')>Belum ditugaskan</option>
                    @foreach($assignees as $assignee)
                        <option value="{{ $assignee->id }}" @selected($filters['assigned_to'] === (string) $assignee->id)>{{ $assignee->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <input type="hidden" name="status" value="{{ $filters['status'] }}">

        <button type="submit" class="min-h-10 rounded-md bg-brand-blue px-4 text-sm font-semibold text-white">Terapkan filter</button>

        @if($hasFilters)
            <a href="{{ route('admin.leads-ui.index') }}" class="inline-flex min-h-10 items-center px-2 text-sm font-medium text-brand-blue">Hapus filter</a>
        @endif
    </form>

    <div class="mt-6">
        @if($leads->isEmpty())
            @if($hasFilters)
                @include('admin.partials.empty-state', [
                    'message' => 'Tidak ada lead yang cocok dengan filter ini. Longgarkan salah satu filter atau hapus semuanya untuk melihat seluruh lead.',
                    'actionUrl' => route('admin.leads-ui.index'),
                    'actionLabel' => 'Hapus semua filter',
                ])
            @elseif(auth()->user()->can('view-analytics'))
                @include('admin.partials.empty-state', [
                    'message' => 'Belum ada lead masuk. Lead terbentuk ketika pengunjung menekan tombol WhatsApp dan tim menindaklanjutinya dari klik tersebut.',
                    'actionUrl' => route('admin.analytics-ui.whatsapp-clicks'),
                    'actionLabel' => 'Lihat klik WhatsApp yang bisa dikonversi',
                ])
            @else
                @include('admin.partials.empty-state', [
                    'message' => 'Belum ada lead yang ditugaskan kepada Anda. Lead baru muncul di sini setelah administrator membagikan klik WhatsApp kepada Anda.',
                ])
            @endif
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[56rem] border-collapse text-sm">
                    <caption class="sr-only">Daftar lead beserta status, kebutuhan, dan jadwal tindak lanjut</caption>
                    <thead>
                        <tr class="border-b border-slate-300 text-left text-slate-600">
                            <th scope="col" class="py-2 pr-3 font-medium">Pelanggan</th>
                            <th scope="col" class="py-2 px-3 font-medium">Status</th>
                            <th scope="col" class="py-2 px-3 font-medium">Kebutuhan</th>
                            <th scope="col" class="py-2 px-3 font-medium">Penanggung jawab</th>
                            <th scope="col" class="py-2 px-3 font-medium">Masuk</th>
                            <th scope="col" class="py-2 pl-3 font-medium">Tindak lanjut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($leads as $lead)
                            <tr class="border-b border-slate-200 align-top">
                                <th scope="row" class="py-2 pr-3 text-left font-normal">
                                    <a href="{{ route('admin.leads-ui.show', $lead) }}" class="font-medium text-brand-navy">
                                        {{ $lead->customer_name ?: 'Tanpa nama pelanggan' }}
                                    </a>
                                    @if($lead->whatsapp_number)
                                        <span class="mt-0.5 block text-xs text-slate-600">{{ $lead->whatsapp_number }}</span>
                                    @endif
                                </th>
                                <td class="py-2 px-3">
                                    @include('admin.partials.status-badge', [
                                        'label' => \App\Support\Labels::status($lead->status),
                                        'tone' => \App\Support\Labels::statusTone($lead->status),
                                    ])
                                </td>
                                <td class="py-2 px-3 text-slate-700">{{ \App\Support\Labels::leadType($lead->lead_type) }}</td>
                                <td class="py-2 px-3 text-slate-700">{{ $lead->assignedTo?->name ?? 'Belum ditugaskan' }}</td>
                                <td class="py-2 px-3 text-slate-700">
                                    <span class="block">{{ Format::date($lead->created_at) }}</span>
                                    <span class="block text-xs text-slate-600">{{ Format::relative($lead->created_at) }}</span>
                                </td>
                                <td class="py-2 pl-3 text-slate-700">
                                    <span class="block">{{ Format::date($lead->follow_up_at) }}</span>
                                    <span class="block text-xs text-slate-600">{{ Format::deadline($lead->follow_up_at) }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $leads->links() }}
            </div>
        @endif
    </div>
@endsection
