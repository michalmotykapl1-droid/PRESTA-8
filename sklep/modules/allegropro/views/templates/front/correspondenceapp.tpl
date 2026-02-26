<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#ff5a00">
  <title>{$ap_app_title|escape:'htmlall':'UTF-8'} - Allegro Pro</title>

  {assign var=ap_assets_b value=$ap_bridge.ts|intval}
  <link rel="stylesheet" href="{$ap_module_path|escape:'htmlall':'UTF-8'}views/css/correspondenceapp.css?v={$ap_module_version|escape:'htmlall':'UTF-8'}&b={$ap_assets_b}">
</head>
<body>

<!-- Inline SVG sprite (icons) -->
<svg xmlns="http://www.w3.org/2000/svg" style="position:absolute;width:0;height:0;overflow:hidden" aria-hidden="true" focusable="false">
  <symbol id="ap-ico-mail" viewBox="0 0 24 24">
    <path d="M4 6h16v12H4z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
    <path d="M4 7l8 6 8-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
  </symbol>
  <symbol id="ap-ico-chat" viewBox="0 0 24 24">
    <path d="M5 6h14a3 3 0 0 1 3 3v5a3 3 0 0 1-3 3H11l-4 3v-3H5a3 3 0 0 1-3-3V9a3 3 0 0 1 3-3z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
    <path d="M7 11h10M7 14h7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
  </symbol>
  <symbol id="ap-ico-list" viewBox="0 0 24 24">
    <path d="M7 7h14M7 12h14M7 17h14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
    <path d="M4 7h.01M4 12h.01M4 17h.01" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
  </symbol>
  <symbol id="ap-ico-unread" viewBox="0 0 24 24">
    <path d="M4 6h16v12H4z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
    <path d="M4 7l8 6 8-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    <circle cx="18" cy="6" r="3" fill="currentColor"/>
  </symbol>
  <symbol id="ap-ico-reply" viewBox="0 0 24 24">
    <path d="M9 9l-5 5 5 5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M4 14h9a7 7 0 0 1 7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
  </symbol>
  <symbol id="ap-ico-receipt" viewBox="0 0 24 24">
    <path d="M6 2h12v20l-2-1-2 1-2-1-2 1-2-1-2 1z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
    <path d="M9 7h6M9 11h6M9 15h6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
  </symbol>
  <symbol id="ap-ico-tag" viewBox="0 0 24 24">
    <path d="M20 13l-7 7-11-11V2h7z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
    <circle cx="7.5" cy="7.5" r="1.5" fill="currentColor"/>
  </symbol>
  <symbol id="ap-ico-info" viewBox="0 0 24 24">
    <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/>
    <path d="M12 11v6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
    <path d="M12 7h.01" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
  </symbol>
  <symbol id="ap-ico-clip" viewBox="0 0 24 24">
    <path d="M8 12l7-7a4 4 0 0 1 6 6l-9 9a6 6 0 0 1-8-8l9-9" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
  </symbol>
  <symbol id="ap-ico-clock" viewBox="0 0 24 24">
    <circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2"/>
    <path d="M12 7v6l4 2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
  </symbol>
  <symbol id="ap-ico-calendar" viewBox="0 0 24 24">
    <path d="M7 2v3M17 2v3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
    <path d="M4 6h16v16H4z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
    <path d="M4 10h16" fill="none" stroke="currentColor" stroke-width="2"/>
  </symbol>
  <symbol id="ap-ico-inbox" viewBox="0 0 24 24">
    <path d="M4 4h16v10l-3 6H7l-3-6z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
    <path d="M7 14h3l2 2h2l2-2h3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
  </symbol>
  <symbol id="ap-ico-spark" viewBox="0 0 24 24">
    <path d="M12 2l1.4 5.2L19 9l-5.6 1.8L12 16l-1.4-5.2L5 9l5.6-1.8z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
  </symbol>
  <symbol id="ap-ico-user" viewBox="0 0 24 24">
    <path d="M20 21a8 8 0 0 0-16 0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
    <circle cx="12" cy="8" r="4" fill="none" stroke="currentColor" stroke-width="2"/>
  </symbol>
  <symbol id="ap-ico-timer" viewBox="0 0 24 24">
    <path d="M9 2h6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
    <path d="M12 7a9 9 0 1 0 9 9" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
    <path d="M12 12l3-2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
    <path d="M19 6l2 2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
  </symbol>
  <symbol id="ap-ico-shield" viewBox="0 0 24 24">
    <path d="M12 2l8 4v6c0 5-3.4 9.4-8 10-4.6-.6-8-5-8-10V6z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
  </symbol>
  <symbol id="ap-ico-cash" viewBox="0 0 24 24">
    <path d="M3 7h18v10H3z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
    <circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="2"/>
    <path d="M7 9h0M17 15h0" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
  </symbol>
  <symbol id="ap-ico-swap" viewBox="0 0 24 24">
    <path d="M7 7h13l-3-3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M17 17H4l3 3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
  </symbol>
  <symbol id="ap-ico-wrench" viewBox="0 0 24 24">
    <path d="M21 7a6 6 0 0 1-8 5L7 18l-3-3 6-6a6 6 0 0 1 5-8l-2 4 4 4z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
  </symbol>
  <symbol id="ap-ico-return" viewBox="0 0 24 24">
    <path d="M9 10l-4 4 4 4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M5 14h10a6 6 0 0 0 0-12H9" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
  </symbol>
  <symbol id="ap-ico-alert" viewBox="0 0 24 24">
    <path d="M12 2l10 20H2z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
    <path d="M12 9v5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
    <path d="M12 17h.01" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
  </symbol>
  <symbol id="ap-ico-check" viewBox="0 0 24 24">
    <path d="M20 6L9 17l-5-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
  </symbol>
  <symbol id="ap-ico-x" viewBox="0 0 24 24">
    <path d="M18 6L6 18M6 6l12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
  </symbol>
  <symbol id="ap-ico-sync" viewBox="0 0 24 24">
    <path d="M21 12a9 9 0 0 0-15.5-6.4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
    <path d="M3 4v6h6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M3 12a9 9 0 0 0 15.5 6.4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
    <path d="M21 20v-6h-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
  </symbol>
  <symbol id="ap-ico-plus" viewBox="0 0 24 24">
    <path d="M12 5v14M5 12h14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
  </symbol>
  <symbol id="ap-ico-search" viewBox="0 0 24 24">
    <circle cx="11" cy="11" r="7" fill="none" stroke="currentColor" stroke-width="2"/>
    <path d="M20 20l-3.5-3.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
  </symbol>
  <symbol id="ap-ico-doc" viewBox="0 0 24 24">
    <path d="M7 2h7l3 3v17H7z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
    <path d="M14 2v5h5" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
    <path d="M9 12h6M9 16h6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
  </symbol>
</svg>

<div class="ap-app ap-section-messages" id="ap-app">
  <header class="ap-topbar">
    <div class="ap-topbar__left">
      <div class="ap-brand">ALLEGRO PRO <span class="ap-brand__dot" aria-hidden="true"></span></div>
      <div class="ap-title">{$ap_app_title|escape:'htmlall':'UTF-8'}</div>
    </div>
    <div class="ap-topbar__right">
      <button type="button" class="ap-userbtn" data-ap-action="openViewSettings" aria-label="Ustawienia widoku">
        <span class="ap-userbtn__avatar" data-ap-user-initials>—</span>
        <span class="ap-userbtn__name" data-ap-user-name>{$ap_employee_name|escape:'htmlall':'UTF-8'}</span>
        <svg class="ap-ico ap-userbtn__ico" aria-hidden="true"><use href="#ap-ico-wrench"></use></svg>
      </button>
    </div>
  </header>

  <div class="ap-shell">
    <aside class="ap-sidebar" aria-label="Filtry korespondencji">
      <div class="ap-sidebar__inner">

        <div class="ap-sidebar__section ap-sidebar__section--tight">
          <div class="ap-sidebar__search">
            <div class="ap-inputwrap">
              <span class="ap-inputwrap__icon" aria-hidden="true">
                <svg class="ap-ico ap-ico--muted"><use href="#ap-ico-search"></use></svg>
              </span>
              <input
                type="search"
                class="ap-input"
                placeholder="Szukaj (login, zamówienie, oferta…)"
                aria-label="Szukaj w korespondencji"
                data-ap-search>
            </div>
            <div class="ap-hint">Szukaj działa po synchronizacji. Obsługuje: login, ID wątku/issue, checkoutForm.id, offer.id.</div>
          </div>
        </div>

        <div class="ap-sidebar__section">
          <div class="ap-sidebar__title">Widok</div>

          <button type="button" class="ap-navitem ap-navitem--active" data-ap-section="messages">
            <span class="ap-itemleft">
              <span class="ap-icobox ap-icobox--orange" aria-hidden="true">
                <svg class="ap-ico"><use href="#ap-ico-mail"></use></svg>
              </span>
              <span class="ap-navitem__label">Wiadomości</span>
            </span>
            <span class="ap-badge" data-ap-badge="messages_all">—</span>
          </button>

          <button type="button" class="ap-navitem" data-ap-section="issues">
            <span class="ap-itemleft">
              <span class="ap-icobox ap-icobox--blue" aria-hidden="true">
                <svg class="ap-ico"><use href="#ap-ico-chat"></use></svg>
              </span>
              <span class="ap-navitem__label">Dyskusje / reklamacje</span>
            </span>
            <span class="ap-badge" data-ap-badge="issues_all">—</span>
          </button>
        </div>

        <!-- Filtry: Wiadomości -->
        <div class="ap-sidebar__section" data-ap-filter-group="messages">
          <div class="ap-sidebar__title">Wiadomości — szybkie filtry</div>

          <button type="button" class="ap-filter ap-filter--active" data-ap-section="messages" data-ap-filter="msg_all">
            <span class="ap-itemleft">
              <span class="ap-icobox ap-icobox--gray" aria-hidden="true">
                <svg class="ap-ico"><use href="#ap-ico-list"></use></svg>
              </span>
              <span>Wszystkie</span>
            </span>
            <span class="ap-badge" data-ap-badge="msg_all">—</span>
          </button>

          <button type="button" class="ap-filter" data-ap-section="messages" data-ap-filter="msg_unread">
            <span class="ap-itemleft">
              <span class="ap-icobox ap-icobox--orange" aria-hidden="true">
                <svg class="ap-ico"><use href="#ap-ico-unread"></use></svg>
              </span>
              <span>Nieprzeczytane</span>
            </span>
            <span class="ap-badge" data-ap-badge="msg_unread">—</span>
          </button>

          <button type="button" class="ap-filter" data-ap-section="messages" data-ap-filter="msg_need_reply">
            <span class="ap-itemleft">
              <span class="ap-icobox ap-icobox--purple" aria-hidden="true">
                <svg class="ap-ico"><use href="#ap-ico-reply"></use></svg>
              </span>
              <span>Wymaga odpowiedzi</span>
            </span>
            <span class="ap-badge" data-ap-badge="msg_need_reply">—</span>
          </button>

          <div class="ap-divider"></div>

          <button type="button" class="ap-filter" data-ap-section="messages" data-ap-filter="msg_order">
            <span class="ap-itemleft">
              <span class="ap-icobox ap-icobox--blue" aria-hidden="true">
                <svg class="ap-ico"><use href="#ap-ico-receipt"></use></svg>
              </span>
              <span>Dot. zamówienia</span>
            </span>
            <span class="ap-badge" data-ap-badge="msg_order">—</span>
          </button>

          <button type="button" class="ap-filter" data-ap-section="messages" data-ap-filter="msg_offer">
            <span class="ap-itemleft">
              <span class="ap-icobox ap-icobox--teal" aria-hidden="true">
                <svg class="ap-ico"><use href="#ap-ico-tag"></use></svg>
              </span>
              <span>Dot. oferty</span>
            </span>
            <span class="ap-badge" data-ap-badge="msg_offer">—</span>
          </button>

          <button type="button" class="ap-filter" data-ap-section="messages" data-ap-filter="msg_general">
            <span class="ap-itemleft">
              <span class="ap-icobox ap-icobox--gray" aria-hidden="true">
                <svg class="ap-ico"><use href="#ap-ico-info"></use></svg>
              </span>
              <span>Ogólne</span>
            </span>
            <span class="ap-badge" data-ap-badge="msg_general">—</span>
          </button>

          <div class="ap-divider"></div>

          <button type="button" class="ap-filter" data-ap-section="messages" data-ap-filter="msg_attachments">
            <span class="ap-itemleft">
              <span class="ap-icobox ap-icobox--gray" aria-hidden="true">
                <svg class="ap-ico"><use href="#ap-ico-clip"></use></svg>
              </span>
              <span>Z załącznikami</span>
            </span>
            <span class="ap-badge" data-ap-badge="msg_attachments">—</span>
          </button>

          <button type="button" class="ap-filter" data-ap-section="messages" data-ap-filter="msg_last24h">
            <span class="ap-itemleft">
              <span class="ap-icobox ap-icobox--gray" aria-hidden="true">
                <svg class="ap-ico"><use href="#ap-ico-clock"></use></svg>
              </span>
              <span>Ostatnie 24h</span>
            </span>
            <span class="ap-badge" data-ap-badge="msg_last24h">—</span>
          </button>

          <button type="button" class="ap-filter" data-ap-section="messages" data-ap-filter="msg_last7d">
            <span class="ap-itemleft">
              <span class="ap-icobox ap-icobox--gray" aria-hidden="true">
                <svg class="ap-ico"><use href="#ap-ico-calendar"></use></svg>
              </span>
              <span>Ostatnie 7 dni</span>
            </span>
            <span class="ap-badge" data-ap-badge="msg_last7d">—</span>
          </button>
        </div>

        <!-- Filtry: Dyskusje / reklamacje -->
        <div class="ap-sidebar__section" data-ap-filter-group="issues" hidden>

          <div class="ap-filterblock ap-filterblock--dispute">
            <div class="ap-filterblock__head">
              <span class="ap-filterblock__title">Dyskusje</span>
              <span class="ap-badge ap-badge--soft" data-ap-badge="iss_dispute_all">—</span>
            </div>

            <button type="button" class="ap-filter" data-ap-section="issues" data-ap-filter="iss_dispute_all">
              <span class="ap-itemleft">
                <span class="ap-icobox ap-icobox--gray" aria-hidden="true">
                  <svg class="ap-ico"><use href="#ap-ico-inbox"></use></svg>
                </span>
                <span>Wszystkie</span>
              </span>
              <span class="ap-badge" data-ap-badge="iss_dispute_all">—</span>
            </button>

            <button type="button" class="ap-filter" data-ap-section="issues" data-ap-filter="iss_dispute_new">
              <span class="ap-itemleft">
                <span class="ap-icobox ap-icobox--orange" aria-hidden="true">
                  <svg class="ap-ico"><use href="#ap-ico-spark"></use></svg>
                </span>
                <span>Nowe</span>
              </span>
              <span class="ap-badge" data-ap-badge="iss_dispute_new">—</span>
            </button>

            <button type="button" class="ap-filter ap-filter--active" data-ap-section="issues" data-ap-filter="iss_dispute_waiting">
              <span class="ap-itemleft">
                <span class="ap-icobox ap-icobox--purple" aria-hidden="true">
                  <svg class="ap-ico"><use href="#ap-ico-user"></use></svg>
                </span>
                <span>Do odpowiedzi</span>
              </span>
              <span class="ap-badge" data-ap-badge="iss_dispute_waiting">—</span>
            </button>

            <button type="button" class="ap-filter" data-ap-section="issues" data-ap-filter="iss_dispute_due_soon">
              <span class="ap-itemleft">
                <span class="ap-icobox ap-icobox--red" aria-hidden="true">
                  <svg class="ap-ico"><use href="#ap-ico-timer"></use></svg>
                </span>
                <span>Termin blisko</span>
              </span>
              <span class="ap-badge" data-ap-badge="iss_dispute_due_soon">—</span>
            </button>

            <div class="ap-divider"></div>

            <button type="button" class="ap-filter" data-ap-section="issues" data-ap-filter="iss_dispute_ongoing">
              <span class="ap-itemleft">
                <span class="ap-icobox ap-icobox--blue" aria-hidden="true">
                  <svg class="ap-ico"><use href="#ap-ico-chat"></use></svg>
                </span>
                <span>W trakcie</span>
              </span>
              <span class="ap-badge" data-ap-badge="iss_dispute_ongoing">—</span>
            </button>

            <button type="button" class="ap-filter" data-ap-section="issues" data-ap-filter="iss_dispute_unresolved">
              <span class="ap-itemleft">
                <span class="ap-icobox ap-icobox--amber" aria-hidden="true">
                  <svg class="ap-ico"><use href="#ap-ico-alert"></use></svg>
                </span>
                <span>Nierozwiązane</span>
              </span>
              <span class="ap-badge" data-ap-badge="iss_dispute_unresolved">—</span>
            </button>

            <button type="button" class="ap-filter" data-ap-section="issues" data-ap-filter="iss_dispute_closed">
              <span class="ap-itemleft">
                <span class="ap-icobox ap-icobox--gray" aria-hidden="true">
                  <svg class="ap-ico"><use href="#ap-ico-check"></use></svg>
                </span>
                <span>Zamknięte</span>
              </span>
              <span class="ap-badge" data-ap-badge="iss_dispute_closed">—</span>
            </button>
          </div>

          <div class="ap-filterblock ap-filterblock--claim">
            <div class="ap-filterblock__head">
              <span class="ap-filterblock__title">Reklamacje</span>
              <span class="ap-badge ap-badge--soft" data-ap-badge="iss_claim_all">—</span>
            </div>

            <button type="button" class="ap-filter" data-ap-section="issues" data-ap-filter="iss_claim_all">
              <span class="ap-itemleft">
                <span class="ap-icobox ap-icobox--gray" aria-hidden="true">
                  <svg class="ap-ico"><use href="#ap-ico-inbox"></use></svg>
                </span>
                <span>Wszystkie</span>
              </span>
              <span class="ap-badge" data-ap-badge="iss_claim_all">—</span>
            </button>

            <button type="button" class="ap-filter" data-ap-section="issues" data-ap-filter="iss_claim_new">
              <span class="ap-itemleft">
                <span class="ap-icobox ap-icobox--orange" aria-hidden="true">
                  <svg class="ap-ico"><use href="#ap-ico-spark"></use></svg>
                </span>
                <span>Nowe</span>
              </span>
              <span class="ap-badge" data-ap-badge="iss_claim_new">—</span>
            </button>

            <button type="button" class="ap-filter" data-ap-section="issues" data-ap-filter="iss_claim_waiting">
              <span class="ap-itemleft">
                <span class="ap-icobox ap-icobox--purple" aria-hidden="true">
                  <svg class="ap-ico"><use href="#ap-ico-user"></use></svg>
                </span>
                <span>Do odpowiedzi</span>
              </span>
              <span class="ap-badge" data-ap-badge="iss_claim_waiting">—</span>
            </button>

            <button type="button" class="ap-filter" data-ap-section="issues" data-ap-filter="iss_claim_due_soon">
              <span class="ap-itemleft">
                <span class="ap-icobox ap-icobox--red" aria-hidden="true">
                  <svg class="ap-ico"><use href="#ap-ico-timer"></use></svg>
                </span>
                <span>Termin blisko</span>
              </span>
              <span class="ap-badge" data-ap-badge="iss_claim_due_soon">—</span>
            </button>

            <div class="ap-divider"></div>

            <button type="button" class="ap-filter" data-ap-section="issues" data-ap-filter="iss_claim_submitted">
              <span class="ap-itemleft">
                <span class="ap-icobox ap-icobox--purple" aria-hidden="true">
                  <svg class="ap-ico"><use href="#ap-ico-doc"></use></svg>
                </span>
                <span>Złożone</span>
              </span>
              <span class="ap-badge" data-ap-badge="iss_claim_submitted">—</span>
            </button>

            <button type="button" class="ap-filter" data-ap-section="issues" data-ap-filter="iss_claim_accepted">
              <span class="ap-itemleft">
                <span class="ap-icobox ap-icobox--green" aria-hidden="true">
                  <svg class="ap-ico"><use href="#ap-ico-check"></use></svg>
                </span>
                <span>Zaakceptowane</span>
              </span>
              <span class="ap-badge" data-ap-badge="iss_claim_accepted">—</span>
            </button>

            <button type="button" class="ap-filter" data-ap-section="issues" data-ap-filter="iss_claim_rejected">
              <span class="ap-itemleft">
                <span class="ap-icobox ap-icobox--red" aria-hidden="true">
                  <svg class="ap-ico"><use href="#ap-ico-x"></use></svg>
                </span>
                <span>Odrzucone</span>
              </span>
              <span class="ap-badge" data-ap-badge="iss_claim_rejected">—</span>
            </button>

            <details class="ap-accordion">
              <summary>Zwroty / roszczenia</summary>
              <div class="ap-accordion__body">
                <button type="button" class="ap-filter" data-ap-section="issues" data-ap-filter="iss_expect_refund">
                  <span class="ap-itemleft">
                    <span class="ap-icobox ap-icobox--green" aria-hidden="true">
                      <svg class="ap-ico"><use href="#ap-ico-cash"></use></svg>
                    </span>
                    <span>Zwrot pieniędzy</span>
                  </span>
                  <span class="ap-badge" data-ap-badge="iss_expect_refund">—</span>
                </button>

                <button type="button" class="ap-filter" data-ap-section="issues" data-ap-filter="iss_expect_partial_refund">
                  <span class="ap-itemleft">
                    <span class="ap-icobox ap-icobox--green" aria-hidden="true">
                      <svg class="ap-ico"><use href="#ap-ico-cash"></use></svg>
                    </span>
                    <span>Częściowy zwrot</span>
                  </span>
                  <span class="ap-badge" data-ap-badge="iss_expect_partial_refund">—</span>
                </button>

                <button type="button" class="ap-filter" data-ap-section="issues" data-ap-filter="iss_expect_exchange">
                  <span class="ap-itemleft">
                    <span class="ap-icobox ap-icobox--teal" aria-hidden="true">
                      <svg class="ap-ico"><use href="#ap-ico-swap"></use></svg>
                    </span>
                    <span>Wymiana</span>
                  </span>
                  <span class="ap-badge" data-ap-badge="iss_expect_exchange">—</span>
                </button>

                <button type="button" class="ap-filter" data-ap-section="issues" data-ap-filter="iss_expect_repair">
                  <span class="ap-itemleft">
                    <span class="ap-icobox ap-icobox--teal" aria-hidden="true">
                      <svg class="ap-ico"><use href="#ap-ico-wrench"></use></svg>
                    </span>
                    <span>Naprawa</span>
                  </span>
                  <span class="ap-badge" data-ap-badge="iss_expect_repair">—</span>
                </button>

                <button type="button" class="ap-filter" data-ap-section="issues" data-ap-filter="iss_return_required">
                  <span class="ap-itemleft">
                    <span class="ap-icobox ap-icobox--gray" aria-hidden="true">
                      <svg class="ap-ico"><use href="#ap-ico-return"></use></svg>
                    </span>
                    <span>Zwrot produktu wymagany</span>
                  </span>
                  <span class="ap-badge" data-ap-badge="iss_return_required">—</span>
                </button>
              </div>
            </details>

            <details class="ap-accordion">
              <summary>Podstawa</summary>
              <div class="ap-accordion__body">
                <button type="button" class="ap-filter" data-ap-section="issues" data-ap-filter="iss_right_warranty">
                  <span class="ap-itemleft">
                    <span class="ap-icobox ap-icobox--gray" aria-hidden="true">
                      <svg class="ap-ico"><use href="#ap-ico-shield"></use></svg>
                    </span>
                    <span>Gwarancja</span>
                  </span>
                  <span class="ap-badge" data-ap-badge="iss_right_warranty">—</span>
                </button>

                <button type="button" class="ap-filter" data-ap-section="issues" data-ap-filter="iss_right_complaint">
                  <span class="ap-itemleft">
                    <span class="ap-icobox ap-icobox--gray" aria-hidden="true">
                      <svg class="ap-ico"><use href="#ap-ico-doc"></use></svg>
                    </span>
                    <span>Reklamacja</span>
                  </span>
                  <span class="ap-badge" data-ap-badge="iss_right_complaint">—</span>
                </button>
              </div>
            </details>
          </div>
        </div>

      </div>
    </aside>

    <main class="ap-workspace">
      <div class="ap-workspace__header">
        <div>
          <div class="ap-h1" data-ap-header-title>Wiadomości</div>
          <div class="ap-subhead" data-ap-header-subtitle>Filtr: Wszystkie</div>
        </div>
        <div class="ap-actions">
          <button type="button" class="ap-btn" data-ap-action="sync">
            <svg class="ap-ico ap-ico--btn" aria-hidden="true"><use href="#ap-ico-sync"></use></svg>
            Synchronizuj
          </button>
          <button type="button" class="ap-btn ap-btn--ghost" data-ap-action="enrich" title="Test: segreguj już pobrane wiadomości (uzupełnij filtry)">
            <svg class="ap-ico ap-ico--btn" aria-hidden="true"><use href="#ap-ico-check"></use></svg>
            Segreguj
          </button>
          <button type="button" class="ap-btn ap-btn--primary" disabled>
            <svg class="ap-ico ap-ico--btn" aria-hidden="true"><use href="#ap-ico-plus"></use></svg>
            Nowa wiadomość
          </button>
        </div>
      </div>

      <div class="ap-content">
        <section class="ap-view ap-view--active" data-ap-view="messages">
          <div class="ap-card">
            <div class="ap-card__title">Wiadomości (MVP)</div>
            <div class="ap-card__desc">
              Etap 4: synchronizacja listy wiadomości do bazy + filtry + wyszukiwanie.
            </div>

            <div class="ap-split">
              <div class="ap-pane ap-pane--list">
                <div class="ap-pane__head">
                  <div class="ap-pane__title">Wiadomości</div>
                  <div class="ap-pane__meta">Łącznie: <span data-ap-list-total="threads">0</span></div>
                </div>

                <div class="ap-note" data-ap-note="threads" hidden></div>
                <div class="ap-list" data-ap-list="threads"></div>

          <div class="ap-list-more" data-ap-more="threads" hidden></div>
                <div class="ap-empty" data-ap-empty="threads" hidden>Brak wiadomości w tym filtrze.</div>
              </div>

              <div class="ap-pane ap-pane--preview">
                <div class="ap-pane__head">
                  <div class="ap-pane__title">Podgląd</div>
                </div>

                <!-- Empty state -->
                <div class="ap-preview" data-ap-preview-empty="messages">
                  <div class="ap-preview__title">Wybierz wiadomość z listy</div>
                  <div class="ap-preview__text">Po kliknięciu rozmowa otworzy się tutaj jako chat. Możesz też wysłać odpowiedź i oznaczyć rozmowę jako przeczytaną.</div>
                </div>

                <!-- Thread view (chat) -->
                <div class="ap-thread" data-ap-thread-view hidden>
                  <div class="ap-thread__head">
                    <div>
                      <button type="button" class="ap-backbtn" data-ap-action="backToThreads">
                        <svg class="ap-ico ap-ico--btn" aria-hidden="true"><use href="#ap-ico-return"></use></svg>
                        Wróć do listy
                      </button>
                      <div class="ap-thread__title" data-ap-thread-title>—</div>
                      <div class="ap-thread__meta" data-ap-thread-meta>—</div>
                    </div>
                    <div class="ap-thread__actions">
                      <button type="button" class="ap-btn ap-btn--ghost" data-ap-action="threadMarkRead">
                        <svg class="ap-ico ap-ico--btn" aria-hidden="true"><use href="#ap-ico-check"></use></svg>
                        Oznacz przeczytane
                      </button>
                    </div>
                  </div>

                  <div class="ap-chat" data-ap-chat="messages"></div>

                  <div class="ap-compose">
                    <textarea class="ap-compose__input" rows="3" placeholder="Napisz odpowiedź…" data-ap-compose-text></textarea>
                    <div class="ap-compose__actions">
                      <button type="button" class="ap-btn ap-btn--primary" data-ap-action="threadSend">
                        <svg class="ap-ico ap-ico--btn" aria-hidden="true"><use href="#ap-ico-reply"></use></svg>
                        Wyślij
                      </button>
                    </div>
                  </div>
                </div>

                <div class="ap-loading" data-ap-preview-loading="messages" hidden>
                  <div class="ap-loading__spinner" aria-hidden="true"></div>
                  <div class="ap-loading__text">Ładowanie rozmowy…</div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section class="ap-view" data-ap-view="issues">
          <div class="ap-card">
            <div class="ap-card__title">Dyskusje / reklamacje (MVP)</div>
            <div class="ap-card__desc">
              Etap 4: synchronizacja listy zgłoszeń do bazy + filtry + wyszukiwanie (endpoint /sale/issues, nagłówek beta).
            </div>

            <div class="ap-split">
              <div class="ap-pane ap-pane--list">
                <div class="ap-pane__head">
                  <div class="ap-pane__title">Zgłoszenia</div>
                  <div class="ap-pane__meta">Łącznie: <span data-ap-list-total="issues">0</span></div>
                </div>

                <div class="ap-note" data-ap-note="issues" hidden></div>
                <div class="ap-list" data-ap-list="issues"></div>

          <div class="ap-list-more" data-ap-more="issues" hidden></div>
                <div class="ap-empty" data-ap-empty="issues" hidden>Brak zgłoszeń w tym filtrze.</div>
              </div>

              <div class="ap-pane ap-pane--preview">
                <div class="ap-pane__head">
                  <div class="ap-pane__title">Chat</div>
                </div>

                <!-- Empty state -->
                <div class="ap-preview" data-ap-preview-empty="issues">
                  <div class="ap-preview__title">Wybierz zgłoszenie z listy</div>
                  <div class="ap-preview__text">Po kliknięciu zobaczysz historię chatu (endpoint /sale/issues/{ldelim}id{rdelim}/chat) i będziesz mógł odpisać.</div>
                </div>

                <!-- Issue view (chat) -->
                <div class="ap-thread" data-ap-issue-view hidden>
                  <div class="ap-thread__head">
                    <div>
                      <button type="button" class="ap-backbtn" data-ap-action="backToIssues">
                        <svg class="ap-ico ap-ico--btn" aria-hidden="true"><use href="#ap-ico-return"></use></svg>
                        Wróć do listy
                      </button>
                      <div class="ap-thread__title" data-ap-issue-title>—</div>
                      <div class="ap-thread__meta" data-ap-issue-meta>—</div>
                    </div>
                  </div>

                  <div class="ap-chat" data-ap-chat="issues"></div>

                  <div class="ap-compose">
                    <textarea class="ap-compose__input" rows="3" placeholder="Napisz odpowiedź do zgłoszenia…" data-ap-compose-issue-text></textarea>
                    <div class="ap-compose__actions">
                      <button type="button" class="ap-btn ap-btn--primary" data-ap-action="issueSend">
                        <svg class="ap-ico ap-ico--btn" aria-hidden="true"><use href="#ap-ico-reply"></use></svg>
                        Wyślij
                      </button>
                    </div>
                  </div>
                </div>

                <div class="ap-loading" data-ap-preview-loading="issues" hidden>
                  <div class="ap-loading__spinner" aria-hidden="true"></div>
                  <div class="ap-loading__text">Ładowanie zgłoszenia…</div>
                </div>
              </div>
            </div>
          </div>
        </section>
      </div>
    </main>
  </div>

  
  <!-- Ustawienia widoku (split vs pełny ekran) -->
  <div class="ap-modal" data-ap-modal="viewSettings" hidden>
    <div class="ap-modal__backdrop" data-ap-modal-close></div>
    <div class="ap-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="apViewSettingsTitle">
      <div class="ap-modal__header">
        <div class="ap-modal__title" id="apViewSettingsTitle">Ustawienia widoku</div>
        <button type="button" class="ap-iconbtn" data-ap-modal-close aria-label="Zamknij">
          <svg class="ap-ico"><use href="#ap-ico-x"></use></svg>
        </button>
      </div>
      <div class="ap-modal__body">
        <div class="ap-viewoptions">
          <button type="button" class="ap-viewopt" data-ap-viewopt="split">
            <div class="ap-viewopt__preview ap-viewopt__preview--split" aria-hidden="true">
              <span class="ap-viewopt__bar"></span>
              <span class="ap-viewopt__col"></span>
              <span class="ap-viewopt__col ap-viewopt__col--wide"></span>
            </div>
            <div class="ap-viewopt__meta">
              <div class="ap-viewopt__title">Widok kolumnowy</div>
              <div class="ap-viewopt__desc">Lista i rozmowa obok siebie.</div>
            </div>
          </button>
          <button type="button" class="ap-viewopt" data-ap-viewopt="single">
            <div class="ap-viewopt__preview ap-viewopt__preview--single" aria-hidden="true">
              <span class="ap-viewopt__bar"></span>
              <span class="ap-viewopt__col ap-viewopt__col--wide"></span>
            </div>
            <div class="ap-viewopt__meta">
              <div class="ap-viewopt__title">Widok pełny</div>
              <div class="ap-viewopt__desc">Klikasz wątek → rozmowa na całą szerokość + „Wróć”.</div>
            </div>
          </button>
        </div>
        <div class="ap-modal__hint">Ustawienie zapisuje się dla Twojego konta w tej przeglądarce.</div>
      </div>
    </div>
  </div>

  <footer class="ap-footer">
    <span>Allegro Pro — Korespondencja</span>
    <span class="ap-footer__muted">v{$ap_module_version|escape:'htmlall':'UTF-8'}</span>
  </footer>
</div>

<script>
  window.AP_CORR = {
    // Uwaga: używamy json_encode + nofilter, aby w JS nie wylądowało "&amp;" w query string.
    apiUrl: {$ap_api_url|json_encode nofilter},
    bridge: {
      eid: {$ap_bridge.eid|intval},
      ts: {$ap_bridge.ts|intval},
      ttl: {$ap_bridge.ttl|intval},
      sig: {$ap_bridge.sig|json_encode nofilter}
    }
  };
</script>
<script src="{$ap_module_path|escape:'htmlall':'UTF-8'}views/js/front/correspondenceapp.js?v={$ap_module_version|escape:'htmlall':'UTF-8'}&b={$ap_assets_b}"></script>
</body>
</html>
