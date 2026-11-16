@if(session('status'))
    <div class="mt-4 rounded-md border border-emerald-700 px-3 py-2 text-sm text-emerald-800" role="status">
        {{ session('status') }}
    </div>
@endif

@if($errors->any())
    <div class="mt-4 rounded-md border border-red-700 px-3 py-2 text-sm text-red-800" role="alert">
        <p class="font-semibold">Periksa kembali isian berikut:</p>
        <ul class="mt-1 list-disc pl-5">
            @foreach($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif
