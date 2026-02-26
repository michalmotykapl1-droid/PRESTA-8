{strip}
{* --- SEKCJA BLOGA - WERSJA FINAL (HEIGHT FIX) --- *}
{if isset($posts) && $posts}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<div class="bb-blog-section-container">
    <div class="container">
        
        {* --- 1. NAGŁÓWEK --- *}
        <div class="bb-blog-header-section">
            <div class="bb-header-accent">BLOG I INSPIRACJE</div>
            <h2 class="bb-header-title">STREFA WIEDZY</h2>
            <div class="bb-header-line"></div>
            <p class="bb-header-desc">
                Witaj w naszym świecie zdrowia!
                Znajdziesz tu sprawdzone przepisy, porady ekspertów na temat suplementacji oraz inspiracje do życia w stylu slow life.
            </p>
        </div>

        {* --- GŁÓWNY WRAPPER --- *}
        <div class="bb-blog-wrapper">
            
            {* --- LEWA STRONA: WPISY --- *}
            <div class="bb-blog-main-column">
                
                {* Pasek narzędzi *}
                <div class="bb-toolbar-row">
                    <a href="{if isset($latest_link)}{$latest_link|escape:'html':'UTF-8'}{else}#{/if}" class="bb-text-link">
                        ZOBACZ WSZYSTKIE WPISY <i class="fa-solid fa-arrow-right"></i>
                    </a>

                    <div class="bb-scroll-controls">
                        <button id="bb-scroll-left" class="bb-nav-btn" title="Poprzednie"><i class="fa-solid fa-chevron-left"></i></button>
                        <button id="bb-scroll-right" class="bb-nav-btn" title="Następne"><i class="fa-solid fa-chevron-right"></i></button>
                    </div>
                </div>

                {* KONTENER PRZEWIJANIA *}
                <div class="bb-scroll-wrapper">
                    <div class="bb-scroll-content" id="bbBlogScroller">
                        {for $i=1 to 3}
                            {foreach from=$posts item='post' name='blog_posts'}
                                <article class="bb-blog-card">
                                    <div class="bb-card-image-wrapper">
                                       <a href="{$post.link|escape:'html':'UTF-8'}" class="bb-blog-img-link">
                                            {if isset($post.thumb) && $post.thumb}
                                                {* --- MODYFIKACJA WEBP START --- *}
                                                {assign var="webp_thumb" value=$post.thumb|replace:'.jpg':'.webp'|replace:'.png':'.webp'|replace:'.jpeg':'.webp'}
                                                <img src="{$webp_thumb|escape:'html':'UTF-8'}" alt="{$post.title|escape:'html':'UTF-8'}" loading="lazy">
                                                {* --- MODYFIKACJA WEBP KONIEC --- *}
                                            {else}
                                               <div class="bb-no-img"><i class="fa-regular fa-image"></i></div>
                                            {/if}
                                        </a>
                                    </div>

                                    <div class="bb-card-content">
                                        <h4 class="bb-card-title">
                                            <a href="{$post.link|escape:'html':'UTF-8'}">{$post.title|escape:'html':'UTF-8'}</a>
                                        </h4>
                                        <p class="bb-card-desc">
                                            {if isset($post.short_description) && $post.short_description}
                                                {* ZMIANA: Dłuższy opis (160 znaków) wypełnia wysokość *}
                                                {$post.short_description|strip_tags:'UTF-8'|truncate:160:'...'}
                                            {/if}
                                        </p>
                                        <div class="bb-card-footer">
                                            <a href="{$post.link|escape:'html':'UTF-8'}" class="bb-read-more">
                                                CZYTAJ DALEJ
                                            </a>
                                        </div>
                                    </div>
                                </article>
                            {/foreach}
                        {/for}
                        <div class="bb-spacer" style="min-width: 1px;"></div>
                    </div>
                </div>
            </div>

            {* --- PRAWA STRONA: SIDEBAR --- *}
            <div class="bb-blog-sidebar-column">
                
                {* KATEGORIE *}
                <div class="bb-sidebar-card categories-card">
                    <div class="bb-sidebar-header">
                        <i class="fa-solid fa-layer-group"></i> KATEGORIE
                    </div>
                    <ul class="bb-cat-list scroll-cats">
                        {if isset($blockCategTree) && $blockCategTree}
                            {foreach from=$blockCategTree[0].children item=child name=blockCategTree}
                                <li>
                                    <a href="{$child.link|escape:'html':'UTF-8'}">
                                        <span class="cat-name">{$child.title|escape:'html':'UTF-8'}</span>
                                        <span class="cat-count">{$child.count_posts|intval}</span>
                                    </a>
                                </li>
                            {/foreach}
                        {else}
                            <li><span class="no-cat">Brak kategorii</span></li>
                        {/if}
                    </ul>
                </div>

                {* WYSZUKIWARKA *}
                <div class="bb-sidebar-card search-card">
                    <div class="bb-sidebar-header">
                        <i class="fa-solid fa-magnifying-glass"></i> SZUKAJ PORADY
                    </div>
                    <form action="{$link->getModuleLink('ets_blog', 'blog')|escape:'html':'UTF-8'}" method="post" class="bb-search-form">
                        <input type="hidden" name="ets_blog_search_btn" value="1">
                        <input type="text" name="ets_blog_search" placeholder="Wpisz temat...">
                        <button type="submit" class="bb-search-btn"><i class="fa-solid fa-arrow-right"></i></button>
                    </form>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const scroller = document.getElementById('bbBlogScroller');
    const btnLeft = document.getElementById('bb-scroll-left');
    const btnRight = document.getElementById('bb-scroll-right');
    const scrollStep = 345; // Karta + Gap

    if(scroller && btnLeft && btnRight) {
        btnRight.addEventListener('click', function() {
            const maxScrollLeft = scroller.scrollWidth - scroller.clientWidth;
            if(scroller.scrollLeft >= maxScrollLeft - 10) {
                 scroller.scrollTo({ left: 0, behavior: 'smooth' });
            } else {
                scroller.scrollBy({ left: scrollStep, behavior: 'smooth' });
            }
        });
        btnLeft.addEventListener('click', function() {
            if(scroller.scrollLeft <= 10) {
                scroller.scrollTo({ left: scroller.scrollWidth, behavior: 'smooth' });
            } else {
                scroller.scrollBy({ left: -scrollStep, behavior: 'smooth' });
            }
        });
    }
});
</script>
{/if}
{/strip}