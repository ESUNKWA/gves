import './bootstrap';
import './alerts';
import sort from '@alpinejs/sort';
import dataTable from './data-table';
import { initMoneyMasks } from './money-mask';

document.addEventListener('alpine:init', () => {
    window.Alpine.plugin(sort);
    window.Alpine.data('dataTable', dataTable);
});

initMoneyMasks();

if (document.getElementById('reports-dashboard')) {
    import('./reports-dashboard.js');
}
