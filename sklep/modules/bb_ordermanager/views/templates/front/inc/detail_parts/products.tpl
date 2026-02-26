<div class="bg-white rounded shadow-sm border border-slate-200">
    <div class="px-6 py-2 bg-slate-50 border-b border-slate-200 flex items-center text-slate-500 text-[11px] font-bold uppercase tracking-wide">
        <div class="w-16 shrink-0 mr-4"></div>
        <div class="w-16 shrink-0 mr-4">ID Prod.</div>
        <div class="flex-1 min-w-0">Nazwa produktu</div>
        <div class="w-16 text-center">Ilość</div>
        
        <div class="w-24 text-right">Netto</div>
        <div class="w-12 text-right">VAT</div>
        <div class="w-24 text-right">Brutto</div>
        <div class="w-24 text-right">Suma</div>
        
        <div class="w-20 text-right">Waga</div>
        <div class="w-24 text-right">Data</div>
        <div class="w-12 text-right">Akcje</div>
    </div>

    <div v-for="prod in currentOrderDetails.products" :key="prod.product_id" class="flex items-center border-b border-slate-100 last:border-0 hover:bg-[#f8faff] transition-colors py-3 px-6 group relative text-[13px] text-slate-600 font-normal">
        
        <div class="w-16 h-16 bg-white border border-slate-200 rounded-sm shrink-0 mr-4 flex items-center justify-center p-1 group-hover:border-blue-200 transition-colors">
            <img v-if="prod.image_url" :src="prod.image_url" class="max-w-full max-h-full object-contain">
            <i v-else class="fa-regular fa-image text-slate-300 text-2xl"></i>
        </div>

        <div class="w-16 shrink-0 mr-4 text-blue-600 text-xs">{{ prod.product_id }}</div>

        <div class="flex-1 min-w-0 pr-4">
            <div class="text-slate-800 mb-1 leading-tight truncate" :title="prod.product_name">
                {{ prod.product_name }}
            </div>
            <div class="flex flex-wrap gap-x-4 gap-y-1 text-[11px] text-slate-400 font-mono">
                <span v-if="prod.product_reference">SKU: <span class="text-slate-600">{{ prod.product_reference }}</span></span>
                <span v-if="prod.product_ean13 && prod.product_ean13 != '0'">| EAN: <span class="text-slate-600">{{ prod.product_ean13 }}</span></span>
            </div>
        </div>
        
        <div class="w-16 text-center text-slate-800">
            {{ prod.product_quantity }}
        </div>
        
        <div class="w-24 text-right">
            {{ parseFloat(prod.unit_price_tax_excl).toFixed(2) }}
        </div>

        <div class="w-12 text-right text-slate-400 text-xs">
            {{ parseInt(prod.tax_rate) }}%
        </div>

        <div class="w-24 text-right text-slate-800">
            {{ parseFloat(prod.unit_price_tax_incl).toFixed(2) }}
        </div>

        <div class="w-24 text-right text-slate-900">
            {{ parseFloat(prod.total_price_tax_incl).toFixed(2) }} PLN
        </div>

        <div class="w-20 text-right text-slate-400 text-xs">
            {{ parseFloat(prod.product_weight) > 0 ? parseFloat(prod.product_weight).toFixed(3) : '-' }}
        </div>

        <div class="w-24 text-right text-[11px] text-slate-400 leading-tight">
            {{ prod.date_add.split(' ')[0] }}<br>{{ prod.date_add.split(' ')[1] }}
        </div>
        
        <div class="w-12 flex justify-end relative">
            <button @click.stop="toggleProductMenu(prod.product_id)" class="w-8 h-8 rounded-full hover:bg-slate-100 text-blue-600 flex items-center justify-center border border-transparent hover:border-blue-200 transition-all">
                <i class="fa-solid fa-ellipsis-vertical"></i>
            </button>
            
            <div v-if="activeProductMenuId === prod.product_id" class="absolute right-0 top-full mt-1 w-40 bg-white rounded-md shadow-xl border border-slate-100 z-50 overflow-hidden animate-in fade-in slide-in-from-top-1 duration-150" @click.stop>
                <div class="py-1">
                    <button @click="openEditProduct(prod)" class="w-full text-left flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 hover:text-blue-600 transition-colors">
                        <i class="fa-solid fa-pen w-4 text-center text-xs"></i> Edytuj
                    </button>
                    <button @click="deleteProduct(prod.id_order_detail)" class="w-full text-left flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 hover:text-red-600 transition-colors">
                        <i class="fa-solid fa-trash-can w-4 text-center text-xs"></i> Usuń
                    </button>
                    <button class="w-full text-left flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 hover:text-blue-600 transition-colors opacity-50 cursor-not-allowed">
                        <i class="fa-solid fa-copy w-4 text-center text-xs"></i> Duplikuj
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="bg-slate-50 px-6 py-3 border-t border-slate-200 flex justify-between items-center text-[13px]">
        <button @click="openAddProduct" class="text-blue-600 hover:text-blue-800 text-xs font-bold flex items-center gap-1 transition-colors">
            <i class="fa-solid fa-plus"></i> Dodaj produkty do zamówienia...
        </button>
        <div class="text-right flex items-center gap-6">
            <div class="text-slate-500 uppercase tracking-wide">Waga: <span class="text-slate-700">{{ totalWeight }} kg</span></div>
            <div class="text-slate-500 uppercase tracking-wide">Dostawa: <span class="text-slate-700">{{ parseFloat(currentOrderDetails.total_shipping).toFixed(2) }} PLN</span></div>
            <div class="text-slate-800 font-bold">Razem: <span class="text-black">{{ parseFloat(currentOrderDetails.total_paid).toFixed(2) }} PLN</span></div>
        </div>
    </div>
</div>