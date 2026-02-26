{literal}
<script>
const AppMethodsOrders = {
    showToast(msg, type = 'success') { this.toast.message = msg; this.toast.type = type; this.toast.show = true; setTimeout(() => { this.toast.show = false; }, 3000); },

    async fetchOrders() { 
        this.loading = true; this.selectedOrders = []; 
        try { 
            const d = await this.apiCall('get_orders');
            if(d && d.success) this.orders = d.orders;
        } catch(e) { console.error(e); } finally { this.loading = false; } 
    },

    async openOrderDetails(id) { 
        const requestId = String(id);
        this._openOrderRequestId = requestId;

        // Otwórz overlay natychmiast (bez czekania na API), żeby nie było wrażenia "przeskoku"/"ładowania listy".
        this.activeMenuId = null;
        this.activeView = 'detail';
        this.currentOrderDetails = null;
        this.loadingDetails = true; 

        this.editingPayment = false; this.generatedLink = null; this.publicLink = null; this.editingDelivery = false; this.editingInvoice = false; this.editingPickup = false; 

        const newUrl = new URL(window.location.href);
        newUrl.searchParams.set('open_order_id', id);
        window.history.pushState({}, '', newUrl);

        try { 
            const d = await this.apiCall('get_order_details', { id_order: id });

            // Jeśli użytkownik zamknął okno lub otworzył inne zamówienie w trakcie ładowania – ignoruj wynik.
            if (this._openOrderRequestId !== requestId || this.activeView !== 'detail') {
                return;
            }

            if (d && d.success) {
                this.currentOrderDetails = d.order;
                this.targetFolder = d.order.virtual_folder || 'Nowe (Do zamówienia)';
                this.fetchDocuments(id);
            } else {
                alert("Błąd: " + (d && d.error ? d.error : 'Nieznany błąd'));
                this.closeDetails();
            }
        } catch (e) { 
            // Jeśli request został unieważniony – nic nie rób.
            if (this._openOrderRequestId !== requestId) return;
            alert("Błąd sieci.");
            this.closeDetails();
        } finally { 
            if (this._openOrderRequestId === requestId) {
                this.loadingDetails = false;
            }
        } 
    },

    closeDetails() { 
        this._openOrderRequestId = null;
        this.activeView = 'list'; this.currentOrderDetails = null; this.detailActionsOpen = false; this.activeProductMenuId = null; 
        const newUrl = new URL(window.location.href); newUrl.searchParams.delete('open_order_id'); window.history.pushState({}, '', newUrl);
    },

    deleteOrder() {
        if(!confirm("CZY JESTEŚ PEWIEN?\n\nTo zamówienie ZNIKNIE CAŁKOWICIE z systemu. Tej operacji nie można cofnąć.")) return;
        this.apiCall('delete_order', { id_order: this.currentOrderDetails.id_order })
            .then(d => {
                if(d && d.success) { alert("Zamówienie zostało trwale usunięte."); this.closeDetails(); this.fetchOrders(); }
                else { alert("Błąd: " + (d && d.error ? d.error : 'Nieznany błąd')); }
            })
            .catch(e => { console.error(e); alert("Wystąpił błąd podczas usuwania."); });
    },

    openArchiveModal() { this.detailActionsOpen = false; this.archiveReason = ''; this.showArchiveModal = true; },
    
    confirmArchive() {
        if(this.archiveReason.trim().length < 3) { alert("Musisz podać powód przeniesienia do archiwum."); return; }
        this.apiCall('archive_order', { id_order: this.currentOrderDetails.id_order, reason: this.archiveReason })
            .then(d => {
                if(d && d.success) { this.showArchiveModal = false; this.showToast("Przeniesiono do archiwum"); this.openOrderDetails(this.currentOrderDetails.id_order); }
                else { alert("Błąd: " + (d && d.error ? d.error : 'Nieznany błąd')); }
            })
            .catch(e => { console.error(e); alert("Błąd sieci."); });
    },

    // NOWA FUNKCJA: Klonowanie zamówienia (puste)
    cloneOrderForCustomer() {
        if(!confirm("Czy chcesz utworzyć NOWE, PUSTE zamówienie dla tego klienta (kopia danych adresowych)?")) return;

        this.apiCall('clone_order', { id_order: this.currentOrderDetails.id_order })
            .then(d => {
                if(d.success) { 
                    alert("Utworzono nowe zamówienie #" + d.reference);
                    this.detailActionsOpen = false;
                    this.fetchOrders(); // Odśwież listę
                    this.openOrderDetails(d.new_id); // Otwórz to nowe zamówienie
                } else {
                    alert("Błąd: " + (d && d.error ? d.error : 'Nieznany błąd'));
                }
            })
            .catch(e => { console.error(e); alert("Błąd sieci."); });
    },

    async moveOrder() { 
        if(!this.currentOrderDetails) return; this.loadingMove = true; const nf = this.targetFolder, id = this.currentOrderDetails.id_order; 
        try { 
            const d = await this.apiCall('update_folder', { id_order: id, folder: nf });
            if (d && d.success) {
                this.currentOrderDetails.virtual_folder = nf;
                await this.openOrderDetails(id);
                this.fetchOrders();
            } else {
                alert("Błąd: " + (d && d.error ? d.error : 'Nieznany błąd'));
            }
        } catch (e) { console.error(e); alert("Błąd serwera."); } finally { this.loadingMove = false; } 
    },

    // UI Helpers
    toggleHistory(s) { this.historyAccordion[s] = !this.historyAccordion[s]; },
    toggleDetailActions() { this.detailActionsOpen = !this.detailActionsOpen; },
    toggleProductMenu(id) { this.activeProductMenuId = this.activeProductMenuId === id ? null : id; },
    sortBy(k) { if (this.sortKey === k) this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc'; else { this.sortKey = k; this.sortOrder = (k === 'carrier_name') ? 'asc' : 'desc'; } },
    setPage(p) { if (p !== '...' && p >= 1 && p <= this.totalPages) this.currentPage = p; },
    nextPage() { if (this.currentPage < this.totalPages) this.currentPage++; },
    prevPage() { if (this.currentPage > 1) this.currentPage--; },
    toggleSelectAll() { if (this.allSelected) this.selectedOrders = []; else this.selectedOrders = Array.from(new Set([...this.selectedOrders, ...this.paginatedOrders.map(o => o.id_order)])); },
    toggleMenu(id) { this.activeMenuId = this.activeMenuId === id ? null : id; },
    closeMenu() { this.activeMenuId = null; this.detailActionsOpen = false; this.activeProductMenuId = null; this.folderSelectOpen = false; this.userMenuOpen = false; },
    getBadgeClass(c, e) { if(c===0) return 'text-slate-300 bg-slate-100'; if(e) return 'text-red-600 bg-red-100'; return 'text-blue-600 bg-blue-100'; },
    getFolderCount(n) { return this.orders ? this.orders.filter(o => o.virtual_folder === n).length : 0; },
    massPacking() { if (this.selectedOrders.length === 0) { alert('Zaznacz zamówienia.'); return; } const firstId = this.selectedOrders[0]; const order = this.orders.find(o => o.id_order == firstId); if (order && order.packing_link) { window.open(order.packing_link + '&order_list=' + this.selectedOrders.join(','), '_blank'); } else { alert('Błąd linku.'); } }
};
</script>
{/literal}