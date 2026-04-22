import { defineStore } from 'pinia';

let toastSeq = 0;

export const useUi = defineStore('portal-ui', {
    state: () => ({
        toasts: [],
        modal: {
            open: false,
            title: '',
            body: '',
            onConfirm: null,
        },
        online: typeof navigator !== 'undefined' ? navigator.onLine : true,
    }),

    actions: {
        pushToast({ severity = 'info', summary = '', detail = '', life = 4000 } = {}) {
            const id = ++toastSeq;
            this.toasts.push({ id, severity, summary, detail, life });
            if (life > 0) {
                setTimeout(() => this.dismissToast(id), life);
            }
            return id;
        },

        dismissToast(id) {
            this.toasts = this.toasts.filter((t) => t.id !== id);
        },

        openModal({ title = '', body = '', onConfirm = null } = {}) {
            this.modal = { open: true, title, body, onConfirm };
        },

        closeModal() {
            this.modal = { open: false, title: '', body: '', onConfirm: null };
        },

        setOnline(flag) {
            this.online = !!flag;
        },
    },
});
