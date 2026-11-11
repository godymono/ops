window.DashboardReports = (function () {
    const R = () => window.DashboardRender;
    let periods = [];
    let currentPeriod = null;
    let selectedRcsaPeriodId = null;
    let selectedRcsaBranch = null;
    let currentRole = 'HQ';
    let branchHistory = [];
    let extendDefaults = { scope: 'all', branch: null };
    let checklistState = {
        editable: true,
        periodOpen: true,
        items: [],
        period: null,
        source: 'live',
        message: '',
        branchStatus: 'pending',
        workspaceUrl: null,
        branch: null,
        branches: [],
    };

    function cfg() {
        return window.OpsControl || {};
    }

    function isHq() {
        return currentRole === 'HQ';
    }

    async function request(url, options = {}) {
        const response = await fetch(url, {
            ...options,
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': cfg().csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                ...(options.body ? { 'Content-Type': 'application/json' } : {}),
                ...(options.headers || {}),
            },
        });
        const text = await response.text();
        let payload = null;
        try { payload = text ? JSON.parse(text) : null; } catch (e) { payload = { message: text }; }
        if (!response.ok) {
            throw new Error(payload?.message || `Request failed (HTTP ${response.status})`);
        }
        return payload;
    }

    async function load() {
        const data = await request(cfg().routes.reports);
        periods = data.periods || [];
        currentPeriod = data.current || null;
        const active = periods.find((p) => p.isActive || p.editable) || currentPeriod?.period;
        selectedRcsaPeriodId = active?.id || currentPeriod?.period?.id || null;
        fillPeriodSelect();
        fillRcsaMonthSelect();
        if (selectedRcsaPeriodId) {
            await loadChecklist(selectedRcsaPeriodId, selectedRcsaBranch);
        }
        render();
        return currentPeriod;
    }

    async function loadPeriod(periodId) {
        const data = await request(`${cfg().routes.reportPeriods}/${periodId}`);
        currentPeriod = data;
        const exists = periods.find((p) => Number(p.id) === Number(data.period.id));
        if (exists) Object.assign(exists, data.period);
        else periods.unshift(data.period);
        fillPeriodSelect();
        fillRcsaMonthSelect();
        render();
        return data;
    }

    async function loadChecklist(periodId, branch = null) {
        const query = branch ? `?branch=${encodeURIComponent(branch)}` : '';
        const data = await request(`${cfg().routes.reportPeriods}/${periodId}/checklist${query}`);
        selectedRcsaPeriodId = periodId;
        selectedRcsaBranch = data.branch || branch || null;
        checklistState = {
            editable: !!data.editable,
            periodOpen: !!data.periodOpen,
            items: data.items || [],
            period: data.period,
            source: data.source,
            message: data.message || '',
            deadlineLabel: data.deadlineLabel,
            daysRemaining: data.daysRemaining,
            branchStatus: data.branchStatus || 'pending',
            workspaceUrl: data.workspaceUrl || null,
            branch: data.branch || null,
            branches: data.branches || [],
            canUnlock: !!data.canUnlock,
            canHqUnlockPast: !!data.canHqUnlockPast,
        };
        fillRcsaMonthSelect();
        fillRcsaBranchSelect();
        updateRcsaChrome();
        return checklistState;
    }

    async function loadBranchHistory(branch) {
        if (!branch || branch === 'HQ') {
            branchHistory = [];
            renderBranchHistory();
            return branchHistory;
        }
        const data = await request(`${cfg().routes.reportBranchHistory}?branch=${encodeURIComponent(branch)}`);
        branchHistory = data.history || [];
        const meta = document.getElementById('branchReportsMeta');
        if (meta) meta.textContent = `${data.branch} · ${branchHistory.length} month(s)`;
        renderBranchHistory();
        return branchHistory;
    }

    function setRole(role) {
        currentRole = role === 'HQ' ? 'HQ' : role;
        document.querySelectorAll('.hq-only').forEach((el) => {
            el.classList.toggle('hidden', !isHq());
        });
        document.querySelectorAll('.branch-only').forEach((el) => {
            el.classList.toggle('hidden', isHq());
        });

        const tabLabel = document.getElementById('tabReportsLabel');
        if (tabLabel) tabLabel.textContent = isHq() ? 'HQ Monitor' : 'My Reports';

        const flowTitle = document.getElementById('rcsaFlowTitle');
        if (flowTitle) {
            flowTitle.textContent = isHq() ? 'Head Office controls' : 'My monthly RCSA';
        }

        const branchFilterWrap = document.getElementById('branchFilter')?.closest('div');
        if (branchFilterWrap) branchFilterWrap.classList.toggle('hidden', !isHq());

        updateRcsaChrome();
        render();
    }

    function fillPeriodSelect() {
        const select = document.getElementById('reportPeriodSelect');
        if (!select) return;
        const selected = currentPeriod?.period?.id;
        select.innerHTML = periods.map((period) => {
            const mark = period.isActive ? ' · Current' : (period.editable ? ' · Open' : '');
            return `<option value="${period.id}">${R().escapeHtml(period.label)}${mark}</option>`;
        }).join('');
        if (selected) select.value = selected;
    }

    function fillRcsaMonthSelect() {
        const select = document.getElementById('rcsaMonthSelect');
        if (!select) return;
        select.innerHTML = periods.map((period) => {
            const mark = period.editable || period.isOpen ? ' · Open' : ' · Closed';
            return `<option value="${period.id}">${R().escapeHtml(period.label)}${mark}</option>`;
        }).join('');
        if (selectedRcsaPeriodId) select.value = selectedRcsaPeriodId;
    }

    function fillRcsaBranchSelect() {
        const select = document.getElementById('rcsaBranchSelect');
        if (!select) return;

        const branches = (checklistState.branches || [])
            .map((row) => row.branch)
            .filter(Boolean);
        const options = cfg().initialOptions?.branches || [];
        const names = branches.length ? branches : options;
        select.innerHTML = [
            '<option value="">Select branch</option>',
            ...names.map((name) => `<option value="${R().escapeHtml(name)}">${R().escapeHtml(name)}</option>`),
        ].join('');

        if (selectedRcsaBranch) select.value = selectedRcsaBranch;
        select.disabled = !isHq();
        if (!isHq() && selectedRcsaBranch) select.value = selectedRcsaBranch;
    }

    function statusBadgeMeta(status) {
        switch (status) {
            case 'submitted':
                return {
                    label: 'Submitted',
                    className: 'text-[10px] font-bold px-2 py-1 rounded bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300 uppercase',
                };
            case 'unlocked':
                return {
                    label: 'Unlocked',
                    className: 'text-[10px] font-bold px-2 py-1 rounded bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300 uppercase',
                };
            case 'overview':
                return {
                    label: 'Overview',
                    className: 'text-[10px] font-bold px-2 py-1 rounded bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 uppercase',
                };
            default:
                return {
                    label: 'Draft',
                    className: 'text-[10px] font-bold px-2 py-1 rounded bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 uppercase',
                };
        }
    }

    function updateRcsaChrome() {
        const banner = document.getElementById('rcsaPeriodBanner');
        const bannerText = document.getElementById('rcsaPeriodBannerText');
        const deadlineText = document.getElementById('rcsaDeadlineText');
        const flowHint = document.getElementById('rcsaFlowHint');
        const badge = document.getElementById('rcsaBranchStatusBadge');
        const addBtn = document.getElementById('rcsaAddBtn');
        const submitBtn = document.getElementById('rcsaSubmitBtn');
        const unlockBtn = document.getElementById('rcsaUnlockBtn');
        const extendBtn = document.getElementById('rcsaExtendBtn');
        const workspaceBtn = document.getElementById('rcsaWorkspaceBtn');
        const period = checklistState.period;
        if (!period) return;

        const periodOpen = checklistState.periodOpen;
        const status = checklistState.branchStatus || 'pending';
        const editable = checklistState.editable;
        const hasBranch = !!checklistState.branch;

        if (banner) {
            banner.className = periodOpen
                ? 'rounded-xl border px-4 py-3 text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-emerald-50 border-emerald-200 text-emerald-800 dark:bg-emerald-950/30 dark:border-emerald-900/40 dark:text-emerald-300'
                : 'rounded-xl border px-4 py-3 text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-slate-100 border-slate-200 text-slate-700 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-300';
        }
        if (bannerText) {
            bannerText.textContent = periodOpen
                ? `${period.label} · Open for reporting`
                : `${period.label} · Closed`;
        }
        if (deadlineText) {
            const days = checklistState.daysRemaining;
            deadlineText.textContent = periodOpen
                ? (days >= 0
                    ? `Deadline ${checklistState.deadlineLabel} · ${days}d left`
                    : `Deadline ${checklistState.deadlineLabel}`)
                : `Closed · Deadline was ${checklistState.deadlineLabel}`;
        }
        if (flowHint) {
            flowHint.textContent = checklistState.message || (isHq()
                ? 'Monitor → Unlock access → Extend days when needed'
                : 'Draft → Submit → Unlock current month → Edit in workspace');
        }
        if (badge) {
            const meta = statusBadgeMeta(status);
            badge.textContent = meta.label;
            badge.className = meta.className;
        }

        const isActiveMonth = !!checklistState.period?.isActive;
        const showSubmit = !isHq() && periodOpen && hasBranch && (status === 'pending' || status === 'unlocked');
        // Branch: unlock current month only. HQ: unlock current or past (if still open).
        const showUnlock = periodOpen && hasBranch && status === 'submitted'
            && (isHq() || isActiveMonth || checklistState.canUnlock);
        const showExtend = isHq() && hasBranch;
        const showWorkspace = periodOpen && hasBranch && status === 'unlocked';
        const showAdd = !isHq() && editable && hasBranch && status === 'pending';

        if (submitBtn) submitBtn.classList.toggle('hidden', !showSubmit);
        if (unlockBtn) {
            unlockBtn.classList.toggle('hidden', !showUnlock);
            const label = unlockBtn.querySelector('span');
            if (label) {
                label.textContent = isHq() && !isActiveMonth
                    ? 'Unlock Past Report'
                    : 'Unlock & Edit';
            }
        }
        if (extendBtn) extendBtn.classList.toggle('hidden', !showExtend);
        if (workspaceBtn) workspaceBtn.classList.toggle('hidden', !showWorkspace);
        if (addBtn) addBtn.classList.toggle('hidden', !showAdd);
    }

    function renderKpis() {
        const container = document.getElementById('reportsKpi');
        const badge = document.getElementById('badgeReportsPending');
        if (!container) return;

        if (!isHq()) {
            const openRow = branchHistory.find((row) => row.open) || branchHistory[0];
            const submittedCount = branchHistory.filter((row) => row.status === 'submitted').length;
            const draftCount = branchHistory.filter((row) => row.status === 'pending').length;
            container.innerHTML = `
                <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Branch</p>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mt-1">${R().escapeHtml(currentRole)}</h3>
                </div>
                <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-indigo-200 dark:border-indigo-950/60 shadow-sm">
                    <p class="text-xs text-indigo-600 dark:text-indigo-400 font-medium">Current focus</p>
                    <h3 class="text-lg font-bold text-indigo-600 dark:text-indigo-400 mt-1">${R().escapeHtml(openRow?.period || '—')}</h3>
                </div>
                <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-emerald-200 dark:border-emerald-950/60 shadow-sm">
                    <p class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">Submitted months</p>
                    <h3 class="text-xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">${submittedCount}</h3>
                </div>
                <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-amber-200 dark:border-amber-950/60 shadow-sm">
                    <p class="text-xs text-amber-600 dark:text-amber-400 font-medium">Draft months</p>
                    <h3 class="text-xl font-bold text-amber-600 dark:text-amber-400 mt-1">${draftCount}</h3>
                </div>
            `;
            if (badge) badge.textContent = draftCount;
            return;
        }

        if (!currentPeriod?.period) return;
        const p = currentPeriod.period;
        const unlockedCount = p.unlockedCount ?? (currentPeriod.unlocked || []).length;
        if (badge) badge.textContent = p.pendingCount;

        container.innerHTML = `
            <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
                <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Month</p>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mt-1">${R().escapeHtml(p.label)}</h3>
            </div>
            <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-emerald-200 dark:border-emerald-950/60 shadow-sm">
                <p class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">Submitted</p>
                <h3 class="text-xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">${p.submittedCount}</h3>
            </div>
            <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-amber-200 dark:border-amber-950/60 shadow-sm">
                <p class="text-xs text-amber-600 dark:text-amber-400 font-medium">Unlocked</p>
                <h3 class="text-xl font-bold text-amber-600 dark:text-amber-400 mt-1">${unlockedCount}</h3>
            </div>
            <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-rose-200 dark:border-rose-950/60 shadow-sm">
                <p class="text-xs text-rose-600 dark:text-rose-400 font-medium">Missing</p>
                <h3 class="text-xl font-bold text-rose-600 dark:text-rose-400 mt-1">${p.pendingCount}</h3>
            </div>
        `;
    }

    function renderLists() {
        const missing = document.getElementById('reportsMissingList');
        const submitted = document.getElementById('reportsSubmittedList');
        if (!missing || !submitted || !currentPeriod || !isHq()) return;

        const missingRows = currentPeriod.missing || [];
        const submittedRows = currentPeriod.submitted || [];
        const unlockedRows = currentPeriod.unlocked || [];

        missing.innerHTML = missingRows.length
            ? missingRows.map((row) => `<li class="flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>${R().escapeHtml(row.branch)}</li>`).join('')
            : '<li class="text-slate-400">None</li>';

        const submittedHtml = submittedRows.map((row) => `<li class="flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>${R().escapeHtml(row.branch)}</li>`);
        const unlockedHtml = unlockedRows.map((row) => `<li class="flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>${R().escapeHtml(row.branch)} <span class="text-[10px] uppercase font-bold text-amber-600">Unlocked</span></li>`);
        const combined = [...submittedHtml, ...unlockedHtml];
        submitted.innerHTML = combined.length ? combined.join('') : '<li class="text-slate-400">None</li>';
    }

    function statusCell(row) {
        if (row.status === 'submitted') {
            return `<span class="bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 px-2 py-0.5 rounded font-bold text-[10px]">${row.late ? 'Late' : 'Submitted'}</span>`;
        }
        if (row.status === 'unlocked') {
            return '<span class="bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 px-2 py-0.5 rounded font-bold text-[10px]">Unlocked</span>';
        }
        return '<span class="bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 px-2 py-0.5 rounded font-bold text-[10px]">Missing</span>';
    }

    function renderTable() {
        const tbody = document.getElementById('reportsBranchBody');
        const meta = document.getElementById('reportsPeriodMeta');
        if (!tbody || !currentPeriod || !isHq()) return;

        const period = currentPeriod.period;
        if (meta) {
            meta.textContent = `${period.submittedCount}/${period.totalBranches} submitted · deadline ${period.deadlineLabel}${period.extended ? ' (extended)' : ''}`;
        }

        const rows = currentPeriod.branches || [];
        if (!rows.length) {
            tbody.innerHTML = `<tr><td colspan="6" class="py-6 text-center text-slate-400">No branches found.</td></tr>`;
            return;
        }

        tbody.innerHTML = rows.map((row) => {
            const canAct = !!row.open;
            let actions = '';

            if (row.status === 'submitted') {
                actions = `<button type="button" data-view-submission="${row.id}" class="action-btn text-slate-400 hover:text-azania-navy dark:hover:text-blue-400" title="View"><i class="fa-solid fa-eye"></i></button>`;
                if (canAct) {
                    actions += ` <button type="button" data-unlock-branch="${R().escapeHtml(row.branch)}" class="px-2 py-1 rounded bg-amber-600 hover:bg-amber-700 text-white text-[10px] font-bold">Unlock</button>`;
                }
            } else if (row.status === 'unlocked') {
                actions = `<button type="button" data-view-submission="${row.id}" class="action-btn text-slate-400 hover:text-azania-navy dark:hover:text-blue-400" title="View"><i class="fa-solid fa-eye"></i></button>`;
                if (canAct) {
                    const url = `/rcsa/workspace/${period.id}?branch=${encodeURIComponent(row.branch)}`;
                    actions += ` <a href="${url}" class="px-2 py-1 rounded bg-emerald-700 hover:bg-emerald-800 text-white text-[10px] font-bold inline-block">Workspace</a>`;
                }
            } else {
                actions = canAct
                    ? '<span class="text-[10px] text-slate-400 font-semibold">Awaiting branch</span>'
                    : '<span class="text-[10px] text-slate-400 font-semibold uppercase">Closed</span>';
            }

            actions += ` <button type="button" data-extend-branch="${R().escapeHtml(row.branch)}" class="px-2 py-1 rounded bg-sky-700 hover:bg-sky-800 text-white text-[10px] font-bold">+Days</button>`;

            return `
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                    <td class="py-2.5 px-3 font-semibold text-slate-900 dark:text-slate-100">${R().escapeHtml(row.branch)}</td>
                    <td class="py-2.5 px-3 text-center">${statusCell(row)}</td>
                    <td class="py-2.5 px-3 whitespace-nowrap text-slate-500">${R().escapeHtml(row.deadlineLabel || '-')}${row.extended ? ' <span class="text-sky-600 font-bold">ext</span>' : ''}</td>
                    <td class="py-2.5 px-3 whitespace-nowrap text-slate-500">${R().escapeHtml(row.submittedAt || '-')}</td>
                    <td class="py-2.5 px-3 text-center font-semibold">${row.status === 'pending' ? '-' : `${row.rate}%`}</td>
                    <td class="py-2.5 px-3 text-center whitespace-nowrap">${actions}</td>
                </tr>
            `;
        }).join('');
    }

    function renderBranchHistory() {
        const tbody = document.getElementById('branchHistoryBody');
        if (!tbody) return;

        if (!branchHistory.length) {
            tbody.innerHTML = `<tr><td colspan="6" class="py-6 text-center text-slate-400">No reports yet for this branch.</td></tr>`;
            return;
        }

        tbody.innerHTML = branchHistory.map((row) => {
            let actions = '';
            if (row.status === 'submitted' || row.status === 'unlocked') {
                actions += `<button type="button" data-view-submission="${row.id}" class="px-2 py-1 rounded border border-slate-200 dark:border-slate-700 text-[10px] font-bold">View</button>`;
            }
            // Branch can unlock current month; past submitted needs HQ.
            if (row.open && row.status === 'submitted') {
                if (row.canUnlock || row.isActive) {
                    actions += ` <button type="button" data-unlock-branch="${R().escapeHtml(currentRole)}" data-unlock-period="${row.periodId}" class="px-2 py-1 rounded bg-amber-600 text-white text-[10px] font-bold">Unlock & Edit</button>`;
                } else {
                    actions += ' <span class="text-[10px] text-slate-400 font-semibold">Needs HQ unlock</span>';
                }
            }
            if (row.open && row.status === 'unlocked' && row.workspaceUrl) {
                actions += ` <a href="${row.workspaceUrl}" class="px-2 py-1 rounded bg-emerald-700 text-white text-[10px] font-bold inline-block">Workspace</a>`;
            }
            if (row.open && (row.status === 'pending' || row.status === 'unlocked')) {
                actions += ` <button type="button" data-open-month="${row.periodId}" class="px-2 py-1 rounded bg-indigo-700 text-white text-[10px] font-bold">Open</button>`;
            }

            return `
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                    <td class="py-2.5 px-3 font-semibold">${R().escapeHtml(row.period || '-')}</td>
                    <td class="py-2.5 px-3 text-center">${statusCell(row)}</td>
                    <td class="py-2.5 px-3 text-slate-500">${R().escapeHtml(row.deadlineLabel || '-')}</td>
                    <td class="py-2.5 px-3 text-slate-500">${R().escapeHtml(row.submittedAt || '-')}</td>
                    <td class="py-2.5 px-3 text-center font-semibold">${row.status === 'pending' ? '-' : `${row.rate}%`}</td>
                    <td class="py-2.5 px-3 text-center whitespace-nowrap">${actions || '<span class="text-[10px] text-slate-400">—</span>'}</td>
                </tr>
            `;
        }).join('');
    }

    function render() {
        renderKpis();
        renderLists();
        renderTable();
        renderBranchHistory();
        const mainKpi = document.getElementById('kpiContainer');
        if (mainKpi && document.getElementById('module-reports') && !document.getElementById('module-reports').classList.contains('hidden')) {
            mainKpi.innerHTML = document.getElementById('reportsKpi')?.innerHTML || '';
        }
    }

    async function createCurrentMonth() {
        const data = await request(cfg().routes.reportPeriods, {
            method: 'POST',
            body: JSON.stringify({}),
        });
        currentPeriod = data;
        const summary = data.period;
        const idx = periods.findIndex((p) => Number(p.id) === Number(summary.id));
        if (idx >= 0) periods[idx] = summary;
        else periods.unshift(summary);
        selectedRcsaPeriodId = summary.id;
        fillPeriodSelect();
        fillRcsaMonthSelect();
        await loadChecklist(summary.id, selectedRcsaBranch);
        render();
        return data;
    }

    async function viewSubmission(submissionId) {
        const data = await request(`${cfg().routes.reportSubmissions}/${submissionId}`);
        const submission = data.submission;
        const modal = document.getElementById('reportModal');
        document.getElementById('reportModalTitle').textContent = `${submission.branch} · ${submission.period}`;
        document.getElementById('reportModalMeta').textContent =
            `${submission.submittedAt || ''} · YES ${submission.yes} · NO ${submission.no} · Rate ${submission.rate}%`;

        const body = document.getElementById('reportModalBody');
        const items = submission.items || [];
        body.innerHTML = items.length
            ? items.map((item) => `
                <tr>
                    <td class="py-2 px-3 whitespace-nowrap">${R().escapeHtml(item.category || '-')}</td>
                    <td class="py-2 px-3">${R().escapeHtml(item.item)}</td>
                    <td class="py-2 px-3 text-center">
                        <span class="rcsa-status ${R().rcsaStatusClass(item.status)} inline-block px-2 py-0.5 rounded text-[10px] font-bold border">${R().escapeHtml(item.status)}</span>
                    </td>
                    <td class="py-2 px-3">${R().escapeHtml(item.remarks || '-')}</td>
                    <td class="py-2 px-3">${R().escapeHtml(item.actionPlan || '-')}</td>
                    <td class="py-2 px-3 whitespace-nowrap">${R().escapeHtml(item.targetDate || '-')}</td>
                </tr>
            `).join('')
            : `<tr><td colspan="6" class="py-6 text-center text-slate-400">No items.</td></tr>`;

        modal.classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('reportModal')?.classList.add('hidden');
    }

    function openExtendModal({ scope = 'all', branch = null } = {}) {
        extendDefaults = { scope, branch };
        const modal = document.getElementById('extendModal');
        const scopeSelect = document.getElementById('extendScope');
        const branchSelect = document.getElementById('extendBranch');
        const branchWrap = document.getElementById('extendBranchWrap');
        const days = document.getElementById('extendDays');

        const names = (checklistState.branches || []).map((row) => row.branch).filter(Boolean);
        if (branchSelect) {
            branchSelect.innerHTML = names.map((name) => `<option value="${R().escapeHtml(name)}">${R().escapeHtml(name)}</option>`).join('');
            if (branch) branchSelect.value = branch;
        }
        if (scopeSelect) scopeSelect.value = scope === 'branch' ? 'branch' : 'all';
        if (branchWrap) branchWrap.classList.toggle('hidden', scopeSelect?.value !== 'branch');
        if (days) days.value = '3';
        modal?.classList.remove('hidden');
    }

    function closeExtendModal() {
        document.getElementById('extendModal')?.classList.add('hidden');
    }

    async function extendDeadline(days, branch = null, periodId = null) {
        const id = periodId || selectedRcsaPeriodId || currentPeriod?.period?.id;
        if (!id) throw new Error('No period selected.');
        const payload = { days: Number(days) };
        if (branch) payload.branch = branch;

        const data = await request(`${cfg().routes.reportPeriods}/${id}/extend`, {
            method: 'POST',
            body: JSON.stringify(payload),
        });
        currentPeriod = data.period;
        const summary = data.period?.period;
        if (summary) {
            const idx = periods.findIndex((p) => Number(p.id) === Number(summary.id));
            if (idx >= 0) periods[idx] = summary;
        }
        await loadChecklist(id, selectedRcsaBranch);
        render();
        return data;
    }

    async function unlockBranch(branch, periodId = null, { redirect = true } = {}) {
        const id = periodId || selectedRcsaPeriodId || currentPeriod?.period?.id;
        if (!id) throw new Error('No period selected.');
        if (!branch || branch === 'HQ') throw new Error('Select a branch to unlock.');

        const data = await request(`${cfg().routes.reportPeriods}/${id}/unlock`, {
            method: 'POST',
            body: JSON.stringify({ branch }),
        });

        currentPeriod = data.period;
        const summary = data.period?.period;
        if (summary) {
            const idx = periods.findIndex((p) => Number(p.id) === Number(summary.id));
            if (idx >= 0) periods[idx] = summary;
        }
        render();

        if (redirect && data.workspaceUrl) {
            window.location.href = data.workspaceUrl;
            return data;
        }

        await loadChecklist(id, branch);
        return data;
    }

    function openWorkspace() {
        if (checklistState.workspaceUrl) {
            window.location.href = checklistState.workspaceUrl;
            return;
        }
        const periodId = selectedRcsaPeriodId || currentPeriod?.period?.id;
        const branch = selectedRcsaBranch || checklistState.branch;
        if (!periodId || !branch) throw new Error('Select a branch first.');
        window.location.href = `/rcsa/workspace/${periodId}?branch=${encodeURIComponent(branch)}`;
    }

    function resolveBranch(getCurrentSession) {
        const session = getCurrentSession();
        if (session && session !== 'HQ') return session;
        return selectedRcsaBranch
            || document.getElementById('rcsaBranchSelect')?.value
            || checklistState.branch
            || null;
    }

    function bind(getCurrentSession, showToast, onChecklistChange, switchTabFn) {
        document.getElementById('reportPeriodSelect')?.addEventListener('change', async (event) => {
            try {
                await loadPeriod(event.target.value);
            } catch (error) {
                showToast(error.message || 'Unable to load month.');
            }
        });

        document.getElementById('rcsaMonthSelect')?.addEventListener('change', async (event) => {
            try {
                const branch = resolveBranch(getCurrentSession);
                await loadChecklist(event.target.value, branch);
                if (typeof onChecklistChange === 'function') onChecklistChange(checklistState);
            } catch (error) {
                showToast(error.message || 'Unable to load RCSA month.');
            }
        });

        document.getElementById('rcsaBranchSelect')?.addEventListener('change', async (event) => {
            try {
                selectedRcsaBranch = event.target.value || null;
                const periodId = selectedRcsaPeriodId || currentPeriod?.period?.id;
                if (!periodId) return;
                await loadChecklist(periodId, selectedRcsaBranch);
                if (typeof onChecklistChange === 'function') onChecklistChange(checklistState);
            } catch (error) {
                showToast(error.message || 'Unable to load branch.');
            }
        });

        document.getElementById('createPeriodBtn')?.addEventListener('click', async () => {
            try {
                await createCurrentMonth();
                showToast('Month ready.');
            } catch (error) {
                showToast(error.message || 'Unable to create month.');
            }
        });

        async function handleSubmit(branch) {
            if (!branch) return;
            const periodLabel = checklistState.period?.label || 'this month';
            const ok = await window.DashboardConfirm.submit(
                `Submit the RCSA report for ${branch} · ${periodLabel}? Once submitted, edits need unlock.`,
                'Submit monthly report'
            );
            if (!ok) return;
            const periodId = selectedRcsaPeriodId || currentPeriod?.period?.id;
            await submitBranch(branch, isHq() ? 'Head Office' : 'Branch User', periodId);
            await loadChecklist(periodId, branch);
            if (!isHq()) await loadBranchHistory(branch);
            if (typeof onChecklistChange === 'function') onChecklistChange(checklistState);
            showToast('Submitted.');
        }

        document.getElementById('rcsaSubmitBtn')?.addEventListener('click', async () => {
            if (!checklistState.periodOpen) {
                showToast('This month is locked.');
                return;
            }
            const branch = resolveBranch(getCurrentSession);
            try {
                if (!branch) {
                    showToast('Select a branch first.');
                    return;
                }
                await handleSubmit(branch);
            } catch (error) {
                showToast(error.message || 'Submit failed.');
            }
        });

        document.getElementById('rcsaUnlockBtn')?.addEventListener('click', async () => {
            const branch = resolveBranch(getCurrentSession);
            const isActiveMonth = !!checklistState.period?.isActive;
            try {
                if (!branch) {
                    showToast('Select a branch first.');
                    return;
                }
                if (!isHq() && !isActiveMonth) {
                    showToast('Only Head Office can unlock past month reports.');
                    return;
                }
                const forBranch = !isHq();
                const ok = await window.DashboardConfirm.unlock(
                    forBranch
                        ? `Unlock ${branch} and open the workspace so you can edit items, then submit again.`
                        : `Unlock access for ${branch}? They will be able to edit in workspace.`,
                    forBranch ? 'Unlock & edit' : 'Unlock access'
                );
                if (!ok) return;
                await unlockBranch(branch, selectedRcsaPeriodId, { redirect: forBranch });
                if (isHq()) {
                    showToast('Unlocked. Open workspace to edit, or let the branch continue.');
                    if (typeof onChecklistChange === 'function') onChecklistChange(checklistState);
                }
            } catch (error) {
                showToast(error.message || 'Unlock failed.');
            }
        });

        document.getElementById('rcsaExtendBtn')?.addEventListener('click', () => {
            if (!isHq()) return;
            openExtendModal({
                scope: checklistState.branch ? 'branch' : 'all',
                branch: checklistState.branch,
            });
        });

        document.getElementById('extendAllBtn')?.addEventListener('click', () => {
            openExtendModal({ scope: 'all' });
        });

        document.getElementById('rcsaWorkspaceBtn')?.addEventListener('click', () => {
            try {
                openWorkspace();
            } catch (error) {
                showToast(error.message || 'Unable to open workspace.');
            }
        });

        document.getElementById('branchGoRcsaBtn')?.addEventListener('click', () => {
            if (typeof switchTabFn === 'function') switchTabFn('rcsa');
        });

        document.getElementById('extendScope')?.addEventListener('change', (event) => {
            document.getElementById('extendBranchWrap')?.classList.toggle('hidden', event.target.value !== 'branch');
        });

        document.getElementById('extendForm')?.addEventListener('submit', async (event) => {
            event.preventDefault();
            try {
                const scope = document.getElementById('extendScope')?.value || 'all';
                const days = Number(document.getElementById('extendDays')?.value || 0);
                const branch = scope === 'branch' ? document.getElementById('extendBranch')?.value : null;
                if (scope === 'branch' && !branch) {
                    showToast('Select a branch.');
                    return;
                }
                const data = await extendDeadline(days, branch);
                closeExtendModal();
                showToast(data.message || 'Deadline extended.');
            } catch (error) {
                showToast(error.message || 'Unable to extend deadline.');
            }
        });

        document.querySelectorAll('[data-action="close-report-modal"]').forEach((btn) => {
            btn.addEventListener('click', closeModal);
        });
        document.querySelectorAll('[data-action="close-extend-modal"]').forEach((btn) => {
            btn.addEventListener('click', closeExtendModal);
        });

        document.addEventListener('click', async (event) => {
            const viewBtn = event.target.closest('[data-view-submission]');
            if (viewBtn) {
                try {
                    await viewSubmission(viewBtn.dataset.viewSubmission);
                } catch (error) {
                    showToast(error.message || 'Unable to open report.');
                }
                return;
            }

            const unlockBtn = event.target.closest('[data-unlock-branch]');
            if (unlockBtn) {
                try {
                    const targetBranch = unlockBtn.dataset.unlockBranch;
                    const periodId = unlockBtn.dataset.unlockPeriod || currentPeriod?.period?.id || selectedRcsaPeriodId;
                    const forceHqPast = unlockBtn.dataset.unlockPast === '1';
                    if (!isHq() && forceHqPast) {
                        showToast('Only Head Office can unlock past month reports.');
                        return;
                    }
                    if (!isHq() && targetBranch && targetBranch !== currentRole) {
                        showToast('You can only unlock your own branch.');
                        return;
                    }
                    const redirect = !isHq();
                    const ok = await window.DashboardConfirm.unlock(
                        redirect
                            ? `Unlock and open workspace for ${targetBranch}?`
                            : `Unlock access for ${targetBranch}?`,
                        redirect ? 'Unlock & edit' : 'Unlock access'
                    );
                    if (!ok) return;
                    await unlockBranch(targetBranch, periodId, { redirect });
                    if (!redirect) {
                        showToast('Unlocked.');
                        await loadChecklist(selectedRcsaPeriodId, selectedRcsaBranch);
                        if (!isHq()) await loadBranchHistory(currentRole);
                        if (typeof onChecklistChange === 'function') onChecklistChange(checklistState);
                    }
                } catch (error) {
                    showToast(error.message || 'Unlock failed.');
                }
                return;
            }

            const extendBtn = event.target.closest('[data-extend-branch]');
            if (extendBtn) {
                if (!isHq()) return;
                openExtendModal({ scope: 'branch', branch: extendBtn.dataset.extendBranch });
                return;
            }

            const openMonthBtn = event.target.closest('[data-open-month]');
            if (openMonthBtn) {
                try {
                    const branch = resolveBranch(getCurrentSession);
                    await loadChecklist(openMonthBtn.dataset.openMonth, branch);
                    if (typeof switchTabFn === 'function') switchTabFn('rcsa');
                    else if (typeof onChecklistChange === 'function') onChecklistChange(checklistState);
                } catch (error) {
                    showToast(error.message || 'Unable to open month.');
                }
            }
        });
    }

    async function submitBranch(branch, submittedBy = 'Branch User', periodId = null) {
        const id = periodId || currentPeriod?.period?.id;
        if (!id) throw new Error('No period selected.');
        if (!branch || branch === 'HQ') throw new Error('Select a branch to submit.');

        const data = await request(`${cfg().routes.reportPeriods}/${id}/submit`, {
            method: 'POST',
            body: JSON.stringify({ branch, submittedBy }),
        });
        currentPeriod = data.period;
        const summary = data.period.period;
        const idx = periods.findIndex((p) => Number(p.id) === Number(summary.id));
        if (idx >= 0) periods[idx] = summary;
        render();
        return data;
    }

    function setSelectedBranch(branch) {
        selectedRcsaBranch = branch && branch !== 'HQ' ? branch : null;
        const select = document.getElementById('rcsaBranchSelect');
        if (select) {
            select.value = selectedRcsaBranch || '';
            select.disabled = !isHq();
        }
    }

    return {
        load,
        loadPeriod,
        loadChecklist,
        loadBranchHistory,
        render,
        bind,
        setRole,
        unlockBranch,
        openWorkspace,
        extendDeadline,
        setSelectedBranch,
        getCurrentPeriod: () => currentPeriod,
        getChecklistState: () => checklistState,
        isRcsaEditable: () => !!checklistState.editable,
        getSelectedRcsaPeriodId: () => selectedRcsaPeriodId,
        getSelectedRcsaBranch: () => selectedRcsaBranch,
        getBranchStatus: () => checklistState.branchStatus,
        isHq,
    };
})();
