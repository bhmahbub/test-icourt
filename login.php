    <!-- ==================== LOGIN SCREEN ==================== -->
    <div class="d-flex justify-content-center align-items-center min-vh-100">
        <div class="container login-container">
            <div class="card border-0 rounded rounded-4 overflow-hidden shadow">
                <div class="row g-0">
                    <div class="col-md-5 brand-section">
                        <div class="">
                            <i class="fas fa-gavel fa-flip-horizontal px-3 py-3 fa-3x bg-white text-success border border-2 border-success rounded-circle"></i>
                        </div>
                        <h2 class="mt-3"><span class="text-danger fst-italic fw-bold shadow shadow-sm">i</span>Court</h2>
                    </div>
                    <div class="col-md-7">
                        <div class="card-body p-5">
                            <div class="text-success mb-3">
                                <h2 class="fw-bold mb-3">Login</h2>
                                <?php if (!empty($error)): ?>
                                <div class="alert alert-danger d-flex align-items-center mb-3">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    <?php echo $error; ?>
                                </div>
                                <?php endif; ?>
                            </div>

                            <form class="login-form" method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" name="username" id="username" class="form-control"
                                            placeholder="Enter username" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" name="password" id="password" class="form-control"
                                            placeholder="Enter password" required>
                                        <button type="button" class="btn btn-outline-secondary" id="passwordToggle">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <button type="submit" name="login" class="btn login-btn mt-3">Sign In</button>
                                <div class="copyright mt-4">
                                    <b>&copy;</b>
                                    <?php echo date('Y'); ?> iCourt.
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    