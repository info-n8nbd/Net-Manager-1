/**
 * Softynex ISP — Real-time Ticket Browser Notifications
 * Polls the server every 30 s and shows a Web Notification for every new ticket.
 */
(function () {
    'use strict';

    const POLL_INTERVAL = 30000; // 30 seconds
    const POLL_URL      = '/admin/notifications/new-tickets';
    const ICON_URL      = '/images/logo.png'; // fallback gracefully if missing

    let lastTimestamp   = Date.now();
    let permissionGranted = false;
    let pollTimer       = null;

    /* ── 1. Request permission ─────────────────────────────── */
    function requestPermission() {
        if (!('Notification' in window)) return;

        if (Notification.permission === 'granted') {
            permissionGranted = true;
            startPolling();
        } else if (Notification.permission !== 'denied') {
            // Show a friendly toast first so the browser prompt feels expected
            showToast('নতুন টিকেট এলে আপনাকে জানাতে notification চালু করুন।', 'info');
            setTimeout(function () {
                Notification.requestPermission().then(function (perm) {
                    permissionGranted = (perm === 'granted');
                    if (permissionGranted) {
                        showToast('Ticket notification চালু হয়েছে!', 'success');
                        startPolling();
                    }
                });
            }, 1500);
        }
        // If 'denied', do nothing — do not nag the user
    }

    /* ── 2. Polling ────────────────────────────────────────── */
    function startPolling() {
        if (pollTimer) return; // already running
        poll(); // immediate first poll
        pollTimer = setInterval(poll, POLL_INTERVAL);
    }

    function poll() {
        fetch(POLL_URL + '?since=' + lastTimestamp, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin'
        })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (data) {
            if (!data) return;
            lastTimestamp = data.server_time || Date.now();
            if (data.tickets && data.tickets.length > 0) {
                data.tickets.forEach(showTicketNotification);
            }
        })
        .catch(function () { /* network hiccup — silent, will retry next interval */ });
    }

    /* ── 3. Show browser notification ─────────────────────── */
    function showTicketNotification(ticket) {
        if (!permissionGranted) return;

        const priorityEmoji = { high: '🔴', medium: '🟡', low: '🟢' };
        const emoji = priorityEmoji[ticket.priority] || '🎫';

        const title = emoji + ' নতুন Ticket: ' + ticket.ticket_no;
        const body  = ticket.subject + '\nClient: ' + ticket.client + ' | Priority: ' + ticket.priority;

        const n = new Notification(title, {
            body:    body,
            icon:    ICON_URL,
            tag:     'ticket-' + ticket.id,   // deduplicates if shown twice
            requireInteraction: false
        });

        n.onclick = function () {
            window.focus();
            window.location.href = ticket.url;
            n.close();
        };

        // Also show an AdminLTE toast for users already on the page
        showToast(
            '<strong>' + ticket.ticket_no + '</strong> — ' + ticket.subject +
            '<br><small>Client: ' + ticket.client + '</small>',
            ticket.priority === 'high' ? 'danger' : 'warning',
            ticket.url
        );
    }

    /* ── 4. Toast helper (AdminLTE / toastr) ──────────────── */
    function showToast(message, type, url) {
        if (typeof toastr !== 'undefined') {
            var opts = { timeOut: 8000, extendedTimeOut: 3000, closeButton: true, progressBar: true };
            if (url) {
                opts.onclick = function () { window.location.href = url; };
            }
            toastr[type === 'danger' ? 'error' : (type || 'info')](message, 'Support Ticket', opts);
        }
    }

    /* ── 5. Boot when DOM is ready ─────────────────────────── */
    function boot() {
        // Only run on admin pages
        if (!document.querySelector('.main-sidebar')) return;
        requestPermission();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
