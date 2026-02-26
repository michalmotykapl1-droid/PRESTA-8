{literal}
<script>
const AppMethodsFinance = {
    async generatePublicLink() {
        if(!this.currentOrderDetails) return;
        const id = this.currentOrderDetails.id_order;
        try {
            const d = await this.apiCall('get_public_link', { id_order: id });
            if(d && d.success) {
                this.publicLink = d.link;
                navigator.clipboard.writeText(d.link);
                alert("Link skopiowany do schowka:\n" + d.link);
            }
        } catch(e) { console.error(e); }
    },
    
    async generateP24Link() {
        if(!this.currentOrderDetails) return;
        let total = parseFloat(this.currentOrderDetails.total_paid);
        let paid = parseFloat(this.currentOrderDetails.total_paid_real);
        let diff = total - paid;
        if(diff <= 0) diff = 0;
        let userAmount = prompt("Wpisz kwotę do linku P24 (PLN):", diff > 0 ? diff.toFixed(2) : "1.00");
        if (userAmount === null) return;
        userAmount = userAmount.replace(',', '.');
        let amountFloat = parseFloat(userAmount);
        if (isNaN(amountFloat) || amountFloat <= 0.01) {
            alert("Podano nieprawidłową kwotę.");
            return;
        }
        this.generatingLink = true;
        const id = this.currentOrderDetails.id_order;
        try {
            const d = await this.apiCall('generate_p24_link', { id_order: id, amount: amountFloat });
            if(d && d.success) {
                this.generatedLink = d.link;
            } else {
                alert("Błąd P24: " + (d && d.error ? d.error : 'Nieznany błąd'));
            }
        } catch(e) {
            console.error(e);
            alert("Błąd sieci.");
        } finally {
            this.generatingLink = false;
        }
    },
    
    enablePaymentEdit() { this.newPaymentAmount = parseFloat(this.currentOrderDetails.total_paid_real); this.editingPayment = true; },
    
    async savePayment() {
        if(!this.currentOrderDetails) return;
        const id = this.currentOrderDetails.id_order;
        try {
            const d = await this.apiCall('update_payment', { id_order: id, amount: this.newPaymentAmount });
            if(d && d.success) {
                this.currentOrderDetails.total_paid_real = this.newPaymentAmount;
                this.editingPayment = false;
            } else {
                alert(d && d.error ? d.error : 'Błąd');
            }
        } catch(e) { console.error(e); }
    },
    
    markAsPaid() { if(!this.currentOrderDetails) return; this.confirmPaymentAmount = parseFloat(this.currentOrderDetails.total_paid).toFixed(2); this.showPaymentConfirmModal = true; },
    
    async confirmMarkAsPaid() {
        this.showPaymentConfirmModal = false; const id = this.currentOrderDetails.id_order; const total = parseFloat(this.currentOrderDetails.total_paid);
        try {
            const d = await this.apiCall('update_payment', { id_order: id, amount: total });
            if(d && d.success) {
                this.currentOrderDetails.total_paid_real = total;
                this.showToast('Opłacono w całości');
            } else {
                alert(d && d.error ? d.error : 'Błąd');
            }
        } catch(e) { console.error(e); }
    },
    
    getPaymentInfo(order) { if (!order) return { color: 'bg-slate-300', text: '...' }; const paid = parseFloat(order.total_paid_real || 0); const total = parseFloat(order.total_paid || 0); if (paid >= total - 0.05) return { color: 'bg-green-600', text: 'Opłacone' }; else if (paid > 0) return { color: 'bg-orange-500', text: 'Niedopłata' }; else return { color: 'bg-red-500', text: 'Nieopłacone' }; },
    
    getPaymentIconClass(order) { if (!order) return 'text-slate-300'; const paid = parseFloat(order.total_paid_real || 0); const total = parseFloat(order.total_paid || 0); if (paid >= total - 0.05) return 'text-green-600'; if (paid > 0) return 'text-orange-500'; return 'text-red-500'; },
    
    isPaid(o) { if (!o) return false; return (parseFloat(o.total_paid_real || 0) >= parseFloat(o.total_paid || 0) - 0.01); },

    // Dokumenty
    getManualInvoiceUrl() {
        if (!this.currentOrderDetails || !this.orderDocuments || this.orderDocuments.length === 0) return '#';
        const baseDomain = this.orderDocuments[0].view_url.split('/invoices/')[0];
        const buyer = this.currentOrderDetails.address_invoice;
        const params = new URLSearchParams();
        params.append('kind', 'vat');
        params.append('invoice[buyer_name]', (buyer.company ? buyer.company : buyer.firstname + ' ' + buyer.lastname));
        params.append('invoice[buyer_tax_no]', buyer.vat_number || '');
        params.append('invoice[buyer_post_code]', buyer.postcode);
        params.append('invoice[buyer_city]', buyer.city);
        params.append('invoice[buyer_street]', buyer.address1);
        if (this.currentOrderDetails.products) {
            this.currentOrderDetails.products.forEach((prod, index) => {
                params.append(`invoice[positions][${index}][name]`, prod.product_name);
                params.append(`invoice[positions][${index}][quantity]`, prod.product_quantity);
                params.append(`invoice[positions][${index}][quantity_unit]`, 'szt');
                params.append(`invoice[positions][${index}][total_price_gross]`, prod.total_price_tax_incl);
                params.append(`invoice[positions][${index}][tax]`, parseInt(prod.tax_rate));
            });
        }
        return `${baseDomain}/invoices/new?${params.toString()}`;
    },
    
    async fetchDocuments(orderId) {
        this.docsLoading = true; this.orderDocuments = [];
        try { const response = await fetch(this.fvApiUrl + '&action=get_documents&id_order=' + orderId); const text = await response.text(); const d = JSON.parse(text); if (d.success) this.orderDocuments = d.documents; } catch (e) { console.error('Doc Error:', e); } finally { this.docsLoading = false; }
    },
    
    async issueDocument(type, parentId = null) {
        if (!confirm('Czy na pewno chcesz wystawić ten dokument?')) return;
        this.docsProcessing = true; this.processingType = type;
        let url = this.fvApiUrl + '&action=create_document&id_order=' + this.currentOrderDetails.id_order + '&kind=' + type; if (parentId) url += '&parent_id=' + parentId;
        try { const response = await fetch(url); const text = await response.text(); const d = JSON.parse(text); if (d.success) { this.showToast('Dokument wystawiony: ' + d.document.number); this.fetchDocuments(this.currentOrderDetails.id_order); } else { this.showToast('Błąd: ' + d.error, 'error'); } } catch (e) { this.showToast('Błąd połączenia', 'error'); } finally { this.docsProcessing = false; this.processingType = null; }
    }
};
</script>
{/literal}