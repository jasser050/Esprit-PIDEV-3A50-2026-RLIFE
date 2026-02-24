import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        fontSize: String,
        fontFamily: String,
        accentColor: String,
        reduceMotion: Boolean,
        highContrast: Boolean,
        lineHeight: String,
        letterSpacing: String,
        theme: String,
        saveUrl: String
    };

    static targets = [
        'fontSizeSlider',
        'fontSizePreview',
        'fontFamilySelect',
        'accentColorInput',
        'reduceMotionToggle',
        'highContrastToggle',
        'lineHeightSelect',
        'letterSpacingSelect',
        'themeButtons'
    ];

    connect() {
        this.loadFromLocalStorage();
        this.applyAllSettings();
    }

    loadFromLocalStorage() {
        const saved = localStorage.getItem('accessibility_settings');
        if (saved) {
            try {
                const s = JSON.parse(saved);
                this.fontSizeValue = s.fontSize || 'normal';
                this.fontFamilyValue = s.fontFamily || 'system';
                this.accentColorValue = s.accentColor || 'default';
                this.reduceMotionValue = s.reduceMotion || false;
                this.highContrastValue = s.highContrast || false;
                this.lineHeightValue = s.lineHeight || 'normal';
                this.letterSpacingValue = s.letterSpacing || 'normal';
                this.themeValue = s.theme || 'auto';
            } catch (e) {}
        }
    }

    applyAllSettings() {
        this.applyFontSize();
        this.applyFontFamily();
        this.applyAccentColor();
        this.applyReduceMotion();
        this.applyHighContrast();
        this.applyLineHeight();
        this.applyLetterSpacing();
        this.applyTheme();
    }

    changeFontSize(event) {
        const sizeMap = ['xs', 'sm', 'normal', 'lg', 'xl', '2xl'];
        this.fontSizeValue = sizeMap[parseInt(event.target.value) - 1] || 'normal';
        this.applyFontSize();
        this.saveAll();
        if (this.hasFontSizePreviewTarget) {
            this.fontSizePreviewTarget.textContent = this.fontSizeValue.toUpperCase();
        }
    }

    applyFontSize() {
        const sizes = { 'xs': '0.70rem', 'sm': '0.85rem', 'normal': '1rem', 'lg': '1.15rem', 'xl': '1.35rem', '2xl': '1.6rem' };
        const size = sizes[this.fontSizeValue] || '1rem';
        document.documentElement.style.fontSize = size;
        document.documentElement.style.setProperty('--font-size-base', size);
    }

    changeFontFamily(event) {
        this.fontFamilyValue = event.target.value;
        this.applyFontFamily();
        this.saveAll();
    }

    applyFontFamily() {
        const fonts = {
            'system': 'system-ui, -apple-system, sans-serif',
            'roboto': 'Roboto, sans-serif',
            'open-sans': 'Open Sans, sans-serif',
            'lato': 'Lato, sans-serif',
            'montserrat': 'Montserrat, sans-serif',
            'arial': 'Arial, sans-serif',
            'georgia': 'Georgia, serif',
            'opendyslexic': 'OpenDyslexic, sans-serif'
        };
        const font = fonts[this.fontFamilyValue] || fonts['system'];
        document.documentElement.style.setProperty('--font-family-base', font);
        document.body.style.fontFamily = font;
        if (['roboto', 'open-sans', 'lato', 'montserrat'].includes(this.fontFamilyValue)) {
            this.loadGoogleFont(this.fontFamilyValue);
        }
    }

    loadGoogleFont(name) {
        if (!document.querySelector(`link[href*="${name}"]`)) {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = `https://fonts.googleapis.com/css2?family=${name.replace('-', '+')}:wght@400;500;600;700&display=swap`;
            document.head.appendChild(link);
        }
    }

    changeAccentColor(event) {
        this.accentColorValue = event.target.value;
        this.applyAccentColor();
        this.saveAll();
    }

    resetAccentColor() {
        this.accentColorValue = 'default';
        this.applyAccentColor();
        this.saveAll();
    }

    applyAccentColor() {
        const old = document.getElementById('global-accent-css');
        if (old) old.remove();
        
        if (this.accentColorValue === 'default' || !this.accentColorValue || this.accentColorValue === '') {
            localStorage.setItem('accent_color', 'default');
            if (this.hasAccentColorInputTarget) {
                this.accentColorInputTarget.value = '#6366f1';
            }
            return;
        }
        
        const color = this.accentColorValue;
        localStorage.setItem('accent_color', color);
        
        const dark = this.darken(color, 20);
        const style = document.createElement('style');
        style.id = 'global-accent-css';
        style.textContent = `
            :root { --accent: ${color}; --accent-dark: ${dark}; }
            [style*="linear-gradient"], button[type="submit"], .btn-primary { background: linear-gradient(135deg, ${color}, ${dark}) !important; }
            [class*="text-primary"], [class*="text-indigo"], a:hover { color: ${color} !important; }
            [class*="border-primary"], [class*="border-indigo"], :focus { border-color: ${color} !important; --tw-ring-color: ${color} !important; }
            input[type="range"] { accent-color: ${color} !important; }
            input[type="checkbox"]:checked, input[type="radio"]:checked { background-color: ${color} !important; border-color: ${color} !important; }
        `;
        document.head.appendChild(style);
    }

    darken(hex, percent) {
        try {
            const r = Math.max(0, parseInt(hex.slice(1, 3), 16) - Math.round(parseInt(hex.slice(1, 3), 16) * percent / 100));
            const g = Math.max(0, parseInt(hex.slice(3, 5), 16) - Math.round(parseInt(hex.slice(3, 5), 16) * percent / 100));
            const b = Math.max(0, parseInt(hex.slice(5, 7), 16) - Math.round(parseInt(hex.slice(5, 7), 16) * percent / 100));
            return '#' + r.toString(16).padStart(2, '0') + g.toString(16).padStart(2, '0') + b.toString(16).padStart(2, '0');
        } catch (e) { return hex; }
    }

    toggleReduceMotion(event) {
        this.reduceMotionValue = event.target.checked;
        this.applyReduceMotion();
        this.saveAll();
    }

    applyReduceMotion() {
        document.documentElement.classList.toggle('reduce-motion', this.reduceMotionValue);
        localStorage.setItem('reduce_motion', this.reduceMotionValue);
    }

    toggleHighContrast(event) {
        this.highContrastValue = event.target.checked;
        this.applyHighContrast();
        this.saveAll();
    }

    applyHighContrast() {
        document.documentElement.classList.toggle('high-contrast', this.highContrastValue);
        localStorage.setItem('high_contrast', this.highContrastValue);
    }

    changeLineHeight(event) {
        this.lineHeightValue = event.target.value;
        this.applyLineHeight();
        this.saveAll();
    }

    applyLineHeight() {
        const heights = { 'compact': '1.2', 'normal': '1.6', 'relaxed': '2.0', 'spacious': '2.5' };
        const height = heights[this.lineHeightValue] || '1.6';
        
        // Apply to WHOLE WEBSITE - root element
        document.documentElement.style.setProperty('--line-height-base', height);
        document.documentElement.style.lineHeight = height;
        
        // Also apply via style for all elements
        const old = document.getElementById('global-line-height-css');
        if (old) old.remove();
        
        const style = document.createElement('style');
        style.id = 'global-line-height-css';
        style.textContent = `
            * { line-height: ${height} !important; }
        `;
        document.head.appendChild(style);
    }

    changeLetterSpacing(event) {
        this.letterSpacingValue = event.target.value;
        this.applyLetterSpacing();
        this.saveAll();
    }

    applyLetterSpacing() {
        const spacings = { 'tight': '-0.05em', 'normal': '0', 'wide': '0.08em', 'wider': '0.15em' };
        const spacing = spacings[this.letterSpacingValue] || '0';
        
        // Apply to WHOLE WEBSITE - root element
        document.documentElement.style.setProperty('--letter-spacing-base', spacing);
        document.documentElement.style.letterSpacing = spacing;
        
        // Also apply via style for all elements
        const old = document.getElementById('global-letter-spacing-css');
        if (old) old.remove();
        
        const style = document.createElement('style');
        style.id = 'global-letter-spacing-css';
        style.textContent = `
            * { letter-spacing: ${spacing} !important; }
        `;
        document.head.appendChild(style);
    }

    setTheme(event) {
        this.themeValue = event.currentTarget.dataset.theme;
        this.applyTheme();
        this.updateThemeButtons();
        this.saveAll();
    }

    applyTheme() {
        localStorage.setItem('theme', this.themeValue);
        if (this.themeValue === 'auto') {
            document.documentElement.classList.toggle('dark', window.matchMedia('(prefers-color-scheme: dark)').matches);
        } else {
            document.documentElement.classList.toggle('dark', this.themeValue === 'dark');
        }
    }

    updateThemeButtons() {
        if (this.hasThemeButtonsTarget) {
            this.themeButtonsTarget.querySelectorAll('button').forEach(btn => {
                btn.classList.toggle('ring-2', btn.dataset.theme === this.themeValue);
            });
        }
    }

    saveAll() {
        localStorage.setItem('accessibility_settings', JSON.stringify({
            fontSize: this.fontSizeValue,
            fontFamily: this.fontFamilyValue,
            accentColor: this.accentColorValue,
            reduceMotion: this.reduceMotionValue,
            highContrast: this.highContrastValue,
            lineHeight: this.lineHeightValue,
            letterSpacing: this.letterSpacingValue,
            theme: this.themeValue
        }));
        if (this.saveUrlValue) {
            fetch(this.saveUrlValue, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    font_size: this.fontSizeValue,
                    font_family: this.fontFamilyValue,
                    accent_color: this.accentColorValue,
                    reduce_motion: this.reduceMotionValue,
                    high_contrast: this.highContrastValue,
                    line_height: this.lineHeightValue,
                    letter_spacing: this.letterSpacingValue,
                    theme_preference: this.themeValue
                })
            }).catch(() => {});
        }
    }
}
