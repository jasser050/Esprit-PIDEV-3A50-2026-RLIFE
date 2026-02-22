/**
 * Global Accessibility Loader - Applies to WHOLE WEBSITE
 * Runs on every page, loads from localStorage
 */

(function() {
    'use strict';
    
    function applyAll() {
        const saved = localStorage.getItem('accessibility_settings');
        if (!saved) return;
        
        try {
            const s = JSON.parse(saved);
            
            // Font size
            if (s.fontSize) {
                const sizes = { 'xs': '0.70rem', 'sm': '0.85rem', 'normal': '1rem', 'lg': '1.15rem', 'xl': '1.35rem', '2xl': '1.6rem' };
                document.documentElement.style.fontSize = sizes[s.fontSize] || '1rem';
            }
            
            // Font family
            if (s.fontFamily) {
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
                document.body.style.fontFamily = fonts[s.fontFamily] || fonts['system'];
            }
            
            // Line height - apply to WHOLE WEBSITE
            if (s.lineHeight) {
                applyLineHeight(s.lineHeight);
            }
            
            // Letter spacing - apply to WHOLE WEBSITE
            if (s.letterSpacing) {
                applyLetterSpacing(s.letterSpacing);
            }
            
            // Reduce motion
            if (s.reduceMotion) {
                document.documentElement.classList.add('reduce-motion');
            }
            
            // High contrast
            if (s.highContrast) {
                document.documentElement.classList.add('high-contrast');
            }
            
            // Accent color
            if (s.accentColor) {
                applyAccentColor(s.accentColor);
            }
            
        } catch (e) {}
    }
    
    function applyLineHeight(value) {
        const heights = { 'compact': '1.2', 'normal': '1.6', 'relaxed': '2.0', 'spacious': '2.5' };
        const height = heights[value] || '1.6';
        document.documentElement.style.setProperty('--line-height-base', height);
        
        const old = document.getElementById('global-line-height-css');
        if (old) old.remove();
        
        const style = document.createElement('style');
        style.id = 'global-line-height-css';
        style.textContent = `* { line-height: ${height} !important; }`;
        document.head.appendChild(style);
    }
    
    function applyLetterSpacing(value) {
        const spacings = { 'tight': '-0.05em', 'normal': '0', 'wide': '0.08em', 'wider': '0.15em' };
        const spacing = spacings[value] || '0';
        document.documentElement.style.setProperty('--letter-spacing-base', spacing);
        
        const old = document.getElementById('global-letter-spacing-css');
        if (old) old.remove();
        
        const style = document.createElement('style');
        style.id = 'global-letter-spacing-css';
        style.textContent = `* { letter-spacing: ${spacing} !important; }`;
        document.head.appendChild(style);
    }
    
    function applyAccentColor(color) {
        const old = document.getElementById('global-accent-css');
        if (old) old.remove();
        
        if (color === 'default' || !color) {
            return;
        }
        
        const dark = darken(color, 20);
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
    
    function darken(hex, percent) {
        try {
            const r = Math.max(0, parseInt(hex.slice(1, 3), 16) - Math.round(parseInt(hex.slice(1, 3), 16) * percent / 100));
            const g = Math.max(0, parseInt(hex.slice(3, 5), 16) - Math.round(parseInt(hex.slice(3, 5), 16) * percent / 100));
            const b = Math.max(0, parseInt(hex.slice(5, 7), 16) - Math.round(parseInt(hex.slice(5, 7), 16) * percent / 100));
            return '#' + r.toString(16).padStart(2, '0') + g.toString(16).padStart(2, '0') + b.toString(16).padStart(2, '0');
        } catch (e) { return hex; }
    }
    
    // Run immediately
    applyAll();
    
    // Also run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', applyAll);
    }
})();
