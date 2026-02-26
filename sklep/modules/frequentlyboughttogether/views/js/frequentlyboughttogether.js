document.addEventListener('DOMContentLoaded', function () {
  
  // --- 1. FUNKCJE POMOCNICZE ---

  function calculateTotal() {
      // 1. Karta produktu
      updatePriceContainer('.fbt-box-container:not(.fbt-modal-wrapper)', '#fbt-total-price');
      
      // 2. Modal potwierdzenia
      updatePriceContainer('.fbt-modal-wrapper', '#fbt-total-price-modal');
  }

  function updatePriceContainer(wrapperSelector, totalId) {
      const wrapper = document.querySelector(wrapperSelector);
      if (!wrapper) return;

      let total = 0;
      const priceElements = wrapper.querySelectorAll('.fbt-product-cell[data-price]');
      const count = priceElements.length;

      priceElements.forEach(el => {
          let rawPrice = el.getAttribute('data-price');
          if (rawPrice) {
              rawPrice = rawPrice.replace(',', '.');
              let price = parseFloat(rawPrice);
              if (!isNaN(price)) total += price;
          }
      });

      let formattedTotal = total.toFixed(2).replace('.', ',') + ' zł';
      const totalContainer = document.querySelector(totalId);
      if(totalContainer) totalContainer.innerText = formattedTotal;

      // --- ZMIANA MARKETINGOWA: Tekst w modalu ---
      const label = wrapper.querySelector('.fbt-total-label');
      if (label && count > 0) {
          // Tutaj zmieniamy tekst na "RAZEM TYLKO"
          label.innerText = `RAZEM TYLKO (${count} SZT.):`;
      }
  }

  calculateTotal();
  setTimeout(calculateTotal, 1000);

  // --- OBSŁUGA OTWARCIA MODALA ---
  if (typeof $ !== 'undefined') {
      $(document).on('shown.bs.modal', '#blockcart-modal', function () {
          calculateTotal();
      });
  }


  // --- 2. OBSŁUGA KLIKNIĘCIA ---
  document.body.addEventListener('click', function(e) {
      
      const btn = e.target.closest('.fbt-btn-action');
      
      if (!btn || !btn.id.startsWith('fbt-add-all-btn')) return;
      if (btn.style.opacity === '0.7') return; 

      e.preventDefault();
      
      const container = btn.closest('.fbt-box-container') || btn.closest('.fbt-modal-wrapper');
      if (!container) return;

      const originalText = btn.innerText;
      btn.innerText = 'DODAWANIE...';
      btn.style.opacity = '0.7';
      btn.style.pointerEvents = 'none';
      
      const forms = container.querySelectorAll('.fbt-hidden-form');
      
      let isProductPage = (btn.id === 'fbt-add-all-btn');
      
      if (isProductPage) {
          prepareModalContent(container);
      }

      let lastJsonResponse = null;
      let chain = Promise.resolve();
      
      forms.forEach(form => {
         chain = chain.then(() => {
             return new Promise(resolve => {
                 let formData = new FormData(form);
                 formData.append('action', 'update');
                 formData.append('add', '1');
                 formData.append('ajax', true); 
                 
                 fetch(form.getAttribute('action'), {
                     method: 'POST',
                     body: formData,
                     headers: { 'Accept': 'application/json, text/javascript, */*; q=0.01' }
                 })
                 .then(response => response.json())
                 .then(jsonData => {
                     if (jsonData) lastJsonResponse = jsonData;
                     resolve();
                 })
                 .catch((err) => { console.error('FBT Error:', err); resolve(); });
             });
         });
      });

      // PO ZAKOŃCZENIU
      chain.then(() => {
         if (typeof prestashop !== 'undefined' && lastJsonResponse) {
             prestashop.emit('updateCart', {
                 reason: { 
                     linkAction: 'refresh', 
                     cart: lastJsonResponse.cart 
                 },
                 resp: lastJsonResponse 
             });
         }

         if (isProductPage) {
             // 1. KARTA PRODUKTU
             btn.innerText = originalText;
             btn.style.opacity = '1';
             btn.style.pointerEvents = 'auto';
             
             if (typeof $ !== 'undefined') {
                 $('#fbt-modal').modal('show');
             }
         } else {
             // 2. MODAL KOSZYKA (Zielony sukces)
             btn.style.backgroundColor = '#27ae60'; 
             btn.style.borderColor = '#27ae60';
             // Upewniamy się, że ikona sukcesu też jest wyśrodkowana
             btn.style.justifyContent = 'center'; 
             btn.innerHTML = '<i class="material-icons">check</i> DODANO!';
             
             setTimeout(function() {
                 location.reload();
             }, 1000);
         }
      });
  });

  // Funkcja budująca HTML do customowego modala (Karta Produktu)
  function prepareModalContent(container) {
      let modalContentHTML = '';
      const productCells = container.querySelectorAll('.fbt-product-cell');
      
      let count = productCells.length;

      productCells.forEach(cell => {
          let imgObj = cell.querySelector('img');
          let img = imgObj ? imgObj.src : '';
          
          let nameObj = cell.querySelector('.fbt-name a');
          let name = nameObj ? nameObj.innerText : 'Produkt';
          
          let priceObj = cell.querySelector('.fbt-price');
          let price = priceObj ? priceObj.innerText : '';
          
          modalContentHTML += `
            <div class="fbt-modal-item">
                <div class="fbt-modal-item-left">
                    <img src="${img}" class="fbt-modal-img">
                    <span class="fbt-modal-name">${name}</span>
                </div>
                <div class="fbt-modal-price">${price}</div>
            </div>
          `;
      });
      
      const modalBodyList = document.querySelector('.fbt-modal-products');
      if(modalBodyList) {
          modalBodyList.innerHTML = `<div class="fbt-modal-products-list">${modalContentHTML}</div>`;
      }
      
      const currentTotal = document.getElementById('fbt-total-price').innerText;
      const modalTotalInfo = document.querySelector('.fbt-modal-total-info');
      
      if(modalTotalInfo) {
          // Tutaj też spójny tekst
          modalTotalInfo.innerHTML = `
             <div class="fbt-total-row total-price" style="margin:0; padding: 20px; display: flex; justify-content: space-between; align-items: center; width: 100%;">
                 <span class="fbt-modal-total-label">RAZEM TYLKO (${count} SZT.):</span>
                 <span class="fbt-modal-total-value">${currentTotal}</span>
             </div>
          `;
      }
  }

});