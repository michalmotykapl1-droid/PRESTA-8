(function () {
  function patchCorrespondenceLinks() {
    try {
      var selector = 'a[href*="controller=AdminAllegroProCorrespondence"]';
      var links = document.querySelectorAll(selector);
      if (!links || !links.length) return;

      links.forEach(function (a) {
        // Ustawiamy target, ale dodatkowo dokładamy click handler (na wypadek gdyby admin menu przechwytywało nawigację)
        a.setAttribute('target', '_blank');
        a.setAttribute('rel', 'noopener');
        if (!a.getAttribute('title')) {
          a.setAttribute('title', 'Otwórz Korespondencję w nowej karcie');
        }

        if (a.__apCorrBound) return;
        a.__apCorrBound = true;

        a.addEventListener('click', function (e) {
          // Jeśli użytkownik używa modyfikatorów (Ctrl/⌘/Shift) – nie przeszkadzamy
          if (e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;
          if (typeof e.button === 'number' && e.button !== 0) return; // tylko lewy klik

          // Otwórz w nowej karcie i nie zmieniaj aktualnego widoku BO
          try {
            e.preventDefault();
            window.open(a.href, '_blank', 'noopener');
          } catch (err) {
            // fallback: pozwól przeglądarce przejść normalnie
          }
        });
      });
    } catch (e) {
      // ignore
    }
  }

  document.addEventListener('DOMContentLoaded', patchCorrespondenceLinks);

  // Presta czasem przebudowuje menu dynamicznie – obserwujemy DOM i dopinamy target ponownie
  try {
    var obs = new MutationObserver(function () {
      patchCorrespondenceLinks();
    });
    obs.observe(document.documentElement, { childList: true, subtree: true });
  } catch (e) {
    // ignore
  }
})();
