{* PLIK: modules/bb_ordermanager/views/templates/front/inc/doc_buttons.tpl *}
{literal}
<div class="space-y-3 text-sm" v-if="currentOrderDetails">
    
    <div v-if="docsLoading" class="text-slate-400 flex items-center gap-2 py-2">
        <i class="fa-solid fa-circle-notch spin text-blue-500"></i> Sprawdzam dokumenty w Fakturowni...
    </div>

    <div v-else-if="orderDocuments && orderDocuments.length > 0" class="space-y-2">
        <div class="flex items-center justify-between">
            <span class="text-slate-500 font-bold text-[10px] uppercase tracking-wide">Wystawione dokumenty:</span>
        </div>
        
        <div v-for="doc in orderDocuments" :key="doc.id" class="flex items-center justify-between bg-white border border-slate-200 rounded p-2 hover:border-blue-300 transition-colors shadow-sm">
            <div class="flex items-center gap-2 overflow-hidden">
                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded uppercase text-white shrink-0"
                      :class="{
                          'bg-emerald-500': doc.kind === 'vat',
                          'bg-orange-400': doc.kind === 'receipt',
                          'bg-red-500': doc.kind === 'correction',
                          'bg-blue-400': doc.kind === 'proforma'
                      }">
                    {{ doc.kind === 'vat' ? 'FV' : (doc.kind === 'correction' ? 'KOR' : (doc.kind === 'receipt' ? 'PAR' : 'PRO')) }}
                </span>
                
                <a :href="doc.view_url" target="_blank" class="font-mono text-xs font-bold text-slate-700 hover:text-blue-600 truncate" title="Podgląd w Fakturowni">
                    {{ doc.number }}
                </a>
            </div>

            <div class="flex items-center gap-1 shrink-0">
                <a v-if="doc.kind === 'vat' && doc.correction_url" :href="doc.correction_url" target="_blank" 
                        class="text-[10px] bg-slate-50 border border-slate-200 text-slate-600 hover:text-red-600 hover:bg-red-50 hover:border-red-200 px-2 py-1 rounded transition-colors flex items-center gap-1" title="Wystaw korektę ręcznie">
                    <i class="fa-solid fa-eraser"></i> Kor.
                </a>

                <a :href="doc.pdf_url" target="_blank" class="text-[10px] bg-slate-50 border border-slate-200 text-red-600 hover:bg-red-50 hover:border-red-200 px-2 py-1 rounded transition-colors font-bold flex items-center gap-1">
                    <i class="fa-solid fa-file-pdf"></i> PDF
                </a>
            </div>
        </div>
        
        <div class="pt-2 border-t border-slate-100 flex gap-2 justify-end">
             <a v-if="orderDocuments.length > 0" :href="getManualInvoiceUrl()" target="_blank" class="text-[10px] text-slate-400 hover:text-blue-600 underline flex items-center gap-1">
                 <i class="fa-solid fa-plus-circle"></i> Dodaj kolejną FV (Dane z zamówienia)
             </a>
        </div>
    </div>

    <div v-else class="space-y-3">
        <div class="flex items-center justify-between">
            <span class="text-slate-500 w-20 text-xs">Paragon:</span>
            <button @click="issueDocument('receipt')" :disabled="docsProcessing" class="flex-1 text-[11px] border border-slate-300 px-2 py-1.5 rounded bg-white text-slate-600 hover:text-blue-600 hover:border-blue-400 font-bold shadow-sm transition-colors flex items-center justify-center gap-1">
                <span v-if="docsProcessing && processingType === 'receipt'"><i class="fa-solid fa-circle-notch spin"></i></span>
                <span v-else><i class="fa-solid fa-receipt mr-1"></i> WYSTAW PARAGON</span>
            </button>
        </div>
        <div class="flex items-center justify-between">
            <span class="text-slate-500 w-20 text-xs">Faktura:</span>
            <div class="flex gap-2 flex-1">
                <button @click="issueDocument('vat')" :disabled="docsProcessing" class="flex-1 text-[11px] border border-slate-300 px-2 py-1.5 rounded bg-white text-slate-600 hover:text-blue-600 hover:border-blue-400 font-bold shadow-sm transition-colors flex items-center justify-center gap-1">
                    <span v-if="docsProcessing && processingType === 'vat'"><i class="fa-solid fa-circle-notch spin"></i></span>
                    <span v-else><i class="fa-solid fa-file-invoice mr-1"></i> FAKTURA VAT</span>
                </button>
                <button @click="issueDocument('proforma')" :disabled="docsProcessing" class="w-20 text-[10px] border border-slate-300 px-2 py-1.5 rounded bg-white text-slate-500 hover:text-blue-600 hover:border-blue-400 font-bold shadow-sm transition-colors">
                    PRO FORMA
                </button>
            </div>
        </div>
    </div>
</div>
{/literal}