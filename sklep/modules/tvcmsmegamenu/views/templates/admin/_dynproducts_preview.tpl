
{*
  Renders live preview on the configuration page.
  Expects variables:
  - products: array (may be empty; we still render a skeleton to avoid fatal errors)
  - tvdp: settings array (layout/show flags)
*}
<div id="tvdp-preview-wrap" class="tv-mega-preview">
  {include file="modules/tvcmsmegamenu/views/templates/hook/_dynproducts.tpl"}
</div>
