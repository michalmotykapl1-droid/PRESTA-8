<div class="card border mb-3 ap-shipment-card">
    <div class="card-body p-3">
        <style>
            #allegropro_order_details .ap-shipment-card .ap-section-label {
                display: block;
                font-size: 11px;
                letter-spacing: .7px;
                font-weight: 700;
                color: #5f7182;
                margin-bottom: 8px;
                text-transform: uppercase;
            }
            #allegropro_order_details .ap-shipment-card .ap-panel {
                border: 1px solid #cfd7df;
                border-radius: 10px;
                background: #fff;
                padding: 10px;
            }
            #allegropro_order_details .ap-shipment-card .ap-field-wrap {
                display: flex;
                flex-direction: column;
                height: 100%;
            }
            #allegropro_order_details .ap-shipment-card .ap-field-label {
                min-height: 20px;
                margin-bottom: 6px;
            }
            #allegropro_order_details .ap-shipment-card .ap-button-group {
                display: flex;
                gap: 6px;
            }
            #allegropro_order_details .ap-shipment-card .ap-button-group .btn {
                flex: 1;
                border-radius: 7px;
                white-space: nowrap;
            }
            #allegropro_order_details .ap-shipment-card .ap-primary-row .form-control,
            #allegropro_order_details .ap-shipment-card .ap-primary-row .btn,
            #allegropro_order_details .ap-shipment-card .ap-primary-row .ap-panel {
                min-height: 46px;
            }
            #allegropro_order_details .ap-shipment-card .ap-create-col {
                display: flex;
                align-items: center;
            }
            #allegropro_order_details .ap-shipment-card .ap-create-col .btn {
                width: 100%;
                max-width: 340px;
                height: 62px;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: 2px;
                font-weight: 700;
                line-height: 1.05;
                padding: 8px 10px;
                white-space: normal;
                margin-right: -12px;
            }
            #allegropro_order_details .ap-shipment-card .ap-create-main {
                display: block;
                font-size: 20px;
                letter-spacing: 1.1px;
                text-transform: uppercase;
            }
            #allegropro_order_details .ap-shipment-card .ap-create-sub {
                display: block;
                font-size: 20px;
                letter-spacing: 1.1px;
                text-transform: uppercase;
            }
            #allegropro_order_details .ap-shipment-card .ap-weight-layout {
                display: grid;
                grid-template-columns: 1.2fr 1fr;
                gap: 8px;
                align-items: center;
            }
            #allegropro_order_details .ap-shipment-card .ap-grid-3 {
                display: grid;
                grid-template-columns: repeat(3, minmax(120px, 1fr));
                gap: 8px;
            }
            #allegropro_order_details .ap-shipment-card .ap-help-list {
                margin: 0;
                padding-left: 16px;
            }
            #allegropro_order_details .ap-shipment-card .ap-help-list li {
                margin-bottom: 4px;
                color: #6c757d;
                font-size: 12px;
                line-height: 1.45;
            }
            @media (max-width: 991px) {
                #allegropro_order_details .ap-shipment-card .ap-primary-row > [class*='col-'] {
                    margin-bottom: 10px;
                }
                #allegropro_order_details .ap-shipment-card .ap-button-group {
                    flex-wrap: wrap;
                }
                #allegropro_order_details .ap-shipment-card .ap-weight-layout {
                    grid-template-columns: 1fr;
                }
                #allegropro_order_details .ap-shipment-card .ap-create-col {
                    align-items: stretch;
                }
                #allegropro_order_details .ap-shipment-card .ap-create-col .btn {
                    max-width: none;
                    height: auto;
                    min-height: 50px;
                    margin-right: 0;
                }
            }
        </style>

        <label class="mb-2"><strong>Nadaj nową paczkę:</strong></label>

        <div class="form-row mb-2 align-items-stretch ap-primary-row">
            <div class="col-md-4 mb-2 mb-md-0">
                <div class="ap-field-wrap">
                    <span class="ap-section-label ap-field-label">Gabaryt paczki</span>
                    <select id="shipment_size_select" class="form-control">
                        {assign var=sizeOptions value=$allegro_data.shipment_size_options.options|default:[]}
                        {foreach from=$sizeOptions item=sizeOption}
                            <option value="{$sizeOption.value|escape:'htmlall':'UTF-8'}"{if $sizeOption.value == 'CUSTOM'} selected{/if}>{$sizeOption.label|escape:'htmlall':'UTF-8'}</option>
                        {/foreach}
                    </select>
                </div>
            </div>

            <div class="col-md-5 mb-2 mb-md-0">
                <input type="hidden" id="shipment_weight_source" value="CONFIG">
                <div class="ap-panel ap-weight-panel ap-field-wrap h-100">
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:8px;">
                        <span class="ap-section-label" style="margin-bottom:0;">Waga paczki (kg)</span>
                        <span class="ap-section-label" style="margin-bottom:0;">Wybór wagi</span>
                    </div>
                    <div class="ap-weight-layout">
                        <input
                            type="text"
                            id="shipment_weight_input"
                            class="form-control"
                            value="{$allegro_data.shipment_weight_defaults.manual_default|default:1.0|escape:'htmlall':'UTF-8'}"
                            placeholder="Np. 2.50"
                            data-manual-default="{$allegro_data.shipment_weight_defaults.manual_default|default:1.0|escape:'htmlall':'UTF-8'}"
                            data-config-weight="{$allegro_data.shipment_weight_defaults.config_weight|default:1.0|escape:'htmlall':'UTF-8'}"
                            data-products-weight="{$allegro_data.shipment_weight_defaults.products_weight|default:''|escape:'htmlall':'UTF-8'}"
                        >
                        <div class="ap-button-group">
                            <button type="button" id="weight_mode_config" class="btn btn-sm btn-primary" data-weight-mode="CONFIG">Konfiguracja</button>
                            <button type="button" id="weight_mode_products" class="btn btn-sm btn-default" data-weight-mode="PRODUCTS">Z produktów</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 ap-create-col">
                <button class="btn btn-info btn-block" type="button" id="btnCreateShipment"><span class="ap-create-main">UTWÓRZ</span><span class="ap-create-sub">PRZESYŁKĘ</span></button>
            </div>
        </div>

        <input type="hidden" id="shipment_dimension_source" value="CONFIG">
        <div class="mb-2 ap-panel" id="shipment_dimensions_panel" style="{if $allegro_data.carrier_mode == 'COURIER'}display:block;{else}display:none;{/if}">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px; gap:8px;">
                <span class="ap-section-label" style="margin-bottom:0;">Wymiary paczki (cm)</span>
                <div class="ap-button-group" style="max-width:250px; width:100%;">
                    <button type="button" id="dimension_mode_config" class="btn btn-sm btn-primary" data-dimension-mode="CONFIG">Konfiguracja</button>
                    <button type="button" id="dimension_mode_manual" class="btn btn-sm btn-default" data-dimension-mode="MANUAL">Ręcznie</button>
                </div>
            </div>
            <div class="ap-grid-3">
                <input type="number" min="1" step="1" id="shipment_length_input" class="form-control" placeholder="Długość" value="{$allegro_data.shipment_dimension_defaults.manual_default_length|default:10|intval}" data-manual-default="{$allegro_data.shipment_dimension_defaults.manual_default_length|default:10|intval}" data-config-value="{$allegro_data.shipment_dimension_defaults.config_length|default:10|intval}">
                <input type="number" min="1" step="1" id="shipment_width_input" class="form-control" placeholder="Szerokość" value="{$allegro_data.shipment_dimension_defaults.manual_default_width|default:10|intval}" data-manual-default="{$allegro_data.shipment_dimension_defaults.manual_default_width|default:10|intval}" data-config-value="{$allegro_data.shipment_dimension_defaults.config_width|default:10|intval}">
                <input type="number" min="1" step="1" id="shipment_height_input" class="form-control" placeholder="Wysokość" value="{$allegro_data.shipment_dimension_defaults.manual_default_height|default:10|intval}" data-manual-default="{$allegro_data.shipment_dimension_defaults.manual_default_height|default:10|intval}" data-config-value="{$allegro_data.shipment_dimension_defaults.config_height|default:10|intval}">
            </div>
        </div>

        <ul class="ap-help-list mb-2">
            <li>{$allegro_data.shipment_size_options.help_text|default:'Dla gabarytów A/B/C Allegro użyje stałych wymiarów z backendu. Przy "Własny gabaryt" używana jest tylko waga.'|escape:'htmlall':'UTF-8'}</li>
            <li>Wybierz źródło wagi (Konfiguracja / Z produktów), a pole obok zostanie automatycznie uzupełnione. W każdej chwili możesz je ręcznie nadpisać.</li>
            <li>Dla metod kurierskich pola długość / szerokość / wysokość są dostępne od razu i można je edytować.</li>
        </ul>

        <small class="form-text text-muted mb-2" style="font-size:11px;">
            Źródło gabarytów: <strong>{$allegro_data.shipment_size_options.source|default:'fallback'|escape:'htmlall':'UTF-8'}</strong>
            | Profil: <strong>{$allegro_data.shipment_size_options.profile|default:'-'|escape:'htmlall':'UTF-8'}</strong>
            | method_id: <code>{$allegro_data.shipment_size_options.method_id|default:$allegro_data.shipping.method_id|escape:'htmlall':'UTF-8'}</code>
        </small>

        <div class="form-check mt-1">
            <input type="checkbox" id="is_smart_shipment" class="form-check-input" {if $allegro_data.smart_left <= 0}disabled{else}checked{/if}>
            <label for="is_smart_shipment" class="form-check-label">Użyj Allegro Smart! (jeśli dostępny)</label>
        </div>
    </div>
</div>
