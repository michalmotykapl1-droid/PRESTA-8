{literal}
<script>
const AppComputed = {
    totalWeight() { 
        if (!this.currentOrderDetails || !this.currentOrderDetails.products) return 0; 
        let w = 0; 
        this.currentOrderDetails.products.forEach(p => { w += (parseFloat(p.product_weight) * parseInt(p.product_quantity)); }); 
        return w.toFixed(3); 
    },
    sortedOrders() {
        let list = this.orders.filter(o => o.virtual_folder === this.activeFolder);
        if (this.searchQuery) { 
            const q = this.searchQuery.toLowerCase(); 
            list = list.filter(o => o.reference.toLowerCase().includes(q) || o.customer.toLowerCase().includes(q) || (o.products && o.products.some(p => p.product_name.toLowerCase().includes(q)))); 
        }
        return list.sort((a, b) => { 
            let mod = this.sortOrder === 'asc' ? 1 : -1; 
            if (this.sortKey === 'date_add') return (new Date(a.date_add) - new Date(b.date_add)) * mod; 
            else if (this.sortKey === 'total_paid') return (parseFloat(a.total_paid) - parseFloat(b.total_paid)) * mod; 
            else if (this.sortKey === 'carrier_name') { 
                let vA = a.carrier_name || '', vB = b.carrier_name || ''; return vA.localeCompare(vB) * mod; 
            } 
            return 0; 
        });
    },
    paginatedOrders() { 
        const s = (this.currentPage - 1) * this.itemsPerPage; 
        return this.sortedOrders.slice(s, s + this.itemsPerPage); 
    },
    totalPages() { return Math.ceil(this.sortedOrders.length / this.itemsPerPage) || 1; },
    visiblePages() { 
        const t = this.totalPages, c = this.currentPage, d = 2, r = [], rd = []; let l; 
        for (let i = 1; i <= t; i++) { if (i == 1 || i == t || (i >= c - d && i <= c + d)) r.push(i); } 
        for (let i of r) { if (l) { if (i - l === 2) rd.push(l + 1); else if (i - l !== 1) rd.push('...'); } rd.push(i); l = i; } 
        return rd; 
    },
    allSelected() { 
        if (this.paginatedOrders.length === 0) return false; 
        return this.paginatedOrders.every(o => this.selectedOrders.includes(o.id_order)); 
    }
};
</script>
{/literal}