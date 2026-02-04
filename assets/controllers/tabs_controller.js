import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['tab', 'panel'];
    static values = {
        activeIndex: { type: Number, default: 0 }
    };

    connect() {
        this.showTab(this.activeIndexValue);
    }

    select(event) {
        const index = parseInt(event.currentTarget.dataset.tabIndex);
        this.activeIndexValue = index;
        this.showTab(index);
    }

    showTab(index) {
        // Update tabs
        this.tabTargets.forEach((tab, i) => {
            if (i === index) {
                tab.classList.add('tab-active');
                tab.setAttribute('aria-selected', 'true');
            } else {
                tab.classList.remove('tab-active');
                tab.setAttribute('aria-selected', 'false');
            }
        });

        // Update panels
        this.panelTargets.forEach((panel, i) => {
            if (i === index) {
                panel.classList.remove('hidden');
                panel.setAttribute('aria-hidden', 'false');
            } else {
                panel.classList.add('hidden');
                panel.setAttribute('aria-hidden', 'true');
            }
        });
    }
}
