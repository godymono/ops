// Database-driven dropdowns for the existing dashboard.
// Add this code inside the existing dashboard <script> block.
let dashboardOptions = {
    branches: [], categories: [], maintenanceCategories: [],
    licenceCategories: [], rcsaCategories: [], officers: [],
    statuses: [], priorities: [], sessions: ['HQ']
};

async function loadDashboardOptions() {
    const response = await fetch('/dashboard-options', {
        headers: { 'Accept': 'application/json' }
    });
    if (!response.ok) throw new Error(`Unable to load dashboard options (HTTP ${response.status}).`);
    const result = await response.json();
    dashboardOptions = result.options;
}

function fillSelect(selectId, values, firstLabel = null, selectedValue = null) {
    const select = document.getElementById(selectId);
    if (!select) return;
    const currentValue = selectedValue ?? select.value;
    const options = firstLabel === null ? [] : [`<option value="ALL">${firstLabel}</option>`];
    values.forEach(value => options.push(`<option value="${escapeHtml(value)}">${escapeHtml(value)}</option>`));
    select.innerHTML = options.join('');
    if ([...select.options].some(option => option.value === currentValue)) select.value = currentValue;
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    }[char]));
}

function setupFilters() {
    fillSelect('branchFilter', dashboardOptions.branches, 'All Branches');
    fillSelect('categoryFilter', dashboardOptions.categories, 'All Categories');
    fillSelect('statusFilter', dashboardOptions.statuses, 'All Statuses');
    fillSelect('maintPriority', dashboardOptions.priorities);
    fillSelect('maintStatus', dashboardOptions.statuses.filter(value => ['Open', 'In Progress', 'Resolved'].includes(value)));
    fillSelect('rcsaStatus', dashboardOptions.statuses.filter(value => ['YES', 'NO', 'N/A', 'N/E'].includes(value)));

    ['searchInput', 'branchFilter', 'categoryFilter', 'statusFilter'].forEach(id => {
        const element = document.getElementById(id);
        if (element && !element.dataset.filterBound) {
            element.addEventListener('input', renderApp);
            element.addEventListener('change', renderApp);
            element.dataset.filterBound = '1';
        }
    });

    const sessionSelect = document.getElementById('userSessionSelect');
    if (sessionSelect) {
        const currentSession = sessionSelect.value || 'HQ';
        sessionSelect.innerHTML = dashboardOptions.sessions.map(value => {
            const label = value === 'HQ' ? '🏢 All Branches (HQ)' : `📍 ${value}`;
            return `<option value="${escapeHtml(value)}">${escapeHtml(label)}</option>`;
        }).join('');
        sessionSelect.value = dashboardOptions.sessions.includes(currentSession) ? currentSession : 'HQ';
    }
}

function populateModalDropdowns() {
    const branchSelect = document.getElementById('modalBranch');
    const categorySelect = document.getElementById('modalCategory');
    const officerSelect = document.getElementById('maintOfficer');
    const module = document.getElementById('modalModule').value;
    const categories = module === 'rcsa'
        ? dashboardOptions.rcsaCategories
        : module === 'licences'
            ? dashboardOptions.licenceCategories
            : dashboardOptions.maintenanceCategories;

    if (branchSelect) {
        branchSelect.innerHTML = dashboardOptions.branches.map(value =>
            `<option value="${escapeHtml(value)}">${escapeHtml(value)}</option>`
        ).join('');
        if (currentUserSession !== 'HQ') {
            branchSelect.value = currentUserSession;
            branchSelect.disabled = true;
        } else {
            branchSelect.disabled = false;
        }
    }
    if (categorySelect) {
        categorySelect.innerHTML = categories.map(value =>
            `<option value="${escapeHtml(value)}">${escapeHtml(value)}</option>`
        ).join('');
    }
    if (officerSelect) {
        officerSelect.innerHTML = dashboardOptions.officers.map(value =>
            `<option value="${escapeHtml(value)}">${escapeHtml(value)}</option>`
        ).join('');
    }
}

// Replace the original loadState() with this database-only implementation.
async function loadState() {
    const response = await fetch('/state', {
        headers: { 'Accept': 'application/json' }
    });
    if (!response.ok) throw new Error(`Unable to load dashboard data (HTTP ${response.status}).`);
    const result = await response.json();
    if (!result.state) throw new Error('The database returned no dashboard state. Run the database seeder.');
    appState = result.state;
}

// Replace the old resetData(): this reloads saved database data without deleting anything.
async function resetData() {
    if (!confirm('Reload the latest records from the database? No records will be deleted.')) return;
    try {
        await loadState();
        await loadDashboardOptions();
        setupFilters();
        renderApp();
        showToast('Database records reloaded.');
    } catch (error) {
        console.error(error);
        showToast('Unable to reload database records.');
    }
}

// Replace the original DOMContentLoaded listener with this sequence.
window.addEventListener('DOMContentLoaded', async () => {
    initTheme();
    try {
        await loadDashboardOptions();
        setupFilters();
        await loadState();
        switchTab('maintenance');
    } catch (error) {
        console.error('Error initializing dashboard', error);
        showToast('Unable to load dashboard data. Check database configuration and run migrations/seeders.');
    }
});
