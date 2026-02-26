{* PLIK: modules/bb_ordermanager/views/templates/front/inc/detail_parts/modals.tpl *}
{literal}

<transition name="toast">
    <div v-if="toast.show" class="fixed bottom-10 left-1/2 transform -translate-x-1/2 px-6 py-3 rounded-full shadow-2xl z-[100] flex items-center gap-3 pointer-events-none"
         :class="toast.type === 'error' ? 'bg-red-600 text-white' : 'bg-slate-800 text-white'">
        <i class="fa-solid text-lg" :class="toast.type === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check text-green-400'"></i>
        <span class="font-bold text-sm tracking-wide">{{ toast.message }}</span>
    </div>
</transition>

<div v-if="showLockerModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 backdrop-blur-sm" @click.self="showLockerModal = false">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl h-[80vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-200">
        <div class="p-4 border-b border-slate-200 flex justify-between items-center bg-slate-50">
            <h3 class="font-bold text-slate-700">Wybierz punkt odbioru</h3>
            <button @click="showLockerModal = false" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-xl"></i></button>
        </div>
        <div class="p-4 border-b border-slate-200 bg-white">
            <div class="relative">
                <input v-model="lockerSearchQuery" @input="onSearchInput" type="text" placeholder="Szukaj miasta lub ulicy..." class="w-full border border-slate-300 rounded px-4 py-2 pl-10 focus:border-blue-500 outline-none">
                <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
            </div>
        </div>
        <div class="flex-1 overflow-y-auto p-4 custom-scrollbar bg-slate-50">
            <div v-if="loadingLockers" class="text-center py-10"><i class="fa-solid fa-circle-notch spin text-3xl text-blue-500"></i><p class="mt-2 text-slate-500">Szukam punktów...</p></div>
            <div v-else-if="lockersList.length === 0" class="text-center py-10 text-slate-400">Brak wyników. Wpisz miasto.</div>
            <div v-else class="grid grid-cols-1 gap-3">
                <div v-for="point in lockersList" :key="point.name" @click="selectLocker(point.name)" class="bg-white p-4 rounded border border-slate-200 hover:border-blue-500 cursor-pointer transition-all flex items-center gap-4 group">
                    <img :src="point.image" class="w-12 h-12 object-contain rounded bg-slate-50 p-1">
                    <div class="flex-1">
                        <div class="font-bold text-slate-800 flex justify-between">
                            <span>{{ point.name }}</span>
                            <span class="text-blue-600 font-normal text-xs bg-blue-50 px-2 py-0.5 rounded">{{ point.distance }} km</span>
                        </div>
                        <div class="text-sm text-slate-600">{{ point.address }}</div>
                        <div class="text-xs text-slate-400 mt-1">{{ point.desc }}</div>
                    </div>
                    <i class="fa-solid fa-chevron-right text-slate-300 group-hover:text-blue-500"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div v-if="showAddProductModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 backdrop-blur-sm" @click.self="showAddProductModal = false">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl max-h-[85vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-200">
        <div class="p-5 border-b border-slate-200 flex justify-between items-center bg-white shrink-0">
            <h3 class="font-bold text-slate-700 text-lg">Dodaj produkt do zamówienia</h3>
            <button @click="showAddProductModal = false" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-xl"></i></button>
        </div>
        
        <div class="p-5 border-b border-slate-200 bg-slate-50 shrink-0">
            <div class="relative">
                <input v-model="productSearchQuery" 
                       @input="onProductSearchInput"
                       @keyup.enter="searchProducts" 
                       type="text" 
                       placeholder="Wpisz nazwę, EAN, SKU lub producenta..." 
                       class="w-full border border-slate-300 rounded-lg px-4 py-3 pl-11 focus:border-blue-500 outline-none shadow-sm text-sm font-medium"
                       autofocus>
                <i class="fa-solid fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                
                <div v-if="searchResults.length === 0 && productSearchQuery.length > 2" class="absolute right-3 top-1/2 -translate-y-1/2 text-blue-500">
                    <i class="fa-solid fa-circle-notch spin"></i>
                </div>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-4 custom-scrollbar bg-slate-100">
            <div v-if="searchResults.length === 0" class="text-center py-10 text-slate-400 flex flex-col items-center">
                <i class="fa-solid fa-magnifying-glass text-3xl mb-2 opacity-50"></i>
                <span v-if="productSearchQuery.length < 3">Zacznij pisać (min. 3 znaki)...</span>
                <span v-else>Szukam...</span>
            </div>
            
            <div v-else class="space-y-3">
                <div v-for="prod in searchResults" :key="prod.id" class="flex items-center gap-4 bg-white p-3 rounded-lg border border-slate-200 hover:border-blue-400 hover:shadow-md transition-all group">
                    
                    <div class="w-16 h-16 bg-white border border-slate-200 rounded shrink-0 flex items-center justify-center p-1 overflow-hidden relative">
                        <img v-if="prod.image_url" :src="prod.image_url" class="max-w-full max-h-full object-contain">
                        <i v-else class="fa-regular fa-image text-slate-300 text-2xl"></i>
                    </div>

                    <div class="flex-1 min-w-0 flex flex-col justify-center">
                        <div v-if="prod.manufacturer" class="text-[10px] uppercase font-extrabold text-slate-400 mb-0.5 tracking-wide">
                            {{ prod.manufacturer }}
                        </div>
                        
                        <div class="font-bold text-slate-800 text-sm leading-tight mb-1.5 group-hover:text-blue-600 transition-colors">
                            {{ prod.name }}
                        </div>
                        
                        <div class="flex flex-wrap gap-2 text-[10px] text-slate-500 font-mono items-center">
                            <span v-if="prod.ref" class="bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200 text-slate-600">Ref: {{ prod.ref }}</span>
                            <span v-if="prod.ean && prod.ean != '0'" class="bg-yellow-50 px-1.5 py-0.5 rounded border border-yellow-200 text-yellow-700 font-bold flex items-center gap-1">
                                <i class="fa-solid fa-barcode"></i> {{ prod.ean }}
                            </span>
                        </div>
                    </div>

                    <div class="text-right shrink-0 flex items-center gap-4 border-l border-slate-100 pl-4">
                        <div>
                            <div class="font-bold text-slate-800">{{ prod.price_gross }} <span class="text-xs font-normal text-slate-500">PLN</span></div>
                            <div class="text-[10px] text-slate-400">brutto</div>
                        </div>
                        <button @click="selectProductToAdd(prod)" class="w-10 h-10 rounded-full bg-blue-50 text-blue-600 border border-blue-100 hover:bg-blue-600 hover:text-white flex items-center justify-center transition-all shadow-sm transform group-hover:scale-110" title="Dodaj do zamówienia">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div v-if="showEditProductModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 backdrop-blur-sm" @click.self="showEditProductModal = false">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in-95 duration-200">
        <div class="p-4 border-b border-slate-200 flex justify-between items-center bg-slate-50">
            <h3 class="font-bold text-slate-700">Edycja pozycji</h3>
            <button @click="showEditProductModal = false" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-xl"></i></button>
        </div>
        <div class="p-6 space-y-4">
            <div><label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Nazwa produktu</label><div class="text-sm font-semibold text-slate-800 bg-slate-50 p-2 rounded border border-slate-200 leading-tight">{{ editingProductData.name }}</div></div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Ilość</label><input type="number" v-model="editingProductData.qty" class="w-full border border-slate-300 rounded px-3 py-2 focus:border-blue-500 outline-none font-bold text-center"></div>
                <div><label class="block text-xs font-bold text-slate-500 mb-1 uppercase">VAT (%)</label><input type="number" v-model="editingProductData.tax_rate" @input="updateGross" class="w-full border border-slate-300 rounded px-3 py-2 focus:border-blue-500 outline-none text-center"></div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Cena Netto</label><input type="number" step="0.01" v-model="editingProductData.price_net" @input="updateGross" @blur="formatPrice('price_net')" class="w-full border border-slate-300 rounded px-3 py-2 focus:border-blue-500 outline-none text-right"></div>
                <div><label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Cena Brutto</label><input type="number" step="0.01" v-model="editingProductData.price_gross" @input="updateNet" @blur="formatPrice('price_gross')" class="w-full border border-slate-300 rounded px-3 py-2 focus:border-blue-500 outline-none font-bold text-blue-600 text-right"></div>
            </div>
            <button @click="saveProduct" class="w-full bg-blue-600 text-white font-bold py-3 rounded hover:bg-blue-700 transition-colors shadow-sm mt-2">Zapisz zmiany</button>
        </div>
    </div>
</div>

<div v-if="showPaymentConfirmModal" class="fixed inset-0 bg-black/60 z-[60] flex items-center justify-center p-4 backdrop-blur-sm" @click.self="showPaymentConfirmModal = false">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm overflow-hidden animate-in fade-in zoom-in-95 duration-200">
        <div class="p-6 text-center">
            <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl"><i class="fa-solid fa-check"></i></div>
            <h3 class="text-xl font-bold text-slate-800 mb-2">Potwierdź wpłatę</h3>
            <p class="text-slate-500 text-sm mb-6">Czy na pewno chcesz oznaczyć to zamówienie jako opłacone kwotą <strong class="text-slate-800">{{ confirmPaymentAmount }} PLN</strong>?</p>
            <div class="flex gap-3">
                <button @click="showPaymentConfirmModal = false" class="flex-1 py-2.5 border border-slate-300 text-slate-600 font-bold rounded-lg hover:bg-slate-50 transition-colors">Anuluj</button>
                <button @click="confirmMarkAsPaid" class="flex-1 py-2.5 bg-emerald-500 text-white font-bold rounded-lg hover:bg-emerald-600 shadow-md transition-colors">Zatwierdź</button>
            </div>
        </div>
    </div>
</div>

<div v-if="showArchiveModal" class="fixed inset-0 bg-black/60 z-[60] flex items-center justify-center p-4 backdrop-blur-sm" @click.self="showArchiveModal = false">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in-95 duration-200">
        <div class="bg-slate-800 text-white p-4 flex justify-between items-center">
            <h3 class="font-bold flex items-center gap-2"><i class="fa-solid fa-box-archive text-slate-400"></i> Archiwizacja</h3>
            <button @click="showArchiveModal = false" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="p-6">
            <p class="text-sm text-slate-600 mb-4 font-medium">Podaj powód przeniesienia do archiwum (wymagane):</p>
            <textarea v-model="archiveReason" class="w-full border border-slate-300 rounded-lg p-3 text-sm focus:border-blue-500 outline-none h-24 resize-none mb-4 placeholder-slate-400" placeholder="np. dubel, anulowane przez klienta, test..."></textarea>
            <button @click="confirmArchive" class="w-full bg-slate-700 text-white font-bold py-3 rounded-lg hover:bg-slate-600 transition-colors shadow-sm">Przenieś do Archiwum</button>
        </div>
    </div>
</div>

{/literal}