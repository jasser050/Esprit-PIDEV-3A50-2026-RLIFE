// @ts-check
const { test, expect } = require('@playwright/test');

test.describe('Admin Statistics Page', () => {

    test('Check admin statistics page charts', async ({ page }) => {
        const consoleLogs = [];
        page.on('console', msg => consoleLogs.push({ type: msg.type(), text: msg.text() }));
        
        const errors = [];
        page.on('pageerror', err => errors.push(err.message));

        // Go directly to admin statistics (may redirect to login)
        await page.goto('/admin/statistics');
        
        // If redirected to login, log in
        if (page.url().includes('login')) {
            await page.fill('input[name="_username"]', 'admin@rlife.com');
            await page.fill('input[name="_password"]', 'admin123');
            await page.click('button[type="submit"]');
            await page.waitForLoadState('networkidle');
            
            // Navigate to admin statistics after login
            await page.goto('/admin/statistics');
        }
        
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(3000);

        // Check for console errors
        console.log('\n=== CONSOLE LOGS ===');
        consoleLogs.forEach(log => {
            if (log.type === 'error' || log.text.includes('Chart') || log.text.includes('undefined')) {
                console.log(`[${log.type}] ${log.text}`);
            }
        });

        // Check for page errors
        if (errors.length > 0) {
            console.log('\n=== PAGE ERRORS ===');
            errors.forEach(err => console.log(err));
        }

        // Check if Chart.js is loaded
        const chartJsLoaded = await page.evaluate(() => typeof Chart !== 'undefined');
        console.log(`\n=== Chart.js loaded: ${chartJsLoaded} ===`);

        // Check if canvas elements exist
        const userGrowthCanvas = await page.locator('#userGrowthChart').count();
        const genderCanvas = await page.locator('#genderChart').count();
        console.log(`\n=== Canvases: userGrowthChart=${userGrowthCanvas}, genderChart=${genderCanvas} ===`);

        // Check if charts are initialized
        const chartInfo = await page.evaluate(() => {
            const results = {};
            
            if (typeof Chart === 'undefined') {
                return { error: 'Chart.js not loaded' };
            }

            const userGrowthCanvas = document.getElementById('userGrowthChart');
            if (userGrowthCanvas) {
                const chart = Chart.getChart(userGrowthCanvas);
                results.userGrowthChart = {
                    canvasExists: true,
                    chartInitialized: !!chart,
                    chartType: chart?.config?.type
                };
            } else {
                results.userGrowthChart = { canvasExists: false };
            }

            const genderCanvas = document.getElementById('genderChart');
            if (genderCanvas) {
                const chart = Chart.getChart(genderCanvas);
                results.genderChart = {
                    canvasExists: true,
                    chartInitialized: !!chart,
                    chartType: chart?.config?.type
                };
            } else {
                results.genderChart = { canvasExists: false };
            }

            return results;
        });

        console.log('\n=== CHART INFO ===');
        console.log(JSON.stringify(chartInfo, null, 2));

        // Take a screenshot
        await page.screenshot({ path: 'tests/e2e/screenshots/admin-stats.png', fullPage: true });

        // Verify charts are initialized
        if (chartInfo.userGrowthChart?.canvasExists) {
            expect(chartInfo.userGrowthChart.chartInitialized).toBe(true);
        }
        if (chartInfo.genderChart?.canvasExists) {
            expect(chartInfo.genderChart.chartInitialized).toBe(true);
        }
    });

    test('Check admin statistics after Turbo navigation', async ({ page }) => {
        const consoleLogs = [];
        page.on('console', msg => consoleLogs.push({ type: msg.type(), text: msg.text() }));

        // Login first
        await page.goto('/login');
        await page.fill('input[name="_username"]', 'admin@rlife.com');
        await page.fill('input[name="_password"]', 'admin123');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(1000);

        // Go to admin dashboard first
        await page.goto('/admin');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(1000);

        // Now click on Statistics link (Turbo navigation)
        const statsLink = page.locator('a[href*="statistics"]').first();
        if (await statsLink.count() > 0) {
            console.log('\n=== Clicking Statistics link (Turbo nav) ===');
            await statsLink.click();
            await page.waitForLoadState('networkidle');
            await page.waitForTimeout(3000);

            // Check charts after Turbo navigation
            const chartInfo = await page.evaluate(() => {
                if (typeof Chart === 'undefined') {
                    return { error: 'Chart.js not loaded' };
                }

                const results = {};
                const userGrowthCanvas = document.getElementById('userGrowthChart');
                if (userGrowthCanvas) {
                    const chart = Chart.getChart(userGrowthCanvas);
                    results.userGrowthChart = {
                        chartInitialized: !!chart,
                        chartType: chart?.config?.type
                    };
                }

                const genderCanvas = document.getElementById('genderChart');
                if (genderCanvas) {
                    const chart = Chart.getChart(genderCanvas);
                    results.genderChart = {
                        chartInitialized: !!chart,
                        chartType: chart?.config?.type
                    };
                }

                return results;
            });

            console.log('\n=== CHARTS AFTER TURBO NAV ===');
            console.log(JSON.stringify(chartInfo, null, 2));

            // Log any errors from console
            const chartErrors = consoleLogs.filter(l => 
                l.type === 'error' || 
                l.text.includes('Chart') || 
                l.text.includes('undefined') ||
                l.text.includes('Cannot read')
            );
            if (chartErrors.length > 0) {
                console.log('\n=== CHART-RELATED CONSOLE MESSAGES ===');
                chartErrors.forEach(e => console.log(`[${e.type}] ${e.text}`));
            }

            await page.screenshot({ path: 'tests/e2e/screenshots/admin-stats-turbo.png', fullPage: true });
        }
    });
});
