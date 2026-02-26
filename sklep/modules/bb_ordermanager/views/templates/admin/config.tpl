<div class="panel">
    <div class="panel-heading">
        <i class="icon-cogs"></i> Konfiguracja MANAGER PRO
    </div>
    
    <div class="alert alert-info">
        <p><strong><i class="icon-info-circle"></i> Informacja:</strong> Te ustawienia są używane jako wartości domyślne podczas generowania etykiet (Allegro, InPost) bezpośrednio z modułu Manager PRO.</p>
    </div>

    <form method="post" action="{$action_url|escape:'htmlall':'UTF-8'}" class="form-horizontal" id="bbom-config-form">
        <input type="hidden" name="submitManagerProConfig" value="1">

        <h3 style="border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 20px;">
            <i class="icon-print"></i> Format Etykiet
        </h3>
        
        <div class="form-group">
            <label class="control-label col-lg-3">Format pliku</label>
            <div class="col-lg-3">
                <select name="BB_MANAGER_LABEL_FORMAT" class="form-control">
                    <option value="PDF" {if $bb_label_format == 'PDF'}selected{/if}>PDF (Standard)</option>
                    <option value="ZPL" {if $bb_label_format == 'ZPL'}selected{/if}>ZPL (Drukarki termiczne)</option>
                    <option value="EPL" {if $bb_label_format == 'EPL'}selected{/if}>EPL (Zebra)</option>
                </select>
                <p class="help-block">Wybierz format pliku generowanego przez API przewoźników.</p>
            </div>
            
            <label class="control-label col-lg-2">Rozmiar papieru</label>
            <div class="col-lg-3">
                <select name="BB_MANAGER_LABEL_SIZE" class="form-control">
                    <option value="A4" {if $bb_label_size == 'A4'}selected{/if}>A4 (Zwykła drukarka)</option>
                    <option value="A6" {if $bb_label_size == 'A6'}selected{/if}>A6 (Etykiety 10x15cm)</option>
                </select>
                <p class="help-block">Dla etykiet InPost/Allegro.</p>
            </div>
        </div>

        <h3 style="border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 20px; margin-top: 30px;">
            <i class="icon-box"></i> Domyślne parametry paczki
        </h3>
        
        <p class="help-block" style="margin-bottom: 20px;">Wartości używane przy automatycznym tworzeniu przesyłki (gdy nie edytujesz ich ręcznie).</p>

        <div class="form-group">
            <div class="col-lg-4">
                <label class="control-label" style="text-align: left; width: 100%; margin-bottom: 5px;">Typ paczki</label>
                <select name="BB_MANAGER_PKG_TYPE" class="form-control">
                    <option value="PACKAGE" {if $bb_pkg_type == 'PACKAGE' || !$bb_pkg_type}selected{/if}>Paczka standardowa</option>
                    <option value="DOPE" {if $bb_pkg_type == 'DOPE'}selected{/if}>Koperta (DOPE)</option>
                    <option value="PALLET" {if $bb_pkg_type == 'PALLET'}selected{/if}>Paleta</option>
                    <option value="NON_STANDARD" {if $bb_pkg_type == 'NON_STANDARD'}selected{/if}>Niestandardowa</option>
                </select>
            </div>

            <div class="col-lg-2">
                <label class="control-label" style="text-align: left; width: 100%; margin-bottom: 5px;">Waga domyślna (kg)</label>
                <div class="input-group">
                    <input type="text" name="BB_MANAGER_DEF_WEIGHT" value="{$bb_def_weight|default:'1.0'}" class="form-control">
                    <span class="input-group-addon">kg</span>
                </div>
            </div>

            <div class="col-lg-6">
                <label class="control-label" style="text-align: left; width: 100%; margin-bottom: 5px;">Zawartość (opis)</label>
                <input type="text" name="BB_MANAGER_CONTENT" value="{$bb_content|default:'Towary handlowe'}" class="form-control" placeholder="np. Kosmetyki naturalne">
            </div>
        </div>

        <div class="form-group">
            <div class="col-lg-4">
                <label class="control-label" style="text-align: left; width: 100%; margin-bottom: 5px;">Długość (cm)</label>
                <input type="number" name="BB_MANAGER_PKG_LEN" value="{$bb_pkg_len|intval}" class="form-control">
            </div>
            
            <div class="col-lg-4">
                <label class="control-label" style="text-align: left; width: 100%; margin-bottom: 5px;">Szerokość (cm)</label>
                <input type="number" name="BB_MANAGER_PKG_WID" value="{$bb_pkg_wid|intval}" class="form-control">
            </div>
            
            <div class="col-lg-4">
                <label class="control-label" style="text-align: left; width: 100%; margin-bottom: 5px;">Wysokość (cm)</label>
                <input type="number" name="BB_MANAGER_PKG_HEI" value="{$bb_pkg_hei|intval}" class="form-control">
            </div>
        </div>

        <div class="alert alert-info" style="margin-top: 20px;">
            <i class="icon-user"></i> Dane nadawcy są pobierane automatycznie z globalnych ustawień sklepu (Kontakt > Sklepy).
        </div>

        
        <h3 style="border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 20px; margin-top: 30px;">
            <i class="icon-sitemap"></i> Foldery / statusy (Kanban)
        </h3>

        <div class="alert alert-info">
            <i class="icon-info-circle"></i>
            Folder w <strong>BIGBIO Manager</strong> jest mapowany na <strong>status zamówienia</strong> w PrestaShop.
            Jeśli dany status nie istnieje, moduł spróbuje go <strong>utworzyć automatycznie</strong> (wg nazwy folderu).
            <br>
            <strong>Uwaga:</strong> Usunięcie folderu usuwa również powiązany status zamówienia (OrderState) w PrestaShop (jeśli to możliwe).
        </div>

        {if isset($bb_folder_groups) && $bb_folder_groups}

            <div id="bbom-delete-inputs"></div>

            <div class="bbom-kanban-config" id="bbom-kanban-config">
                <div class="bbom-groups" id="bbom-groups">
                    {foreach from=$bb_folder_groups item=g}
                        {assign var=gkey value=$g.key|escape:'htmlall':'UTF-8'}

                        <div class="panel panel-default bbom-group" data-group="{$gkey}">
                            <div class="panel-heading bbom-group-heading">
                                <span class="bbom-handle bbom-group-handle" draggable="true" title="Przeciągnij etap">
                                    <i class="icon-reorder"></i>
                                </span>
                                <span class="bbom-group-title" data-role="title">{$g.title|escape:'htmlall':'UTF-8'}</span>
                                <button type="button" class="btn btn-link btn-xs bbom-edit-group" title="Zmień nazwę etapu">
                                    <i class="icon-pencil"></i>
                                </button>
                                <span class="bbom-group-hint">Przeciągnij etap, aby zmienić kolejność</span>

                                <input type="hidden" name="BB_OM_GROUP_TITLE[{$gkey}]" value="{$g.title|escape:'htmlall':'UTF-8'}">
                                <input type="hidden" name="BB_OM_GROUP_POS[{$gkey}]" value="{$g.position|intval}">
                            </div>

                            <div class="panel-body bbom-folder-list" data-group="{$gkey}">
                                {foreach from=$g.items item=f}
                                    {assign var=fid value=$f.id|escape:'htmlall':'UTF-8'}
                                    <div class="bbom-folder-row {if !$f.active}is-disabled{/if}" data-folder="{$fid}" data-group="{$gkey}" data-stateid="{if isset($bb_folder_state_map_by_id[$f.id])}{$bb_folder_state_map_by_id[$f.id]|intval}{else}0{/if}">
                                        <div class="bbom-handle bbom-folder-handle" draggable="true" title="Przeciągnij folder">
                                            <i class="icon-reorder"></i>
                                        </div>

                                        <div class="bbom-folder-main">
                                            <span class="bbom-dot" style="background: {$f.color_hex|escape:'htmlall':'UTF-8'}"></span>
                                            <span class="bbom-folder-label" data-role="label">{$f.label|escape:'htmlall':'UTF-8'}</span>
                                            <button type="button" class="btn btn-link btn-xs bbom-edit-folder" title="Zmień nazwę folderu">
                                                <i class="icon-pencil"></i>
                                            </button>
                                            {if $f.is_custom}
                                                <button type="button" class="btn btn-link btn-xs bbom-delete-folder" title="Usuń folder i status">
                                                    <i class="icon-trash"></i>
                                                </button>
                                            {/if}
                                            {if $f.is_custom}
                                                <span class="bbom-tag">Własny</span>
                                            {/if}

                                            <div class="bbom-color-box">
                                                <input type="color" class="bbom-color-picker" value="{$f.color_hex|escape:'htmlall':'UTF-8'}" title="Wybierz kolor">
                                                <input type="text" class="form-control input-sm bbom-color-hex" name="BB_OM_FOLDER_COLOR_HEX[{$fid}]" value="{$f.color_hex|escape:'htmlall':'UTF-8'}" title="HEX">
                                                <button type="button" class="btn btn-default btn-sm bbom-copy-hex" title="Kopiuj HEX">
                                                    <i class="icon-copy"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="bbom-folder-controls">
                                            <label class="bbom-switch" title="Widoczność w Managerze">
                                                <input type="checkbox" class="bbom-active" name="BB_OM_FOLDER_ACTIVE[{$fid}]" value="1" {if $f.active}checked{/if}>
                                                <span class="bbom-switch-slider"></span>
                                                <span class="bbom-switch-text">{if $f.active}Widoczny{else}Ukryty{/if}</span>
                                            </label>

                                            <select name="BB_OM_FOLDER_STATE[{$fid}]" class="form-control" title="Status zamówienia">
                                                <option value="0">(Automatycznie wg nazwy)</option>
                                                {foreach from=$bb_order_states item=os}
                                                    <option value="{$os.id_order_state|intval}"
                                                        {if isset($bb_folder_state_map_by_id[$f.id]) && $bb_folder_state_map_by_id[$f.id]==$os.id_order_state}selected{/if}>
                                                        {$os.name|escape:'htmlall':'UTF-8'} (#{$os.id_order_state|intval})
                                                    </option>
                                                {/foreach}
                                            </select>
                                        </div>

                                        <input type="hidden" name="BB_OM_FOLDER_LABEL[{$fid}]" value="{$f.label|escape:'htmlall':'UTF-8'}">
                                        <input type="hidden" name="BB_OM_FOLDER_GROUP[{$fid}]" value="{$gkey}">
                                        <input type="hidden" name="BB_OM_FOLDER_POS[{$fid}]" value="{$f.position|intval}">
                                        <input type="hidden" name="BB_OM_FOLDER_IS_ERROR[{$fid}]" value="{if $f.is_error}1{else}0{/if}">
                                    </div>
                                {/foreach}

                                <div class="bbom-drop-hint">Przeciągnij tutaj folder, aby przenieść do tego etapu</div>
                            </div>
                        </div>
                    {/foreach}
                </div>

                <div class="row bbom-add-row">
                    <div class="col-lg-6">
                        <div class="well bbom-add-box">
                            <h4 style="margin-top:0;"><i class="icon-plus"></i> Dodaj nowy folder</h4>
                            <div class="bbom-add-controls">
                                <input type="text" id="bbom-new-folder-label" class="form-control" placeholder="Nazwa folderu">
                                <select id="bbom-new-folder-group" class="form-control">
                                    {foreach from=$bb_folder_groups item=gg}
                                        <option value="{$gg.key|escape:'htmlall':'UTF-8'}">{$gg.title|escape:'htmlall':'UTF-8'}</option>
                                    {/foreach}
                                </select>

                                <input type="color" id="bbom-new-folder-color" value="#2563eb" title="Kolor">
                                <input type="text" id="bbom-new-folder-hex" class="form-control" value="#2563eb" title="HEX">
                                <button type="button" class="btn btn-default" id="bbom-add-folder-btn"><i class="icon-plus"></i> Dodaj</button>
                            </div>
                            <p class="help-block" style="margin:8px 0 0 0;">Po dodaniu ustaw mapowanie statusu i zapisz konfigurację.</p>
                            <div class="alert alert-danger" id="bbom-add-folder-error" style="display:none;"></div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="well bbom-add-box">
                            <h4 style="margin-top:0;"><i class="icon-plus"></i> Dodaj nowy etap / zakładkę</h4>
                            <div class="bbom-add-controls">
                                <input type="text" id="bbom-new-group-title" class="form-control" placeholder="Nazwa etapu (np. 4. ETAP: ...)" />
                                <button type="button" class="btn btn-default" id="bbom-add-group-btn"><i class="icon-plus"></i> Dodaj etap</button>
                            </div>
                            <p class="help-block" style="margin:8px 0 0 0;">Etap pojawi się od razu w Managerze po zapisaniu.</p>
                            <div class="alert alert-danger" id="bbom-add-group-error" style="display:none;"></div>
                        </div>
                    </div>
                </div>
            </div>

            {* Modal potwierdzenia usunięcia folderu *}
            <div class="modal fade" id="bbomDeleteModal" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title"><i class="icon-trash"></i> Usuń folder</h4>
                        </div>
                        <div class="modal-body">
                            <p>Czy na pewno chcesz usunąć folder <strong id="bbomDelFolderName"></strong>?</p>
                            <p class="text-muted" style="margin-bottom:0;">Jeśli to możliwe, moduł usunie również powiązany status zamówienia w PrestaShop.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Anuluj</button>
                            <button type="button" class="btn btn-danger" id="bbomConfirmDelete"><i class="icon-trash"></i> Usuń</button>
                        </div>
                    </div>
                </div>
            </div>

            {literal}
            <script>
                (function ($) {
                    function uid(prefix) {
                        const r = Math.random().toString(16).slice(2);
                        return (prefix || 'c_') + Date.now().toString(16) + r.slice(0, 6);
                    }

                    function normalizeHex(v) {
                        v = (v || '').trim();
                        if (!v) return '';
                        if (v[0] !== '#') v = '#' + v;
                        if (v.length === 4) {
                            v = '#' + v[1] + v[1] + v[2] + v[2] + v[3] + v[3];
                        }
                        return v;
                    }

                    function updatePositions() {
                        $('.bbom-group').each(function () {
                            const gKey = $(this).data('group');
                            const $pos = $('input[name="BB_OM_GROUP_POS[' + gKey + ']"]');
                            // kolejność grup wg DOM
                            $pos.val($(this).index() + 1);
                        });

                        $('.bbom-folder-list').each(function () {
                            const gKey = $(this).data('group');
                            $(this).find('.bbom-folder-row').each(function (idx) {
                                const fid = $(this).data('folder');
                                $('input[name="BB_OM_FOLDER_GROUP[' + fid + ']"]').val(gKey);
                                $('input[name="BB_OM_FOLDER_POS[' + fid + ']"]').val(idx + 1);
                            });
                        });
                    }

                    // --- Kolor: picker + HEX + kopiowanie ---
                    $(document).on('change', '.bbom-color-picker', function () {
                        const $row = $(this).closest('.bbom-folder-row');
                        const fid = $row.data('folder');
                        const v = normalizeHex($(this).val());
                        $row.find('.bbom-dot').css('background', v);
                        const $hex = $row.find('input[name="BB_OM_FOLDER_COLOR_HEX[' + fid + ']"]');
                        $hex.val(v);
                    });
                    $(document).on('input', '.bbom-color-hex', function () {
                        const $row = $(this).closest('.bbom-folder-row');
                        const v = normalizeHex($(this).val());
                        $row.find('.bbom-dot').css('background', v || '#64748b');
                        $row.find('.bbom-color-picker').val(v || '#64748b');
                    });
                    $(document).on('click', '.bbom-copy-hex', async function () {
                        const $row = $(this).closest('.bbom-folder-row');
                        const fid = $row.data('folder');
                        const v = $row.find('input[name="BB_OM_FOLDER_COLOR_HEX[' + fid + ']"]').val();
                        try {
                            await navigator.clipboard.writeText(v);
                            $(this).addClass('btn-success');
                            setTimeout(() => $(this).removeClass('btn-success'), 600);
                        } catch (e) {
                            // fallback
                            const $tmp = $('<input>').val(v).appendTo('body').select();
                            document.execCommand('copy');
                            $tmp.remove();
                        }
                    });

                    // --- Widoczność ---
                    $(document).on('change', '.bbom-active', function () {
                        const $row = $(this).closest('.bbom-folder-row');
                        const on = $(this).is(':checked');
                        $row.toggleClass('is-disabled', !on);
                        $row.find('.bbom-switch-text').text(on ? 'Widoczny' : 'Ukryty');
                    });

                    // --- Edit folder name ---
                    function startInlineEdit($el, onSave) {
                        const old = $el.text().trim();
                        const $input = $('<input type="text" class="form-control input-sm bbom-inline-input">').val(old);
                        $el.hide().after($input);
                        $input.focus().select();
                        function finish(save) {
                            const val = $input.val().trim();
                            $input.remove();
                            $el.show();
                            if (save && val) {
                                $el.text(val);
                                onSave(val);
                            }
                        }
                        $input.on('keydown', function (e) {
                            if (e.key === 'Enter') finish(true);
                            if (e.key === 'Escape') finish(false);
                        });
                        $input.on('blur', function () { finish(true); });
                    }

                    $(document).on('click', '.bbom-edit-folder', function () {
                        const $row = $(this).closest('.bbom-folder-row');
                        const fid = $row.data('folder');
                        const $label = $row.find('[data-role="label"]');
                        startInlineEdit($label, function (val) {
                            $('input[name="BB_OM_FOLDER_LABEL[' + fid + ']"]').val(val);
                        });
                    });

                    // --- Edit group title ---
                    $(document).on('click', '.bbom-edit-group', function () {
                        const $group = $(this).closest('.bbom-group');
                        const gKey = $group.data('group');
                        const $title = $group.find('[data-role="title"]');
                        startInlineEdit($title, function (val) {
                            $('input[name="BB_OM_GROUP_TITLE[' + gKey + ']"]').val(val);
                            // uaktualnij select do dodawania folderu
                            $('#bbom-new-folder-group option[value="' + gKey + '"]').text(val);
                        });
                    });

                    // --- Dodaj folder ---
                    function showError($box, msg) {
                        $box.text(msg).show();
                        setTimeout(() => $box.fadeOut(200), 2500);
                    }

                    $('#bbom-new-folder-color').on('change', function () {
                        $('#bbom-new-folder-hex').val(normalizeHex($(this).val()));
                    });
                    $('#bbom-new-folder-hex').on('input', function () {
                        const v = normalizeHex($(this).val()) || '#64748b';
                        $('#bbom-new-folder-color').val(v);
                    });

                    $('#bbom-add-folder-btn').on('click', function () {
                        const label = $('#bbom-new-folder-label').val().trim();
                        const group = $('#bbom-new-folder-group').val();
                        const hex = normalizeHex($('#bbom-new-folder-hex').val()) || '#64748b';
                        if (!label) {
                            return showError($('#bbom-add-folder-error'), 'Podaj nazwę folderu.');
                        }
                        // prosty check duplikatu
                        let dup = false;
                        $('.bbom-folder-label[data-role="label"]').each(function () {
                            if ($(this).text().trim().toLowerCase() === label.toLowerCase()) dup = true;
                        });
                        if (dup) {
                            return showError($('#bbom-add-folder-error'), 'Folder o takiej nazwie już istnieje.');
                        }

                        const fid = uid('c_');
                        const $list = $('.bbom-folder-list[data-group="' + group + '"]');
                        if (!$list.length) {
                            return showError($('#bbom-add-folder-error'), 'Nie znaleziono etapu.');
                        }
                        const rowHtml = `
                        <div class="bbom-folder-row" data-folder="${fid}" data-group="${group}">
                          <div class="bbom-handle bbom-folder-handle" draggable="true" title="Przeciągnij folder"><i class="icon-reorder"></i></div>
                          <div class="bbom-folder-main">
                            <span class="bbom-dot" style="background: ${hex}"></span>
                            <span class="bbom-folder-label" data-role="label"></span>
                            <button type="button" class="btn btn-link btn-xs bbom-edit-folder" title="Zmień nazwę folderu"><i class="icon-pencil"></i></button>
                            <button type="button" class="btn btn-link btn-xs bbom-delete-folder" title="Usuń folder i status"><i class="icon-trash"></i></button>
                            <span class="bbom-tag">Własny</span>
                            <div class="bbom-color-box">
                              <input type="color" class="bbom-color-picker" value="${hex}" title="Wybierz kolor">
                              <input type="text" class="form-control input-sm bbom-color-hex" name="BB_OM_FOLDER_COLOR_HEX[${fid}]" value="${hex}" title="HEX">
                              <button type="button" class="btn btn-default btn-sm bbom-copy-hex" title="Kopiuj HEX"><i class="icon-copy"></i></button>
                            </div>
                          </div>
                          <div class="bbom-folder-controls">
                            <label class="bbom-switch" title="Widoczność w Managerze">
                              <input type="checkbox" class="bbom-active" name="BB_OM_FOLDER_ACTIVE[${fid}]" value="1" checked>
                              <span class="bbom-switch-slider"></span>
                              <span class="bbom-switch-text">Widoczny</span>
                            </label>
                            <select name="BB_OM_FOLDER_STATE[${fid}]" class="form-control" title="Status zamówienia">${$('#bbom-template-states').html()}</select>
                          </div>
                          <input type="hidden" name="BB_OM_FOLDER_LABEL[${fid}]" value="">
                          <input type="hidden" name="BB_OM_FOLDER_GROUP[${fid}]" value="${group}">
                          <input type="hidden" name="BB_OM_FOLDER_POS[${fid}]" value="999">
                          <input type="hidden" name="BB_OM_FOLDER_IS_ERROR[${fid}]" value="0">
                        </div>`;

                        const $row = $(rowHtml);
                        $row.find('[data-role="label"]').text(label);
                        $row.find('input[name="BB_OM_FOLDER_LABEL[' + fid + ']"]').val(label);
                        $list.append($row);
                        $('#bbom-new-folder-label').val('');
                        updatePositions();
                    });

                    // --- Dodaj etap ---
                    $('#bbom-add-group-btn').on('click', function () {
                        const title = $('#bbom-new-group-title').val().trim();
                        if (!title) {
                            return showError($('#bbom-add-group-error'), 'Podaj nazwę etapu.');
                        }
                        const gKey = uid('g_');
                        const $groups = $('#bbom-groups');
                        const groupHtml = `
                        <div class="panel panel-default bbom-group" data-group="${gKey}">
                          <div class="panel-heading bbom-group-heading">
                            <span class="bbom-handle bbom-group-handle" draggable="true" title="Przeciągnij etap"><i class="icon-reorder"></i></span>
                            <span class="bbom-group-title" data-role="title"></span>
                            <button type="button" class="btn btn-link btn-xs bbom-edit-group" title="Zmień nazwę etapu"><i class="icon-pencil"></i></button>
                            <span class="bbom-group-hint">Przeciągnij etap, aby zmienić kolejność</span>
                            <input type="hidden" name="BB_OM_GROUP_TITLE[${gKey}]" value="">
                            <input type="hidden" name="BB_OM_GROUP_POS[${gKey}]" value="999">
                          </div>
                          <div class="panel-body bbom-folder-list" data-group="${gKey}">
                            <div class="bbom-drop-hint">Przeciągnij tutaj folder, aby przenieść do tego etapu</div>
                          </div>
                        </div>`;
                        const $g = $(groupHtml);
                        $g.find('[data-role="title"]').text(title);
                        $g.find('input[name="BB_OM_GROUP_TITLE[' + gKey + ']"]').val(title);
                        $groups.append($g);

                        // dopisz do selecta
                        $('#bbom-new-folder-group').append($('<option>').val(gKey).text(title));
                        $('#bbom-new-group-title').val('');
                        updatePositions();
                    });

                    // --- Usuwanie folderu (modal) ---
                    let delCtx = { fid: null, label: null, stateId: 0 };

                    // Aktualizuj data-stateid gdy zmienimy mapowanie statusu w select
                    $(document).on('change', 'select[name^="BB_OM_FOLDER_STATE"]', function () {
                        const $row = $(this).closest('.bbom-folder-row');
                        const v = parseInt($(this).val() || '0', 10);
                        if (v > 0) {
                            $row.data('stateid', v);
                        }
                    });
                    $(document).on('click', '.bbom-delete-folder', function () {
                        const $row = $(this).closest('.bbom-folder-row');
                        const fid = $row.data('folder');
                        const label = $row.find('[data-role="label"]').text().trim();
                        let stateId = parseInt($row.find('select[name="BB_OM_FOLDER_STATE[' + fid + ']"]').val() || '0', 10);
                        if (!stateId) {
                            stateId = parseInt($row.data('stateid') || '0', 10);
                        }
                        delCtx = { fid: fid, label: label, stateId: stateId };
                        $('#bbomDelFolderName').text(label);
                        $('#bbomDeleteModal').modal('show');
                    });
                    $('#bbomConfirmDelete').on('click', function () {
                        if (!delCtx.fid) return;
                        const fid = delCtx.fid;
                        // zaznacz do usunięcia
                        $('#bbom-delete-inputs').append(
                            $('<input>', { type: 'hidden', name: 'BB_OM_FOLDER_DELETE[]', value: fid })
                        );
                        $('#bbom-delete-inputs').append(
                            $('<input>', { type: 'hidden', name: 'BB_OM_FOLDER_DELETE_LABEL[' + fid + ']', value: delCtx.label })
                        );
                        $('#bbom-delete-inputs').append(
                            $('<input>', { type: 'hidden', name: 'BB_OM_FOLDER_DELETE_STATE[' + fid + ']', value: String(delCtx.stateId || 0) })
                        );
                        // usuń z DOM (żeby nie zapisać folderu)
                        $('.bbom-folder-row[data-folder="' + fid + '"]').remove();
                        $('#bbomDeleteModal').modal('hide');
                        updatePositions();
                    });

                    // --- Drag&drop: foldery ---
                    let dragFolder = null;
                    $(document).on('dragstart', '.bbom-folder-handle', function (e) {
                        const $row = $(this).closest('.bbom-folder-row');
                        dragFolder = $row;
                        e.originalEvent.dataTransfer.setData('text/plain', $row.data('folder'));
                        e.originalEvent.dataTransfer.effectAllowed = 'move';
                    });
                    $(document).on('dragover', '.bbom-folder-list', function (e) {
                        e.preventDefault();
                        e.originalEvent.dataTransfer.dropEffect = 'move';
                        $(this).addClass('is-over');
                    });
                    $(document).on('dragover', '.bbom-folder-row', function (e) {
                        e.preventDefault();
                        e.originalEvent.dataTransfer.dropEffect = 'move';
                        $(this).addClass('is-over');
                    });
                    $(document).on('dragleave', '.bbom-folder-list', function () {
                        $(this).removeClass('is-over');
                    });
                    $(document).on('dragleave', '.bbom-folder-row', function () {
                        $(this).removeClass('is-over');
                    });
                    $(document).on('drop', '.bbom-folder-row', function (e) {
                        e.preventDefault();
                        $('.bbom-folder-row').removeClass('is-over');
                        if (!dragFolder) return;
                        const $target = $(this);
                        if (dragFolder[0] !== $target[0]) {
                            dragFolder.insertBefore($target);
                        }
                        dragFolder = null;
                        updatePositions();
                    });
                    $(document).on('drop', '.bbom-folder-list', function (e) {
                        e.preventDefault();
                        $(this).removeClass('is-over');
                        if (!dragFolder) return;
                        // wstaw na koniec listy (nad hint)
                        const $hint = $(this).find('.bbom-drop-hint').first();
                        if ($hint.length) {
                            dragFolder.insertBefore($hint);
                        } else {
                            $(this).append(dragFolder);
                        }
                        dragFolder = null;
                        updatePositions();
                    });

                    // --- Drag&drop: etapy ---
                    let dragGroup = null;
                    $(document).on('dragstart', '.bbom-group-handle', function (e) {
                        dragGroup = $(this).closest('.bbom-group');
                        e.originalEvent.dataTransfer.setData('text/plain', dragGroup.data('group'));
                        e.originalEvent.dataTransfer.effectAllowed = 'move';
                    });
                    $(document).on('dragover', '.bbom-group', function (e) {
                        e.preventDefault();
                        e.originalEvent.dataTransfer.dropEffect = 'move';
                        $(this).addClass('is-over');
                    });
                    $(document).on('dragleave', '.bbom-group', function () {
                        $(this).removeClass('is-over');
                    });
                    $(document).on('drop', '.bbom-group', function (e) {
                        e.preventDefault();
                        $('.bbom-group').removeClass('is-over');
                        if (!dragGroup) return;
                        const $target = $(this);
                        if (dragGroup[0] === $target[0]) {
                            dragGroup = null;
                            return;
                        }
                        // przenieś przed target
                        dragGroup.insertBefore($target);
                        dragGroup = null;
                        updatePositions();
                    });

                    // template selecta statusów (dla nowych folderów)
                    $(function () {
                        const tpl = $('select[name^="BB_OM_FOLDER_STATE"]:first').html();
                        $('<div id="bbom-template-states" style="display:none;"></div>').html(tpl).appendTo('body');
                        updatePositions();
                    });
                })(jQuery);
            </script>
            {/literal}

        {else}
            <div class="alert alert-warning">Nie udało się wczytać listy folderów/statusów.</div>
        {/if}


<h3 style="border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 20px; margin-top: 30px;">
            <i class="icon-lock"></i> Dostęp do BIGBIO Manager
        </h3>

        <div class="bbom-access-box">
            <div class="alert alert-info bbom-access-note">
                <i class="icon-info-circle"></i>
                Określ, którzy pracownicy mogą korzystać z <strong>BIGBIO Manager</strong>.
                To ustawienie dotyczy też <strong>API</strong> i ekranu <strong>Pakowania</strong>.
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">Dostęp</label>
                <div class="col-lg-9">
                    <div class="btn-group bbom-access-mode" data-toggle="buttons">
                        <label class="btn btn-default {if $bb_access_mode == 'all'}active{/if}">
                            <input type="radio" name="BB_OM_ACCESS_MODE" value="all" autocomplete="off" {if $bb_access_mode == 'all'}checked{/if}>
                            Wszyscy pracownicy
                        </label>
                        <label class="btn btn-default {if $bb_access_mode == 'profiles'}active{/if}">
                            <input type="radio" name="BB_OM_ACCESS_MODE" value="profiles" autocomplete="off" {if $bb_access_mode == 'profiles'}checked{/if}>
                            Wybrane profile
                        </label>
                        <label class="btn btn-default {if $bb_access_mode == 'employees'}active{/if}">
                            <input type="radio" name="BB_OM_ACCESS_MODE" value="employees" autocomplete="off" {if $bb_access_mode == 'employees'}checked{/if}>
                            Wybrani pracownicy
                        </label>
                    </div>

                    <p class="help-block bbom-muted" style="margin-top: 8px;">
                        <strong>Wskazówka:</strong> jeśli dostęp ma mieć tylko kilka osób, wybierz <em>Wybrani pracownicy</em>.
                    </p>

                    <div id="bbom-access-error" class="alert alert-danger" style="display:none; margin-top: 10px;"></div>
                </div>
            </div>

            <div id="bbom_profiles_wrapper" class="form-group bbom-access-section">
                <label class="control-label col-lg-3">Profile</label>
                <div class="col-lg-9">
                    <div class="bbom-picker">
                        <div class="bbom-picker__title"><i class="icon-user"></i> Wybierz profile</div>
                        <div class="list-group bbom-picker__list">
                            {if $bb_profiles}
                                {foreach from=$bb_profiles item=p}
                                    {assign var=pid value=$p.id_profile|intval}
                                    <label class="list-group-item">
                                        <input type="checkbox" name="BB_OM_ALLOWED_PROFILES[]" value="{$pid}" {if isset($bb_allowed_profiles_map[$pid])}checked{/if}>
                                        <strong>{$p.name|escape:'htmlall':'UTF-8'}</strong>
                                    </label>
                                {/foreach}
                            {else}
                                <div class="list-group-item">
                                    <span class="bbom-muted">Nie udało się pobrać listy profili.</span>
                                </div>
                            {/if}
                        </div>
                    </div>
                    <p class="help-block">Dostęp będą mieli pracownicy przypisani do zaznaczonych profili.</p>
                </div>
            </div>

            <div id="bbom_employees_wrapper" class="form-group bbom-access-section">
                <label class="control-label col-lg-3">Pracownicy</label>
                <div class="col-lg-9">
                    <div class="bbom-picker">
                        <div class="bbom-picker__title"><i class="icon-user"></i> Wybierz pracowników</div>
                        <div class="list-group bbom-picker__list">
                            {if $bb_employees}
                                {foreach from=$bb_employees item=e}
                                    {assign var=eid value=$e.id_employee|intval}
                                    <label class="list-group-item {if !$e.active}bbom-item-disabled{/if}">
                                        <input type="checkbox" name="BB_OM_ALLOWED_EMPLOYEES[]" value="{$eid}" {if isset($bb_allowed_employees_map[$eid])}checked{/if} {if !$e.active}disabled{/if}>
                                        <strong>{$e.firstname|escape:'htmlall':'UTF-8'} {$e.lastname|escape:'htmlall':'UTF-8'}</strong>
                                        <span class="bbom-emp-meta">
                                            {$e.email|escape:'htmlall':'UTF-8'}{if $e.profile_name} • {$e.profile_name|escape:'htmlall':'UTF-8'}{/if}
                                            {if !$e.active} • <span class="bbom-tag bbom-tag--danger">nieaktywny</span>{/if}
                                        </span>
                                    </label>
                                {/foreach}
                            {else}
                                <div class="list-group-item">
                                    <span class="bbom-muted">Nie udało się pobrać listy pracowników.</span>
                                </div>
                            {/if}
                        </div>
                    </div>
                    <p class="help-block">Dostęp będą mieli wyłącznie wskazani pracownicy.</p>
                </div>
            </div>
        </div>

        <script type="text/javascript">
            (function ($) {
                $(function () {
                    function getMode() {
                        return $('input[name=BB_OM_ACCESS_MODE]:checked').val() || 'all';
                    }

                    function applyMode(mode) {
                        if (mode === 'profiles') {
                            $('#bbom_profiles_wrapper').show();
                            $('#bbom_employees_wrapper').hide();
                        } else if (mode === 'employees') {
                            $('#bbom_profiles_wrapper').hide();
                            $('#bbom_employees_wrapper').show();
                        } else {
                            $('#bbom_profiles_wrapper').hide();
                            $('#bbom_employees_wrapper').hide();
                        }
                    }

                    applyMode(getMode());

                    $('input[name=BB_OM_ACCESS_MODE]').on('change', function () {
                        $('#bbom-access-error').hide().text('');
                        applyMode(getMode());
                    });

                    $('#bbom-config-form').on('submit', function (e) {
                        var mode = getMode();
                        var error = '';

                        if (mode === 'profiles' && $('input[name="BB_OM_ALLOWED_PROFILES[]"]:checked').length === 0) {
                            error = 'Wybierz przynajmniej jeden profil albo ustaw dostęp dla wszystkich pracowników.';
                        }
                        if (mode === 'employees' && $('input[name="BB_OM_ALLOWED_EMPLOYEES[]"]:checked').length === 0) {
                            error = 'Wybierz przynajmniej jednego pracownika albo ustaw dostęp dla wszystkich pracowników.';
                        }

                        if (error) {
                            e.preventDefault();
                            $('#bbom-access-error').text(error).show();
                            $('html, body').animate({ scrollTop: $('#bbom-access-error').offset().top - 110 }, 200);
                            return false;
                        }

                        return true;
                    });
                });
            })(jQuery);
        </script>

        {* Foldery / statusy (Kanban) - UX: drag&drop, dodawanie, ukrywanie *}
        <script type="text/javascript">
            (function ($) {
                $(function () {
                    var $board = $('#bbom-folder-board');
                    if (!$board.length) {
                        return;
                    }

                    var draggedRow = null;

                    function escapeHtml(str) {
                        return String(str)
                            .replace(/&/g, '&amp;')
                            .replace(/</g, '&lt;')
                            .replace(/>/g, '&gt;')
                            .replace(/"/g, '&quot;')
                            .replace(/'/g, '&#039;');
                    }

                    function updatePositionsAndGroups() {
                        $('.bbom-folder-list').each(function () {
                            var $list = $(this);
                            var group = $list.data('group');
                            $list.find('.bbom-folder-item').each(function (idx) {
                                var $row = $(this);
                                var key = $row.data('key');
                                $row.find('input[name="BB_OM_FOLDER_GROUP[' + key + ']"]').val(group);
                                $row.find('input[name="BB_OM_FOLDER_POS[' + key + ']"]').val(idx + 1);
                            });
                        });
                    }

                    function refreshRowDisabledState($row) {
                        var $cb = $row.find('input[name^="BB_OM_FOLDER_ACTIVE"]');
                        var isOn = $cb.is(':checked');
                        $row.toggleClass('is-disabled', !isOn);
                        $row.find('.bbom-switch-text').text(isOn ? 'Widoczny' : 'Ukryty');
                    }

                    // Toggle aktywności
                    $(document).on('change', 'input[name^="BB_OM_FOLDER_ACTIVE"]', function () {
                        refreshRowDisabledState($(this).closest('.bbom-folder-item'));
                    });

                    // Usuń folder (tylko niestandardowy) + usuń też status zamówienia w PrestaShop (po zapisie)
                    $(document).on('click', '.bbom-folder-remove', function () {
                        var $row = $(this).closest('.bbom-folder-item');
                        var key = $row.data('key');

                        // Folder label
                        var label = ($row.find('input[name^="BB_OM_FOLDER_LABEL"]').val() || $row.find('.bbom-folder-labeltext').text() || '').trim();

                        // ID statusu (dropdown jest zwykle ustawiony na realny status)
                        var stateId = parseInt($row.find('select[name^="BB_OM_FOLDER_STATE"]').val(), 10) || 0;

                        var msg = 'Usunąć folder' + (label ? ' "' + label + '"' : '') + ' oraz powiązany status zamówienia w PrestaShop?\n\n'
                            + 'Usunięcie statusu nastąpi po zapisaniu ustawień (jeśli status nie jest używany i nie jest systemowy).';

                        if (!window.confirm(msg)) {
                            return;
                        }

                        // Ukryte pola do kontrolera
                        var $del = $('#bbom-delete-inputs');
                        if ($del.length && key) {
                            $('<input>', { type: 'hidden', name: 'BB_OM_FOLDER_DELETE[]', value: key }).appendTo($del);
                            $('<input>', { type: 'hidden', name: 'BB_OM_FOLDER_DELETE_LABEL[' + key + ']', value: label }).appendTo($del);
                            $('<input>', { type: 'hidden', name: 'BB_OM_FOLDER_DELETE_STATE[' + key + ']', value: stateId }).appendTo($del);
                        }

                        $row.remove();
                        updatePositionsAndGroups();
                    });

                    // Drag & drop
                    $(document).on('dragstart', '.bbom-folder-handle', function (e) {
                        draggedRow = $(this).closest('.bbom-folder-item')[0];
                        if (!draggedRow) {
                            return;
                        }
                        $(draggedRow).addClass('is-dragging');
                        try {
                            e.originalEvent.dataTransfer.effectAllowed = 'move';
                            e.originalEvent.dataTransfer.setData('text/plain', $(draggedRow).data('key') || '');
                        } catch (err) {}
                    });

                    $(document).on('dragend', '.bbom-folder-handle', function () {
                        if (draggedRow) {
                            $(draggedRow).removeClass('is-dragging');
                        }
                        draggedRow = null;
                        updatePositionsAndGroups();
                    });

                    function getDragAfterElement(container, y) {
                        var draggableElements = [].slice.call(container.querySelectorAll('.bbom-folder-item:not(.is-dragging)'));
                        var closest = { offset: Number.NEGATIVE_INFINITY, element: null };

                        draggableElements.forEach(function (child) {
                            var box = child.getBoundingClientRect();
                            var offset = y - box.top - box.height / 2;
                            if (offset < 0 && offset > closest.offset) {
                                closest = { offset: offset, element: child };
                            }
                        });

                        return closest.element;
                    }

                    $(document).on('dragover', '.bbom-folder-list', function (e) {
                        e.preventDefault();
                        if (!draggedRow) {
                            return;
                        }
                        var container = this;
                        var afterElement = getDragAfterElement(container, e.originalEvent.clientY);
                        if (afterElement == null) {
                            container.appendChild(draggedRow);
                        } else {
                            container.insertBefore(draggedRow, afterElement);
                        }
                    });

                    // Dodawanie folderu
                    $('#bbom-add-folder-btn').on('click', function () {
                        var label = $.trim($('#bbom-new-folder-label').val() || '');
                        var group = $('#bbom-new-folder-group').val() || 'stage1';
                        var $colorOpt = $('#bbom-new-folder-color option:selected');
                        var colorClass = $colorOpt.val() || 'bg-slate-500';
                        var colorHex = $colorOpt.data('hex') || '#64748b';
                        var $errorBox = $('#bbom-folder-add-error');

                        $errorBox.hide().text('');

                        if (!label) {
                            $errorBox.text('Podaj nazwę folderu.').show();
                            return;
                        }

                        // sprawdź duplikaty
                        var exists = false;
                        $('input[name^="BB_OM_FOLDER_LABEL"]').each(function () {
                            if (($.trim($(this).val() || '').toLowerCase()) === label.toLowerCase()) {
                                exists = true;
                            }
                        });
                        if (exists) {
                            $errorBox.text('Folder o takiej nazwie już istnieje. Wybierz inną nazwę.').show();
                            return;
                        }

                        // key tylko do pola name[] w formularzu (serwer i tak bierze label)
                        var key = 'new_' + Date.now();

                        // Skopiuj select statusów z pierwszego istniejącego wiersza (żeby mieć wszystkie opcje)
                        var $selectTpl = $('.bbom-folder-item select[name^="BB_OM_FOLDER_STATE"]').first().clone();
                        if (!$selectTpl.length) {
                            // awaryjnie stwórz minimalny select
                            $selectTpl = $('<select class="form-control"><option value="0">(Automatycznie wg nazwy)</option></select>');
                        }
                        $selectTpl.attr('name', 'BB_OM_FOLDER_STATE[' + key + ']');
                        $selectTpl.val('0');

                        var rowHtml = '';
                        rowHtml += '<div class="bbom-folder-item" data-key="' + escapeHtml(key) + '">';
                        rowHtml += '  <div class="bbom-folder-handle" draggable="true" title="Przeciągnij"><i class="icon-reorder"></i></div>';
                        rowHtml += '  <div class="bbom-folder-name">';
                        rowHtml += '    <span class="bbom-folder-dot" style="background:' + escapeHtml(colorHex) + ';"></span>';
                        rowHtml += '    <span class="bbom-folder-labeltext">' + escapeHtml(label) + '</span>';
                        rowHtml += '    <span class="bbom-tag bbom-tag--custom">Niestandardowy</span>';
                        rowHtml += '  </div>';
                        rowHtml += '  <div class="bbom-folder-toggle">';
                        rowHtml += '    <label class="bbom-switch" title="Pokaż/ukryj folder w Managerze">';
                        rowHtml += '      <input type="checkbox" name="BB_OM_FOLDER_ACTIVE[' + escapeHtml(key) + ']" value="1" checked>';
                        rowHtml += '      <span class="bbom-switch-slider"></span>';
                        rowHtml += '    </label>';
                        rowHtml += '    <span class="bbom-switch-text">Widoczny</span>';
                        rowHtml += '  </div>';
                        rowHtml += '  <div class="bbom-folder-state"></div>';
                        rowHtml += '  <div class="bbom-folder-actions">';
                        rowHtml += '    <button type="button" class="btn btn-default btn-sm bbom-folder-remove" title="Usuń folder"><i class="icon-trash"></i></button>';
                        rowHtml += '  </div>';

                        rowHtml += '  <input type="hidden" name="BB_OM_FOLDER_LABEL[' + escapeHtml(key) + ']" value="' + escapeHtml(label) + '">';
                        rowHtml += '  <input type="hidden" name="BB_OM_FOLDER_GROUP[' + escapeHtml(key) + ']" value="' + escapeHtml(group) + '">';
                        rowHtml += '  <input type="hidden" name="BB_OM_FOLDER_POS[' + escapeHtml(key) + ']" value="0">';
                        rowHtml += '  <input type="hidden" name="BB_OM_FOLDER_COLOR_CLASS[' + escapeHtml(key) + ']" value="' + escapeHtml(colorClass) + '">';
                        rowHtml += '  <input type="hidden" name="BB_OM_FOLDER_COLOR_HEX[' + escapeHtml(key) + ']" value="' + escapeHtml(colorHex) + '">';
                        rowHtml += '  <input type="hidden" name="BB_OM_FOLDER_IS_ERROR[' + escapeHtml(key) + ']" value="0">';
                        rowHtml += '</div>';

                        var $row = $(rowHtml);
                        $row.find('.bbom-folder-state').append($selectTpl);

                        var $targetList = $('.bbom-folder-list[data-group="' + group + '"]');
                        if (!$targetList.length) {
                            $targetList = $('.bbom-folder-list').first();
                        }

                        $targetList.append($row);
                        updatePositionsAndGroups();

                        // reset form
                        $('#bbom-new-folder-label').val('');
                        $('#bbom-new-folder-label').focus();
                    });

                    // Upewnij się, że pozycje są aktualne przed zapisem
                    $('#bbom-config-form').on('submit', function () {
                        updatePositionsAndGroups();
                    });

                    // Initial
                    updatePositionsAndGroups();
                    $('.bbom-folder-item').each(function () {
                        refreshRowDisabledState($(this));
                    });
                });
            })(jQuery);
        </script>

        <div class="panel-footer">
            <button type="submit" class="btn btn-default pull-right" name="submitManagerProConfig">
                <i class="process-icon-save"></i> Zapisz wszystkie ustawienia
            </button>
        </div>
    </form>
</div>