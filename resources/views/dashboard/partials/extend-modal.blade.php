<div id="extendModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" data-action="close-extend-modal"></div>
    <div class="relative bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Extend submission days</h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Head Office can give more time to one branch or all branches.</p>
            </div>
            <button type="button" data-action="close-extend-modal" class="text-slate-400 hover:text-slate-700 dark:hover:text-slate-200">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="extendForm" class="p-5 space-y-4">
            <div>
                <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 mb-1">Scope</label>
                <select id="extendScope" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 text-xs">
                    <option value="all">All branches</option>
                    <option value="branch">One branch</option>
                </select>
            </div>
            <div id="extendBranchWrap" class="hidden">
                <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 mb-1">Branch</label>
                <select id="extendBranch" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 text-xs"></select>
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 mb-1">Extra days</label>
                <input type="number" id="extendDays" min="1" max="90" value="3"
                    class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 text-xs" required>
                <p class="text-[10px] text-slate-400 mt-1">Adds days from the current deadline (or today if already passed).</p>
            </div>
            <div class="flex items-center justify-end gap-2 pt-1">
                <button type="button" data-action="close-extend-modal" class="px-3 py-1.5 rounded-lg text-xs font-semibold border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300">Cancel</button>
                <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-sky-700 hover:bg-sky-800 text-white">Extend</button>
            </div>
        </form>
    </div>
</div>
