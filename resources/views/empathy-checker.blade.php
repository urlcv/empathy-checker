{{--
  Empathy Checker — client-side rules engine (no LLM, no API).
--}}
<style>[x-cloak]{display:none!important}</style>
<div
    x-data="empathyChecker()"
    x-init="init()"
    class="space-y-6"
>
    <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-4 text-sm text-slate-700 space-y-2">
        <p class="font-semibold text-slate-900">Make hiring and workplace messages clearer and kinder</p>
        <p>Paste rejection notes, interview invites, follow-ups, or internal HR text. You will get scores, tone, gaps, and fixes in seconds.</p>
        <ul class="list-disc list-inside text-slate-600 space-y-0.5">
            <li><span class="font-medium text-slate-800">Runs locally</span> in your browser — nothing is uploaded.</li>
            <li><span class="font-medium text-slate-800">Rules, not AI</span> — phrase lists and heuristics, not generated prose.</li>
        </ul>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
        {{-- Left: input --}}
        <div class="space-y-4">
            <div>
                <label for="ec-input" class="block text-sm font-medium text-gray-700 mb-2">Your message</label>
                <textarea
                    id="ec-input"
                    x-model="text"
                    @input="scheduleAnalyze()"
                    rows="14"
                    placeholder="Paste or type your email or message here…"
                    class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-900 leading-relaxed focus:ring-2 focus:ring-primary-500 focus:border-transparent resize-y min-h-[220px]"
                ></textarea>
                <div class="mt-2 flex flex-wrap items-center justify-between gap-2 text-xs text-gray-500">
                    <div class="flex flex-wrap gap-3">
                        <span x-text="wordCount + ' words'"></span>
                        <span x-text="charCount.toLocaleString() + ' characters'"></span>
                    </div>
                    <span>Max 50,000 characters</span>
                </div>
            </div>

            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">Quick examples</p>
                <div class="flex flex-wrap gap-2">
                    <template x-for="ex in examples" :key="ex.id">
                        <button
                            type="button"
                            @click="loadExample(ex)"
                            class="text-xs px-3 py-1.5 rounded-lg border border-gray-200 bg-white text-gray-700 hover:border-primary-300 hover:bg-primary-50 transition-colors"
                            x-text="ex.label"
                        ></button>
                    </template>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    @click="reset()"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50"
                >Reset</button>
                <button
                    type="button"
                    @click="copySummary()"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg bg-primary-600 text-white hover:bg-primary-700 disabled:opacity-40"
                    :disabled="trimmed.length === 0"
                >
                    <span x-show="!copiedSummary">Copy summary</span>
                    <span x-show="copiedSummary" x-cloak>Copied!</span>
                </button>
            </div>

            <div class="rounded-xl p-4 text-sm bg-blue-50 border border-blue-200 text-blue-900">
                <span class="font-semibold">Tip:</span>
                Longer rejections usually need a thank-you, a clear outcome, and either next steps or a respectful close. Pair “unfortunately” with genuine appreciation so it does not feel like the only beat.
            </div>
        </div>

        {{-- Right: analysis --}}
        <div class="space-y-5 min-w-0">
            <template x-if="trimmed.length === 0">
                <div class="rounded-xl border border-dashed border-gray-200 bg-gray-50 p-8 text-center text-sm text-gray-500">
                    Add a message to see empathy score, tone, dimensions, and suggestions. Editing is debounced so the panel updates smoothly.
                </div>
            </template>

            <template x-if="trimmed.length > 0">
                <div class="space-y-5" x-cloak>
                    {{-- Score + tone --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="rounded-xl border border-gray-200 bg-white p-5 text-center shadow-sm">
                            <div class="text-4xl font-bold tabular-nums" :class="scoreColorClass" x-text="result.overall"></div>
                            <div class="text-sm text-gray-500 mt-1">Empathy score</div>
                            <div class="text-xs text-gray-400 mt-0.5">Out of 100</div>
                        </div>
                        <div class="rounded-xl border border-gray-200 p-5 flex flex-col justify-center" :class="toneBoxClass">
                            <div class="text-xs font-semibold uppercase tracking-wide opacity-70">Tone</div>
                            <div class="text-lg font-semibold mt-0.5" x-text="result.toneLabel"></div>
                            <p class="text-sm mt-1 opacity-90 leading-snug" x-text="result.toneHint"></p>
                        </div>
                    </div>

                    {{-- Dimensions --}}
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800 mb-3">Score breakdown</h3>
                        <div class="space-y-3">
                            <template x-for="d in result.dimensions" :key="d.key">
                                <div class="rounded-lg border border-gray-100 bg-white p-4 shadow-sm">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900" x-text="d.title"></div>
                                            <p class="text-xs text-gray-600 mt-1 leading-relaxed" x-text="d.explanation"></p>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <div class="text-lg font-bold tabular-nums text-gray-900" x-text="d.score"></div>
                                            <span class="inline-block mt-1 text-[10px] font-semibold uppercase tracking-wide px-2 py-0.5 rounded-full"
                                                  :class="d.badgeClass" x-text="d.levelLabel"></span>
                                        </div>
                                    </div>
                                    <div class="mt-3 h-2 rounded-full bg-gray-100 overflow-hidden">
                                        <div class="h-full rounded-full transition-all duration-300"
                                             :class="d.barClass"
                                             :style="'width:' + d.score + '%'"></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Strengths / issues --}}
                    <div class="grid grid-cols-1 gap-3">
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-4" x-show="result.strengths.length">
                            <h4 class="text-xs font-semibold uppercase tracking-wide text-emerald-800 mb-2">Strengths</h4>
                            <ul class="text-sm text-emerald-900 space-y-1 list-disc list-inside">
                                <template x-for="(s, idx) in result.strengths" :key="'s'+idx">
                                    <li x-text="s"></li>
                                </template>
                            </ul>
                        </div>
                        <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-4" x-show="result.issues.length">
                            <h4 class="text-xs font-semibold uppercase tracking-wide text-amber-900 mb-2">Issues</h4>
                            <ul class="text-sm text-amber-950 space-y-1 list-disc list-inside">
                                <template x-for="(s, idx) in result.issues" :key="'i'+idx">
                                    <li x-text="s"></li>
                                </template>
                            </ul>
                        </div>
                    </div>

                    {{-- Anxiety --}}
                    <div class="rounded-xl border border-rose-200 bg-rose-50 p-4">
                        <h4 class="text-sm font-semibold text-rose-900 mb-2">Anxiety triggers</h4>
                        <p class="text-xs text-rose-800/90 mb-3">Language that can increase stress when clarity or closure is missing.</p>
                        <ul class="space-y-2">
                            <template x-for="(a, idx) in result.anxiety" :key="'a'+idx">
                                <li class="flex gap-2 text-sm text-rose-950">
                                    <span class="text-rose-400 shrink-0">▸</span>
                                    <span x-text="a"></span>
                                </li>
                            </template>
                            <li x-show="result.anxiety.length === 0" class="text-sm text-rose-800/80 italic">No major anxiety triggers flagged — nice work keeping uncertainty in check.</li>
                        </ul>
                    </div>

                    {{-- Missing checks --}}
                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <h4 class="text-sm font-semibold text-gray-900 mb-3">Missing empathy checks</h4>
                        <ul class="space-y-2">
                            <template x-for="m in result.missing" :key="m.id">
                                <li class="flex items-start gap-2 text-sm">
                                    <span class="mt-0.5 shrink-0 w-4 h-4 rounded border flex items-center justify-center text-[10px]"
                                          :class="m.pass ? 'border-emerald-400 bg-emerald-50 text-emerald-700' : 'border-amber-400 bg-amber-50 text-amber-800'"
                                          x-text="m.pass ? '✓' : '!'"></span>
                                    <span :class="m.pass ? 'text-gray-600' : 'text-gray-900 font-medium'" x-text="m.label"></span>
                                </li>
                            </template>
                        </ul>
                    </div>

                    {{-- Patterns --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="rounded-xl border border-emerald-100 bg-emerald-50/40 p-4">
                            <h4 class="text-xs font-semibold text-emerald-800 mb-2">Positive patterns</h4>
                            <div class="flex flex-wrap gap-1.5">
                                <template x-for="p in result.positiveHits" :key="'p'+p.phrase">
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-white border border-emerald-200 text-xs text-emerald-900">
                                        <span class="truncate max-w-[200px]" x-text="p.phrase"></span>
                                        <span class="text-emerald-600 font-medium" x-show="p.count > 1" x-text="'×'+p.count"></span>
                                    </span>
                                </template>
                                <span x-show="result.positiveHits.length === 0" class="text-xs text-gray-500 italic">None detected — consider adding gratitude or acknowledgement.</span>
                            </div>
                        </div>
                        <div class="rounded-xl border border-rose-100 bg-rose-50/40 p-4">
                            <h4 class="text-xs font-semibold text-rose-900 mb-2">Harsh or cold patterns</h4>
                            <div class="flex flex-wrap gap-1.5">
                                <template x-for="p in result.negativeHits" :key="'n'+p.phrase">
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-white border border-rose-200 text-xs text-rose-950">
                                        <span class="truncate max-w-[200px]" x-text="p.phrase"></span>
                                        <span class="text-rose-600 font-medium" x-show="p.count > 1" x-text="'×'+p.count"></span>
                                    </span>
                                </template>
                                <span x-show="result.negativeHits.length === 0" class="text-xs text-gray-500 italic">No classic cold phrases found.</span>
                            </div>
                        </div>
                    </div>

                    {{-- Vague / anxiety phrases list --}}
                    <div class="rounded-xl border border-amber-100 bg-amber-50/30 p-4" x-show="result.vagueHits.length">
                        <h4 class="text-xs font-semibold text-amber-900 mb-2">Vague or stress-prone wording</h4>
                        <div class="flex flex-wrap gap-1.5">
                            <template x-for="p in result.vagueHits" :key="'v'+p.phrase">
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-white border border-amber-200 text-xs text-amber-950">
                                    <span class="truncate max-w-[220px]" x-text="p.phrase"></span>
                                    <span class="text-amber-700 font-medium" x-show="p.count > 1" x-text="'×'+p.count"></span>
                                </span>
                            </template>
                        </div>
                    </div>

                    {{-- Suggestions --}}
                    <div class="rounded-xl border border-primary-100 bg-primary-50/40 p-4">
                        <h4 class="text-sm font-semibold text-primary-900 mb-2">Suggestions</h4>
                        <ul class="text-sm text-primary-950 space-y-1.5 list-disc list-inside">
                            <template x-for="(s, idx) in result.suggestions" :key="'sg'+idx">
                                <li x-text="s"></li>
                            </template>
                        </ul>
                    </div>

                    {{-- Highlighted preview --}}
                    <div class="rounded-xl border border-gray-200 bg-white p-4 overflow-hidden">
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                            <h4 class="text-sm font-semibold text-gray-900">Phrase review</h4>
                            <div class="flex flex-wrap gap-3 text-[10px] text-gray-500">
                                <span><span class="inline-block w-3 h-3 rounded bg-emerald-200 align-middle mr-1"></span> Supportive</span>
                                <span><span class="inline-block w-3 h-3 rounded bg-amber-200 align-middle mr-1"></span> Vague / stress</span>
                                <span><span class="inline-block w-3 h-3 rounded bg-rose-200 align-middle mr-1"></span> Harsh / cold</span>
                            </div>
                        </div>
                        <div class="text-sm leading-relaxed text-gray-800 border border-gray-100 rounded-lg p-3 bg-gray-50 max-h-64 overflow-y-auto font-sans"
                             x-html="result.highlightHtml"></div>
                        <p class="text-[11px] text-gray-400 mt-2">Highlighted spans use the same phrase lists as the score — longest match wins per character.</p>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

@push('scripts')
@verbatim
<script>
(function () {
    const MAX_CHARS = 50000;

    /**
     * Central tuning: tweak weights here to shift score ranges without rewriting logic.
     * Target bands: poor ~20–40, average ~45–65, good ~70–85, excellent 85+.
     */
    const WEIGHTS = {
        compoundOverallBoost: 15, // gratitude + effort recognition both present
        ackBase: 34,
        ackPerGratitude: 7,
        ackGratitudeCap: 20,
        ackPerEffort: 5,
        ackEffortCap: 14,
        ackOpeningBonus: 9,
        clarityBase: 44,
        clarityByWeekday: 18,
        clarityUpdateByPhrase: 14,
        clarityTimelineWord: 5,
        clarityTimelineCap: 12,
        clarityNextSteps: 7,
        clarityVaguePenalty: 12,
        clarityVaguePenaltyProgress: 4,
        respectBase: 56,
        respectSignOff: 12,
        respectPlease: 4,
        respectDifficultDecision: 9,
        warmthBase: 50,
        warmthPerWarmExtra: 6,
        warmthWarmExtraCap: 18,
        warmthPerSupportive: 7,
        warmthSupportiveCap: 14,
        warmthRobotic: 11,
        warmthColdOpener: 8,
        focusBase: 44,
        focusYouYourBoost: 12,
        focusProcessJargon: 7,
    };

    /** Case-insensitive regex rows: { re, label } for detection + UI reporting */
    const PATTERNS = {
        gratitude: [
            { re: /\bthank you\b/i, label: 'Thank you' },
            { re: /\bthanks(?:\s+for)?\b/i, label: 'Thanks' },
            { re: /\bappreciate(?:d|s)?\b/i, label: 'Appreciation' },
            { re: /\bgrateful\b/i, label: 'Grateful' },
        ],
        effort: [
            { re: /time\s+you\s+(?:took|spent)/i, label: 'Time you took / spent' },
            { re: /\beffort\b/i, label: 'Effort recognised' },
            { re: /insight\s+you\s+shared/i, label: 'Insight you shared' },
            { re: /learn(?:ing|ed)?\s+(?:more\s+)?about/i, label: 'Learning about you' },
            { re: /(?:speak|speaking|spoke)\s+with\s+you/i, label: 'Speaking with you' },
            { re: /taking\s+the\s+time/i, label: 'Taking the time' },
            { re: /(?:phone|video|our)\s+(?:call|conversation)\b/i, label: 'Call / conversation' },
        ],
        supportive: [
            { re: /feel\s+free\s+to\s+reach\s+out/i, label: 'Feel free to reach out' },
            { re: /happy\s+to\s+help/i, label: 'Happy to help' },
            { re: /let\s+me\s+know\s+if\s+you\s+have/i, label: 'Invite to ask questions' },
            { re: /questions\s+in\s+the\s+meantime/i, label: 'Questions in the meantime' },
            { re: /reach\s+out\b/i, label: 'Reach out' },
        ],
        warmExtra: [
            { re: /really\s+appreciated/i, label: 'Really appreciated' },
            { re: /great\s+to\s+learn/i, label: 'Great to learn' },
            { re: /enjoyed\s+speaking/i, label: 'Enjoyed speaking' },
            { re: /look\s+forward\s+to/i, label: 'Look forward' },
            { re: /enjoyed\s+learning/i, label: 'Enjoyed learning' },
        ],
    };

    /** Substrings for harsh / vague highlights (unchanged case-insensitive scan) */
    const PHRASES = {
        positive: [
            'thank you for your time',
            'thank you for applying',
            'we appreciate your interest',
            'enjoyed learning more about your experience',
            'we will update you by',
            'feel free to reach out',
        ],
        negative: [
            'unfortunately',
            'regret to inform you',
            'due to high volume',
            'not a good fit',
            'we have decided to move forward with other candidates',
            'moving forward with other candidates',
            'you have not been selected',
            'you were not selected',
        ],
        vague: [
            'we will be in touch',
            'we\'ll be in touch',
            'in due course',
            'stay tuned',
            'shortly',
            'soon',
            'as soon as possible',
            'touch base',
            'circle back',
        ],
        timelineWords: [
            'today', 'tomorrow', 'next week', 'by friday', 'by monday', 'by tuesday',
            'by wednesday', 'by thursday', 'end of day', 'eod', 'end of week',
            'within 24 hours', 'within 48 hours', 'next steps', 'calendar',
        ],
        robotic: [
            'due to the large number',
            'high volume of applicants',
            'standard process',
        ],
        casual: ['lol', 'omg', 'gonna', 'hey guys', 'btw', 'np'],
        rejectionSignals: [
            'not selected', 'unable to offer', 'will not be moving forward',
            'not move forward', 'unsuccessful',
        ],
    };

    const SIGN_OFF_RES = [
        /(?:^|\n)\s*best\s*[,\n]/im,
        /(?:^|\n)\s*thanks\s*[,\n]/im,
        /(?:^|\n)\s*regards\s*[,\n]/im,
        /\bkind regards\b/i,
        /\bbest regards\b/i,
        /\bwarm regards\b/i,
        /\ball the best\b/i,
        /\bsincerely\b/i,
        /\bcheers\s*[,\n]/im,
    ];

    function clamp(n, lo, hi) {
        return Math.max(lo, Math.min(hi, n));
    }

    /** Collect unique labels for any regex that matches (whole text, case-insensitive via /i). */
    function matchPatternLabels(text, rows) {
        const labels = [];
        const seen = new Set();
        for (const { re, label } of rows) {
            const r = new RegExp(re.source, 'i');
            if (r.test(text)) {
                if (!seen.has(label)) {
                    seen.add(label);
                    labels.push(label);
                }
            }
        }
        return labels;
    }

    function countInsensitive(haystack, needle) {
        if (!needle) return 0;
        const h = haystack.toLowerCase();
        const n = needle.toLowerCase();
        let c = 0;
        let pos = 0;
        while (true) {
            const i = h.indexOf(n, pos);
            if (i === -1) break;
            c++;
            pos = i + 1;
        }
        return c;
    }

    function collectPhraseHits(text, list) {
        const hits = [];
        for (const phrase of list) {
            const c = countInsensitive(text, phrase);
            if (c > 0) hits.push({ phrase, count: c });
        }
        hits.sort((a, b) => b.count - a.count || a.phrase.localeCompare(b.phrase));
        return { hits };
    }

    function wordCountFn(t) {
        return t.trim().split(/\s+/).filter(Boolean).length;
    }

    /** Progress vs rejection vs general — drives penalties only */
    function inferMessageType(lower) {
        const rejection = /\bunfortunately\b|\bregret(?:\s+to\s+inform)?\b/i.test(lower)
            || /move\s+forward\s+with\s+other\s+candidates/i.test(lower)
            || /\bnot\s+been\s+selected\b|\byou\s+were\s+not\s+selected\b/i.test(lower);
        const progress = /still\s+in\s+the\s+process|interviewing\s+candidates|interviewing\s+applicants/i.test(lower)
            || /expect\s+to\s+(?:provide\s+)?(?:an\s+)?update|provide\s+an\s+update|update\s+by/i.test(lower);
        if (rejection) return 'rejection';
        if (progress && !rejection) return 'progress_update';
        return 'general';
    }

    /** Sign-off: look at closing lines only — Best / Thanks / Regards count */
    function hasSignOff(text) {
        const tail = text.slice(Math.max(0, text.length - 420));
        return SIGN_OFF_RES.some((re) => re.test(tail));
    }

    function applySeverityBuffer(lower, original, phrases, level, buf) {
        for (const phrase of phrases) {
            const p = phrase.toLowerCase();
            let pos = 0;
            while (true) {
                const i = lower.indexOf(p, pos);
                if (i === -1) break;
                const end = i + phrase.length;
                for (let j = i; j < end && j < buf.length; j++) {
                    buf[j] = Math.max(buf[j], level);
                }
                pos = i + 1;
            }
        }
    }

    /** Highlight regex pattern matches (green tier) */
    function applyRegexSeverityBuffer(text, rows, level, buf) {
        for (const { re } of rows) {
            const rx = new RegExp(re.source, 'gi');
            let m;
            while ((m = rx.exec(text)) !== null) {
                const i = m.index;
                const end = i + m[0].length;
                for (let j = i; j < end && j < buf.length; j++) {
                    buf[j] = Math.max(buf[j], level);
                }
                if (m[0].length === 0) {
                    rx.lastIndex++;
                }
            }
        }
    }

    function buildHighlightHtml(original, buf) {
        if (!original.length) return '';
        let html = '';
        let cur = buf[0] || 0;
        let start = 0;
        const open = (sev) => {
            if (sev === 3) return '<mark class="bg-rose-200 text-rose-950 rounded px-0.5">';
            if (sev === 2) return '<mark class="bg-amber-200 text-amber-950 rounded px-0.5">';
            if (sev === 1) return '<mark class="bg-emerald-200 text-emerald-950 rounded px-0.5">';
            return '';
        };
        const esc = (s) => s
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');

        for (let i = 1; i <= original.length; i++) {
            const next = i < original.length ? buf[i] : -1;
            if (i === original.length || buf[i] !== cur) {
                const chunk = original.slice(start, i);
                if (cur > 0) {
                    html += open(cur) + esc(chunk) + '</mark>';
                } else {
                    html += esc(chunk);
                }
                start = i;
                cur = next;
            }
        }
        return html.replace(/\n/g, '<br>');
    }

    window.analyzeEmpathyMessage = function analyzeEmpathyMessage(raw) {
        const text = raw.length > MAX_CHARS ? raw.slice(0, MAX_CHARS) : raw;
        const trimmed = text.trim();
        const lower = text.toLowerCase();
        const wc = wordCountFn(trimmed);
        const charCount = text.length;

        const empty = {
            overall: 0,
            toneLabel: '—',
            toneHint: '',
            dimensions: [],
            strengths: [],
            issues: [],
            suggestions: [],
            missing: [],
            anxiety: [],
            positiveHits: [],
            negativeHits: [],
            vagueHits: [],
            highlightHtml: '',
        };

        if (wc === 0) {
            return empty;
        }

        const msgType = inferMessageType(lower);
        const signOffOK = hasSignOff(text);
        const opening = lower.slice(0, Math.min(lower.length, 160));

        const gratLabels = matchPatternLabels(text, PATTERNS.gratitude);
        const effortLabels = matchPatternLabels(text, PATTERNS.effort);
        const supportiveLabels = matchPatternLabels(text, PATTERNS.supportive);
        const warmExtraLabels = matchPatternLabels(text, PATTERNS.warmExtra);

        const hasGratitude = gratLabels.length > 0;
        const hasEffort = effortLabels.length > 0;
        const compoundPair = hasGratitude && hasEffort;

        const negH = collectPhraseHits(lower, PHRASES.negative);
        const vagueH = collectPhraseHits(lower, PHRASES.vague);

        const hasTimelineWord = PHRASES.timelineWords.some((t) => lower.includes(t));
        const hasWeekdayDeadline = /\bby\s+(?:monday|tuesday|wednesday|thursday|friday|saturday|sunday)\b/i.test(lower);
        const hasUpdateBy = /(?:provide\s+)?(?:an\s+)?update\s+by|update\s+you\s+by|hear\s+from\s+us\s+by/i.test(lower);

        // --- Acknowledgement: gratitude + effort recognition (regex), opening line ---
        let acknowledgement = WEIGHTS.ackBase
            + Math.min(WEIGHTS.ackGratitudeCap, gratLabels.length * WEIGHTS.ackPerGratitude)
            + Math.min(WEIGHTS.ackEffortCap, effortLabels.length * WEIGHTS.ackPerEffort);
        if (/(thank|thanks|appreciate|grateful)/.test(opening)) acknowledgement += WEIGHTS.ackOpeningBonus;
        if (wc > 28 && !hasGratitude) acknowledgement -= msgType === 'rejection' ? 20 : 12;
        acknowledgement = clamp(acknowledgement, 0, 100);

        // --- Clarity: strong boost for "by Friday" OR "update by" (avoid double-counting the same promise) ---
        let clarity = WEIGHTS.clarityBase;
        if (hasWeekdayDeadline) {
            clarity += WEIGHTS.clarityByWeekday;
        } else if (hasUpdateBy) {
            clarity += WEIGHTS.clarityUpdateByPhrase;
        }
        let tw = 0;
        for (const w of PHRASES.timelineWords) {
            // Avoid double-counting "by Friday" when we already gave the weekday deadline bonus
            if (hasWeekdayDeadline && w.startsWith('by ')) continue;
            if (lower.includes(w)) tw += WEIGHTS.clarityTimelineWord;
        }
        clarity += Math.min(WEIGHTS.clarityTimelineCap, tw);
        if (/\bnext steps\b/.test(lower)) clarity += WEIGHTS.clarityNextSteps;
        if (/\d{1,2}\/\d{1,2}|\d{4}-\d{2}-\d{2}/.test(text)) clarity += 6;

        const vagueOnly = /\b(soon|shortly|in due course)\b/.test(lower) && !hasTimelineWord && !hasWeekdayDeadline;
        const touchVague = /we\s+will\s+be\s+in\s+touch|we'll\s+be\s+in\s+touch/.test(lower)
            && !/\b(monday|tuesday|wednesday|thursday|friday|today|tomorrow|next week|\d)\b/i.test(lower);
        const vaguePenalty = msgType === 'progress_update' ? WEIGHTS.clarityVaguePenaltyProgress : WEIGHTS.clarityVaguePenalty;
        if (vagueOnly) clarity -= vaguePenalty;
        if (touchVague) clarity -= msgType === 'progress_update' ? 5 : WEIGHTS.clarityVaguePenalty;
        clarity = clamp(clarity, 0, 100);

        // --- Respect: sign-offs expanded; rejection-specific blunt penalties ---
        let respect = WEIGHTS.respectBase;
        if (signOffOK) respect += WEIGHTS.respectSignOff;
        if (/\bplease\b/.test(lower)) respect += WEIGHTS.respectPlease;
        if (/(difficult decision|not an easy decision)/.test(lower)) respect += WEIGHTS.respectDifficultDecision;
        if (msgType === 'rejection') {
            if (/not a good fit/.test(lower)) respect -= 9;
            if (/(you have not been selected|you were not selected)/.test(lower) && gratLabels.length === 0) respect -= 14;
            if (negH.hits.length > 2 && gratLabels.length < 2) respect -= 8;
        }
        if (!signOffOK && wc > 35) respect -= 10;
        respect = clamp(respect, 0, 100);

        // --- Warmth: reward warm/supportive phrases; penalise only clear robotic / cold openers ---
        let warmth = WEIGHTS.warmthBase
            + Math.min(WEIGHTS.warmthWarmExtraCap, warmExtraLabels.length * WEIGHTS.warmthPerWarmExtra)
            + Math.min(WEIGHTS.warmthSupportiveCap, supportiveLabels.length * WEIGHTS.warmthPerSupportive);
        let roboticHits = 0;
        for (const r of PHRASES.robotic) {
            if (lower.includes(r)) roboticHits++;
        }
        if (roboticHits) warmth -= Math.min(22, roboticHits * WEIGHTS.warmthRobotic);
        if (msgType !== 'progress_update' && (/^unfortunately\b/m.test(trimmed) || /^unfortunately\b/.test(lower.slice(0, 40)))) {
            warmth -= WEIGHTS.warmthColdOpener;
        }
        if (/regret\s+to\s+inform/.test(lower)) warmth -= msgType === 'rejection' ? 7 : 4;
        warmth = clamp(warmth, 0, 100);

        const youMatches = (lower.match(/\b(you|your|you're|you've)\b/g) || []).length;
        const weMatches = (lower.match(/\b(we|our|us|we're|we've)\b/g) || []).length;
        const focusRatio = youMatches / (youMatches + weMatches + 0.22);
        let candidateFocus = WEIGHTS.focusBase + Math.round((focusRatio - 0.42) * 50);
        if (/\b(your experience|your background|your application|insight you)\b/i.test(lower)) candidateFocus += WEIGHTS.focusYouYourBoost;
        if (/\bour (process|policy)\b/.test(lower) && youMatches < 4) candidateFocus -= WEIGHTS.focusProcessJargon;
        candidateFocus = clamp(candidateFocus, 0, 100);

        const dims = [
            { key: 'acknowledgement', title: 'Acknowledgement', score: acknowledgement },
            { key: 'clarity', title: 'Clarity', score: clarity },
            { key: 'respect', title: 'Respect', score: respect },
            { key: 'warmth', title: 'Warmth', score: warmth },
            { key: 'candidate_focus', title: 'Candidate focus', score: candidateFocus },
        ];

        const avgDim = dims.reduce((s, d) => s + d.score, 0) / dims.length;
        // Compound boost: gratitude + effort — large bump to overall only (dims already reflect content)
        let overall = Math.round(avgDim + (compoundPair ? WEIGHTS.compoundOverallBoost : 0));
        overall = clamp(overall, 0, 100);

        const casualScore = PHRASES.casual.reduce((s, p) => s + countInsensitive(lower, p), 0)
            + ((text.match(/!/g) || []).length > 4 ? 3 : 0);
        const coldSignals = negH.hits.length + (!hasGratitude && wc > 35 && msgType === 'rejection' ? 3 : 0);
        const warmSignals = warmExtraLabels.length + supportiveLabels.length + (warmth > 64 ? 1 : 0);

        let toneLabel = 'Neutral';
        let toneHint = 'Readable and balanced — neutral is fine when timelines and respect are clear.';
        if (casualScore >= 4 || (casualScore >= 2 && /(lol|gonna|hey guys)/.test(lower))) {
            toneLabel = 'Overly casual';
            toneHint = 'Very informal markers can read as too relaxed for hiring mail.';
        } else if (coldSignals > warmSignals + 4 && warmth < 48 && msgType === 'rejection') {
            toneLabel = 'Cold';
            toneHint = 'For bad news, add more acknowledgement and specificity.';
        } else if (warmSignals >= 3 && warmth >= 60 && hasGratitude) {
            toneLabel = 'Warm';
            toneHint = 'Sounds human and considerate — keep dates concrete if people are waiting.';
        } else if ((/kind regards|best regards|sincerely|dear\s+\w/i.test(lower) || signOffOK) && casualScore < 2 && warmth < 72) {
            toneLabel = 'Professional';
            toneHint = 'Polite and appropriate for workplace email.';
        }

        // --- Human-readable positive pattern list for chips ---
        const positiveHits = [];
        const addHit = (label) => positiveHits.push({ phrase: label, count: 1 });
        gratLabels.forEach((l) => addHit(`${l} detected`));
        effortLabels.forEach((l) => addHit(`${l} detected`));
        supportiveLabels.forEach((l) => addHit(`${l} detected`));
        if (compoundPair) addHit('Gratitude + effort (compound boost)');
        if (warmExtraLabels.length) warmExtraLabels.forEach((l) => addHit(`${l}`));

        const strengths = [];
        if (msgType === 'progress_update') strengths.push('Reads as a progress update — not scored like a rejection.');
        if (hasGratitude) strengths.push('Gratitude or appreciation is present.');
        if (hasEffort) strengths.push('Recognises the candidate’s time, effort, or contribution.');
        if (supportiveLabels.length) strengths.push('Supportive or open-door language detected.');
        if (hasWeekdayDeadline || hasUpdateBy) strengths.push('Uses a specific update window or deadline.');
        if (signOffOK) strengths.push('Ends with a recognised sign-off (e.g. Best, Thanks, Regards).');
        if (compoundPair) strengths.push('Combines thanks with recognition of effort — strong empathy signal.');
        if (strengths.length === 0) strengths.push('Message is clear enough — add explicit thanks and a date if people are waiting.');

        const issues = [];
        if (negH.hits.length && msgType === 'rejection') issues.push(`${negH.hits.length} phrase(s) often associated with harsh rejections — balance with warmth.`);
        if (vagueH.hits.length && !hasTimelineWord && !hasWeekdayDeadline) issues.push('Some vague timing phrases — add a firmer date if you can.');
        if (!hasGratitude && wc > 30 && msgType === 'rejection') issues.push('Little appreciation for a rejection — consider thanking them for their time.');
        if (warmth < 44 && roboticHits) issues.push('Stock or process-heavy phrasing — a single human line can help.');
        if (issues.length === 0) issues.push('No major structural issues flagged.');

        const explainRejectOk = msgType !== 'rejection'
            || !PHRASES.rejectionSignals.some((r) => lower.includes(r))
            || lower.length > 200
            || /(because|since|although|while we|difficult decision)/i.test(lower);

        const missing = [
            { id: 'thanks', label: 'Explicit thank-you or appreciation', pass: hasGratitude },
            { id: 'time', label: 'Acknowledgement of time or effort', pass: hasEffort || /(time|effort|insight)/i.test(lower) },
            { id: 'next', label: 'Clear next step or status', pass: /\b(will|we'll|expect|interview|update|provide|questions)\b/i.test(lower) && wc > 12 },
            { id: 'timeline', label: 'Timeline or expected update', pass: hasTimelineWord || hasWeekdayDeadline || hasUpdateBy || /\d{1,2}\/\d{1,2}/.test(text) },
            { id: 'human', label: 'Human phrasing (not only process jargon)', pass: warmth >= 46 || supportiveLabels.length > 0 },
            { id: 'explain', label: 'Explanation when sharing disappointing news', pass: explainRejectOk },
        ];

        const anxiety = [];
        if (msgType === 'progress_update' && /\b(soon|shortly)\b/.test(lower) && !hasWeekdayDeadline) {
            anxiety.push('“Soon” or “shortly” is softer than a date — consider naming a day if anxiety is high.');
        } else if (/\b(soon|shortly)\b/.test(lower) && !hasTimelineWord && !hasWeekdayDeadline) {
            anxiety.push('“Soon” or “shortly” without a date keeps people guessing.');
        }
        if (touchVague && msgType !== 'progress_update') {
            anxiety.push('“We’ll be in touch” without a timeframe feels open-ended.');
        }
        if (msgType === 'rejection' && PHRASES.rejectionSignals.some((r) => lower.includes(r)) && gratLabels.length < 2) {
            anxiety.push('Rejection wording with limited appreciation can feel abrupt.');
        }
        if (!signOffOK && wc > 45) {
            anxiety.push('No clear sign-off line (e.g. Best, Thanks, Regards) — can feel abrupt.');
        }

        const suggestions = [];
        if (!hasGratitude && wc > 20) suggestions.push('Add a thank-you near the start, referencing time or interest.');
        if (!hasTimelineWord && !hasWeekdayDeadline && msgType !== 'general') suggestions.push('Name a day or window for the next update.');
        if (negH.hits.length && msgType === 'rejection') suggestions.push('Soften template rejection lines with one specific, sincere sentence.');
        if (!signOffOK && wc > 30) suggestions.push('Close with Best, Thanks, Regards, or Kind regards on their own line.');
        if (suggestions.length === 0) suggestions.push('Read aloud once — if anything sounds generic, add one concrete detail.');

        const original = text;
        const buf = new Uint8Array(original.length);
        const oLower = original.toLowerCase();
        applySeverityBuffer(oLower, original, PHRASES.positive, 1, buf);
        applyRegexSeverityBuffer(text, PATTERNS.gratitude, 1, buf);
        applyRegexSeverityBuffer(text, PATTERNS.effort, 1, buf);
        applyRegexSeverityBuffer(text, PATTERNS.supportive, 1, buf);
        applySeverityBuffer(oLower, original, PHRASES.vague, 2, buf);
        applySeverityBuffer(oLower, original, PHRASES.negative, 3, buf);

        const highlightHtml = buildHighlightHtml(original, buf);

        const levelMeta = (score) => {
            if (score >= 70) return { levelLabel: 'Strong', badgeClass: 'bg-emerald-100 text-emerald-800', barClass: 'bg-emerald-500' };
            if (score >= 40) return { levelLabel: 'Mixed', badgeClass: 'bg-amber-100 text-amber-800', barClass: 'bg-amber-400' };
            return { levelLabel: 'Weak', badgeClass: 'bg-rose-100 text-rose-800', barClass: 'bg-rose-400' };
        };

        const explanations = {
            acknowledgement: 'Gratitude and recognition of the reader’s time and effort.',
            clarity: 'Concrete timelines, next steps, and specific language vs vague promises.',
            respect: 'Polite framing and considerate delivery of difficult news.',
            warmth: 'Human, conversational phrasing rather than purely transactional wording.',
            candidate_focus: 'Language centred on the reader (you/your) vs internal process-speak.',
        };

        const dimensionsOut = dims.map((d) => {
            const lm = levelMeta(d.score);
            return {
                key: d.key,
                title: d.title,
                score: Math.round(d.score),
                levelLabel: lm.levelLabel,
                badgeClass: lm.badgeClass,
                barClass: lm.barClass,
                explanation: explanations[d.key] || '',
            };
        });

        return {
            overall,
            toneLabel,
            toneHint,
            dimensions: dimensionsOut,
            strengths: strengths.slice(0, 6),
            issues: issues.slice(0, 6),
            suggestions: [...new Set(suggestions)].slice(0, 8),
            missing,
            anxiety,
            positiveHits: posH.hits.slice(0, 12),
            negativeHits: negH.hits.slice(0, 12),
            vagueHits: vagueH.hits.slice(0, 12),
            highlightHtml,
            _meta: { wc, charCount },
        };
    };
})();

function empathyChecker() {
    return {
        text: '',
        trimmed: '',
        wordCount: 0,
        charCount: 0,
        result: {
            overall: 0,
            toneLabel: '—',
            toneHint: '',
            dimensions: [],
            strengths: [],
            issues: [],
            suggestions: [],
            missing: [],
            anxiety: [],
            positiveHits: [],
            negativeHits: [],
            vagueHits: [],
            highlightHtml: '',
        },
        examples: [
            {
                id: 'cold',
                label: 'Cold rejection',
                body: "Dear Applicant,\n\nUnfortunately, we regret to inform you that you have not been selected. Due to the high volume of applications, we cannot provide individual feedback. We will be in touch if a suitable role arises.\n\nRegards,\nRecruiting",
            },
            {
                id: 'warm',
                label: 'Warm rejection',
                body: "Hi Jordan,\n\nThank you for taking the time to interview with us and for your patience while we completed the process. We enjoyed learning more about your experience.\n\nAfter careful consideration, we will not be moving forward with your application for this role. The decision was difficult given the strength of the field.\n\nWe will update you by Friday if we open a role that better aligns with your background. Thank you again for your interest.\n\nKind regards,\nAlex",
            },
            {
                id: 'vague',
                label: 'Vague follow-up',
                body: "Hi,\n\nThanks for coming in. We will be in touch soon with next steps.\n\nBest",
            },
            {
                id: 'invite',
                label: 'Thoughtful invite',
                body: "Hi Sam,\n\nThank you for applying. We would like to invite you to a 45-minute video interview on Thursday at 2pm. You will meet with two engineers; we will send a calendar invite shortly.\n\nIf you need an alternative time, please reply by Wednesday COB and we will find a slot.\n\nWe appreciate your interest and look forward to speaking with you.\n\nBest regards,\nPat",
            },
        ],
        debounceId: null,
        copiedSummary: false,
        copyTimer: null,

        get scoreColorClass() {
            const s = this.result.overall;
            if (s >= 72) return 'text-emerald-600';
            if (s >= 45) return 'text-amber-600';
            return 'text-rose-600';
        },

        get toneBoxClass() {
            const t = this.result.toneLabel;
            if (t === 'Warm') return 'bg-emerald-50 border-emerald-200 text-emerald-900';
            if (t === 'Professional') return 'bg-slate-50 border-slate-200 text-slate-900';
            if (t === 'Cold') return 'bg-slate-100 border-slate-300 text-slate-900';
            if (t === 'Overly casual') return 'bg-orange-50 border-orange-200 text-orange-900';
            return 'bg-gray-50 border-gray-200 text-gray-800';
        },

        init() {
            this.runAnalyze();
        },

        scheduleAnalyze() {
            if (this.debounceId) clearTimeout(this.debounceId);
            this.debounceId = setTimeout(() => this.runAnalyze(), 280);
        },

        runAnalyze() {
            const max = 50000;
            const t = this.text.length > max ? this.text.slice(0, max) : this.text;
            this.text = t;
            this.trimmed = t.trim();
            this.wordCount = this.trimmed ? this.trimmed.split(/\s+/).filter(Boolean).length : 0;
            this.charCount = t.length;
            this.result = window.analyzeEmpathyMessage(t);
        },

        loadExample(ex) {
            this.text = ex.body;
            this.runAnalyze();
        },

        reset() {
            this.text = '';
            this.runAnalyze();
        },

        copySummary() {
            const r = this.result;
            const lines = [
                `Empathy score: ${r.overall}/100`,
                `Tone: ${r.toneLabel} — ${r.toneHint}`,
                '',
                'Dimensions:',
                ...r.dimensions.map((d) => `  • ${d.title}: ${d.score} (${d.levelLabel})`),
                '',
                'Strengths:',
                ...r.strengths.map((s) => `  • ${s}`),
                '',
                'Issues:',
                ...r.issues.map((s) => `  • ${s}`),
                '',
                'Suggestions:',
                ...r.suggestions.map((s) => `  • ${s}`),
            ];
            navigator.clipboard.writeText(lines.join('\n')).then(() => {
                this.copiedSummary = true;
                if (this.copyTimer) clearTimeout(this.copyTimer);
                this.copyTimer = setTimeout(() => { this.copiedSummary = false; }, 2000);
            });
        },
    };
}
</script>
@endverbatim
@endpush
