{* PLIK: modules/bb_ordermanager/views/templates/front/order_list.tpl *}
{literal}
<main class="flex-1 flex flex-col relative bg-[#f0f2f5] min-w-0 font-sans text-[13px]">
    
    <header class="h-14 bg-white border-b border-slate-300 flex justify-between items-center px-4 shadow-sm shrink-0 z-10 gap-4">
        
        <div class="flex items-center gap-4 min-w-fit">
            <h2 class="font-bold text-slate-700 text-lg">{{ activeFolder }}</h2>
            <button @click="fetchOrders" class="text-slate-400 hover:text-blue-600 transition-colors" title="Odśwież">
                <i class="fa-solid fa-sync" :class="{'spin': loading}"></i>
            </button>
            <span v-if="loading" class="text-xs text-slate-400">Odświeżanie...</span>
        </div>
        
        <div class="flex-1 flex justify-center">
            <transition name="fade">
                <div v-if="selectedOrders.length > 0" class="bg-slate-800 text-white px-4 py-1.5 rounded-full shadow-lg flex items-center gap-3 animate-in slide-in-from-top-2 duration-300">
                    <span class="text-xs font-semibold bg-slate-700 px-2 py-0.5 rounded text-slate-300">
                        Wybrano: {{ selectedOrders.length }}
                    </span>
                    <div class="h-4 w-px bg-slate-600 mx-1"></div>
                    <button class="hover:text-blue-300 text-xs font-bold transition-colors flex items-center gap-1">
                        <i class="fa-solid fa-print"></i> Etykiety
                    </button>
                    <button class="hover:text-blue-300 text-xs font-bold transition-colors flex items-center gap-1">
                        <i class="fa-solid fa-file-invoice"></i> Faktury
                    </button>
                    <div class="h-4 w-px bg-slate-600 mx-1"></div>
                    
                    <button @click="massPacking" class="bg-emerald-600 hover:bg-emerald-500 text-white px-3 py-1 rounded text-xs font-bold transition-colors flex items-center gap-1">
                        <i class="fa-solid fa-box-open"></i> Pakuj ({{ selectedOrders.length }})
                    </button>
                </div>
            </transition>
        </div>
        
        <div class="flex items-center gap-3 min-w-fit">
            <div class="relative">
                <input v-model="searchQuery" type="text" placeholder="Szukaj (Nr, Klient, Produkt)..." class="pl-3 pr-8 py-1.5 bg-white border border-slate-300 rounded text-sm w-64 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition-all">
                <i class="fa-solid fa-magnifying-glass absolute right-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
            </div>
            <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-1.5 rounded text-sm font-bold shadow-sm transition-colors">
                + Dodaj zamówienie
            </button>

            <!-- User menu (BaseLinker-like) -->
            <div v-if="authLogged" class="relative">
                <button @click.stop="userMenuOpen = !userMenuOpen"
                        class="flex items-center gap-2 pl-2 pr-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 transition">
                    <div class="w-9 h-9 rounded-full bg-blue-600 text-white flex items-center justify-center font-extrabold">
                        {{ (employee && employee.firstname) ? employee.firstname.charAt(0).toUpperCase() : 'U' }}
                    </div>
                    <div class="hidden lg:block text-left leading-tight">
                        <div class="text-[10px] text-slate-400">Zalogowano</div>
                        <div class="text-sm font-bold text-slate-700 truncate max-w-[150px]">
                            {{ employee && employee.firstname ? employee.firstname : '' }}
                        </div>
                    </div>
                    <i class="fa-solid fa-chevron-down text-slate-400 text-xs"></i>
                </button>

                <div v-if="userMenuOpen" class="absolute right-0 mt-2 w-48 bg-white border border-slate-200 rounded-lg shadow-lg overflow-hidden z-50">
                    <button @click="doLogout" class="w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 flex items-center gap-2">
                        <i class="fa-solid fa-right-from-bracket text-slate-400"></i>
                        Wyloguj
                    </button>
                </div>
            </div>
        </div>
    </header>

    <div class="flex-1 overflow-y-auto custom-scrollbar p-4">
        
        <div v-if="loading && orders.length === 0" class="text-center py-20 text-slate-500">
            <i class="fa-solid fa-circle-notch spin text-3xl mb-3 text-blue-500"></i>
            <p>Pobieranie listy zamówień...</p>
        </div>

        <div v-else-if="sortedOrders.length === 0" class="text-center py-20 opacity-60">
            <i class="fa-solid fa-inbox text-5xl text-slate-300 mb-3"></i>
            <h3 class="text-lg font-bold text-slate-600">Brak zamówień</h3>
            <p class="text-slate-400">Folder "{{ activeFolder }}" jest pusty.</p>
        </div>

        <table v-else class="w-full border-separate border-spacing-y-3 text-left table-fixed">
            <thead>
                <tr class="text-slate-500 font-bold text-[11px] uppercase tracking-wide">
                    <th class="w-10 pl-3 text-center">
                        <input type="checkbox" 
                               class="cursor-pointer align-middle w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500 transition-all"
                               :checked="allSelected"
                               @change="toggleSelectAll">
                    </th>
                    <th class="w-40 px-2">Zamówienie</th>
                    <th class="w-32 px-2 sortable" @click="sortBy('date_add')" title="Sortuj po dacie">
                        Data
                        <span class="ml-1 text-xs">
                            <i v-if="sortKey !== 'date_add'" class="fa-solid fa-sort text-slate-300 hover:text-blue-400"></i>
                            <i v-else-if="sortOrder === 'asc'" class="fa-solid fa-sort-up text-blue-600 align-bottom"></i>
                            <i v-else class="fa-solid fa-sort-down text-blue-600 align-top"></i>
                        </span>
                    </th>
                    <th class="w-auto px-2">Produkty</th> 
                    <th class="w-44 px-2 sortable" @click="sortBy('carrier_name')" title="Sortuj po przewoźniku">
                        Dostawa
                        <span class="ml-1 text-xs">
                            <i v-if="sortKey !== 'carrier_name'" class="fa-solid fa-sort text-slate-300 hover:text-blue-400"></i>
                            <i v-else-if="sortOrder === 'asc'" class="fa-solid fa-sort-up text-blue-600 align-bottom"></i>
                            <i v-else class="fa-solid fa-sort-down text-blue-600 align-top"></i>
                        </span>
                    </th>
                    <th class="w-48 px-2">Dane zamawiającego</th>
                    <th class="w-32 text-right px-2 sortable" @click="sortBy('total_paid')" title="Sortuj po kwocie">
                        Kwota
                        <span class="ml-1 text-xs">
                            <i v-if="sortKey !== 'total_paid'" class="fa-solid fa-sort text-slate-300 hover:text-blue-400"></i>
                            <i v-else-if="sortOrder === 'asc'" class="fa-solid fa-sort-up text-blue-600 align-bottom"></i>
                            <i v-else class="fa-solid fa-sort-down text-blue-600 align-top"></i>
                        </span>
                    </th>
                    <th class="w-12 pr-3 text-right">Akcje</th>
                </tr>
            </thead>

            <tbody>
                <tr v-for="order in paginatedOrders" :key="order.id_order" 
                    class="bg-white hover:shadow-md transition-shadow group relative align-top"
                    :class="{'ring-1 ring-blue-500 bg-blue-50/40': selectedOrders.includes(order.id_order)}">
                    
                    <td class="py-3 pl-3 border-y border-l border-slate-200 rounded-l text-center align-top pt-4"
                        :class="{'border-blue-500 border-l-4': selectedOrders.includes(order.id_order)}">
                        <input type="checkbox" 
                               class="cursor-pointer w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500 transition-all"
                               v-model="selectedOrders"
                               :value="order.id_order">
                    </td>

                    <td class="py-3 px-2 border-y border-slate-200 align-top"
                        :class="{'border-blue-500': selectedOrders.includes(order.id_order)}">
                        <div class="flex flex-col gap-1">
                            <a href="#" @click.prevent="openOrderDetails(order.id_order)" 
                               class="text-slate-700 font-bold hover:text-blue-600 text-[14px] leading-tight transition-colors">
                                {{ order.reference }}
                            </a>
                            <div class="text-[11px] flex items-center gap-1.5 mt-0.5">
                                <span class="text-slate-400 font-mono">#{{ order.id_order }}</span>
                                <span class="text-slate-300">|</span>
                                <span v-if="order.email && order.email.includes('@allegromail.pl')" class="text-orange-600 font-bold">Allegro</span>
                                <span v-else class="text-slate-500 font-medium">Sklep</span>
                            </div>
                            
                            <div class="flex gap-3 mt-2 text-[16px]">
                                <i class="fa-solid fa-sack-dollar transition-colors" :class="getPaymentIconClass(order)" :title="isPaid(order) ? 'Opłacone' : 'Brak wpłaty / Niedopłata'"></i>
                                
                                <i class="fa-solid fa-truck-fast transition-colors" :class="order.shipping_number ? 'text-blue-500' : 'text-slate-300'" :title="order.shipping_number ? 'Nr przesyłki: ' + order.shipping_number : 'Brak przesyłki'"></i>
                                
                                <i class="fa-solid fa-box-open transition-colors" :class="{'text-emerald-500': order.pack_status === 'done', 'text-orange-500': order.pack_status === 'partial', 'text-slate-300': order.pack_status === 'none'}" :title="order.pack_status === 'done' ? 'Spakowane w całości' : (order.pack_status === 'partial' ? 'Częściowo spakowane' : 'Nie spakowane')"></i>

                                <i class="fa-solid fa-file-invoice transition-colors" :class="order.has_invoice ? 'text-purple-500' : 'text-slate-300'" :title="order.has_invoice ? 'Faktura wystawiona' : 'Brak faktury'"></i>
                            </div>

                            <div class="mt-2">
                                <span class="text-[10px] px-2 py-0.5 rounded border border-slate-200 bg-slate-50 text-slate-500 font-semibold truncate inline-block max-w-full">
                                    {{ order.status_name }}
                                </span>
                            </div>
                        </div>
                    </td>

                    <td class="py-3 px-2 border-y border-slate-200 text-slate-600 text-[12px] align-top"
                        :class="{'border-blue-500': selectedOrders.includes(order.id_order)}">
                        <div class="font-medium">{{ order.formatted_date.split(' ')[0] }}</div>
                        <div class="text-slate-400 text-[10px]">{{ order.formatted_date.split(' ')[1] }}</div>
                    </td>

                    <td class="py-3 px-2 border-y border-slate-200 align-top"
                        :class="{'border-blue-500': selectedOrders.includes(order.id_order)}">
                        <div v-if="order.products && order.products.length > 0" class="space-y-3">
                            <div v-for="(prod, idx) in order.products" :key="idx" class="flex items-start gap-3">
                                <div class="w-10 h-10 bg-white border border-slate-200 p-0.5 shrink-0 rounded-sm">
                                    <img v-if="prod.image_url" :src="prod.image_url" class="w-full h-full object-contain">
                                    <div v-else class="w-full h-full bg-slate-50 flex items-center justify-center text-slate-300">
                                        <i class="fa-regular fa-image"></i>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0 leading-snug">
                                    <div class="text-slate-800 text-[12px] truncate" :title="prod.product_name">
                                        <span class="font-bold mr-1">{{ prod.product_quantity }}x</span>
                                        {{ prod.product_name }}
                                    </div>
                                    <div class="text-[10px] text-slate-500 mt-0.5">
                                        <span v-if="prod.product_reference" class="bg-slate-100 px-1 rounded text-slate-500">SKU: {{ prod.product_reference }}</span>
                                    </div>
                                </div>
                            </div>
                            <div v-if="order.products.length > 3" class="text-[10px] text-blue-600 font-semibold cursor-pointer hover:underline pl-14">
                                + {{ order.products.length - 3 }} więcej pozycji...
                            </div>
                        </div>
                        <div v-else class="text-slate-400 italic text-xs pl-2">Brak produktów</div>
                    </td>

                    <td class="py-3 px-2 border-y border-slate-200 align-top"
                        :class="{'border-blue-500': selectedOrders.includes(order.id_order)}">
                        
                        <div v-if="order.pickup_point_id" class="flex flex-col items-start gap-1">
                            <span class="bg-blue-100 text-blue-700 border border-blue-200 text-[11px] font-bold px-2 py-0.5 rounded flex items-center gap-1">
                                <i class="fa-solid fa-box text-[10px]"></i> {{ order.pickup_point_id }}
                            </span>
                            <div class="text-[11px] text-slate-600 leading-tight">
                                <span class="block font-medium truncate max-w-[150px]" :title="order.carrier_name">{{ order.carrier_name }}</span>
                                <span v-if="order.pickup_point_addr" class="block text-slate-400 mt-0.5">{{ order.pickup_point_addr }}</span>
                            </div>
                        </div>
                        
                        <div v-else class="text-[11px] text-slate-600 leading-tight">
                            <div class="font-bold text-slate-800 mb-1 flex items-center gap-1 truncate max-w-[150px]" :title="order.carrier_name">
                                <i class="fa-solid fa-truck text-[10px]"></i> {{ order.carrier_name || 'Kurier' }}
                            </div>
                            <div v-if="order.company" class="text-emerald-700 font-bold mb-0.5 bg-emerald-50 px-1 rounded inline-block">{{ order.company }}</div>
                            <div v-if="order.address2" class="text-slate-700 font-bold mb-0.5">{{ order.address2 }}</div>
                            <div class="text-slate-600 font-semibold mb-0.5">{{ order.address1 }}</div>
                            <div class="text-[10px] text-slate-500">{{ order.postcode }} {{ order.city }} ({{ order.country_iso }})</div>
                        </div>
                    </td>

                    <td class="py-3 px-2 border-y border-slate-200 align-top"
                        :class="{'border-blue-500': selectedOrders.includes(order.id_order)}">
                        <div class="flex items-center gap-1.5 mb-1">
                            <img :src="`https://flagcdn.com/16x12/${order.country_iso ? order.country_iso.toLowerCase() : 'pl'}.png`" class="border border-slate-200 shadow-sm">
                            <span class="font-bold text-slate-700 text-[12px] truncate">{{ order.customer }}</span>
                        </div>
                        <div class="text-[11px] text-slate-400 truncate hover:text-blue-600 cursor-pointer" :title="order.email">{{ order.email }}</div>
                    </td>

                    <td class="py-3 px-2 border-y border-slate-200 text-right align-top"
                        :class="{'border-blue-500': selectedOrders.includes(order.id_order)}">
                        <div class="font-bold text-slate-800 text-[14px]">
                            {{ parseFloat(order.total_paid).toFixed(2) }} <span class="text-[10px] text-slate-500">PLN</span>
                        </div>
                        <div class="text-[10px] text-slate-400 mt-1 truncate max-w-[100px] ml-auto" :title="order.payment_method">{{ order.payment_method }}</div>
                    </td>

                    <td class="py-3 pr-3 border-y border-r border-slate-200 rounded-r text-right align-top pt-4"
                        :class="{'border-blue-500': selectedOrders.includes(order.id_order)}">
                        <div class="relative inline-block text-left">
                            <button @click.stop="toggleMenu(order.id_order)" 
                                    class="w-8 h-8 rounded border border-slate-300 bg-white text-slate-500 hover:text-blue-600 hover:border-blue-400 transition-colors shadow-sm flex items-center justify-center ml-auto focus:outline-none"
                                    :class="{'border-blue-500 text-blue-600 ring-2 ring-blue-100': activeMenuId === order.id_order}">
                                <i class="fa-solid fa-chevron-down text-xs transition-transform duration-200" :class="{'rotate-180': activeMenuId === order.id_order}"></i>
                            </button>
                            <div v-if="activeMenuId === order.id_order" 
                                 class="absolute right-0 top-full mt-1 w-48 bg-white rounded-md shadow-xl border border-slate-100 z-50 overflow-hidden origin-top-right animate-in fade-in slide-in-from-top-2 duration-200"
                                 @click.stop>
                                <div class="py-1">
                                    <a href="#" @click.prevent="openOrderDetails(order.id_order)" 
                                       class="group flex items-center px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 hover:text-blue-600 transition-colors">
                                        <i class="fa-solid fa-eye w-5 text-slate-400 group-hover:text-blue-500"></i> Szczegóły zamówienia
                                    </a>
                                    <a href="#" class="group flex items-center px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 hover:text-blue-600 transition-colors">
                                        <i class="fa-solid fa-print w-5 text-slate-400 group-hover:text-blue-500"></i> Przygotuj etykietę
                                    </a>
                                    <a href="#" class="group flex items-center px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 hover:text-blue-600 transition-colors">
                                        <i class="fa-solid fa-file-invoice w-5 text-slate-400 group-hover:text-blue-500"></i> Wystaw fakturę
                                    </a>
                                    <div class="border-t border-slate-100 my-1"></div>
                                    
                                    <a :href="order.packing_link" target="_blank" class="group flex items-center px-4 py-2 text-sm text-slate-700 hover:bg-emerald-50 hover:text-emerald-600 transition-colors">
                                        <i class="fa-solid fa-box-open w-5 text-slate-400 group-hover:text-emerald-500"></i> <span class="font-semibold">Pakuj</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <footer class="h-14 bg-white border-t border-slate-200 flex justify-between items-center px-6 shrink-0 z-10 text-sm" v-if="orders.length > 0">
        <div class="flex items-center gap-2 text-slate-500 font-normal">
            <span>Pokaż</span>
            <select v-model="itemsPerPage" class="border border-slate-300 rounded px-2 py-1 bg-white focus:outline-none focus:border-blue-500 cursor-pointer text-slate-700 text-xs font-normal shadow-sm">
                <option v-for="opt in itemsPerPageOptions" :key="opt" :value="opt">{{ opt }}</option>
            </select>
            <span>wpisów</span>
        </div>

        <div class="flex items-center gap-1" v-if="totalPages > 1">
            <button @click="prevPage" :disabled="currentPage === 1" class="px-3 py-1.5 rounded border text-xs font-medium transition-colors" :class="currentPage === 1 ? 'bg-slate-50 text-slate-300 border-slate-200 cursor-not-allowed' : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50 hover:text-blue-600 shadow-sm'">Poprzednia</button>
            <div class="flex items-center gap-1 mx-2">
                <button v-for="(p, index) in visiblePages" :key="index" @click="setPage(p)" class="w-8 h-8 flex items-center justify-center rounded text-xs font-medium transition-colors" :class="{ 'bg-blue-600 text-white shadow-sm': p === currentPage, 'bg-white text-slate-600 hover:bg-slate-100 hover:text-blue-600': p !== currentPage && p !== '...', 'text-slate-400 cursor-default': p === '...' }">{{ p }}</button>
            </div>
            <button @click="nextPage" :disabled="currentPage === totalPages" class="px-3 py-1.5 rounded border text-xs font-medium transition-colors" :class="currentPage === totalPages ? 'bg-slate-50 text-slate-300 border-slate-200 cursor-not-allowed' : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50 hover:text-blue-600 shadow-sm'">Następna</button>
        </div>
        
        <div class="text-slate-400 text-xs hidden sm:block" v-else>
            Wyświetlono wszystkich {{ sortedOrders.length }} zamówień
        </div>
    </footer>

</main>
{/literal}