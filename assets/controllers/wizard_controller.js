import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['step', 'indicator', 'prevBtn', 'nextBtn', 'submitBtn'];
    static values = {
        current: { type: Number, default: 0 },
        total: { type: Number, default: 2 }
    };

    connect() {
        // Always reset to step 0 — prevents Turbo Drive cache restoring a stale step
        this.currentValue = 0;
        this.showStep(0);
    }

    next() {
        // Guard against rapid double-clicks
        if (this._transitioning) return;
        if (this.currentValue < this.totalValue - 1) {
            if (this.validateCurrentStep()) {
                this._transitioning = true;
                this.currentValue++;
                this.showStep(this.currentValue);
                setTimeout(() => { this._transitioning = false; }, 400);
            }
        }
    }

    prev() {
        if (this.currentValue > 0) {
            this.currentValue--;
            this.showStep(this.currentValue);
        }
    }

    goToStep(event) {
        const stepIndex = parseInt(event.currentTarget.dataset.step);
        if (stepIndex <= this.currentValue) {
            this.currentValue = stepIndex;
            this.showStep(this.currentValue);
        }
    }

    showStep(index) {
        // Update step visibility
        this.stepTargets.forEach((step, i) => {
            step.classList.toggle('hidden', i !== index);
            if (i === index) {
                step.classList.add('animate-fade-in');
            }
        });

        // Update indicators
        this.indicatorTargets.forEach((indicator, i) => {
            const circle = indicator.querySelector('.step-circle');
            const line = indicator.querySelector('.step-line');

            if (i < index) {
                // Completed
                circle?.classList.add('bg-primary-600', 'border-primary-600', 'text-white');
                circle?.classList.remove('bg-white', 'dark:bg-slate-800', 'border-slate-300', 'dark:border-slate-600', 'text-slate-500');
                line?.classList.add('bg-primary-600');
                line?.classList.remove('bg-slate-200', 'dark:bg-slate-700');
            } else if (i === index) {
                // Current
                circle?.classList.add('bg-primary-600', 'border-primary-600', 'text-white');
                circle?.classList.remove('bg-white', 'dark:bg-slate-800', 'border-slate-300', 'dark:border-slate-600', 'text-slate-500');
            } else {
                // Upcoming
                circle?.classList.remove('bg-primary-600', 'border-primary-600', 'text-white');
                circle?.classList.add('bg-white', 'dark:bg-slate-800', 'border-slate-300', 'dark:border-slate-600', 'text-slate-500');
                line?.classList.remove('bg-primary-600');
                line?.classList.add('bg-slate-200', 'dark:bg-slate-700');
            }
        });

        // Update button visibility
        if (this.hasPrevBtnTarget) {
            this.prevBtnTarget.classList.toggle('hidden', index === 0);
        }
        if (this.hasNextBtnTarget) {
            this.nextBtnTarget.classList.toggle('hidden', index === this.totalValue - 1);
        }
        if (this.hasSubmitBtnTarget) {
            this.submitBtnTarget.classList.toggle('hidden', index !== this.totalValue - 1);
        }
    }

    validateCurrentStep() {
        const currentStep = this.stepTargets[this.currentValue];
        const inputs = currentStep.querySelectorAll('input[required], select[required], textarea[required]');
        let isValid = true;

        inputs.forEach(input => {
            // For checkboxes required means it must be checked, not just have a value
            const empty = input.type === 'checkbox' ? !input.checked : !input.value.trim();
            if (empty) {
                isValid = false;
                input.classList.add('border-danger-500', 'focus:ring-danger-500');

                // Add error message if not exists
                let errorEl = input.parentElement.querySelector('.error-message');
                if (!errorEl) {
                    errorEl = document.createElement('p');
                    errorEl.className = 'error-message text-sm text-danger-600 mt-1';
                    errorEl.textContent = input.type === 'checkbox' ? 'You must accept to continue' : 'This field is required';
                    input.parentElement.appendChild(errorEl);
                }
            } else {
                input.classList.remove('border-danger-500', 'focus:ring-danger-500');
                const errorEl = input.parentElement.querySelector('.error-message');
                if (errorEl) errorEl.remove();
            }
        });

        // Check password match if on password step
        const password = currentStep.querySelector('input[name="password"]');
        const confirmPassword = currentStep.querySelector('input[name="confirm_password"]');
        if (password && confirmPassword && password.value !== confirmPassword.value) {
            isValid = false;
            confirmPassword.classList.add('border-danger-500');
            let errorEl = confirmPassword.parentElement.querySelector('.error-message');
            if (!errorEl) {
                errorEl = document.createElement('p');
                errorEl.className = 'error-message text-sm text-danger-600 mt-1';
                errorEl.textContent = 'Passwords do not match';
                confirmPassword.parentElement.appendChild(errorEl);
            }
        }

        return isValid;
    }
}
