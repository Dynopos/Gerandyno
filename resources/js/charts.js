import {
    BarController,
    BarElement,
    CategoryScale,
    Chart,
    Filler,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    Tooltip,
} from 'chart.js';

Chart.register(
    BarController,
    BarElement,
    CategoryScale,
    Filler,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    Tooltip,
);

const SERIES_COLOR = '#dc2626'; // red-600 — matches the rest of the app's branding
const SERIES_FILL = 'rgba(220, 38, 38, 0.08)'; // red-600 wash, for line/area charts
const MUTED_TEXT = '#94a3b8'; // slate-400
const GRIDLINE = '#e2e8f0'; // slate-200

function formatMoney(value) {
    return 'RM ' + Number(value).toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatCompact(value) {
    if (Math.abs(value) >= 1000) {
        return (value / 1000).toLocaleString('en-MY', { maximumFractionDigits: 1 }) + 'k';
    }

    return value.toString();
}

function buildChart(canvas) {
    const type = canvas.dataset.chart;
    const labels = JSON.parse(canvas.dataset.labels);
    const values = JSON.parse(canvas.dataset.values);
    const isLine = type === 'line';

    return new Chart(canvas, {
        type,
        data: {
            labels,
            datasets: [
                {
                    data: values,
                    backgroundColor: isLine ? SERIES_FILL : SERIES_COLOR,
                    borderColor: SERIES_COLOR,
                    borderWidth: isLine ? 2 : 0,
                    borderRadius: isLine ? 0 : { topLeft: 4, topRight: 4, bottomLeft: 0, bottomRight: 0 },
                    borderSkipped: false,
                    maxBarThickness: 24,
                    fill: isLine,
                    tension: 0.35,
                    pointRadius: isLine ? 0 : undefined,
                    pointHoverRadius: isLine ? 4 : undefined,
                    pointHoverBackgroundColor: SERIES_COLOR,
                    pointHoverBorderColor: '#ffffff',
                    pointHoverBorderWidth: 2,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0f172a',
                    padding: 10,
                    cornerRadius: 8,
                    displayColors: false,
                    callbacks: {
                        label: (ctx) => formatMoney(ctx.parsed.y),
                    },
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        color: MUTED_TEXT,
                        font: { size: 11 },
                        maxRotation: 0,
                        autoSkip: true,
                        maxTicksLimit: 10,
                    },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: GRIDLINE },
                    border: { display: false },
                    ticks: {
                        color: MUTED_TEXT,
                        font: { size: 11 },
                        callback: (value) => formatCompact(value),
                    },
                },
            },
        },
    });
}

function initCharts() {
    document.querySelectorAll('[data-chart]').forEach((canvas) => {
        if (canvas.dataset.chartInitialized) {
            return;
        }

        canvas.dataset.chartInitialized = 'true';
        buildChart(canvas);
    });
}

document.addEventListener('DOMContentLoaded', initCharts);
