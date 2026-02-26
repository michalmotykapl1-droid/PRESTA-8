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
$(() => {
  if (
    $('#onepagecheckoutps').length === 0
    || typeof Fronted === 'undefined'
    || typeof AppOPC === 'undefined'
    || typeof OPC_External_Validation === 'undefined'
  ) {
    return;
  }

  const riseCheckoutModal = (message, name = 'shipping_modal', type = 'warning') => {
    Fronted.showModal({
      name,
      type,
      message
    });
  }

  const checkoutBlockAlertState = (blockId) => {
    const getBlock = () => $('#onepagecheckoutps').find(blockId);

    const activeState = (state = 'warning') => {
      getBlock().addClass(`alert alert-${state}`);
    }

    const inactiveState = (state = 'warning') => {
      getBlock().removeClass(`alert alert-${state}`);
    }

    return {
      activeState,
      inactiveState,
    }
  }

  const {
    activeState: showShippingWarning,
    inactiveState: hideShippingWarning,
  } = checkoutBlockAlertState('#shipping_container');

  const {
    activeState: showAddressWarning,
    inactiveState: hideAddressWarning,
  } = checkoutBlockAlertState('#panel_address_delivery');

  const setHiddenPhoneInputValue = ($contentWrapper) => {
    const $input = $contentWrapper.find('.js-inpost-shipping-phone-hidden');
    if (0 === $input.length) {
      return;
    }

    const $addressForm = $('#panel_address_delivery');
    if (0 === $addressForm.length) {
      $input.val('');

      return;
    }

    const $phoneInputs = $addressForm
      .find('[name="delivery_phone_mobile"], [name="delivery_phone"]')
      .sort((a, b) => {
        // prioritize "phone_mobile" over "phone"
        return $(a).attr('name') === 'delivery_phone_mobile' ? -1 : 1;
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

  const canPlaceOrder = () => {
    let returnData = {
      canProceed: true,
      errors: []
    };

    const $contentWrapper = $('.js-inpost-shipping-container:visible');

    if ($contentWrapper.length > 0) {
      setHiddenPhoneInputValue($contentWrapper);
      const $addressDeliveryPhoneInput = $('#onepagecheckoutps input[name="delivery_phone_mobile"]');
      const $inpostPhoneInput = $('input[name="inpost_phone"]');

      const formData = new FormData();

      formData.append('action', 'updateChoice');
      $contentWrapper.find(':input').each((index, element) => {
        const $input = $(element);
        formData.append($input.attr('name'), $input.val());
      });

      if($addressDeliveryPhoneInput.length && !$inpostPhoneInput.val() && $addressDeliveryPhoneInput.val()){
        formData.set('inpost_phone', $addressDeliveryPhoneInput.val());
      }

      $.ajax({
        async: false,
        method: 'post',
        url: inPostAjaxController,
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: (response) => {
          if (false === response.success && response.errors) {
            returnData.canProceed = false;
            returnData.errors = response.errors;
          }
        }
      });
    }

    return returnData;
  }

  const validatePlaceOrder = () => {
    const { canProceed, errors } = canPlaceOrder();

    if (!canProceed) {
      const {
        locker = null,
        phone = null,
      } = errors;

      const messages = [];

      if (locker) {
        showShippingWarning();
        messages.push(locker);

        AppOPC.$opc_step_two.find('#shipping_container').one('click', () => {
          hideShippingWarning();
        })
      }

      if (phone) {
        showAddressWarning();
        messages.push(phone);

        AppOPC.$opc_step_one.find('#panel_address_delivery').one('click', () => {
          hideAddressWarning();
        })
      }

      if (messages.length > 0) {
        riseCheckoutModal(messages.join('<br>'));
      }
    }

    return canProceed;
  }

  const addValidator = () => {
    if (typeof OPC_External_Validation.validations['review:placeOrder'] !== 'undefined'
        && Array.isArray(OPC_External_Validation.validations['review:placeOrder'])
    ) {
      OPC_External_Validation.validations['review:placeOrder'].push(validatePlaceOrder);
    }
  };

  // MAKE SURE THAT VALIDATOR IS ALREADY INITIALIZED
  setTimeout(addValidator, 100);
});
