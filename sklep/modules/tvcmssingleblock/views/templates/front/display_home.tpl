{**
* ThemeVolty - Single Block (Loyalty Program - Clean Text Link Style)
*}
{strip}
{if $dis_arr_result.status}
<div class="container-fluid tvcmssingle-block-modern-wrapper">
    <div class="container">
        <div class="tv-loyalty-box">
            
            {* CZĘŚĆ 1: GŁÓWNE HASŁO I LINK TEKSTOWY *}
            <div class="tv-loyalty-intro">
                <div class="tv-loyalty-header">
                    <span class="tv-loyalty-label">PROGRAM LOJALNOŚCIOWY</span>
                    <h2 class="tv-loyalty-title">Zbieraj punkty, <span>odbieraj rabaty!</span></h2>
                </div>
                
                {* LINK (Zamiast przycisku) *}
                <a href="{$dis_arr_result.data.link}" class="tv-loyalty-link">
                    ZOBACZ SWOJE PUNKTY <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

            {* CZĘŚĆ 2: 3 KROKI *}
            <div class="tv-loyalty-steps">
                
                {* KROK 1 *}
                <div class="tv-step-item">
                    <div class="tv-step-icon">
                        <i class="fa-solid fa-cart-shopping"></i>
                    </div>
                    <div class="tv-step-info">
                        <h4>Kupuj produkty</h4>
                        <p>Każde wydane <strong>{$dis_arr_result.data.loyalty_rate} zł</strong> to <strong>1 pkt</strong> na Twoim koncie</p>
                    </div>
                </div>

                {* STRZAŁKA *}
                <div class="tv-step-arrow"><i class="fa-solid fa-chevron-right"></i></div>

                {* KROK 2 *}
                <div class="tv-step-item">
                    <div class="tv-step-icon">
                        <i class="fa-solid fa-piggy-bank"></i>
                    </div>
                    <div class="tv-step-info">
                        <h4>Zbieraj punkty</h4>
                        <p>Punkty sumują się automatycznie i są ważne <strong>{$dis_arr_result.data.loyalty_validity} dni</strong></p>
                    </div>
                </div>

                {* STRZAŁKA *}
                <div class="tv-step-arrow"><i class="fa-solid fa-chevron-right"></i></div>

                {* KROK 3 *}
                <div class="tv-step-item">
                    <div class="tv-step-icon">
                        <i class="fa-solid fa-gift"></i>
                    </div>
                    <div class="tv-step-info">
                        <h4>Płać mniej</h4>
                        {* Obliczenie przykładowego rabatu dla 20 pkt *}
                        {assign var="example_points" value=20}
                        {assign var="example_discount" value=$dis_arr_result.data.loyalty_value * $example_points}
                        
                        <p>Wymień punkty na bon: <strong>{$example_points} pkt = {$example_discount|replace:'.':','} zł</strong> rabatu</p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
{/if}
{/strip}