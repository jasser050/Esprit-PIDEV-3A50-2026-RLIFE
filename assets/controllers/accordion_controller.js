import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['item', 'content', 'icon'];
    static values = {
        allowMultiple: { type: Boolean, default: false }
    };

    toggle(event) {
        const button = event.currentTarget;
        const item = button.closest('[data-accordion-target="item"]');
        const content = item.querySelector('[data-accordion-target="content"]');
        const icon = button.querySelector('[data-accordion-target="icon"]');

        const isOpen = content.style.maxHeight && content.style.maxHeight !== '0px';

        // Close other items if not allowing multiple
        if (!this.allowMultipleValue && !isOpen) {
            this.itemTargets.forEach(otherItem => {
                if (otherItem !== item) {
                    const otherContent = otherItem.querySelector('[data-accordion-target="content"]');
                    const otherIcon = otherItem.querySelector('[data-accordion-target="icon"]');
                    this.closeItem(otherContent, otherIcon);
                }
            });
        }

        // Toggle current item
        if (isOpen) {
            this.closeItem(content, icon);
        } else {
            this.openItem(content, icon);
        }
    }

    openItem(content, icon) {
        content.style.maxHeight = content.scrollHeight + 'px';
        content.style.opacity = '1';
        if (icon) {
            icon.style.transform = 'rotate(180deg)';
        }
    }

    closeItem(content, icon) {
        content.style.maxHeight = '0px';
        content.style.opacity = '0';
        if (icon) {
            icon.style.transform = 'rotate(0deg)';
        }
    }
}
