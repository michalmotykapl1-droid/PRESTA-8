<div class="panel">
  <div class="panel-heading">
    <i class="icon-list-alt"></i>
    Szczegóły logu #{if isset($bbom_log.id_log)}{$bbom_log.id_log|intval}{/if}
  </div>

  <div class="panel-body">
    <div class="row">
      <div class="col-lg-6">
        <p>
          <strong>Data:</strong>
          {if isset($bbom_log.date_add)}{$bbom_log.date_add|escape:'htmlall':'UTF-8'}{/if}
        </p>
        <p>
          <strong>Pracownik:</strong>
          {if isset($bbom_employee_display) && $bbom_employee_display != ''}
            {$bbom_employee_display|escape:'htmlall':'UTF-8'}
          {else}
            System/Automat
          {/if}
          {if isset($bbom_log.id_employee) && (int)$bbom_log.id_employee > 0}
            <span class="text-muted">(ID: {$bbom_log.id_employee|intval})</span>
          {elseif isset($bbom_is_auto) && $bbom_is_auto}
            <span class="label label-default">AUTO</span>
          {/if}
        </p>
        <p>
          <strong>Akcja:</strong>
          {if isset($bbom_action_label) && $bbom_action_label != ''}
            <span class="label label-info">{$bbom_action_label|escape:'htmlall':'UTF-8'}</span>
            {if isset($bbom_action_code) && $bbom_action_code != ''}
              <span class="text-muted">({$bbom_action_code|escape:'htmlall':'UTF-8'})</span>
            {/if}
          {else}
            <span class="text-muted">-</span>
          {/if}
        </p>
      </div>

      <div class="col-lg-6">
        <p>
          <strong>Zamówienie:</strong>
          {if isset($bbom_order_link) && $bbom_order_link != ''}
            {$bbom_order_link nofilter}
          {elseif isset($bbom_log.id_order)}
            <span class="text-muted">#{$bbom_log.id_order|intval}</span>
          {else}
            <span class="text-muted">-</span>
          {/if}
        </p>
      </div>
    </div>

    <hr>

    <h4>Opis</h4>
    <div class="well" style="white-space: pre-wrap;">
      {if isset($bbom_message_display) && $bbom_message_display != ''}
        {$bbom_message_display|escape:'htmlall':'UTF-8'}
      {elseif isset($bbom_log.message)}
        {$bbom_log.message|escape:'htmlall':'UTF-8'}
      {/if}
    </div>

    {if isset($bbom_details_pretty) && $bbom_details_pretty != ''}
      <h4>Szczegóły (JSON)</h4>
      <pre style="max-height: 520px; overflow: auto;">{$bbom_details_pretty|escape:'htmlall':'UTF-8'}</pre>
    {/if}

    <div class="clearfix">
      <a href="{if isset($bbom_back_url)}{$bbom_back_url|escape:'htmlall':'UTF-8'}{/if}" class="btn btn-default">
        <i class="icon-arrow-left"></i> Powrót do listy
      </a>
    </div>
  </div>
</div>
