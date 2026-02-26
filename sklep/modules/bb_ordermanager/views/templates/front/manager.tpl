{* PLIK: modules/bb_ordermanager/views/templates/front/manager.tpl *}
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BigBio Manager</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    {include file='module:bb_ordermanager/views/templates/front/inc/manager_css.tpl'}
</head>
<body>

<script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>

<div id="app" v-cloak class="flex h-screen w-full bg-slate-100 text-slate-800 font-sans relative overflow-hidden">
    
    {include file='module:bb_ordermanager/views/templates/front/inc/sidebar.tpl'}
    
    {include file='module:bb_ordermanager/views/templates/front/order_list.tpl'}
    
    {include file='module:bb_ordermanager/views/templates/front/order_detail.tpl'}
    
    {include file='module:bb_ordermanager/views/templates/front/inc/toast.tpl'}

    {include file='module:bb_ordermanager/views/templates/front/inc/login_modal.tpl'}

</div>

<script>
    const API_URL = "{$api_url nofilter}";
    const AUTH_URL = "{$auth_url nofilter}";
    const FV_API_URL = "{$fv_api_url nofilter}"; 
    const BBOM_MENU = {$bbom_menu_json nofilter};
</script>

{include file='module:bb_ordermanager/views/templates/front/inc/manager_js.tpl'}

</body>
</html>