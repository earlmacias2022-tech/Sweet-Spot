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
document.querySelectorAll("[data-view]").forEach(link => {
    link.addEventListener("click", event => {
        event.preventDefault();

        const view = link.dataset.view;
        document.querySelectorAll(".sidebar-link[data-view]").forEach(item => {
            item.classList.toggle("active", item.dataset.view === view);
        });

        document.querySelectorAll(".admin-view").forEach(section => {
            section.classList.toggle("active", section.id === "view-" + view);
        });

        const breadcrumb = document.querySelector("#breadcrumb-current");
        if (breadcrumb) {
            breadcrumb.textContent = link.textContent.trim().replace(/\d+/g, "").trim();
        }

        document.querySelector("#admin-sidebar")?.classList.remove("is-open");
        window.location.hash = view;
    });
});

// Links and shortcuts that open a dashboard section
document.querySelectorAll("[data-view-link]").forEach(link => {
    link.addEventListener("click", event => {
        event.preventDefault();
        const view = link.dataset.viewLink;
        document.querySelector('[data-view="' + view + '"]')?.click();
    });
});

// Mobile sidebar
document.querySelector("#sidebar-toggle")?.addEventListener("click", () => {
    document.querySelector("#admin-sidebar")?.classList.toggle("is-open");
});

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
            noResults.innerHTML = '<td colspan="4" class="category-no-results">No products match your search.</td>';
            productTableBody.appendChild(noResults);
        }
        noResults.hidden = false;
    } else if (noResults) {
        noResults.hidden = true;
    }
}

productSearch?.addEventListener("input", filterDatabaseProducts);
categoryFilter?.addEventListener("change", filterDatabaseProducts);

// Restore the selected dashboard section when a dashboard hash is present.
const requestedView = window.location.hash.slice(1);
if (requestedView) {
    document.querySelector('[data-view="' + requestedView + '"]')?.click();
}
