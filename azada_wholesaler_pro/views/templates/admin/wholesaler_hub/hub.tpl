<div class="panel azada-hub-panel">
    <h3><i class="icon-th-large"></i> Nowoczesny Panel Hurtowni</h3>
    <p class="text-muted azada-hub-lead">
        Etap 2: BioPlanet z pełnym układem zakładek: Start integracji, Import i źródło danych, Ceny i stany, Treści i SEO, Reguły jakości, Akcje i utrzymanie.
    </p>

    <form method="post" action="{$azada_hub_post_url|escape:'html':'UTF-8'}" class="azada-hub-cards-form">
        <input type="hidden" name="submitAzadaHubCards" value="1" />

        <div class="azada-hub-grid">
            {foreach from=$azada_hub_cards item=card}
                <div class="azada-hub-card" data-wholesaler="{$card.id_wholesaler|intval}">
                    <div class="azada-hub-card__head">
                        <div>
                            <h4>{$card.name|escape:'html':'UTF-8'}</h4>
                            <div class="azada-hub-card__meta">
                                <span class="label label-default">ID: {$card.id_wholesaler|intval}</span>
                                <span class="label {if $card.active}label-success{else}label-default{/if}">
                                    {if $card.active}Aktywna integracja{else}Nieaktywna integracja{/if}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="azada-hub-card__body">
                        <div class="azada-field-row">
                            <label>SQL tabela:</label>
                            <code>{$card.raw_table_name|escape:'html':'UTF-8'}</code>
                        </div>

                        <div class="azada-field-row">
                            <label>Ostatnie pobranie:</label>
                            <span>{if $card.last_import}{$card.last_import|escape:'html':'UTF-8'}{else}Brak danych{/if}</span>
                        </div>

                        <div class="azada-field-row">
                            <label>Status połączenia:</label>
                            <span class="azada-connection-status {if $card.connection_status == 1}is-connected{elseif $card.connection_status == 2}is-error{else}is-unknown{/if}">
                                {if $card.connection_status == 1}
                                    Połączona
                                {elseif $card.connection_status == 2}
                                    Błąd połączenia
                                {else}
                                    Nie zweryfikowano
                                {/if}
                            </span>
                        </div>

                        <div class="azada-field-row azada-field-row--toggle">
                            <label>Hurtownia w imporcie:</label>
                            <div class="azada-switch-wrap">
                                <input type="hidden" name="hub_enabled[{$card.id_wholesaler|intval}]" value="{if $card.hub_enabled}1{else}0{/if}" class="azada-hub-enabled-input" />
                                <button
                                    type="button"
                                    class="azada-switch {if $card.hub_enabled}is-on{else}is-off{/if}"
                                    aria-pressed="{if $card.hub_enabled}true{else}false{/if}"
                                >
                                    <span class="azada-switch__track">
                                        <span class="azada-switch__thumb"></span>
                                        <span class="azada-switch__label azada-switch__label--on">ON</span>
                                        <span class="azada-switch__label azada-switch__label--off">OFF</span>
                                    </span>
                                </button>
                                <span class="azada-switch-state-text">{if $card.hub_enabled}Włączona{else}Wyłączona{/if}</span>
                            </div>
                        </div>
                    </div>

                    <div class="azada-hub-card__foot">
                        <button
                            type="button"
                            class="btn btn-default btn-block azada-hub-settings-btn"
                            data-wholesaler="{$card.id_wholesaler|intval}"
                            data-name="{$card.name|escape:'html':'UTF-8'}"
                            data-table="{$card.raw_table_name|escape:'html':'UTF-8'}"
                            data-sync-mode="{$card.sync_mode|escape:'html':'UTF-8'}"
                            data-price-field="{$card.price_field|escape:'html':'UTF-8'}"
                            data-notes="{$card.notes|escape:'html':'UTF-8'}"
                            data-use-local-cache="{$card.use_local_cache|intval}"
                            data-cache-ttl-minutes="{$card.cache_ttl_minutes|intval}"
                            data-price-multiplier="{$card.price_multiplier|escape:'html':'UTF-8'}"
                            data-price-markup-percent="{$card.price_markup_percent|escape:'html':'UTF-8'}"
                            data-stock-buffer="{$card.stock_buffer|intval}"
                            data-stock-min-limit="{$card.stock_min_limit|intval}"
                            data-stock-max-limit="{$card.stock_max_limit|intval}"
                            data-price-min-limit="{$card.price_min_limit|escape:'html':'UTF-8'}"
                            data-price-max-limit="{$card.price_max_limit|escape:'html':'UTF-8'}"
                            data-zero-below-stock="{$card.zero_below_stock|intval}"
                            data-seo-strip-style="{$card.seo_strip_style|intval}"
                            data-seo-strip-iframe="{$card.seo_strip_iframe|intval}"
                            data-seo-strip-links="{$card.seo_strip_links|intval}"
                            data-seo-short-desc-fallback="{$card.seo_short_desc_fallback|intval}"
                            data-seo-meta-title-template="{$card.seo_meta_title_template|escape:'html':'UTF-8'}"
                            data-seo-meta-desc-template="{$card.seo_meta_desc_template|escape:'html':'UTF-8'}"
                            data-seo-description-prefix="{$card.seo_description_prefix|escape:'html':'UTF-8'}"
                            data-seo-description-suffix="{$card.seo_description_suffix|escape:'html':'UTF-8'}"
                            data-quality-require-ean="{$card.quality_require_ean|intval}"
                            data-quality-require-name="{$card.quality_require_name|intval}"
                            data-quality-require-price="{$card.quality_require_price|intval}"
                            data-quality-require-stock="{$card.quality_require_stock|intval}"
                            data-quality-reject-missing-data="{$card.quality_reject_missing_data|intval}"
                        >
                            <i class="icon-cog"></i> Ustawienia
                        </button>
                    </div>
                </div>
            {/foreach}
        </div>

        <div class="azada-hub-actions">
            <button type="submit" class="btn btn-primary">
                <i class="icon-save"></i> Zapisz ustawienia hurtowni (ON/OFF)
            </button>
        </div>
    </form>

    <div
        class="azada-modal-overlay"
        id="azadaHubSettingsModal"
        style="display:none;"
        data-clear-cache-url="{$azada_hub_clear_cache_url|escape:'html':'UTF-8'}"
        data-force-sync-url="{$azada_hub_force_sync_url|escape:'html':'UTF-8'}"
        data-disable-products-url="{$azada_hub_disable_products_url|escape:'html':'UTF-8'}"
        data-delete-products-url="{$azada_hub_delete_products_url|escape:'html':'UTF-8'}"
    >
        <form method="post" action="{$azada_hub_post_url|escape:'html':'UTF-8'}" class="azada-modal-card azada-modal-card--wide" id="azadaHubSettingsForm">
            <input type="hidden" name="submitAzadaHubSettings" value="1" />
            <input type="hidden" name="hub_settings_id_wholesaler" id="azada_hub_settings_id_wholesaler" value="0" />

            <div class="azada-modal-head">
                <h4>Ustawienia hurtowni: <span id="azadaHubModalName">-</span></h4>
                <button type="button" class="btn btn-default" id="azadaHubModalClose"><i class="icon-remove"></i></button>
            </div>

            <div class="azada-modal-info" id="azadaHubModalOnlyBio" style="display:none;">
                Na tym etapie szczegółowe zakładki dotyczą wyłącznie BioPlanet (sql: <code>azada_raw_bioplanet</code>).
            </div>

            <div class="azada-modal-tabs" role="tablist" aria-label="Zakładki ustawień hurtowni">
                <button type="button" class="azada-tab-btn is-active" data-tab="tab-start">Start integracji</button>
                <button type="button" class="azada-tab-btn" data-tab="tab-import">Import i źródło danych</button>
                <button type="button" class="azada-tab-btn" data-tab="tab-pricing">Ceny i stany</button>
                <button type="button" class="azada-tab-btn" data-tab="tab-content-seo">Treści i SEO</button>
                <button type="button" class="azada-tab-btn" data-tab="tab-quality">Reguły jakości</button>
                <button type="button" class="azada-tab-btn" data-tab="tab-maintenance">Akcje i utrzymanie</button>
            </div>

            <div class="azada-modal-body">
                <div class="azada-tab-pane is-active" data-tab-pane="tab-start">
                    <p class="azada-tab-description">Podstawy uruchomienia i diagnozy integracji.</p>
                    <div class="form-group">
                        <label for="azada_hub_settings_sync_mode">Tryb synchronizacji</label>
                        <select class="form-control" name="hub_settings_sync_mode" id="azada_hub_settings_sync_mode">
                            <option value="api">API</option>
                            <option value="file">Plik</option>
                            <option value="hybrid">Hybryda</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="azada_hub_settings_notes">Notatki techniczne</label>
                        <textarea class="form-control" rows="3" name="hub_settings_notes" id="azada_hub_settings_notes" placeholder="Np. uwagi integracyjne, niestandardowe mapowanie..." ></textarea>
                    </div>
                </div>

                <div class="azada-tab-pane" data-tab-pane="tab-import">
                    <p class="azada-tab-description">Kontrola źródła danych, cache i parametrów pobierania.</p>
                    <div class="form-group">
                        <label for="azada_hub_settings_price_field">Pole ceny źródłowej</label>
                        <input type="text" class="form-control" name="hub_settings_price_field" id="azada_hub_settings_price_field" value="CenaPoRabacieNetto" />
                    </div>

                    <div class="form-group">
                        <label>Cache lokalnych plików hurtowni</label>
                        <div class="azada-switch-group">
                            <input type="hidden" name="hub_settings_use_local_cache" id="azada_hub_settings_use_local_cache" value="1" />
                            <button type="button" class="azada-switch is-on" id="azadaHubUseLocalCacheSwitch" aria-pressed="true">
                                <span class="azada-switch__track">
                                    <span class="azada-switch__thumb"></span>
                                    <span class="azada-switch__label azada-switch__label--on">ON</span>
                                    <span class="azada-switch__label azada-switch__label--off">OFF</span>
                                </span>
                            </button>
                            <span id="azadaHubUseLocalCacheLabel" class="azada-inline-note">Włączone</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="azada_hub_settings_cache_ttl_minutes">Odświeżenie cache po (minuty)</label>
                        <input type="number" min="1" max="10080" class="form-control" name="hub_settings_cache_ttl_minutes" id="azada_hub_settings_cache_ttl_minutes" value="60" />
                    </div>
                </div>

                <div class="azada-tab-pane" data-tab-pane="tab-pricing">
                    <p class="azada-tab-description">Najważniejsze reguły handlowe: ceny, progi i stany.</p>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="azada_hub_settings_price_multiplier">Mnożnik ceny</label>
                                <input type="number" step="0.0001" min="0.0001" class="form-control" name="hub_settings_price_multiplier" id="azada_hub_settings_price_multiplier" value="1.0000" />
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="azada_hub_settings_price_markup_percent">Narzut (%)</label>
                                <input type="number" step="0.01" min="-99.99" class="form-control" name="hub_settings_price_markup_percent" id="azada_hub_settings_price_markup_percent" value="0.00" />
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="azada_hub_settings_price_min_limit">Minimalna cena importu</label>
                                <input type="number" step="0.01" min="0" class="form-control" name="hub_settings_price_min_limit" id="azada_hub_settings_price_min_limit" value="0.00" />
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="azada_hub_settings_price_max_limit">Maksymalna cena importu</label>
                                <input type="number" step="0.01" min="0" class="form-control" name="hub_settings_price_max_limit" id="azada_hub_settings_price_max_limit" value="0.00" />
                            </div>
                        </div>
                    </div>

                    <hr class="azada-form-separator" />

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="azada_hub_settings_stock_buffer">Bufor stanu (np. -1)</label>
                                <input type="number" step="1" class="form-control" name="hub_settings_stock_buffer" id="azada_hub_settings_stock_buffer" value="0" />
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="azada_hub_settings_zero_below_stock">Ustaw stan 0 poniżej progu</label>
                                <input type="number" step="1" min="0" class="form-control" name="hub_settings_zero_below_stock" id="azada_hub_settings_zero_below_stock" value="0" />
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="azada_hub_settings_stock_min_limit">Minimalny stan do importu</label>
                                <input type="number" step="1" min="0" class="form-control" name="hub_settings_stock_min_limit" id="azada_hub_settings_stock_min_limit" value="0" />
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="azada_hub_settings_stock_max_limit">Maksymalny stan do importu</label>
                                <input type="number" step="1" min="0" class="form-control" name="hub_settings_stock_max_limit" id="azada_hub_settings_stock_max_limit" value="0" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="azada-tab-pane" data-tab-pane="tab-content-seo">
                    <p class="azada-tab-description">Reguły czyszczenia treści oraz ustawienia meta dla importowanych produktów.</p>

                    <div class="row">
                        <div class="col-sm-6">
                            <label class="azada-checkline">
                                <input type="checkbox" id="azada_hub_settings_seo_strip_style" name="hub_settings_seo_strip_style" value="1" />
                                Usuwaj style inline z HTML
                            </label>
                        </div>
                        <div class="col-sm-6">
                            <label class="azada-checkline">
                                <input type="checkbox" id="azada_hub_settings_seo_strip_iframe" name="hub_settings_seo_strip_iframe" value="1" />
                                Usuwaj iframe / video embed
                            </label>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6">
                            <label class="azada-checkline">
                                <input type="checkbox" id="azada_hub_settings_seo_strip_links" name="hub_settings_seo_strip_links" value="1" />
                                Usuwaj linki zewnętrzne
                            </label>
                        </div>
                        <div class="col-sm-6">
                            <label class="azada-checkline">
                                <input type="checkbox" id="azada_hub_settings_seo_short_desc_fallback" name="hub_settings_seo_short_desc_fallback" value="1" />
                                Generuj fallback krótkiego opisu
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="azada_hub_settings_seo_meta_title_template">Szablon meta title</label>
                        <input type="text" maxlength="255" class="form-control" name="hub_settings_seo_meta_title_template" id="azada_hub_settings_seo_meta_title_template" placeholder="{ldelim}name{rdelim} | {ldelim}brand{rdelim} | BioPlanet" />
                    </div>

                    <div class="form-group">
                        <label for="azada_hub_settings_seo_meta_desc_template">Szablon meta description</label>
                        <input type="text" maxlength="255" class="form-control" name="hub_settings_seo_meta_desc_template" id="azada_hub_settings_seo_meta_desc_template" placeholder="Kup {ldelim}name{rdelim} w dobrej cenie. Hurtownia BioPlanet." />
                    </div>

                    <div class="form-group">
                        <label for="azada_hub_settings_seo_description_prefix">Prefix opisu</label>
                        <input type="text" maxlength="255" class="form-control" name="hub_settings_seo_description_prefix" id="azada_hub_settings_seo_description_prefix" placeholder="Produkt z oficjalnej oferty BioPlanet." />
                    </div>

                    <div class="form-group">
                        <label for="azada_hub_settings_seo_description_suffix">Suffix opisu</label>
                        <input type="text" maxlength="255" class="form-control" name="hub_settings_seo_description_suffix" id="azada_hub_settings_seo_description_suffix" placeholder="Sprawdź także inne produkty ekologiczne." />
                    </div>
                </div>

                <div class="azada-tab-pane" data-tab-pane="tab-quality">
                    <p class="azada-tab-description">Reguły jakości służą do odrzucania lub oznaczania niekompletnych danych przed zapisem.</p>

                    <div class="row">
                        <div class="col-sm-6">
                            <label class="azada-checkline">
                                <input type="checkbox" id="azada_hub_settings_quality_require_ean" name="hub_settings_quality_require_ean" value="1" />
                                Wymagaj EAN
                            </label>
                        </div>
                        <div class="col-sm-6">
                            <label class="azada-checkline">
                                <input type="checkbox" id="azada_hub_settings_quality_require_name" name="hub_settings_quality_require_name" value="1" />
                                Wymagaj nazwy produktu
                            </label>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6">
                            <label class="azada-checkline">
                                <input type="checkbox" id="azada_hub_settings_quality_require_price" name="hub_settings_quality_require_price" value="1" />
                                Wymagaj poprawnej ceny
                            </label>
                        </div>
                        <div class="col-sm-6">
                            <label class="azada-checkline">
                                <input type="checkbox" id="azada_hub_settings_quality_require_stock" name="hub_settings_quality_require_stock" value="1" />
                                Wymagaj poprawnego stanu
                            </label>
                        </div>
                    </div>

                    <label class="azada-checkline azada-checkline--single">
                        <input type="checkbox" id="azada_hub_settings_quality_reject_missing_data" name="hub_settings_quality_reject_missing_data" value="1" />
                        Odrzucaj rekordy z brakami (zamiast tylko ostrzegać)
                    </label>

                    <p class="azada-inline-help">
                        Raport pominiętych rekordów i pre-view odrzutów będzie rozwijany w kolejnym etapie silnika importu.
                    </p>
                </div>

                <div class="azada-tab-pane" data-tab-pane="tab-maintenance">
                    <p class="azada-tab-description">Operacje utrzymaniowe i porządkowe dla hurtowni.</p>
                    <div class="azada-maintenance-actions">
                        <button type="button" class="btn btn-default" id="azadaHubClearCacheBtn">
                            <i class="icon-eraser"></i> Wyczyść cache hurtowni
                        </button>
                        <button type="button" class="btn btn-warning" id="azadaHubForceSyncBtn">
                            <i class="icon-refresh"></i> Wymuś pełną synchronizację teraz
                        </button>
                        <button type="button" class="btn btn-default" id="azadaHubDisableProductsBtn">
                            <i class="icon-ban-circle"></i> Wyłącz produkty hurtowni
                        </button>
                        <button type="button" class="btn btn-danger" id="azadaHubDeleteProductsBtn">
                            <i class="icon-trash"></i> Usuń produkty hurtowni
                        </button>
                    </div>
                    <p class="azada-inline-help">
                        Akcje działają tylko dla BioPlanet na tym etapie. Po synchronizacji odśwież stronę, aby zobaczyć najnowsze statusy.
                    </p>
                </div>
            </div>

            <div class="azada-modal-actions">
                <button type="button" class="btn btn-default" id="azadaHubModalCancel">Anuluj</button>
                <button type="submit" class="btn btn-primary" id="azadaHubModalSave">Zapisz ustawienia BioPlanet</button>
            </div>
        </form>
    </div>
</div>
