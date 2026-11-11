@extends('layouts.auth')

@section('title', 'Sign in')
@section('hero_title', 'Welcome back to OpsControl.')
@section('hero_text', 'Sign in as Head Office to monitor branches, or as a Branch user to submit your RCSA.')

@section('content')
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 sm:p-8">
        <div class="mb-6">
            <h2 class="text-xl font-bold text-slate-900">Sign in</h2>
            <p class="text-xs text-slate-500 mt-1">Use your work email and password.</p>
        </div>

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="block text-[11px] font-semibold text-slate-500 mb-1">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-azania-navy/30 focus:border-azania-navy">
                @error('email')
                    <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-[11px] font-semibold text-slate-500 mb-1">Password</label>
                <div class="relative">
                    <input id="password" type="password" name="password" required autocomplete="current-password"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-azania-navy/30 focus:border-azania-navy">
                    <button type="button" data-toggle-password="password" class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-700">
                        <i class="fa-regular fa-eye"></i>
                    </button>
                </div>
                @error('password')
                    <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <label class="flex items-center gap-2 text-xs text-slate-600">
                <input type="checkbox" name="remember" value="1" @checked(old('remember')) class="rounded border-slate-300 text-azania-navy focus:ring-azania-navy">
                Keep me signed in
            </label>

            <button type="submit" class="w-full rounded-xl bg-azania-navy hover:bg-blue-900 text-white text-sm font-semibold py-2.5 transition">
                Sign in
            </button>
        </form>

        <p class="mt-5 text-xs text-slate-500 text-center">
            New here?
            <a href="{{ route('register') }}" class="font-semibold text-azania-navy hover:underline">Create an account</a>
        </p>
    </div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-toggle-password]').forEach((btn) => {
    btn.addEventListener('click', () => {
        const input = document.getElementById(btn.dataset.togglePassword);
        if (!input) return;
        input.type = input.type === 'password' ? 'text' : 'password';
        btn.querySelector('i')?.classList.toggle('fa-eye');
        btn.querySelector('i')?.classList.toggle('fa-eye-slash');
    });
});
</script>
@endpush
