/**
 * SIWES360 - Frontend Core JavaScript
 * Vanilla JavaScript for UI interactivity, attendance clocks, and table filters
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Password Visibility Toggle
    const togglePwdBtn = document.getElementById('togglePwd');
    const pwdInput = document.getElementById('password');
    if (togglePwdBtn && pwdInput) {
        togglePwdBtn.addEventListener('click', () => {
            const isPassword = pwdInput.type === 'password';
            pwdInput.type = isPassword ? 'text' : 'password';
            togglePwdBtn.textContent = isPassword ? '🙈' : '👁️';
        });
    }

    // 2. CSRF protection: attach the server-issued token to every POST form.
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
        document.querySelectorAll('form[method="POST"], form[method="post"]').forEach(form => {
            if (!form.querySelector('input[name="csrf_token"]')) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'csrf_token';
                input.value = csrfToken;
                form.appendChild(input);
            }
        });
    }

    // 3. Responsive Sidebar Mobile Toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const appSidebar = document.querySelector('.app-sidebar');
    if (sidebarToggle && appSidebar) {
        sidebarToggle.addEventListener('click', () => {
            appSidebar.classList.toggle('open');
        });

        // Close when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 900 && 
                appSidebar.classList.contains('open') && 
                !appSidebar.contains(e.target) && 
                !sidebarToggle.contains(e.target)) {
                appSidebar.classList.remove('open');
            }
        });
    }

    // 4. Live Digital Clock
    const liveClockEl = document.getElementById('liveClock');
    if (liveClockEl) {
        const updateClock = () => {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            liveClockEl.textContent = `${hours}:${minutes}:${seconds}`;
        };
        updateClock();
        setInterval(updateClock, 1000);
    }

    // 5. Generic Table Search & Filter Helper
    const tableSearchInput = document.getElementById('tableSearch');
    if (tableSearchInput) {
        tableSearchInput.addEventListener('input', (e) => {
            const term = e.target.value.toLowerCase().trim();
            const tableRows = document.querySelectorAll('.data-table tbody tr');
            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(term) ? '' : 'none';
            });
        });
    }

    // 6. Generic Modal Controls
    window.openModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) modal.classList.add('open');
    };

    window.closeModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) modal.classList.remove('open');
    };
});
