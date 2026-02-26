{literal}
<script>
const AppMethodsLogistics = {
    // --- PACZKOMATY (INPOST) ---
    async openLockerSearch() {
        this.showLockerModal = true;
        this.loadingLockers = true; 
        this.lockersList = []; 
        this.lockerSearchQuery = ''; 
        
        const addr = this.currentOrderDetails.address_delivery;
        
        try { 
            const d = await this.apiCall('search_lockers', {
                city: addr.city,
                postcode: addr.postcode,
                street: addr.address1
            });
            if (d && d.success) this.lockersList = d.points || []; 
            else alert('Błąd API'); 
        } catch(e) { 
            alert('Błąd połączenia.');
        } finally { 
            this.loadingLockers = false; 
        }
    },
    
    onSearchInput() { 
        if (this.searchTimeout) clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(() => { this.searchLockersManual(); }, 1000); 
    },
    
    async searchLockersManual() { 
        if (!this.lockerSearchQuery || this.lockerSearchQuery.length < 3) return;
        this.loadingLockers = true; 
        try { 
            const d = await this.apiCall('search_lockers', { city: this.lockerSearchQuery });
            if (d && d.success) this.lockersList = d.points || []; 
            else this.lockersList = []; 
        } catch(e) { 
            console.error(e);
        } finally { 
            this.loadingLockers = false; 
        } 
    },
    
    selectLocker(code) { 
        this.tempAddress.other = code;
        this.showLockerModal = false; 
    },
    
    // --- EDYCJA ADRESÓW ---
    startEditAddress(type) { 
        this.tempAddress = JSON.parse(JSON.stringify(this.currentOrderDetails[type]));
        if (type === 'address_delivery') this.editingDelivery = true; 
        if (type === 'address_invoice') this.editingInvoice = true;
    },
    
    startEditPickup() { 
        this.tempAddress = JSON.parse(JSON.stringify(this.currentOrderDetails.address_delivery));
        if (this.currentOrderDetails.pickup_point_id && !this.tempAddress.other) { 
            this.tempAddress.other = this.currentOrderDetails.pickup_point_id; 
        } 
        this.editingPickup = true;
    },
    
    cancelEdit(type) { 
        if (type === 'address_delivery') this.editingDelivery = false;
        if (type === 'address_invoice') this.editingInvoice = false; 
        if (type === 'pickup') this.editingPickup = false; 
        this.tempAddress = {};
    },
    
    async saveAddress(type) { 
        this.savingAddress = true;
        const addr = this.tempAddress; 
        
        try { 
            const d = await this.apiCall('update_address_data', {
                id_address: addr.id_address,
                id_order: (this.currentOrderDetails && this.currentOrderDetails.id_order) ? this.currentOrderDetails.id_order : undefined,
                address_type: type,
                firstname: addr.firstname,
                lastname: addr.lastname,
                company: addr.company || '',
                address1: addr.address1,
                postcode: addr.postcode,
                city: addr.city,
                vat_number: addr.vat_number || '',
                other: addr.other || ''
            });
            
            if (d && d.success) { 
                if (d.new_id) { 
                    this.tempAddress.id_address = d.new_id; 
                    addr.id_address = d.new_id;
                } 
                if (type === 'pickup') { 
                    this.currentOrderDetails.address_delivery = { ...this.tempAddress }; 
                    if (this.tempAddress.other) { 
                        this.currentOrderDetails.pickup_point_id = this.tempAddress.other;
                        this.currentOrderDetails.pickup_point_name = 'Zaktualizowany Punkt'; 
                        this.currentOrderDetails.pickup_point_addr = ''; 
                    } 
                    this.editingPickup = false; 
                } else { 
                    this.currentOrderDetails[type] = { ...this.tempAddress };
                    if (type === 'address_delivery') this.editingDelivery = false; 
                    if (type === 'address_invoice') this.editingInvoice = false;
                } 
            } else { 
                alert('Błąd zapisu: ' + (d && d.error ? d.error : 'Nieznany błąd')); 
            } 
        } catch (e) { 
            alert('Błąd sieci.');
        } finally { 
            this.savingAddress = false; 
        } 
    },
    
    // --- NOWE: ALLEGRO WYSYŁKA ---
    openAllegroShipmentModal() {
        if (!this.currentOrderDetails) return;
        
        // Automatyczne wykrywanie trybu (Paczkomat czy Kurier)
        let mode = 'COURIER';
        const cName = (this.currentOrderDetails.carrier_name || '').toLowerCase();
        
        if (this.currentOrderDetails.pickup_point_id || cName.includes('paczkomat') || cName.includes('one box') || cName.includes('automat')) {
            mode = 'BOX';
        }
        
        this.allegroShippingMode = mode;
        
        // --- ZMIANA: Pobranie wagi z sumy produktów (1:1) ---
        let weightOrder = parseFloat(this.currentOrderDetails.total_weight_real);
        // Jeśli waga > 0, użyj jej. Jeśli 0, ustaw 1.0 jako domyślną.
        this.allegroWeight = (weightOrder > 0) ? weightOrder : 1.0;
        
        this.allegroIsSmart = true; // Domyślnie zaznaczony Smart
        this.showAllegroModal = true;
    },

    async createAllegroShipment(sizeCode) {
        // sizeCode = 'A', 'B', 'C' lub null (dla kuriera)
        if (!confirm('Czy na pewno wygenerować etykietę Allegro?')) return;
        
        this.allegroCreating = true;
        const id = this.currentOrderDetails.id_order;
        
        try {
            const params = {
                id_order: id,
                is_smart: (this.allegroIsSmart ? 1 : 0)
            };
            if (this.allegroShippingMode === 'BOX') {
                params.size_code = sizeCode;
            } else {
                params.weight = this.allegroWeight;
            }

            const d = await this.apiCall('create_allegro_shipment', params);
            
            if (d && d.success) {
                this.showToast('Przesyłka utworzona pomyślnie!', 'success');
                this.showAllegroModal = false;
                // Odświeżamy szczegóły zamówienia, aby nowa przesyłka pojawiła się na liście
                this.openOrderDetails(id); 
            } else {
                alert('Błąd Allegro: ' + (d && d.error ? d.error : 'Nieznany błąd'));
            }
        } catch (e) {
            console.error(e);
            alert('Wystąpił błąd połączenia z serwerem.');
        } finally {
            this.allegroCreating = false;
        }
    },
    
    // --- UTILS ---
    copyAddressToClipboard(type) { 
        const addr = this.currentOrderDetails[type];
        if (!addr) return; 
        let text = `${addr.firstname} ${addr.lastname}\n`; 
        if(addr.company) text += `${addr.company}\n`; 
        text += `${addr.address1}\n`; 
        text += `${addr.postcode} ${addr.city}`;
        if(addr.vat_number) text += `\nNIP: ${addr.vat_number}`; 
        if(addr.phone || addr.phone_mobile) text += `\nTel: ${addr.phone_mobile || addr.phone}`;
        navigator.clipboard.writeText(text).then(() => { alert('Skopiowano adres do schowka!'); }); 
    },
    
    async copyDeliveryToInvoice() { 
        if(!confirm('Pobrać dane z ADRESU DOSTAWY do FAKTURY?')) return;
        const src = this.currentOrderDetails.address_delivery; 
        const currentInvoice = this.currentOrderDetails.address_invoice; 
        this.tempAddress = { 
            id_address: currentInvoice.id_address, 
            firstname: src.firstname, 
            lastname: src.lastname, 
            company: src.company, 
            address1: src.address1, 
            postcode: src.postcode, 
            city: src.city, 
            vat_number: currentInvoice.vat_number, 
            other: '' 
        };
        await this.saveAddress('address_invoice'); 
    },
    
    copyAddress(t) { 
        if (t === 'delivery_to_invoice') this.currentOrderDetails.address_invoice = { ...this.currentOrderDetails.address_delivery, vat_number: '' };
        else this.currentOrderDetails.address_delivery = { ...this.currentOrderDetails.address_invoice }; 
    }
};
</script>
{/literal}