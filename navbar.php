    <!-- Navbar with text -->
    <nav class="navbar navbar-expand bg-body-tertiary shadow shadow-sm">
        <div class="container px-md-0 px-5">
            <a class="navbar-brand text-success" href="saas.php">
                <i class="fas fa-gavel fa-flip-horizontal px-2 py-2 bg-white text-success border border-2 border-success rounded-circle me-2"></i>
                <span class=""><b><span class="text-danger fst-italic">i</span>Court</b></span>
            </a>

            <div class="d-flex align-items-center">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="me-2 fw-bold text-secondary">
                                    <?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'User'; ?>
                                </span>
                                <img src="https://ui-avatars.com/api/?name=<?= isset($_SESSION['name']) ? urlencode($_SESSION['name']) : 'User' ?>&length=1&bold=true&background=198754&color=fff"
                                    height="40px" class="rounded-circle" alt="User">
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <?php if ($_SESSION['role'] === 'administrator'): ?>
                                    <li><a class="dropdown-item text-success fw-bold" href="#" data-bs-toggle="modal" data-bs-target="#createUserModal">
                                        <i class="fas fa-user-plus me-2"></i> Create User
                                    </a></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item text-success fw-bold" href="#" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                    <i class="fas fa-key me-2"></i> Change Password
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger fw-bold" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">
                                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                                </a></li>
                            </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>