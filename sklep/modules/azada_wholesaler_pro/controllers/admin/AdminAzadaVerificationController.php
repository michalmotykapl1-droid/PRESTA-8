<?php

require_once(dirname(__FILE__) . '/../../classes/AzadaAnalysis.php');
require_once(dirname(__FILE__) . '/../../classes/services/AzadaVerificationEngine.php');
require_once(dirname(__FILE__) . '/../../classes/services/AzadaDbRepository.php');

class AdminAzadaVerificationController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
        
        if (class_exists('AzadaVerificationEngine')) {
            AzadaVerificationEngine::ensureDatabase();
        }
    }

    public function initContent()
    {
        if (Tools::getValue('action') == 'getAnalysisDetails') {
            $this->ajaxProcessGetAnalysisDetails();
            exit;
        }

        parent::initContent();

        $sql = "SELECT i.*, a.status as analysis_status, a.doc_number_order as analyzed_orders, a.total_diff_net, a.date_analyzed, a.id_analysis, w.name as wholesaler_name
                FROM "._DB_PREFIX_."azada_wholesaler_pro_invoice_files i
                LEFT JOIN "._DB_PREFIX_."azada_wholesaler_pro_analysis a ON (i.id_invoice = a.id_invoice_file)
                LEFT JOIN "._DB_PREFIX_."azada_wholesaler_pro_integration w ON (i.id_wholesaler = w.id_wholesaler)
                WHERE i.is_downloaded = 1
                ORDER BY i.doc_date DESC";
        
        $invoices = Db::getInstance()->executeS($sql);
        $autoQueue = [];
        $skipAutoRun = ((int)Tools::getValue('analyzed') === 1);

        if ($invoices) {
            foreach ($invoices as $key => &$inv) {
                $cleanInvAmount = str_replace([' ', 'PLN', '&nbsp;', "\xc2\xa0"], '', $inv['amount_netto']);
                $cleanInvAmount = (float)str_replace(',', '.', $cleanInvAmount);
                $isCorrection = ($cleanInvAmount < 0);
                
                $inv['is_correction'] = $isCorrection;

                if ($isCorrection) {
                    unset($invoices[$key]);
                    continue; 
                }

                // WYMUSZENIE PEŁNEGO PRZELICZENIA przy każdym wejściu w zakładkę,
                // ale bez zapętlenia po wewnętrznym reloadzie (analyzed=1).
                if (!$skipAutoRun) {
                    $autoQueue[] = (int)$inv['id_invoice'];
                }

                $inv['status_color'] = 'default';
                $inv['status_label'] = 'NIE SPRAWDZONO';
                $inv['can_expand'] = false;

                if ($inv['analysis_status'] === 'OK') {
                    $inv['status_color'] = 'success';
                    $inv['status_label'] = 'ZGODNA (OK)';
                } elseif ($inv['analysis_status'] === 'MISMATCH') {
                    $inv['status_color'] = 'danger';
                    $inv['status_label'] = 'BŁĘDY / RÓŻNICE';
                    $inv['can_expand'] = true;
                } elseif ($inv['analysis_status'] === 'NO_ORDER') {
                    $inv['status_color'] = 'warning';
                    $inv['status_label'] = 'BRAK ZAMÓWIENIA';
                }

                // Pokazujemy WYŁĄCZNIE dokumenty CSV, które realnie zostały użyte
                // do obliczenia zgodności (na podstawie zapisanych analyzed_orders).
                $candidates = [];

                if (!empty($inv['analyzed_orders'])) {
                    $docNumbersRaw = explode(',', $inv['analyzed_orders']);
                    $docNumbers = [];
                    foreach ($docNumbersRaw as $dn) {
                        $dn = trim($dn);
                        if ($dn !== '' && !in_array($dn, $docNumbers)) {
                            $docNumbers[] = $dn;
                        }
                    }

                    foreach ($docNumbers as $docNumber) {
                        $rowsDoc = Db::getInstance()->executeS("SELECT external_doc_number, amount_netto, status, doc_date
                            FROM "._DB_PREFIX_."azada_wholesaler_pro_order_files
                            WHERE id_wholesaler = ".(int)$inv['id_wholesaler']."
                            AND external_doc_number = '".pSQL($docNumber)."'
                            ORDER BY id_file DESC");

                        if (!empty($rowsDoc)) {
                            $candidates[] = $rowsDoc[0]; // najnowsza wersja dokumentu
                        }
                    }
                }

                $sumCandidates = 0.0;
                $uniqueCandidates = []; // unikalne i użyte w analizie
                $processedDocs = [];

                if ($candidates) {
                    foreach ($candidates as $cand) {
                        if (in_array($cand['external_doc_number'], $processedDocs)) continue;
                        $processedDocs[] = $cand['external_doc_number'];

                        $st = mb_strtolower($cand['status'], 'UTF-8');
                        $cand['badge_class'] = 'default';
                        $isCancelled = false;

                        if (strpos($st, 'anulowane') !== false || strpos($st, 'brak') !== false) {
                            $cand['badge_class'] = 'danger';
                            $isCancelled = true;
                        } elseif (strpos($st, 'zrealizowane') !== false) {
                            $cand['badge_class'] = 'success';
                        }

                        if (!$isCancelled) {
                            $cAmt = str_replace([' ', 'PLN', '&nbsp;', "\xc2\xa0"], '', $cand['amount_netto']);
                            $cAmt = str_replace(',', '.', $cAmt);
                            $sumCandidates += (float)$cAmt;
                        }

                        $uniqueCandidates[] = $cand;
                    }
                }

                $inv['candidate_orders'] = $uniqueCandidates;
                $inv['candidate_sum_formatted'] = number_format($sumCandidates, 2, ',', ' ') . ' PLN';
            }
        }

        $this->context->smarty->assign([
            'invoices' => $invoices,
            'auto_queue' => json_encode($autoQueue),
            'controller_url' => $this->context->link->getAdminLink('AdminAzadaVerification')
        ]);

        $tplPath = dirname(__FILE__) . '/../../views/templates/admin/verification.tpl';
        $content = $this->context->smarty->fetch($tplPath);

        $this->context->smarty->assign('content', $content);
    }

    public function ajaxProcessRunAnalysis()
    {
        $idInvoice = (int)Tools::getValue('id_invoice');
        if (!$idInvoice) die(json_encode(['status'=>'error', 'msg'=>'Brak ID Faktury']));

        $res = AzadaVerificationEngine::analyzeInvoice($idInvoice);
        die(json_encode($res));
    }

    public function ajaxProcessGetAnalysisDetails()
    {
        $idAnalysis = (int)Tools::getValue('id_analysis');
        $rows = AzadaVerificationEngine::getAnalysisDetails($idAnalysis);
        
        if (!$rows) die('<div class="alert alert-info">Brak zarejestrowanych różnic.</div>');

        // Grupowanie różnic po znaku wartości (na plus / na minus / zero)
        // oraz przypisanie koloru w kolumnie "Różnica".
        $plusRows = [];
        $minusRows = [];
        $zeroRows = [];

        foreach ($rows as $row) {
            $rawDiff = isset($row['diff_val']) ? (string)$row['diff_val'] : '';

            // Wyciągamy ostatnią liczbę z tekstu, np. "Kwota: +12.06 PLN".
            // Fallback: 0 gdy brak liczby.
            $amount = 0.0;
            if (preg_match('/([+-]?\d+(?:\.\d+)?)(?!.*[+-]?\d+(?:\.\d+)?)/', $rawDiff, $m)) {
                $amount = (float)$m[1];
            }

            $row['_diff_amount'] = $amount;
            if ($amount > 0) {
                $row['_diff_sign'] = 'plus';
                $plusRows[] = $row;
            } elseif ($amount < 0) {
                $row['_diff_sign'] = 'minus';
                $minusRows[] = $row;
            } else {
                $row['_diff_sign'] = 'zero';
                $zeroRows[] = $row;
            }
        }

        // W ramach grup sortujemy po wartości bezwzględnej malejąco,
        // żeby największe różnice były na górze.
        $sortByAbsDesc = function ($a, $b) {
            $aa = abs((float)$a['_diff_amount']);
            $bb = abs((float)$b['_diff_amount']);
            if ($aa == $bb) return 0;
            return ($aa < $bb) ? 1 : -1;
        };
        usort($plusRows, $sortByAbsDesc);
        usort($minusRows, $sortByAbsDesc);
        usort($zeroRows, $sortByAbsDesc);

        $html = '<table class="table table-bordered table-condensed" style="background:#fff; font-size:12px;">
            <thead>
                <tr class="active" style="color:#c0392b;">
                    <th width="10%">Faktura</th>
                    <th width="15%">Źródło (Zam.)</th>
                    <th width="10%">SKU</th>
                    <th>Produkt (EAN / Nazwa)</th>
                    <th>Typ Różnicy</th>
                    <th class="text-right">Wartość na FV</th>
                    <th class="text-right">Wartość w ZAM</th>
                    <th class="text-right">Różnica</th>
                </tr>
            </thead>
            <tbody>';

        $renderRows = function ($list, $sectionTitle, $sectionColor) {
            $htmlPart = '';

            if (!empty($list)) {
                $htmlPart .= '<tr style="background:' . $sectionColor . '; font-weight:bold;">
                    <td colspan="8" style="text-transform:uppercase; letter-spacing:0.3px;">' . $sectionTitle . '</td>
                </tr>';
            }

            foreach ($list as $r) {
                $badge = '<span class="label label-danger">BŁĄD</span>';
                $rowStyle = '';
                $iconAlert = '';

                if ($r['error_type'] == 'PRICE') $badge = '<span class="label label-warning">CENA</span>';
                if ($r['error_type'] == 'QTY') $badge = '<span class="label label-primary">ILOŚĆ</span>';
                if ($r['error_type'] == 'MISSING_IN_ORDER') $badge = '<span class="label label-danger" style="background:#d9534f;">BRAK W ZAMÓWIENIU</span>';
                
                if ($r['error_type'] == 'FOUND_IN_CANCELLED') {
                    $badge = '<span class="label label-danger" style="background:#000; border:1px solid red;">Z ANULOWANYCH</span>';
                    $rowStyle = 'background-color:#ffe6e6;';
                    $iconAlert = '<i class="icon-warning text-danger" style="font-size:14px; margin-right:5px;"></i> ';
                }

                if ($r['error_type'] == 'MISSING_IN_INVOICE') {
                    $badge = '<span class="label label-info" style="background:#999;">BRAK NA FV</span>';
                    $rowStyle = 'background-color:#f9f9f9; color:#777;';
                }

                $invNum = isset($r['doc_number_invoice']) ? $r['doc_number_invoice'] : '-';
                $srcOrd = isset($r['source_orders']) ? $r['source_orders'] : '-';
                $sku = isset($r['wholesaler_sku']) ? $r['wholesaler_sku'] : '-';

                $diffColor = '#c0392b';
                if (isset($r['_diff_sign']) && $r['_diff_sign'] === 'minus') {
                    $diffColor = '#1e8e3e';
                } elseif (isset($r['_diff_sign']) && $r['_diff_sign'] === 'zero') {
                    $diffColor = '#777';
                }

                $htmlPart .= '<tr style="'.$rowStyle.'">
                    <td style="font-weight:bold;">'.$invNum.'</td>
                    <td style="font-size:10px; color:#555;">'.$srcOrd.'</td>
                    <td style="font-family:monospace; font-weight:bold;">'.$sku.'</td>
                    <td>'.$iconAlert.'<strong>'.$r['product_name'].'</strong><br><small class="text-muted">'.$r['product_identifier'].'</small></td>
                    <td>'.$badge.'</td>
                    <td class="text-right">'.$r['val_invoice'].'</td>
                    <td class="text-right">'.$r['val_order'].'</td>
                    <td class="text-right" style="font-weight:bold; color:'.$diffColor.';">'.$r['diff_val'].'</td>
                </tr>';
            }

            return $htmlPart;
        };

        $html .= $renderRows($plusRows, 'RÓŻNICE NA PLUS (+)', '#ffeaea');
        $html .= $renderRows($minusRows, 'RÓŻNICE NA MINUS (-)', '#eaf7ec');
        $html .= $renderRows($zeroRows, 'RÓŻNICE ZERO (0)', '#f3f3f3');

        if (empty($plusRows) && empty($minusRows) && empty($zeroRows)) {
            $html .= '<tr><td colspan="8" class="text-center text-muted">Brak danych do wyświetlenia.</td></tr>';
        }

        $html .= '</tbody></table>';
        die($html);
    }
}
