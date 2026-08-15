        </main>
    </div>
</div>

<!-- Canlı sipariş bildirimleri (tüm admin sayfalarında aktif) -->
<div class="notify-stack" id="notifyStack" role="status" aria-live="polite"></div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('sidebarToggleBtn');
    const sidebar = document.getElementById('adminSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');

    if (toggleBtn && sidebar && backdrop) {
        const toggleSidebar = () => {
            sidebar.classList.toggle('show');
            backdrop.classList.toggle('show');
        };

        toggleBtn.addEventListener('click', toggleSidebar);
        backdrop.addEventListener('click', toggleSidebar);

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && sidebar.classList.contains('show')) toggleSidebar();
        });
    }
});
</script>
<script src="../public/assets/js/admin-orders.js"></script>
</body>
</html>
