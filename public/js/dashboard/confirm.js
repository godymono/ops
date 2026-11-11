window.DashboardConfirm = (function () {
    const VARIANTS = {
        primary: {
            wrap: 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300',
            button: 'bg-indigo-700 hover:bg-indigo-800',
            icon: 'fa-solid fa-circle-question',
            okIcon: 'fa-solid fa-check',
        },
        success: {
            wrap: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300',
            button: 'bg-emerald-700 hover:bg-emerald-800',
            icon: 'fa-solid fa-paper-plane',
            okIcon: 'fa-solid fa-paper-plane',
        },
        warning: {
            wrap: 'bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300',
            button: 'bg-amber-600 hover:bg-amber-700',
            icon: 'fa-solid fa-unlock',
            okIcon: 'fa-solid fa-unlock',
        },
        danger: {
            wrap: 'bg-rose-50 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300',
            button: 'bg-rose-600 hover:bg-rose-700',
            icon: 'fa-solid fa-triangle-exclamation',
            okIcon: 'fa-solid fa-trash',
        },
    };

    let resolver = null;
    let bound = false;

    function els() {
        return {
            modal: document.getElementById('confirmModal'),
            dialog: document.getElementById('confirmDialog'),
            title: document.getElementById('confirmTitle'),
            message: document.getElementById('confirmMessage'),
            iconWrap: document.getElementById('confirmIconWrap'),
            icon: document.getElementById('confirmIcon'),
            okBtn: document.getElementById('confirmOkBtn'),
            okLabel: document.getElementById('confirmOkLabel'),
            okIcon: document.getElementById('confirmOkIcon'),
            cancelBtn: document.getElementById('confirmCancelBtn'),
        };
    }

    function bindOnce() {
        if (bound) return;
        const { modal, okBtn, cancelBtn } = els();
        if (!modal) return;
        bound = true;

        okBtn?.addEventListener('click', () => close(true));
        cancelBtn?.addEventListener('click', () => close(false));
        modal.querySelectorAll('[data-confirm-dismiss]').forEach((node) => {
            node.addEventListener('click', () => close(false));
        });
        document.addEventListener('keydown', (event) => {
            if (!modal || modal.classList.contains('hidden')) return;
            if (event.key === 'Escape') {
                event.preventDefault();
                close(false);
            }
            if (event.key === 'Enter' && !event.shiftKey) {
                const tag = (event.target?.tagName || '').toLowerCase();
                if (tag === 'textarea' || tag === 'button') return;
                event.preventDefault();
                close(true);
            }
        });
    }

    function open(options = {}) {
        bindOnce();
        const {
            title = 'Confirm',
            message = 'Are you sure you want to continue?',
            confirmText = 'Confirm',
            cancelText = 'Cancel',
            variant = 'primary',
        } = options;

        const ui = els();
        if (!ui.modal) {
            return Promise.resolve(window.confirm(message));
        }

        const theme = VARIANTS[variant] || VARIANTS.primary;

        ui.title.textContent = title;
        ui.message.textContent = message;
        ui.okLabel.textContent = confirmText;
        ui.cancelBtn.textContent = cancelText;

        ui.iconWrap.className = `shrink-0 h-12 w-12 rounded-2xl flex items-center justify-center ${theme.wrap}`;
        ui.icon.className = `${theme.icon} text-lg`;
        ui.okBtn.className = `px-4 py-2.5 rounded-xl text-xs font-semibold text-white shadow-sm transition flex items-center justify-center gap-2 ${theme.button}`;
        ui.okIcon.className = theme.okIcon;

        ui.modal.classList.remove('hidden');
        ui.modal.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(() => {
            ui.dialog?.classList.remove('scale-95', 'opacity-0');
            ui.dialog?.classList.add('scale-100', 'opacity-100');
        });
        setTimeout(() => ui.okBtn?.focus(), 30);

        return new Promise((resolve) => {
            resolver = resolve;
        });
    }

    function close(result) {
        const ui = els();
        if (!ui.modal || ui.modal.classList.contains('hidden')) return;

        ui.dialog?.classList.add('scale-95', 'opacity-0');
        ui.dialog?.classList.remove('scale-100', 'opacity-100');
        setTimeout(() => {
            ui.modal.classList.add('hidden');
            ui.modal.setAttribute('aria-hidden', 'true');
        }, 160);

        if (resolver) {
            const done = resolver;
            resolver = null;
            done(!!result);
        }
    }

    function ask(options) {
        return open(typeof options === 'string' ? { message: options } : options);
    }

    return {
        ask,
        open,
        close,
        submit: (message, title = 'Submit report') => ask({
            title,
            message,
            confirmText: 'Submit',
            variant: 'success',
        }),
        unlock: (message, title = 'Unlock report') => ask({
            title,
            message,
            confirmText: 'Unlock & Edit',
            variant: 'warning',
        }),
        danger: (message, title = 'Please confirm') => ask({
            title,
            message,
            confirmText: 'Delete',
            variant: 'danger',
        }),
    };
})();
