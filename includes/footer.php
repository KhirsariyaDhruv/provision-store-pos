        </div> <!-- End page-content -->
    </div> <!-- End main-content -->

    <!-- Mobile Bottom Navigation -->
    <nav class="mobile-nav mobile-only">
        <a href="index.php" class="mobile-nav-item <?= $current_page == 'index.php' ? 'active' : '' ?>">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="pos.php" class="mobile-nav-item <?= $current_page == 'pos.php' ? 'active' : '' ?>">
            <i class="fas fa-cash-register"></i>
            <span>POS</span>
        </a>
        <a href="inventory.php" class="mobile-nav-item <?= $current_page == 'inventory.php' ? 'active' : '' ?>">
            <i class="fas fa-boxes"></i>
            <span>Stock</span>
        </a>
        <a href="customers.php" class="mobile-nav-item <?= $current_page == 'customers.php' ? 'active' : '' ?>">
            <i class="fas fa-users"></i>
            <span>Khata</span>
        </a>
        <a href="profile.php" class="mobile-nav-item <?= $current_page == 'profile.php' ? 'active' : '' ?>">
            <i class="fas fa-user"></i>
            <span>Profile</span>
        </a>
    </nav>

<script>
    // Simple Sidebar Toggle for Mobile
    // This is basic, for full production we'd put this in main.js
    document.querySelector('.toggle-btn')?.addEventListener('click', () => {
        document.querySelector('.sidebar').classList.toggle('active');
    });
</script>
</body>
</html>
