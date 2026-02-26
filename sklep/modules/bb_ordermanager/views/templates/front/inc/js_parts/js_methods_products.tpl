{literal}
<script>
const AppMethodsProducts = {
    openAddProduct() { 
        this.productSearchQuery = ''; 
        this.searchResults = []; 
        this.showAddProductModal = true; 
        // Focus na polu input po otwarciu (opcjonalnie, wymaga ref)
    },
    
    // NOWA FUNKCJA: Opóźnienie wyszukiwania (Debounce)
    onProductSearchInput() {
        // Jeśli użytkownik nadal pisze, czyścimy poprzedni licznik
        if (this.searchTimeout) clearTimeout(this.searchTimeout);
        
        // Jeśli wpisał mniej niż 3 znaki, nie szukamy i czyścimy wyniki
        if (this.productSearchQuery.length < 3) {
            this.searchResults = [];
            return;
        }

        // Ustawiamy nowe opóźnienie 500ms (pół sekundy)
        this.searchTimeout = setTimeout(() => {
            this.searchProducts();
        }, 500);
    },

    async searchProducts() {
        if(this.productSearchQuery.length < 3) return;
        
        // Opcjonalnie: pokaż loading
        // this.loadingProducts = true; 
        
        const d = await this.apiCall('search_products', { query: this.productSearchQuery });
        if(d && d.success) this.searchResults = d.products;
    },
    
    selectProductToAdd(prod) {
        const qty = prompt("Podaj ilość:", "1"); 
        if(!qty) return;

        this.apiCall('add_product_to_order', {
            id_order: this.currentOrderDetails.id_order,
            id_product: prod.id,
            qty: parseInt(qty)
        }).then(d => {
            if(d && d.success) { 
                this.showAddProductModal = false; 
                this.openOrderDetails(this.currentOrderDetails.id_order); 
                this.showToast("Produkt dodany"); 
            } else {
                alert(d && d.error ? d.error : 'Błąd');
            }
        });
    },
    
    openEditProduct(prod) {
        this.editingProductData = { 
            id_detail: prod.id_order_detail, 
            name: prod.product_name, 
            qty: parseInt(prod.product_quantity), 
            price_net: parseFloat(prod.unit_price_tax_excl).toFixed(2), 
            tax_rate: parseFloat(prod.tax_rate), 
            price_gross: parseFloat(prod.unit_price_tax_incl).toFixed(2) 
        };
        this.activeProductMenuId = null; 
        this.showEditProductModal = true;
    },
    
    updateGross() { 
        const net = parseFloat(this.editingProductData.price_net) || 0; 
        const tax = parseFloat(this.editingProductData.tax_rate) || 0; 
        this.editingProductData.price_gross = (net * (1 + tax / 100)).toFixed(2); 
    },
    
    updateNet() { 
        const gross = parseFloat(this.editingProductData.price_gross) || 0; 
        const tax = parseFloat(this.editingProductData.tax_rate) || 0; 
        this.editingProductData.price_net = (gross / (1 + tax / 100)).toFixed(2); 
    },
    
    formatPrice(key) { 
        const val = parseFloat(this.editingProductData[key]); 
        if (!isNaN(val)) this.editingProductData[key] = val.toFixed(2); 
    },
    
    saveProduct() {
        const d = this.editingProductData;
        this.apiCall('update_order_product', {
            id_order_detail: d.id_detail,
            qty: d.qty,
            price_net: d.price_net,
            tax_rate: d.tax_rate
        }).then(res => {
            if(res && res.success) { 
                this.showEditProductModal = false; 
                this.openOrderDetails(this.currentOrderDetails.id_order); 
                this.showToast("Produkt zaktualizowany"); 
            } else {
                alert(res && res.error ? res.error : 'Błąd');
            }
        });
    },
    
    deleteProduct(detailId) {
        if(!confirm("Czy na pewno usunąć ten produkt z zamówienia?")) return;
        this.activeProductMenuId = null;
        this.apiCall('delete_order_product', { id_order_detail: detailId })
            .then(res => {
                if(res && res.success) { 
                    this.openOrderDetails(this.currentOrderDetails.id_order); 
                    this.showToast("Produkt usunięty"); 
                } else {
                    alert(res && res.error ? res.error : 'Błąd');
                }
            });
    }
};
</script>
{/literal}