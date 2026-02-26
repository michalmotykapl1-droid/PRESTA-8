{literal}
<transition name="toast">
    <div v-if="toast.show" class="fixed bottom-10 left-1/2 transform -translate-x-1/2 px-6 py-3 rounded-full shadow-2xl z-[100] flex items-center gap-3 pointer-events-none"
         :class="toast.type === 'error' ? 'bg-red-600 text-white' : 'bg-slate-800 text-white'">
        <i class="fa-solid text-lg" :class="toast.type === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check text-green-400'"></i>
        <span class="font-bold text-sm tracking-wide">{{ toast.message }}</span>
    </div>
</transition>
{/literal}