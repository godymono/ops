window.DashboardRender = (function () {
    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, (char) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
        }[char]));
    }

    function getDaysRemaining(dateStr) {
        if (!dateStr) return 0;
        const target = new Date(dateStr);
        const today = new Date();
        return Math.ceil((target - today) / (1000 * 60 * 60 * 24));
    }

    function rcsaStatusClass(status) {
        if (status === 'YES') return 'status-yes';
        if (status === 'NO') return 'status-no';
        return 'status-neutral';
    }

    function applyRcsaStatusColor(selectEl, status = null) {
        if (!selectEl) return;
        const value = status ?? selectEl.value;
        selectEl.classList.remove('status-yes', 'status-no', 'status-neutral');
        selectEl.classList.add('rcsa-status', rcsaStatusClass(value));
    }

    function updateBadges(appState) {
        const pendingMaint = appState.maintenance.filter((m) => m.status !== 'Resolved').length;
        const licenceAlerts = appState.licences.filter((l) => getDaysRemaining(l.expiryDate) <= 30).length;
        const badgeMaint = document.getElementById('badgePendingCount');
        const badgeLicence = document.getElementById('badgeLicenceAlert');
        if (badgeMaint) badgeMaint.textContent = pendingMaint;
        if (badgeLicence) badgeLicence.textContent = licenceAlerts;
    }

    function renderKPIsMaintenance(data) {
        const container = document.getElementById('kpiContainer');
        const total = data.length;
        const open = data.filter((d) => d.status === 'Open').length;
        const inProg = data.filter((d) => d.status === 'In Progress').length;
        const resolved = data.filter((d) => d.status === 'Resolved').length;

        container.innerHTML = `
            <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Total</p>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mt-1">${total}</h3>
                </div>
                <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-rose-200 dark:border-rose-950/60 shadow-sm">
                    <p class="text-xs text-rose-600 dark:text-rose-400 font-medium">Open</p>
                    <h3 class="text-xl font-bold text-rose-600 dark:text-rose-400 mt-1">${open}</h3>
                </div>
                <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-amber-200 dark:border-amber-950/60 shadow-sm">
                    <p class="text-xs text-amber-600 dark:text-amber-400 font-medium">In Progress</p>
                    <h3 class="text-xl font-bold text-amber-600 dark:text-amber-400 mt-1">${inProg}</h3>
                </div>
                <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-emerald-200 dark:border-emerald-950/60 shadow-sm">
                    <p class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">Resolved</p>
                    <h3 class="text-xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">${resolved}</h3>
                </div>
            `;
        }

        function renderKPIsLicences(data) {
            const container = document.getElementById('kpiContainer');
            const total = data.length;
            const valid = data.filter((d) => getDaysRemaining(d.expiryDate) > 30).length;
            const expiringSoon = data.filter((d) => {
                const days = getDaysRemaining(d.expiryDate);
                return days >= 0 && days <= 30;
            }).length;
            const expired = data.filter((d) => getDaysRemaining(d.expiryDate) < 0).length;

            container.innerHTML = `
                <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Total</p>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mt-1">${total}</h3>
                </div>
                <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-emerald-200 dark:border-emerald-950/60 shadow-sm">
                    <p class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">Valid</p>
                    <h3 class="text-xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">${valid}</h3>
                </div>
                <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-amber-200 dark:border-amber-950/60 shadow-sm">
                    <p class="text-xs text-amber-600 dark:text-amber-400 font-medium">Expiring</p>
                    <h3 class="text-xl font-bold text-amber-600 dark:text-amber-400 mt-1">${expiringSoon}</h3>
                </div>
                <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-rose-200 dark:border-rose-950/60 shadow-sm">
                    <p class="text-xs text-rose-600 dark:text-rose-400 font-medium">Expired</p>
                    <h3 class="text-xl font-bold text-rose-600 dark:text-rose-400 mt-1">${expired}</h3>
                </div>
            `;
        }

        function renderKPIsRcsa(data) {
            const container = document.getElementById('kpiContainer');
            const total = data.length;
            const yes = data.filter((d) => d.status === 'YES').length;
            const no = data.filter((d) => d.status === 'NO').length;
            const rate = total > 0 ? Math.round((yes / total) * 100) : 0;

            container.innerHTML = `
                <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Total</p>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mt-1">${total}</h3>
                </div>
                <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-emerald-200 dark:border-emerald-950/60 shadow-sm">
                    <p class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">YES</p>
                    <h3 class="text-xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">${yes}</h3>
                </div>
                <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-rose-200 dark:border-rose-950/60 shadow-sm">
                    <p class="text-xs text-rose-600 dark:text-rose-400 font-medium">NO</p>
                    <h3 class="text-xl font-bold text-rose-600 dark:text-rose-400 mt-1">${no}</h3>
                </div>
                <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-blue-200 dark:border-blue-950/60 shadow-sm">
                    <p class="text-xs text-azania-navy dark:text-blue-400 font-medium">Rate</p>
                    <h3 class="text-xl font-bold text-azania-navy dark:text-blue-400 mt-1">${rate}%</h3>
                </div>
            `;
        }

    function renderMaintenanceTable(data, currentUserSession) {
        const tbody = document.getElementById('maintenanceTableBody');
        tbody.innerHTML = '';

        if (data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8" class="py-6 text-center text-slate-400 dark:text-slate-500">No maintenance issues found.</td></tr>`;
            return;
        }

        data.forEach((item) => {
            const priorityColor = item.priority === 'High'
                ? 'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border-rose-200 dark:border-rose-900'
                : (item.priority === 'Medium'
                    ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200 dark:border-amber-900'
                    : 'bg-blue-100 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border-blue-200 dark:border-blue-900');

            tbody.innerHTML += `
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                    <td class="py-2.5 px-3 text-center font-mono text-slate-400 dark:text-slate-500 font-semibold whitespace-nowrap">#${item.id}</td>
                    <td class="py-2.5 px-3 font-semibold text-slate-900 dark:text-slate-100 whitespace-nowrap">${escapeHtml(item.branch)}</td>
                    <td class="py-2.5 px-3 min-w-[220px] max-w-xs break-words whitespace-normal font-medium text-slate-800 dark:text-slate-200 leading-tight">
                        <div>${escapeHtml(item.title)}</div>
                        <span class="text-[10px] text-slate-400 dark:text-slate-500 uppercase font-semibold">${escapeHtml(item.category)}</span>
                    </td>
                    <td class="py-2.5 px-3 text-center whitespace-nowrap">
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded border ${priorityColor}">${escapeHtml(item.priority)}</span>
                    </td>
                    <td class="py-2.5 px-3 font-medium text-slate-700 dark:text-slate-300 whitespace-nowrap">${escapeHtml(item.officer)}</td>
                    <td class="py-2.5 px-3 text-slate-600 dark:text-slate-400 whitespace-nowrap">${escapeHtml(item.targetDate || '-')}</td>
                    <td class="py-2.5 px-3 text-center whitespace-nowrap">
                        <select data-inline-status="maintenance" data-id="${item.id}" class="bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs rounded px-2 py-1 font-semibold text-slate-800 dark:text-slate-200 focus:outline-none">
                            <option value="Open" ${item.status === 'Open' ? 'selected' : ''}>Open</option>
                            <option value="In Progress" ${item.status === 'In Progress' ? 'selected' : ''}>In Progress</option>
                            <option value="Resolved" ${item.status === 'Resolved' ? 'selected' : ''}>Resolved</option>
                        </select>
                    </td>
                    <td class="py-2.5 px-3 text-center whitespace-nowrap">
                        <div class="flex items-center justify-center space-x-1">
                            <button type="button" data-edit="maintenance" data-id="${item.id}" class="action-btn text-slate-400 hover:text-azania-navy dark:hover:text-blue-400" title="Edit"><i class="fa-solid fa-pen"></i></button>
                            <button type="button" data-delete="maintenance" data-id="${item.id}" class="action-btn text-slate-400 hover:text-rose-600 dark:hover:text-rose-400" title="Delete"><i class="fa-solid fa-trash"></i></button>
                        </div>
                    </td>
                </tr>
            `;
        });
    }

    function renderLicencesTable(data, currentUserSession) {
        const tbody = document.getElementById('licenceTableBody');
        tbody.innerHTML = '';

        if (data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8" class="py-6 text-center text-slate-400 dark:text-slate-500">No statutory licences found.</td></tr>`;
            return;
        }

        data.forEach((item) => {
            const daysLeft = getDaysRemaining(item.expiryDate);
            const daysBadge = daysLeft < 0
                ? `<span class="bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 px-2 py-0.5 rounded font-bold text-[10px]">Expired (${Math.abs(daysLeft)}d ago)</span>`
                : (daysLeft <= 30
                    ? `<span class="bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 px-2 py-0.5 rounded font-bold text-[10px]">${daysLeft} days left</span>`
                    : `<span class="bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 px-2 py-0.5 rounded font-bold text-[10px]">${daysLeft} days left</span>`);

            tbody.innerHTML += `
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                    <td class="py-2.5 px-3 text-center font-mono text-slate-400 dark:text-slate-500 font-semibold whitespace-nowrap">#${item.id}</td>
                    <td class="py-2.5 px-3 font-semibold text-slate-900 dark:text-slate-100 whitespace-nowrap">${escapeHtml(item.branch)}</td>
                    <td class="py-2.5 px-3 min-w-[220px] max-w-xs break-words whitespace-normal font-medium text-slate-800 dark:text-slate-200 leading-tight">${escapeHtml(item.title)}</td>
                    <td class="py-2.5 px-3 font-medium text-slate-600 dark:text-slate-400 whitespace-nowrap">${escapeHtml(item.authority)}</td>
                    <td class="py-2.5 px-3 text-slate-600 dark:text-slate-400 font-mono whitespace-nowrap">${escapeHtml(item.expiryDate || '-')}</td>
                    <td class="py-2.5 px-3 text-center whitespace-nowrap">${daysBadge}</td>
                    <td class="py-2.5 px-3 text-center whitespace-nowrap">
                        <select data-inline-status="licences" data-id="${item.id}" class="bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs rounded px-2 py-1 font-semibold text-slate-800 dark:text-slate-200 focus:outline-none">
                            <option value="Valid" ${item.status === 'Valid' ? 'selected' : ''}>Valid</option>
                            <option value="Renewal Pending" ${item.status === 'Renewal Pending' ? 'selected' : ''}>Renewal Pending</option>
                            <option value="Expired" ${item.status === 'Expired' ? 'selected' : ''}>Expired</option>
                        </select>
                    </td>
                    <td class="py-2.5 px-3 text-center whitespace-nowrap">
                        <div class="flex items-center justify-center space-x-1">
                            <button type="button" data-edit="licences" data-id="${item.id}" class="action-btn text-slate-400 hover:text-azania-navy dark:hover:text-blue-400" title="Edit"><i class="fa-solid fa-pen"></i></button>
                            <button type="button" data-delete="licences" data-id="${item.id}" class="action-btn text-slate-400 hover:text-rose-600 dark:hover:text-rose-400" title="Delete"><i class="fa-solid fa-trash"></i></button>
                        </div>
                    </td>
                </tr>
            `;
        });
    }

    function renderRcsaTable(data, editable = true) {
        const tbody = document.getElementById('rcsaTableBody');
        const summary = document.getElementById('rcsaCountSummary');
        if (summary) summary.textContent = `${data.length} items`;
        tbody.innerHTML = '';

        if (data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="9" class="py-6 text-center text-slate-400 dark:text-slate-500">${editable ? 'No RCSA items found.' : 'No submitted report for this month.'}</td></tr>`;
            return;
        }

        data.slice(0, 100).forEach((item) => {
            const statusCell = editable
                ? `<select data-inline-status="rcsa" data-id="${item.id}" class="rcsa-status ${rcsaStatusClass(item.status)} text-xs rounded px-2 py-1 focus:outline-none">
                        <option value="YES" ${item.status === 'YES' ? 'selected' : ''}>YES</option>
                        <option value="NO" ${item.status === 'NO' ? 'selected' : ''}>NO</option>
                        <option value="N/A" ${item.status === 'N/A' ? 'selected' : ''}>N/A</option>
                        <option value="N/E" ${item.status === 'N/E' ? 'selected' : ''}>N/E</option>
                   </select>`
                : `<span class="rcsa-status ${rcsaStatusClass(item.status)} inline-block px-2 py-0.5 rounded text-[10px] font-bold border">${escapeHtml(item.status)}</span>`;

            const actionsCell = editable
                ? `<div class="flex items-center justify-center space-x-1">
                        <button type="button" data-edit="rcsa" data-id="${item.id}" class="action-btn text-slate-400 hover:text-azania-navy dark:hover:text-blue-400" title="Edit"><i class="fa-solid fa-pen"></i></button>
                        <button type="button" data-delete="rcsa" data-id="${item.id}" class="action-btn text-slate-400 hover:text-rose-600 dark:hover:text-rose-400" title="Delete"><i class="fa-solid fa-trash"></i></button>
                   </div>`
                : `<span class="text-[10px] text-slate-400 font-semibold uppercase">Locked</span>`;

            tbody.innerHTML += `
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                    <td class="py-2.5 px-3 text-center font-mono text-slate-400 dark:text-slate-500 font-semibold whitespace-nowrap">#${item.id}</td>
                    <td class="py-2.5 px-3 font-semibold text-slate-900 dark:text-slate-100 whitespace-nowrap">${escapeHtml(item.branch)}</td>
                    <td class="py-2.5 px-3 text-slate-600 dark:text-slate-400 whitespace-nowrap">${escapeHtml(item.category)}</td>
                    <td class="py-2.5 px-3 min-w-[200px] max-w-xs md:max-w-md break-words whitespace-normal font-medium text-slate-800 dark:text-slate-200 leading-tight">${escapeHtml(item.item)}</td>
                    <td class="py-2.5 px-3 text-center whitespace-nowrap">${statusCell}</td>
                    <td class="py-2.5 px-3 min-w-[160px] max-w-xs break-words whitespace-normal text-slate-600 dark:text-slate-400 leading-tight">${escapeHtml(item.remarks || '-')}</td>
                    <td class="py-2.5 px-3 min-w-[160px] max-w-xs break-words whitespace-normal text-slate-600 dark:text-slate-400 leading-tight">${escapeHtml(item.actionPlan || '-')}</td>
                    <td class="py-2.5 px-3 text-slate-600 dark:text-slate-400 whitespace-nowrap">${escapeHtml(item.targetDate || '-')}</td>
                    <td class="py-2.5 px-3 text-center whitespace-nowrap">${actionsCell}</td>
                </tr>
            `;
        });
    }

    return {
        escapeHtml,
        getDaysRemaining,
        rcsaStatusClass,
        applyRcsaStatusColor,
        updateBadges,
        renderKPIsMaintenance,
        renderKPIsLicences,
        renderKPIsRcsa,
        renderMaintenanceTable,
        renderLicencesTable,
        renderRcsaTable,
    };
})();
