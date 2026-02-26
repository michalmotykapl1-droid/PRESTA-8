(function () {
  function findMenuLink() {
    // Presta często używa id: subtab-<ClassName>
    var el = document.querySelector('#subtab-AdminAzadaCronLogs a');
    if (el) return el;

    // Fallback: link zawiera controller
    el = document.querySelector('a[href*="controller=AdminAzadaCronLogs"]');
    if (el) return el;

    // Fallback 2: czasem w URL jest tylko nazwa kontrolera
    el = document.querySelector('a[href*="AdminAzadaCronLogs"]');
    if (el) return el;

    return null;
  }

  function setBadge(count) {
    var link = findMenuLink();
    if (!link) return;

    // usuń stary badge (jeśli jest)
    var old = link.querySelector('.azada-cronlog-badge');
    if (old) old.remove();

    if (!count || count <= 0) {
      return;
    }

    var span = document.createElement('span');
    span.className = 'badge badge-danger azada-cronlog-badge';
    span.style.marginLeft = '6px';
    span.textContent = String(count);
    link.appendChild(span);
  }

  function fetchCount() {
    if (typeof AZADA_CRON_LOG_BADGE_URL === 'undefined' || !AZADA_CRON_LOG_BADGE_URL) {
      return;
    }

    try {
      fetch(AZADA_CRON_LOG_BADGE_URL, { credentials: 'same-origin' })
        .then(function (r) {
          return r.json();
        })
        .then(function (data) {
          var c = 0;
          if (data && typeof data.count !== 'undefined') {
            c = parseInt(data.count, 10) || 0;
          }
          setBadge(c);
        })
        .catch(function () {
          // cicho
        });
    } catch (e) {
      // cicho
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    // 1x po załadowaniu
    fetchCount();

    // Odświeżaj co 60s (lekko, bez spamu)
    setInterval(fetchCount, 60000);
  });
})();
