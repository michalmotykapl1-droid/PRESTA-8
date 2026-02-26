{**
* 2007-2025 PrestaShop — ThemeVolty tvcmsmegamenu
* Nadpisanie helpers/form aby ukrywać sekcje + doładować JS/CSS w BO
*}

{extends file="helpers/form/form.tpl"}

{block name="field"}
    {if $input.type == 'select_link'}
        <select class="form-control fixed-width-xxl ps_link" name="ps_link" id="ps_link">
            {$all_options|escape:'quotes':'UTF-8'}
        </select>
        <script type="text/javascript">
            var type_link = {$type_link|intval};
            {if $type_link == 1}
            $(document).ready(function() {
                $("#ps_link").val('{if isset($ps_link_value) &&  $ps_link_value != ''}{$ps_link_value|escape:"html":"UTF-8"}{/if}');
            });
            {else}
                $('.ps_link').parent('.form-group').css('display','none');
            {/if}
        </script>
    {/if}

    {* --- NOWE: Pole wyboru pliku TPL --- *}
    {if $input.name == 'custom_tpl_file'}
        <div class="tv-menu-custom-tpl">{$smarty.block.parent}</div>
        <script type="text/javascript">
            var type_link = {$type_link|intval};
            $(document).ready(function () {
                // Pokaż tylko jeśli typ to 6 (Custom TPL)
                if (type_link != 6) {
                    $('.tv-menu-custom-tpl').parent('.form-group').css('display','none');
                }
            });
        </script>
    {/if}

    {if $input.name == 'title'}
        <div class="tv-menu-title">{$smarty.block.parent}</div>
        <script type="text/javascript">
        var type_link = {$type_link|intval};
        // Ukryj tytuł dla Presta Link (1) i Product (4), pokaż dla TPL (6)
        if (type_link == 1 || type_link == 4) {
            $('.tv-menu-title').parent('.form-group').css('display','none');
        }
        </script>
    {elseif $input.name == 'link'}
        <div class="tv-menu-link">{$smarty.block.parent}</div>
        <script type="text/javascript">
        var type_link = {$type_link|intval};
        // Ukryj link dla Presta (1), Product (4) i TPL (6)
        if (type_link == 1 || type_link == 4 || type_link == 6) {
            $('.tv-menu-link').parent('.form-group').css('display','none');
        }
        </script>
    {elseif $input.name == 'text'}
        <div class="tv-menu-text">{$smarty.block.parent}</div>
        <script type="text/javascript">
        var type_link = {$type_link|intval};
        // Ukryj HTML dla Presta (1), Custom (2), Product (4) i TPL (6)
        if (type_link == 1 || type_link == 2 || type_link == 4 || type_link == 6) {
            $('.tv-menu-text').parent('.form-group').css('display','none');
        }
        </script>
    {elseif $input.name == 'id_product'}
        <div class="tv-menu-product">{$smarty.block.parent}</div>
        <script type="text/javascript">
        var type_link = {$type_link|intval};
        // Ukryj Produkt dla innych typów
        if (type_link == 1 || type_link == 2 || type_link == 3 || type_link == 6) {
            $('.tv-menu-product').parent('.form-group').css('display','none');
        }
        </script>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}

{block name="footer"}
  {$smarty.block.parent}
  {assign var=modurl value=$smarty.const._MODULE_DIR_|cat:"tvcmsmegamenu/"}
  <link rel="stylesheet" href="{$modurl}views/css/bo_dynproducts.css">
  <script src="{$modurl}views/js/bo_dynproducts.js"></script>

  {* JS do obsługi zmiany typu linku na żywo *}
  <script type="text/javascript">
    $(document).ready(function(){
        $('select[name="type_link"]').change(function(){
            var val = $(this).val();

            // Ukryj wszystko na start
            $('.ps_link').parent('.form-group').hide();
            $('.tv-menu-link').parent('.form-group').hide();
            $('.tv-menu-text').parent('.form-group').hide();
            $('.tv-menu-product').parent('.form-group').hide();
            $('.tv-menu-custom-tpl').parent('.form-group').hide();
            $('.tv-menu-title').parent('.form-group').hide();

            // Pokaż odpowiednie pola
            if (val == 1) { // Presta Link
                $('.ps_link').parent('.form-group').show();
                // tytuł ukryty (jak w logice powyżej)
            }
            else if (val == 2) { // Custom Link
                $('.tv-menu-link').parent('.form-group').show();
                $('.tv-menu-title').parent('.form-group').show();
            }
            else if (val == 3) { // HTML Block
                $('.tv-menu-text').parent('.form-group').show();
                $('.tv-menu-title').parent('.form-group').show();
            }
            else if (val == 4) { // Product
                $('.tv-menu-product').parent('.form-group').show();
                // tytuł ukryty
            }
            else if (val == 6) { // Custom TPL
                $('.tv-menu-custom-tpl').parent('.form-group').show();
                $('.tv-menu-title').parent('.form-group').show(); // Opcjonalnie tytuł
            }
        });
    });
  </script>
{/block}
