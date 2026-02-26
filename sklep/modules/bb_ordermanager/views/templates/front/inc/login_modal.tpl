{* PLIK: modules/bb_ordermanager/views/templates/front/inc/login_modal.tpl *}
{literal}
<!-- LOGIN OVERLAY -->
<!--
  Ważne: Nie pokazujemy od razu okna logowania.
  Najpierw Vue sprawdza sesję (authChecked=false). Bez tego był "flash" logowania
  u zalogowanych pracowników przy odświeżeniu / powrocie z pakowania.
-->

<!-- AUTH CHECK LOADER -->
<div v-if="!authChecked" class="fixed inset-0 z-[9999] bg-white flex items-center justify-center">
    <div class="text-center text-slate-500">
        <i class="fa-solid fa-circle-notch spin text-3xl text-blue-600"></i>
        <div class="mt-3 text-sm">Sprawdzanie sesji…</div>
    </div>
</div>

<!-- LOGIN FORM (tylko gdy sprawdzenie zakończone i brak sesji) -->
<div v-else-if="!authLogged" class="fixed inset-0 z-[9999] bg-white">
    <div class="h-full w-full grid grid-cols-1 md:grid-cols-2">
        <!-- Left panel (branding) -->
        <div class="hidden md:flex flex-col justify-between p-10 bg-gradient-to-br from-blue-700 via-blue-600 to-indigo-700 text-white">
            <div class="flex items-center gap-3">
                <div class="bg-white/15 rounded-xl p-3">
                    <i class="fa-solid fa-boxes-stacked text-2xl"></i>
                </div>
                <div>
                    <div class="text-2xl font-extrabold tracking-tight">BIGBIO Manager</div>
                    <div class="text-white/80 text-sm">Panel obsługi zamówień</div>
                </div>
            </div>

            <div class="max-w-md">
                <div class="text-4xl font-extrabold leading-tight">Zaloguj się do panelu sprzedawcy</div>
                <div class="mt-4 text-white/80 leading-relaxed">
                    Użyj danych logowania pracownika PrestaShop (tych samych, co do zaplecza).
                </div>
                <div class="mt-6 text-xs text-white/70">
                    Dostęp jest ograniczony tylko do pracowników. Po zalogowaniu zobaczysz swoje imię w prawym górnym rogu.
                </div>
            </div>

            <div class="text-white/60 text-xs">
                © BigBio • Order Manager
            </div>
        </div>

        <!-- Right panel (form) -->
        <div class="flex items-center justify-center p-6 md:p-10 bg-white">
            <div class="w-full max-w-md">
                <div class="md:hidden mb-8">
                    <div class="flex items-center gap-3">
                        <div class="bg-blue-600 text-white rounded-xl p-3">
                            <i class="fa-solid fa-boxes-stacked text-2xl"></i>
                        </div>
                        <div>
                            <div class="text-xl font-extrabold tracking-tight text-slate-800">BIGBIO Manager</div>
                            <div class="text-slate-500 text-sm">Panel obsługi zamówień</div>
                        </div>
                    </div>
                </div>

                <h2 class="text-2xl font-extrabold text-slate-800">Logowanie</h2>
                <p class="mt-2 text-sm text-slate-500">
                    Zaloguj się kontem pracownika PrestaShop.
                </p>

                <div v-if="loginError" class="mt-5 p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
                    <i class="fa-solid fa-triangle-exclamation mr-2"></i>{{ loginError }}
                </div>

                <form class="mt-6 space-y-4" @submit.prevent="doLogin">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Email</label>
                        <input v-model.trim="loginEmail" type="email" autocomplete="username" required
                               class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition"
                               placeholder="np. admin@twojsklep.pl" />
                    </div>
                    <div>
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Hasło</label>
                        </div>
                        <input v-model="loginPassword" type="password" autocomplete="current-password" required
                               class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition"
                               placeholder="••••••••" />
                    </div>

                    <button type="submit"
                            class="w-full mt-2 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg shadow-sm transition flex items-center justify-center gap-2"
                            :disabled="loggingIn">
                        <i v-if="loggingIn" class="fa-solid fa-circle-notch spin"></i>
                        <span>{{ loggingIn ? 'Logowanie…' : 'Zaloguj się' }}</span>
                    </button>
                </form>

                <div class="mt-6 text-xs text-slate-400 leading-relaxed">
                    Jeśli nie pamiętasz hasła, zresetuj je w panelu administracyjnym PrestaShop.
                </div>
            </div>
        </div>
    </div>
</div>
{/literal}
