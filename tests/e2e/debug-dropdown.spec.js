// @ts-check
const { test, expect } = require('@playwright/test');

test.describe('Debug JavaScript Initialization Issues', () => {
    
    test('Debug dropdown click behavior', async ({ page }) => {
        // Collect all console messages
        const consoleLogs = [];
        page.on('console', msg => {
            consoleLogs.push({ type: msg.type(), text: msg.text() });
        });

        // Collect all errors
        const errors = [];
        page.on('pageerror', err => {
            errors.push(err.message);
        });

        // Go to login page
        await page.goto('/login');
        
        // Login - form uses _username and _password
        await page.fill('input[name="_username"]', 'jasserbalti555@gmail.com');
        await page.fill('input[name="_password"]', 'jasserQ0*');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);

        console.log('\n=== CONSOLE LOGS AFTER PAGE LOAD ===');
        consoleLogs.forEach(log => console.log(`[${log.type}] ${log.text}`));

        // Find dropdown button
        const dropdownButton = page.locator('[data-controller="dropdown"] button[data-action*="dropdown#toggle"]').first();
        const buttonExists = await dropdownButton.count();
        console.log(`\n=== DROPDOWN BUTTON EXISTS: ${buttonExists > 0} ===`);

        if (buttonExists > 0) {
            const dropdownContainer = page.locator('[data-controller="dropdown"]').first();
            const menu = dropdownContainer.locator('[data-dropdown-target="menu"]');

            // Check initial state
            const initialHidden = await menu.evaluate(el => el.classList.contains('hidden'));
            console.log(`\n=== INITIAL MENU STATE: hidden=${initialHidden} ===`);

            // Clear logs before click
            consoleLogs.length = 0;

            // CLICK
            console.log('\n=== CLICKING DROPDOWN BUTTON ===');
            await dropdownButton.click();
            await page.waitForTimeout(300);

            // Check state after click
            const afterClickHidden = await menu.evaluate(el => el.classList.contains('hidden'));
            console.log(`\n=== AFTER CLICK MENU STATE: hidden=${afterClickHidden} ===`);

            console.log('\n=== CONSOLE LOGS FROM CLICK ===');
            consoleLogs.forEach(log => console.log(`[${log.type}] ${log.text}`));

            // The test: menu should NOT be hidden after click
            expect(afterClickHidden).toBe(false);
        }

        if (errors.length > 0) {
            console.log('\n=== PAGE ERRORS ===');
            errors.forEach(err => console.log(err));
        }
    });

    test('Trace classList changes on dropdown menu', async ({ page }) => {
        const consoleLogs = [];
        page.on('console', msg => {
            consoleLogs.push(msg.text());
        });

        await page.goto('/login');
        await page.fill('input[name="_username"]', 'jasserbalti555@gmail.com');
        await page.fill('input[name="_password"]', 'jasserQ0*');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);

        // Inject tracing to intercept all classList modifications
        await page.evaluate(() => {
            const menu = document.querySelector('[data-dropdown-target="menu"]');
            if (!menu) {
                console.log('[TRACE] Menu not found!');
                return;
            }

            const originalToggle = DOMTokenList.prototype.toggle;
            const originalAdd = DOMTokenList.prototype.add;
            const originalRemove = DOMTokenList.prototype.remove;

            DOMTokenList.prototype.toggle = function(token) {
                if (this === menu.classList && token === 'hidden') {
                    console.log('[TRACE] toggle("hidden") called');
                    console.log('[TRACE] Current hidden state:', this.contains('hidden'));
                    const stack = new Error().stack.split('\n').slice(1, 5).join(' <- ');
                    console.log('[TRACE] Stack:', stack);
                }
                return originalToggle.call(this, token);
            };

            DOMTokenList.prototype.add = function(...tokens) {
                if (this === menu.classList && tokens.includes('hidden')) {
                    console.log('[TRACE] add("hidden") called');
                    const stack = new Error().stack.split('\n').slice(1, 5).join(' <- ');
                    console.log('[TRACE] Stack:', stack);
                }
                return originalAdd.call(this, ...tokens);
            };

            DOMTokenList.prototype.remove = function(...tokens) {
                if (this === menu.classList && tokens.includes('hidden')) {
                    console.log('[TRACE] remove("hidden") called');
                    const stack = new Error().stack.split('\n').slice(1, 5).join(' <- ');
                    console.log('[TRACE] Stack:', stack);
                }
                return originalRemove.call(this, ...tokens);
            };

            console.log('[TRACE] Tracing installed');
        });

        consoleLogs.length = 0;

        // Click dropdown
        const dropdownButton = page.locator('[data-action*="dropdown#toggle"]').first();
        await dropdownButton.click();
        await page.waitForTimeout(500);

        console.log('\n=== TRACE RESULTS ===');
        consoleLogs.filter(log => log.includes('[TRACE]')).forEach(log => console.log(log));

        // Count how many times toggle was called
        const toggleCount = consoleLogs.filter(log => log.includes('toggle("hidden")')).length;
        console.log(`\n=== TOGGLE CALLED ${toggleCount} TIME(S) ===`);
        
        // If toggle is called more than once, that's the bug
        expect(toggleCount).toBe(1);
    });

    test('Check Stimulus controller connection', async ({ page }) => {
        await page.goto('/login');
        await page.fill('input[name="_username"]', 'jasserbalti555@gmail.com');
        await page.fill('input[name="_password"]', 'jasserQ0*');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);

        const stimulusInfo = await page.evaluate(() => {
            const results = {
                stimulusAppExists: !!window.Stimulus,
                dropdownControllers: [],
                sidebarControllers: []
            };

            // Check dropdown controllers
            document.querySelectorAll('[data-controller="dropdown"]').forEach((el, i) => {
                const controller = window.Stimulus?.getControllerForElementAndIdentifier(el, 'dropdown');
                results.dropdownControllers.push({
                    index: i,
                    hasController: !!controller,
                    controllerClass: controller?.constructor?.name
                });
            });

            // Check sidebar controllers  
            document.querySelectorAll('[data-controller="sidebar"]').forEach((el, i) => {
                const controller = window.Stimulus?.getControllerForElementAndIdentifier(el, 'sidebar');
                results.sidebarControllers.push({
                    index: i,
                    hasController: !!controller,
                    controllerClass: controller?.constructor?.name
                });
            });

            return results;
        });

        console.log('\n=== STIMULUS INFO ===');
        console.log(JSON.stringify(stimulusInfo, null, 2));

        // Stimulus should be loaded
        expect(stimulusInfo.stimulusAppExists).toBe(true);
    });

    test('Check for multiple event listeners', async ({ page }) => {
        const consoleLogs = [];
        page.on('console', msg => consoleLogs.push(msg.text()));

        await page.goto('/login');
        await page.fill('input[name="_username"]', 'jasserbalti555@gmail.com');
        await page.fill('input[name="_password"]', 'jasserQ0*');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);

        // Check how many click handlers are on the button
        await page.evaluate(() => {
            const btn = document.querySelector('[data-action*="dropdown#toggle"]');
            if (!btn) return;

            let clickCount = 0;
            const originalAddEventListener = btn.addEventListener.bind(btn);
            
            // Wrap click to count
            const originalDispatchEvent = btn.dispatchEvent.bind(btn);
            btn.dispatchEvent = function(event) {
                if (event.type === 'click') {
                    console.log('[COUNT] Click event dispatched');
                }
                return originalDispatchEvent(event);
            };

            // Create a click and see how many handlers respond
            const menu = document.querySelector('[data-dropdown-target="menu"]');
            let toggleCallCount = 0;
            
            const origToggle = menu.classList.toggle.bind(menu.classList);
            menu.classList.toggle = function(token) {
                if (token === 'hidden') {
                    toggleCallCount++;
                    console.log(`[COUNT] classList.toggle("hidden") call #${toggleCallCount}`);
                }
                return origToggle(token);
            };

            console.log('[COUNT] Monitoring installed, now click the button');
        });

        consoleLogs.length = 0;

        // Now click
        await page.locator('[data-action*="dropdown#toggle"]').first().click();
        await page.waitForTimeout(300);

        console.log('\n=== CLICK HANDLER COUNT ===');
        const countLogs = consoleLogs.filter(l => l.includes('[COUNT]'));
        countLogs.forEach(log => console.log(log));

        const toggleCalls = countLogs.filter(l => l.includes('classList.toggle')).length;
        console.log(`\nTotal toggle calls: ${toggleCalls}`);
        
        // Should only be 1 toggle call per click
        expect(toggleCalls).toBeLessThanOrEqual(1);
    });
});
