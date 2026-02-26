tvcmssearch — Diet Feature Filter Fix
=====================================

What changed
------------
• Only "core" dietary feature groups are shown in the live-search filter grid.
• The list is configurable via Configuration key: TVCMSSEARCH_DIET_FEATURE_IDS.
  Default whitelist: 13,14,15,16,17,18,20,22
  (EXCLUDES 19 = "Rodzaj produktu").

How to update the whitelist (optional)
--------------------------------------
In your database (ps_configuration), set:
  name = TVCMSSEARCH_DIET_FEATURE_IDS
  value = comma-separated feature GROUP IDs, e.g.  "13,14,15,16,17,18,20,22"

Or run SQL:
  INSERT INTO ps_configuration (name, value, date_add, date_upd)
  VALUES ('TVCMSSEARCH_DIET_FEATURE_IDS','13,14,15,16,17,18,20,22', NOW(), NOW())
  ON DUPLICATE KEY UPDATE value=VALUES(value), date_upd=VALUES(date_upd);

Files touched
-------------
• modules/tvcmssearch/src/Services/DietFeatureService.php
  - added: `use Configuration;`
  - replaced hardcoded `$dietary_feature_ids` with config-driven version and safe default.

Deploy
------
1) In PrestaShop, uninstall *tvcmssearch* (do NOT delete data), or just upload & overwrite files via FTP.
2) Upload/Install the provided ZIP.
3) Clear cache (both Symfony & Smarty).
4) Test live search — the "Filtry dodatkowe" grid should now show only the whitelisted features,
   without "Rodzaj produktu".
