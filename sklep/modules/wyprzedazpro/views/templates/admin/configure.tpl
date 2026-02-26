{* Plik główny: configure.tpl - Zarządca widoku *}

{assign var=sort value=$sort|default:''}
{assign var=way value=$way|default:''}
{assign var=sort_not_found value=$sort_not_found|default:''}
{assign var=way_not_found value=$way_not_found|default:''}
{assign var=sort_duplicates value=$sort_duplicates|default:''}
{assign var=way_duplicates value=$way_duplicates|default:''}

{* 0. NOWOŚĆ: Tabela produktów w KOSZU (Na samej górze zgodnie z życzeniem) *}
{include file='./parts/list_bin.tpl'}

{* 1. Ustawienia Rabatów *}
{include file='./parts/settings.tpl'}

{* 2. Operacje (Import / Sync) *}
{include file='./parts/operations.tpl'}

{* 3. Historia Importów *}
{include file='./parts/history.tpl'}

{* 4. Alerty (np. przeterminowane) *}
{include file='./parts/alerts.tpl'}

{* 5. Tabela Główna (Lista Produktów) *}
{include file='./parts/list_main.tpl'}

{* 6. Tabela Duplikatów *}
{include file='./parts/list_duplicates.tpl'}

{* 7. Tabela Brakujących EAN *}
{include file='./parts/list_missing.tpl'}