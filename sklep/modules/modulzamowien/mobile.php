<?php
/**
 * MOBILNY SKANER - WERSJA SQL 1.0
 * - Pobiera dane z tabeli `modulzamowien_picking_session`
 * - Zapisuje postęp do bazy SQL
 * - Synchronizuje realny stan magazynowy (PickingManager)
 */

require_once(dirname(__FILE__).'/../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../init.php');

// 1. Sprawdzenie uprawnień
$cookie = new Cookie('psAdmin');
if (!$cookie->id_employee) {
    // PrestaShop może mieć zmienioną nazwę katalogu admin (np. admin123),
    // dlatego budujemy poprawny link dynamicznie.
    $adminDir = basename(_PS_ADMIN_DIR_);
    $loginUrl = Tools::getShopDomainSsl(true) . __PS_BASE_URI__ . $adminDir . '/index.php';

    die('<div style="font-family:sans-serif; text-align:center; padding:50px; color:red;">
            <h1>BRAK DOSTĘPU</h1>
            <p>Zaloguj się do panelu admina na telefonie.</p>
            <br>
            <a href="' . htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') . '" class="btn">Przejdź do logowania</a>
         </div>');
}

// 2. Ładowanie klas
require_once(dirname(__FILE__).'/classes/Wms/PickingManager.php');
require_once(dirname(__FILE__).'/classes/Repositories/PickingSessionRepository.php');
require_once(dirname(__FILE__).'/classes/Managers/OrderSessionManager.php');

$repo = new PickingSessionRepository();
$sessionManager = new OrderSessionManager();

// --- OBSŁUGA AJAX (Zapisywanie skanowania) ---
if (Tools::getValue('ajax') == 1) {
    $ean = Tools::getValue('ean'); // Tu przychodzi SKU lub EAN
    $newQty = (int)Tools::getValue('qty'); 
    $action = Tools::getValue('action');
    
    // Pobieramy aktualny stan z BAZY dla tego produktu
    $oldQty = $repo->getPickedQty($ean);
    
    if ($action == 'confirm_pick') {
        $wms = new PickingManager();
        
        $diff = $newQty - $oldQty;
        
        // 1. Aktualizacja realnego stanu WMS (tylko różnica)
        // Jeśli w Zakładce 2 wykonano Smart Swap, w bazie może być ustawione ALT SKU.
        // Wtedy stan zdejmujemy z ALT SKU, ale postęp zapisujemy do rekordu bazowego (po oryginalnym SKU).
        $targetSku = $ean;
        $altSku = $repo->getAlternativeSku($ean);
        if (!empty($altSku)) {
            $targetSku = $altSku;
        }

        if ($diff > 0) {
            $wms->confirmPick($targetSku, $diff);
        } elseif ($diff < 0) {
            // Obsługa cofania (jeśli kiedykolwiek dodasz możliwość zmniejszania ilości na mobile)
            $wms->revertPick($targetSku, abs($diff));
        }

        // 1b. Synchronizacja list zakupów (Zakładka 3)
        // Tak samo jak w panelu: gdy zbieramy z WMS, zmniejszamy ilość do kupienia.
        // Delta jest ujemna przy zbieraniu (bo $diff > 0 => zmniejszamy zakup).
        if ($diff != 0) {
            $sessionManager->updateOrderSessionQty($ean, -1 * $diff);
        }
        
        // 2. Aktualizacja sesji w BAZIE DANYCH
        // Sprawdzamy czy zebrano całość. Musimy pobrać info o celu (qty_to_pick)
        $allItems = $repo->getAllItems();
        $isCollected = false;
        
        foreach ($allItems as $r) {
            if ($r['sku'] == $ean || $r['ean'] == $ean) {
                if ($newQty >= (int)$r['qty_stock']) { // qty_stock to alias na qty_to_pick w repo
                    $isCollected = true;
                }
                break;
            }
        }
        
        // Zapis do tabeli sesji
        $repo->updatePickedQty($ean, $newQty, $isCollected);
        
        die(json_encode(['success' => true]));
    }
    die(json_encode(['error' => true]));
}

// 4. POBIERANIE DANYCH DO WIDOKU (Z BAZY)
$pickingData = $repo->getAllItems();
$pickingDataJson = json_encode($pickingData);

?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <title>WMS MOBILE (SQL)</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background: #f0f2f5; margin: 0; padding: 10px; padding-bottom: 80px; color:#333; }
        
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .btn-close { background: #ddd; border: none; padding: 8px 15px; border-radius: 20px; font-weight: bold; text-decoration: none; color: #333; font-size:12px;}
        
        .global-success { background: #28a745; color: white; text-align: center; padding: 10px; border-radius: 5px; margin-bottom: 10px; font-weight: bold; display: none; }
        
        .card { background: #fff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); padding: 15px; margin-bottom: 15px; text-align: center; position: relative; overflow: hidden; min-height: 400px; display: flex; flex-direction: column; justify-content: space-between; }
        
        .location-badge { background: #007aff; color: white; font-size: 32px; font-weight: 800; padding: 10px 20px; border-radius: 8px; display: inline-block; margin-bottom: 10px; box-shadow: 0 2px 5px rgba(0,122,255,0.3); }
        
        .product-img { width: 100%; height: 160px; object-fit: contain; margin-bottom: 10px; }
        .product-name { font-size: 16px; font-weight: 600; line-height: 1.3; margin-bottom: 5px; min-height: 42px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        
        .ean-box { background: #eee; padding: 5px 10px; border-radius: 4px; font-family: monospace; font-size: 14px; color: #555; display: inline-block; margin-bottom: 10px; }
        
        .qty-container { display: flex; align-items: center; justify-content: center; margin: 10px 0; background: #f1f1f1; padding: 10px; border-radius: 10px; border: 2px solid #eee; }
        .qty-container.done { background: #d4edda; border-color: #c3e6cb; }
        
        .qty-current { font-size: 42px; font-weight: 800; color: #e91e63; }
        .qty-container.done .qty-current { color: #28a745; }
        
        .qty-sep { font-size: 20px; color: #999; margin: 0 10px; font-weight: 300; }
        .qty-total { font-size: 20px; font-weight: 600; color: #555; }
        
        .nav-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .btn-nav { background: #007aff; color: white; border: none; width: 60px; height: 60px; border-radius: 50%; font-size: 24px; box-shadow: 0 3px 6px rgba(0,0,0,0.2); cursor: pointer; display: flex; align-items: center; justify-content: center; }
        .btn-nav:active { transform: scale(0.95); background: #005ecb; }
        .btn-nav.disabled { background: #ccc; cursor: not-allowed; opacity: 0.5; }
        .nav-info { font-size: 14px; color: #888; font-weight: bold; }

        .controls { padding: 0 5px; }
        
        .scan-input { width: 100%; padding: 12px; font-size: 16px; border: 2px solid #007aff; border-radius: 8px; text-align: center; margin-bottom: 10px; box-sizing: border-box; background: #eef5ff; color: #333; }
        .scan-input:focus { outline: none; border-color: #0056b3; background: #fff; }
        
        .btn-manual { background: #28a745; color: white; border: none; padding: 15px; width: 100%; font-size: 18px; font-weight: bold; border-radius: 8px; cursor: pointer; box-shadow: 0 4px 6px rgba(40, 167, 69, 0.3); display: flex; align-items: center; justify-content: center; gap: 10px; }
        .btn-manual:active { background: #218838; transform: translateY(2px); }
        
        .stamp-done { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-15deg); border: 5px solid #28a745; color: #28a745; font-size: 40px; font-weight: 900; padding: 10px 20px; border-radius: 10px; opacity: 0; pointer-events: none; transition: opacity 0.3s; background: rgba(255,255,255,0.8); z-index: 10; }
        .card.is-finished .stamp-done { opacity: 1; }

        .status-footer { position: fixed; bottom: 0; left: 0; right: 0; background: #333; color: white; padding: 10px; text-align: center; font-size: 12px; font-weight: bold; z-index: 100; }
        
        .flash-success { animation: flashGreen 0.3s; }
        .flash-error { animation: flashRed 0.5s; }
        @keyframes flashGreen { 0% { background: #fff; } 50% { background: #d4edda; } 100% { background: #fff; } }
        @keyframes flashRed { 0% { background: #fff; } 50% { background: #f8d7da; } 100% { background: #fff; } }
    </style>
</head>
<body>

    <audio id="audio-pop" src="https://actions.google.com/sounds/v1/science_fiction/pop.ogg"></audio>
    <audio id="audio-success" src="https://actions.google.com/sounds/v1/rewards/hero_simple_celebration_01.ogg"></audio>
    <audio id="audio-error" src="https://actions.google.com/sounds/v1/alarms/dosimeter_alarm_one_pulse.ogg"></audio>

    <div class="header">
        <div style="font-weight:bold; font-size:16px;"><i class="fa fa-cubes"></i> WMS</div>
        <a href="#" onclick="window.close()" class="btn-close">ZAMKNIJ</a>
    </div>
    
    <div id="global-msg" class="global-success">
        <i class="fa fa-check-circle"></i> WSZYSTKO ZEBRANE!
    </div>

    <div class="nav-bar">
        <button class="btn-nav" id="btn-prev" onclick="prevItem()"><i class="fa fa-chevron-left"></i></button>
        <div class="nav-info">POZYCJA <span id="idx-current">1</span> / <span id="idx-total">10</span></div>
        <button class="btn-nav" id="btn-next" onclick="nextItem()"><i class="fa fa-chevron-right"></i></button>
    </div>

    <div id="app-view">
        <div style="padding:50px; text-align:center; color:#888;">
            <i class="fa fa-spinner fa-spin" style="font-size:40px;"></i><br><br>Ładowanie...
        </div>
    </div>
    
    <div class="controls">
        <input type="text" id="scanner" class="scan-input" placeholder="Zeskanuj kod tutaj..." autocomplete="off" inputmode="none">
        
        <button type="button" class="btn-manual" onclick="manualConfirm(event)">
            <i class="fa fa-plus-circle"></i> ZBIERZ 1 SZT.
        </button>
    </div>

    <div class="status-footer">
        POZOSTAŁO DO ZEBRANIA: <span id="count-left" style="font-size:16px; color:#ffeb3b;">0</span>
    </div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    var allItems = <?php echo $pickingDataJson ? $pickingDataJson : '[]'; ?>;
    var activeList = []; 
    var currentIndex = 0;

    var sndPop = document.getElementById('audio-pop');
    var sndSuccess = document.getElementById('audio-success');
    var sndError = document.getElementById('audio-error');

    function playSound(type) {
        try {
            if(type === 'pop') { sndPop.currentTime=0; sndPop.play(); }
            if(type === 'success') { sndSuccess.currentTime=0; sndSuccess.play(); }
            if(type === 'error') { sndError.currentTime=0; sndError.play(); }
        } catch(e) { console.log('Audio error:', e); }
    }

    function init() {
        // Filtrujemy tylko pozycje, które mają coś do zebrania
        activeList = allItems.filter(function(i) {
            return (parseInt(i.qty_stock) > 0);
        });

        var firstTodo = activeList.findIndex(function(i) {
            return (i.is_collected != true && i.is_collected != '1');
        });
        
        if (firstTodo !== -1) {
            currentIndex = firstTodo;
        } else {
            currentIndex = 0;
        }
        
        render();
        setTimeout(function() { $('#scanner').focus(); }, 500);
    }

    function render() {
        var remaining = 0;
        activeList.forEach(function(i) {
            if (i.is_collected != true && i.is_collected != '1') remaining++;
        });
        $('#count-left').text(remaining);
        
        if (remaining === 0 && activeList.length > 0) {
            $('#global-msg').slideDown();
        } else {
            $('#global-msg').slideUp();
        }

        if (activeList.length === 0) {
            $('#app-view').html('<div style="padding:40px; text-align:center;">Brak towarów do zebrania w bazie.</div>');
            return;
        }

        if (currentIndex < 0) currentIndex = 0;
        if (currentIndex >= activeList.length) currentIndex = activeList.length - 1;

        $('#idx-current').text(currentIndex + 1);
        $('#idx-total').text(activeList.length);
        
        $('#btn-prev').toggleClass('disabled', currentIndex === 0);
        $('#btn-next').toggleClass('disabled', currentIndex === activeList.length - 1);

        var item = activeList[currentIndex];
        
        if (!item.user_picked_qty) item.user_picked_qty = 0;
        var picked = parseInt(item.user_picked_qty);
        var total = parseInt(item.qty_stock);
        var isDone = (picked >= total);

        var imgUrl = item.image_id ? '../../img/p/' + item.image_id.split('').join('/') + '/' + item.image_id + '.jpg' : 'https://via.placeholder.com/300x200?text=BRAK';

        // Lokalizacja (Regał / Półka)
        var locDisplay = (item.regal ? item.regal : '?') + ' / ' + (item.polka ? item.polka : '?');
        if (item.location) locDisplay = item.location;

        var html = `
            <div class="card ` + (isDone ? 'is-finished' : '') + `" id="main-card">
                <div class="stamp-done">GOTOWE</div>
                
                <div class="location-badge">
                    ` + locDisplay + `
                </div>
                
                <img src="` + imgUrl + `" class="product-img">
                
                <div class="product-name">` + item.name + `</div>
                
                <div class="qty-container ` + (isDone ? 'done' : '') + `">
                    <span class="qty-current">` + picked + `</span>
                    <span class="qty-sep">z</span>
                    <span class="qty-total">` + total + ` szt.</span>
                </div>
                
                <div class="ean-box">` + item.ean + `</div>
                <div style="font-size:11px; color:#999;">SKU: ` + item.sku + `</div>
            </div>
        `;
        
        $('#app-view').html(html);
    }

    function prevItem() {
        if (currentIndex > 0) {
            currentIndex--;
            render();
        }
    }

    function nextItem() {
        if (currentIndex < activeList.length - 1) {
            currentIndex++;
            render();
        }
    }
    
    function autoAdvance() {
        for (var i = currentIndex + 1; i < activeList.length; i++) {
            var item = activeList[i];
            if (item.is_collected != true && item.is_collected != '1') {
                currentIndex = i;
                render();
                return;
            }
        }
        for (var i = 0; i < currentIndex; i++) {
            var item = activeList[i];
            if (item.is_collected != true && item.is_collected != '1') {
                currentIndex = i;
                render();
                return;
            }
        }
        render();
    }

    function processOneItem(item) {
        var current = parseInt(item.user_picked_qty) || 0;
        var max = parseInt(item.qty_stock);
        
        if (current >= max) {
            // Już zebrane na maksa
            return; 
        }

        var newQty = current + 1;
        item.user_picked_qty = newQty; 

        $('#main-card').addClass('flash-success');
        setTimeout(function(){ $('#main-card').removeClass('flash-success'); }, 300);

        if (newQty >= max) {
            // KONIEC POZYCJI (Wszystko zebrane)
            item.is_collected = true;
            playSound('success'); // Tu gra Hero Success
            sendUpdate(item, newQty, true); 
        } else {
            // CZĘŚCIOWE ZEBRANIE (np. 1 z 5)
            playSound('pop'); // Tu gra krótkie Pop
            sendUpdate(item, newQty, false);
        }
    }

    function sendUpdate(item, qty, isFinished) {
        $.ajax({
            url: 'mobile.php',
            type: 'POST',
            data: {
                ajax: 1,
                action: 'confirm_pick',
                ean: item.sku, // Ważne: wysyłamy SKU jako identyfikator (dla A_MAG)
                qty: qty
            },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    if (isFinished) {
                        render(); 
                        setTimeout(function() {
                            autoAdvance(); 
                        }, 1000); 
                    } else {
                        render();
                    }
                } else {
                    alert('Błąd zapisu!');
                }
            },
            error: function() {
                alert('Błąd połączenia!');
            }
        });
    }

    function manualConfirm(e) {
        if(e) e.stopPropagation();
        var item = activeList[currentIndex];
        if (!item) return;
        
        if(navigator.vibrate) navigator.vibrate(50); // Krótka wibracja przy kliku ręcznym
        processOneItem(item);
    }

    // Obsługa skanera
    $('#scanner').on('keypress', function(e) {
        if(e.which == 13) {
            var code = $(this).val().trim();
            if (!code) return;
            
            var currentItem = activeList[currentIndex];
            // Sprawdzamy EAN i SKU
            if (code === currentItem.ean || code === currentItem.sku) {
                processOneItem(currentItem);
            } else {
                // BŁĄD SKANOWANIA
                playSound('error');
                
                // --- WIBRACJA (Potrójna, mocna) ---
                if(navigator.vibrate) {
                    navigator.vibrate([200, 100, 200, 100, 200]);
                }
                
                $('#main-card').addClass('flash-error');
                setTimeout(function(){ $('#main-card').removeClass('flash-error'); }, 500);
            }
            $(this).val('');
        }
    });
    
    // Kliknięcie w tło oddaje focus skanerowi
    $(document).on('click', function(e) {
        if (!$(e.target).closest('button, a, input').length) {
            $('#scanner').focus();
        }
    });

    $(document).ready(function() {
        init();
    });
</script>
</body>
</html>