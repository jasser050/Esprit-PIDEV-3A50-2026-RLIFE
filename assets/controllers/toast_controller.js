import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['toast'];

    connect() {
        // Auto-dismiss toasts after 5 seconds
        this.toastTargets.forEach(toast => {
            setTimeout(() => {
                this.dismissToast(toast);
            }, 5000);
        });
    }

    dismiss(event) {
        this.dismissToast(event.currentTarget);
    }

    dismissToast(toast) {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        toast.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
        
        setTimeout(() => {
            toast.remove();
        }, 300);
    }

    // Method to show a toast programmatically
    show(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.dataset.toastTarget = 'toast';
        toast.dataset.action = 'click->toast#dismiss';
        
        const icons = {
            success: '<svg class="w-5 h-5 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>',
            error: '<svg class="w-5 h-5 text-danger-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>',
            warning: '<svg class="w-5 h-5 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>',
            info: '<svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
        };
        
        toast.innerHTML = `
            <div class="flex items-center gap-3">
                ${icons[type] || icons.info}
                <span>${message}</span>
            </div>
        `;
        
        this.element.appendChild(toast);
        
        setTimeout(() => {
            this.dismissToast(toast);
        }, 5000);
    }
}
