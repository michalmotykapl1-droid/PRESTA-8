{literal}
<div class="bg-white rounded shadow-sm border border-slate-200">
    <div class="px-5 py-3 border-b border-slate-200 bg-white flex justify-between items-center rounded-t">
        <h3 class="font-bold text-slate-700 text-[15px]"><i class="fa-solid fa-truck-fast mr-2 text-slate-400"></i> Przesyłki</h3>
        <button @click="openAddTrackingModal" class="text-xs bg-slate-50 border border-slate-200 text-slate-600 px-3 py-1 rounded hover:bg-slate-100 font-bold transition-colors">
            + Dodaj numer
        </button>
    </div>

    <div class="p-5">
        <div v-if="currentOrderDetails.shipments && currentOrderDetails.shipments.length > 0" class="space-y-3 mb-6">
            <div v-for="ship in currentOrderDetails.shipments" class="flex items-center justify-between bg-slate-50 border border-slate-200 rounded p-3">
                <div class="flex items-center gap-3">
                    <div class="text-emerald-500 text-lg"><i class="fa-solid fa-box-open"></i></div>
                    <div>
                        <div class="font-bold text-slate-700 text-sm">{{ ship.carrier_name }}</div>
                        <div class="text-xs text-slate-500 font-mono mt-0.5">{{ ship.tracking_number }}</div>
                    </div>
                </div>
                <div class="flex gap-2">
                    <a :href="ship.track_url" target="_blank" class="text-xs bg-white border border-slate-300 text-slate-600 px-3 py-1.5 rounded hover:text-blue-600 hover:border-blue-400 font-bold transition-colors">
                        Śledź
                    </a>
                    <a :href="API_URL + '&action=get_allegro_label&id_order=' + currentOrderDetails.id_order + '&shipment_id=' + ship.tracking_number" target="_blank" class="text-xs bg-white border border-slate-300 text-red-600 px-3 py-1.5 rounded hover:bg-red-50 font-bold transition-colors">
                        <i class="fa-solid fa-file-pdf"></i> Etykieta
                    </a>
                </div>
            </div>
        </div>
        <div v-else class="text-center text-slate-400 text-sm mb-6 italic">Brak utworzonych przesyłek.</div>

        <div v-if="currentOrderDetails.is_allegro_smart" class="bg-[#ffece5] border-l-4 border-[#ff5a00] p-3 flex justify-between items-center rounded-r mb-4">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-check text-[#ff5a00]"></i>
                <div>
                    <strong class="text-slate-800 text-sm block">ALLEGRO SMART!</strong>
                    <span class="text-[10px] text-slate-600">Wysyłka opłacona przez Allegro.</span>
                </div>
            </div>
            <div class="text-right bg-white px-2 py-1 rounded border border-[#ffdec2]">
                <div class="text-[9px] text-slate-400 uppercase font-bold">Pozostało</div>
                <div class="text-sm font-bold" :class="currentOrderDetails.smart_left > 0 ? 'text-green-600' : 'text-red-500'">
                    {{ currentOrderDetails.smart_left }} <span class="text-slate-300 text-[10px]">/ {{ currentOrderDetails.smart_limit }}</span>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
            <button @click="openAllegroShipmentModal" class="bg-white border border-[#ff5a00] text-[#ff5a00] hover:bg-[#fff5f0] px-4 py-2 rounded text-sm font-bold shadow-sm flex items-center gap-2 transition-all">
                <i class="fa-brands fa-allegro"></i> Wysyłam z Allegro
            </button>
            
            <button class="bg-white border border-[#ffcc00] text-slate-700 hover:bg-[#fffdf0] px-4 py-2 rounded text-sm font-bold shadow-sm flex items-center gap-2 transition-all">
                <i class="fa-solid fa-box text-[#ffcc00]"></i> InPost
            </button>
            
            <button class="bg-white border border-blue-500 text-blue-600 hover:bg-blue-50 px-4 py-2 rounded text-sm font-bold shadow-sm flex items-center gap-2 transition-all">
                <i class="fa-solid fa-truck"></i> Apaczka
            </button>
        </div>
    </div>
</div>
{/literal}

{include file='module:bb_ordermanager/views/templates/front/inc/modals/allegro_shipment.tpl'}