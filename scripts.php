    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ==================== PASSWORD TOGGLE ====================
        const passwordToggle = document.getElementById('passwordToggle');
        if (passwordToggle) {
            passwordToggle.addEventListener('click', function () {
                const passwordInput = document.getElementById('password');
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    this.innerHTML = '<i class="fas fa-eye-slash"></i>';
                } else {
                    passwordInput.type = 'password';
                    this.innerHTML = '<i class="fas fa-eye"></i>';
                }
            });
        }

        // ==================== DISTRICT DROPDOWN AJAX ====================
        document.getElementById('divisionSelect').addEventListener('change', function() {
            const division = this.value;
            if (!division) return;
            
            fetch(`?action=get_districts&division=${encodeURIComponent(division)}`)
                .then(response => response.json())
                .then(districts => {
                    const districtSelect = document.getElementById('districtSelect');
                    districtSelect.innerHTML = '<option value="">Select District</option>';
                    districts.forEach(district => {
                        const option = document.createElement('option');
                        option.value = district;
                        option.textContent = district;
                        districtSelect.appendChild(option);
                    });
                });
        });

        // ==================== AUTO-HIDE TOAST MESSAGES ====================
        setTimeout(() => {
            const messages = document.querySelectorAll('.message');
            messages.forEach(msg => {
                msg.style.opacity = '0';
                setTimeout(() => msg.remove(), 500);
            });
        }, 3500);
    </script>