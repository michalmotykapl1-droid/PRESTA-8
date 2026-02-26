{literal}
<script>
const AppMethodsAuth = {
    // sprawdzenie sesji
    async checkAuth() {
        try {
            const r = await fetch(AUTH_URL + '&action=me', {
                credentials: 'same-origin'
            });
            const d = await r.json();
            if (d && d.success && d.logged) {
                this.authLogged = true;
                this.employee = d.employee || null;
                this.csrfToken = d.csrf_token || '';
                this.loginError = '';
            } else {
                this.authLogged = false;
                this.employee = null;
                this.csrfToken = '';

                if (d && d.reason === 'FORBIDDEN') {
                    this.loginError = 'Brak uprawnień do BIGBIO Manager. Skontaktuj się z administratorem.';
                }
            }
        } catch (e) {
            console.error('AUTH/me error', e);
            this.authLogged = false;
            this.employee = null;
            this.csrfToken = '';
        } finally {
            this.authChecked = true;
        }
        return this.authLogged;
    },

    async doLogin() {
        this.loginError = '';
        if (!this.loginEmail || !this.loginPassword) {
            this.loginError = 'Podaj email i hasło.';
            return;
        }

        this.loggingIn = true;
        try {
            const body = new URLSearchParams();
            body.set('action', 'login');
            body.set('email', this.loginEmail);
            body.set('password', this.loginPassword);

            const r = await fetch(AUTH_URL, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                },
                body
            });

            const d = await r.json();
            if (d && d.success) {
                this.authLogged = true;
                this.employee = d.employee || null;
                this.csrfToken = d.csrf_token || '';
                this.loginPassword = '';
                this.loginError = '';

                // start aplikacji
                await this.fetchOrders();

                const urlParams = new URLSearchParams(window.location.search);
                const openId = urlParams.get('open_order_id');
                if (openId) {
                    setTimeout(() => { this.openOrderDetails(openId); }, 100);
                }
            } else {
                this.authLogged = false;
                this.employee = null;
                this.csrfToken = '';
                if (d && d.error_code === 'FORBIDDEN') {
                    this.loginError = (d && d.error) ? d.error : 'Brak uprawnień.';
                } else {
                    this.loginError = (d && d.error) ? d.error : 'Błąd logowania.';
                }
            }
        } catch (e) {
            console.error('AUTH/login error', e);
            this.loginError = 'Błąd połączenia.';
        } finally {
            this.loggingIn = false;
        }
    },

    async doLogout() {
        try {
            await fetch(AUTH_URL + '&action=logout', { credentials: 'same-origin' });
        } catch (e) {
            console.error('AUTH/logout error', e);
        }

        this.authLogged = false;
        this.employee = null;
        this.csrfToken = '';
        this.userMenuOpen = false;
        this.selectedOrders = [];
        this.orders = [];
        this.currentOrderDetails = null;
        this.activeView = 'list';
    },

    // helper do wywołań API modułu (wymaga zalogowania + CSRF)
    buildApiUrl(action, params = {}) {
        let url = API_URL + '&action=' + encodeURIComponent(action);
        for (const [k, v] of Object.entries(params)) {
            if (v === undefined || v === null) continue;
            url += '&' + encodeURIComponent(k) + '=' + encodeURIComponent(String(v));
        }
        return url;
    },

    async apiCall(action, params = {}, _retried = false) {
        if (!this.authLogged) {
            // UI i tak pokaże login overlay
            return { success: false, error: 'AUTH_REQUIRED', error_code: 'AUTH_REQUIRED' };
        }

        const url = this.buildApiUrl(action, params);

        let r;
        try {
            r = await fetch(url, {
                credentials: 'same-origin',
                headers: {
                    'X-BBOM-CSRF': this.csrfToken || ''
                }
            });
        } catch (e) {
            console.error('API error', e);
            return { success: false, error: 'NETWORK_ERROR' };
        }

        let d = null;
        try {
            d = await r.json();
        } catch (e) {
            return { success: false, error: 'INVALID_JSON' };
        }

        // csrf błąd -> spróbuj odświeżyć token (raz)
        if ((r.status === 403 || (d && d.error_code === 'CSRF_INVALID')) && !_retried) {
            await this.checkAuth();
            if (this.authLogged && this.csrfToken) {
                return await this.apiCall(action, params, true);
            }
        }

        // sesja wygasła / brak autoryzacji
        if (r.status === 401 || (d && d.error_code === 'AUTH_REQUIRED')) {
            this.authLogged = false;
            this.employee = null;
            this.csrfToken = '';
            this.userMenuOpen = false;
        }

        // csrf nadal błędny
        if (r.status === 403 || (d && d.error_code === 'CSRF_INVALID')) {
            this.loginError = 'Sesja wygasła. Zaloguj się ponownie.';
            this.authLogged = false;
            this.employee = null;
            this.csrfToken = '';
            this.userMenuOpen = false;
        }

        return d;
    }
};
</script>
{/literal}
