{**
 * Strefa Zdrowia - WIDOK LEWEJ KOLUMNY (Z linkiem)
 *}
{strip}
<div class="strefa-left-widget tvall-block-box-shadows">
    
    {* NAGŁÓWEK WIDGETU *}
    <div class="strefa-left-header">
        <div class="left-icon-badge">
            <i class='material-icons'>local_hospital</i>
        </div>
        <h3>STREFA ZDROWIA</h3>
        <div class="left-divider"></div>
    </div>

    {* TREŚĆ WIDGETU *}
    <div class="strefa-left-content">
        <p class="left-intro">
            Kompleksowa opieka specjalistów. <br><strong>Stacjonarnie, mobilnie i online.</strong>
        </p>

        {* LISTA USŁUG *}
        <ul class="left-menu-list">
            <li>
                <a href="#" class="left-menu-link">
                    <i class='material-icons'>accessibility_new</i>
                    <span>Fizjoterapia</span>
                </a>
            </li>
            <li>
                <a href="#" class="left-menu-link">
                    <i class='material-icons'>spa</i>
                    <span>Naturopatia</span>
                </a>
            </li>
            <li>
                <a href="#" class="left-menu-link">
                    <i class='material-icons'>help_outline</i>
                    <span>Porady & Uroda</span>
                </a>
            </li>
            <li>
                <a href="#" class="left-menu-link">
                    <i class='material-icons'>healing</i>
                    <span>Diagnostyka</span>
                </a>
            </li>
        </ul>

        {* CTA BUTTON - LINK DO LANDING PAGE *}
        <div class="left-cta-box">
            <a href="{$link->getModuleLink('tvcmsstrefazdrowia', 'display')}" class="btn-left-medical">
                WEJDŹ DO STREFY <i class='material-icons'>arrow_forward</i>
            </a>
        </div>
    </div>

</div>
{/strip}