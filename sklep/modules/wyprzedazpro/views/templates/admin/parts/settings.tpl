<div class="panel">
    <div class="panel-heading">
        <i class="icon-cogs"></i> {l s='Panel zarządzania Wyprzedaż PRO (WMS)' mod='wyprzedazpro'}
    </div>

    <form action="{$current|escape:'html':'UTF-8'}&token={$token|escape:'html':'UTF-8'}" method="post" class="form-horizontal">
        <fieldset>
            <legend>{l s='Ustawienia rabatów i dat' mod='wyprzedazpro'}</legend>

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Liczba dni dla "Krótkiej daty"' mod='wyprzedazpro'}</label>
                <div class="col-lg-9">
                    <input type="text" name="WYPRZEDAZPRO_SHORT_DATE_DAYS" value="{$WYPRZEDAZPRO_SHORT_DATE_DAYS|default:14}" class="form-control" style="width: 80px; display: inline-block;" />
                    <p class="help-block">{l s='Poniżej tej liczby dni produkt trafi do kategorii "Krótka data".' mod='wyprzedazpro'}</p>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Rabat dla grupy "Krótka data" (%)' mod='wyprzedazpro'}</label>
                <div class="col-lg-9">
                    <input type="text" name="WYPRZEDAZPRO_DISCOUNT_SHORT" value="{$WYPRZEDAZPRO_DISCOUNT_SHORT|default:0}" class="form-control" style="width: 80px; display: inline-block;" /> %
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Rabat dla daty < 7 dni (%)' mod='wyprzedazpro'}</label>
                <div class="col-lg-9">
                    <input type="text" name="WYPRZEDAZPRO_DISCOUNT_VERY_SHORT" value="{$WYPRZEDAZPRO_DISCOUNT_VERY_SHORT|default:50}" class="form-control" style="width: 80px; display: inline-block;" /> %
                </div>
            </div>
            
            <div class="form-group" id="group_discount_bin">
                <label class="control-label col-lg-3">{l s='Rabat dla regału "KOSZ" (%)' mod='wyprzedazpro'}</label>
                <div class="col-lg-9">
                    <input type="text" name="WYPRZEDAZPRO_DISCOUNT_BIN" value="{$WYPRZEDAZPRO_DISCOUNT_BIN|default:0}" class="form-control" style="width: 80px; display: inline-block;" /> %
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Ignoruj datę ważności dla regału "KOSZ"' mod='wyprzedazpro'}</label>
                <div class="col-lg-9">
                    <span class="switch prestashop-switch fixed-width-lg">
                        <input type="radio" name="WYPRZEDAZPRO_IGNORE_BIN_EXPIRY" id="WYPRZEDAZPRO_IGNORE_BIN_EXPIRY_on" value="1" {if $WYPRZEDAZPRO_IGNORE_BIN_EXPIRY}checked="checked"{/if}>
                        <label for="WYPRZEDAZPRO_IGNORE_BIN_EXPIRY_on" class="radio-label">{l s='Tak' mod='wyprzedazpro'}</label>
                        <input type="radio" name="WYPRZEDAZPRO_IGNORE_BIN_EXPIRY" id="WYPRZEDAZPRO_IGNORE_BIN_EXPIRY_off" value="0" {if !$WYPRZEDAZPRO_IGNORE_BIN_EXPIRY}checked="checked"{/if}>
                        <label for="WYPRZEDAZPRO_IGNORE_BIN_EXPIRY_off" class="radio-label">{l s='Nie' mod='wyprzedazpro'}</label>
                        <a class="slide-button btn"></a>
                    </span>
                    <p class="help-block">{l s='Jeśli włączone, produkty z regału "KOSZ" nie będą oznaczane jako "Po terminie".' mod='wyprzedazpro'}</p>
                </div>
            </div>
            
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Rabat WYPRZEDAŻ – do 30 dni od przyjęcia (%)' mod='wyprzedazpro'}</label>
                <div class="col-lg-9">
                    <input type="text" name="WYPRZEDAZPRO_DISCOUNT_30" value="{$WYPRZEDAZPRO_DISCOUNT_30|default:0}" class="form-control" style="width: 80px; display: inline-block;" /> %
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Rabat WYPRZEDAŻ – od 31 do 90 dni (%)' mod='wyprzedazpro'}</label>
                <div class="col-lg-9">
                    <input type="text" name="WYPRZEDAZPRO_DISCOUNT_90" value="{$WYPRZEDAZPRO_DISCOUNT_90|default:0}" class="form-control" style="width: 80px; display: inline-block;" /> %
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Rabat WYPRZEDAŻ – powyżej 90 dni (%)' mod='wyprzedazpro'}</label>
                <div class="col-lg-9">
                    <input type="text" name="WYPRZEDAZPRO_DISCOUNT_OVER" value="{$WYPRZEDAZPRO_DISCOUNT_OVER|default:0}" class="form-control" style="width: 80px; display: inline-block;" /> %
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Włącz regułę: >90 dni i ważność ≥ 6 m-cy' mod='wyprzedazpro'}</label>
                <div class="col-lg-9">
                    <span class="switch prestashop-switch fixed-width-lg">
                        <input type="radio" name="WYPRZEDAZPRO_ENABLE_OVER90_LONGEXP" id="enable_over90_1" value="1" {if $WYPRZEDAZPRO_ENABLE_OVER90_LONGEXP}checked="checked"{/if} />
                        <label for="enable_over90_1">{l s='Tak' mod='wyprzedazpro'}</label>
                        <input type="radio" name="WYPRZEDAZPRO_ENABLE_OVER90_LONGEXP" id="enable_over90_0" value="0" {if !$WYPRZEDAZPRO_ENABLE_OVER90_LONGEXP}checked="checked"{/if} />
                        <label for="enable_over90_0">{l s='Nie' mod='wyprzedazpro'}</label>
                        <a class="slide-button btn"></a>
                    </span>
                </div>
            </div>

            <div class="form-group" id="group_over90_longexp" {if !$WYPRZEDAZPRO_ENABLE_OVER90_LONGEXP}style="display:none"{/if}>
                <label class="control-label col-lg-3">{l s='Rabat dla >90 dni i ważność ≥ 6 m-cy (%)' mod='wyprzedazpro'}</label>
                <div class="col-lg-9">
                    <input type="text" name="WYPRZEDAZPRO_DISCOUNT_OVER90_LONGEXP" value="{$WYPRZEDAZPRO_DISCOUNT_OVER90_LONGEXP|escape:'html':'UTF-8'}" class="form-control" style="width: 80px; display: inline-block;" /> %
                </div>
            </div>

            <div class="panel-footer">
                <button type="submit" name="submitWyprzedazSettings" class="btn btn-default pull-right">
                    <i class="process-icon-save"></i> {l s='Zapisz ustawienia' mod='wyprzedazpro'}
                </button>
            </div>
        </fieldset>
    </form>
</div>