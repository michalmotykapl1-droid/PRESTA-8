/*! tvcmssearch.mobile.js — Mobile Logic (Top Categories + Badges + Close) */
(function(w,d){
  'use strict';
  
  var MOBILE_MAX = 992; 

  function onReady(fn){ if(d.readyState!=='loading'){fn();} else {d.addEventListener('DOMContentLoaded', fn);} }
  function isMobile(){ return w.innerWidth <= MOBILE_MAX; }

  // --- 1. Obsługa Zamykania (X) ---
  function initCloseHandler() {
      d.addEventListener('click', function(e) {
          var target = e.target;
          var closeBtn = target.closest('.tvsearch-dropdown-close') || target.closest('.tvsearch-dropdown-close-wrapper');
          
          if (closeBtn) {
              e.preventDefault(); e.stopPropagation();
              var resultsContainer = d.querySelector('.tvsearch-result');
              if (resultsContainer) {
                  resultsContainer.style.display = 'none';
                  resultsContainer.innerHTML = '';
              }
              // Overlay i scroll lock
              var overlay = d.querySelector('.tvsearch-page-overlay');
              if(overlay) overlay.classList.remove('is-visible');
              d.body.classList.remove('tvsearch-scroll-lock');
              
              var form = d.querySelector('.premium-search-form');
              if(form) form.classList.remove('is-loading');
          }
      });
  }

  // --- 2. Init ---
  function init(){
    initCloseHandler();
    // Tutaj nie musimy już nic robić z kategoriami, 
    // ponieważ są one renderowane bezpośrednio w display_ajax_result.tpl
    // i obsługiwane przez główny plik tvcmssearch.js (klasa .tvsearch-category-link)
  }

  onReady(init);

})(window, document);