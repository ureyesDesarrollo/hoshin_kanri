function badgeEstatus(e) {
    if (e == 1) return '<span class="badge bg-success">Activo</span>';
    if (e == 2) return '<span class="badge bg-secondary">Cerrado</span>';
    if (e == 3) return '<span class="badge bg-danger">Cancelado</span>';
    return '';
}

function badgeCompletada(e) {
    if (e == 0) return '<span class="badge bg-warning">En proceso</span>';
    if (e == 1) return '<span class="badge bg-success">Completado</span>';
    return '';
}

function getResponsableIniciales(nombre) {
    if (!nombre) return '?';
    const partes = nombre.split(' ');
    return partes.map(p => p.charAt(0)).join('').toUpperCase().substring(0, 2);
}

function formatFecha(f) {
    return new Date(f).toLocaleDateString();
}

function renderPagination(p, containerId) {
    const ul = $(`#${containerId}`);
    ul.empty();

    if (p.total_pages <= 1) return;

    const maxVisible = 2; // páginas a cada lado
    let start = Math.max(1, p.page - maxVisible);
    let end = Math.min(p.total_pages, p.page + maxVisible);

    // Prev
    ul.append(`
        <li class="page-item ${p.page === 1 ? 'disabled' : ''}">
            <a class="page-link border-0" href="#" data-page="${p.page - 1}">
                <i class="fas fa-chevron-left"></i>
            </a>
        </li>
    `);

    // Primera página
    if (start > 1) {
        ul.append(pageItem(1, p.page));
        if (start > 2) ul.append(ellipsis());
    }

    // Rango central
    for (let i = start; i <= end; i++) {
        ul.append(pageItem(i, p.page));
    }

    // Última página
    if (end < p.total_pages) {
        if (end < p.total_pages - 1) ul.append(ellipsis());
        ul.append(pageItem(p.total_pages, p.page));
    }

    // Next
    ul.append(`
        <li class="page-item ${p.page === p.total_pages ? 'disabled' : ''}">
            <a class="page-link border-0" href="#" data-page="${p.page + 1}">
                <i class="fas fa-chevron-right"></i>
            </a>
        </li>
    `);
}

// Helpers
function pageItem(i, current) {
    return `
        <li class="page-item ${i === current ? 'active' : ''}">
            <a class="page-link border-0 ${i === current ? 'bg-primary text-white' : ''}"
               href="#" data-page="${i}">
                ${i}
            </a>
        </li>
    `;
}

function ellipsis() {
    return `
        <li class="page-item disabled">
            <span class="page-link border-0">…</span>
        </li>
    `;
}