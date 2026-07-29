/**
 * Progressive-enhancement datatable: operates directly on a server-rendered
 * <table> (search, sort, paginate client-side) instead of re-templating rows
 * in JS, so every existing Blade row (badges, forms, permission-gated
 * buttons...) keeps working untouched.
 *
 * Sortable columns are opt-in via `data-sort` on a <th>. Sorting compares
 * `data-sort-value` on the matching <td> when present (e.g. a timestamp),
 * falling back to the cell's text content otherwise.
 *
 * The @empty/@forelse fallback row must carry `data-empty-row` so it's
 * excluded from search/sort/pagination — it only exists in the DOM when
 * there are zero rows to begin with, so no extra bookkeeping is required.
 */
export default function dataTable({ perPage = 10 } = {}) {
    return {
        query: '',
        page: 1,
        perPage,
        sortIndex: null,
        sortDir: 1,
        totalRows: 0,
        visibleCount: 0,

        init() {
            const table = this.$refs.wrapper.querySelector('table');
            if (!table) return;

            this.table = table;
            this.tbody = table.querySelector('tbody');
            const allRows = Array.from(this.tbody.children).filter((el) => el.tagName === 'TR');
            this.rows = allRows.filter((row) => !row.hasAttribute('data-empty-row'));
            this.totalRows = this.rows.length;

            this.headers = Array.from(table.querySelectorAll('thead th'));
            this.headers.forEach((th, index) => {
                if (!('sort' in th.dataset)) return;
                th.classList.add('cursor-pointer', 'select-none', 'hover:text-fg');
                th.addEventListener('click', () => this.toggleSort(index));
                th.insertAdjacentHTML('beforeend', ' <span class="data-table-sort-indicator inline-block w-3"></span>');
            });

            this.$watch('query', () => {
                this.page = 1;
                this.render();
            });
            this.$watch('perPage', () => {
                this.page = 1;
                this.render();
            });

            this.render();
        },

        toggleSort(index) {
            if (this.sortIndex === index) {
                this.sortDir *= -1;
            } else {
                this.sortIndex = index;
                this.sortDir = 1;
            }
            this.render();
        },

        cellValue(row, index) {
            const cell = row.children[index];
            if (!cell) return '';
            return cell.dataset.sortValue !== undefined
                ? cell.dataset.sortValue
                : cell.textContent.trim().toLowerCase();
        },

        matchesQuery(row) {
            if (!this.query.trim()) return true;
            return row.textContent.toLowerCase().includes(this.query.trim().toLowerCase());
        },

        get filteredRows() {
            return this.rows.filter((row) => this.matchesQuery(row));
        },

        get sortedRows() {
            const rows = this.filteredRows;
            if (this.sortIndex === null) return rows;

            const index = this.sortIndex;

            return rows.slice().sort((a, b) => {
                const va = this.cellValue(a, index);
                const vb = this.cellValue(b, index);
                const na = parseFloat(va);
                const nb = parseFloat(vb);
                const bothNumeric = va !== '' && vb !== '' && !isNaN(na) && !isNaN(nb);
                const cmp = bothNumeric ? na - nb : va.localeCompare(vb, 'fr');

                return cmp * this.sortDir;
            });
        },

        get totalPages() {
            return Math.max(1, Math.ceil(this.filteredRows.length / this.perPage));
        },

        get pageStart() {
            return this.visibleCount === 0 ? 0 : (this.page - 1) * this.perPage + 1;
        },

        get pageEnd() {
            return Math.min(this.page * this.perPage, this.visibleCount);
        },

        render() {
            if (this.page > this.totalPages) this.page = this.totalPages;

            const sorted = this.sortedRows;
            this.visibleCount = sorted.length;

            this.rows.forEach((row) => {
                row.style.display = 'none';
            });

            const start = (this.page - 1) * this.perPage;
            sorted.slice(start, start + this.perPage).forEach((row) => {
                row.style.display = '';
                this.tbody.appendChild(row);
            });

            this.headers.forEach((th, index) => {
                const indicator = th.querySelector('.data-table-sort-indicator');
                if (!indicator) return;
                indicator.textContent = index === this.sortIndex ? (this.sortDir === 1 ? '▲' : '▼') : '';
            });
        },

        prevPage() {
            if (this.page > 1) {
                this.page--;
                this.render();
            }
        },

        nextPage() {
            if (this.page < this.totalPages) {
                this.page++;
                this.render();
            }
        },

        goToPage(page) {
            this.page = page;
            this.render();
        },
    };
}
