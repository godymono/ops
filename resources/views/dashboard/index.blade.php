@extends('layouts.dashboard')

@section('content')
    <!-- Top Executive Header -->
    <header class="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 sticky top-0 z-30 shadow-sm transition-colors duration-200">
        <div class="max-w-12xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="bg-azania-navy dark:bg-blue-600 text-white p-2.5 rounded-xl shadow-sm">
                    <i class="fa-solid fa-building-columns text-base"></i>
                </div>
                <div>
                    <div class="flex items-center space-x-2">
                        <h1 class="font-brand font-bold text-slate-900 dark:text-white text-base sm:text-xl">AZANIA BANK PLC</h1>
                        <span class="bg-blue-50 dark:bg-blue-950/60 text-azania-navy dark:text-blue-400 border border-blue-200 dark:border-blue-800 text-[10px] font-bold px-2 py-0.5 rounded-md uppercase tracking-wider">Risk & Audit</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center space-x-2 sm:space-x-3">
                <div class="hidden sm:flex flex-col items-end leading-tight mr-1">
                    <span id="authUserName" class="text-xs font-bold text-slate-800 dark:text-slate-100">{{ $authUser['name'] ?? 'User' }}</span>
                    <span id="authUserMeta" class="text-[10px] text-slate-500 dark:text-slate-400">
                        {{ ($authUser['isHq'] ?? false) ? 'Head Office' : ($authUser['branch'] ?? 'Branch') }}
                    </span>
                </div>

                <button type="button" data-action="toggle-theme" class="bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-amber-400 p-2 rounded-lg text-xs font-semibold border border-slate-200 dark:border-slate-700 transition flex items-center justify-center" title="Toggle Dark/Light Mode">
                    <i id="themeToggleIcon" class="fa-solid fa-moon text-sm"></i>
                </button>

                <button type="button" data-action="open-add" id="headerAddBtn" class="bg-azania-navy hover:bg-blue-900 dark:bg-blue-600 dark:hover:bg-blue-700 text-white px-3.5 py-2 rounded-lg font-semibold text-xs sm:text-sm transition flex items-center space-x-2 shadow-sm">
                    <i class="fa-solid fa-plus text-amber-400"></i>
                    <span id="headerAddBtnLabel">Add</span>
                </button>
                <div class="h-5 w-px bg-slate-200 dark:bg-slate-800 hidden sm:block"></div>
                <button type="button" data-action="export-csv" class="bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 px-3 py-2 rounded-lg text-xs font-semibold border border-slate-200 dark:border-slate-700 transition flex items-center space-x-1.5" title="Export CSV">
                    <i class="fa-solid fa-file-csv text-emerald-600 dark:text-emerald-400"></i>
                    <span class="hidden md:inline">Export</span>
                </button>
                <button type="button" data-action="reset-data" class="hq-only bg-slate-50 dark:bg-slate-800 hover:bg-rose-50 dark:hover:bg-rose-950/40 text-slate-600 dark:text-slate-300 hover:text-rose-600 dark:hover:text-rose-400 px-3 py-2 rounded-lg text-xs font-semibold border border-slate-200 dark:border-slate-700 hover:border-rose-200 dark:hover:border-rose-800 transition flex items-center space-x-1.5" title="Reset Default Excel Data">
                    <i class="fa-solid fa-arrow-rotate-left text-xs"></i>
                    <span class="hidden lg:inline">Reset</span>
                </button>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 px-3 py-2 rounded-lg text-xs font-semibold border border-slate-200 dark:border-slate-700 transition flex items-center space-x-1.5" title="Sign out">
                        <i class="fa-solid fa-right-from-bracket text-rose-500"></i>
                        <span class="hidden md:inline">Sign out</span>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <div id="sessionPrivilegeBanner" class="bg-blue-50/80 dark:bg-blue-950/40 border-b border-blue-100 dark:border-blue-900/40 px-4 sm:px-8 py-2 text-xs flex items-center justify-between transition-colors duration-200"></div>

    <main class="flex-grow max-w-12xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
        <section id="kpiContainer" class="grid grid-cols-2 lg:grid-cols-4 gap-4"></section>

        <div class="bg-slate-200/80 dark:bg-slate-900 p-1.5 rounded-xl flex flex-wrap gap-1.5 border border-slate-300/80 dark:border-slate-800">
            <button type="button" data-tab="maintenance" id="tab-maintenance" class="tab-btn flex-1 min-w-[150px] py-2.5 px-4 rounded-lg text-xs sm:text-sm font-semibold transition flex items-center justify-center gap-2">
                <i class="fa-solid fa-screwdriver-wrench text-amber-600 dark:text-amber-400"></i>
                <span>Maintenance</span>
                <span id="badgePendingCount" class="ml-1 bg-amber-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">0</span>
            </button>

            <button type="button" data-tab="licences" id="tab-licences" class="tab-btn flex-1 min-w-[150px] py-2.5 px-4 rounded-lg text-xs sm:text-sm font-semibold transition flex items-center justify-center gap-2">
                <i class="fa-solid fa-award text-blue-600 dark:text-blue-400"></i>
                <span>Licences</span>
                <span id="badgeLicenceAlert" class="ml-1 bg-rose-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">0</span>
            </button>

            <button type="button" data-tab="rcsa" id="tab-rcsa" class="tab-btn flex-1 min-w-[150px] py-2.5 px-4 rounded-lg text-xs sm:text-sm font-semibold transition flex items-center justify-center gap-2">
                <i class="fa-solid fa-clipboard-check text-emerald-600 dark:text-emerald-400"></i>
                <span>RCSA</span>
                <span class="ml-1 bg-slate-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">306</span>
            </button>

            <button type="button" data-tab="reports" id="tab-reports" class="tab-btn flex-1 min-w-[150px] py-2.5 px-4 rounded-lg text-xs sm:text-sm font-semibold transition flex items-center justify-center gap-2">
                <i class="fa-solid fa-calendar-check text-indigo-600 dark:text-indigo-400"></i>
                <span id="tabReportsLabel">HQ Monitor</span>
                <span id="badgeReportsPending" class="ml-1 bg-rose-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">0</span>
            </button>
        </div>

        <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3.5 shadow-sm">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-slate-400 text-xs"></i>
                    <input type="text" id="searchInput" placeholder="Search..."
                        class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg pl-8 pr-3 py-1.5 text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:border-azania-navy dark:focus:border-blue-500 transition">
                </div>
                <div>
                    <select id="branchFilter" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-1.5 text-xs text-slate-700 dark:text-slate-200 focus:bg-white dark:focus:bg-slate-900 focus:outline-none">
                        <option value="ALL">All Branches</option>
                    </select>
                </div>
                <div>
                    <select id="categoryFilter" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-1.5 text-xs text-slate-700 dark:text-slate-200 focus:bg-white dark:focus:bg-slate-900 focus:outline-none">
                        <option value="ALL">All Categories</option>
                    </select>
                </div>
                <div>
                    <select id="statusFilter" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-1.5 text-xs text-slate-700 dark:text-slate-200 focus:bg-white dark:focus:bg-slate-900 focus:outline-none">
                        <option value="ALL">All Statuses</option>
                    </select>
                </div>
            </div>
        </section>

        <section id="module-maintenance" class="space-y-4 tab-content hidden">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-sm overflow-hidden flex flex-col">
                <div class="p-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center space-x-3">
                        <div class="p-2 bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 rounded-lg border border-amber-200 dark:border-amber-800">
                            <i class="fa-solid fa-screwdriver-wrench text-sm"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-slate-900 dark:text-white">Maintenance</h2>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <label class="text-xs text-slate-600 dark:text-slate-400 font-medium">Show Resolved:</label>
                        <input type="checkbox" id="toggleResolvedCheck" class="w-4 h-4 text-azania-navy rounded border-slate-300 dark:border-slate-700 dark:bg-slate-800 focus:ring-azania-navy">
                        <button type="button" data-action="open-add" data-module="maintenance" class="bg-azania-navy hover:bg-blue-900 dark:bg-blue-600 dark:hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg text-xs font-semibold transition flex items-center gap-1.5">
                            <i class="fa-solid fa-plus text-amber-300"></i>
                            <span>Add</span>
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-600 dark:text-slate-300 uppercase tracking-wider font-semibold border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="py-3 px-3 w-12 text-center">Ref</th>
                                <th class="py-3 px-3 whitespace-nowrap">Branch</th>
                                <th class="py-3 px-3 min-w-[220px]">Title</th>
                                <th class="py-3 px-3 text-center whitespace-nowrap">Priority</th>
                                <th class="py-3 px-3 whitespace-nowrap">Officer</th>
                                <th class="py-3 px-3 whitespace-nowrap">Target</th>
                                <th class="py-3 px-3 text-center whitespace-nowrap">Status</th>
                                <th class="py-3 px-3 text-center whitespace-nowrap">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="maintenanceTableBody" class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300"></tbody>
                    </table>
                </div>
            </div>
        </section>

        <section id="module-licences" class="space-y-4 tab-content hidden">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-sm overflow-hidden flex flex-col">
                <div class="p-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center space-x-3">
                        <div class="p-2 bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 rounded-lg border border-blue-200 dark:border-blue-800">
                            <i class="fa-solid fa-certificate text-sm"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-slate-900 dark:text-white">Licences</h2>
                        </div>
                    </div>
                    <button type="button" data-action="open-add" data-module="licences" class="bg-azania-navy hover:bg-blue-900 dark:bg-blue-600 dark:hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg text-xs font-semibold transition flex items-center gap-1.5 self-start sm:self-auto">
                        <i class="fa-solid fa-plus text-amber-300"></i>
                        <span>Add</span>
                    </button>
                </div>

                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-600 dark:text-slate-300 uppercase tracking-wider font-semibold border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="py-3 px-3 w-12 text-center">Ref</th>
                                <th class="py-3 px-3 whitespace-nowrap">Branch</th>
                                <th class="py-3 px-3 min-w-[220px]">Licence</th>
                                <th class="py-3 px-3 whitespace-nowrap">Authority</th>
                                <th class="py-3 px-3 whitespace-nowrap">Expiry</th>
                                <th class="py-3 px-3 text-center whitespace-nowrap">Days</th>
                                <th class="py-3 px-3 text-center whitespace-nowrap">Status</th>
                                <th class="py-3 px-3 text-center whitespace-nowrap">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="licenceTableBody" class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300"></tbody>
                    </table>
                </div>
            </div>
        </section>

        <section id="module-rcsa" class="space-y-4 tab-content hidden">
            <div id="rcsaPeriodBanner" class="rounded-xl border px-4 py-3 text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-emerald-50 border-emerald-200 text-emerald-800 dark:bg-emerald-950/30 dark:border-emerald-900/40 dark:text-emerald-300">
                <div class="flex items-center gap-2 font-semibold">
                    <i class="fa-solid fa-calendar-days"></i>
                    <span id="rcsaPeriodBannerText">Current month</span>
                </div>
                <div class="flex items-center gap-2 text-[11px]">
                    <span id="rcsaDeadlineText">Deadline —</span>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-sm p-4 space-y-3">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white" id="rcsaFlowTitle">Monthly flow</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400" id="rcsaFlowHint">Draft → Submit → Unlock → Edit → Submit</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <select id="rcsaMonthSelect" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-1.5 text-xs font-semibold"></select>
                        <select id="rcsaBranchSelect" class="hq-only bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-1.5 text-xs font-semibold"></select>
                        <span id="rcsaBranchStatusBadge" class="text-[10px] font-bold px-2 py-1 rounded bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 uppercase">Draft</span>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2" id="rcsaFlowActions">
                    <button type="button" id="rcsaSubmitBtn" class="branch-action bg-indigo-700 hover:bg-indigo-800 text-white px-3 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1.5">
                        <i class="fa-solid fa-paper-plane"></i><span>Submit</span>
                    </button>
                    <button type="button" id="rcsaUnlockBtn" class="hidden bg-amber-600 hover:bg-amber-700 text-white px-3 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1.5">
                        <i class="fa-solid fa-unlock"></i><span>Unlock & Edit</span>
                    </button>
                    <button type="button" id="rcsaExtendBtn" class="hq-only hidden bg-sky-700 hover:bg-sky-800 text-white px-3 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1.5">
                        <i class="fa-solid fa-calendar-plus"></i><span>Extend Days</span>
                    </button>
                    <button type="button" id="rcsaWorkspaceBtn" class="hidden bg-emerald-700 hover:bg-emerald-800 text-white px-3 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1.5">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i><span>Open Workspace</span>
                    </button>
                    <button type="button" data-action="open-add" data-module="rcsa" id="rcsaAddBtn" class="branch-action bg-slate-800 hover:bg-slate-900 dark:bg-slate-700 dark:hover:bg-slate-600 text-white px-3 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1.5">
                        <i class="fa-solid fa-plus"></i><span>Add</span>
                    </button>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-sm overflow-hidden flex flex-col">
                <div class="p-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900 flex items-center justify-between gap-3">
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
                                <th class="py-3 px-3 min-w-[200px] max-w-xs md:max-w-md">Item</th>
                                <th class="py-3 px-3 text-center whitespace-nowrap">Status</th>
                                <th class="py-3 px-3 min-w-[160px] max-w-xs">Remarks</th>
                                <th class="py-3 px-3 min-w-[160px] max-w-xs">Action</th>
                                <th class="py-3 px-3 whitespace-nowrap">Target</th>
                                <th class="py-3 px-3 text-center whitespace-nowrap">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="rcsaTableBody" class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300"></tbody>
                    </table>
                </div>
            </div>
        </section>

        <section id="module-reports" class="space-y-4 tab-content hidden">
            <div id="reportsKpi" class="grid grid-cols-2 lg:grid-cols-4 gap-4"></div>

            {{-- Head Office monitoring --}}
            <div id="hqReportsPanel" class="hq-only bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-sm overflow-hidden">
                <div class="p-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900 flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white">Head Office monitor</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400" id="reportsPeriodMeta">Track submissions, unlock access, extend deadlines</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <select id="reportPeriodSelect" class="bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-1.5 text-xs text-slate-700 dark:text-slate-200 focus:outline-none"></select>
                        <button type="button" id="extendAllBtn" class="bg-sky-700 hover:bg-sky-800 text-white px-3 py-1.5 rounded-lg text-xs font-semibold transition flex items-center gap-1.5">
                            <i class="fa-solid fa-calendar-plus"></i>
                            <span>Extend All</span>
                        </button>
                        <button type="button" id="createPeriodBtn" class="bg-indigo-700 hover:bg-indigo-800 text-white px-3 py-1.5 rounded-lg text-xs font-semibold transition flex items-center gap-1.5">
                            <i class="fa-solid fa-folder-plus"></i>
                            <span>Ensure Month</span>
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-3 gap-0 xl:divide-x divide-slate-100 dark:divide-slate-800">
                    <div class="xl:col-span-2 overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-600 dark:text-slate-300 uppercase tracking-wider font-semibold border-b border-slate-200 dark:border-slate-800">
                                <tr>
                                    <th class="py-3 px-3">Branch</th>
                                    <th class="py-3 px-3 text-center">Status</th>
                                    <th class="py-3 px-3 whitespace-nowrap">Deadline</th>
                                    <th class="py-3 px-3 whitespace-nowrap">Submitted</th>
                                    <th class="py-3 px-3 text-center">Rate</th>
                                    <th class="py-3 px-3 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="reportsBranchBody" class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300"></tbody>
                        </table>
                    </div>
                    <div class="p-4 space-y-3 bg-slate-50/40 dark:bg-slate-950/30">
                        <div>
                            <h3 class="text-xs font-bold text-rose-600 dark:text-rose-400 uppercase tracking-wide">Missing</h3>
                            <ul id="reportsMissingList" class="mt-2 space-y-1.5 text-xs text-slate-700 dark:text-slate-300"></ul>
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wide">Submitted</h3>
                            <ul id="reportsSubmittedList" class="mt-2 space-y-1.5 text-xs text-slate-700 dark:text-slate-300"></ul>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Branch: own history --}}
            <div id="branchReportsPanel" class="branch-only hidden bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-sm overflow-hidden">
                <div class="p-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white">My monthly reports</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400" id="branchReportsMeta">Your branch submission history</p>
                    </div>
                    <button type="button" id="branchGoRcsaBtn" class="bg-emerald-700 hover:bg-emerald-800 text-white px-3 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1.5">
                        <i class="fa-solid fa-clipboard-check"></i>
                        <span>Work on current RCSA</span>
                    </button>
                </div>
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-600 dark:text-slate-300 uppercase tracking-wider font-semibold border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="py-3 px-3">Month</th>
                                <th class="py-3 px-3 text-center">Status</th>
                                <th class="py-3 px-3">Deadline</th>
                                <th class="py-3 px-3">Submitted</th>
                                <th class="py-3 px-3 text-center">Rate</th>
                                <th class="py-3 px-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="branchHistoryBody" class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300"></tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>

    @include('dashboard.partials.modal')
    @include('dashboard.partials.report-modal')
    @include('dashboard.partials.extend-modal')
    @include('dashboard.partials.confirm-modal')
    @include('dashboard.partials.toast')
@endsection

@push('scripts')
    <script src="{{ asset('js/dashboard/api.js') }}"></script>
    <script src="{{ asset('js/dashboard/theme.js') }}"></script>
    <script src="{{ asset('js/dashboard/render.js') }}"></script>
    <script src="{{ asset('js/dashboard/confirm.js') }}"></script>
    <script src="{{ asset('js/dashboard/modal.js') }}"></script>
    <script src="{{ asset('js/dashboard/reports.js') }}"></script>
    <script src="{{ asset('js/dashboard/app.js') }}"></script>
@endpush
