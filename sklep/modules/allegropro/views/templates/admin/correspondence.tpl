{* ETAP 1: tylko UI/szkielet - bez integracji z API i bez bazy *}

<style>
.allegropro-wrap { margin-top: 8px; }
.allegropro-hero {
  border: 1px solid #dbe8ff;
  border-radius: 12px;
  padding: 20px;
  background: linear-gradient(135deg, #f7fbff 0%, #eef5ff 100%);
  margin-bottom: 16px;
}
.allegropro-hero h3 { margin: 0 0 8px 0; font-size: 24px; font-weight: 700; }
.allegropro-hero p { margin: 0; color: #51606f; }
.allegropro-card {
  border: 1px solid #e5eaf1;
  border-radius: 12px;
  background: #fff;
  overflow: hidden;
  margin-top: 12px;
}
.allegropro-card-head {
  padding: 14px 16px;
  border-bottom: 1px solid #edf1f7;
  font-weight: 700;
  background: #fafcff;
}
.allegropro-card-body { padding: 16px; }
.allegropro-muted { color: #6e7b88; }
.allegropro-pill {
  display:inline-block;
  padding: 2px 8px;
  border-radius: 999px;
  background:#eef5ff;
  border: 1px solid #dbe8ff;
  font-size: 12px;
  font-weight: 700;
}
.allegropro-kv { display:flex; gap:10px; flex-wrap:wrap; margin-top:10px; }
.allegropro-kv > div { background:#f8fafc; border:1px solid #edf1f7; padding:10px 12px; border-radius:10px; }
</style>

<div class="allegropro-wrap">
  <div class="allegropro-hero">
    <h3>Allegro Pro • Korespondencja</h3>
    <p>
      Jedna zakładka na całą komunikację: <span class="allegropro-pill">Wiadomości</span> oraz <span class="allegropro-pill">Dyskusje / reklamacje</span>.
      W kolejnych etapach dodamy synchronizację do bazy danych + listy + podgląd + odpowiedzi.
    </p>

    <div class="allegropro-kv">
      <div>
        <div style="font-weight:700;margin-bottom:2px;">ETAP 1</div>
        <div class="allegropro-muted">Zakładka + UI/szkielet</div>
      </div>
      <div>
        <div style="font-weight:700;margin-bottom:2px;">ETAP 2</div>
        <div class="allegropro-muted">Tabele + synchronizacja (threads / issues)</div>
      </div>
      <div>
        <div style="font-weight:700;margin-bottom:2px;">ETAP 3</div>
        <div class="allegropro-muted">Widok rozmowy + wysyłka odpowiedzi + oznaczanie przeczytane</div>
      </div>
    </div>
  </div>

  <ul class="nav nav-tabs" role="tablist" style="margin-bottom:0;">
    <li class="active" role="presentation">
      <a href="#ap_msg" aria-controls="ap_msg" role="tab" data-toggle="tab">
        <i class="icon icon-envelope"></i> Wiadomości (Message Center)
      </a>
    </li>
    <li role="presentation">
      <a href="#ap_issues" aria-controls="ap_issues" role="tab" data-toggle="tab">
        <i class="icon icon-comments"></i> Dyskusje / reklamacje (Issues)
      </a>
    </li>
  </ul>

  <div class="tab-content">
    <div role="tabpanel" class="tab-pane active" id="ap_msg">
      <div class="allegropro-card">
        <div class="allegropro-card-head">Wiadomości (MVP)</div>
        <div class="allegropro-card-body">
          <p class="allegropro-muted" style="margin-top:0;">
            W kolejnym etapie robimy:
          </p>
          <ul>
            <li>tabele: <code>allegropro_msg_thread</code>, <code>allegropro_msg_message</code></li>
            <li>sync z Allegro: <code>/messaging/threads</code>, <code>/messaging/threads/{literal}{threadId}{/literal}/messages</code>, <code>/messaging/threads/{literal}{threadId}{/literal}/read</code></li>
            <li>powiązanie z zamówieniem po <code>relatesTo.order.id</code> / <code>checkoutFormId</code></li>
          </ul>
          <p class="allegropro-muted" style="margin-bottom:0;">
            Etap 1 tylko potwierdza, że nowa zakładka działa i pojawia się bez reinstalacji.
          </p>
        </div>
      </div>
    </div>

    <div role="tabpanel" class="tab-pane" id="ap_issues">
      <div class="allegropro-card">
        <div class="allegropro-card-head">Dyskusje / reklamacje (MVP)</div>
        <div class="allegropro-card-body">
          <p class="allegropro-muted" style="margin-top:0;">
            W kolejnym etapie robimy:
          </p>
          <ul>
            <li>tabele: <code>allegropro_issue</code>, <code>allegropro_issue_chat</code></li>
            <li>sync z Allegro: <code>/sale/issues</code> + chat <code>/sale/issues/{literal}{issueId}{/literal}/chat</code></li>
            <li>wysyłka odpowiedzi: <code>/sale/issues/{literal}{issueId}{/literal}/message</code></li>
          </ul>
          <div class="alert alert-info" style="margin-bottom:0;">
            Uwaga: Allegro usunęło zasoby <code>/sale/disputes</code> (od 07.01.2026). Używamy tylko <code>/sale/issues</code> z nagłówkiem <code>Accept: application/vnd.allegro.beta.v1+json</code>.
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
