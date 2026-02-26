{literal}
<script>
const AppData = {
    // --- AUTH (logowanie pracownika do modułu) ---
    authChecked: false,
    authLogged: false,
    employee: null,
    csrfToken: '',
    loginEmail: '',
    loginPassword: '',
    loginError: '',
    loggingIn: false,
    userMenuOpen: false,

    // Stan aplikacji
    loading: false, loadingDetails: false, loadingMove: false, errorMsg: null,
    activeView: 'list', currentOrderDetails: null, 
    
    // Filtrowanie i sortowanie
    searchQuery: '',
    activeFolder: (typeof BBOM_MENU !== 'undefined' && Array.isArray(BBOM_MENU) && BBOM_MENU.length && BBOM_MENU[0].items && BBOM_MENU[0].items.length
        ? BBOM_MENU[0].items[0].label
        : 'Nowe (Do zamówienia)'),
    targetFolder: '',
    selectedOrders: [], orders: [],
    currentPage: 1, itemsPerPage: 25, itemsPerPageOptions: [15, 25, 50, 100, 300, 500],
    sortKey: 'date_add', sortOrder: 'desc',
    
    // UI States
    detailActionsOpen: false, activeProductMenuId: null, folderSelectOpen: false,
    activeMenuId: null,
    historyAccordion: { fees: false, payments: true, status: true, changes: true, auto: false, pack: false },
    toast: { show: false, message: '', type: 'success' },

    // Edycja adresów
    editingDelivery: false, editingInvoice: false, editingPickup: false, 
    tempAddress: {}, savingAddress: false,
    
    // Paczkomaty (Modal)
    showLockerModal: false, loadingLockers: false, lockersList: [], lockerSearchQuery: '', searchTimeout: null,
    
    // Produkty (Modale)
    showAddProductModal: false, showEditProductModal: false, 
    productSearchQuery: '', searchResults: [], 
    editingProductData: { id_detail: 0, name: '', qty: 1, price_net: "0.00", tax_rate: 23, price_gross: "0.00" },

    // Płatności
    showPaymentConfirmModal: false, confirmPaymentAmount: "0.00",
    editingPayment: false, newPaymentAmount: 0, generatedLink: null, generatingLink: false, publicLink: null,

    // Archiwizacja i Usuwanie
    showArchiveModal: false, archiveReason: '',

    // Dokumenty (Fakturownia)
    orderDocuments: [], docsLoading: false, docsProcessing: false, processingType: null,
    fvApiUrl: FV_API_URL, 
    
    // --- NOWE: ALLEGRO WYSYŁKA ---
    showAllegroModal: false,
    allegroShippingMode: 'BOX', // BOX lub COURIER
    allegroCreating: false,
    allegroWeight: 1.0,
    allegroIsSmart: true,
    
    // Struktura Menu (Foldery)
    // Dynamicznie z konfiguracji (BBOM_MENU). Fallback na stałą listę, jeśli zmienna nie istnieje.
    menu: (typeof BBOM_MENU !== 'undefined' && Array.isArray(BBOM_MENU) ? BBOM_MENU : [
        { title: "1. ETAP: ZAMAWIANIE", total: 0, items: [ { label: "Nowe (Do zamówienia)", color: "bg-blue-600" }, { label: "Nieopłacone", color: "bg-slate-400" }, { label: "Do wyjaśnienia", color: "bg-red-500", isError: true } ] },
        { title: "⭐ PRIORYTETY LOKALNE", total: 0, items: [ { label: "Odbiór osobisty", color: "bg-violet-500" }, { label: "Dostawa do klienta", color: "bg-sky-500" } ] },
        { title: "2. ETAP: PAKOWANIE (DZIŚ)", total: 0, items: [ { label: "MAGAZYN (Własne)", color: "bg-indigo-600" }, { label: "BP", color: "bg-emerald-500" }, { label: "BP(1 poz) - Szybkie", color: "bg-emerald-400" }, { label: "EKOWITAL", color: "bg-emerald-500" }, { label: "EKOWITAL(1 poz)", color: "bg-emerald-400" }, { label: "BP + EKOWITAL", color: "bg-emerald-500" }, { label: "BP + EKO <10", color: "bg-emerald-400" }, { label: "NATURA", color: "bg-emerald-500" }, { label: "STEWIARNIA", color: "bg-emerald-500" }, { label: "MIX", color: "bg-orange-400" }, { label: "MIX < 10", color: "bg-orange-300" } ] },
        { title: "3. ETAP: OCZEKUJE NA DOSTAWĘ", total: 0, items: [ { label: "Dostawa: JUTRO", color: "bg-yellow-400" }, { label: "Dostawa: POJUTRZE", color: "bg-yellow-200" }, { label: "Czeka na brakujący towar", color: "bg-purple-500" } ] },
        { title: "↩️ ZWROTY I REKLAMACJE", total: 0, items: [ { label: "Zwroty (Do obsługi)", color: "bg-pink-500", isError: true }, { label: "Reklamacje (Uszkodzenia)", color: "bg-pink-600" } ] },
        { title: "🚫 ANULOWANE / ARCHIWUM", total: 0, items: [ { label: "Anulowane (Klient)", color: "bg-gray-800" }, { label: "Anulowane (Sklep)", color: "bg-gray-800" }, { label: "Spakowane / Gotowe", color: "bg-teal-600" }, { label: "Wysłane (Historia)", color: "bg-slate-300" }, { label: "Archiwum", color: "bg-slate-500" } ] }
    ])
};
</script>
{/literal}