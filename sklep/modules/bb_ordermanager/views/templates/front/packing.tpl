{* PLIK: modules/bb_ordermanager/views/templates/front/packing.tpl *}
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pakowanie #{$order->reference}</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    {include file='module:bb_ordermanager/views/templates/front/inc/packing_parts/css.tpl'}
</head>
<body>

    {include file='module:bb_ordermanager/views/templates/front/inc/packing_parts/sidebar.tpl'}
    {include file='module:bb_ordermanager/views/templates/front/inc/packing_parts/main_content.tpl'}
    {include file='module:bb_ordermanager/views/templates/front/inc/packing_parts/js.tpl'}

</body>
</html>