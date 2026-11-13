@extends('layouts.document')

@section('title', 'Masuk | WP CCTV Indonesia')

@section('body')
    <main class="mx-auto flex min-h-screen max-w-lg flex-col justify-center px-4 py-12 sm:px-6">
        <a href="{{ route('home') }}" class="text-sm font-semibold text-brand-blue">Kembali ke beranda</a>
        <h1 class="mt-6 text-2xl font-bold text-brand-navy">Masuk admin</h1>
        <p class="mt-3 text-base leading-7 text-slate-700">Gunakan akun admin yang disiapkan operator.</p>

        <form method="POST" action="{{ route('login.store') }}" class="mt-8 space-y-5">
            @csrf
            <div>
                <label for="email" class="block text-sm font-semibold text-brand-navy">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus class="mt-2 min-h-11 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-base text-slate-900 focus:border-brand-blue">
                @error('email')
                    <p class="mt-2 text-sm text-red-700" role="alert">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="password" class="block text-sm font-semibold text-brand-navy">Kata sandi</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required class="mt-2 min-h-11 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-base text-slate-900 focus:border-brand-blue">
            </div>
            <button type="submit" class="min-h-11 w-full rounded-md bg-brand-blue px-4 py-2 font-semibold text-white hover:bg-blue-800">Masuk</button>
        </form>
    </main>
@endsection
