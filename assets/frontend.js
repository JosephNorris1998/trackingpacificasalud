/* global tpsCCData, jQuery */
(function ($) {
    'use strict';

    /**
     * Show a custom confirmation dialog.
     * Returns a Promise that resolves true (confirmed) or false (cancelled).
     */
    function showConfirm() {
        return new Promise(function (resolve) {
            var overlay = $('<div class="tps-cc-confirm-overlay"></div>');
            var box = $(
                '<div class="tps-cc-confirm-box">' +
                '  <p>' + tpsCCData.confirmMsg + '</p>' +
                '  <div class="tps-cc-confirm-actions">' +
                '    <button class="tps-cc-confirm-yes">' + tpsCCData.confirmYes + '</button>' +
                '    <button class="tps-cc-confirm-no">'  + tpsCCData.confirmNo  + '</button>' +
                '  </div>' +
                '</div>'
            );
            overlay.append(box);
            $('body').append(overlay);

            // Focus management
            box.find('.tps-cc-confirm-yes').focus();

            overlay.on('click', '.tps-cc-confirm-yes', function () {
                overlay.remove();
                resolve(true);
            });
            overlay.on('click', '.tps-cc-confirm-no', function () {
                overlay.remove();
                resolve(false);
            });
            // Close on overlay background click
            overlay.on('click', function (e) {
                if ($(e.target).is(overlay)) {
                    overlay.remove();
                    resolve(false);
                }
            });
            // Close on Escape
            $(document).one('keydown.tps_cc_confirm', function (e) {
                if (e.key === 'Escape') {
                    overlay.remove();
                    resolve(false);
                }
            });
        });
    }

    /** Show a brief toast notification */
    function showToast(msg) {
        var toast = $('<div class="tps-cc-toast"></div>').text(msg);
        $('body').append(toast);
        setTimeout(function () {
            toast.remove();
        }, 2650);
    }

    /** Handle click on any .tps-cc-btn */
    $(document).on('click', 'button.tps-cc-btn', function () {
        var $btn     = $(this);
        var buttonId = parseInt($btn.data('id'), 10);

        if (!buttonId) { return; }

        showConfirm().then(function (confirmed) {
            if (!confirmed) { return; }

            $btn.prop('disabled', true);

            $.ajax({
                url    : tpsCCData.ajaxUrl,
                method : 'POST',
                data   : {
                    action    : 'tps_cc_register_click',
                    nonce     : tpsCCData.nonce,
                    button_id : buttonId
                },
                success: function (response) {
                    if (response.success) {
                        showToast(tpsCCData.thankYouMsg);
                    } else {
                        showToast(tpsCCData.errorMsg);
                    }
                },
                error: function () {
                    showToast(tpsCCData.errorMsg);
                },
                complete: function () {
                    $btn.prop('disabled', false);
                }
            });
        });
    });

}(jQuery));
