import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'toggle', 'strength', 'requirements'];

    connect() {
        if (this.hasInputTarget) {
            this.inputTarget.addEventListener('input', () => this.checkStrength());
        }
    }

    toggleVisibility() {
        const input = this.inputTarget;
        const isPassword = input.type === 'password';

        input.type = isPassword ? 'text' : 'password';

        // Update icon
        const showIcon = this.toggleTarget.querySelector('.icon-show');
        const hideIcon = this.toggleTarget.querySelector('.icon-hide');

        if (showIcon && hideIcon) {
            showIcon.classList.toggle('hidden', !isPassword);
            hideIcon.classList.toggle('hidden', isPassword);
        }
    }

    checkStrength() {
        const password = this.inputTarget.value;
        let strength = 0;

        // Check length
        if (password.length >= 8) strength++;
        if (password.length >= 12) strength++;

        // Check for lowercase
        if (/[a-z]/.test(password)) strength++;

        // Check for uppercase
        if (/[A-Z]/.test(password)) strength++;

        // Check for numbers
        if (/[0-9]/.test(password)) strength++;

        // Check for special chars
        if (/[^A-Za-z0-9]/.test(password)) strength++;

        // Update strength indicator
        if (this.hasStrengthTarget) {
            const bar = this.strengthTarget;
            const percent = (strength / 6) * 100;
            bar.style.width = `${percent}%`;

            // Update color
            bar.classList.remove('bg-danger-500', 'bg-warning-500', 'bg-success-500');
            if (strength <= 2) {
                bar.classList.add('bg-danger-500');
            } else if (strength <= 4) {
                bar.classList.add('bg-warning-500');
            } else {
                bar.classList.add('bg-success-500');
            }
        }

        // Update requirements
        if (this.hasRequirementsTarget) {
            const requirements = this.requirementsTarget.querySelectorAll('[data-requirement]');
            requirements.forEach(req => {
                const type = req.dataset.requirement;
                let met = false;

                switch(type) {
                    case 'length': met = password.length >= 8; break;
                    case 'lowercase': met = /[a-z]/.test(password); break;
                    case 'uppercase': met = /[A-Z]/.test(password); break;
                    case 'number': met = /[0-9]/.test(password); break;
                    case 'special': met = /[^A-Za-z0-9]/.test(password); break;
                }

                const icon = req.querySelector('.req-icon');
                if (met) {
                    req.classList.add('text-success-600', 'dark:text-success-400');
                    req.classList.remove('text-slate-400', 'dark:text-slate-500');
                    if (icon) icon.dataset.lucide = 'check-circle';
                } else {
                    req.classList.remove('text-success-600', 'dark:text-success-400');
                    req.classList.add('text-slate-400', 'dark:text-slate-500');
                    if (icon) icon.dataset.lucide = 'circle';
                }
            });

            // Reinitialize icons
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }
    }
}
