// @ts-check
const { test, expect } = require('@playwright/test');

// Helper function to login
async function login(page) {
    await page.goto('/login');
    await page.fill('input[name="_username"]', 'jasserbalti555@gmail.com');
    await page.fill('input[name="_password"]', 'jasserQ0*');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);
}

test.describe('Charts and Statistics Tests', () => {

    test('Dashboard charts render on first load', async ({ page }) => {
        const consoleLogs = [];
        page.on('console', msg => consoleLogs.push({ type: msg.type(), text: msg.text() }));
        
        const errors = [];
        page.on('pageerror', err => errors.push(err.message));

        await login(page);
        
        // Navigate to dashboard
        await page.goto('/dashboard');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);

        // Check if chart canvases exist
        const chartCanvases = await page.locator('canvas[data-chart-target="canvas"]').count();
        console.log(`\n=== DASHBOARD: Found ${chartCanvases} chart canvases ===`);

        // Check if Chart.js initialized the charts (canvas should have dimensions)
        const chartInfo = await page.evaluate(() => {
            const canvases = document.querySelectorAll('canvas[data-chart-target="canvas"]');
            return Array.from(canvases).map((canvas, i) => {
                const chart = Chart.getChart(canvas);
                return {
                    index: i,
                    hasChart: !!chart,
                    chartType: chart?.config?.type,
                    width: canvas.width,
                    height: canvas.height
                };
            });
        });

        console.log('\n=== DASHBOARD CHART INFO ===');
        console.log(JSON.stringify(chartInfo, null, 2));

        // Log any errors
        if (errors.length > 0) {
            console.log('\n=== PAGE ERRORS ===');
            errors.forEach(err => console.log(err));
        }

        // Verify charts are rendered
        expect(chartCanvases).toBeGreaterThan(0);
        chartInfo.forEach(info => {
            expect(info.hasChart).toBe(true);
        });
    });

    test('Dashboard charts render after Turbo navigation', async ({ page }) => {
        await login(page);

        // First go to a different page
        await page.goto('/courses');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(500);

        // Navigate to dashboard via Turbo (click a link)
        await page.click('a[href*="dashboard"]');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);

        // Check if charts rendered
        const chartInfo = await page.evaluate(() => {
            const canvases = document.querySelectorAll('canvas[data-chart-target="canvas"]');
            return Array.from(canvases).map((canvas, i) => {
                const chart = Chart.getChart(canvas);
                return {
                    index: i,
                    hasChart: !!chart,
                    chartType: chart?.config?.type
                };
            });
        });

        console.log('\n=== DASHBOARD CHARTS AFTER TURBO NAV ===');
        console.log(JSON.stringify(chartInfo, null, 2));

        chartInfo.forEach(info => {
            expect(info.hasChart).toBe(true);
        });
    });

    test('Projects page charts render on first load', async ({ page }) => {
        const errors = [];
        page.on('pageerror', err => errors.push(err.message));

        await login(page);
        
        // Navigate to projects page directly
        await page.goto('/projects');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);

        // Check for chart canvases
        const statutCanvas = page.locator('#statutChart');
        const anneeCanvas = page.locator('#anneeChart');

        const hasStatutCanvas = await statutCanvas.count() > 0;
        const hasAnneeCanvas = await anneeCanvas.count() > 0;

        console.log(`\n=== PROJECTS PAGE: statutChart=${hasStatutCanvas}, anneeChart=${hasAnneeCanvas} ===`);

        // Check if Chart.js initialized them
        const chartInfo = await page.evaluate(() => {
            const results = {};
            
            const statutCanvas = document.getElementById('statutChart');
            if (statutCanvas) {
                const chart = Chart.getChart(statutCanvas);
                results.statutChart = {
                    exists: true,
                    hasChart: !!chart,
                    type: chart?.config?.type
                };
            } else {
                results.statutChart = { exists: false };
            }

            const anneeCanvas = document.getElementById('anneeChart');
            if (anneeCanvas) {
                const chart = Chart.getChart(anneeCanvas);
                results.anneeChart = {
                    exists: true,
                    hasChart: !!chart,
                    type: chart?.config?.type
                };
            } else {
                results.anneeChart = { exists: false };
            }

            return results;
        });

        console.log('\n=== PROJECTS CHART INFO ===');
        console.log(JSON.stringify(chartInfo, null, 2));

        if (errors.length > 0) {
            console.log('\n=== PAGE ERRORS ===');
            errors.forEach(err => console.log(err));
        }

        // Both charts should be initialized
        if (chartInfo.statutChart.exists) {
            expect(chartInfo.statutChart.hasChart).toBe(true);
        }
        if (chartInfo.anneeChart.exists) {
            expect(chartInfo.anneeChart.hasChart).toBe(true);
        }
    });

    test('Projects page charts render after Turbo navigation', async ({ page }) => {
        await login(page);

        // First go to dashboard
        await page.goto('/dashboard');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(500);

        // Navigate to projects via sidebar link (Turbo)
        const projectsLink = page.locator('a[href*="project"]').first();
        if (await projectsLink.count() > 0) {
            await projectsLink.click();
            await page.waitForLoadState('networkidle');
            await page.waitForTimeout(2000);

            // Check charts
            const chartInfo = await page.evaluate(() => {
                const results = {};
                
                const statutCanvas = document.getElementById('statutChart');
                if (statutCanvas) {
                    const chart = Chart.getChart(statutCanvas);
                    results.statutChart = {
                        hasChart: !!chart,
                        type: chart?.config?.type
                    };
                }

                const anneeCanvas = document.getElementById('anneeChart');
                if (anneeCanvas) {
                    const chart = Chart.getChart(anneeCanvas);
                    results.anneeChart = {
                        hasChart: !!chart,
                        type: chart?.config?.type
                    };
                }

                return results;
            });

            console.log('\n=== PROJECTS CHARTS AFTER TURBO NAV ===');
            console.log(JSON.stringify(chartInfo, null, 2));

            if (chartInfo.statutChart) {
                expect(chartInfo.statutChart.hasChart).toBe(true);
            }
            if (chartInfo.anneeChart) {
                expect(chartInfo.anneeChart.hasChart).toBe(true);
            }
        }
    });

    test('Assignments page charts render on first load', async ({ page }) => {
        const errors = [];
        page.on('pageerror', err => errors.push(err.message));

        await login(page);
        
        await page.goto('/assignments');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);

        const chartInfo = await page.evaluate(() => {
            const results = {};
            
            const statutCanvas = document.getElementById('statutChart');
            if (statutCanvas) {
                const chart = Chart.getChart(statutCanvas);
                results.statutChart = {
                    exists: true,
                    hasChart: !!chart,
                    type: chart?.config?.type
                };
            } else {
                results.statutChart = { exists: false };
            }

            const prioriteCanvas = document.getElementById('prioriteChart');
            if (prioriteCanvas) {
                const chart = Chart.getChart(prioriteCanvas);
                results.prioriteChart = {
                    exists: true,
                    hasChart: !!chart,
                    type: chart?.config?.type
                };
            } else {
                results.prioriteChart = { exists: false };
            }

            return results;
        });

        console.log('\n=== ASSIGNMENTS CHART INFO ===');
        console.log(JSON.stringify(chartInfo, null, 2));

        if (errors.length > 0) {
            console.log('\n=== PAGE ERRORS ===');
            errors.forEach(err => console.log(err));
        }

        if (chartInfo.statutChart.exists) {
            expect(chartInfo.statutChart.hasChart).toBe(true);
        }
        if (chartInfo.prioriteChart.exists) {
            expect(chartInfo.prioriteChart.hasChart).toBe(true);
        }
    });

    test('Assignments page charts render after Turbo navigation', async ({ page }) => {
        await login(page);

        // Start from dashboard
        await page.goto('/dashboard');
        await page.waitForLoadState('networkidle');

        // Navigate to assignments via Turbo
        const assignmentsLink = page.locator('a[href*="assignment"]').first();
        if (await assignmentsLink.count() > 0) {
            await assignmentsLink.click();
            await page.waitForLoadState('networkidle');
            await page.waitForTimeout(2000);

            const chartInfo = await page.evaluate(() => {
                const results = {};
                
                const statutCanvas = document.getElementById('statutChart');
                if (statutCanvas) {
                    const chart = Chart.getChart(statutCanvas);
                    results.statutChart = {
                        hasChart: !!chart,
                        type: chart?.config?.type
                    };
                }

                const prioriteCanvas = document.getElementById('prioriteChart');
                if (prioriteCanvas) {
                    const chart = Chart.getChart(prioriteCanvas);
                    results.prioriteChart = {
                        hasChart: !!chart,
                        type: chart?.config?.type
                    };
                }

                return results;
            });

            console.log('\n=== ASSIGNMENTS CHARTS AFTER TURBO NAV ===');
            console.log(JSON.stringify(chartInfo, null, 2));

            if (chartInfo.statutChart) {
                expect(chartInfo.statutChart.hasChart).toBe(true);
            }
            if (chartInfo.prioriteChart) {
                expect(chartInfo.prioriteChart.hasChart).toBe(true);
            }
        }
    });

    test('Check for Chart.js "Canvas already in use" errors', async ({ page }) => {
        const errors = [];
        const warnings = [];
        
        page.on('pageerror', err => errors.push(err.message));
        page.on('console', msg => {
            if (msg.type() === 'error' || msg.type() === 'warning') {
                const text = msg.text();
                if (text.includes('Canvas') || text.includes('chart') || text.includes('Chart')) {
                    warnings.push(text);
                }
            }
        });

        await login(page);

        // Navigate back and forth multiple times
        for (let i = 0; i < 3; i++) {
            await page.goto('/dashboard');
            await page.waitForLoadState('networkidle');
            await page.waitForTimeout(500);

            await page.goto('/projects');
            await page.waitForLoadState('networkidle');
            await page.waitForTimeout(500);

            await page.goto('/assignments');
            await page.waitForLoadState('networkidle');
            await page.waitForTimeout(500);
        }

        console.log('\n=== CHART-RELATED ERRORS ===');
        errors.forEach(err => console.log('ERROR:', err));
        warnings.forEach(warn => console.log('WARNING:', warn));

        // Should not have "Canvas is already in use" errors
        const canvasInUseError = errors.find(e => e.includes('Canvas is already in use'));
        expect(canvasInUseError).toBeUndefined();
    });
});
