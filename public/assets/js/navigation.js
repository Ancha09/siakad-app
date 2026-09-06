(() => {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const menuButton = document.querySelector('.hamburger');
    const notifications = document.getElementById('notifPanel');
    const notificationButton = document.querySelector('[aria-controls="notifPanel"]');
    const mobile = window.matchMedia('(max-width: 900px)');

    function setSidebar(open) {
        sidebar?.classList.toggle('open', open);
        overlay?.classList.toggle('open', open);
        menuButton?.setAttribute('aria-expanded', String(open));
        menuButton?.setAttribute('aria-label', open ? 'Tutup menu navigasi' : 'Buka menu navigasi');
        if (sidebar) sidebar.inert = mobile.matches && !open;
    }

    window.toggleSidebar = () => setSidebar(!sidebar?.classList.contains('open'));
    window.closeSidebar = () => {
        const restoreFocus = sidebar?.contains(document.activeElement);
        setSidebar(false);
        if (restoreFocus) menuButton?.focus();
    };
    window.toggleNotif = () => {
        const open = !notifications?.classList.contains('open');
        notifications?.classList.toggle('open', open);
        notificationButton?.setAttribute('aria-expanded', String(open));
        if (notifications) notifications.inert = !open;
        if (!open) notificationButton?.focus();
    };

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        window.closeSidebar();
        if (notifications?.classList.contains('open')) window.toggleNotif();
    });
    mobile.addEventListener('change', () => window.closeSidebar());
    setSidebar(false);
    if (notifications) notifications.inert = !notifications.classList.contains('open');
})();
