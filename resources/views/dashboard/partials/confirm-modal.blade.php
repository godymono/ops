<div id="confirmModal" class="hidden fixed inset-0 z-[80] flex items-center justify-center p-4" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-950/55 backdrop-blur-[2px] transition-opacity" data-confirm-dismiss></div>
    <div role="dialog" aria-modal="true" aria-labelledby="confirmTitle" aria-describedby="confirmMessage"
        class="relative w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-2xl shadow-slate-900/20 transform scale-95 opacity-0 transition-all duration-200"
        id="confirmDialog">
        <div class="p-6 sm:p-7">
            <div class="flex items-start gap-4">
                <div id="confirmIconWrap" class="shrink-0 h-12 w-12 rounded-2xl flex items-center justify-center bg-indigo-50 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300">
                    <i id="confirmIcon" class="fa-solid fa-circle-question text-lg"></i>
                </div>
                <div class="min-w-0 flex-1 pt-0.5">
                    <h3 id="confirmTitle" class="text-base font-bold text-slate-900 dark:text-white">Confirm</h3>
                    <p id="confirmMessage" class="mt-1.5 text-sm text-slate-500 dark:text-slate-400 leading-relaxed"></p>
                </div>
            </div>

            <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                <button type="button" id="confirmCancelBtn"
                    class="px-4 py-2.5 rounded-xl text-xs font-semibold border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                    Cancel
                </button>
                <button type="button" id="confirmOkBtn"
                    class="px-4 py-2.5 rounded-xl text-xs font-semibold text-white bg-indigo-700 hover:bg-indigo-800 shadow-sm transition flex items-center justify-center gap-2">
                    <i id="confirmOkIcon" class="fa-solid fa-check"></i>
                    <span id="confirmOkLabel">Confirm</span>
                </button>
            </div>
        </div>
    </div>
</div>
