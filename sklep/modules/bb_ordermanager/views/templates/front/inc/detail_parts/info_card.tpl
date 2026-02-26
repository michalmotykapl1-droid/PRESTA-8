{* PLIK: modules/bb_ordermanager/views/templates/front/inc/detail_parts/info_card.tpl *}
{literal}
<div class="bg-white rounded shadow-sm border border-slate-200">
    <div class="px-5 py-3 border-b border-slate-200 flex justify-between items-center bg-white rounded-t">
        <h3 class="font-bold text-slate-700 text-[15px]">Informacje o zamówieniu</h3>
        <div class="flex gap-2">
            <button class="bg-white border border-slate-300 text-slate-600 px-3 py-1 rounded text-xs font-bold hover:bg-slate-50 shadow-sm flex items-center gap-1 opacity-60 cursor-not-allowed"><i class="fa-solid fa-print"></i> Wydruki i eksporty <i class="fa-solid fa-caret-down ml-1"></i></button>
            <a :href="currentOrderDetails.packing_link" target="_blank" class="bg-white border border-slate-300 text-emerald-600 px-3 py-1 rounded text-xs font-bold hover:bg-emerald-50 shadow-sm flex items-center gap-1"><i class="fa-solid fa-box"></i> Pakuj</a>
            
            <div class="relative">
                <button @click.stop="toggleDetailActions" class="bg-white border border-slate-300 text-slate-600 px-3 py-1 rounded text-xs font-bold hover:bg-slate-50 hover:text-blue-600 hover:border-blue-400 shadow-sm flex items-center gap-1 transition-colors" :class="{'bg-slate-50 text-blue-600 border-blue-400': detailActionsOpen}">Akcje <i class="fa-solid fa-caret-down ml-1 transition-transform" :class="{'rotate-180': detailActionsOpen}"></i></button>
                
                <div v-if="detailActionsOpen" class="absolute right-0 top-full mt-1 w-72 bg-white rounded-md shadow-xl border border-slate-100 z-50 overflow-hidden animate-in fade-in slide-in-from-top-2 duration-200" @click.stop>
                    <div class="py-1">
                        <a href="#" @click.prevent="openArchiveModal" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 hover:text-blue-600 transition-colors">
                            <i class="fa-solid fa-box-archive w-4 text-center text-slate-400"></i> Przenieś do archiwum
                        </a>
                        
                        <a href="#" @click.prevent="deleteOrder" class="flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors">
                            <i class="fa-regular fa-trash-can w-4 text-center"></i> Usuń zamówienie
                        </a>

                        <div class="border-t border-slate-100 my-1"></div>

                        <a href="#" @click.prevent="cloneOrderForCustomer" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 hover:text-blue-600 transition-colors">
                            <i class="fa-regular fa-user w-4 text-center"></i> Utwórz nowe zamówienie dla tego klienta
                        </a>
                        
                        <div class="border-t border-slate-100 my-1"></div>
                        
                        <a href="#" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-emerald-50 hover:text-emerald-600 transition-colors">
                            <i class="fa-solid fa-truck-fast w-4 text-center"></i> <span class="font-bold">Doślij produkt</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="p-6 flex flex-col md:flex-row gap-10">
        <div class="flex-1 space-y-5 border-r border-slate-100 pr-6">
            <div class="flex flex-col gap-2">
                <div class="flex items-center gap-3 text-sm h-8">
                    <span class="text-slate-500 w-24">Zapłacono:</span>
                    <div v-if="!editingPayment" class="flex items-center flex-wrap gap-2">
                        <span :class="['text-white', 'px-2', 'py-0.5', 'rounded', 'text-xs', 'font-bold', 'shadow-sm', getPaymentInfo(currentOrderDetails).color]">
                            {{ parseFloat(currentOrderDetails.total_paid_real).toFixed(2) }} PLN
                        </span>
                        <span class="text-slate-400 text-xs mr-1">z {{ parseFloat(currentOrderDetails.total_paid).toFixed(2) }}</span>
                        
                        <span v-if="parseFloat(currentOrderDetails.total_paid_real) > parseFloat(currentOrderDetails.total_paid) + 0.01" class="bg-purple-100 text-purple-700 text-xs font-bold px-2 py-0.5 rounded border border-purple-200 ml-1">
                            Do zwrotu: {{ (parseFloat(currentOrderDetails.total_paid_real) - parseFloat(currentOrderDetails.total_paid)).toFixed(2) }} PLN
                        </span>

                        <button v-if="parseFloat(currentOrderDetails.total_paid_real) < parseFloat(currentOrderDetails.total_paid) - 0.01" @click="markAsPaid" class="bg-emerald-500 hover:bg-emerald-600 text-white text-[10px] font-bold px-2 py-0.5 rounded shadow-sm transition-colors" title="Oznacz jako w pełni opłacone"><i class="fa-solid fa-check"></i></button>
                        <button @click="generateP24Link" :disabled="generatingLink" class="bg-indigo-500 hover:bg-indigo-600 text-white text-[10px] font-bold px-2 py-0.5 rounded shadow-sm transition-colors flex items-center gap-1" title="Generuj link do płatności Przelewy24"><i class="fa-solid fa-link" :class="{'spin': generatingLink}"></i> Link P24</button>
                        <button @click="enablePaymentEdit" class="text-slate-400 text-xs border border-slate-200 px-2 py-0.5 rounded bg-white flex items-center gap-1 hover:text-blue-600 hover:border-blue-300 transition-colors" title="Edytuj kwotę ręcznie"><i class="fa-solid fa-pen"></i></button>
                    </div>
 
                    <div v-else class="flex items-center gap-2">
                        <input v-model="newPaymentAmount" type="number" step="0.01" class="w-24 border border-slate-300 rounded px-2 py-0.5 text-xs focus:border-blue-500 focus:outline-none">
                        <button @click="savePayment" class="bg-blue-600 text-white px-2 py-0.5 rounded text-xs hover:bg-blue-700">OK</button>
                        <button @click="editingPayment = false" class="text-slate-400 hover:text-slate-600 text-xs px-1"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                </div>
                <div v-if="generatedLink" class="pl-[108px] mt-1 animate-in fade-in duration-300">
                    <div class="flex items-center gap-2 bg-indigo-50 border border-indigo-200 rounded p-2">
                        <input type="text" :value="generatedLink" class="bg-white border border-indigo-200 rounded text-xs px-2 py-1 w-full text-indigo-700 font-mono" readonly onclick="this.select()">
                        <button onclick="navigator.clipboard.writeText(this.previousElementSibling.value)" class="bg-white border border-indigo-200 text-indigo-600 px-2 py-1 rounded hover:bg-indigo-100 text-xs" title="Kopiuj"><i class="fa-regular fa-copy"></i></button>
                    </div>
                </div>
            </div>

            <div class="space-y-2 text-sm mt-3"><div class="flex"><span class="text-slate-500 w-28">Klient:</span><span class="text-slate-800 font-semibold">{{ currentOrderDetails.customer || 'Gość' }}</span></div><div class="flex"><span class="text-slate-500 w-28">E-mail:</span><a :href="'mailto:'+currentOrderDetails.email" class="text-blue-600 hover:underline">{{ currentOrderDetails.email }}</a></div><div class="flex"><span class="text-slate-500 w-28">Telefon:</span><span class="text-slate-800">{{ currentOrderDetails.mobile || currentOrderDetails.phone || '---' }}</span></div><div class="flex"><span class="text-slate-500 w-28">Źródło:</span><span class="text-slate-800">{{ currentOrderDetails.module }}</span></div></div>
            <div class="border-t border-slate-100 my-2"></div>
            <div class="space-y-2 text-sm"><div class="flex"><span class="text-slate-500 w-28">Wysyłka:</span><span class="text-slate-800 font-bold">{{ currentOrderDetails.carrier_name }}</span></div><div class="flex"><span class="text-slate-500 w-28">Koszt:</span><span class="text-slate-800">{{ parseFloat(currentOrderDetails.total_shipping).toFixed(2) }} PLN</span></div></div>
        </div>

        <div class="flex-1 space-y-6">
            <div class="flex items-center gap-4">
                <span class="text-slate-500 w-16 font-bold text-sm"><i class="fa-solid fa-rotate mr-1"></i> Status:</span>

                <div class="flex-1 flex gap-2 relative">
                        <div @click.stop="folderSelectOpen = !folderSelectOpen" class="flex-1 flex items-center gap-2 border border-slate-300 rounded px-3 py-1.5 bg-white shadow-sm h-9 cursor-pointer hover:bg-slate-50 transition-colors">
                            <div class="w-2.5 h-2.5 rounded-full shrink-0" :style="{backgroundColor: getFolderColor(targetFolder)}"></div>
                        <span class="text-sm font-semibold text-slate-700 truncate select-none">{{ targetFolder }}</span>
                        <i class="fa-solid fa-chevron-down ml-auto text-xs text-slate-400 transition-transform" :class="{'rotate-180': folderSelectOpen}"></i>
                    </div>
                    <div v-if="folderSelectOpen" class="absolute left-0 top-full mt-1 w-full bg-white rounded-md shadow-xl border border-slate-200 z-50 max-h-80 overflow-y-auto custom-scrollbar animate-in fade-in slide-in-from-top-2 duration-150" @click.stop>
                        <div v-for="(section, sIndex) in menu" :key="sIndex">
                            <div v-if="section.title" class="px-3 py-1.5 bg-slate-50 text-[10px] font-bold text-slate-400 uppercase tracking-widest border-b border-slate-100 border-t first:border-t-0">{{ section.title }}</div>
                            <ul>
                                <li v-for="item in section.items" @click="targetFolder = item.label; folderSelectOpen = false" class="px-3 py-2 flex items-center gap-3 cursor-pointer hover:bg-blue-50 transition-colors" :class="{'bg-blue-50': targetFolder === item.label}">
                                    <div class="w-2.5 h-2.5 rounded-full shadow-sm shrink-0" :style="{backgroundColor: item.color_hex || '#cbd5e1'}"></div>
                                    <span class="text-sm text-slate-700 font-medium">{{ item.label }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <button @click="moveOrder" class="border border-blue-300 bg-blue-50 text-blue-700 px-4 rounded hover:bg-blue-100 text-sm font-bold transition-colors w-24 flex justify-center items-center h-9" :disabled="loadingMove"><span v-if="!loadingMove">Przenieś</span><i v-else class="fa-solid fa-circle-notch spin"></i></button>
                </div>
            </div>

            {/literal}{include file='module:bb_ordermanager/views/templates/front/inc/doc_buttons.tpl'}{literal}
            
            <div class="border-t border-slate-100 my-2"></div>
    
            <div class="space-y-1 text-xs text-slate-500"><div class="flex justify-between"><span>Data złożenia:</span><span class="text-slate-800 font-mono">{{ currentOrderDetails.date_add }}</span></div><div class="flex justify-between"><span>Ostatnia zmiana:</span><span class="text-slate-800 font-mono">{{ currentOrderDetails.date_upd || currentOrderDetails.date_add }}</span></div></div>
        </div>
    </div>
</div>
{/literal}