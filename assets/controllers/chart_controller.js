import { Controller } from '@hotwired/stimulus';
import Chart from 'chart.js/auto';

export default class extends Controller {
    static targets = ['canvas'];
    static values = {
        type: { type: String, default: 'line' },
        data: Object,
        options: { type: Object, default: {} }
    };

    connect() {
        this.chart = null;
        this.initChart();

        // Listen for theme changes
        this.observer = new MutationObserver(() => {
            this.updateChartColors();
        });

        this.observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class']
        });
    }

    disconnect() {
        if (this.chart) {
            this.chart.destroy();
        }
        if (this.observer) {
            this.observer.disconnect();
        }
    }

    initChart() {
        const ctx = this.canvasTarget.getContext('2d');
        const isDark = document.documentElement.classList.contains('dark');

        const colors = this.getThemeColors(isDark);
        const chartData = this.prepareChartData(colors);
        const chartOptions = this.prepareChartOptions(colors);

        this.chart = new Chart(ctx, {
            type: this.typeValue,
            data: chartData,
            options: chartOptions
        });
    }

    getThemeColors(isDark) {
        return {
            text: isDark ? '#e2e8f0' : '#334155',
            textMuted: isDark ? '#94a3b8' : '#64748b',
            gridLines: isDark ? 'rgba(148, 163, 184, 0.1)' : 'rgba(148, 163, 184, 0.2)',
            primary: '#8b5cf6',
            primaryLight: 'rgba(139, 92, 246, 0.2)',
            success: '#10b981',
            successLight: 'rgba(16, 185, 129, 0.2)',
            warning: '#f59e0b',
            warningLight: 'rgba(245, 158, 11, 0.2)',
            accent: '#f97316',
            accentLight: 'rgba(249, 115, 22, 0.2)',
            danger: '#f43f5e',
            dangerLight: 'rgba(244, 63, 94, 0.2)'
        };
    }

    prepareChartData(colors) {
        const data = this.dataValue;

        // Apply theme colors to datasets
        if (data.datasets) {
            data.datasets = data.datasets.map((dataset, index) => {
                const colorKeys = ['primary', 'success', 'warning', 'accent', 'danger'];
                const colorKey = dataset.colorKey || colorKeys[index % colorKeys.length];

                return {
                    ...dataset,
                    borderColor: colors[colorKey],
                    backgroundColor: this.typeValue === 'line'
                        ? colors[colorKey + 'Light']
                        : dataset.backgroundColor || colors[colorKey],
                    pointBackgroundColor: colors[colorKey],
                    pointBorderColor: colors[colorKey],
                    pointHoverBackgroundColor: colors[colorKey],
                    tension: 0.4
                };
            });
        }

        return data;
    }

    prepareChartOptions(colors) {
        const baseOptions = {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                legend: {
                    display: this.optionsValue.showLegend !== false,
                    position: 'bottom',
                    labels: {
                        color: colors.text,
                        usePointStyle: true,
                        padding: 20,
                        font: {
                            family: "'Plus Jakarta Sans', sans-serif",
                            size: 12
                        }
                    }
                },
                tooltip: {
                    backgroundColor: colors.text === '#e2e8f0' ? '#1e293b' : '#ffffff',
                    titleColor: colors.text === '#e2e8f0' ? '#f8fafc' : '#0f172a',
                    bodyColor: colors.text === '#e2e8f0' ? '#e2e8f0' : '#334155',
                    borderColor: colors.gridLines,
                    borderWidth: 1,
                    padding: 12,
                    cornerRadius: 8,
                    titleFont: {
                        family: "'Plus Jakarta Sans', sans-serif",
                        size: 13,
                        weight: 600
                    },
                    bodyFont: {
                        family: "'Plus Jakarta Sans', sans-serif",
                        size: 12
                    }
                }
            },
            scales: this.typeValue !== 'doughnut' && this.typeValue !== 'pie' ? {
                x: {
                    grid: {
                        color: colors.gridLines,
                        drawBorder: false
                    },
                    ticks: {
                        color: colors.textMuted,
                        font: {
                            family: "'Plus Jakarta Sans', sans-serif",
                            size: 11
                        }
                    }
                },
                y: {
                    grid: {
                        color: colors.gridLines,
                        drawBorder: false
                    },
                    ticks: {
                        color: colors.textMuted,
                        font: {
                            family: "'Plus Jakarta Sans', sans-serif",
                            size: 11
                        }
                    },
                    beginAtZero: true
                }
            } : undefined
        };

        return { ...baseOptions, ...this.optionsValue };
    }

    updateChartColors() {
        if (!this.chart) return;

        const isDark = document.documentElement.classList.contains('dark');
        const colors = this.getThemeColors(isDark);

        // Update datasets
        this.chart.data.datasets = this.chart.data.datasets.map((dataset, index) => {
            const colorKeys = ['primary', 'success', 'warning', 'accent', 'danger'];
            const colorKey = dataset.colorKey || colorKeys[index % colorKeys.length];

            return {
                ...dataset,
                borderColor: colors[colorKey],
                backgroundColor: this.typeValue === 'line'
                    ? colors[colorKey + 'Light']
                    : dataset.backgroundColor || colors[colorKey],
                pointBackgroundColor: colors[colorKey],
                pointBorderColor: colors[colorKey]
            };
        });

        // Update scales colors
        if (this.chart.options.scales) {
            if (this.chart.options.scales.x) {
                this.chart.options.scales.x.grid.color = colors.gridLines;
                this.chart.options.scales.x.ticks.color = colors.textMuted;
            }
            if (this.chart.options.scales.y) {
                this.chart.options.scales.y.grid.color = colors.gridLines;
                this.chart.options.scales.y.ticks.color = colors.textMuted;
            }
        }

        // Update legend colors
        if (this.chart.options.plugins && this.chart.options.plugins.legend) {
            this.chart.options.plugins.legend.labels.color = colors.text;
        }

        // Update tooltip colors
        if (this.chart.options.plugins && this.chart.options.plugins.tooltip) {
            this.chart.options.plugins.tooltip.backgroundColor = isDark ? '#1e293b' : '#ffffff';
            this.chart.options.plugins.tooltip.titleColor = isDark ? '#f8fafc' : '#0f172a';
            this.chart.options.plugins.tooltip.bodyColor = isDark ? '#e2e8f0' : '#334155';
        }

        this.chart.update();
    }
}
