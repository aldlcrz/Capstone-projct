@props([
    'tourId' => 'default',
    'steps' => [],
    'autoStart' => true,
    'userId' => Auth::id() ?? 'guest',
])

<script type="application/json" id="spotlight-tour-data-{{ $tourId }}">
{!! json_encode([
    'tourId' => $tourId,
    'userId' => (string)$userId,
    'steps' => $steps,
    'autoStart' => (bool)$autoStart
]) !!}
</script>

<script>
(function() {
    window.startSpotlightTour = window.startSpotlightTour || function(tourId) {
        window.dispatchEvent(new CustomEvent('start-spotlight-tour', { detail: { tourId: tourId } }));
    };

    if (!window.spotlightTourEngine) {
        window.spotlightTourEngine = function(config) {
            return {
                tourId: (config && config.tourId) ? config.tourId : 'default',
                userId: (config && config.userId) ? config.userId : 'guest',
                steps: (config && Array.isArray(config.steps)) ? config.steps : [],
                autoStart: (config && typeof config.autoStart !== 'undefined') ? Boolean(config.autoStart) : true,
                
                isActive: false,
                currentStepIndex: 0,
                targetRect: { x: 0, y: 0, width: 0, height: 0 },
                tooltipPos: { x: 0, y: 0 },
                arrowCurvePath: '',

                get currentStep() {
                    if (!this.steps || this.steps.length === 0) return {};
                    return this.steps[this.currentStepIndex] || {};
                },

                get storageKey() {
                    return 'lumbarong_tour_done_' + this.tourId + '_' + this.userId;
                },

                getSelector(step) {
                    if (!step) return null;
                    return step.selector || step.target || step.element || null;
                },

                initTour() {
                    const isDone = localStorage.getItem(this.storageKey);
                    if (!isDone && this.autoStart && this.steps.length > 0) {
                        setTimeout(() => {
                            this.startTour();
                        }, 800);
                    }

                    window.addEventListener('resize', () => {
                        if (this.isActive) this.updatePosition();
                    }, { passive: true });

                    window.addEventListener('scroll', () => {
                        if (this.isActive) this.updatePosition();
                    }, { passive: true });
                },

                startTour() {
                    if (!this.steps || this.steps.length === 0) return;
                    this.currentStepIndex = 0;
                    
                    const firstStep = this.steps[0];
                    const sel = this.getSelector(firstStep);
                    if (sel) {
                        const el = document.querySelector(sel);
                        if (el) {
                            const rect = el.getBoundingClientRect();
                            this.targetRect = {
                                x: Math.max(0, rect.left),
                                y: Math.max(0, rect.top),
                                width: rect.width,
                                height: rect.height
                            };
                            this.updatePosition();
                            el.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });
                        }
                    }

                    this.isActive = true;
                    this.$nextTick(() => {
                        this.updatePosition();
                    });
                },

                dismissTour() {
                    this.isActive = false;
                    try {
                        localStorage.setItem(this.storageKey, 'true');
                    } catch(e) {}
                },

                prevStep() {
                    if (this.currentStepIndex > 0) {
                        this.goToStep(this.currentStepIndex - 1);
                    }
                },

                nextStep() {
                    if (this.currentStepIndex < this.steps.length - 1) {
                        this.goToStep(this.currentStepIndex + 1);
                    } else {
                        this.dismissTour();
                    }
                },

                goToStep(index) {
                    if (index < 0 || index >= this.steps.length) {
                        this.dismissTour();
                        return;
                    }
                    this.currentStepIndex = index;
                    const step = this.steps[index];
                    const sel = this.getSelector(step);

                    if (sel) {
                        const el = document.querySelector(sel);
                        if (el) {
                            this.updatePosition();
                            el.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });
                            setTimeout(() => { this.updatePosition(); }, 150);
                            setTimeout(() => { this.updatePosition(); }, 350);
                            return;
                        }
                    }

                    // Centered fallback when element selector is not in viewport or missing
                    this.updatePosition();
                },

                updatePosition() {
                    const step = this.steps[this.currentStepIndex];
                    if (!step) return;

                    const sel = this.getSelector(step);
                    const el = sel ? document.querySelector(sel) : null;

                    const tooltipWidth = Math.min(380, window.innerWidth - 32);
                    const tooltipHeight = 190;
                    const padding = 16;

                    if (!el) {
                        // Screen Center fallback
                        this.targetRect = { x: 0, y: 0, width: 0, height: 0 };
                        this.tooltipPos = {
                            x: Math.max(16, (window.innerWidth - tooltipWidth) / 2),
                            y: Math.max(16, (window.innerHeight - tooltipHeight) / 2)
                        };
                        this.arrowCurvePath = '';
                        return;
                    }

                    const rect = el.getBoundingClientRect();
                    this.targetRect = {
                        x: Math.max(0, rect.left),
                        y: Math.max(0, rect.top),
                        width: rect.width,
                        height: rect.height
                    };

                    let tooltipX = rect.left + (rect.width / 2) - (tooltipWidth / 2);
                    tooltipX = Math.max(16, Math.min(window.innerWidth - tooltipWidth - 16, tooltipX));

                    let tooltipY;
                    let arrowStart = { x: 0, y: 0 };
                    let arrowEnd = { x: 0, y: 0 };

                    const spaceBelow = window.innerHeight - (rect.bottom + 16);
                    const spaceAbove = rect.top - 16;

                    if (spaceBelow >= tooltipHeight || spaceBelow > spaceAbove) {
                        tooltipY = Math.min(window.innerHeight - tooltipHeight - 16, rect.bottom + padding + 8);
                        arrowStart = { x: tooltipX + (tooltipWidth / 2), y: tooltipY };
                        arrowEnd = { x: rect.left + (rect.width / 2), y: rect.bottom + 8 };
                    } else {
                        tooltipY = Math.max(16, rect.top - tooltipHeight - padding - 8);
                        arrowStart = { x: tooltipX + (tooltipWidth / 2), y: tooltipY + tooltipHeight };
                        arrowEnd = { x: rect.left + (rect.width / 2), y: rect.top - 8 };
                    }

                    this.tooltipPos = { x: tooltipX, y: tooltipY };

                    const midX = (arrowStart.x + arrowEnd.x) / 2 + (arrowStart.x < arrowEnd.x ? 30 : -30);
                    const midY = (arrowStart.y + arrowEnd.y) / 2;

                    this.arrowCurvePath = `M ${arrowStart.x} ${arrowStart.y} Q ${midX} ${midY} ${arrowEnd.x} ${arrowEnd.y}`;
                },

                get tooltipStyle() {
                    return `left: ${this.tooltipPos.x}px; top: ${this.tooltipPos.y}px; z-index: 10000;`;
                }
            };
        };

        if (window.Alpine) {
            window.Alpine.data('spotlightTourEngine', window.spotlightTourEngine);
        } else {
            document.addEventListener('alpine:init', () => {
                window.Alpine.data('spotlightTourEngine', window.spotlightTourEngine);
            });
        }
    }
})();
</script>

<div x-data="spotlightTourEngine(JSON.parse(document.getElementById('spotlight-tour-data-{{ $tourId }}').textContent))"
     x-init="initTour()"
     @start-spotlight-tour.window="if (!$event.detail || $event.detail.tourId === '{{ $tourId }}' || !$event.detail.tourId) startTour()"
     x-cloak
     x-show="isActive"
     style="display: none;"
     class="fixed inset-0 z-9999 pointer-events-none select-none overflow-hidden"
     aria-live="polite">

    <template x-if="isActive">
        <div class="contents">
            {{-- Fullscreen SVG Dimmed Backdrop with Dynamic Cutout Mask --}}
            <svg class="absolute inset-0 w-full h-full pointer-events-auto"
                 style="width: 100vw; height: 100vh;"
                 xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <mask id="spotlight-mask-{{ $tourId }}">
                        {{-- White fills everything (opaque mask) --}}
                        <rect x="0" y="0" width="100%" height="100%" fill="white" />
                        {{-- Black cutout removes mask over spotlight target --}}
                        <rect :x="targetRect.width > 0 ? ((targetRect.x || 0) - 8) : -9999"
                              :y="targetRect.width > 0 ? ((targetRect.y || 0) - 8) : -9999"
                              :width="targetRect.width > 0 ? ((targetRect.width || 0) + 16) : 0"
                              :height="targetRect.width > 0 ? ((targetRect.height || 0) + 16) : 0"
                              rx="16" ry="16"
                              fill="black"
                              class="transition-all duration-300 ease-out" />
                    </mask>
                </defs>

                {{-- Dimmed Overlay Rect with Mask Applied --}}
                <rect x="0" y="0" width="100%" height="100%"
                      fill="rgba(15, 12, 10, 0.75)"
                      mask="url(#spotlight-mask-{{ $tourId }})"
                      class="transition-all duration-300"
                      @click="nextStep()" />

                {{-- Glowing Spotlight Stroke around Target --}}
                <rect :x="targetRect.width > 0 ? ((targetRect.x || 0) - 8) : -9999"
                      :y="targetRect.width > 0 ? ((targetRect.y || 0) - 8) : -9999"
                      :width="targetRect.width > 0 ? ((targetRect.width || 0) + 16) : 0"
                      :height="targetRect.width > 0 ? ((targetRect.height || 0) + 16) : 0"
                      rx="16" ry="16"
                      fill="none"
                      stroke="#FFFFFF"
                      stroke-width="2.5"
                      stroke-dasharray="6 4"
                      class="transition-all duration-300 ease-out animate-pulse" />

                {{-- Dotted Guide Arrow from Tooltip to Target --}}
                <path :d="arrowCurvePath"
                      x-show="targetRect.width > 0"
                      fill="none"
                      stroke="#FBF9F5"
                      stroke-width="2.5"
                      stroke-dasharray="5 4"
                      stroke-linecap="round"
                      style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.5));"
                      class="transition-all duration-300 pointer-events-none" />
            </svg>

            {{-- Floating Guidance Tooltip Card --}}
            <div class="absolute pointer-events-auto transition-all duration-300 ease-out"
                 :style="tooltipStyle"
                 @click.stop>
                
                <div class="relative bg-white/95 backdrop-blur-md rounded-2xl sm:rounded-3xl p-5 sm:p-6 shadow-[0_20px_60px_rgba(0,0,0,0.35)] border border-white/60 max-w-85 sm:max-w-95 space-y-3.5 text-left">
                    
                    {{-- Header: Tour Step & Close --}}
                    <div class="flex items-center justify-between gap-2 border-b border-gray-100 pb-2.5">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-[#C49520] animate-pulse"></span>
                            <span class="text-[10px] font-extrabold uppercase tracking-[0.2em] text-[#C49520]">
                                Guide <span x-text="(currentStepIndex + 1) + ' of ' + steps.length"></span>
                            </span>
                        </div>
                        <button type="button" 
                                @click="dismissTour()"
                                class="text-gray-400 hover:text-gray-700 text-xs font-bold w-6 h-6 rounded-full flex items-center justify-center hover:bg-gray-100 transition-colors"
                                title="Close Tour">
                            ✕
                        </button>
                    </div>

                    {{-- Body: Title & Instruction --}}
                    <div class="space-y-1">
                        <h4 class="font-serif text-base sm:text-lg font-bold text-gray-900 leading-snug"
                            x-text="currentStep.title || 'Feature Guide'"></h4>
                        <p class="text-xs sm:text-[13px] text-gray-600 leading-relaxed font-normal"
                            x-text="currentStep.text || currentStep.description || currentStep.content || ''"></p>
                    </div>

                    {{-- Action Controls (Previous / I Understand / Skip) --}}
                    <div class="flex items-center justify-between gap-2 pt-1">
                        <div>
                            <button type="button"
                                    x-show="currentStepIndex > 0"
                                    @click="prevStep()"
                                    class="px-3.5 py-2 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold transition-all">
                                Previous
                            </button>
                        </div>

                        <div class="flex items-center gap-2">
                            <button type="button"
                                    @click="dismissTour()"
                                    class="text-[11px] font-bold text-gray-400 hover:text-gray-700 px-2 py-1 transition-colors">
                                Skip
                            </button>
                            
                            <button type="button"
                                    @click="nextStep()"
                                    style="background-color: #1E1915;"
                                    onmouseover="this.style.backgroundColor='#C49520';"
                                    onmouseout="this.style.backgroundColor='#1E1915';"
                                    class="px-5 py-2 rounded-full text-white text-xs font-bold transition-all shadow-md active:scale-95 flex items-center gap-1.5">
                                <span x-text="currentStepIndex === steps.length - 1 ? 'Got It ✓' : 'I Understand →'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
