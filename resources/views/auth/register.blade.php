@extends('layouts.auth')

@section('title', 'Create account')
@section('hero_title', 'Create your OpsControl account.')
@section('hero_text', 'Pick Head Office for monitoring, or Branch to work only on your branch RCSA.')

@section('content')
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 sm:p-8">
        <div class="mb-6">
            <h2 class="text-xl font-bold text-slate-900">Create account</h2>
            <p class="text-xs text-slate-500 mt-1">Your role decides what you can see and do.</p>
        </div>

        <form method="POST" action="{{ route('register') }}" id="registerForm" class="space-y-4">
            @csrf

            <div>
                <p class="block text-[11px] font-semibold text-slate-500 mb-2">I am registering as</p>
                <div class="grid grid-cols-2 gap-2">
                    <label class="role-card cursor-pointer rounded-xl border-2 p-3 transition {{ old('role', 'branch') === 'hq' ? 'border-azania-navy bg-blue-50' : 'border-slate-200 bg-slate-50' }}">
                        <input type="radio" name="role" value="hq" class="sr-only" @checked(old('role') === 'hq')>
                        <div class="flex items-start gap-2">
                            <i class="fa-solid fa-building-columns text-azania-navy mt-0.5"></i>
                            <div>
                                <p class="text-xs font-bold text-slate-900">Head Office</p>
                                <p class="text-[10px] text-slate-500 mt-0.5 leading-snug">Monitor, unlock, extend deadlines</p>
                            </div>
                        </div>
                    </label>
                    <label class="role-card cursor-pointer rounded-xl border-2 p-3 transition {{ old('role', 'branch') === 'branch' ? 'border-emerald-600 bg-emerald-50' : 'border-slate-200 bg-slate-50' }}">
                        <input type="radio" name="role" value="branch" class="sr-only" @checked(old('role', 'branch') === 'branch')>
                        <div class="flex items-start gap-2">
                            <i class="fa-solid fa-store text-emerald-700 mt-0.5"></i>
                            <div>
                                <p class="text-xs font-bold text-slate-900">Branch</p>
                                <p class="text-[10px] text-slate-500 mt-0.5 leading-snug">Submit and view your branch only</p>
                            </div>
                        </div>
                    </label>
                </div>
                @error('role')
                    <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div id="branchField" class="{{ old('role', 'branch') === 'branch' ? '' : 'hidden' }}">
                <label for="branch_id" class="block text-[11px] font-semibold text-slate-500 mb-1">Branch</label>
                <select id="branch_id" name="branch_id"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600/30 focus:border-emerald-600">
                    <option value="">Select your branch</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) old('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
                @error('branch_id')
                    <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="name" class="block text-[11px] font-semibold text-slate-500 mb-1">Full name</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autocomplete="name"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-azania-navy/30 focus:border-azania-navy">
                @error('name')
                    <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="block text-[11px] font-semibold text-slate-500 mb-1">Work email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-azania-navy/30 focus:border-azania-navy">
                @error('email')
                    <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="password" class="block text-[11px] font-semibold text-slate-500 mb-1">Password</label>
                    <input id="password" type="password" name="password" required autocomplete="new-password"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-azania-navy/30 focus:border-azania-navy">
                    @error('password')
                        <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="password_confirmation" class="block text-[11px] font-semibold text-slate-500 mb-1">Confirm</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-azania-navy/30 focus:border-azania-navy">
                </div>
            </div>
            <p class="text-[10px] text-slate-400">Use at least 8 characters with letters and numbers.</p>

            <button type="submit" class="w-full rounded-xl bg-azania-navy hover:bg-blue-900 text-white text-sm font-semibold py-2.5 transition">
                Create account
            </button>
        </form>

        <p class="mt-5 text-xs text-slate-500 text-center">
            Already have an account?
            <a href="{{ route('login') }}" class="font-semibold text-azania-navy hover:underline">Sign in</a>
        </p>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    const branchField = document.getElementById('branchField');
    const branchSelect = document.getElementById('branch_id');
    const cards = document.querySelectorAll('.role-card');

    function paint() {
        const role = document.querySelector('input[name="role"]:checked')?.value || 'branch';
        cards.forEach((card) => {
            const input = card.querySelector('input[name="role"]');
            const active = input?.value === role;
            card.classList.toggle('border-azania-navy', active && role === 'hq');
            card.classList.toggle('bg-blue-50', active && role === 'hq');
            card.classList.toggle('border-emerald-600', active && role === 'branch');
            card.classList.toggle('bg-emerald-50', active && role === 'branch');
            card.classList.toggle('border-slate-200', !active);
            card.classList.toggle('bg-slate-50', !active);
        });
        const isBranch = role === 'branch';
        branchField?.classList.toggle('hidden', !isBranch);
        if (branchSelect) branchSelect.required = isBranch;
        if (!isBranch && branchSelect) branchSelect.value = '';
    }

    cards.forEach((card) => card.addEventListener('click', () => {
        const input = card.querySelector('input[name="role"]');
        if (input) input.checked = true;
        paint();
    }));
    paint();
})();
</script>
@endpush
