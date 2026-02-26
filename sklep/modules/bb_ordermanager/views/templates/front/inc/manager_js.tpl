{include file='module:bb_ordermanager/views/templates/front/inc/js_parts/js_data.tpl'}
{include file='module:bb_ordermanager/views/templates/front/inc/js_parts/js_computed.tpl'}
{include file='module:bb_ordermanager/views/templates/front/inc/js_parts/js_methods_auth.tpl'}
{include file='module:bb_ordermanager/views/templates/front/inc/js_parts/js_methods_products.tpl'}
{include file='module:bb_ordermanager/views/templates/front/inc/js_parts/js_methods_orders.tpl'}
{include file='module:bb_ordermanager/views/templates/front/inc/js_parts/js_methods_logistics.tpl'}
{include file='module:bb_ordermanager/views/templates/front/inc/js_parts/js_methods_finance.tpl'}

{literal}
<script>
    const { createApp } = Vue;
    createApp({
        data() {
            return AppData;
        },
        computed: AppComputed,
        methods: {
            ...AppMethodsAuth,
            ...AppMethodsOrders,
            ...AppMethodsProducts,
            ...AppMethodsLogistics,
            ...AppMethodsFinance,
            
            getFolderColor(name) {
                if (!name) return '#cbd5e1';
                for (const section of this.menu) {
                    const found = section.items.find(i => i.label === name);
                    if (found) {
                        return found.color_hex || '#cbd5e1';
                    }
                }
                return '#cbd5e1';
            }
        },
        async mounted() { 
            // Najpierw sprawdź sesję pracownika (bez "flash" logowania)
            await this.checkAuth();

            // Jeśli zalogowany -> ładujemy listę i ewentualnie otwieramy konkretne zamówienie
            if (this.authLogged) {
                await this.fetchOrders();

                const urlParams = new URLSearchParams(window.location.search);
                const openId = urlParams.get('open_order_id');
                if (openId) { setTimeout(() => { this.openOrderDetails(openId); }, 100); }
            }

            document.addEventListener('click', this.closeMenu);
        },
        beforeUnmount() { document.removeEventListener('click', this.closeMenu); }
    }).mount('#app')
</script>
{/literal}