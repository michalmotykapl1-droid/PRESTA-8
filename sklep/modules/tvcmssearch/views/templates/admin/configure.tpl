{* Ten szablon jest teraz używany tylko jako kontener dla formularza generowanego przez HelperForm. *}
{* Nie zawiera już bezpośrednio pól formularza HTML. *}

<div class="panel">
    <h3>{if isset($module_display_name)}{$module_display_name} - {/if}{l s='Ustawienia wyszukiwarki' mod='tvcmssearch'}</h3>

    {* Komunikaty są wyświetlane przez AdminController, więc Smarty może je odebrać *}
    {if isset($confirmations) && $confirmations|@count > 0}
        <div class="alert alert-success">
            {foreach from=$confirmations item='conf'}
                {$conf}<br>
            {/foreach}
        </div>
    {/if}
    {if isset($errors) && $errors|@count > 0}
        <div class="alert alert-danger">
            {foreach from=$errors item='error'}
                {$error}<br>
            {/foreach}
        </div>
    {/if}

    {* Tutaj zostanie wstrzyknięty formularz wygenerowany przez HelperForm *}
    {$content}
</div>