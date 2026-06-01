document.addEventListener('click', function(e) {
    var link = e.target.closest('a');
    if (!link) return;
    var href = link.getAttribute('href');
    if (!href || href === '#' || href === '' || href.startsWith('javascript:')) return;
    if (/\/clients\/\d+(\/edit)?$/.test(href)) {
        link.setAttribute('target', '_blank');
    }
});
