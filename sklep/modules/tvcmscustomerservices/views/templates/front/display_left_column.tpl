{**
* ThemeVolty - Customer Services (Left Column - Minimalist Thin Style)
*}
{strip}
<div class="bb-left-container">
    <div class="bb-left-header">
        <h4>DLACZEGO BIGBIO?</h4>
    </div>

    <div class="bb-services-list">
        
        {* 1. 10 LAT DOŚWIADCZENIA *}
        <div class="bb-service-item">
            <div class="bb-icon-box">
                {* Używamy fa-solid, CSS zrobi z tego cienki kontur *}
                <i class="fa-solid fa-trophy"></i>
            </div>
            <div class="bb-text-box">
                <span class="bb-title-big">10 LAT</span>
                <span class="bb-desc-small">DOŚWIADCZENIA</span>
            </div>
        </div>

        {* 2. 100% KAPITAŁ *}
        <div class="bb-service-item">
            <div class="bb-icon-box">
                <i class="fa-solid fa-sack-dollar"></i>
            </div>
            <div class="bb-text-box">
                <span class="bb-title-big">100% POLSKI</span>
                <span class="bb-desc-small">KAPITAŁ</span>
            </div>
        </div>

        {* 3. LICZNIK ZAMÓWIEŃ *}
        <div class="bb-service-item">
            <div class="bb-icon-box">
                <i class="fa-solid fa-truck-fast"></i>
            </div>
            <div class="bb-text-box">
                <span class="bb-title-big bb-counter" data-target="500000">+0</span>
                <span class="bb-desc-small">ZREALIZOWANYCH ZAMÓWIEŃ</span>
            </div>
        </div>

        {* 4. OCENA KLIENTÓW *}
        <div class="bb-service-item">
            <div class="bb-icon-box">
                <i class="fa-solid fa-star"></i>
            </div>
            <div class="bb-text-box">
                <span class="bb-title-big">4.9 / 5.0</span>
                <span class="bb-desc-small">ŚREDNIA OCEN</span>
            </div>
        </div>

    </div>
</div>

{* Skrypt licznika (zostaje bez zmian, działa dobrze) *}
<script>
document.addEventListener("DOMContentLoaded", function() {
    const counters = document.querySelectorAll('.bb-counter');
    const duration = 1000; 

    const startAnimation = () => {
        counters.forEach(counter => {
            if (counter.classList.contains('animated')) return;
            const target = +counter.getAttribute('data-target');
            const startTime = performance.now();
            const updateCount = (currentTime) => {
                const elapsedTime = currentTime - startTime;
                const progress = Math.min(elapsedTime / duration, 1);
                const easeOut = t => 1 - Math.pow(1 - t, 3);
                const currentCount = Math.floor(easeOut(progress) * target);
                counter.innerText = "+" + currentCount.toLocaleString('pl-PL').replace(/,/g, ' ');
                if (progress < 1) {
                    requestAnimationFrame(updateCount);
                } else {
                    counter.innerText = "+" + target.toLocaleString('pl-PL').replace(/,/g, ' ');
                    counter.classList.add('animated');
                }
            };
            requestAnimationFrame(updateCount);
        });
    };
    let observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) { startAnimation(); }
        });
    }, { threshold: 0.1 });
    counters.forEach(c => observer.observe(c));
});
</script>
{/strip}