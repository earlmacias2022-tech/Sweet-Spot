// SWEET SPOT ADMIN — DASHBOARD NAVIGATION AND PRODUCT FILTERS

function escapeHTML(value) {
    return String(value).replace(/[&<>"']/g, character => ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#039;"
    }[character]));
}

// Dashboard section navigation
function activateDashboardView(view, updateHash = true) {
    const targetSection = document.getElementById('view-' + view);
    if (!targetSection) return;
    document.querySelectorAll('.sidebar-link[data-view]').forEach(link => {
        const isActive = link.dataset.view === view;
        link.classList.toggle('active', isActive);
        if (isActive) link.setAttribute('aria-current', 'page');
        else link.removeAttribute('aria-current');
    });
    document.querySelectorAll('.admin-view').forEach(section => {
        section.classList.toggle('active', section === targetSection);
    });
    const activeLink = document.querySelector('.sidebar-link[data-view="' + view + '"]');
    const breadcrumb = document.querySelector('#breadcrumb-current');
    if (breadcrumb && activeLink) breadcrumb.textContent = activeLink.textContent.trim();
    document.querySelector('#admin-sidebar')?.classList.remove('is-open');
    if (updateHash && window.location.hash !== '#' + view) window.location.hash = view;
}

document.querySelectorAll('.sidebar-link[data-view]').forEach(link => {
    link.addEventListener('click', event => {
        event.preventDefault();
        activateDashboardView(link.dataset.view);
    });
});

document.querySelectorAll('[data-view-link]').forEach(link => {
    link.addEventListener('click', event => {
        event.preventDefault();
        activateDashboardView(link.dataset.viewLink);
    });
});

function restoreDashboardViewFromHash() {
    const requestedView = window.location.hash.slice(1);
    activateDashboardView(requestedView || 'overview', false);
}
window.addEventListener('hashchange', restoreDashboardViewFromHash);
window.addEventListener('pageshow', restoreDashboardViewFromHash);
restoreDashboardViewFromHash();

// Product list filtering uses the products already rendered from MySQL by PHP.
// It does not create, edit, or delete records.
const productTableBody = document.querySelector("#product-table-body");
const productSearch = document.querySelector("#product-search");
const categoryFilter = document.querySelector("#category-filter");

function filterDatabaseProducts() {
    if (!productTableBody) return;

    const searchTerm = (productSearch?.value || "").trim().toLowerCase();
    const selectedCategory = categoryFilter?.value || "all";
    const rows = Array.from(productTableBody.querySelectorAll("tr"));
    let visibleCount = 0;

    rows.forEach(row => {
        const rowText = row.textContent.toLowerCase();
        const rowCategory = row.dataset.categoryId || "0";
        const matchesSearch = rowText.includes(searchTerm);
        const matchesCategory = selectedCategory === "all" || rowCategory === selectedCategory;
        const isVisible = matchesSearch && matchesCategory;

        row.hidden = !isVisible;
        if (isVisible) visibleCount++;
    });

    const resultCount = document.querySelector("#table-result-count");
    if (resultCount) {
        resultCount.textContent = "Showing " + visibleCount + " of " + rows.length + " products";
    }

    let noResults = document.querySelector("#product-filter-empty");
    if (visibleCount === 0 && rows.length > 0) {
        if (!noResults) {
            noResults = document.createElement("tr");
            noResults.id = "product-filter-empty";
            noResults.innerHTML = '<td colspan="5" class="category-no-results">No products match your search.</td>';
            productTableBody.appendChild(noResults);
        }
        noResults.hidden = false;
    } else if (noResults) {
        noResults.hidden = true;
    }
}

productSearch?.addEventListener("input", filterDatabaseProducts);
categoryFilter?.addEventListener("change", filterDatabaseProducts);

