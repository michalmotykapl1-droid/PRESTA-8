{**
 * 2007-2025 PrestaShop
 * ... (licencja) ...
 *}
{strip}
{extends file='page.tpl'}

{block name='page_content_container' prepend}
    <div id="content-hook_order_confirmation" class="card">
      <div class="card-block">
        <div class="row">
          <div class="col-md-12">

            {block name='order_confirmation_header'}
              <h3 class="h1 card-title">
                <i class="material-icons rtl-no-flip done">&#xE876;</i>{l s='Your order is confirmed' d='Shop.Theme.Checkout'}
              </h3>
            {/block}

            <p>
              {l s='An email has been sent to your mail address %email%.' d='Shop.Theme.Checkout' sprintf=['%email%' => $customer.email]}
              {if $order.details.invoice_url}
                {* [1][/1] is for a HTML tag. *}
                {l
                  s='You can also [1]download your invoice[/1]'
                  d='Shop.Theme.Checkout'
                  sprintf=[
                    '[1]' => "<a href='{$order.details.invoice_url}'>",
                    '[/1]' => "</a>"
                  ]
                }
              {/if}
            </p>

            {block name='hook_order_confirmation'}
               {$HOOK_ORDER_CONFIRMATION nofilter}
            {/block}

          </div>
        </div>
      </div>
    </div>
{/block}

{block name='page_content_container'}
  <div class="tvorder-conformation-wrapper">
    <div id="content" class="page-content page-order-confirmation card">
      <div class="card-block">
        <div class="row">

          {block name='order_confirmation_table'}
            {include
               file='checkout/_partials/order-confirmation-table.tpl'
              products=$order.products
              subtotals=$order.subtotals
              totals=$order.totals
              labels=$order.labels
              add_product_link=false
            }
           {/block}

          {block name='order_details'}
            <div id="order-details" class="col-md-4">
              <h3 class="h3 card-title">{l s='Order details' d='Shop.Theme.Checkout'}:</h3>
              <ul>
                <li>{l s='Order reference: %reference%' d='Shop.Theme.Checkout' sprintf=['%reference%' => $order.details.reference]}</li>
                 <li>{l s='Payment method: %method%' d='Shop.Theme.Checkout' sprintf=['%method%' => $order.details.payment]}</li>
                {if !$order.details.is_virtual}
                  <li>
                    {l s='Shipping method: %method%' d='Shop.Theme.Checkout' sprintf=['%method%' => $order.carrier.name]}<br>
                    <em class="carrier-delay-confirmation">{$order.carrier.delay}</em>
                   </li>
                {/if}
              </ul>
            </div>
          {/block}

        </div>
      </div>
    </div>

    {block name='hook_payment_return'}
      {if !empty($HOOK_PAYMENT_RETURN)}
      <div id="content-hook_payment_return" class="card definition-list">
        <div class="card-block">
          <div class="row">
            <div class="col-md-12">
              {$HOOK_PAYMENT_RETURN nofilter}
            </div>
          </div>
        </div>
      </div>
      {/if}
     {/block}
  </div>

  {block name='customer_registration_form'}
    {* --- POCZĄTEK OSTATECZNEJ POPRAWKI BŁĘDÓW --- *}
    {if $customer.is_guest}
      <div id="registration-form" class="card">
        <div class="card-block">
          <h4 class="h4">{l s='Save time on your next order, sign up now' d='Shop.Theme.Checkout'}</h4>
          
          {* Sprawdzamy, czy formularz istnieje ZANIM go wyrenderujemy *}
          {if !empty($register_form)}
            {render file='customer/_partials/customer-form.tpl' ui=$register_form}
          {else}
            {* Jeśli formularz jest null (co powoduje błąd), wyświetlamy tekst zastępczy *}
            <p>{l s='You can create an account at any time from the "My Account" page.' d='Shop.Theme.Checkout'}</p>
          {/if}

        </div>
      </div>
    {/if}
    {* --- KONIEC OSTATECZNEJ POPRAWKI BŁĘDÓW --- *}
  {/block}

  {block name='hook_order_confirmation_1'}
    {hook h='displayOrderConfirmation1'}
  {/block}

  {block name='hook_order_confirmation_2'}
    
 <div id="content-hook-order-confirmation-footer">
      {hook h='displayOrderConfirmation2'}
    </div>
  {/block}
{/block}

{* --- Skrypt do konwersji daty dostawy --- *}
{block name='javascript_inline' append}
  {literal}
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        
        function addBusinessDays(startDate, daysToAdd) {
          if (daysToAdd === 0) {
              let checkDate = new Date(startDate.getTime());
              let dayOfWeek = checkDate.getDay();
              if (dayOfWeek === 0) { checkDate.setDate(checkDate.getDate() + 1); }
              else if (dayOfWeek === 6) { checkDate.setDate(checkDate.getDate() + 2); }
              return checkDate;
          }
          let currentDate = new Date(startDate.getTime());
          let daysAdded = 0;
          while (daysAdded < daysToAdd) {
              currentDate.setDate(currentDate.getDate() + 1);
              let dayOfWeek = currentDate.getDay();
              if (dayOfWeek !== 0 && dayOfWeek !== 6) {
                  daysAdded++;
              }
          }
          return currentDate;
        }

        function formatDeliveryDate(date, options) {
          return new Intl.DateTimeFormat('pl-PL', options).format(date);
        }

        function getFormattedDateText(delayString) {
          if (!delayString || !delayString.match(/^\d+:\d+$/)) { return null; }
          const [minDays, maxDays] = delayString.split(':').map(Number);
          const today = new Date();
          const minDate = addBusinessDays(today, minDays);
          const maxDate = addBusinessDays(today, maxDays);
          let newDelayText;
          if (minDays === maxDays) {
              newDelayText = formatDeliveryDate(minDate, { day: 'numeric', month: 'long' });
          } else if (minDate.getMonth() === maxDate.getMonth()) {
              const minDay = minDate.getDate();
              const maxDay = maxDate.getDate();
              const monthName = formatDeliveryDate(minDate, { month: 'long' });
              newDelayText = `${minDay} – ${maxDay} ${monthName}`;
          } else {
              const minStr = formatDeliveryDate(minDate, { day: 'numeric', month: 'short' });
              const maxStr = formatDeliveryDate(maxDate, { day: 'numeric', month: 'short' });
              newDelayText = `${minStr} – ${maxStr}`;
          }
          return newDelayText;
        }
        
        try {
          const delayElement = document.querySelector('.carrier-delay-confirmation');
          if (delayElement) {
            const delayString = delayElement.innerText.trim();
            const newDelayText = getFormattedDateText(delayString);
            if (newDelayText) {
              delayElement.innerText = newDelayText;
            }
          }
        } catch (e) {
          console.error('Błąd formatowania daty na stronie potwierdzenia.', e);
        }
      });
    </script>
  {/literal}
{/block}
{/strip}