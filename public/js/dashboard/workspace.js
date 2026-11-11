(function () {
    const Api = window.DashboardApi;
    const Theme = window.DashboardTheme;
    const Render = window.DashboardRender;
    const Modal = window.DashboardModal;
    const ws = window.OpsControl.workspace || {};

    let appState = {
        maintenance: window.OpsControl.initialState?.maintenance || [],
        licences: window.OpsControl.initialState?.licences || [],
        rcsa: (window.OpsControl.initialState?.rcsa || []).filter((row) => row.branch === ws.branch),
    };
    let dashboardOptions = window.OpsControl.initialOptions || {};
    const currentUserSession = ws.branch;

    function showToast(msg) {
        const toast = document.getElementById('toast');
        const toastMsg = document.getElementById('toastMessage');
        if (!toast || !toastMsg) return;
        toastMsg.textContent = msg;
        toast.classList.remove('translate-y-20', 'opacity-0', 'pointer-events-none');
        setTimeout(() => toast.classList.add('translate-y-20', 'opacity-0', 'pointer-events-none'), 3000);
    }

    function renderApp() {
        const search = document.getElementById('searchInput').value.toLowerCase();
        const category = document.getElementById('categoryFilter').value;
        const status = document.getElementById('statusFilter').value;
        const data = appState.rcsa.filter((item) => {
            const textMatch = !search
                || (item.item && item.item.toLowerCase().includes(search))
                || (item.remarks && item.remarks.toLowerCase().includes(search));
            const catMatch = category === 'ALL' || item.category === category;
            const statusMatch = status === 'ALL' || item.status === status;
            return item.branch === ws.branch && textMatch && catMatch && statusMatch;
        });
        Render.renderKPIsRcsa(data);
        Render.renderRcsaTable(data, true);
    }

    function upsert(record) {
        const index = appState.rcsa.findIndex((row) => Number(row.id) === Number(record.id));
        if (index >= 0) appState.rcsa[index] = record;
        else appState.rcsa.unshift(record);
    }

    async function submitWorkspace() {
        const ok = await window.DashboardConfirm.submit(
            `Submit the RCSA report for ${ws.branch} · ${ws.checklist?.period?.label || 'this month'}?`,
            'Submit monthly report'
        );
        if (!ok) return;
        const response = await fetch(`${window.OpsControl.routes.reportPeriods}/${ws.periodId}/submit`, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.OpsControl.csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ branch: ws.branch, submittedBy: 'Branch User' }),
        });
        const payload = await response.json();
        if (!response.ok) throw new Error(payload.message || 'Submit failed.');
        showToast('Submitted.');
        setTimeout(() => {
            window.location.href = `${window.OpsControl.routes.dashboard}?tab=rcsa`;
        }, 600);
    }

    window.addEventListener('DOMContentLoaded', () => {
        Theme.initTheme();
        const filterOptions = {
            ...dashboardOptions,
            categories: dashboardOptions.rcsaCategories || dashboardOptions.categories || [],
        };
        Modal.setupFilters(filterOptions, renderApp);
        const branchFilter = document.getElementById('branchFilter');
        if (branchFilter) {
            branchFilter.innerHTML = `<option value="${ws.branch}">${ws.branch}</option>`;
            branchFilter.value = ws.branch;
            branchFilter.disabled = true;
        }

        document.querySelectorAll('[data-action="open-add"]').forEach((btn) => {
            btn.addEventListener('click', () => Modal.openAddModal('rcsa', dashboardOptions, ws.branch));
        });
        document.querySelectorAll('[data-action="close-modal"]').forEach((btn) => {
            btn.addEventListener('click', () => Modal.closeModal());
        });
        Modal.bindDestinationEvents(() => dashboardOptions, () => ws.branch);

        document.getElementById('unifiedForm')?.addEventListener('submit', async (event) => {
            event.preventDefault();
            try {
                const collected = Modal.collectFormPayload();
                collected.payload.branch = ws.branch;
                collected.module = 'rcsa';
                if (collected.itemId) {
                    const result = await Api.updateRecord('rcsa', collected.itemId, collected.payload);
                    upsert(result.record);
                    showToast('Updated.');
                } else {
                    const result = await Api.createRecord('rcsa', collected.payload);
                    upsert(result.record);
                    showToast('Saved.');
                }
                Modal.closeModal();
                renderApp();
            } catch (error) {
                showToast(error.message || 'Save failed.');
            }
        });

        document.addEventListener('change', async (event) => {
            const target = event.target;
            if (target?.matches('[data-inline-status="rcsa"]')) {
                Render.applyRcsaStatusColor(target, target.value);
                try {
                    const result = await Api.updateStatus('rcsa', target.dataset.id, target.value);
                    upsert(result.record);
                    renderApp();
                    showToast(`Updated · ${target.value}`);
                } catch (error) {
                    showToast(error.message || 'Update failed.');
                }
            }
            if (target?.id === 'rcsaStatus') {
                Render.applyRcsaStatusColor(target, target.value);
            }
        });

        document.addEventListener('click', async (event) => {
            const editBtn = event.target.closest('[data-edit="rcsa"]');
            if (editBtn) {
                Modal.openEditModal('rcsa', editBtn.dataset.id, appState, dashboardOptions, ws.branch);
                return;
            }
            const deleteBtn = event.target.closest('[data-delete="rcsa"]');
            if (deleteBtn) {
                const ok = await window.DashboardConfirm.danger(
                    'This RCSA item will be removed permanently.',
                    'Delete record'
                );
                if (!ok) return;
                try {
                    await Api.deleteRecord('rcsa', deleteBtn.dataset.id);
                    appState.rcsa = appState.rcsa.filter((row) => Number(row.id) !== Number(deleteBtn.dataset.id));
                    renderApp();
                    showToast('Deleted.');
                } catch (error) {
                    showToast(error.message || 'Delete failed.');
                }
            }
        });

        document.getElementById('workspaceSubmitBtn')?.addEventListener('click', async () => {
            try {
                await submitWorkspace();
            } catch (error) {
                showToast(error.message || 'Submit failed.');
            }
        });

        renderApp();
    });
})();
