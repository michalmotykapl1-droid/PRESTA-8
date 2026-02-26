tvcmssearch — Diet categories FILTER in controller (no UI change)
=================================================================
• Filtruje $dietCategories w kontrolerze (tvcmssearch/tvcmssearch.php) — więc szablon i JS nie wymagają zmian.
• Działa na whitelist po ID z konfiguracji TVCMSSEARCH_DIET_CATEGORY_IDS.
• Gdy konfiguracja nie ustawiona: fallback po nazwach (Bio/Organic, Bez glutenu, Keto, Bez cukru, Wegańskie, Wegetariańskie,
  Bez laktozy, Niski IG).
• Import: added `use Configuration;`

SQL (prefix dxna_), Twoje główne kategorie na screenie:
168 (Bez glutenu), 182 (Wegetariańskie), 169 (Wegańskie), 170 (Bez laktozy),
171 (Bio / Organic), 172 (Keto & Low-Carb), 173 (Bez cukru), 261703 (Niski Indeks Glikemiczny)

INSERT INTO dxna_configuration (name, value, date_add, date_upd)
VALUES ('TVCMSSEARCH_DIET_CATEGORY_IDS','168,182,169,170,171,172,173,261703', NOW(), NOW())
ON DUPLICATE KEY UPDATE value=VALUES(value), date_upd=VALUES(date_upd);
