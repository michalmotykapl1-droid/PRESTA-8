{* PLIK: modules/bb_ordermanager/views/templates/front/order_detail.tpl *}
{literal}
<div v-if="activeView === 'detail'" 
     class="absolute inset-0 bg-[#f4f6f8] z-50 flex flex-col overflow-hidden animate-in slide-in-from-right duration-200 font-sans text-[13px]">

    <!-- Gdy currentOrderDetails jest jeszcze NULL (np. szybki powrót z pakowania) pokazujemy loader, 
         żeby nie było wrażenia, że wchodzi na listę i dopiero potem w szczegóły. -->
    <template v-if="!currentOrderDetails">
        <header class="h-14 bg-white border-b border-slate-300 flex items-center justify-between px-4 shadow-sm shrink-0">
            <div class="flex items-center gap-3">
                <button @click="closeDetails" class="text-slate-500 hover:text-blue-600 transition-colors" title="Wróć">
                    <i class="fa-solid fa-arrow-left"></i>
                </button>
                <div class="font-bold text-slate-700">Ładowanie zamówienia...</div>
            </div>
        </header>

        <div class="flex-1 flex items-center justify-center">
            <div class="text-center text-slate-500">
                <i class="fa-solid fa-circle-notch spin text-3xl mb-3 text-blue-500"></i>
                <div class="font-semibold">Pobieranie danych zamówienia...</div>
                <div class="text-xs text-slate-400 mt-1">To zwykle trwa chwilę.</div>
            </div>
        </div>
    </template>

    <template v-else>
        {/literal}{include file='module:bb_ordermanager/views/templates/front/inc/detail_parts/header.tpl'}{literal}

        <div class="flex-1 overflow-y-auto p-5 custom-scrollbar">
            <div class="w-full space-y-5">
                {/literal}
                {include file='module:bb_ordermanager/views/templates/front/inc/detail_parts/products.tpl'}
                {include file='module:bb_ordermanager/views/templates/front/inc/detail_parts/info_card.tpl'}
                {include file='module:bb_ordermanager/views/templates/front/inc/detail_parts/addresses.tpl'}
                {include file='module:bb_ordermanager/views/templates/front/inc/detail_parts/shipments.tpl'}
                {include file='module:bb_ordermanager/views/templates/front/inc/detail_parts/history.tpl'}
                {literal}
            </div>
        </div>

        {/literal}{include file='module:bb_ordermanager/views/templates/front/inc/detail_parts/modals.tpl'}{literal}
    </template>
</div>
{/literal}