{literal}
<header class="bg-white border-b border-slate-300 h-14 shrink-0 flex items-center justify-between px-6 shadow-sm z-20">
    <div class="flex items-center gap-4">
        <button @click="closeDetails" class="flex items-center gap-2 text-slate-600 hover:text-blue-600 font-semibold transition-colors bg-white hover:bg-slate-50 border border-slate-300 px-3 py-1.5 rounded shadow-sm text-sm"><i class="fa-solid fa-chevron-left text-xs"></i> Wróć</button>
        <div class="h-6 w-px bg-slate-300 mx-2"></div>
        <div class="flex items-baseline gap-3"><i class="fa-regular fa-star text-slate-400 hover:text-yellow-400 cursor-pointer text-lg"></i><h2 class="text-xl font-normal text-slate-600">Zamówienie <span class="font-bold text-slate-800">{{ currentOrderDetails.id_order }}</span></h2><div class="text-xs text-slate-400">(Ref: {{ currentOrderDetails.reference }})</div></div>
    </div>
    <div class="flex items-center gap-2">
        <button @click="generatePublicLink" class="bg-white border border-slate-300 text-slate-600 px-3 py-1.5 rounded shadow-sm hover:text-blue-600 text-xs font-bold flex items-center gap-2 transition-colors">
            <i class="fa-solid fa-earth-europe"></i> Strona Zamówienia (Link)
        </button>
        <button class="bg-blue-600 text-white px-4 py-1.5 rounded font-bold hover:bg-blue-700 shadow-sm transition-colors text-sm flex items-center gap-2"><i class="fa-solid fa-plus"></i> Dodaj zamówienie</button>
    </div>
</header>
{/literal}