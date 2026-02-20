<?php

class AzadaSettingsTechnical
{
    public static function getForm($module, $baseUrl)
    {
        $js = self::getJavascriptLogic($baseUrl);
        
        // Pobieramy aktualny token ręcznie, bo użyjemy własnego pola HTML
        $currentToken = Configuration::get('AZADA_CRON_KEY');

        // --- BUDUJEMY WŁASNY WYGLĄD POLA Z TOKENEM (Flexbox) ---
        $customTokenField = '
        <div class="form-group">
            <label class="control-label col-lg-3 required">Twój Token</label>
            <div class="col-lg-9">
                <div style="display: flex; align-items: center; gap: 10px; max-width: 600px;">
                    <input type="text" 
                           name="AZADA_CRON_KEY" 
                           id="AZADA_CRON_KEY" 
                           value="'.$currentToken.'" 
                           class="form-control" 
                           style="flex: 1;">
                    
                    <button id="btn-generate-token" class="btn btn-default" type="button" style="white-space: nowrap;">
                        <i class="icon-random"></i> Generuj nowy
                    </button>
                </div>
                <p class="help-block" style="margin-top:5px;">Ciąg znaków zabezpieczający wywołanie skryptów. Musi być trudny do zgadnięcia.</p>
            </div>
        </div>';

        return [
            'form' => [
                'legend' => ['title' => '1. Adresy skryptów (CRON)', 'icon' => 'icon-cogs'],
                'input' => [
                    ['type' => 'html', 'name' => 'js_logic', 'html_content' => $js],
                    
                    // Linki CRON
                    [
                        'type' => 'text', 'label' => 'Adres pliku CRON (Import)',
                        'name' => 'VIEW_CRON_IMPORT', 'id' => 'VIEW_CRON_IMPORT',
                        'class' => 'col-lg-9', 
                        'readonly' => true,
                        'desc' => 'Zalecane wywoływanie: co 2 godziny.',
                    ],
                    [
                        'type' => 'text', 'label' => 'Adres pliku UPDATE',
                        'name' => 'VIEW_CRON_UPDATE', 'id' => 'VIEW_CRON_UPDATE',
                        'class' => 'col-lg-9',
                        'readonly' => true,
                        'desc' => 'Zalecane wywoływanie: co 15 minut.',
                    ],
                    
                    // Przełącznik
                    [
                        'type' => 'switch', 'label' => 'Używaj tokenu bezpieczeństwa',
                        'name' => 'AZADA_USE_SECURE_TOKEN',
                        'is_bool' => true,
                        'values' => [['id' => 'on', 'value' => 1, 'label' => 'Tak'], ['id' => 'off', 'value' => 0, 'label' => 'Nie']],
                    ],
                    
                    // --- NASZE WŁASNE POLE HTML ZAMIAST STANDARDOWEGO ---
                    [
                        'type' => 'html',
                        'name' => 'custom_token_html',
                        'html_content' => $customTokenField
                    ],
                ],
                'submit' => ['title' => 'Zapisz sekcję Techniczną', 'class' => 'btn btn-primary pull-right']
            ]
        ];
    }

    private static function getJavascriptLogic($baseUrl)
    {
        return "
        <script>
            $(document).ready(function() {
                var baseUrl = '{$baseUrl}';

                function updateCronLinks() {
                    var token = $('#AZADA_CRON_KEY').val();
                    var useToken = $('input[name=\"AZADA_USE_SECURE_TOKEN\"]:checked').val() == 1;
                    
                    var tokenPart = '';
                    if (useToken && token.length > 0) {
                        tokenPart = '&token=' + token;
                    }

                    $('#VIEW_CRON_IMPORT').val(baseUrl + '?action=import' + tokenPart);
                    $('#VIEW_CRON_UPDATE').val(baseUrl + '?action=update' + tokenPart);
                }

                function generateRandomToken() {
                    var chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
                    var res = '';
                    for (var i = 32; i > 0; --i) res += chars[Math.floor(Math.random() * chars.length)];
                    return res;
                }

                $(document).on('click', '#btn-generate-token', function(e) {
                    e.preventDefault();
                    $('#AZADA_CRON_KEY').val(generateRandomToken());
                    // Delikatny efekt mignięcia
                    $('#AZADA_CRON_KEY').fadeOut(100).fadeIn(100);
                    updateCronLinks();
                });

                $(document).on('keyup change', '#AZADA_CRON_KEY', updateCronLinks);
                
                $(document).on('change', 'input[name=\"AZADA_USE_SECURE_TOKEN\"]', function() {
                    updateCronLinks();
                });
                
                setTimeout(updateCronLinks, 500);
            });
        </script>";
    }
}