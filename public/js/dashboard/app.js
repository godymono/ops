(function () {
    const Api = window.DashboardApi;
    const Theme = window.DashboardTheme;
    const Render = window.DashboardRender;
    const Modal = window.DashboardModal;
    const Reports = window.DashboardReports;

    let currentTab = 'maintenance';
    let currentUserSession = 'HQ';
    let appState = {
        maintenance: [],
        licences: [],
        rcsa: [],
    };
    let dashboardOptions = window.OpsControl?.initialOptions || {
        branches: [], categories: [], maintenanceCategories: [],
        licenceCategories: [], rcsaCategories: [], officers: [],
        statuses: [], priorities: [], sessions: ['HQ'],
    };

    function showToast(msg) {
        const toast = document.getElementById('toast');
        const toastMsg = document.getElementById('toastMessage');
        if (!toast || !toastMsg) return;
        toastMsg.textContent = msg;
        toast.classList.remove('translate-y-20', 'opacity-0', 'pointer-events-none');
        setTimeout(() => {
            toast.classList.add('translate-y-20', 'opacity-0', 'pointer-events-none');
        }, 3000);
    }

    function upsertLocalRecord(module, record) {
        const index = appState[module].findIndex((row) => Number(row.id) === Number(record.id));
        if (index >= 0) appState[module][index] = record;
        else appState[module].unshift(record);
    }

    function removeLocalRecord(module, id) {
        appState[module] = appState[module].filter((row) => Number(row.id) !== Number(id));
    }

    function updateHeaderAddLabel(tabName) {
        const label = document.getElementById('headerAddBtnLabel');
        if (!label) return;
        label.textContent = 'Add';
    }

    function switchTab(tabName) {
        currentTab = tabName;
        updateHeaderAddLabel(tabName);

        document.querySelectorAll('.tab-btn').forEach((btn) => {
            btn.className = 'tab-btn flex-1 min-w-[150px] py-2.5 px-4 rounded-lg text-xs sm:text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-white/50 dark:hover:bg-slate-800/50 transition flex items-center justify-center gap-2';
        });
        document.querySelectorAll('.tab-content').forEach((content) => content.classList.add('hidden'));

        const activeBtn = document.getElementById(`tab-${tabName}`);
        const activeContent = document.getElementById(`module-${tabName}`);
        if (activeBtn) {
            activeBtn.className = 'tab-btn flex-1 min-w-[150px] py-2.5 px-4 rounded-lg text-xs sm:text-sm font-bold bg-white dark:bg-slate-800 text-azania-navy dark:text-blue-400 shadow-sm border border-slate-200 dark:border-slate-700 transition flex items-center justify-center gap-2';
        }
        if (activeContent) activeContent.classList.remove('hidden');

        const filtersSection = document.getElementById('searchInput')?.closest('section');
        if (filtersSection) {
            filtersSection.classList.toggle('hidden', tabName === 'reports');
        }

        const headerAdd = document.getElementById('headerAddBtn');
        if (headerAdd) headerAdd.classList.toggle('hidden', tabName === 'reports');

        if (tabName === 'rcsa') {
            const sessionBranch = currentUserSession === 'HQ'
                ? Reports.getSelectedRcsaBranch()
                : currentUserSession;
            const periodId = Reports.getSelectedRcsaPeriodId();
            if (periodId) {
                Reports.loadChecklist(periodId, sessionBranch).then(() => renderApp()).catch(() => renderApp());
                return;
            }
        }

        renderApp();
    }

    function applyAuthSession(sessionVal) {
        currentUserSession = sessionVal || 'HQ';
        const branchFilter = document.getElementById('branchFilter');
        if (branchFilter) {
            if (currentUserSession !== 'HQ') {
                branchFilter.value = currentUserSession;
                branchFilter.disabled = true;
            } else {
                branchFilter.disabled = false;
            }
        }

        Reports.setRole(currentUserSession);
        Reports.setSelectedBranch(currentUserSession === 'HQ' ? null : currentUserSession);

        const banner = document.getElementById('sessionPrivilegeBanner');
        if (banner) {
            if (currentUserSession === 'HQ') {
                banner.className = 'bg-blue-50/80 dark:bg-blue-950/40 border-b border-blue-100 dark:border-blue-900/40 px-4 sm:px-8 py-2 text-xs flex items-center justify-between transition-colors duration-200';
                banner.innerHTML = `
                    <div class="flex items-center space-x-2 text-azania-navy dark:text-blue-400 font-semibold">
                        <i class="fa-solid fa-building-columns"></i>
                        <span>Head Office · Monitor submissions, unlock access, extend deadlines</span>
                    </div>
                    <span class="text-[11px] text-slate-500 dark:text-slate-400 hidden sm:inline">Signed in securely</span>
                `;
            } else {
                banner.className = 'bg-amber-50/90 dark:bg-amber-950/30 border-b border-amber-100 dark:border-amber-900/40 px-4 sm:px-8 py-2 text-xs flex items-center justify-between transition-colors duration-200';
                banner.innerHTML = `
                    <div class="flex items-center space-x-2 text-amber-800 dark:text-amber-400 font-semibold">
                        <i class="fa-solid fa-store"></i>
                        <span>${Render.escapeHtml(currentUserSession)} · Branch workspace</span>
                    </div>
                    <span class="text-[11px] text-amber-700/80 dark:text-amber-300/80 hidden sm:inline">Only your branch data</span>
                `;
            }
        }

        const periodId = Reports.getSelectedRcsaPeriodId();
        const branch = currentUserSession === 'HQ' ? Reports.getSelectedRcsaBranch() : currentUserSession;

        return (async () => {
            if (periodId) await Reports.loadChecklist(periodId, branch);
            if (currentUserSession !== 'HQ') await Reports.loadBranchHistory(currentUserSession);
            renderApp();
        })();
    }

    function renderApp() {
        Render.updateBadges(appState);

        const search = document.getElementById('searchInput').value.toLowerCase();
        let branch = currentUserSession !== 'HQ' ? currentUserSession : document.getElementById('branchFilter').value;
        if (currentTab === 'rcsa') {
            const rcsaBranch = Reports.getSelectedRcsaBranch() || document.getElementById('rcsaBranchSelect')?.value;
            if (rcsaBranch) branch = rcsaBranch;
        }
        const category = document.getElementById('categoryFilter').value;
        const status = document.getElementById('statusFilter').value;
        const includeResolved = document.getElementById('toggleResolvedCheck')?.checked;

        const filterFn = (item) => {
            const textMatch = !search
                || (item.title && item.title.toLowerCase().includes(search))
                || (item.item && item.item.toLowerCase().includes(search))
                || (item.remarks && item.remarks.toLowerCase().includes(search))
                || (item.officer && item.officer.toLowerCase().includes(search));
            const branchMatch = branch === 'ALL' || item.branch === branch;
            const catMatch = category === 'ALL' || item.category === category;
            const statusMatch = status === 'ALL' || item.status === status;
            return textMatch && branchMatch && catMatch && statusMatch;
        };

        if (currentTab === 'maintenance') {
            let data = appState.maintenance.filter(filterFn);
            if (!includeResolved) data = data.filter((d) => d.status !== 'Resolved');
            Render.renderKPIsMaintenance(data);
            Render.renderMaintenanceTable(data, currentUserSession);
        } else if (currentTab === 'licences') {
            const data = appState.licences.filter(filterFn);
            Render.renderKPIsLicences(data);
            Render.renderLicencesTable(data, currentUserSession);
        } else if (currentTab === 'rcsa') {
            const checklist = Reports.getChecklistState();
            const status = Reports.getBranchStatus();
            // Inline edit only while Draft. Unlocked edits happen in workspace.
            const canInlineEdit = Reports.isRcsaEditable() && status === 'pending';
            const useLive = status === 'pending' || status === 'unlocked' || status === 'overview';
            const source = useLive ? appState.rcsa : (checklist.items || []);
            const data = source.filter(filterFn);
            Render.renderKPIsRcsa(data);
            Render.renderRcsaTable(data, canInlineEdit);
        } else if (currentTab === 'reports') {
            Reports.render();
        }
    }

    async function updateInlineStatus(module, id, newStatus) {
        try {
            const result = await Api.updateStatus(module, id, newStatus);
            upsertLocalRecord(module, result.record);
            renderApp();
            showToast(`Updated · ${newStatus}`);
        } catch (error) {
            console.error(error);
            showToast(error.message || 'Update failed.');
            await reloadFromServer(false);
        }
    }

    async function deleteRecord(module, id) {
        const ok = await window.DashboardConfirm.danger(
            'This record will be removed permanently. This cannot be undone.',
            'Delete record'
        );
        if (!ok) return;

        try {
            await Api.deleteRecord(module, id);
            removeLocalRecord(module, id);
            renderApp();
            showToast('Deleted.');
        } catch (error) {
            console.error(error);
            showToast(error.message || 'Delete failed.');
        }
    }

    async function refreshOptions() {
        try {
            const optionsPayload = await Api.loadOptions();
            dashboardOptions = optionsPayload.options || dashboardOptions;
            Modal.setupFilters(dashboardOptions, renderApp);
        } catch (error) {
            console.error(error);
        }
    }

    async function handleFormSubmit(event) {
        event.preventDefault();

        let collected;
        try {
            collected = Modal.collectFormPayload();
        } catch (error) {
            showToast(error.message || 'Please complete the form.');
            return;
        }

        const { module, payload, itemId } = collected;
        if (module === 'rcsa') {
            const status = Reports.getBranchStatus();
            if (!Reports.isRcsaEditable() || status !== 'pending') {
                showToast(status === 'unlocked'
                    ? 'Open Workspace to edit this unlocked report.'
                    : status === 'submitted'
                        ? 'Unlock the report to make changes.'
                        : 'This RCSA month is locked.');
                return;
            }
        }

        try {
            if (itemId) {
                if (module === 'licences' && !payload.status) {
                    const existing = appState.licences.find((row) => Number(row.id) === Number(itemId));
                    payload.status = existing?.status || 'Valid';
                }
                const result = await Api.updateRecord(module, itemId, payload);
                upsertLocalRecord(module, result.record);
                showToast('Updated.');
            } else {
                if (module === 'licences' && !payload.status) payload.status = 'Valid';
                const result = await Api.createRecord(module, payload);
                upsertLocalRecord(module, result.record);
                showToast('Saved.');
                await refreshOptions();
                switchTab(module);
            }
            Modal.closeModal();
            renderApp();
        } catch (error) {
            console.error(error);
            showToast(error.message || 'Save failed.');
        }
    }

    function exportCSV() {
        const data = appState[currentTab] || [];
        if (data.length === 0) {
            showToast('Nothing to export.');
            return;
        }

        const headers = Object.keys(data[0]);
        const rows = data.map((obj) => headers.map((header) => JSON.stringify(obj[header] ?? '')).join(','));
        const csvContent = `data:text/csv;charset=utf-8,${[headers.join(','), ...rows].join('\n')}`;
        const link = document.createElement('a');
        link.setAttribute('href', encodeURI(csvContent));
        link.setAttribute('download', `azania_${currentTab}_report.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        showToast('Exported.');
    }

    async function resetData() {
        const ok = await window.DashboardConfirm.ask({
            title: 'Reset dashboard data?',
            message: 'This restores the default seeded dataset. Custom changes may be lost.',
            confirmText: 'Reset data',
            variant: 'danger',
        });
        if (!ok) return;
        try {
            const result = await Api.resetDefaults();
            appState = result.state;
            dashboardOptions = result.options || dashboardOptions;
            Modal.setupFilters(dashboardOptions, renderApp);
            await applyAuthSession(window.OpsControl?.auth?.session || 'HQ');
            showToast('Reset complete.');
        } catch (error) {
            console.error(error);
            showToast(error.message || 'Reset failed.');
        }
    }

    async function reloadFromServer(showMessage = true) {
        const result = await Api.loadState();
        appState = result.state;
        if (result.options) dashboardOptions = result.options;
        Modal.setupFilters(dashboardOptions, renderApp);
        renderApp();
        if (showMessage) showToast('Reloaded.');
    }

    function bindEvents() {
        document.querySelectorAll('[data-tab]').forEach((btn) => {
            btn.addEventListener('click', () => switchTab(btn.dataset.tab));
        });

        document.querySelectorAll('[data-action="toggle-theme"]').forEach((btn) => {
            btn.addEventListener('click', () => Theme.toggleDarkMode());
        });
        document.querySelectorAll('[data-action="open-add"]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const module = btn.dataset.module || currentTab;
                if (module === 'rcsa') {
                    const status = Reports.getBranchStatus();
                    if (!Reports.isRcsaEditable() || status !== 'pending') {
                        showToast(status === 'unlocked'
                            ? 'Open Workspace to add items.'
                            : status === 'submitted'
                                ? 'Unlock the report to make changes.'
                                : 'Select a branch or unlock this month.');
                        return;
                    }
                }
                Modal.openAddModal(module, dashboardOptions, currentUserSession);
            });
        });
        document.querySelectorAll('[data-action="export-csv"]').forEach((btn) => {
            btn.addEventListener('click', exportCSV);
        });
        document.querySelectorAll('[data-action="reset-data"]').forEach((btn) => {
            btn.addEventListener('click', resetData);
        });
        document.querySelectorAll('[data-action="close-modal"]').forEach((btn) => {
            btn.addEventListener('click', () => Modal.closeModal());
        });

        Modal.bindDestinationEvents(
            () => dashboardOptions,
            () => currentUserSession
        );
        Reports.bind(() => currentUserSession, showToast, () => {
            if (currentTab === 'rcsa' || currentTab === 'reports') renderApp();
        }, switchTab);
        document.getElementById('unifiedForm')?.addEventListener('submit', handleFormSubmit);

        document.addEventListener('change', (event) => {
            const target = event.target;
            if (target?.matches('[data-inline-status]')) {
                if (target.dataset.inlineStatus === 'rcsa') {
                    const status = Reports.getBranchStatus();
                    if (!Reports.isRcsaEditable() || status !== 'pending') {
                        showToast(status === 'unlocked'
                            ? 'Open Workspace to edit.'
                            : status === 'submitted'
                                ? 'Unlock the report to make changes.'
                                : 'This RCSA month is locked.');
                        renderApp();
                        return;
                    }
                }
                if (target.dataset.inlineStatus === 'rcsa') {
                    Render.applyRcsaStatusColor(target, target.value);
                }
                updateInlineStatus(target.dataset.inlineStatus, target.dataset.id, target.value);
            }
            if (target?.id === 'rcsaStatus') {
                Render.applyRcsaStatusColor(target, target.value);
            }
        });

        document.addEventListener('click', (event) => {
            const editBtn = event.target.closest('[data-edit]');
            if (editBtn) {
                if (editBtn.dataset.edit === 'rcsa') {
                    const status = Reports.getBranchStatus();
                    if (!Reports.isRcsaEditable() || status !== 'pending') {
                        showToast(status === 'unlocked'
                            ? 'Open Workspace to edit.'
                            : status === 'submitted'
                                ? 'Unlock the report to make changes.'
                                : 'This RCSA month is locked.');
                        return;
                    }
                }
                Modal.openEditModal(editBtn.dataset.edit, editBtn.dataset.id, appState, dashboardOptions, currentUserSession);
                return;
            }
            const deleteBtn = event.target.closest('[data-delete]');
            if (deleteBtn) {
                if (deleteBtn.dataset.delete === 'rcsa') {
                    const status = Reports.getBranchStatus();
                    if (!Reports.isRcsaEditable() || status !== 'pending') {
                        showToast(status === 'unlocked'
                            ? 'Open Workspace to edit.'
                            : status === 'submitted'
                                ? 'Unlock the report to make changes.'
                                : 'This RCSA month is locked.');
                        return;
                    }
                }
                deleteRecord(deleteBtn.dataset.delete, deleteBtn.dataset.id);
            }
        });
    }

    window.renderApp = renderApp;

    window.addEventListener('DOMContentLoaded', async () => {
        Theme.initTheme();
        bindEvents();

        try {
            if (window.OpsControl?.initialState) {
                appState = window.OpsControl.initialState;
            } else {
                const loaded = await Api.loadState();
                appState = loaded.state;
                dashboardOptions = loaded.options || dashboardOptions;
            }

            if (!dashboardOptions?.branches?.length) {
                const optionsPayload = await Api.loadOptions();
                dashboardOptions = optionsPayload.options;
            }

            Modal.setupFilters(dashboardOptions, renderApp);
            await Reports.load();

            const auth = window.OpsControl?.auth;
            if (!auth?.session) {
                showToast('Session missing. Please sign in again.');
                window.location.href = '/login';
                return;
            }

            await applyAuthSession(auth.session);

            const params = new URLSearchParams(window.location.search);
            const startTab = params.get('tab');
            const defaultTab = auth.isHq ? 'reports' : 'rcsa';
            switchTab(['maintenance', 'licences', 'rcsa', 'reports'].includes(startTab) ? startTab : defaultTab);

            if (window.OpsControl?.flashStatus) {
                showToast(window.OpsControl.flashStatus);
            }
        } catch (error) {
            console.error('Error initializing dashboard', error);
            showToast('Unable to load data.');
        }
    });
})();
