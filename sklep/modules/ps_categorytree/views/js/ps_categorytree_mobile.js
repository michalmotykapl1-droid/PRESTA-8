document.addEventListener('DOMContentLoaded', function () {
    
    initMobileTree();
    // Fallback
    setTimeout(initMobileTree, 500);

    function initMobileTree() {
        var togglers = document.querySelectorAll('.mobile-cat-toggler');
        if (togglers.length === 0) return;

        togglers.forEach(function (toggler) {
            if (toggler.getAttribute('data-processed') === 'true') return;
            
            toggler.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();

                var targetId = this.getAttribute('data-menu-target');
                var targetEl = document.querySelector(targetId);

                if (!targetEl) return;

                var isExpanded = this.getAttribute('aria-expanded') === 'true';

                if (isExpanded) {
                    // --- ZAMYKANIE ---
                    targetEl.classList.remove('expanded');
                    targetEl.style.display = 'none';
                    this.setAttribute('aria-expanded', 'false');
                } else {
                    // --- OTWIERANIE ---
                    
                    // 1. Akordeon: Zamknij inne na tym samym poziomie
                    var parentUl = this.closest('ul');
                    if (parentUl) {
                        var siblings = parentUl.querySelectorAll(':scope > li > .mobile-cat-children.expanded');
                        siblings.forEach(function(siblingContent) {
                            if ('#' + siblingContent.id !== targetId) {
                                var siblingToggler = siblingContent.parentNode.querySelector('.mobile-cat-toggler');
                                siblingContent.classList.remove('expanded');
                                siblingContent.style.display = 'none';
                                if(siblingToggler) siblingToggler.setAttribute('aria-expanded', 'false');
                            }
                        });
                    }

                    // 2. Otwórz kliknięte
                    targetEl.classList.add('expanded');
                    targetEl.style.display = 'block';
                    this.setAttribute('aria-expanded', 'true');

                    // --- SCROLLOWANIE (TYLKO DLA GŁÓWNYCH KATEGORII) ---
                    var parentLi = this.closest('li');
                    
                    // Sprawdzamy poziom zagłębienia
                    // depth 0 = Główne kategorie (Dziecko, Dom i Ogród itp.)
                    var depth = parentUl ? parentUl.getAttribute('data-depth') : null;

                    if (parentLi && depth == 0) {
                        setTimeout(function() {
                            parentLi.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start',
                                inline: 'nearest'
                            });
                        }, 200);
                    }
                }
            });
            toggler.setAttribute('data-processed', 'true');
        });

        // Otwórz domyślne
        var preExpanded = document.querySelectorAll('.mobile-cat-children.expanded');
        preExpanded.forEach(function(el){
            el.style.display = 'block';
        });
    }
});