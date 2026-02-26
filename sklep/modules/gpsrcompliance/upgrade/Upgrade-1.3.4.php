<?php
if (!defined('_PS_VERSION_')) { exit; }
function upgrade_module_1_3_4($module)
{
    foreach (['displayAdminProductsExtra','displayAdminProductsMainStepLeftColumnMiddle','displayAdminProductsMainStepLeftColumnBottom'] as $h) {
        if (Hook::getIdByName($h)) { $module->registerHook($h); }
    }
    if (method_exists($module, 'installTabs')) { $module->installTabs(); }
    return true;
}
