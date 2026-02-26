<div id="toast" class="toast"><i id="toast-icon" class="fa-solid fa-check-circle"></i><span id="toast-msg">Komunikat</span></div>

<div id="error-modal" class="error-overlay">
    <div class="modal-box">
        <div class="modal-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <div class="modal-title">NIEPOPRAWNY PRODUKT</div>
        <div class="modal-msg">Zeskanowany kod nie pasuje do zamówienia.</div>
        <div class="scanned-code">EAN: <span id="err-ean">---</span></div>
    </div>
</div>

<div id="success-modal" class="success-overlay">
    <div class="modal-box">
        <div class="modal-icon"><i class="fa-solid fa-circle-check animate-bounce"></i></div>
        <div class="modal-title">ZAMÓWIENIE SKOMPLETOWANE!</div>
        <div class="modal-msg" id="success-msg">Ładuję kolejne zamówienie...</div>
    </div>
    
    <a href="{$manager_url}" id="btn-finish" class="btn-home" style="display:none;">
        <i class="fa-solid fa-list-check"></i> Wróć do Managera
    </a>
</div>