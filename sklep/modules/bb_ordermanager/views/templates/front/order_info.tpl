<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zamówienie #{$order.reference}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Open Sans', sans-serif; background-color: #f1f5f9; }
        
        .step-current { background-color: #10b981; color: white; border: 4px solid #d1fae5; box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.4); transform: scale(1.15); z-index: 30; }
        .step-done { background-color: #ecfdf5; color: #10b981; border: 2px solid #10b981; z-index: 20; }
        .step-todo { background-color: white; color: #cbd5e1; border: 2px solid #e2e8f0; z-index: 10; }
        .text-current { color: #059669; font-weight: 800; }
        .text-done { color: #10b981; font-weight: 600; opacity: 0.7; }
        .text-todo { color: #94a3b8; font-weight: 500; }

        .loader { border: 4px solid #f3f3f3; border-top: 4px solid #10b981; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body class="text-slate-800 min-h-screen flex flex-col">

    <header class="bg-white border-b border-slate-200 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <a href="{$shop_url}" class="transition-opacity hover:opacity-80">
                    <img src="{$logo_url}" alt="{$shop_name}" class="h-10 w-auto object-contain">
                </a>
            </div>
            <div class="text-right">
                <div class="text-[10px] text-slate-400 uppercase font-bold tracking-wide">Zamówienie</div>
                <div class="font-mono text-lg font-bold text-slate-800">#{$order.reference}</div>
            </div>
        </div>
    </header>

    <div class="flex-1 w-full max-w-7xl mx-auto p-4 sm:p-6 lg:p-8 space-y-8">

        <div class="bg-white rounded-xl shadow-md border border-slate-200 p-8 sm:p-12 pb-16 relative overflow-hidden">
            {if $is_cancelled}
                <div class="flex flex-col items-center justify-center text-red-600 py-8">
                    <i class="fa-solid fa-ban text-4xl mb-4 text-red-500"></i>
                    <h2 class="text-2xl font-bold mb-2">Zamówienie Anulowane</h2>
                </div>
            {else}
                <div class="relative w-full px-4 mt-4">
                    <div class="absolute top-1/2 left-0 w-full h-1.5 bg-slate-100 -translate-y-1/2 rounded-full z-0"></div>
                    <div class="absolute top-1/2 left-0 h-1.5 bg-emerald-500 -translate-y-1/2 rounded-full transition-all duration-1000 z-0" style="width: {if $current_step == 1}0%{elseif $current_step == 2}25%{elseif $current_step == 3}50%{elseif $current_step == 4}75%{else}100%{/if}%"></div>
                    <div class="relative z-10 flex justify-between w-full">
                        <div class="flex flex-col items-center justify-center w-10">
                            <div class="w-12 h-12 rounded-full flex items-center justify-center transition-all {if $current_step == 1}step-current{elseif $current_step > 1}step-done{else}step-todo{/if}"><i class="fa-solid fa-file-circle-check text-lg"></i></div>
                            <span class="absolute top-16 text-xs uppercase tracking-wide text-center w-32 font-bold {if $current_step == 1}text-current{elseif $current_step > 1}text-done{else}text-todo{/if}">Przyjęte</span>
                        </div>
                        <div class="flex flex-col items-center justify-center w-10">
                            <div class="w-12 h-12 rounded-full flex items-center justify-center transition-all {if $current_step == 2}step-current{elseif $current_step > 2}step-done{else}step-todo{/if}"><i class="fa-solid fa-arrows-rotate text-lg {if $current_step == 2}fa-spin{/if}"></i></div>
                            <span class="absolute top-16 text-xs uppercase tracking-wide text-center w-32 font-bold {if $current_step == 2}text-current{elseif $current_step > 2}text-done{else}text-todo{/if}">W realizacji</span>
                        </div>
                        <div class="flex flex-col items-center justify-center w-10">
                            <div class="w-12 h-12 rounded-full flex items-center justify-center transition-all {if $current_step == 3}step-current{elseif $current_step > 3}step-done{else}step-todo{/if}"><i class="fa-solid fa-box-open text-lg {if $current_step == 3}animate-bounce{/if}"></i></div>
                            <span class="absolute top-16 text-xs uppercase tracking-wide text-center w-32 font-bold {if $current_step == 3}text-current{elseif $current_step > 3}text-done{else}text-todo{/if}">Spakowane</span>
                        </div>
                        <div class="flex flex-col items-center justify-center w-10">
                            <div class="w-12 h-12 rounded-full flex items-center justify-center transition-all {if $current_step == 4}step-current{elseif $current_step > 4}step-done{else}step-todo{/if}"><i class="fa-solid fa-truck-fast text-lg {if $current_step == 4}animate-pulse{/if}"></i></div>
                            <span class="absolute top-16 text-xs uppercase tracking-wide text-center w-32 font-bold {if $current_step == 4}text-current{elseif $current_step > 4}text-done{else}text-todo{/if}">Wysłane</span>
                        </div>
                        <div class="flex flex-col items-center justify-center w-10">
                            <div class="w-12 h-12 rounded-full flex items-center justify-center transition-all {if $current_step == 5}step-current{else}step-todo{/if}"><i class="fa-solid fa-house-chimney text-lg"></i></div>
                            <span class="absolute top-16 text-xs uppercase tracking-wide text-center w-32 font-bold {if $current_step == 5}text-current{else}text-todo{/if}">W drodze</span>
                        </div>
                    </div>
                </div>
                <div class="mt-24 bg-emerald-50 border-l-4 border-emerald-500 p-6 rounded-r-lg shadow-sm">
                    <div class="flex items-start gap-4">
                        <div class="text-emerald-500 text-2xl mt-0.5"><i class="fa-solid fa-circle-info"></i></div>
                        <div>
                            <p class="text-slate-700 leading-relaxed text-base font-medium">{$status_desc}</p>
                            {if $tracking}
                                <div class="mt-4 flex items-center gap-3">
                                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wide">Nr przesyłki:</span>
                                    <div class="flex items-center bg-white border border-emerald-200 rounded overflow-hidden shadow-sm group cursor-pointer" onclick="copyText('{$tracking}')">
                                        <span class="px-3 py-1 font-mono font-bold text-emerald-700 select-all">{$tracking}</span>
                                        <div class="px-2 py-1 bg-emerald-50 border-l border-emerald-100 text-emerald-400 group-hover:text-emerald-600 transition-colors">
                                            <i class="fa-regular fa-copy text-xs"></i>
                                        </div>
                                    </div>
                                </div>
                            {/if}
                        </div>
                    </div>
                </div>
            {/if}
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 font-bold text-slate-700 flex justify-between">
                    <span>Zamówione produkty</span>
                    <span class="bg-white border px-2 py-0.5 rounded text-xs font-bold text-slate-500">{$products|count} szt.</span>
                </div>
                <div class="divide-y divide-slate-100">
                    {foreach from=$products item=product}
                    <div class="flex items-center gap-5 p-5 hover:bg-slate-50 transition-colors">
                        <div class="w-14 h-14 bg-white border border-slate-200 rounded-lg flex items-center justify-center p-1 shrink-0">
                            {if $product.image_url}
                                <img src="{$product.image_url}" class="max-w-full max-h-full object-contain">
                            {else}
                                <i class="fa-solid fa-box text-slate-300 text-xl"></i>
                            {/if}
                        </div>
                        <div class="flex-1">
                            <div class="font-bold text-slate-800 text-sm">{$product.product_name}</div>
                            <div class="text-xs text-slate-500">Ilość: {$product.product_quantity}</div>
                        </div>
                        <div class="text-right font-bold text-slate-700 text-sm">
                            {Tools::displayPrice($product.total_price_tax_incl)}
                        </div>
                    </div>
                    {/foreach}
                </div>
                <div class="bg-slate-50 px-6 py-5 border-t border-slate-200 text-sm text-slate-500 text-right">
                    <div class="mb-1">Dostawa: <span class="font-bold text-slate-700">{Tools::displayPrice($order.total_shipping)}</span></div>
                    <div class="text-lg font-bold text-slate-800 mt-2">Razem: <span class="text-emerald-600">{Tools::displayPrice($order.total_paid)}</span></div>
                </div>
            </div>

            <div class="space-y-6">
                
                {if $discount_code && $is_paid}
                <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl shadow-lg text-white overflow-hidden relative border border-indigo-400">
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center"><i class="fa-solid fa-gift text-yellow-300"></i></div>
                            <h3 class="font-bold text-lg">Twój kod na -{$reduction_percent|intval}%</h3>
                        </div>
                        <p class="text-indigo-100 text-xs mb-4">Użyj go w naszym sklepie oficjalnym!</p>
                        
                        <div class="bg-white/10 border border-white/20 rounded-lg p-3 text-center mb-4 cursor-pointer hover:bg-white/20 transition-colors group" onclick="copyText('{$discount_code}')">
                            <div class="font-mono text-xl font-black tracking-wider text-yellow-300 group-hover:scale-105 transition-transform">{$discount_code}</div>
                            <div class="text-[10px] text-white/60 uppercase tracking-wide mt-1">Kliknij by skopiować</div>
                        </div>

                        <div class="flex justify-between items-center text-xs text-indigo-200 border-t border-white/10 pt-3">
                            <span>Ważność:</span>
                            <span class="font-bold text-white"><i class="fa-regular fa-clock mr-1"></i> {$days_left} dni</span>
                        </div>
                        
                        <a href="{$shop_url}" class="block w-full bg-white text-indigo-600 text-center font-bold py-2 rounded mt-4 hover:bg-indigo-50 transition-colors text-sm shadow-md">
                            Przejdź do sklepu <i class="fa-solid fa-arrow-right ml-1"></i>
                        </a>
                        <div class="text-[10px] text-center mt-2 text-white/50">Dodaj do ulubionych (Ctrl+D)</div>
                    </div>
                </div>
                {/if}

                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 font-bold text-slate-700"><i class="fa-solid fa-truck text-slate-400 mr-2"></i> Dostawa</div>
                    <div class="p-6 text-sm">
                        <div class="font-bold text-slate-800 mb-2">{$carrier_name}</div>
                        {if $pickup_point}
                            <div class="bg-indigo-50 border border-indigo-100 rounded-lg p-3">
                                <div class="text-indigo-900 font-bold mb-1">{$pickup_point.id}</div>
                                <div class="text-slate-700">{$pickup_point.name}</div>
                                <div class="text-xs text-slate-500">{$pickup_point.address}</div>
                            </div>
                        {else}
                            <div class="text-slate-700">
                                {$order.firstname} {$order.lastname}<br>
                                {$address.address1}<br>
                                {$address.postcode} {$address.city}
                            </div>
                        {/if}
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden p-6 text-center">
                    <div class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fa-solid fa-life-ring text-lg"></i>
                    </div>
                    <h4 class="font-bold text-slate-800 text-sm mb-1">Masz pytania?</h4>
                    <p class="text-xs text-slate-500 mb-4">Skontaktuj się z nami.</p>
                    <a href="{$shop_url}index.php?controller=contact" class="block w-full bg-white border border-slate-300 hover:border-emerald-500 hover:text-emerald-600 text-slate-600 font-bold py-2 rounded transition-all text-xs uppercase tracking-wide">
                        Kontakt ze sklepem
                    </a>
                </div>
            </div>
        </div>
    </div>

    {if !$discount_code && $is_paid}
    <div class="w-full bg-gradient-to-r from-violet-600 to-indigo-600 text-white shadow-inner mt-auto border-t-4 border-yellow-400">
        <div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="flex items-center gap-5">
                <div class="hidden sm:flex w-14 h-14 bg-white/10 rounded-full items-center justify-center shrink-0 backdrop-blur-sm border border-white/20">
                    <i class="fa-solid fa-gift text-2xl text-yellow-300 animate-pulse"></i>
                </div>
                <div>
                    {if $is_allegro}
                        <h2 class="text-xl sm:text-2xl font-extrabold mb-1 tracking-tight">Kupujesz na Allegro?</h2>
                    {else}
                        <h2 class="text-xl sm:text-2xl font-extrabold mb-1 tracking-tight">Dziękujemy za zakupy!</h2>
                    {/if}
                    <p class="text-indigo-100 text-sm sm:text-base font-medium">Odbierz kod na <span class="font-bold text-yellow-300">{$reduction_percent|intval}% rabatu</span> do wykorzystania w naszym sklepie internetowym: <strong>{$shop_name}</strong>.</p>
                </div>
            </div>
            <button onclick="openDiscountModal()" class="bg-yellow-400 hover:bg-yellow-300 text-indigo-900 font-extrabold py-3 px-8 rounded-full shadow-lg transform hover:-translate-y-1 transition-all duration-200 text-center whitespace-nowrap flex items-center gap-2 group text-sm sm:text-base">
                ODBIERZ KOD <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
            </button>
        </div>
        <div class="bg-black/20 py-2 text-center text-[10px] text-white/40 uppercase tracking-widest font-bold">
            &copy; {$smarty.now|date_format:"%Y"} {$shop_name}
        </div>
    </div>
    {else}
    <div class="bg-white border-t border-slate-200 py-6 mt-auto">
        <div class="max-w-6xl mx-auto px-4 text-center text-slate-400 text-sm">
            &copy; {$smarty.now|date_format:"%Y"} <strong>{$shop_name}</strong>
        </div>
    </div>
    {/if}

    <div id="toast-notification" class="fixed bottom-10 left-1/2 transform -translate-x-1/2 bg-slate-800 text-white px-6 py-3 rounded-full shadow-2xl opacity-0 transition-opacity duration-300 pointer-events-none z-[100] flex items-center gap-3">
        <i class="fa-solid fa-circle-check text-green-400 text-xl"></i>
        <span class="font-bold text-sm tracking-wide">Skopiowano kod do schowka!</span>
    </div>

    <div id="discountModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[100] hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden transform transition-all scale-95 opacity-0" id="modalContent">
            <div class="bg-gradient-to-r from-violet-600 to-indigo-600 p-6 text-white text-center relative">
                <button onclick="closeModal()" class="absolute top-4 right-4 text-white/60 hover:text-white transition-colors"><i class="fa-solid fa-xmark text-xl"></i></button>
                <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4 backdrop-blur-md"><i class="fa-solid fa-ticket text-3xl text-yellow-300"></i></div>
                <h3 class="text-xl font-bold">Twój osobisty rabat</h3>
                <p class="text-indigo-100 text-xs mt-1">Tylko w naszym sklepie oficjalnym!</p>
            </div>
            <div class="p-8 text-center">
                <div id="modalLoading" class="flex flex-col items-center py-4">
                    <div class="loader mb-4"></div>
                    <p class="text-slate-600 font-medium">Generowanie unikalnego kodu...</p>
                </div>
                <div id="modalResult" class="hidden">
                    <p class="text-sm text-slate-500 mb-4">Oto Twój kod rabatowy ważny przez 30 dni:</p>
                    <div class="bg-indigo-50 border-2 border-indigo-100 rounded-lg p-4 mb-6 relative group cursor-pointer" onclick="copyText(document.getElementById('discountCode').innerText)">
                        <div class="font-mono text-2xl font-black text-indigo-700 tracking-wider" id="discountCode">...</div>
                        <div class="text-[10px] text-indigo-400 mt-1 uppercase font-bold tracking-wide">Kliknij, aby skopiować</div>
                    </div>
                    <div class="space-y-3">
                        <a href="{$shop_url}" class="block w-full bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-3 rounded-lg shadow-lg shadow-emerald-500/30 transition-all transform hover:-translate-y-0.5">
                            Przejdź do sklepu <i class="fa-solid fa-arrow-right-from-bracket ml-2"></i>
                        </a>
                        <div class="text-xs text-slate-400">
                            <span class="inline-flex items-center gap-1"><i class="fa-solid fa-star text-yellow-400"></i> Wciśnij <strong>Ctrl + D</strong> aby dodać sklep do ulubionych</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {literal}
    <script>
        const ajaxUrl = "{/literal}{$ajax_url nofilter}&id_order={$order.id_order}&token={$order.reference|cat:$order.secure_key|md5}&action=generate_discount{literal}";

        function openDiscountModal() {
            const modal = document.getElementById('discountModal');
            const content = document.getElementById('modalContent');
            modal.classList.remove('hidden');
            setTimeout(() => { content.classList.remove('scale-95', 'opacity-0'); content.classList.add('scale-100', 'opacity-100'); }, 10);
            generateCode();
        }

        function closeModal() {
            const modal = document.getElementById('discountModal');
            const content = document.getElementById('modalContent');
            content.classList.remove('scale-100', 'opacity-100');
            content.classList.add('scale-95', 'opacity-0');
            setTimeout(() => { modal.classList.add('hidden'); }, 300);
            setTimeout(() => { location.reload(); }, 350);
        }

        function generateCode() {
            const loading = document.getElementById('modalLoading');
            const result = document.getElementById('modalResult');
            loading.classList.remove('hidden');
            result.classList.add('hidden');

            const minTime = new Promise(resolve => setTimeout(resolve, 2500));
            const fetchReq = fetch(ajaxUrl).then(r => { if (!r.ok) throw new Error('Błąd sieci'); return r.json(); });

            Promise.all([minTime, fetchReq]).then(([_, data]) => {
                if(data.success) {
                    document.getElementById('discountCode').innerText = data.code;
                    loading.classList.add('hidden');
                    result.classList.remove('hidden');
                } else {
                    if (data.message === 'Kod odzyskany' || data.message === 'Kod już istnieje') {
                        document.getElementById('discountCode').innerText = data.code;
                        loading.classList.add('hidden');
                        result.classList.remove('hidden');
                    } else {
                        alert('Błąd: ' + data.error);
                        closeModal();
                    }
                }
            }).catch(err => { console.error(err); alert('Błąd połączenia.'); closeModal(); });
        }

        function copyText(text) {
            navigator.clipboard.writeText(text).then(() => {
                showToast();
            }).catch(err => { console.error(err); });
        }

        function showToast() {
            const toast = document.getElementById('toast-notification');
            toast.classList.remove('opacity-0', 'translate-y-4');
            toast.classList.add('opacity-100', 'translate-y-0');
            setTimeout(() => {
                toast.classList.remove('opacity-100', 'translate-y-0');
                toast.classList.add('opacity-0', 'translate-y-4');
            }, 2500);
        }
    </script>
    {/literal}

</body>
</html>