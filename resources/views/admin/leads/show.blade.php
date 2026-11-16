@extends('layouts.admin')
@use('App\Support\Format')
@use('App\Support\Labels')

@section('title', ($lead->customer_name ?: 'Lead').' | WP CCTV Indonesia')
@section('page_title', $lead->customer_name ?: 'Lead tanpa nama pelanggan')

@section('page_subtitle')
    Lead #{{ $lead->id }}, masuk {{ Format::date($lead->created_at) }} ({{ Format::relative($lead->created_at) }}).
@endsection

@section('page_actions')
    <a href="{{ route('admin.leads-ui.index') }}" class="inline-flex min-h-10 items-center text-sm font-medium text-brand-blue">Kembali ke daftar lead</a>
@endsection

@section('content')
    <div class="grid gap-8 lg:grid-cols-3">
        <section class="lg:col-span-2" aria-labelledby="fakta-heading">
            <h2 id="fakta-heading" class="text-base font-semibold text-brand-navy">Data lead</h2>

            <dl class="mt-3 grid grid-cols-1 gap-x-8 gap-y-3 border-t border-slate-200 pt-3 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-slate-600">Status</dt>
                    <dd class="mt-1">
                        @include('admin.partials.status-badge', [
                            'label' => Labels::status($lead->status),
                            'tone' => Labels::statusTone($lead->status),
                        ])
                    </dd>
                </div>
                <div>
                    <dt class="text-slate-600">Nomor WhatsApp</dt>
                    <dd class="mt-1 text-slate-800">{{ $lead->whatsapp_number ?: Format::MISSING }}</dd>
                </div>
                <div>
                    <dt class="text-slate-600">Jenis kebutuhan</dt>
                    <dd class="mt-1 text-slate-800">{{ Labels::leadType($lead->lead_type) }}</dd>
                </div>
                <div>
                    <dt class="text-slate-600">Prioritas</dt>
                    <dd class="mt-1 text-slate-800">{{ Labels::priority($lead->priority) }}</dd>
                </div>
                <div>
                    <dt class="text-slate-600">Penanggung jawab</dt>
                    <dd class="mt-1 text-slate-800">{{ $lead->assignedTo?->name ?? 'Belum ditugaskan' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-600">Jadwal tindak lanjut</dt>
                    <dd class="mt-1 text-slate-800">
                        {{ $followUp['date'] }}
                        <span class="block text-xs text-slate-600">{{ $followUp['deadline'] }}</span>
                    </dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-slate-600">Kebutuhan yang disampaikan</dt>
                    <dd class="mt-1 whitespace-pre-line text-slate-800">{{ $lead->requirements ?: 'Belum ada keterangan kebutuhan dari pelanggan.' }}</dd>
                </div>
            </dl>

            <h2 id="asal-heading" class="mt-8 text-base font-semibold text-brand-navy">Asal klik WhatsApp</h2>
            @if($click)
                <dl class="mt-3 grid grid-cols-1 gap-x-8 gap-y-3 border-t border-slate-200 pt-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-slate-600">Halaman asal</dt>
                        <dd class="mt-1 text-slate-800">{{ $click->page_title ?: $click->page_url }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-600">Jenis halaman</dt>
                        <dd class="mt-1 text-slate-800">{{ Labels::pageType($click->page_type) }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-600">Waktu klik</dt>
                        <dd class="mt-1 text-slate-800">{{ Format::dateTime($click->created_at) }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-600">Perangkat</dt>
                        <dd class="mt-1 text-slate-800">{{ Labels::device($click->device_category) }}</dd>
                    </div>
                    @if($click->utm_source || $click->utm_campaign)
                        <div>
                            <dt class="text-slate-600">Sumber kampanye</dt>
                            <dd class="mt-1 text-slate-800">{{ collect([$click->utm_source, $click->utm_medium, $click->utm_campaign])->filter()->implode(' / ') }}</dd>
                        </div>
                    @endif
                </dl>
            @else
                @include('admin.partials.empty-state', [
                    'message' => 'Lead ini dibuat manual tanpa klik WhatsApp, sehingga tidak ada halaman asal yang bisa ditelusuri.',
                ])
            @endif

            <h2 id="riwayat-heading" class="mt-8 text-base font-semibold text-brand-navy">Riwayat aktivitas</h2>
            <p class="mt-1 text-sm text-slate-600">
                Urut dari yang terbaru. Sejak lead dibuat, {{ Format::number(count($activities)) }} aktivitas tercatat, maksimal 50 terakhir ditampilkan.
            </p>

            @if($activities === [])
                @include('admin.partials.empty-state', [
                    'message' => 'Belum ada aktivitas pada lead ini. Tambahkan catatan hasil telepon atau pesan WhatsApp agar riwayatnya dapat dipertanggungjawabkan.',
                ])
            @else
                <div class="mt-3 overflow-x-auto">
                    <table class="w-full min-w-[40rem] border-collapse text-sm">
                        <caption class="sr-only">Riwayat aktivitas lead, terbaru lebih dulu</caption>
                        <thead>
                            <tr class="border-b border-slate-300 text-left text-slate-600">
                                <th scope="col" class="py-2 pr-3 font-medium">Terbaru</th>
                                <th scope="col" class="py-2 px-3 font-medium">Pelaku</th>
                                <th scope="col" class="py-2 px-3 font-medium">Aktivitas</th>
                                <th scope="col" class="py-2 pl-3 font-medium">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activities as $activity)
                                <tr class="border-b border-slate-200 align-top">
                                    <th scope="row" class="py-2 pr-3 text-left font-normal text-slate-700">
                                        <span class="block">{{ $activity['when'] }}</span>
                                        <span class="block text-xs text-slate-600">{{ $activity['relative'] }}</span>
                                    </th>
                                    <td class="py-2 px-3 text-slate-700">{{ $activity['who'] }}</td>
                                    <td class="py-2 px-3 text-slate-700">
                                        {{ $activity['action'] }}
                                        @if($activity['field'])
                                            <span class="block text-xs text-slate-600">{{ $activity['field'] }}</span>
                                        @endif
                                    </td>
                                    <td class="py-2 pl-3 text-slate-800">{{ $activity['change'] ?: Format::MISSING }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <aside class="space-y-6" aria-labelledby="aksi-heading">
            <h2 id="aksi-heading" class="text-base font-semibold text-brand-navy">Tindakan</h2>

            <form method="POST" action="{{ route('admin.leads-ui.status.update', $lead) }}" class="border-t border-slate-200 pt-3">
                @csrf
                @method('PATCH')
                <label for="status" class="block text-sm font-medium text-slate-700">Ubah status</label>
                <select id="status" name="status" class="mt-1 min-h-10 w-full rounded-md border border-slate-300 px-3 text-sm text-slate-700">
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected($lead->status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-600">Perubahan status tercatat pada riwayat lead beserta waktu dan pelakunya.</p>
                <button type="submit" class="mt-2 min-h-10 w-full rounded-md bg-brand-blue px-4 text-sm font-semibold text-white">Simpan status</button>
            </form>

            <form method="POST" action="{{ route('admin.leads-ui.assignee.update', $lead) }}" class="border-t border-slate-200 pt-3">
                @csrf
                @method('PATCH')
                <label for="assigned_to" class="block text-sm font-medium text-slate-700">Penanggung jawab</label>
                <select id="assigned_to" name="assigned_to" class="mt-1 min-h-10 w-full rounded-md border border-slate-300 px-3 text-sm text-slate-700">
                    <option value="">Belum ditugaskan</option>
                    @foreach($assignees as $assignee)
                        <option value="{{ $assignee->id }}" @selected($lead->assigned_to === $assignee->id)>{{ $assignee->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="mt-2 min-h-10 w-full rounded-md border border-slate-300 px-4 text-sm font-semibold text-brand-navy">Simpan penugasan</button>
            </form>

            <form method="POST" action="{{ route('admin.leads-ui.follow-up.update', $lead) }}" class="border-t border-slate-200 pt-3">
                @csrf
                @method('PATCH')
                <label for="follow_up_at" class="block text-sm font-medium text-slate-700">Tanggal tindak lanjut</label>
                <input
                    type="date"
                    id="follow_up_at"
                    name="follow_up_at"
                    value="{{ $followUp['input'] }}"
                    class="mt-1 min-h-10 w-full rounded-md border border-slate-300 px-3 text-sm text-slate-700"
                >
                <p class="mt-1 text-xs text-slate-600">{{ $followUp['deadline'] }}</p>
                <button type="submit" class="mt-2 min-h-10 w-full rounded-md border border-slate-300 px-4 text-sm font-semibold text-brand-navy">Jadwalkan</button>
            </form>

            <form method="POST" action="{{ route('admin.leads-ui.activities.store', $lead) }}" class="border-t border-slate-200 pt-3">
                @csrf
                <label for="note" class="block text-sm font-medium text-slate-700">Catatan baru</label>
                <textarea
                    id="note"
                    name="note"
                    rows="3"
                    class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-700"
                    placeholder="Hasil telepon, kebutuhan kamera, atau kesepakatan survei."
                ></textarea>
                <button type="submit" class="mt-2 min-h-10 w-full rounded-md border border-slate-300 px-4 text-sm font-semibold text-brand-navy">Tambah catatan</button>
            </form>
        </aside>
    </div>
@endsection
