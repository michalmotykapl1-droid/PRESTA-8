{literal}
<aside class="w-72 bg-white h-full flex flex-col border-r border-slate-200 shadow-xl z-20 shrink-0">
    <div class="p-5 bg-slate-50 border-b border-slate-200 flex items-center gap-3">
        <div class="bg-blue-600 text-white p-1.5 rounded-lg shadow-sm"><i class="fa-solid fa-boxes-stacked"></i></div>
        <div><h1 class="font-bold text-lg text-slate-800 leading-tight">BIGBIO <span class="text-blue-600">Manager</span></h1></div>
    </div>
    <div class="flex-1 overflow-y-auto py-3 space-y-4 custom-scrollbar">
        <div v-for="(section, sIndex) in menu" :key="sIndex">
            <div v-if="section.title" class="px-5 py-1 flex justify-between items-center mb-1"><span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ section.title }}</span></div>
            <ul>
                <li v-for="item in section.items" @click="activeFolder = item.label" :class="{'bg-slate-200 font-bold border-r-4 border-blue-600': activeFolder === item.label, 'hover:bg-slate-50': activeFolder !== item.label}" class="px-5 py-2 flex justify-between items-center text-sm transition-colors text-slate-600 cursor-pointer">
                    <div class="flex items-center gap-3 truncate">
                        <div class="w-2 h-2 rounded-full shadow-sm" :style="{backgroundColor: item.color_hex || '#cbd5e1'}"></div>
                        <span class="truncate" :class="{'text-slate-400': getFolderCount(item.label) === 0}">{{ item.label }}</span>
                    </div>
                    <span class="text-[10px] px-1.5 py-0.5 rounded font-bold min-w-[24px] text-center" :class="getBadgeClass(getFolderCount(item.label), item.isError)">{{ getFolderCount(item.label) }}</span>
                </li>
            </ul>
            <div v-if="sIndex < menu.length - 1" class="mx-5 border-b border-slate-100 mt-3"></div>
        </div>
    </div>
</aside>
{/literal}