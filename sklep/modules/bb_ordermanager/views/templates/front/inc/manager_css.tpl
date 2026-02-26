<style>
    /* --- FIX: Ukrywanie nieprzetworzonych tagów Vue podczas ładowania --- */
    [v-cloak] { display: none !important; }

    /* Ogólne ustawienia */
    body { margin: 0; padding: 0; overflow: hidden; background-color: #f1f5f9; font-family: 'Open Sans', sans-serif; }
    
    /* Pasek przewijania */
    .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    
    /* Animacje */
    .spin { animation: spin 1s linear infinite; }
    @keyframes spin { 100% { transform: rotate(360deg); } }
    
    .fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
    .fade-enter-from, .fade-leave-to { opacity: 0; }
    
    .slide-in-from-right { animation: slideInRight 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
    @keyframes slideInRight { from { transform: translateX(100%); } to { transform: translateX(0); } }
    
    .toast-enter-active, .toast-leave-active { transition: all 0.3s ease; }
    .toast-enter-from, .toast-leave-to { opacity: 0; transform: translate(-50%, 20px); }

    /* Tabela */
    th.sortable { cursor: pointer; user-select: none; transition: color 0.2s; }
    th.sortable:hover { color: #2563eb; }
</style>