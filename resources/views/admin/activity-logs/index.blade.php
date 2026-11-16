@extends('layouts.admin')
@use('App\Support\Format')

@section('title', 'Log aktivitas | WP CCTV Indonesia')
@section('page_title', 'Log aktivitas')
@section('page_subtitle', 'Jejak tindakan administrator. Terbaru lebih dulu, 50 baris per halaman.')

@section('content')
    <form method="GET" action="{{ route('admin.activity-logs-ui.index') }}" class="flex flex-wrap items-end gap-3 border-b border-slate-200 pb-4">
        <div>
            <label for="filter-user" class="block text-sm font-medium text-slate-700">Pelaku</label>
            <select id="filter-user" name="user_id" class="mt-1 min-h-10 w-48 rounded-md border border-slate-300 px-3 text-sm text-slate-700">
                <option value="">Semua pelaku</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" @selected((string) $filters['user_id'] === (string) $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="filter-action" class="block text-sm font-medium text-slate-700">Tindakan</label>
            <select id="filter-action" name="action" class="mt-1 min-h-10 w-56 rounded-md border border-slate-300 px-3 text-sm text-slate-700">
                <option value="">Semua tindakan</option>
                @foreach($actions as $action)
                    <option value="{{ $action }}" @selected($filters['action'] === $action)>{{ \App\Support\Labels::activity($action) }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="filter-from" class="block text-sm font-medium text-slate-700">Dari tanggal</label>
            <input
                type="date"
                id="filter-from"
                name="from"
                value="{{ $filters['from'] }}"
                class="mt-1 min-h-10 w-40 rounded-md border border-slate-300 px-3 text-sm text-slate-700"
            >
        </div>

        <div>
            <label for="filter-to" class="block text-sm font-medium text-slate-700">Sampai tanggal</label>
            <input
                type="date"
                id="filter-to"
                name="to"
                value="{{ $filters['to'] }}"
                class="mt-1 min-h-10 w-40 rounded-md border border-slate-300 px-3 text-sm text-slate-700"
            >
        </div>

        <button type="submit" class="min-h-10 rounded-md bg-brand-blue px-4 text-sm font-semibold text-white">Terapkan filter</button>

        @if($hasFilters)
            <a href="{{ route('admin.activity-logs-ui.index') }}" class="inline-flex min-h-10 items-center px-2 text-sm font-medium text-brand-blue">Hapus filter</a>
        @endif
    </form>

    <div class="mt-6">
        @if($logs->isEmpty())
            @if($hasFilters)
                @include('admin.partials.empty-state', [
                    'message' => 'Tidak ada aktivitas yang cocok dengan filter ini. Longgarkan rentang tanggal atau pilih pelaku lain.',
                    'actionUrl' => route('admin.activity-logs-ui.index'),
                    'actionLabel' => 'Hapus semua filter',
                ])
            @else
                @include('admin.partials.empty-state', [
                    'message' => 'Belum ada aktivitas tercatat. Log terisi otomatis ketika admin mengubah status lead, menugaskan lead, atau menyimpan pengaturan.',
                ])
            @endif
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[44rem] border-collapse text-sm">
                    <caption class="sr-only">Log aktivitas administrator</caption>
                    <thead>
                        <tr class="border-b border-slate-300 text-left text-slate-600">
                            <th scope="col" class="py-2 pr-3 font-medium">Waktu</th>
                            <th scope="col" class="py-2 px-3 font-medium">Pelaku</th>
                            <th scope="col" class="py-2 px-3 font-medium">Tindakan</th>
                            <th scope="col" class="py-2 pl-3 font-medium">Objek</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $log)
                            <tr class="border-b border-slate-200 align-top">
                                <th scope="row" class="whitespace-nowrap py-2 pr-3 text-left font-normal text-slate-700">{{ $log['when'] }}</th>
                                <td class="py-2 px-3 text-slate-700">{{ $log['who'] }}</td>
                                <td class="py-2 px-3 text-slate-700">
                                    {{ $log['action_label'] }}
                                    <span class="block text-xs text-slate-600">{{ $log['action'] }}</span>
                                </td>
                                <td class="py-2 pl-3 text-slate-700">
                                    @if($log['subject_id'] !== null)
                                        {{ $log['subject'] }} #{{ $log['subject_id'] }}
                                    @else
                                        {{ $log['subject'] }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
@endsection
