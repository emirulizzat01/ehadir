<div
    x-data="whatsappSummaryComponent({
        date: '{{ $date }}',
        bahagianId: '{{ request('bahagian_id', '') }}',
        unitId: '{{ request('unit_id', '') }}'
    })"
    x-init="init()"
>
    <!-- Trigger Button -->
    <x-ui.button
        type="button"
        @click="openModal()"
        variant="secondary"
        size="sm"
        class="border-emerald-300 dark:border-emerald-700 hover:border-emerald-500 text-emerald-800 dark:text-emerald-300 hover:bg-emerald-50/50 dark:hover:bg-emerald-950/40"
        title="Jana ringkasan untuk WhatsApp"
    >
        <x-icon name="whatsapp" class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
        <span>Ringkasan WhatsApp</span>
    </x-ui.button>

    <!-- Modal -->
    <x-ui.modal name="modal-whatsapp-summary" maxWidth="2xl">
        <div class="p-6 space-y-5 text-left">
            <!-- Modal Header -->
            <div class="flex items-start justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-950/80 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 border border-emerald-200 dark:border-emerald-800">
                        <x-icon name="whatsapp" class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                            Ringkasan Kehadiran WhatsApp
                            <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800" x-text="scopeLabel || 'Memuatkan...'"></span>
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Salin teks diformatkan untuk perkongsian ke kumpulan WhatsApp rasmi.
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="$dispatch('close-modal', { name: 'modal-whatsapp-summary' })"
                    class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded-lg"
                >
                    <x-icon name="x-mark" class="w-5 h-5" />
                </button>
            </div>

            <!-- Mode Selector Pills -->
            <div class="flex flex-wrap items-center justify-between gap-2 bg-slate-50 dark:bg-slate-800/60 p-2 rounded-xl border border-slate-200/80 dark:border-slate-700/80">
                <div class="flex items-center gap-1.5 text-xs">
                    <span class="font-medium text-slate-600 dark:text-slate-300 ml-1">Format Teks:</span>
                </div>
                <div class="inline-flex rounded-lg p-0.5 bg-slate-200/70 dark:bg-slate-900/80 text-xs font-medium">
                    <button
                        type="button"
                        @click="setFormat('exceptions')"
                        :class="format === 'exceptions' ? 'bg-white dark:bg-slate-800 text-emerald-700 dark:text-emerald-400 shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100'"
                        class="px-3 py-1.5 rounded-md transition-all flex items-center gap-1.5"
                    >
                        <span>Pengecualian Sahaja</span>
                        <span class="text-[10px] px-1.5 py-0.2 rounded-sm bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 font-bold">Disyorkan</span>
                    </button>
                    <button
                        type="button"
                        @click="setFormat('full')"
                        :class="format === 'full' ? 'bg-white dark:bg-slate-800 text-emerald-700 dark:text-emerald-400 shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100'"
                        class="px-3 py-1.5 rounded-md transition-all"
                    >
                        Senarai Penuh
                    </button>
                    <button
                        type="button"
                        @click="setFormat('stats')"
                        :class="format === 'stats' ? 'bg-white dark:bg-slate-800 text-emerald-700 dark:text-emerald-400 shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100'"
                        class="px-3 py-1.5 rounded-md transition-all"
                    >
                        Statistik Sahaja
                    </button>
                </div>
            </div>

            <!-- Stats Chips -->
            <template x-if="stats">
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 text-center text-xs">
                    <div class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700">
                        <span class="text-slate-400 block text-[10px] uppercase font-semibold">Jumlah</span>
                        <span class="font-bold text-slate-800 dark:text-slate-100 text-sm" x-text="stats.total"></span>
                    </div>
                    <div class="p-2 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/50">
                        <span class="text-emerald-600 dark:text-emerald-400 block text-[10px] uppercase font-semibold">Pejabat</span>
                        <span class="font-bold text-emerald-700 dark:text-emerald-300 text-sm" x-text="stats.pejabat"></span>
                    </div>
                    <div class="p-2 rounded-lg bg-sky-50 dark:bg-sky-950/40 border border-sky-200 dark:border-sky-800/50">
                        <span class="text-sky-600 dark:text-sky-400 block text-[10px] uppercase font-semibold">Tugas Luar</span>
                        <span class="font-bold text-sky-700 dark:text-sky-300 text-sm" x-text="stats.duty"></span>
                    </div>
                    <div class="p-2 rounded-lg bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/50">
                        <span class="text-amber-600 dark:text-amber-400 block text-[10px] uppercase font-semibold">Cuti/MC</span>
                        <span class="font-bold text-amber-700 dark:text-amber-300 text-sm" x-text="stats.leave"></span>
                    </div>
                    <div class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 col-span-2 sm:col-span-1">
                        <span class="text-slate-400 block text-[10px] uppercase font-semibold">Kadar Hadir</span>
                        <span class="font-bold text-slate-800 dark:text-slate-100 text-sm" x-text="stats.presence_rate + '%'"></span>
                    </div>
                </div>
            </template>

            <!-- WhatsApp Message Preview Bubble -->
            <div class="relative">
                <div class="rounded-xl border border-emerald-500/30 bg-[#efeae2]/40 dark:bg-slate-900/80 p-4 max-h-72 overflow-y-auto">
                    <div x-show="loading" class="flex flex-col items-center justify-center py-10 text-slate-400 gap-2">
                        <div class="w-6 h-6 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin"></div>
                        <span class="text-xs">Menjana ringkasan kehadiran...</span>
                    </div>
                    <pre
                        x-show="!loading"
                        x-text="summaryText"
                        class="font-mono text-xs text-slate-800 dark:text-slate-200 whitespace-pre-wrap select-all leading-relaxed"
                    ></pre>
                </div>
            </div>

            <!-- Action Buttons Footer -->
            <div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-slate-100 dark:border-slate-800">
                <div class="text-xs text-slate-400">
                    <span x-show="copied" class="text-emerald-600 dark:text-emerald-400 font-semibold flex items-center gap-1">
                        <x-icon name="check" class="w-4 h-4" />
                        Teks berjaya disalin ke papan keratan (clipboard)!
                    </span>
                    <span x-show="!copied">
                        Klik 'Salin Teks' dan tampal (paste) terus ke dalam WhatsApp.
                    </span>
                </div>

                <div class="flex items-center gap-2">
                    <x-ui.button
                        type="button"
                        @click="$dispatch('close-modal', { name: 'modal-whatsapp-summary' })"
                        variant="ghost"
                        size="sm"
                    >
                        Tutup
                    </x-ui.button>

                    <a
                        :href="whatsappShareUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold border border-emerald-300 dark:border-emerald-700 text-emerald-700 dark:text-emerald-300 bg-white dark:bg-slate-800 hover:bg-emerald-50 dark:hover:bg-emerald-950 transition-colors"
                        title="Buka terus di WhatsApp Web atau aplikasi mudah alih"
                    >
                        <x-icon name="whatsapp" class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                        <span>Buka WhatsApp</span>
                    </a>

                    <button
                        type="button"
                        @click="copyText()"
                        :disabled="loading || !summaryText"
                        :class="copied ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : 'bg-emerald-600 hover:bg-emerald-700 text-white'"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-semibold shadow-xs transition-all cursor-pointer disabled:opacity-50"
                    >
                        <template x-if="!copied">
                            <span class="inline-flex items-center gap-1.5">
                                <x-icon name="clipboard" class="w-4 h-4" />
                                <span>Salin Teks</span>
                            </span>
                        </template>
                        <template x-if="copied">
                            <span class="inline-flex items-center gap-1.5">
                                <x-icon name="check" class="w-4 h-4" />
                                <span>Disalin!</span>
                            </span>
                        </template>
                    </button>
                </div>
            </div>
        </div>
    </x-ui.modal>
</div>

<script>
    function whatsappSummaryComponent(config) {
        return {
            date: config.date,
            bahagianId: config.bahagianId || '',
            unitId: config.unitId || '',
            format: 'exceptions',
            loading: false,
            copied: false,
            summaryText: '',
            scopeLabel: '',
            stats: null,

            init() {
                // Initial fetch
            },

            openModal() {
                this.fetchSummary();
                this.$dispatch('open-modal', { name: 'modal-whatsapp-summary' });
            },

            setFormat(newFormat) {
                this.format = newFormat;
                this.fetchSummary();
            },

            async fetchSummary() {
                this.loading = true;
                this.copied = false;
                try {
                    const params = new URLSearchParams({
                        tarikh: this.date,
                        format: this.format,
                    });
                    if (this.bahagianId) params.append('bahagian_id', this.bahagianId);
                    if (this.unitId) params.append('unit_id', this.unitId);

                    const res = await fetch(`{{ route('attendance.whatsapp-summary') }}?${params.toString()}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.summaryText = data.text;
                        this.scopeLabel = data.scope_label;
                        this.stats = data.stats;
                    }
                } catch (err) {
                    console.error('Ralat memuatkan ringkasan WhatsApp:', err);
                } finally {
                    this.loading = false;
                }
            },

            async copyText() {
                if (!this.summaryText) return;
                try {
                    if (navigator.clipboard && window.isSecureContext) {
                        await navigator.clipboard.writeText(this.summaryText);
                    } else {
                        const textarea = document.createElement('textarea');
                        textarea.value = this.summaryText;
                        textarea.style.position = 'fixed';
                        textarea.style.left = '-999999px';
                        document.body.appendChild(textarea);
                        textarea.focus();
                        textarea.select();
                        document.execCommand('copy');
                        textarea.remove();
                    }
                    this.copied = true;
                    setTimeout(() => {
                        this.copied = false;
                    }, 2500);
                } catch (err) {
                    console.error('Gagal menyalin teks:', err);
                }
            },

            get whatsappShareUrl() {
                return 'https://api.whatsapp.com/send?text=' + encodeURIComponent(this.summaryText || '');
            }
        };
    }
</script>

