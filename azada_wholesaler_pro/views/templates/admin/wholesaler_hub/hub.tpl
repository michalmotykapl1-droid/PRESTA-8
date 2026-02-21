<div class="panel azada-hub-panel">
    <h3><i class="icon-th-large"></i> Nowoczesny Panel Hurtowni (v1)</h3>
    <p class="text-muted azada-hub-lead">
        Docelowy widok kafli hurtowni. W tym etapie: tylko status połączenia, włącz/wyłącz hurtownię oraz przycisk „Ustawienia”.
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
                        >
                            <i class="icon-cog"></i> Ustawienia
                        </button>
                    </div>
                </div>
            {/foreach}
        </div>

        <div class="azada-hub-actions">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="icon-save"></i> Zapisz ustawienia hurtowni
            </button>
        </div>
    </form>
</div>

<div class="azada-modal-overlay" id="azadaHubSettingsModal" style="display:none;">
    <div class="azada-modal-card">
        <div class="azada-modal-head">
            <h4>Ustawienia hurtowni: <span id="azadaHubModalName">-</span></h4>
            <button type="button" class="btn btn-default btn-sm" id="azadaHubModalClose">✕</button>
        </div>

        <div class="azada-modal-tabs" id="azadaHubTabs">
            <button type="button" class="azada-tab-btn is-active" data-tab="tab-basic">Podstawowe</button>
            <button type="button" class="azada-tab-btn" data-tab="tab-products">Produkty</button>
            <button type="button" class="azada-tab-btn" data-tab="tab-stock">Stany</button>
            <button type="button" class="azada-tab-btn" data-tab="tab-dimensions">Wymiary</button>
            <button type="button" class="azada-tab-btn" data-tab="tab-b2b">Dostęp B2B</button>
        </div>

        <div class="azada-modal-info" id="azadaHubModalOnlyBio" style="display:none;">
            Ten etap obsługuje ustawienia szczegółowe tylko dla BioPlanet.
        </div>

        <form method="post" action="{$azada_hub_post_url|escape:'html':'UTF-8'}" id="azadaHubSettingsForm">
            <input type="hidden" name="submitAzadaHubSettings" value="1" />
            <input type="hidden" name="hub_settings_id_wholesaler" id="azada_hub_settings_id_wholesaler" value="0" />

            <div class="azada-modal-body">
                <div class="azada-tab-pane is-active" data-tab-pane="tab-basic">
                    <div class="form-group">
                        <label for="azada_hub_settings_sync_mode">Tryb synchronizacji</label>
                        <select name="hub_settings_sync_mode" id="azada_hub_settings_sync_mode" class="form-control">
                            <option value="api">API</option>
                            <option value="file">Plik</option>
                            <option value="hybrid">Hybrid</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="azada_hub_settings_price_field">Pole cenowe</label>
                        <input type="text" class="form-control" name="hub_settings_price_field" id="azada_hub_settings_price_field" placeholder="np. CenaPoRabacieNetto" />
                    </div>

                    <div class="form-group">
                        <label for="azada_hub_settings_notes">Notatki techniczne</label>
                        <textarea class="form-control" rows="4" name="hub_settings_notes" id="azada_hub_settings_notes" placeholder="Uwagi do integracji BioPlanet..."></textarea>
                    </div>
                </div>

                <div class="azada-tab-pane" data-tab-pane="tab-products">
                    <p class="azada-placeholder-row">Sekcja „Produkty” zostanie podłączona w kolejnym kroku (mapowanie pól i reguły importu).</p>
                </div>

                <div class="azada-tab-pane" data-tab-pane="tab-stock">
                    <p class="azada-placeholder-row">Sekcja „Stany” zostanie podłączona w kolejnym kroku (strategia aktualizacji stanów i częstotliwość).</p>
                </div>

                <div class="azada-tab-pane" data-tab-pane="tab-dimensions">
                    <p class="azada-placeholder-row">Sekcja „Wymiary” zostanie podłączona w kolejnym kroku (mapowanie szerokość/wysokość/głębokość).</p>
                </div>

                <div class="azada-tab-pane" data-tab-pane="tab-b2b">
                    <p class="azada-placeholder-row">Sekcja „Dostęp B2B” zostanie podłączona w kolejnym kroku (integracja loginu i autoryzacji B2B).</p>
                </div>
            </div>

            <div class="azada-modal-actions">
                <button type="button" class="btn btn-default" id="azadaHubModalCancel">Anuluj</button>
                <button type="submit" class="btn btn-primary" id="azadaHubModalSave">Zapisz ustawienia BioPlanet</button>
            </div>
        </form>
    </div>
</div>
