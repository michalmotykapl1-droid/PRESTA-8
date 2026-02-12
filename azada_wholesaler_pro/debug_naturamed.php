<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(dirname(__FILE__) . '/../../config/config.inc.php');
$classFile = dirname(__FILE__) . '/classes/integrations/AzadaNaturaMedB2B.php';
require_once($classFile);

$login = 'zamowienia@bigbio.pl'; 
$password = 'Big123456';
$api = new AzadaNaturaMedB2B();

echo "<h2>1. Logowanie...</h2>";
$loginCheck = $api->checkLogin($login, $password);
echo "Status: " . ($loginCheck ? 'OK' : 'BŁĄD') . "<hr>";

if ($loginCheck) {
    echo "<h2>2. Przechwycenie surowego HTML z AJAX</h2>";
    
    // Używamy refleksji, aby pobrać surowy HTML z prywatnej metody
    $reflection = new ReflectionClass('AzadaNaturaMedB2B');
    $method = $reflection->getMethod('request');
    $method->setAccessible(true);
    
    $dateFrom = date('Y-m-d', strtotime("-30 days"));
    $dateTo = date('Y-m-d');
    $ajaxUrl = "https://naturamed.com.pl/dokumenty/PobierzListe/Faktura/$dateFrom/$dateTo/False/False";
    
    $response = $method->invoke($api, $ajaxUrl, [], ['X-Requested-With: XMLHttpRequest']);
    $json = json_decode($response, true);
    $html = isset($json['Html']) ? $json['Html'] : $response;

    echo "<h3>Analiza pierwszego wiersza tabeli:</h3>";
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="UTF-8"><html><body>' . $html . '</body></html>');
    $xpath = new DOMXPath($dom);
    $rows = $xpath->query('//tr[td]');

    if ($rows->length > 0) {
        $firstRow = $rows->item(0);
        echo "Liczba znalezionych kolumn: " . $firstRow->getElementsByTagName('td')->length . "<br>";
        
        // Zrzucamy cały HTML wiersza do podejrzenia atrybutów
        echo "<h4>Surowy kod HTML wiersza (szukamy ID):</h4>";
        echo "<textarea style='width:100%; height:300px; background:#222; color:#0f0;'>" . htmlspecialchars($dom->saveHTML($firstRow)) . "</textarea>";
        
        echo "<h4>Wszystkie znalezione atrybuty 'data-' w tym wierszu:</h4>";
        $allElements = $xpath->query('.//*', $firstRow);
        foreach ($allElements as $el) {
            foreach ($el->attributes as $attr) {
                if (stripos($attr->nodeName, 'data-') !== false || stripos($attr->nodeName, 'id') !== false) {
                    echo "Element <b>&lt;" . $el->nodeName . "&gt;</b> ma atrybut <b>" . $attr->nodeName . "</b> = <code>" . $attr->nodeValue . "</code><br>";
                }
            }
        }
    } else {
        echo "<b style='color:red;'>Nie znaleziono żadnych wierszy w odpowiedzi AJAX!</b>";
        echo "Pełna odpowiedź serwera:<pre>" . htmlspecialchars($response) . "</pre>";
    }
}