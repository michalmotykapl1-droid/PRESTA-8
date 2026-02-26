/**
 * Copyright since 2021 InPost S.A.
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the EUPL-1.2 or later.
 * You may not use this work except in compliance with the Licence.
 *
 * You may obtain a copy of the Licence at:
 * https://joinup.ec.europa.eu/software/page/eupl
 * It is also bundled with this package in the file LICENSE.txt
 *
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the Licence is distributed on an AS IS basis,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the Licence for the specific language governing permissions
 * and limitations under the Licence.
 *
 * @author    InPost S.A.
 * @copyright Since 2021 InPost S.A.
 * @license   https://joinup.ec.europa.eu/software/page/eupl
 */

/** @var {boolean} inPostSuperCheckoutGuest */
$(() => {
  if ('function' !== typeof addSupercheckoutOrderValidator) {
    return;
  }

  getClosestPointForAddress();

  function getClosestPointForAddress() {

    const fieldsSelectors = [
        'input[name="shipping_address[address1]"]',
        'input[name="shipping_address[address2]"]',
        //'input[name="shipping_address[postcode]"]',
        'input[name="shipping_address[city]"]',
        //'select[name="shipping_address[id_country]"]'
    ];

    $(fieldsSelectors.join(',')).on('blur', function () {

      const $wrapper = $('.carrier-extra-content');
      const $closestMachineContainer = $wrapper.find('.js-inpost-closest-machine');
      const $inpostSelectClosestMachine = $wrapper.find('.js-select-closest-machine');
      const $machineName = $wrapper.find('.js-inpost-shipping-machine-name');
      const $machineAddress = $wrapper.find('.js-inpost-shipping-machine-address');

      $closestMachineContainer.slideUp();

      fieldsSelectors.push('input[name="shipping_address[postcode]"]');
      fieldsSelectors.push('select[name="shipping_address[id_country]"]');

      const formData = new FormData();
      formData.append('action', 'getClosestPoint');
      let filled = true;

      $.each(fieldsSelectors, function(index, input){
        const $field = $(input);
        const fieldName = $field.attr('name').substr(16).replace(/[\[\]]/g, '');
        if(fieldName != 'address2' && $field.val().length < 2){
          filled = false;
          return;
        }
        formData.append(fieldName, $field.val());
      });

      if(!filled){
        return;
      }

      $.ajax({
        method: 'post',
        url: inPostAjaxController,
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function (response) {
          if (response.success) {

            $inpostSelectClosestMachine.data('address', response.address);
            $inpostSelectClosestMachine.data('machine', response.machine);

            $closestMachineContainer.slideDown();
          } else {
            $closestMachineContainer.slideUp();
          }
        },
      });
    });
  };

  addSupercheckoutOrderValidator(() => {
    const $contentWrapper = $('.js-inpost-shipping-container:visible');

    if ($contentWrapper.length > 0) {
      setHiddenPhoneInputValue($contentWrapper);

      let errors = null;
      const formData = new FormData();

      formData.append('action', 'updateChoice');
      $contentWrapper.find(':input').each((index, element) => {
        const $input = $(element);
        formData.append($input.attr('name'), $input.val());
      });

      $.ajax({
        async: false,
        method: 'post',
        url: inPostAjaxController,
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: (response) => {
          if (false === response.success) {
            errors = $.map(response.errors, (error) => `<li>${error}</li>`).join('');
          }
        }
      });

      if (null !== errors) {
        throw { message: errors };
      }
    }
  });

  const setHiddenPhoneInputValue = ($contentWrapper) => {
    const $input = $contentWrapper.find('.js-inpost-shipping-phone-hidden');
    if (0 === $input.length) {
      return;
    }

    if (!inPostSuperCheckoutGuest && !$('#shipping-new').is(':visible')) {
      $input.val('');

      return;
    }

    const $phoneInputs = $('[name="shipping_address[phone_mobile]"], [name="shipping_address[phone]"]')
      .sort((a, b) => {
        // prioritize "phone_mobile" over "phone"
        return $(a).attr('name') === 'shipping_address[phone_mobile]' ? -1 : 1;
      });

    $phoneInputs.each((index, shippingInput) => {
      const phone = $(shippingInput).val();

      if ('' !== phone.trim()) {
        $input.val(phone);

        // RETURN FALSE TO BREAK THE LOOP IF WE FOUND A VALUE
        return false;
      }
    });
  }
});
