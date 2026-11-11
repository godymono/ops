<div id="reportModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center hidden p-4 overflow-y-auto">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-4xl shadow-xl overflow-hidden my-8">
        <div class="bg-slate-50 dark:bg-slate-800/60 px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white" id="reportModalTitle">Report</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400" id="reportModalMeta"></p>
            </div>
            <button type="button" data-action="close-report-modal" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>
        <div class="p-4 overflow-x-auto custom-scrollbar max-h-[70vh]">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-600 dark:text-slate-300 uppercase tracking-wider font-semibold border-b border-slate-200 dark:border-slate-800 sticky top-0">
                    <tr>
                        <th class="py-2.5 px-3">Category</th>
                        <th class="py-2.5 px-3 min-w-[220px]">Item</th>
                        <th class="py-2.5 px-3 text-center">Status</th>
                        <th class="py-2.5 px-3">Remarks</th>
                        <th class="py-2.5 px-3">Action</th>
                        <th class="py-2.5 px-3">Target</th>
                    </tr>
                </thead>
                <tbody id="reportModalBody" class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300"></tbody>
            </table>
        </div>
    </div>
</div>
