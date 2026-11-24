// SWEET SPOT ADMIN — FRONT-END PREVIEW ONLY
const sampleProducts = [
{id:1,name:"Butter Croissant",category:"Pastries",price:85,status:"Available",image:"https://images.unsplash.com/photo-1555507036-ab1f4038808a?auto=format&fit=crop&w=160&q=80"},
{id:2,name:"Classic Ensaymada",category:"Bread & Buns",price:65,status:"Available",image:"https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=160&q=80"},
{id:3,name:"Chocolate Danish",category:"Pastries",price:95,status:"Available",image:"https://images.unsplash.com/photo-1519915028121-7d3463d5b3b7?auto=format&fit=crop&w=160&q=80"},
{id:4,name:"Cinnamon Swirl",category:"Pastries",price:90,status:"Low stock",image:"https://images.unsplash.com/photo-1509365465985-25d11c17e812?auto=format&fit=crop&w=160&q=80"},
{id:5,name:"Strawberry Shortcake",category:"Cakes",price:180,status:"Available",image:"https://images.unsplash.com/photo-1578985545062-69928b1d9587?auto=format&fit=crop&w=160&q=80"},
{id:6,name:"Country Sourdough",category:"Bread & Buns",price:160,status:"Available",image:"https://images.unsplash.com/photo-1585478259715-876acc5be8eb?auto=format&fit=crop&w=160&q=80"}
];
let nextProductId = 7;
const tableBody = document.querySelector("#product-table-body");
const productModal = document.querySelector("#product-modal");
const productForm = document.querySelector("#product-form");
const toast = document.querySelector("#admin-toast");

function showToast(message){
    toast.textContent = message;
    toast.classList.add("show");
    window.setTimeout(()=>toast.classList.remove("show"),2600);
}
function escapeHTML(value){
    return String(value).replace(/[&<>"']/g, char => ({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#039;"}[char]));
}
function money(value){return "₱"+Number(value).toLocaleString("en-PH",{minimumFractionDigits:2,maximumFractionDigits:2});}
function renderProducts(){
    const query=(document.querySelector("#product-search")?.value||"").trim().toLowerCase();
    const category=document.querySelector("#category-filter")?.value||"all";
    const visible=sampleProducts.filter(product=>(category==="all"||product.category===category)&&product.name.toLowerCase().includes(query));
    tableBody.innerHTML=visible.map(product=>`
        <tr data-product-id="${product.id}">
            <td><div class="table-product"><img src="${escapeHTML(product.image||"")}" alt=""><div><strong>${escapeHTML(product.name)}</strong><small>SKU-SS-${String(product.id).padStart(3,"0")}</small></div></div></td>
            <td><span class="category-pill">${escapeHTML(product.category)}</span></td>
            <td class="table-price">${money(product.price)}</td>
            <td><span class="product-status ${product.status==="Available"?"available":product.status==="Low stock"?"low-stock":"unavailable"}"><i></i>${escapeHTML(product.status)}</span></td>
            <td><div class="row-actions"><button type="button" class="row-action edit-product" data-id="${product.id}">Edit</button><button type="button" class="row-action delete-product" data-id="${product.id}">Delete</button></div></td>
        </tr>`).join("");
    document.querySelector("#product-count").textContent="("+sampleProducts.length+")";
    document.querySelector("#stat-products").textContent=String(sampleProducts.length).padStart(2,"0");
    document.querySelector("#table-result-count").textContent="Showing "+visible.length+" of "+sampleProducts.length+" sample products";
    document.querySelector("#recent-product-list").innerHTML=sampleProducts.slice(-4).reverse().map(product=>`
        <div class="mini-product-row"><img src="${escapeHTML(product.image||"")}" alt=""><div class="mini-product-info"><strong>${escapeHTML(product.name)}</strong><small>${escapeHTML(product.category)}</small></div><b>${money(product.price)}</b></div>`).join("");
}
function openProductModal(product=null){
    productForm.reset();
    document.querySelector("#edit-product-id").value=product?.id||"";
    document.querySelector("#modal-title").textContent=product?"Edit product":"Add a product";
    document.querySelector("#product-name").value=product?.name||"";
    document.querySelector("#product-category").value=product?.category||"";
    document.querySelector("#product-price").value=product?.price??"";
    document.querySelector("#product-description").value=product?.description||"";
    document.querySelector("#product-image").value=product?.image||"";
    document.querySelector("#product-availability").value=product?.status||"Available";
    productModal.classList.add("is-open");
    productModal.setAttribute("aria-hidden","false");
    document.body.style.overflow="hidden";
    document.querySelector("#product-name").focus();
}
function closeProductModal(){
    productModal.classList.remove("is-open");
    productModal.setAttribute("aria-hidden","true");
    document.body.style.overflow="";
}
document.querySelectorAll("[data-view]").forEach(link=>link.addEventListener("click",event=>{
    event.preventDefault();
    const view=link.dataset.view;
    document.querySelectorAll(".sidebar-link").forEach(item=>item.classList.toggle("active",item.dataset.view===view));
    document.querySelectorAll(".admin-view").forEach(section=>section.classList.toggle("active",section.id==="view-"+view));
    const activeLabel=link.textContent.trim().replace(/\d+/g,"").trim();
    document.querySelector("#breadcrumb-current").textContent=activeLabel;
    document.querySelector("#admin-sidebar").classList.remove("is-open");
    window.location.hash=view;
}));
document.querySelectorAll("[data-view-link]").forEach(button=>button.addEventListener("click",event=>{
    event.preventDefault();
    const view=button.dataset.viewLink;
    const nav=document.querySelector('[data-view="'+view+'"]');
    nav?.click();
}));
document.querySelectorAll("[data-open-product]").forEach(button=>button.addEventListener("click",()=>openProductModal()));
document.querySelectorAll("[data-close-modal]").forEach(button=>button.addEventListener("click",closeProductModal));
productModal.addEventListener("click",event=>{if(event.target===productModal)closeProductModal();});
document.addEventListener("keydown",event=>{if(event.key==="Escape")closeProductModal();});
document.querySelector("#sidebar-toggle").addEventListener("click",()=>document.querySelector("#admin-sidebar").classList.toggle("is-open"));
document.querySelectorAll(".banner-close").forEach(button=>button.addEventListener("click",()=>button.closest(".preview-banner").remove()));
document.querySelector("#product-search").addEventListener("input",renderProducts);
document.querySelector("#category-filter").addEventListener("change",renderProducts);
tableBody.addEventListener("click",event=>{
    const editButton=event.target.closest(".edit-product");
    const deleteButton=event.target.closest(".delete-product");
    if(editButton){
        const product=sampleProducts.find(item=>item.id===Number(editButton.dataset.id));
        if(product)openProductModal(product);
    }
    if(deleteButton){
        const id=Number(deleteButton.dataset.id);
        const product=sampleProducts.find(item=>item.id===id);
        if(product&&window.confirm('Remove "'+product.name+'" from this sample list?')){
            sampleProducts.splice(sampleProducts.indexOf(product),1);
            renderProducts();
            showToast("Sample product removed. This change is not saved.");
        }
    }
});
productForm.addEventListener("submit",event=>{
    event.preventDefault();
    const id=Number(document.querySelector("#edit-product-id").value);
    const image=document.querySelector("#product-image").value.trim();
    const details={
        name:document.querySelector("#product-name").value.trim(),
        category:document.querySelector("#product-category").value,
        price:Number(document.querySelector("#product-price").value),
        description:document.querySelector("#product-description").value.trim(),
        image:image||"https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=160&q=80",
        status:document.querySelector("#product-availability").value
    };
    if(id){
        const product=sampleProducts.find(item=>item.id===id);
        if(product)Object.assign(product,details);
        showToast("Product updated in preview.");
    }else{
        sampleProducts.unshift({id:nextProductId++,...details});
        showToast("Product added to preview.");
    }
    renderProducts();
    closeProductModal();
});
document.querySelectorAll("[data-view]").forEach(link=>{if(link.dataset.view===window.location.hash.slice(1))link.click();});
renderProducts();
