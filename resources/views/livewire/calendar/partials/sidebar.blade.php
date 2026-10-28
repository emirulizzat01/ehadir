<aside class="w-full lg:w-64 shrink-0 space-y-6">
    <!-- Quick Create Button (Google Calendar "+ Create" style) -->
    @if (auth()->user()?->hasPermission('calendar.manage'))
        <button
            type="button"
            @click="$dispatch('open-slide-over', { name: 'slideover-create-event' })"
            class="inline-flex items-center justify-center gap-2.5 w-full px-5 py-3 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm shadow-sm hover:shadow-md transition-all active:scale-[0.98] cursor-pointer"
        >
            <x-icon name="plus" class="w-5 h-5 stroke-[2.5]" />
            <span>Jadualkan Aktiviti</span>
        </button>
    @endif

    <!-- Mini Calendar Datepicker (Google Calendar Mini Month) -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
        <div class="flex items-center justify-between mb-3 px-1">
            <h3 class="text-xs font-bold text-slate-900 dark:text-white capitalize">
                {{ $miniMonthTitle }}
            </h3>
            <div class="flex items-center gap-1">
                <button
                    type="button"
                    wire:click="previousMiniMonth"
                    class="p-1 rounded-lg text-slate-500 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                    title="Bulan Sebelumnya"
                >
                    <x-icon name="chevron-left" class="w-3.5 h-3.5" />
                </button>
                <button
                    type="button"
                    wire:click="nextMiniMonth"
                    class="p-1 rounded-lg text-slate-500 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                    title="Bulan Seterusnya"
                >
                    <x-icon name="chevron-right" class="w-3.5 h-3.5" />
                </button>
            </div>
        </div>

        <!-- Weekday initials -->
        <div class="grid grid-cols-7 text-center mb-1 text-[11px] font-semibold text-slate-400 dark:text-slate-500">
            <span>I</span>
            <span>S</span>
            <span>R</span>
            <span>K</span>
            <span>J</span>
            <span>S</span>
            <span>A</span>
        </div>

        <!-- Days grid -->
        <div class="space-y-0.5">
            @foreach ($miniWeeks as $week)
                <div class="grid grid-cols-7 text-center gap-0.5">
                    @foreach ($week as $day)
                        <button
                            type="button"
                            wire:click="selectDate('{{ $day['date'] }}')"
                            class="relative h-7 w-7 mx-auto flex flex-col items-center justify-center rounded-full text-xs font-medium transition-colors
                                @if ($day['isSelected'])
                                    bg-emerald-600 text-white font-bold shadow-xs
                                @elseif ($day['isToday'])
                                    border border-emerald-600 text-emerald-600 dark:text-emerald-400 font-bold hover:bg-emerald-50 dark:hover:bg-emerald-950/40
                                @elseif (! $day['isCurrentMonth'])
                                    text-slate-300 dark:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800
                                @else
                                    text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800
                                @endif
                            "
                        >
                            <span>{{ $day['dayNumber'] }}</span>
                            @if ($day['hasEvents'] && ! $day['isSelected'])
                                <span class="absolute bottom-0.5 w-1 h-1 rounded-full bg-emerald-500"></span>
                            @endif
                        </button>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>

    <!-- Category Filters (Google Calendar "My Calendars" Toggles) -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs space-y-3">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
            <span class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                Kategori Aktiviti
            </span>
            <button
                type="button"
                wire:click="toggleAllTypes"
                class="text-[11px] font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
            >
                {{ count($selectedTypes) === 5 ? 'Batal Semua' : 'Pilih Semua' }}
            </button>
        </div>

        <div class="space-y-1.5 text-xs">
            @php
                $categories = [
                    'MESYUARAT' => ['label' => 'Mesyuarat', 'color' => 'bg-purple-500', 'border' => 'border-purple-500', 'text' => 'text-purple-600 dark:text-purple-400'],
                    'BENGKEL' => ['label' => 'Bengkel', 'color' => 'bg-sky-500', 'border' => 'border-sky-500', 'text' => 'text-sky-600 dark:text-sky-400'],
                    'KURSUS' => ['label' => 'Kursus', 'color' => 'bg-amber-500', 'border' => 'border-amber-500', 'text' => 'text-amber-600 dark:text-amber-400'],
                    'LAWATAN_TAPAK' => ['label' => 'Lawatan Tapak', 'color' => 'bg-emerald-500', 'border' => 'border-emerald-500', 'text' => 'text-emerald-600 dark:text-emerald-400'],
                    'LAIN_LAIN' => ['label' => 'Lain-lain', 'color' => 'bg-slate-500', 'border' => 'border-slate-500', 'text' => 'text-slate-600 dark:text-slate-400'],
                ];
            @endphp

            @foreach ($categories as $catKey => $catInfo)
                @php
                    $isChecked = in_array($catKey, $selectedTypes);
                @endphp
                <button
                    type="button"
                    wire:click="toggleType('{{ $catKey }}')"
                    class="w-full flex items-center justify-between p-1.5 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800/60 transition-colors group text-left cursor-pointer"
                >
                    <div class="flex items-center gap-2.5">
                        <span class="w-3.5 h-3.5 rounded-md flex items-center justify-center transition-colors {{ $isChecked ? $catInfo['color'] : 'border-2 ' . $catInfo['border'] }}">
                            @if ($isChecked)
                                <x-icon name="check" class="w-2.5 h-2.5 text-white stroke-[3]" />
                            @endif
                        </span>
                        <span class="font-medium {{ $isChecked ? 'text-slate-900 dark:text-white' : 'text-slate-400 dark:text-slate-500 line-through' }}">
                            {{ $catInfo['label'] }}
                        </span>
                    </div>
                </button>
            @endforeach
        </div>
    </div>

    <!-- Scoped Bahagian Filter -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs space-y-3">
        <label for="filterBahagian" class="block text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
            Penapis Bahagian
        </label>
        <select
            id="filterBahagian"
            wire:model.live="selectedBahagianId"
            class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-emerald-500 py-2 px-2.5"
        >
            <option value="">Semua Bahagian</option>
            @foreach ($bahagianList as $b)
                <option value="{{ $b->id }}">{{ $b->nama }} ({{ $b->kod }})</option>
            @endforeach
        </select>

        @if ($selectedBahagianId)
            <button
                type="button"
                wire:click="$set('selectedBahagianId', null)"
                class="text-[11px] font-semibold text-rose-600 hover:text-rose-700 dark:text-rose-400 flex items-center gap-1"
            >
                <x-icon name="x-mark" class="w-3 h-3" />
                <span>Kosongkan Penapis</span>
            </button>
        @endif
    </div>

    <!-- "Aktiviti Saya Sahaja" Toggle -->
    @if (auth()->check())
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <button
                type="button"
                wire:click="toggleOnlyMyEvents"
                class="w-full flex items-center justify-between text-left cursor-pointer"
            >
                <div>
                    <span class="text-xs font-bold text-slate-900 dark:text-white block">
                        Aktiviti Saya Sahaja
                    </span>
                    <span class="text-[11px] text-slate-500 dark:text-slate-400">
                        Acara di mana saya terlibat
                    </span>
                </div>
                <div class="w-9 h-5 rounded-full transition-colors relative flex items-center px-0.5 {{ $onlyMyEvents ? 'bg-emerald-600' : 'bg-slate-300 dark:bg-slate-700' }}">
                    <span class="w-4 h-4 rounded-full bg-white transition-transform transform {{ $onlyMyEvents ? 'translate-x-4' : 'translate-x-0' }}"></span>
                </div>
            </button>
        </div>

        <!-- Langganan Kalendar (Tier 2 Tokenized ICS Feed) -->
        @php
            $feedUrl = auth()->user()->getCalendarFeedUrl();
        @endphp
        <div
            x-data="{
                copied: false,
                showGuide: false,
                copyFeedUrl() {
                    navigator.clipboard.writeText('{{ $feedUrl }}');
                    this.copied = true;
                    setTimeout(() => { this.copied = false; }, 2500);
                }
            }"
            class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs space-y-3"
        >
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-1.5">
                    <x-icon name="calendar" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
                    <span>Langganan Kalendar</span>
                </span>
                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-md bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300">
                    Tier 2 ICS
                </span>
            </div>

            <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">
                Segerakkan semua aktiviti eHadir ke Google Calendar, Outlook atau telefon pintar anda secara automatik.
            </p>

            <!-- 1-Click Copy Button -->
            <button
                type="button"
                @click="copyFeedUrl()"
                class="w-full flex items-center justify-center gap-2 py-2 px-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 hover:bg-slate-100 dark:bg-slate-800 dark:hover:bg-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-200 transition-colors shadow-2xs cursor-pointer"
            >
                <template x-if="!copied">
                    <span class="flex items-center gap-1.5">
                        <x-icon name="download" class="w-3.5 h-3.5 text-emerald-600" />
                        <span>Salin Pautan ICS</span>
                    </span>
                </template>
                <template x-if="copied">
                    <span class="flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400 font-bold">
                        <x-icon name="check" class="w-3.5 h-3.5 stroke-[3]" />
                        <span>Pautan Berjaya Disalin!</span>
                    </span>
                </template>
            </button>

            <!-- How to use & Regenerate -->
            <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-[11px]">
                <button
                    type="button"
                    @click="showGuide = !showGuide"
                    class="font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 cursor-pointer"
                >
                    Panduan Google Calendar
                </button>

                <form method="POST" action="{{ route('calendar.feed.regenerate') }}" onsubmit="return confirm('Jana semula pautan baharu? Pautan lama di Google Calendar akan terbatal.');">
                    @csrf
                    <button
                        type="submit"
                        class="text-slate-400 hover:text-rose-600 transition-colors cursor-pointer"
                        title="Jana semula token peribadi"
                    >
                        Jana Semula
                    </button>
                </form>
            </div>

            <!-- Guide Dropdown -->
            <div
                x-show="showGuide"
                x-cloak
                class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-[11px] text-slate-600 dark:text-slate-300 space-y-1.5"
            >
                <p class="font-bold text-slate-900 dark:text-white">Langkah di Google Calendar:</p>
                <ol class="list-decimal list-inside space-y-1 text-slate-500 dark:text-slate-400">
                    <li>Buka Google Calendar di komputer</li>
                    <li>Di sebelah kiri, klik <strong>+</strong> di sebelah <em>Other calendars</em></li>
                    <li>Pilih <strong>From URL</strong></li>
                    <li>Tampal pautan ICS yang telah disalin dan klik <strong>Add calendar</strong></li>
                </ol>
            </div>
        </div>
    @endif
</aside>
