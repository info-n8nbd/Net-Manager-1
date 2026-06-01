document.addEventListener('DOMContentLoaded', function() {
    var currentPath = window.location.pathname.replace(/\/$/, '');
    var sidebarLinks = document.querySelectorAll('.nav-sidebar .nav-link');
    var bestMatch = null;
    var bestLen = 0;

    sidebarLinks.forEach(function(link) {
        var href = link.getAttribute('href');
        if (!href || href === '#' || href === 'javascript:void(0)') return;

        try {
            var linkPath = new URL(href, window.location.origin).pathname.replace(/\/$/, '');
        } catch(e) { return; }

        if (currentPath === linkPath || currentPath.indexOf(linkPath + '/') === 0) {
            if (linkPath.length > bestLen) {
                bestLen = linkPath.length;
                bestMatch = link;
            }
        }
    });

    if (bestMatch) {
        sidebarLinks.forEach(function(link) {
            if (link.closest('.nav-treeview')) {
                link.classList.remove('active');
            }
        });

        bestMatch.classList.add('active');

        var treeview = bestMatch.closest('.nav-treeview');
        if (treeview) {
            var parentLi = treeview.closest('.nav-item');
            if (parentLi) {
                parentLi.classList.add('menu-open');
                var parentLink = parentLi.querySelector(':scope > .nav-link');
                if (parentLink) {
                    parentLink.classList.add('active');
                }
            }
        }
    }
});
