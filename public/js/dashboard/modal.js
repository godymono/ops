window.DashboardModal = (function () {
    const R = () => window.DashboardRender;

    const DESTINATIONS = {
        maintenance: {
            label: 'Maintenance',
            table: 'maintenance_issues',
            hint: 'Saving to Maintenance',
            titleLabel: 'Title *',
            titlePlaceholder: 'Issue details...',
            categoryLabel: 'Category *',
            submitLabel: 'Save',
            activeClass: 'border-amber-500 bg-amber-50/60 dark:bg-amber-950/20 ring-1 ring-amber-400/40',
        },
        licences: {
            label: 'Licences',
            table: 'licences',
            hint: 'Saving to Licences',
            titleLabel: 'Licence Name *',
            titlePlaceholder: 'Licence name...',
            categoryLabel: 'Category *',
            submitLabel: 'Save',
            activeClass: 'border-blue-500 bg-blue-50/60 dark:bg-blue-950/20 ring-1 ring-blue-400/40',
        },
        rcsa: {
            label: 'RCSA',
            table: 'rcsa_assessments',
            hint: 'Saving to RCSA',
            titleLabel: 'Control Item *',
            titlePlaceholder: 'Checklist item...',
            categoryLabel: 'Category *',
            submitLabel: 'Save',
            activeClass: 'border-emerald-500 bg-emerald-50/60 dark:bg-emerald-950/20 ring-1 ring-emerald-400/40',
        },
    };

    function fillSelect(selectId, values, firstLabel = null, selectedValue = null) {
        const select = document.getElementById(selectId);
        if (!select) return;
        const currentValue = selectedValue ?? select.value;
        const options = firstLabel === null ? [] : [`<option value="ALL">${firstLabel}</option>`];
        values.forEach((value) => {
            options.push(`<option value="${R().escapeHtml(value)}">${R().escapeHtml(value)}</option>`);
        });
        select.innerHTML = options.join('');
        if ([...select.options].some((option) => option.value === currentValue)) {
            select.value = currentValue;
        }
    }

    function setupFilters(dashboardOptions, onChange) {
        fillSelect('branchFilter', dashboardOptions.branches || [], 'All Branches');
        fillSelect('categoryFilter', dashboardOptions.categories || [], 'All Categories');
        fillSelect('statusFilter', dashboardOptions.statuses || [], 'All Statuses');
        fillSelect('maintPriority', dashboardOptions.priorities || []);
        fillSelect(
            'maintStatus',
            (dashboardOptions.statuses || []).filter((value) => ['Open', 'In Progress', 'Resolved'].includes(value))
        );

        // Keep friendly RCSA status labels (do not flatten to bare values)
        const rcsaStatus = document.getElementById('rcsaStatus');
        if (rcsaStatus && !rcsaStatus.dataset.labelsLocked) {
            rcsaStatus.dataset.labelsLocked = '1';
        }

        ['searchInput', 'branchFilter', 'categoryFilter', 'statusFilter', 'toggleResolvedCheck'].forEach((id) => {
            const element = document.getElementById(id);
            if (element && !element.dataset.filterBound) {
                element.addEventListener('input', onChange);
                element.addEventListener('change', onChange);
                element.dataset.filterBound = '1';
            }
        });

    }

    function getSelectedModule() {
        return document.getElementById('modalModule')?.value || 'maintenance';
    }

    function setSelectedModule(module, dashboardOptions, currentUserSession, { lockDestination = false } = {}) {
        const value = DESTINATIONS[module] ? module : 'maintenance';
        document.getElementById('modalModule').value = value;
        const select = document.getElementById('modalModuleSelect');
        if (select) {
            select.value = value;
            select.disabled = !!lockDestination;
        }

        const pickerWrap = document.getElementById('destinationPickerWrap');
        if (pickerWrap) {
            pickerWrap.classList.toggle('opacity-60', !!lockDestination);
            pickerWrap.querySelectorAll('[data-destination]').forEach((btn) => {
                btn.disabled = !!lockDestination;
                btn.classList.toggle('pointer-events-none', !!lockDestination);
            });
        }

        updateDestinationUI(value);
        populateModalDropdowns(dashboardOptions, currentUserSession);
        toggleModalFields();
    }

    function updateDestinationUI(module) {
        const meta = DESTINATIONS[module] || DESTINATIONS.maintenance;

        document.querySelectorAll('.destination-card').forEach((card) => {
            const isActive = card.dataset.destination === module;
            card.classList.remove(
                'border-amber-500', 'bg-amber-50/60', 'dark:bg-amber-950/20', 'ring-1', 'ring-amber-400/40',
                'border-blue-500', 'bg-blue-50/60', 'dark:bg-blue-950/20', 'ring-blue-400/40',
                'border-emerald-500', 'bg-emerald-50/60', 'dark:bg-emerald-950/20', 'ring-emerald-400/40',
                'border-slate-200', 'dark:border-slate-700'
            );
            if (isActive) {
                meta.activeClass.split(' ').forEach((cls) => card.classList.add(cls));
            } else {
                card.classList.add('border-slate-200', 'dark:border-slate-700');
            }
        });

        const titleLabel = document.getElementById('modalTitleLabel');
        const titleText = document.getElementById('modalTitleText');
        const categoryLabel = document.getElementById('modalCategoryLabel');
        const submitBtn = document.getElementById('modalSubmitBtn');

        if (titleLabel) titleLabel.textContent = meta.titleLabel;
        if (titleText) titleText.placeholder = meta.titlePlaceholder;
        if (categoryLabel) categoryLabel.textContent = meta.categoryLabel;
        if (submitBtn) submitBtn.textContent = meta.submitLabel;
    }

    function populateModalDropdowns(dashboardOptions, currentUserSession) {
        const branchSelect = document.getElementById('modalBranch');
        const categorySelect = document.getElementById('modalCategory');
        const categoryCustom = document.getElementById('modalCategoryCustom');
        const officerSelect = document.getElementById('maintOfficer');
        const module = getSelectedModule();
        const categories = module === 'rcsa'
            ? (dashboardOptions.rcsaCategories || [])
            : module === 'licences'
                ? (dashboardOptions.licenceCategories || [])
                : (dashboardOptions.maintenanceCategories || []);

        if (branchSelect) {
            branchSelect.innerHTML = (dashboardOptions.branches || []).map((value) =>
                `<option value="${R().escapeHtml(value)}">${R().escapeHtml(value)}</option>`
            ).join('');
            if (currentUserSession !== 'HQ') {
                branchSelect.value = currentUserSession;
                branchSelect.disabled = true;
            } else {
                branchSelect.disabled = false;
            }
        }

        if (categorySelect) {
            const options = categories.map((value) =>
                `<option value="${R().escapeHtml(value)}">${R().escapeHtml(value)}</option>`
            );
            options.push('<option value="__custom__">+ New category</option>');
            categorySelect.innerHTML = options.join('');
        }

        if (categoryCustom) {
            categoryCustom.value = '';
            categoryCustom.classList.add('hidden');
        }

        if (officerSelect) {
            officerSelect.innerHTML = (dashboardOptions.officers || []).map((value) =>
                `<option value="${R().escapeHtml(value)}">${R().escapeHtml(value)}</option>`
            ).join('');
        }

        officerSelect?.toggleAttribute('required', module === 'maintenance');
        document.getElementById('licenceExpiryDate')?.toggleAttribute('required', module === 'licences');
    }

    function toggleModalFields() {
        const mod = getSelectedModule();
        document.getElementById('fieldsMaintenance').classList.toggle('hidden', mod !== 'maintenance');
        document.getElementById('fieldsLicences').classList.toggle('hidden', mod !== 'licences');
        document.getElementById('fieldsRcsa').classList.toggle('hidden', mod !== 'rcsa');
        if (mod === 'rcsa') {
            const rcsaStatus = document.getElementById('rcsaStatus');
            window.DashboardRender.applyRcsaStatusColor(rcsaStatus, rcsaStatus.value);
        }
    }

    function closeModal() {
        document.getElementById('itemModal').classList.add('hidden');
    }

    function openAddModal(currentTab, dashboardOptions, currentUserSession) {
        document.getElementById('modalTitle').innerHTML = `<i class="fa-solid fa-plus-circle text-azania-navy dark:text-blue-400"></i> Add Record`;
        document.getElementById('unifiedForm').reset();
        document.getElementById('modalItemId').value = '';
        document.getElementById('modalModuleSelect').disabled = false;
        setSelectedModule(currentTab || 'maintenance', dashboardOptions, currentUserSession, { lockDestination: false });
        if (currentTab === 'rcsa') {
            document.getElementById('rcsaStatus').value = 'N/E';
            window.DashboardRender.applyRcsaStatusColor(document.getElementById('rcsaStatus'), 'N/E');
        }
        document.getElementById('itemModal').classList.remove('hidden');
    }

    function openEditModal(module, id, appState, dashboardOptions, currentUserSession) {
        const item = appState[module].find((row) => Number(row.id) === Number(id));
        if (!item) return;

        document.getElementById('modalTitle').innerHTML = `<i class="fa-solid fa-pen-to-square text-azania-navy dark:text-blue-400"></i> Edit #${item.id}`;
        document.getElementById('modalItemId').value = item.id;
        setSelectedModule(module, dashboardOptions, currentUserSession, { lockDestination: true });

        document.getElementById('modalBranch').value = item.branch;

        const categorySelect = document.getElementById('modalCategory');
        const categoryCustom = document.getElementById('modalCategoryCustom');
        if ([...categorySelect.options].some((opt) => opt.value === item.category)) {
            categorySelect.value = item.category;
            categoryCustom.classList.add('hidden');
        } else {
            categorySelect.value = '__custom__';
            categoryCustom.classList.remove('hidden');
            categoryCustom.value = item.category || '';
        }

        document.getElementById('modalTitleText').value = item.title || item.item || '';

        if (module === 'maintenance') {
            document.getElementById('maintPriority').value = item.priority || 'Medium';
            document.getElementById('maintOfficer').value = item.officer || 'Operations Control Manager';
            document.getElementById('maintStatus').value = item.status || 'Open';
            document.getElementById('maintTargetDate').value = item.targetDate || '';
        } else if (module === 'licences') {
            document.getElementById('licenceAuthority').value = item.authority || '';
            document.getElementById('licenceExpiryDate').value = item.expiryDate || '';
        } else if (module === 'rcsa') {
            const rcsaStatus = document.getElementById('rcsaStatus');
            rcsaStatus.value = item.status || 'N/E';
            window.DashboardRender.applyRcsaStatusColor(rcsaStatus, rcsaStatus.value);
            document.getElementById('rcsaTargetDate').value = item.targetDate || '';
            document.getElementById('rcsaRemarks').value = item.remarks || '';
            document.getElementById('rcsaActionPlan').value = item.actionPlan || '';
        }

        document.getElementById('itemModal').classList.remove('hidden');
    }

    function resolveCategory() {
        const select = document.getElementById('modalCategory');
        const custom = document.getElementById('modalCategoryCustom');
        if (select.value === '__custom__') {
            return (custom.value || '').trim();
        }
        return select.value;
    }

    function collectFormPayload() {
        const module = getSelectedModule();
        const branch = document.getElementById('modalBranch').value;
        const category = resolveCategory();
        const titleText = document.getElementById('modalTitleText').value;
        const payload = { branch, category };

        if (!category) {
            throw new Error('Please select or enter a category.');
        }

        if (module === 'maintenance') {
            payload.title = titleText;
            payload.priority = document.getElementById('maintPriority').value;
            payload.officer = document.getElementById('maintOfficer').value;
            payload.status = document.getElementById('maintStatus').value;
            payload.targetDate = document.getElementById('maintTargetDate').value;
        } else if (module === 'licences') {
            payload.title = titleText;
            payload.authority = document.getElementById('licenceAuthority').value;
            payload.expiryDate = document.getElementById('licenceExpiryDate').value;
        } else {
            payload.item = titleText;
            payload.title = titleText;
            payload.status = document.getElementById('rcsaStatus').value;
            payload.targetDate = document.getElementById('rcsaTargetDate').value;
            payload.remarks = document.getElementById('rcsaRemarks').value;
            payload.actionPlan = document.getElementById('rcsaActionPlan').value;
        }

        return { module, payload, itemId: document.getElementById('modalItemId').value };
    }

    function bindDestinationEvents(getDashboardOptions, getCurrentUserSession) {
        document.querySelectorAll('[data-destination]').forEach((btn) => {
            btn.addEventListener('click', () => {
                setSelectedModule(btn.dataset.destination, getDashboardOptions(), getCurrentUserSession());
            });
        });

        document.getElementById('modalModuleSelect')?.addEventListener('change', (event) => {
            setSelectedModule(event.target.value, getDashboardOptions(), getCurrentUserSession());
        });

        document.getElementById('modalCategory')?.addEventListener('change', (event) => {
            const custom = document.getElementById('modalCategoryCustom');
            if (!custom) return;
            const isCustom = event.target.value === '__custom__';
            custom.classList.toggle('hidden', !isCustom);
            if (isCustom) custom.focus();
        });
    }

    return {
        DESTINATIONS,
        setupFilters,
        populateModalDropdowns,
        toggleModalFields,
        closeModal,
        openAddModal,
        openEditModal,
        collectFormPayload,
        fillSelect,
        setSelectedModule,
        bindDestinationEvents,
        updateDestinationUI,
    };
})();
