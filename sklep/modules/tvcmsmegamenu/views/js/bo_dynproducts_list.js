
(function(){
  function ready(fn){ if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',fn);} else {fn();} }
  function post(url, data){
    return fetch(url, {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},
      body: new URLSearchParams(data)
    }).then(function(r){ return r.text(); });
  }
  ready(function(){
    var nodes = document.querySelectorAll('[data-tvdp-config]');
    if(!nodes.length) return;
    // Build ajax url against current configure page
    var base = window.location.href.split('#')[0];
    // ensure ajax=1&action=tvdpPreview ends up in the request
    nodes.forEach(function(n){
      var cfg = n.getAttribute('data-tvdp-config');
      if(!cfg) return;
      // where to inject
      var target = n.querySelector('.tv-megamenu-slider-wrapper') || n;
      post(base, {ajax:'1', action:'tvdpPreview', cfg: cfg}).then(function(html){
        try{
          target.innerHTML = html;
        }catch(e){ /* ignore */ }
      });
    });
  });
})();
