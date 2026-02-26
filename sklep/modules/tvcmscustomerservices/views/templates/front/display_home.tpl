{**
* ThemeVolty - Customer Services (Home - Gray to Black Hover)
*}
{strip}
{if $dis_arr_result.status}
    <div class="tvcmscustomer-services-home container-fluid">
        <div class="container">
            <div class="tvservices-home-wrapper">
                
                {* USŁUGA 1: 100% BEZPIECZNE ZAKUPY *}
                {if $dis_arr_result.data.service_1.status}
                <div class="tvservice-home-item">
                    <div class="tvservice-home-icon">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <div class="tvservice-home-content">
                        <div class="tvservice-home-title">{$dis_arr_result.data.service_1.title}</div>
                        <div class="tvservice-home-desc">{$dis_arr_result.data.service_1.desc}</div>
                    </div>
                </div>
                {/if}

                {* USŁUGA 2: PROGRAM LOJALNOŚCIOWY (Świnka) *}
                {if $dis_arr_result.data.service_2.status}
                <div class="tvservice-home-item">
                    <div class="tvservice-home-icon">
                        <i class="fa-solid fa-piggy-bank"></i>
                    </div>
                    <div class="tvservice-home-content">
                        <div class="tvservice-home-title">{$dis_arr_result.data.service_2.title}</div>
                        <div class="tvservice-home-desc">{$dis_arr_result.data.service_2.desc}</div>
                    </div>
                </div>
                {/if}

                {* USŁUGA 3: SZYBKA REALIZACJA *}
                {if $dis_arr_result.data.service_3.status}
                <div class="tvservice-home-item">
                    <div class="tvservice-home-icon">
                        <i class="fa-regular fa-clock"></i>
                    </div>
                    <div class="tvservice-home-content">
                        <div class="tvservice-home-title">{$dis_arr_result.data.service_3.title}</div>
                        <div class="tvservice-home-desc">{$dis_arr_result.data.service_3.desc}</div>
                    </div>
                </div>
                {/if}

                {* USŁUGA 4: 30 DNI NA ZWROT (Strzałka w kółku) *}
                {if $dis_arr_result.data.service_4.status}
                <div class="tvservice-home-item">
                    <div class="tvservice-home-icon">
                        <i class="fa-solid fa-rotate-left"></i>
                    </div>
                    <div class="tvservice-home-content">
                        <div class="tvservice-home-title">{$dis_arr_result.data.service_4.title}</div>
                        <div class="tvservice-home-desc">{$dis_arr_result.data.service_4.desc}</div>
                    </div>
                </div>
                {/if}

                {* NOWOŚĆ: USŁUGA 5 - LICZNIK ZAMÓWIEŃ *}
                <div class="tvservice-home-item tv-counter-box">
                    <div class="tvservice-home-icon">
                        <i class="fa-solid fa-boxes-stacked"></i>
                    </div>
                    <div class="tvservice-home-content">
                        <div class="tvservice-home-title tv-counter-number" data-target="500000">+0</div>
                        <div class="tvservice-home-desc">Zrealizowanych zamówień</div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {* SKRYPT DO ANIMACJI LICZNIKA *}
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const counters = document.querySelectorAll('.tv-counter-number');
        const duration = 2000;

        const animateCounters = () => {
            counters.forEach(counter => {
                const target = +counter.getAttribute('data-target');
                const startTime = performance.now();

                const updateCount = (currentTime) => {
                    const elapsedTime = currentTime - startTime;
                    const progress = Math.min(elapsedTime / duration, 1);
                    const easeOutQuad = t => t * (2 - t);
                    
                    const currentCount = Math.floor(easeOutQuad(progress) * target);

                    if (progress < 1) {
                        counter.innerText = "+" + currentCount.toLocaleString('pl-PL');
                        requestAnimationFrame(updateCount);
                    } else {
                        counter.innerText = "+" + target.toLocaleString('pl-PL');
                    }
                };
                requestAnimationFrame(updateCount);
            });
        };

        let observer = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounters();
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        counters.forEach(counter => {
            observer.observe(counter);
        });
    });
    </script>
{/if}
{/strip}