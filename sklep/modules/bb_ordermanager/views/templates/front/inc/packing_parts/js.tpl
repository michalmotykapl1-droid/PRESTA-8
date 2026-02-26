<script>
    const ajaxUrl = "{$ajax_url nofilter}";
    const baseLink = "{$base_link nofilter}";
    const currentOrderId = {$current_order_id};
    const nextOrderId = {$next_order_id};
    const BBOM_CSRF = "{$csrf_token|escape:'javascript'}";
    const scannerInput = document.getElementById('scanner-input');
    const AudioContext = window.AudioContext || window.webkitAudioContext;
    const audioCtx = new AudioContext();

    document.addEventListener('click', (e) => {
        if (audioCtx.state === 'suspended') audioCtx.resume();
        if (!e.target.closest('button') && !e.target.closest('a') && !e.target.closest('input') && !e.target.closest('.status-btn')) scannerInput.focus();
    });
    scannerInput.focus();

    scannerInput.addEventListener('keydown', (e) => {
        if (document.getElementById('error-modal').classList.contains('show') || document.getElementById('success-modal').classList.contains('show')) {
            e.preventDefault(); return;
        }
        if (e.key === 'Enter') {
            e.preventDefault();
            const ean = scannerInput.value.trim();
            scannerInput.value = '';
            if(ean) handleScan(ean);
        }
    });

    function updateSidebarStatus() {
        const rows = document.querySelectorAll('.product-row');
        let total = 0;
        let packed = 0;

        rows.forEach(row => {
            total += parseInt(row.dataset.needed);
            packed += parseInt(row.dataset.packed);
        });

        const sidebarItem = document.getElementById('sidebar-order-' + currentOrderId);
        if (sidebarItem) {
            sidebarItem.querySelector('.s-packed').innerText = packed;
            sidebarItem.querySelector('.s-total').innerText = total;
            sidebarItem.classList.remove('status-new', 'status-partial', 'status-done');
            if (packed >= total && total > 0) {
                sidebarItem.classList.add('status-done');
                sidebarItem.dataset.status = 2;
            } else if (packed > 0) {
                sidebarItem.classList.add('status-partial');
                sidebarItem.dataset.status = 1;
            } else {
                sidebarItem.classList.add('status-new');
                sidebarItem.dataset.status = 0;
            }
        }
    }

    function playSound(type) {
        if (!audioCtx) return;
        const o = audioCtx.createOscillator();
        const g = audioCtx.createGain();
        o.connect(g);
        g.connect(audioCtx.destination);
        const t = audioCtx.currentTime;
        if (type === 'success') {
            o.type = 'sine';
            o.frequency.setValueAtTime(800, t); o.frequency.exponentialRampToValueAtTime(1200, t + 0.1);
            g.gain.setValueAtTime(0.2, t); g.gain.exponentialRampToValueAtTime(0.01, t + 0.1);
            o.start(t); o.stop(t + 0.1);
        } else if (type === 'error') {
            o.type = 'sawtooth';
            o.frequency.setValueAtTime(150, t); o.frequency.linearRampToValueAtTime(80, t + 0.5);
            g.gain.setValueAtTime(0.5, t); g.gain.linearRampToValueAtTime(0.01, t + 0.5);
            o.start(t); o.stop(t + 0.5);
        } else if (type === 'warning') {
            o.type = 'square';
            o.frequency.setValueAtTime(400, t); g.gain.setValueAtTime(0.1, t); o.start(t); o.stop(t + 0.15);
        } else if (type === 'order_complete') {
            const notes = [523.25, 659.25, 783.99];
            notes.forEach((freq, i) => {
                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                osc.type = 'sine'; osc.frequency.setValueAtTime(freq, t + (i * 0.1));
                gain.gain.setValueAtTime(0.1, t + (i * 0.1)); gain.gain.exponentialRampToValueAtTime(0.01, t + (i * 0.1) + 0.6);
                osc.start(t + (i * 0.1)); osc.stop(t + (i * 0.1) + 0.6);
            });
        }
    }

    function showToast(msg, type) {
        if (msg.indexOf('SKOMPLETOWANE') !== -1) return;
        const t = document.getElementById('toast');
        const i = document.getElementById('toast-icon');
        document.getElementById('toast-msg').innerText = msg;
        t.className = 'toast show ' + type;
        if (type === 'success') i.className = 'fa-solid fa-circle-check';
        else i.className = 'fa-solid fa-triangle-exclamation';
        setTimeout(() => { t.classList.remove('show'); }, 2000);
    }

    function showErrorModal(ean) {
        const modal = document.getElementById('error-modal');
        document.getElementById('err-ean').innerText = ean;
        modal.classList.add('show');
        playSound('error');
        setTimeout(() => { modal.classList.remove('show'); scannerInput.value = ''; scannerInput.focus(); }, 1500);
    }

    function showSuccessModal() {
        const modal = document.getElementById('success-modal');
        const title = document.querySelector('#success-modal .modal-title');
        const msg = document.getElementById('success-msg');
        const btn = document.getElementById('btn-finish');
        const icon = document.querySelector('#success-modal .modal-icon i');
        title.innerText = "ZAMÓWIENIE SKOMPLETOWANE!";
        msg.innerText = "Świetna robota!";
        btn.style.display = 'none';
        icon.className = 'fa-solid fa-circle-check animate-bounce';

        modal.classList.add('show');
        playSound('order_complete');
        if (nextOrderId > 0) {
            msg.innerText = "Ładuję kolejne zamówienie...";
            setTimeout(() => { window.location.href = baseLink + '&id_order=' + nextOrderId; }, 2000);
        } else {
            setTimeout(() => {
                title.innerText = "KONIEC KOLEJKI";
                msg.innerText = "Wszystkie zamówienia z listy zostały spakowane.";
                btn.style.display = 'inline-flex';
                icon.className = 'fa-solid fa-flag-checkered animate-pulse'; 
            }, 2000);
        }
    }

    function handleScan(ean) {
        const rows = document.querySelectorAll('.product-row');
        let matchedRow = null;
        for (let row of rows) {
            if (row.dataset.ean === ean) {
                const needed = parseInt(row.dataset.needed);
                const packed = parseInt(row.dataset.packed);
                if (packed < needed) { matchedRow = row; break; }
                if (!matchedRow) matchedRow = row;
            }
        }
        if (matchedRow) { updateQuantity(matchedRow, 1); } 
        else { showErrorModal(ean); }
    }

    function updateQuantity(row, change) {
        let current = parseInt(row.dataset.packed);
        let needed = parseInt(row.dataset.needed);
        let newQty = current + change;
        if (newQty < 0) newQty = 0;
        
        row.dataset.packed = newQty;
        row.querySelector('.qty-packed').innerText = newQty;
        
        const statusBtn = row.querySelector('.status-btn');
        const qtySpan = row.querySelector('.qty-packed');
        
        row.classList.remove('packed-done', 'packed-partial');

        if (newQty >= needed) {
            row.classList.add('packed-done');
            statusBtn.classList.add('checked');
            qtySpan.classList.add('done');
            row.style.order = '1'; 
            if (change > 0) { showToast('Spakowano produkt', 'success'); playSound('success'); }
        } else if (newQty > 0) {
            row.classList.add('packed-partial');
            statusBtn.classList.remove('checked');
            qtySpan.classList.remove('done');
            row.style.order = '0';
        } else {
            statusBtn.classList.remove('checked');
            qtySpan.classList.remove('done');
            row.style.order = '0';
        }

        saveProgress(row.dataset.idDetail, row.dataset.id, row.dataset.attr, newQty);
        updateSidebarStatus();
        checkAll();
    }

    function markFullyPacked(detailId) {
        const row = document.getElementById('row-' + detailId);
        if(!row) return;
        const needed = parseInt(row.dataset.needed);
        const current = parseInt(row.dataset.packed);
        if (current < needed) {
            const diff = needed - current;
            updateQuantity(row, diff);
        }
        scannerInput.focus();
    }

    function manualUpdate(detailId, change) {
        const row = document.getElementById('row-' + detailId);
        if(row) updateQuantity(row, change);
        scannerInput.focus();
    }

    function saveProgress(detailId, pid, aid, qty) {
        fetch(ajaxUrl + '&action=update_progress&id_order_detail=' + detailId + '&product_id=' + pid + '&product_attribute_id=' + aid + '&qty=' + qty, {
            headers: {
                'X-BBOM-CSRF': BBOM_CSRF
            }
        })
        .then(r => r.json())
        .then(data => {
            if(data.invoice && data.invoice.success) {
                console.log('Faktura wystawiona:', data.invoice.message);
            }
        });
    }

    function checkAll() {
        const all = document.querySelectorAll('.product-row');
        let done = 0;
        all.forEach(r => { if(r.classList.contains('packed-done')) done++; });
        if(done === all.length && all.length > 0) {
            showSuccessModal();
        }
    }

    window.addEventListener('load', () => {
        const rows = document.querySelectorAll('.product-row');
        rows.forEach(row => {
            if (row.classList.contains('packed-done')) {
                row.style.order = '1';
            } else {
                row.style.order = '0';
            }
        });
        updateSidebarStatus();
    });
</script>