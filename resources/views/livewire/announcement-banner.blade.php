@if($announcements && $announcements->count() > 0)
<div
    x-data="{
        open: true,
        detailsOpen: false,
        detailsAnn: null,
        detailsImageIndex: 0,
        currentIndex: 0,
        imageIndex: 0,
        announcements: @js($announcements->values()->toArray()),

        get current() {
            return this.announcements[this.currentIndex] ?? null;
        },
        openDetails(ann) {
            this.detailsAnn = ann;
            this.detailsImageIndex = 0;
            this.detailsOpen = true;
        },
        closeDetails() {
            this.detailsOpen = false;
        },
        goNext() {
            this.imageIndex = 0;
            this.currentIndex = (this.currentIndex + 1) % this.announcements.length;
        },
        goPrev() {
            this.imageIndex = 0;
            this.currentIndex = (this.currentIndex - 1 + this.announcements.length) % this.announcements.length;
        },
        goTo(idx) {
            this.imageIndex = 0;
            this.currentIndex = idx;
        },
        dismiss(id) {
            this.announcements = this.announcements.filter(a => a.id !== id);
            if (this.announcements.length === 0) {
                this.open = false;
                return;
            }
            if (this.currentIndex >= this.announcements.length) {
                this.currentIndex = 0;
            }
            this.imageIndex = 0;

            fetch('/api/announcements/' + id + '/dismiss', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept': 'application/json'
                }
            }).catch(() => {});
        },
        getAllImages(announcement) {
            if (!announcement) return [];
            let images = [];
            if (announcement.secure_image_url) images.push(announcement.secure_image_url);
            if (announcement.images && Array.isArray(announcement.images)) {
                announcement.images.forEach(img => {
                    if (img && img.secure_url) images.push(img.secure_url);
                });
            }
            return images;
        },
        getBannerBg(announcement) {
            if (!announcement) return '';
            let imgs = this.getAllImages(announcement);
            if (imgs.length > 0) {
                return 'background-image: url(' + imgs[0] + '); background-size: cover; background-position: center;';
            }
            return '';
        },
        typeGradient(type) {
            return {
                info:    'from-blue-500 to-indigo-700',
                warning: 'from-amber-400 to-orange-600',
                success: 'from-emerald-500 to-teal-700',
                danger:  'from-red-500 to-rose-700',
            }[type] || 'from-zinc-500 to-zinc-700';
        }
    }"
    wire:ignore
>
    {{-- ── Modal Backdrop ── --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="display:none;"
    >
        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-black/50"></div>

        {{-- ── Modal Panel ── --}}
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-4"
            class="relative z-10 w-full max-w-2xl bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl overflow-hidden"
            style="display:none;"
            @click.stop
        >
            {{-- ── Hero Image / Gradient (per slide) ── --}}
            <div class="relative h-52 overflow-hidden">
                <template x-for="(ann, index) in announcements" :key="'hero-' + ann.id">
                    <div
                        x-show="currentIndex === index"
                        x-transition:enter="transition ease-out duration-500"
                        x-transition:enter-start="opacity-0 scale-105"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-300"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="absolute inset-0 bg-gradient-to-br"
                        :class="typeGradient(ann.type)"
                        :style="getBannerBg(ann)"
                        style="display:none;"
                    >
                        {{-- Overlay --}}
                        <div class="absolute inset-0" :class="getAllImages(ann).length > 0 ? 'bg-black/50' : 'bg-black/20'"></div>

                        {{-- Slide content in hero --}}
                        <div class="relative h-full flex flex-col justify-between p-6">
                            {{-- Badges row --}}
                            <div class="flex items-center gap-2 flex-wrap">
                                <template x-if="ann.priority === 'urgent'">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-red-500 text-white uppercase tracking-wide">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                        Urgent
                                    </span>
                                </template>
                                <template x-if="ann.priority === 'high'">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-400/90 text-amber-900 uppercase tracking-wide">
                                        High Priority
                                    </span>
                                </template>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-white/20 text-white capitalize" x-text="ann.type"></span>
                            </div>

                            {{-- Title --}}
                            <div>
                                <h2 class="text-2xl font-bold text-white drop-shadow-sm line-clamp-2 leading-tight" x-text="ann.title"></h2>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- Prev/Next arrows on hero --}}
                <template x-if="announcements.length > 1">
                    <div>
                        <button @click="goPrev()" type="button" class="absolute left-3 top-1/2 -translate-y-1/2 w-9 h-9 flex items-center justify-center rounded-full bg-black/35 hover:bg-black/60 text-white transition-colors z-10">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <button @click="goNext()" type="button" class="absolute right-3 top-1/2 -translate-y-1/2 w-9 h-9 flex items-center justify-center rounded-full bg-black/35 hover:bg-black/60 text-white transition-colors z-10">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </template>

                {{-- Close button --}}
                <button @click="open = false" type="button" class="absolute top-3 right-3 w-8 h-8 flex items-center justify-center rounded-full bg-black/35 hover:bg-black/60 text-white transition-colors z-10">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>

                {{-- Dot indicators --}}
                <div x-show="announcements.length > 1" class="absolute bottom-3 left-1/2 -translate-x-1/2 flex items-center gap-1.5 z-10">
                    <template x-for="(ann, idx) in announcements" :key="'dot-' + ann.id">
                        <button
                            @click="goTo(idx)"
                            type="button"
                            :class="currentIndex === idx ? 'w-6 bg-white' : 'w-2 bg-white/45 hover:bg-white/70'"
                            class="h-2 rounded-full transition-all duration-300"
                        ></button>
                    </template>
                </div>

                {{-- Slide counter --}}
                <div x-show="announcements.length > 1" class="absolute top-3 left-3 px-2 py-1 bg-black/40 text-white text-xs rounded-lg font-medium z-10">
                    <span x-text="currentIndex + 1"></span><span class="opacity-60"> / </span><span x-text="announcements.length"></span>
                </div>
            </div>

            {{-- ── Body ── --}}
            <template x-for="(ann, index) in announcements" :key="'body-' + ann.id">
                <div x-show="currentIndex === index" class="p-6" style="display:none;">

                    {{-- Content text --}}
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed text-sm whitespace-pre-line line-clamp-4" x-text="ann.content"></p>


                    {{-- Action row --}}
                    <div class="flex flex-col gap-3 mt-5 pt-4 border-t border-gray-100 dark:border-zinc-800 sm:flex-row sm:items-center sm:justify-between">
                        <button
                            @click="dismiss(ann.id)"
                            type="button"
                            class="inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-red-500 dark:text-zinc-500 dark:hover:text-red-400 transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            Dismiss
                        </button>

                        <div class="flex items-center gap-2 flex-wrap">
                            <flux:button variant="ghost" size="sm" @click="open = false">Close</flux:button>
                            <flux:button variant="filled" size="sm" @click="openDetails(ann)">
                                View Details
                            </flux:button>
                            <template x-if="announcements.length > 1">
                                <flux:button variant="primary" size="sm" @click="goNext()">
                                    Next
                                    <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                                </flux:button>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- ── Details Panel (slides over the carousel modal) ── --}}
    <div
        x-show="detailsOpen"
        x-transition:enter="transition ease-out duration-250"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-60 flex items-center justify-center p-4"
        style="display:none;"
    >
        <div class="absolute inset-0 bg-black/55" @click="closeDetails()"></div>

        <div
            x-show="detailsOpen"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95 translate-y-4"
            class="relative z-10 w-full max-w-2xl bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col"
            style="display:none;"
            @click.stop
        >
            {{-- Details header --}}
            <div class="flex items-start justify-between gap-3 p-6 pb-4 flex-shrink-0">
                <div class="flex-1">
                    <div class="flex items-center gap-2 flex-wrap mb-1">
                        <template x-if="detailsAnn && detailsAnn.priority === 'urgent'">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-red-500 text-white uppercase tracking-wide">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                Urgent
                            </span>
                        </template>
                        <template x-if="detailsAnn && detailsAnn.priority === 'high'">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-400/90 text-amber-900 uppercase tracking-wide">High Priority</span>
                        </template>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white leading-snug" x-text="detailsAnn ? detailsAnn.title : ''"></h2>
                </div>
                <button @click="closeDetails()" type="button" class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 hover:bg-gray-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-gray-500 dark:text-gray-400 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="border-t border-gray-100 dark:border-zinc-800"></div>

            {{-- Scrollable body --}}
            <div class="overflow-y-auto flex-1 p-6 pt-5 space-y-5">

                {{-- Image carousel --}}
                <template x-if="detailsAnn && getAllImages(detailsAnn).length > 0">
                    <div class="relative">
                        <div class="relative h-72 overflow-hidden rounded-xl border border-gray-200 dark:border-zinc-700 group bg-gray-50 dark:bg-zinc-800">
                            <template x-for="(imgUrl, idx) in getAllImages(detailsAnn)" :key="'dimg-' + idx">
                                <div
                                    x-show="detailsImageIndex === idx"
                                    x-transition:enter="transition ease-out duration-300"
                                    x-transition:enter-start="opacity-0"
                                    x-transition:enter-end="opacity-100"
                                    class="absolute inset-0 flex items-center justify-center cursor-pointer"
                                    style="display:none;"
                                    @click="window.open(imgUrl, '_blank')"
                                    title="Click to view full size"
                                >
                                    <img :src="imgUrl" :alt="'Image ' + (idx + 1)" class="max-w-full max-h-full object-contain">
                                    <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                        <span class="bg-white dark:bg-zinc-800 rounded-lg px-3 py-2 shadow-lg flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 font-medium">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/></svg>
                                            View full size
                                        </span>
                                    </div>
                                </div>
                            </template>

                            {{-- Image nav arrows --}}
                            <template x-if="getAllImages(detailsAnn).length > 1">
                                <div>
                                    <button @click="detailsImageIndex = detailsImageIndex === 0 ? getAllImages(detailsAnn).length - 1 : detailsImageIndex - 1" type="button" class="absolute left-2 top-1/2 -translate-y-1/2 w-9 h-9 flex items-center justify-center rounded-full bg-black/40 hover:bg-black/65 text-white transition-colors z-10">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                                    </button>
                                    <button @click="detailsImageIndex = detailsImageIndex === getAllImages(detailsAnn).length - 1 ? 0 : detailsImageIndex + 1" type="button" class="absolute right-2 top-1/2 -translate-y-1/2 w-9 h-9 flex items-center justify-center rounded-full bg-black/40 hover:bg-black/65 text-white transition-colors z-10">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                                    </button>
                                    <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-1.5 z-10">
                                        <template x-for="(imgUrl, idx) in getAllImages(detailsAnn)" :key="'ddot-' + idx">
                                            <button @click="detailsImageIndex = idx" type="button" :class="detailsImageIndex === idx ? 'bg-white w-5' : 'bg-white/50 hover:bg-white/75 w-2'" class="h-2 rounded-full transition-all duration-300"></button>
                                        </template>
                                    </div>
                                    <div class="absolute top-2.5 right-3 px-2 py-1 bg-black/55 text-white text-xs rounded-lg">
                                        <span x-text="detailsImageIndex + 1"></span> / <span x-text="getAllImages(detailsAnn).length"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- Full content --}}
                <p class="text-gray-700 dark:text-gray-300 leading-relaxed text-sm whitespace-pre-line" x-text="detailsAnn ? detailsAnn.content : ''"></p>
            </div>

            {{-- Details footer --}}
            <div class="border-t border-gray-100 dark:border-zinc-800 px-6 py-4 flex items-center justify-between flex-shrink-0">
                <button
                    @click="detailsAnn && dismiss(detailsAnn.id); closeDetails()"
                    type="button"
                    class="inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-red-500 dark:text-zinc-500 dark:hover:text-red-400 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Dismiss
                </button>
                <flux:button variant="ghost" size="sm" @click="closeDetails()">Back</flux:button>
            </div>
        </div>
    </div>
</div>
@endif
