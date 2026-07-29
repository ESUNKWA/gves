/**
 * Thousands-separator input mask for amount fields (French convention: a
 * space every 3 digits, e.g. "300 000"). Delegated at the document level so
 * it works for dynamically rendered rows (x-for) without per-field wiring —
 * any <input data-money> gets formatted live, and every one is stripped back
 * to plain digits right before its form submits, so the server only ever
 * sees a clean numeric string.
 */
export function formatMoney(value) {
    const digits = String(value).replace(/\D/g, '');

    return digits.replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
}

export function initMoneyMasks() {
    document.addEventListener('input', (event) => {
        if (!event.target.matches('[data-money]')) return;

        const el = event.target;
        const cursorFromEnd = el.value.length - el.selectionStart;
        el.value = formatMoney(el.value);
        const position = Math.max(0, el.value.length - cursorFromEnd);
        el.setSelectionRange(position, position);
    });

    document.addEventListener(
        'submit',
        (event) => {
            event.target.querySelectorAll?.('[data-money]').forEach((el) => {
                el.value = el.value.replace(/\s/g, '');
            });
        },
        true
    );
}
