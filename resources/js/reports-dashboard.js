import {
    Chart,
    BarController,
    LineController,
    CategoryScale,
    LinearScale,
    BarElement,
    LineElement,
    PointElement,
    Legend,
    Tooltip,
} from 'chart.js';

Chart.register(
    BarController,
    LineController,
    CategoryScale,
    LinearScale,
    BarElement,
    LineElement,
    PointElement,
    Legend,
    Tooltip
);

// Reference categorical palette (fixed order — never cycled), light/dark steps.
const PALETTE = {
    light: ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#008300', '#4a3aa7', '#e34948'],
    dark: ['#3987e5', '#d95926', '#199e70', '#c98500', '#d55181', '#008300', '#9085e9', '#e66767'],
};

const isDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
const colors = isDark ? PALETTE.dark : PALETTE.light;

function cssVar(name) {
    const value = getComputedStyle(document.documentElement).getPropertyValue(name).trim();

    return `rgb(${value})`;
}

const ink = {
    text: cssVar('--muted'),
    grid: cssVar('--line-soft'),
    surface: cssVar('--surface'),
};

Chart.defaults.font.family = "Figtree, ui-sans-serif, system-ui, sans-serif";
Chart.defaults.color = ink.text;
Chart.defaults.borderColor = ink.grid;

function baseScales(extra = {}) {
    return {
        x: {
            grid: { display: false },
            ticks: { color: ink.text },
            ...(extra.x || {}),
        },
        y: {
            beginAtZero: true,
            grid: { color: ink.grid },
            ticks: { color: ink.text, precision: 0 },
            ...(extra.y || {}),
        },
    };
}

function singleSeriesBarChart(canvasId, labels, values, colorIndex = 0) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    data: values,
                    backgroundColor: colors[colorIndex],
                    borderRadius: { topLeft: 4, topRight: 4, bottomLeft: 0, bottomRight: 0 },
                    borderSkipped: false,
                    maxBarThickness: 24,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: baseScales(),
        },
    });
}

function multiCategoryBarChart(canvasId, labels, values) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    data: values,
                    backgroundColor: labels.map((_, i) => colors[i % colors.length]),
                    borderRadius: { topLeft: 4, topRight: 4, bottomLeft: 0, bottomRight: 0 },
                    borderSkipped: false,
                    maxBarThickness: 24,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: baseScales(),
        },
    });
}

function groupedBarChart(canvasId, labels, series) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: series.map((s, i) => ({
                label: s.label,
                data: s.data,
                backgroundColor: colors[i % colors.length],
                borderRadius: { topLeft: 4, topRight: 4, bottomLeft: 0, bottomRight: 0 },
                borderSkipped: false,
                maxBarThickness: 24,
                categoryPercentage: 0.7,
                barPercentage: 0.9,
            })),
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: true, position: 'top', labels: { color: ink.text, usePointStyle: true } } },
            scales: baseScales(),
        },
    });
}

function lineChart(canvasId, labels, series) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;

    new Chart(canvas, {
        type: 'line',
        data: {
            labels,
            datasets: series.map((s, i) => ({
                label: s.label,
                data: s.data,
                borderColor: colors[i % colors.length],
                backgroundColor: colors[i % colors.length],
                borderWidth: 2,
                pointRadius: 5,
                pointBorderWidth: 2,
                pointBorderColor: ink.surface,
                pointBackgroundColor: colors[i % colors.length],
                tension: 0.25,
                fill: false,
            })),
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { display: true, position: 'top', labels: { color: ink.text, usePointStyle: true } } },
            scales: baseScales(),
        },
    });
}

const dataEl = document.getElementById('reports-data');
if (dataEl) {
    const data = JSON.parse(dataEl.textContent);

    // Each tab panel is toggled with Alpine's x-show — everything except the
    // default "workforce" tab starts as `display: none`, and Chart.js reads a
    // 0x0 box for a hidden canvas at construction time. So charts for the other
    // tabs are built lazily, the first time their tab is actually shown, rather
    // than all at once on page load.
    const tabBuilders = {
        attendance() {
            singleSeriesBarChart('chart-attendance-late', data.attendance.months, data.attendance.lateCounts, 3);
            singleSeriesBarChart('chart-attendance-overtime', data.attendance.months, data.attendance.overtimeHours, 0);
        },
        leaves() {
            groupedBarChart('chart-leaves', data.leaves.types, [
                { label: 'Acquis', data: data.leaves.accrued },
                { label: 'Pris', data: data.leaves.used },
                { label: 'Restant', data: data.leaves.remaining },
            ]);
        },
        payroll() {
            lineChart('chart-payroll', data.payroll.months, [
                { label: 'Brut', data: data.payroll.gross },
                { label: 'Net', data: data.payroll.net },
                { label: 'Charges patronales', data: data.payroll.employerCharges },
            ]);
        },
        movements() {
            groupedBarChart('chart-movements', data.movements.months, [
                { label: 'Entrées', data: data.movements.hires },
                { label: 'Sorties', data: data.movements.departures },
            ]);
        },
    };

    const builtTabs = new Set();

    document.addEventListener('reports-tab-click', (event) => {
        const tab = event.detail;
        if (builtTabs.has(tab) || !tabBuilders[tab]) return;

        builtTabs.add(tab);
        tabBuilders[tab]();
    });

    // "workforce" is the default visible tab, so its charts build immediately.
    singleSeriesBarChart('chart-workforce-department', Object.keys(data.workforce.byDepartment), Object.values(data.workforce.byDepartment), 0);
    singleSeriesBarChart('chart-workforce-site', Object.keys(data.workforce.bySite), Object.values(data.workforce.bySite), 2);
    singleSeriesBarChart('chart-workforce-age', Object.keys(data.workforce.ageBrackets), Object.values(data.workforce.ageBrackets), 6);
    multiCategoryBarChart('chart-workforce-status', Object.keys(data.workforce.byStatus), Object.values(data.workforce.byStatus));
}
