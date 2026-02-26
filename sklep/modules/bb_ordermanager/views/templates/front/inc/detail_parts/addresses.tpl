<div class="grid grid-cols-1 md:grid-cols-3 gap-5">
    <div class="bg-white rounded shadow-sm border border-slate-200 h-full flex flex-col">
        <div class="px-4 py-2.5 border-b border-slate-200 bg-white flex justify-between items-center rounded-t">
            <h4 class="font-bold text-slate-700 text-sm">Adres dostawy</h4>
            <div class="flex gap-1" v-if="!editingDelivery">
                <button @click="copyAddressToClipboard('address_delivery')" class="p-1 text-slate-400 hover:text-blue-600 border border-transparent hover:border-slate-200 rounded transition-colors"><i class="fa-regular fa-copy"></i></button>
                <button @click="startEditAddress('address_delivery')" class="p-1 text-slate-400 hover:text-blue-600 border border-transparent hover:border-slate-200 rounded transition-colors"><i class="fa-solid fa-pen"></i></button>
            </div>
        </div>
        <div class="p-5 text-sm space-y-2 text-slate-700 flex-1">
            <div v-if="!editingDelivery">
                <div class="flex"><span class="text-slate-500 w-28">Imię i nazwisko:</span><span class="text-slate-800 font-semibold">{{ currentOrderDetails.address_delivery.firstname }} {{ currentOrderDetails.address_delivery.lastname }}</span></div>
                <div class="flex"><span class="text-slate-500 w-28">Firma:</span><span class="text-slate-800">{{ currentOrderDetails.address_delivery.company || '...' }}</span></div>
                <div class="flex"><span class="text-slate-500 w-28">Adres:</span><span class="text-slate-800">{{ currentOrderDetails.address_delivery.address1 }}</span></div>
                <div class="flex"><span class="text-slate-500 w-28">Kod i miasto:</span><span class="text-slate-800">{{ currentOrderDetails.address_delivery.postcode }} {{ currentOrderDetails.address_delivery.city }}</span></div>
                <div class="flex"><span class="text-slate-500 w-28">Województwo:</span><span class="text-slate-800">{{ currentOrderDetails.address_delivery.state || '...' }}</span></div>
                <div class="flex"><span class="text-slate-500 w-28">Kraj:</span><span class="text-slate-800">{{ currentOrderDetails.address_delivery.country || '...' }}</span></div>
                <div class="flex mt-2 pt-2 border-t border-slate-100" v-if="currentOrderDetails.address_delivery.other">
                    <span class="text-slate-500 w-28 font-bold text-xs">PUNKT/INFO:</span>
                    <span class="text-blue-600 font-mono font-bold">{{ currentOrderDetails.address_delivery.other }}</span>
                </div>
            </div>
            <div v-else class="space-y-2">
                <input v-model="tempAddress.firstname" class="w-full border p-1 rounded text-xs" placeholder="Imię">
                <input v-model="tempAddress.lastname" class="w-full border p-1 rounded text-xs" placeholder="Nazwisko">
                <input v-model="tempAddress.company" class="w-full border p-1 rounded text-xs" placeholder="Firma">
                <input v-model="tempAddress.address1" class="w-full border p-1 rounded text-xs" placeholder="Ulica i numer">
                <div class="flex gap-2">
                    <input v-model="tempAddress.postcode" class="w-1/3 border p-1 rounded text-xs" placeholder="Kod">
                    <input v-model="tempAddress.city" class="w-2/3 border p-1 rounded text-xs" placeholder="Miasto">
                </div>
                <div class="flex gap-2 mt-3">
                    <button @click="saveAddress('address_delivery')" class="bg-blue-600 text-white px-3 py-1 rounded text-xs font-bold hover:bg-blue-700 w-full" :disabled="savingAddress">
                        <span v-if="!savingAddress">Zapisz</span>
                        <span v-else><i class="fa-solid fa-circle-notch spin"></i></span>
                    </button>
                    <button @click="cancelEdit('address_delivery')" class="bg-white border border-slate-300 text-slate-600 px-3 py-1 rounded text-xs hover:bg-slate-50">Anuluj</button>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded shadow-sm border border-slate-200 h-full flex flex-col">
        <div class="px-4 py-2.5 border-b border-slate-200 bg-white flex justify-between items-center rounded-t">
            <h4 class="font-bold text-slate-700 text-sm">Dane do faktury</h4>
            <div class="flex gap-1" v-if="!editingInvoice">
                <button @click="copyDeliveryToInvoice()" title="Wklej dane z ADRESU DOSTAWY (Kopiuj z lewej)" class="p-1 text-slate-400 hover:text-blue-600 border border-transparent hover:border-slate-200 rounded transition-colors"><i class="fa-solid fa-paste"></i></button>
                <button @click="startEditAddress('address_invoice')" class="p-1 text-slate-400 hover:text-blue-600 border border-transparent hover:border-slate-200 rounded transition-colors"><i class="fa-solid fa-pen"></i></button>
            </div>
        </div>
        <div class="p-5 text-sm space-y-2 text-slate-700 flex-1">
            <div v-if="!editingInvoice">
                <div class="flex"><span class="text-slate-500 w-28">Imię i nazwisko:</span><span class="text-slate-800">{{ currentOrderDetails.address_invoice.firstname }} {{ currentOrderDetails.address_invoice.lastname }}</span></div>
                <div class="flex"><span class="text-slate-500 w-28">Firma:</span><span class="text-slate-800">{{ currentOrderDetails.address_invoice.company || '...' }}</span></div>
                <div class="flex"><span class="text-slate-500 w-28">Adres:</span><span class="text-slate-800">{{ currentOrderDetails.address_invoice.address1 }}</span></div>
                <div class="flex"><span class="text-slate-500 w-28">Kod i miasto:</span><span class="text-slate-800">{{ currentOrderDetails.address_invoice.postcode }} {{ currentOrderDetails.address_invoice.city }}</span></div>
                <div class="flex"><span class="text-slate-500 w-28">NIP:</span><span class="text-slate-800 font-bold">{{ currentOrderDetails.address_invoice.vat_number || '...' }}</span></div>
            </div>
            <div v-else class="space-y-2">
                <input v-model="tempAddress.firstname" class="w-full border p-1 rounded text-xs" placeholder="Imię">
                <input v-model="tempAddress.lastname" class="w-full border p-1 rounded text-xs" placeholder="Nazwisko">
                <input v-model="tempAddress.company" class="w-full border p-1 rounded text-xs" placeholder="Firma">
                <input v-model="tempAddress.address1" class="w-full border p-1 rounded text-xs" placeholder="Ulica i numer">
                <div class="flex gap-2">
                    <input v-model="tempAddress.postcode" class="w-1/3 border p-1 rounded text-xs" placeholder="Kod">
                    <input v-model="tempAddress.city" class="w-2/3 border p-1 rounded text-xs" placeholder="Miasto">
                </div>
                <input v-model="tempAddress.vat_number" class="w-full border p-1 rounded text-xs" placeholder="NIP (Opcjonalnie)">
                <div class="flex gap-2 mt-3">
                    <button @click="saveAddress('address_invoice')" class="bg-blue-600 text-white px-3 py-1 rounded text-xs font-bold hover:bg-blue-700 w-full" :disabled="savingAddress">
                        <span v-if="!savingAddress">Zapisz</span>
                        <span v-else><i class="fa-solid fa-circle-notch spin"></i></span>
                    </button>
                    <button @click="cancelEdit('address_invoice')" class="bg-white border border-slate-300 text-slate-600 px-3 py-1 rounded text-xs hover:bg-slate-50">Anuluj</button>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded shadow-sm border border-slate-200 h-full flex flex-col">
        <div class="px-4 py-2.5 border-b border-slate-200 bg-white flex justify-between items-center rounded-t">
            <h4 class="font-bold text-slate-700 text-sm">Punkt Odbioru / Info</h4>
            <div class="flex gap-1" v-if="!editingPickup">
                <button @click="startEditPickup()" title="Edytuj Punkt Odbioru" class="p-1 text-slate-400 hover:text-blue-600 border border-transparent hover:border-slate-200 rounded transition-colors"><i class="fa-solid fa-pen"></i></button>
            </div>
        </div>
        <div class="p-5 text-sm space-y-2 text-slate-700 flex-1">
            <div v-if="!editingPickup">
                <div v-if="currentOrderDetails.pickup_point_id" class="bg-blue-50 border border-blue-100 rounded-md p-4 h-full flex flex-col justify-center">
                    <div class="flex items-center gap-2 mb-2"><span class="bg-blue-600 text-white text-[10px] font-bold px-2 py-0.5 rounded shadow-sm uppercase">Punkt odbioru</span></div>
                    <div class="text-lg font-bold text-blue-800 mb-1">{{ currentOrderDetails.pickup_point_id }}</div>
                    <div class="text-sm font-bold text-slate-800 leading-snug">{{ currentOrderDetails.pickup_point_name }}</div>
                    <div class="text-xs text-slate-600 mt-1 leading-snug">{{ currentOrderDetails.pickup_point_addr }}</div>
                </div>
                <div v-else-if="currentOrderDetails.address_delivery.other" class="bg-yellow-50 text-yellow-800 p-3 rounded border border-yellow-200 text-xs h-full flex flex-col justify-center">
                    <span class="font-bold block mb-1 uppercase text-[10px]">Dane dodatkowe (Inne):</span>
                    {{ currentOrderDetails.address_delivery.other }}
                </div>
                <div v-else class="h-full flex flex-col items-center justify-center text-slate-400 italic">
                    <i class="fa-solid fa-truck text-2xl mb-2 opacity-30"></i>
                    <span>Dostawa pod adres klienta (Kurier)</span>
                </div>
            </div>

            <div v-else class="space-y-3">
                <label class="block text-[10px] font-bold uppercase text-slate-500">Kod Paczkomatu / Info:</label>
                <input v-model="tempAddress.other" class="w-full border border-blue-300 bg-blue-50 text-blue-900 font-bold p-2 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="np. WAW22A">
                <button @click="openLockerSearch" class="w-full bg-slate-100 border border-slate-300 text-slate-600 px-3 py-2 rounded text-xs font-bold hover:bg-white hover:text-blue-600 flex items-center justify-center gap-2 transition-colors">
                    <i class="fa-solid fa-map-location-dot"></i> Znajdź Paczkomat (Lista)
                </button>
                <div class="flex gap-2 mt-2">
                    <button @click="saveAddress('pickup')" class="bg-blue-600 text-white px-3 py-1.5 rounded text-xs font-bold hover:bg-blue-700 w-full" :disabled="savingAddress">
                        <span v-if="!savingAddress">Zapisz</span>
                        <span v-else><i class="fa-solid fa-circle-notch spin"></i></span>
                    </button>
                    <button @click="cancelEdit('pickup')" class="bg-white border border-slate-300 text-slate-600 px-3 py-1.5 rounded text-xs hover:bg-slate-50">Anuluj</button>
                </div>
            </div>
        </div>
    </div>
</div>