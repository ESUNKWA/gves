import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

function cssVar(name, fallback) {
    const value = getComputedStyle(document.documentElement).getPropertyValue(name).trim();

    return value ? `rgb(${value})` : fallback;
}

const swalDefaults = {
    confirmButtonColor: cssVar('--color-primary', '#f990a5'),
    cancelButtonColor: cssVar('--muted', '#78746c'),
    buttonsStyling: true,
};

// Any <form data-confirm="..."> shows a SweetAlert2 confirmation before
// actually submitting, replacing the native onsubmit="return confirm(...)"
// pattern used across the app (destructive actions, payroll runs, etc.).
document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.dataset.confirm) return;

    event.preventDefault();

    Swal.fire({
        ...swalDefaults,
        icon: 'warning',
        text: form.dataset.confirm,
        showCancelButton: true,
        confirmButtonText: 'Confirmer',
        cancelButtonText: 'Annuler',
        reverseButtons: true,
    }).then((result) => {
        if (result.isConfirmed) {
            form.submit();
        }
    });
});

// Flash messages (redirect ->with('status'|'error', ...)) are read once from
// data attributes set server-side on <body> (see layouts/app.blade.php) and
// shown as a toast instead of an inline banner.
document.addEventListener('DOMContentLoaded', () => {
    const { flashStatus, flashError } = document.body.dataset;

    if (flashStatus) {
        Swal.fire({
            ...swalDefaults,
            icon: 'success',
            text: flashStatus,
            toast: true,
            position: 'top-end',
            timer: 3500,
            timerProgressBar: true,
            showConfirmButton: false,
        });
    }

    if (flashError) {
        Swal.fire({
            ...swalDefaults,
            icon: 'error',
            text: flashError,
            toast: true,
            position: 'top-end',
            timer: 4500,
            timerProgressBar: true,
            showConfirmButton: false,
        });
    }
});
