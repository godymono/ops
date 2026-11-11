@extends('layouts.dashboard')

@section('content')
    <header class="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 sticky top-0 z-30 shadow-sm">
        <div class="max-w-12xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <div class="bg-emerald-700 text-white p-2.5 rounded-xl shadow-sm">
                    <i class="fa-solid fa-clipboard-check"></i>
                </div>
                <div class="min-w-0">
                    <h1 class="font-bold text-slate-900 dark:text-white text-sm sm:text-base truncate">RCSA Workspace</h1>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                        {{ $checklist['period']['label'] ?? '' }} · {{ $branch }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span id="workspaceStatusBadge" class="text-[10px] font-bold px-2 py-1 rounded bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300 uppercase">
                    {{ ($checklist['branchStatus'] ?? 'pending') === 'unlocked' ? 'Unlocked' : 'Draft' }}
                </span>
                <a href="{{ url('/') }}?tab=rcsa" class="px-3 py-1.5 rounded-lg text-xs font-semibold border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800">
                    Back
                </a>
                <button type="button" id="workspaceSubmitBtn" class="bg-indigo-700 hover:bg-indigo-800 text-white px-3 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1.5">
                    <i class="fa-solid fa-paper-plane"></i>
                    <span>Submit</span>
                </button>
                <button type="button" data-action="open-add" data-module="rcsa" class="bg-emerald-700 hover:bg-emerald-800 text-white px-3 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1.5">
                    <i class="fa-solid fa-plus"></i>
                    <span>Add</span>
                </button>
            </div>
        </div>
    </header>

    <div class="bg-amber-50 dark:bg-amber-950/30 border-b border-amber-200 dark:border-amber-900/40 px-4 sm:px-8 py-2 text-xs text-amber-800 dark:text-amber-300 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
        <span><i class="fa-solid fa-unlock mr-1"></i> Editing unlocked for this branch. Submit when done.</span>
        <span>Deadline {{ $checklist['deadlineLabel'] ?? '—' }}</span>
    </div>

    <main class="flex-grow max-w-12xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-4">
        <section id="kpiContainer" class="grid grid-cols-2 lg:grid-cols-4 gap-4"></section>

        <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3.5 shadow-sm">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-slate-400 text-xs"></i>
                    <input type="text" id="searchInput" placeholder="Search..."
                        class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg pl-8 pr-3 py-1.5 text-xs">
                </div>
                <div>
                    <select id="branchFilter" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-1.5 text-xs" disabled>
                        <option value="{{ $branch }}">{{ $branch }}</option>
                    </select>
                </div>
                <div>
                    <select id="categoryFilter" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-1.5 text-xs">
                        <option value="ALL">All Categories</option>
                    </select>
                </div>
                <div>
                    <select id="statusFilter" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-1.5 text-xs">
                        <option value="ALL">All Statuses</option>
                    </select>
                </div>
            </div>
        </section>

        <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">RCSA</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400" id="rcsaCountSummary">0 items</p>
                </div>
            </div>
            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left text-xs table-auto">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-600 dark:text-slate-300 uppercase tracking-wider font-semibold border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="py-3 px-3 w-10 text-center">ID</th>
                            <th class="py-3 px-3 whitespace-nowrap">Branch</th>
                            <th class="py-3 px-3 whitespace-nowrap">Category</th>
                            <th class="py-3 px-3 min-w-[200px]">Item</th>
                            <th class="py-3 px-3 text-center">Status</th>
                            <th class="py-3 px-3 min-w-[140px]">Remarks</th>
                            <th class="py-3 px-3 min-w-[140px]">Action</th>
                            <th class="py-3 px-3">Target</th>
                            <th class="py-3 px-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="rcsaTableBody" class="divide-y divide-slate-100 dark:divide-slate-800"></tbody>
                </table>
            </div>
        </section>
    </main>

    @include('dashboard.partials.modal')
    @include('dashboard.partials.confirm-modal')
    @include('dashboard.partials.toast')
@endsection

@push('scripts')
    <script>
        window.OpsControl.workspace = {
            periodId: {{ (int) $periodId }},
            branch: @json($branch),
            checklist: @json($checklist),
        };
    </script>
    <script src="{{ asset('js/dashboard/api.js') }}"></script>
    <script src="{{ asset('js/dashboard/theme.js') }}"></script>
    <script src="{{ asset('js/dashboard/render.js') }}"></script>
    <script src="{{ asset('js/dashboard/confirm.js') }}"></script>
    <script src="{{ asset('js/dashboard/modal.js') }}"></script>
    <script src="{{ asset('js/dashboard/workspace.js') }}"></script>
@endpush
