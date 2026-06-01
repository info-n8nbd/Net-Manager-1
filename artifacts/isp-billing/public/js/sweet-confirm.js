/**
 * Smart ISP — Global Confirm & Alert dialogs via SweetAlert2 v5
 */
(function () {
    'use strict';

    var styleId = 'smart-swal-style-v5';
    if (!document.getElementById(styleId)) {
        var s = document.createElement('style');
        s.id = styleId;
        s.textContent = [
            '.smart-swal-popup{border-radius:10px!important;padding:1em 1.1em!important;max-width:360px!important;box-shadow:0 8px 30px rgba(0,0,0,.15)!important;}',
            '.smart-swal-title{font-size:15px!important;font-weight:600!important;color:#1a2e5a!important;margin-bottom:.25em!important;}',
            '.smart-swal-html{font-size:13px!important;color:#4a5568!important;line-height:1.5!important;}',
            '.swal2-icon{width:42px!important;height:42px!important;margin:.4em auto .5em!important;}',
            '.swal2-icon .swal2-icon-content{font-size:24px!important;}',
            '.swal2-actions{gap:8px!important;margin-top:.7em!important;}',
            '.smart-swal-popup .btn{font-size:12px!important;padding:5px 16px!important;border-radius:6px!important;}'
        ].join('');
        document.head.appendChild(s);
    }

    window.smartConfirm = function (opts, onConfirm, onCancel) {
        if (!window.Swal) { if (typeof onConfirm === 'function') onConfirm(); return; }
        if (typeof opts === 'string') opts = { message: opts };
        var isDelete = opts.isDelete || /delete|remove|delet/i.test(opts.message || '');
        Swal.fire({
            title: opts.title || (isDelete ? 'Confirm Delete' : 'Confirm'),
            text: opts.message || 'Are you sure?',
            icon: isDelete ? 'warning' : 'question',
            showCancelButton: true,
            confirmButtonText: opts.confirmText || (isDelete ? 'Yes, Delete' : 'Confirm'),
            cancelButtonText: opts.cancelText || 'Cancel',
            reverseButtons: true,
            buttonsStyling: false,
            width: '360px',
            padding: '1em',
            customClass: {
                popup: 'smart-swal-popup',
                title: 'smart-swal-title',
                htmlContainer: 'smart-swal-html',
                confirmButton: isDelete ? 'btn btn-danger btn-sm' : 'btn btn-primary btn-sm',
                cancelButton: 'btn btn-light btn-sm',
            }
        }).then(function (r) {
            if (r.isConfirmed) { if (typeof onConfirm === 'function') onConfirm(); }
            else { if (typeof onCancel === 'function') onCancel(); }
        });
    };

    window.smartAlert = function (msg, type) {
        if (!window.Swal) { return; }
        type = type || 'info';
        Swal.fire({
            text: String(msg),
            icon: type,
            confirmButtonText: 'OK',
            buttonsStyling: false,
            width: '340px',
            padding: '1em',
            customClass: {
                popup: 'smart-swal-popup',
                title: 'smart-swal-title',
                htmlContainer: 'smart-swal-html',
                confirmButton: 'btn btn-primary btn-sm',
            }
        });
    };

    var _nativeAlert = window.alert;
    window.alert = function (msg) {
        if (!window.Swal) { _nativeAlert.call(window, msg); return; }
        var str = String(msg);
        var type = /error|fail|invalid|wrong/i.test(str) ? 'error'
                 : /success|saved|done|complet/i.test(str) ? 'success'
                 : /warn|caution/i.test(str) ? 'warning' : 'info';
        smartAlert(str, type);
    };

    // ── Intercept onsubmit="return confirm(...)" ──────────────────────────────
    document.addEventListener('submit', function (e) {
        try {
            var form = e.target;
            var attr = form.getAttribute('onsubmit');
            if (!attr || attr.indexOf('confirm(') === -1) return;
            e.preventDefault();
            e.stopImmediatePropagation();
            var m = attr.match(/confirm\(['"]([^'"]+)['"]\)/);
            var msg = m ? m[1] : 'Are you sure?';
            var isDel = /delete|remove|delet/i.test(msg + attr);
            smartConfirm({ message: msg, isDelete: isDel }, function () {
                form.removeAttribute('onsubmit');
                form.submit();
            });
        } catch (err) { console.warn('[sweet-confirm submit]', err); }
    }, true);

    // ── Intercept onclick="return confirm(...)" ───────────────────────────────
    document.addEventListener('click', function (e) {
        try {
            var el = e.target.closest('[onclick*="confirm("]');
            if (!el) return;
            var attr = el.getAttribute('onclick');
            if (!attr || attr.indexOf('confirm(') === -1) return;

            // Extract message — no /s flag for compatibility
            var m = attr.match(/confirm\(['"]([^'"]+)['"]\)/) ||
                    attr.match(/confirm\(`([^`]+)`\)/);
            var msg = m ? m[1] : 'Are you sure?';
            var isDel = /delete|remove|delet/i.test(msg + attr);

            e.preventDefault();
            e.stopImmediatePropagation();

            smartConfirm({ message: msg, isDelete: isDel }, function () {
                el.removeAttribute('onclick');
                el.click();
                el.setAttribute('onclick', attr);
            });
        } catch (err) { console.warn('[sweet-confirm click]', err); }
    }, true);

})();
