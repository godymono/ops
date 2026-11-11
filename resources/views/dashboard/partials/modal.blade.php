<div id="itemModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center hidden p-4 overflow-y-auto">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-2xl shadow-xl overflow-hidden my-8 transition-all">
        <div class="bg-slate-50 dark:bg-slate-800/60 px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2" id="modalTitle">
                <i class="fa-solid fa-plus-circle text-azania-navy dark:text-blue-400"></i> Add Record
            </h3>
            <button type="button" data-action="close-modal" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>

        <form id="unifiedForm" class="p-6 space-y-4">
            <input type="hidden" id="modalItemId" value="">
            <input type="hidden" id="modalModule" value="maintenance">

            <div id="destinationPickerWrap" class="space-y-2">
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Destination *</label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2" id="destinationPicker">
                    <button type="button" data-destination="maintenance" class="destination-card text-left rounded-xl border-2 border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-3 transition hover:border-amber-400">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400">
                                <i class="fa-solid fa-screwdriver-wrench text-xs"></i>
                            </span>
                            <span class="text-xs font-bold text-slate-900 dark:text-white">Maintenance</span>
                        </div>
                    </button>

                    <button type="button" data-destination="licences" class="destination-card text-left rounded-xl border-2 border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-3 transition hover:border-blue-400">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400">
                                <i class="fa-solid fa-award text-xs"></i>
                            </span>
                            <span class="text-xs font-bold text-slate-900 dark:text-white">Licences</span>
                        </div>
                    </button>

                    <button type="button" data-destination="rcsa" class="destination-card text-left rounded-xl border-2 border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-3 transition hover:border-emerald-400">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400">
                                <i class="fa-solid fa-clipboard-check text-xs"></i>
                            </span>
                            <span class="text-xs font-bold text-slate-900 dark:text-white">RCSA</span>
                        </div>
                    </button>
                </div>

                <select id="modalModuleSelect" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-800 dark:text-slate-100 focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:border-azania-navy font-semibold">
                    <option value="maintenance">Maintenance</option>
                    <option value="licences">Licences</option>
                    <option value="rcsa">RCSA</option>
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Branch *</label>
                    <select id="modalBranch" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-800 dark:text-slate-100 focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:border-azania-navy"></select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1" id="modalCategoryLabel">Category *</label>
                    <select id="modalCategory" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-800 dark:text-slate-100 focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:border-azania-navy"></select>
                    <input type="text" id="modalCategoryCustom" placeholder="New category..." class="mt-2 w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-azania-navy hidden">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1" id="modalTitleLabel">Title *</label>
                <textarea id="modalTitleText" rows="2" required placeholder="Enter details..." class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg p-2.5 text-xs text-slate-800 dark:text-slate-100 focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:border-azania-navy"></textarea>
            </div>

            <div id="fieldsMaintenance" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Priority</label>
                        <select id="maintPriority" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-800 dark:text-slate-100">
                            <option value="High">High</option>
                            <option value="Medium" selected>Medium</option>
                            <option value="Low">Low</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Officer *</label>
                        <select id="maintOfficer" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-800 dark:text-slate-100 font-semibold focus:outline-none focus:border-azania-navy"></select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Status</label>
                        <select id="maintStatus" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-800 dark:text-slate-100">
                            <option value="Open">Open</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Resolved">Resolved</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Target Date</label>
                    <input type="date" id="maintTargetDate" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-800 dark:text-slate-100">
                </div>
            </div>

            <div id="fieldsLicences" class="space-y-4 hidden">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Authority</label>
                        <input type="text" id="licenceAuthority" placeholder="e.g. OSHA, BRELA" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-800 dark:text-slate-100">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Expiry Date *</label>
                        <input type="date" id="licenceExpiryDate" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-800 dark:text-slate-100">
                    </div>
                </div>
            </div>

            <div id="fieldsRcsa" class="space-y-4 hidden">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Status</label>
                        <select id="rcsaStatus" class="rcsa-status status-neutral w-full rounded-lg px-3 py-2 text-xs">
                            <option value="N/E">N/E</option>
                            <option value="YES">YES</option>
                            <option value="NO">NO</option>
                            <option value="N/A">N/A</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Target Date</label>
                        <input type="date" id="rcsaTargetDate" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-800 dark:text-slate-100">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Remarks</label>
                    <textarea id="rcsaRemarks" rows="2" placeholder="Remarks..." class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg p-2.5 text-xs text-slate-800 dark:text-slate-100"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Action Plan</label>
                    <textarea id="rcsaActionPlan" rows="2" placeholder="Action plan..." class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg p-2.5 text-xs text-slate-800 dark:text-slate-100"></textarea>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex justify-end space-x-2">
                <button type="button" data-action="close-modal" class="px-4 py-2 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 text-xs font-semibold transition">Cancel</button>
                <button type="submit" id="modalSubmitBtn" class="px-5 py-2 rounded-lg bg-azania-navy hover:bg-blue-900 dark:bg-blue-600 dark:hover:bg-blue-700 text-white text-xs font-bold shadow-sm transition">Save</button>
            </div>
        </form>
    </div>
</div>
