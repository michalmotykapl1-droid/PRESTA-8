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
                        <button type="button" class="btn btn-default btn-block azada-hub-settings-btn" data-wholesaler="{$card.id_wholesaler|intval}">
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
