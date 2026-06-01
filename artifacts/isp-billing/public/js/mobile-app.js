/* =========================================================
   SMART ISP - Mobile App JS
   Swipe sidebar, DataTables fix, touch enhancements
   ========================================================= */

(function($) {
    'use strict';

    var isMobile = function() { return window.innerWidth <= 991; };

    // Reliable sidebar toggle — replicates AdminLTE PushMenu internals
    function toggleAdminSidebar() {
        var body = document.body;
        var isOpen = body.classList.contains('sidebar-open');
        if (isOpen) {
            body.classList.remove('sidebar-open');
            var ov = document.getElementById('sidebar-overlay');
            if (ov) ov.remove();
        } else {
            body.classList.add('sidebar-open');
            body.classList.remove('sidebar-collapse');
            var old = document.getElementById('sidebar-overlay');
            if (old) old.remove();
            var overlay = document.createElement('div');
            overlay.id = 'sidebar-overlay';
            overlay.style.display = 'block';
            overlay.addEventListener('click', function() {
                body.classList.remove('sidebar-open');
                overlay.remove();
            });
            document.body.appendChild(overlay);
        }
    }

    /* ---- 1. DATATABLES: Prevent horizontal overflow on mobile ---- */
    if ($.fn.DataTable) {
        $.extend(true, $.fn.dataTable.defaults, {
            autoWidth: false,
            scrollCollapse: true,
            responsive: false
        });

        /* After any DataTable init, remove inline width from table */
        $(document).on('init.dt', function(e, settings) {
            if (!isMobile()) return;
            var api = new $.fn.dataTable.Api(settings);
            $(api.table().node()).css('width', '100%');
            $(api.table().container()).css('overflow-x', 'auto');
        });

        /* On draw, strip fixed widths from th/td */
        $(document).on('draw.dt', function(e, settings) {
            if (!isMobile()) return;
            var api = new $.fn.dataTable.Api(settings);
            $(api.table().node()).find('th, td').each(function() {
                this.style.removeProperty('width');
                this.style.removeProperty('min-width');
            });
            $(api.table().node()).css('width', '100%');
        });
    }

    /* ---- 2. SIDEBAR SWIPE GESTURE (open/close by swiping) ---- */
    var touchStartX = 0;
    var touchStartY = 0;

    document.addEventListener('touchstart', function(e) {
        touchStartX = e.changedTouches[0].screenX;
        touchStartY = e.changedTouches[0].screenY;
    }, { passive: true });

    document.addEventListener('touchend', function(e) {
        if (!isMobile()) return;
        var dx = e.changedTouches[0].screenX - touchStartX;
        var dy = e.changedTouches[0].screenY - touchStartY;

        /* Ignore vertical swipes */
        if (Math.abs(dy) > Math.abs(dx)) return;
        /* Ignore short swipes */
        if (Math.abs(dx) < 50) return;

        var sidebarOpen = $('body').hasClass('sidebar-open');

        /* Swipe right (from left edge) → open sidebar */
        if (dx > 0 && touchStartX < 30 && !sidebarOpen) {
            toggleAdminSidebar();
        }

        /* Swipe left → close sidebar */
        if (dx < 0 && sidebarOpen) {
            toggleAdminSidebar();
        }
    }, { passive: true });

    /* ---- 3. CLOSE SIDEBAR WHEN TAPPING OVERLAY ---- */
    $(document).on('click touchstart', '.wrapper', function(e) {
        if (!isMobile()) return;
        if (!$('body').hasClass('sidebar-open')) return;
        var target = $(e.target);
        if (!target.closest('.main-sidebar').length && !target.closest('[data-widget="pushmenu"]').length) {
            toggleAdminSidebar();
        }
    });

    /* ---- 4. ORIENTATION CHANGE: Redraw tables & charts ---- */
    window.addEventListener('orientationchange', function() {
        setTimeout(function() {
            /* Redraw DataTables */
            if ($.fn.DataTable) {
                $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
            }
            /* Resize Chart.js charts */
            if (window.Chart) {
                Chart.instances && Object.values(Chart.instances).forEach(function(chart) {
                    try { chart.resize(); } catch(ex) {}
                });
            }
        }, 400);
    });

    /* ---- 5. MAKE ALL CARD TABLES SCROLLABLE (runtime fix) ---- */
    $(document).ready(function() {
        if (!isMobile()) return;

        /* Wrap bare tables in a scroll div */
        $('table.table').each(function() {
            if (!$(this).closest('.table-responsive, .dataTables_wrapper').length) {
                $(this).wrap('<div style="overflow-x:auto;-webkit-overflow-scrolling:touch;width:100%;"></div>');
            }
        });

        /* Ensure dataTables wrapper scrolls */
        $('.dataTables_wrapper').css({
            'overflow-x': 'auto',
            '-webkit-overflow-scrolling': 'touch'
        });
    });

    /* ---- 6. PREVENT DOUBLE-TAP ZOOM ON BUTTONS ---- */
    var lastTap = 0;
    $(document).on('touchend', '.btn, .nav-link, .dropdown-item', function(e) {
        var now = Date.now();
        if (now - lastTap < 300) { e.preventDefault(); }
        lastTap = now;
    });

    /* ---- 7. FLOATING BACK-TO-TOP BUTTON ---- */
    $(document).ready(function() {
        if (!isMobile()) return;
        var btn = $('<button id="mobileBackTop" style="' +
            'display:none;position:fixed;bottom:20px;right:15px;z-index:9999;' +
            'width:40px;height:40px;border-radius:50%;background:#1e3c72;color:#fff;' +
            'border:none;font-size:18px;cursor:pointer;box-shadow:0 3px 10px rgba(0,0,0,0.3);' +
            'align-items:center;justify-content:center;">' +
            '<i class="fas fa-chevron-up"></i></button>');
        $('body').append(btn);
        $(window).on('scroll', function() {
            if ($(this).scrollTop() > 200) { btn.css('display','flex'); }
            else { btn.hide(); }
        });
        btn.on('click', function() {
            $('html, body').animate({ scrollTop: 0 }, 300);
        });
    });

    /* ---- 8. COMPACT CONTENT PADDING ON MOBILE ---- */
    $(document).ready(function() {
        if (!isMobile()) return;
        /* Reduce content section padding */
        $('.content').css('padding', '0 8px 8px 8px');
        $('.container-fluid').css('padding', '0 4px');
    });

    /* ---- 9. FIX LONG TEXT IN TABLE CELLS ---- */
    $(document).ready(function() {
        if (!isMobile()) return;
        $('<style>' +
            'td { max-width: 200px; word-wrap: break-word; word-break: break-word; }' +
            '.dataTables_wrapper .table { table-layout: auto !important; }' +
        '</style>').appendTo('head');
    });

})(jQuery);
