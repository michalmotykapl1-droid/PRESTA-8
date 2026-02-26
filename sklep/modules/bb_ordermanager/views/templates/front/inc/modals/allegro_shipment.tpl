{literal}
<div v-if="showAllegroModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[60] flex items-center justify-center p-4 animate-in fade-in duration-200">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full overflow-hidden transform scale-100">
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 class="font-bold text-slate-800 text-lg"><i class="fa-brands fa-allegro text-[#ff5a00] mr-2"></i> Nadaj przesyłkę</h3>
            <button @click="showAllegroModal = false" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-xl"></i></button>
        </div>
        
        <div class="p-6 space-y-5">
            
            <div v-if="currentOrderDetails.is_allegro_smart" class="bg-[#ffece5] border-l-4 border-[#ff5a00] p-3 flex justify-between items-center rounded-r mb-2">
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

            <div class="flex gap-2 p-1 bg-slate-100 rounded-lg">
                <button @click="allegroShippingMode = 'BOX'" class="flex-1 py-1.5 text-xs font-bold rounded-md transition-all" :class="allegroShippingMode === 'BOX' ? 'bg-white shadow text-[#ff5a00]' : 'text-slate-500 hover:text-slate-700'">PACZKOMAT</button>
                <button @click="allegroShippingMode = 'COURIER'" class="flex-1 py-1.5 text-xs font-bold rounded-md transition-all" :class="allegroShippingMode === 'COURIER' ? 'bg-white shadow text-[#ff5a00]' : 'text-slate-500 hover:text-slate-700'">KURIER</button>
            </div>

            <div v-if="allegroShippingMode === 'BOX'" class="grid grid-cols-3 gap-3">
                <button @click="createAllegroShipment('A')" :disabled="allegroCreating" class="border-2 border-slate-200 hover:border-[#ff5a00] hover:bg-[#fff5f0] rounded-lg p-4 text-center group transition-all">
                    <div class="text-2xl font-black text-slate-300 group-hover:text-[#ff5a00] mb-1">A</div>
                    <div class="text-[10px] text-slate-400 font-bold uppercase">Mała</div>
                </button>
                <button @click="createAllegroShipment('B')" :disabled="allegroCreating" class="border-2 border-slate-200 hover:border-[#ff5a00] hover:bg-[#fff5f0] rounded-lg p-4 text-center group transition-all">
                    <div class="text-2xl font-black text-slate-300 group-hover:text-[#ff5a00] mb-1">B</div>
                    <div class="text-[10px] text-slate-400 font-bold uppercase">Średnia</div>
                </button>
                <button @click="createAllegroShipment('C')" :disabled="allegroCreating" class="border-2 border-slate-200 hover:border-[#ff5a00] hover:bg-[#fff5f0] rounded-lg p-4 text-center group transition-all">
                    <div class="text-2xl font-black text-slate-300 group-hover:text-[#ff5a00] mb-1">C</div>
                    <div class="text-[10px] text-slate-400 font-bold uppercase">Duża</div>
                </button>
            </div>

            <div v-else class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Waga (kg)</label>
                    <input v-model="allegroWeight" type="number" step="0.1" class="w-full border border-slate-300 rounded px-3 py-2 text-sm font-bold text-slate-700 focus:outline-none focus:border-[#ff5a00]">
                </div>
                <button @click="createAllegroShipment(null)" :disabled="allegroCreating" class="w-full bg-[#ff5a00] hover:bg-[#e04e00] text-white py-3 rounded font-bold shadow-lg shadow-orange-200 transition-colors flex justify-center items-center gap-2">
                    <span v-if="!allegroCreating">NADAJ PRZESYŁKĘ</span>
                    <span v-else><i class="fa-solid fa-circle-notch spin"></i> Przetwarzanie...</span>
                </button>
            </div>

            <div class="pt-4 border-t border-slate-100" v-if="currentOrderDetails.is_allegro_smart">
                <label class="flex items-center gap-3 cursor-pointer group">
                    <div class="relative flex items-center">
                        <input type="checkbox" v-model="allegroIsSmart" class="peer sr-only">
                        <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#ff5a00]"></div>
                    </div>
                    <span class="text-sm font-medium text-slate-600 group-hover:text-slate-800">Użyj Allegro Smart!</span>
                </label>
            </div>
        </div>
    </div>
</div>
{/literal}