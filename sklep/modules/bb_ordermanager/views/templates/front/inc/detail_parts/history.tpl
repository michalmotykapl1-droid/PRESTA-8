{literal}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 pb-10">
    <div class="bg-white rounded shadow-sm border border-slate-200 p-5 h-fit">
        <h4 class="font-bold text-slate-700 mb-3 flex items-center gap-2"><i class="fa-regular fa-comments"></i> Wymiana wiadomości</h4>
        <div v-if="currentOrderDetails.messages && currentOrderDetails.messages.length > 0" class="space-y-4 mb-4"><div v-for="(msg, idx) in currentOrderDetails.messages" :key="idx" class="border-l-4 border-blue-400 bg-slate-50 p-3 rounded-r"><div class="text-xs text-slate-400 mb-1">{{ msg.date_add }}</div><div class="text-sm text-slate-700">{{ msg.message }}</div></div></div><div v-else class="text-slate-500 text-sm mb-4">Brak wiadomości.</div><button class="text-blue-600 hover:text-blue-800 text-sm font-bold flex items-center gap-2"><i class="fa-solid fa-plus"></i> Napisz wiadomość</button>
    </div>
    
    <div class="bg-white rounded shadow-sm border border-slate-200 p-5 h-fit">
        <h4 class="font-bold text-slate-700 mb-4">Informacje dodatkowe</h4>
        <div class="space-y-3">
            <div class="border border-slate-200 rounded overflow-hidden"><div @click="toggleHistory('fees')" class="bg-slate-50 px-4 py-2 flex justify-between items-center cursor-pointer hover:bg-slate-100 transition-colors select-none"><span class="text-sm font-bold text-slate-700">Prowizje i opłaty</span><i class="fa-solid fa-chevron-down text-slate-400 text-xs transition-transform" :class="{'rotate-180': historyAccordion.fees}"></i></div><div v-if="historyAccordion.fees" class="p-4 text-sm text-slate-500 bg-white border-t border-slate-100">Brak informacji o prowizjach dla tego zamówienia.</div></div>
            <div class="border border-slate-200 rounded overflow-hidden"><div @click="toggleHistory('payments')" class="bg-slate-50 px-4 py-2 flex justify-between items-center cursor-pointer hover:bg-slate-100 transition-colors select-none"><span class="text-sm font-bold text-slate-700">Historia płatności zamówienia</span><i class="fa-solid fa-chevron-down text-slate-400 text-xs transition-transform" :class="{'rotate-180': historyAccordion.payments}"></i></div><div v-if="historyAccordion.payments" class="p-4 text-sm bg-white border-t border-slate-100"><div v-if="currentOrderDetails.history_payment && currentOrderDetails.history_payment.length > 0" class="space-y-2"><div v-for="(pay, idx) in currentOrderDetails.history_payment" :key="idx" class="flex justify-between border-b border-slate-100 pb-2 last:border-0"><div class="text-xs text-slate-500">{{ pay.date_add }}</div><div class="text-right"><div class="font-bold text-slate-800">{{ pay.amount }} PLN</div><div class="text-xs text-slate-500">{{ pay.payment_method }} <span v-if="pay.transaction_id" class="font-mono text-[10px] ml-1">({{ pay.transaction_id }})</span></div></div></div></div><div v-else class="text-slate-500">Brak historii płatności.</div></div></div>
            <div class="border border-slate-200 rounded overflow-hidden"><div @click="toggleHistory('status')" class="bg-slate-50 px-4 py-2 flex justify-between items-center cursor-pointer hover:bg-slate-100 transition-colors select-none"><span class="text-sm font-bold text-slate-700">Historia statusów zamówienia</span><i class="fa-solid fa-chevron-down text-slate-400 text-xs transition-transform" :class="{'rotate-180': historyAccordion.status}"></i></div><div v-if="historyAccordion.status" class="p-4 text-sm bg-white border-t border-slate-100"><table class="w-full text-left"><thead class="text-[10px] text-slate-400 uppercase border-b border-slate-100"><tr><th class="pb-2 font-semibold">Użytkownik / Data</th><th class="pb-2 text-right font-semibold">Status (Folder)</th></tr></thead><tbody class="divide-y divide-slate-100"><tr v-for="(h, idx) in currentOrderDetails.history_status" :key="idx"><td class="py-2 text-slate-600 leading-tight">{{ h.employee_display }}<br><span class="text-[10px] text-slate-400">{{ h.date_add }}</span></td><td class="py-2 text-right font-bold text-slate-800 text-xs">{{ h.folder_name }}</td></tr></tbody></table></div></div>
            
            <div class="border border-slate-200 rounded overflow-hidden">
                <div @click="toggleHistory('changes')" class="bg-slate-50 px-4 py-2 flex justify-between items-center cursor-pointer hover:bg-slate-100 transition-colors select-none">
                    <span class="text-sm font-bold text-slate-700">Historia zmian w zamówieniu</span>
                    <i class="fa-solid fa-chevron-down text-slate-400 text-xs transition-transform" :class="{'rotate-180': historyAccordion.changes}"></i>
                </div>
                <div v-if="historyAccordion.changes" class="p-4 text-sm bg-white border-t border-slate-100">
                    <div v-if="currentOrderDetails.history_changes && currentOrderDetails.history_changes.length > 0" class="space-y-2">
                        <div v-for="(log, lIdx) in currentOrderDetails.history_changes" :key="lIdx" class="flex flex-col border-b border-slate-100 pb-2 last:border-0 last:pb-0">
                            <div class="text-[10px] text-slate-400 mb-0.5">{{ log.date_add }}</div>
                            <div class="text-slate-700 text-xs">{{ log.message }}</div>
                        </div>
                    </div>
                    <div v-else class="text-slate-500">Brak historii zmian.</div>
                </div>
            </div>

            <div class="border border-slate-200 rounded overflow-hidden"><div @click="toggleHistory('auto')" class="bg-slate-50 px-4 py-2 flex justify-between items-center cursor-pointer hover:bg-slate-100 transition-colors select-none"><span class="text-sm font-bold text-slate-700">Historia automatycznych akcji</span><i class="fa-solid fa-chevron-down text-slate-400 text-xs transition-transform" :class="{'rotate-180': historyAccordion.auto}"></i></div><div v-if="historyAccordion.auto" class="p-4 text-sm text-slate-500 bg-white border-t border-slate-100">Brak wykonanych automatycznych akcji.</div></div>
            
            <div class="border border-slate-200 rounded overflow-hidden">
                <div @click="toggleHistory('pack')" class="bg-slate-50 px-4 py-2 flex justify-between items-center cursor-pointer hover:bg-slate-100 transition-colors select-none">
                    <span class="text-sm font-bold text-slate-700">Historia zbierania i pakowania</span>
                    <i class="fa-solid fa-chevron-down text-slate-400 text-xs transition-transform" :class="{'rotate-180': historyAccordion.pack}"></i>
                </div>
                <div v-if="historyAccordion.pack" class="p-4 text-sm bg-white border-t border-slate-100">
                    <div v-if="currentOrderDetails.history_pack && currentOrderDetails.history_pack.length > 0" class="space-y-2">
                        <div v-for="(log, lIdx) in currentOrderDetails.history_pack" :key="lIdx" class="flex items-start gap-3 border-b border-slate-100 pb-2 last:border-0 last:pb-0">
                            <div class="text-emerald-500 mt-0.5"><i class="fa-solid fa-box-check"></i></div>
                            <div>
                                <div class="text-[10px] text-slate-400 mb-0.5">{{ log.date_add }}</div>
                                <div class="text-slate-700 text-xs font-medium">{{ log.message }}</div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-slate-500">Brak historii pakowania.</div>
                </div>
            </div>
        </div>
    </div>
</div>
{/literal}