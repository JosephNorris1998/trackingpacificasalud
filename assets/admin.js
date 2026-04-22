/* global tpsCCAdmin, jQuery */
(function ($) {
    'use strict';

    // ── Helpers ────────────────────────────────────────────────────────────────

    function openModal(title) {
        $('#tps-cc-modal-title').text(title);
        $('#tps-cc-form-msg').text('').removeClass('success error');
        $('#tps-cc-modal-overlay').fadeIn(180);
        $('body').css('overflow', 'hidden');
    }

    function closeModal() {
        $('#tps-cc-modal-overlay').fadeOut(180);
        $('body').css('overflow', '');
        $('#tps-cc-form')[0].reset();
        $('#tps-cc-id').val('0');
        updateLivePreview();
    }

    function showMsg($el, msg, type) {
        $el.text(msg).removeClass('success error').addClass(type);
        setTimeout(function () { $el.text('').removeClass('success error'); }, 3500);
    }

    // ── Live preview ──────────────────────────────────────────────────────────

    function updateLivePreview() {
        var bg     = $('#tps-cc-bg-color').val()     || '#0073aa';
        var tc     = $('#tps-cc-text-color').val()   || '#ffffff';
        var fs     = parseInt($('#tps-cc-font-size').val(), 10) || 16;
        var pad    = $('#tps-cc-btn-padding').val()   || '12px 24px';
        var radius = parseInt($('#tps-cc-border-radius').val(), 10);
        var w      = $('#tps-cc-btn-width').val()     || 'auto';
        var label  = $('#tps-cc-button-label').val()  || 'Vista previa';

        $('#tps-cc-live-btn').css({
            'background-color' : bg,
            'color'            : tc,
            'font-size'        : fs + 'px',
            'padding'          : pad,
            'border-radius'    : (isNaN(radius) ? 4 : radius) + 'px',
            'width'            : w
        }).text(label);
    }

    // ── Colour pickers ────────────────────────────────────────────────────────

    function initColorPickers() {
        $('.tps-cc-color-picker').wpColorPicker({
            change: function () {
                setTimeout(updateLivePreview, 50);
            },
            clear: updateLivePreview
        });
    }

    // ── DOM ready ─────────────────────────────────────────────────────────────

    $(function () {

        initColorPickers();
        updateLivePreview();

        // ── New button ────────────────────────────────────────────────────────

        $(document).on('click', '#tps-cc-new-btn', function () {
            $('#tps-cc-form')[0].reset();
            $('#tps-cc-id').val('0');
            if ($.fn.wpColorPicker) {
                $('#tps-cc-bg-color').wpColorPicker('color', '#0073aa');
                $('#tps-cc-text-color').wpColorPicker('color', '#ffffff');
            }
            $('#tps-cc-btn-align').val('center');
            updateLivePreview();
            openModal('Nuevo botón');
        });

        // ── Edit button ───────────────────────────────────────────────────────

        $(document).on('click', '.tps-cc-edit-btn', function () {
            var id = $(this).data('id');

            $.ajax({
                url    : tpsCCAdmin.ajaxUrl,
                method : 'POST',
                data   : { action: 'tps_cc_get_button', nonce: tpsCCAdmin.nonce, id: id },
                success: function (resp) {
                    if (!resp.success) { return; }
                    var b = resp.data;
                    $('#tps-cc-id').val(b.id);
                    $('#tps-cc-button-name').val(b.button_name);
                    $('#tps-cc-button-label').val(b.button_label);
                    if ($.fn.wpColorPicker) {
                        $('#tps-cc-bg-color').wpColorPicker('color', b.bg_color);
                        $('#tps-cc-text-color').wpColorPicker('color', b.text_color);
                    }
                    $('#tps-cc-font-size').val(b.font_size);
                    $('#tps-cc-btn-width').val(b.btn_width);
                    $('#tps-cc-btn-padding').val(b.btn_padding);
                    $('#tps-cc-border-radius').val(b.border_radius);
                    $('#tps-cc-btn-align').val(b.btn_align || 'center');
                    updateLivePreview();
                    openModal('Editar botón #' + b.id);
                }
            });
        });

        // ── Save form ─────────────────────────────────────────────────────────

        $(document).on('submit', '#tps-cc-form', function (e) {
            e.preventDefault();

            var $msg  = $('#tps-cc-form-msg');
            var $form = $(this);
            var data  = $form.serialize() + '&action=tps_cc_save_button&nonce=' + encodeURIComponent(tpsCCAdmin.nonce);

            $('#tps-cc-save-btn').prop('disabled', true).text('Guardando…');

            $.ajax({
                url    : tpsCCAdmin.ajaxUrl,
                method : 'POST',
                data   : data,
                success: function (resp) {
                    if (resp.success) {
                        showMsg($msg, '✅ Guardado correctamente. Recarga la página para ver los cambios.', 'success');
                        setTimeout(function () { location.reload(); }, 2000);
                    } else {
                        showMsg($msg, '❌ ' + (resp.data && resp.data.message ? resp.data.message : 'Error al guardar.'), 'error');
                    }
                },
                error: function () {
                    showMsg($msg, '❌ Error de conexión.', 'error');
                },
                complete: function () {
                    $('#tps-cc-save-btn').prop('disabled', false).text('Guardar botón');
                }
            });
        });

        // ── Delete button ─────────────────────────────────────────────────────

        $(document).on('click', '.tps-cc-delete-btn', function () {
            var id = $(this).data('id');
            if (!window.confirm('¿Seguro que quieres eliminar este botón? Esta acción no se puede deshacer.')) {
                return;
            }

            $.ajax({
                url    : tpsCCAdmin.ajaxUrl,
                method : 'POST',
                data   : { action: 'tps_cc_delete_button', nonce: tpsCCAdmin.nonce, id: id },
                success: function (resp) {
                    if (resp.success) {
                        $('#tps-cc-row-' + id).fadeOut(300, function () { $(this).remove(); });
                    } else {
                        alert('Error al eliminar.');
                    }
                }
            });
        });

        // ── Reset individual clicks ───────────────────────────────────────────

        $(document).on('click', '.tps-cc-reset-btn', function () {
            var id = $(this).data('id');
            if (!window.confirm('¿Resetear los clics de este botón?')) { return; }

            $.ajax({
                url    : tpsCCAdmin.ajaxUrl,
                method : 'POST',
                data   : { action: 'tps_cc_reset_clicks', nonce: tpsCCAdmin.nonce, id: id },
                success: function (resp) {
                    if (resp.success) {
                        $('#tps-cc-count-' + id).text('0');
                    }
                }
            });
        });

        // ── Reset ALL clicks ──────────────────────────────────────────────────

        $(document).on('click', '#tps-cc-reset-all-btn', function () {
            if (!window.confirm('¿Resetear los clics de TODOS los botones?')) { return; }

            $.ajax({
                url    : tpsCCAdmin.ajaxUrl,
                method : 'POST',
                data   : { action: 'tps_cc_reset_clicks', nonce: tpsCCAdmin.nonce, id: 0 },
                success: function (resp) {
                    if (resp.success) {
                        $('[id^="tps-cc-count-"]').text('0');
                    }
                }
            });
        });

        // ── Copy shortcode ────────────────────────────────────────────────────

        $(document).on('click', '.tps-cc-copy-btn', function () {
            var sc = $(this).data('shortcode');
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(sc).then(function () {
                    alert('✅ Shortcode copiado al portapapeles:\n' + sc);
                });
            } else {
                window.prompt('Copia este shortcode:', sc);
            }
        });

        // ── Modal close ───────────────────────────────────────────────────────

        $(document).on('click', '#tps-cc-modal-close, #tps-cc-cancel-btn', closeModal);
        $(document).on('click', '#tps-cc-modal-overlay', function (e) {
            if ($(e.target).is('#tps-cc-modal-overlay')) { closeModal(); }
        });
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape' && $('#tps-cc-modal-overlay').is(':visible')) { closeModal(); }
        });

        // ── Live preview bindings ─────────────────────────────────────────────

        $(document).on('input change', '#tps-cc-button-label, #tps-cc-font-size, #tps-cc-btn-padding, #tps-cc-border-radius, #tps-cc-btn-width', updateLivePreview);

    }); // end DOM ready

}(jQuery));
