{{--
  Empathy Checker — client-side rules engine (no LLM, no API).
  Scores hiring/workplace messages on empathy dimensions.
--}}
<style>[x-cloak]{display:none!important}</style>
<div
    x-data="empathyChecker()"
    x-init="init()"
    class="space-y-5"
>
    {{-- Intro --}}
    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 space-y-1.5">
        <p class="font-semibold text-gray-900">Score your hiring and workplace messages for empathy</p>
        <p>Paste rejection emails, interview invites, follow-ups, or HR messages. Get scores across five dimensions, tone analysis, and actionable fixes — all in your browser, nothing uploaded.</p>
    </div>

    {{-- Quick examples --}}
    <div>
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">Try an example</p>
        <div class="flex flex-wrap gap-2">
            <template x-for="ex in examples" :key="ex.id">
                <button
                    type="button"
                    @click="loadExample(ex)"
                    class="text-xs px-3 py-1.5 rounded-lg border transition-colors"
                    :class="activeExample === ex.id
                        ? 'border-primary-300 bg-primary-50 text-primary-700'
                        : 'border-gray-200 bg-white text-gray-700 hover:border-primary-300 hover:bg-primary-50'"
                    x-text="ex.label"
                ></button>
            </template>
        </div>
    </div>

    {{-- Input --}}
    <div>
        <label for="ec-input" class="block text-sm font-medium text-gray-700 mb-1.5">Your message</label>
        <textarea
            id="ec-input"
            x-model="text"
            @input="scheduleAnalyze()"
            rows="10"
            placeholder="Paste or type your email, rejection, invite, or follow-up here…"
            class="block w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm text-gray-900 leading-relaxed focus:ring-2 focus:ring-primary-500 focus:border-transparent resize-y min-h-[180px]"
        ></textarea>
        <div class="mt-1.5 flex items-center justify-between text-xs text-gray-400">
            <div class="flex gap-3">
                <span x-text="wordCount + ' words'"></span>
                <span x-text="charCount.toLocaleString() + ' chars'"></span>
                <span x-show="msgType" x-cloak class="px-1.5 py-0.5 rounded bg-gray-100 text-gray-500 font-medium" x-text="msgType === 'rejection' ? 'Rejection detected' : msgType === 'progress_update' ? 'Progress update detected' : ''"></span>
            </div>
            <span>Max 50,000</span>
        </div>
    </div>

    {{-- Action buttons --}}
    <div class="flex flex-wrap gap-2">
        <button
            type="button"
            @click="reset()"
            class="inline-flex items-center px-3 py-2 text-xs font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors"
            :disabled="!trimmed.length"
        >Reset</button>
        <button
            type="button"
            @click="copySummary()"
            class="inline-flex items-center px-3 py-2 text-xs font-medium rounded-lg transition-colors"
            :class="copiedSummary ? 'bg-emerald-50 border border-emerald-300 text-emerald-700' : 'bg-primary-600 text-white hover:bg-primary-700'"
            :disabled="trimmed.length === 0"
        >
            <span x-show="!copiedSummary">Copy summary</span>
            <span x-show="copiedSummary" x-cloak>Copied!</span>
        </button>
    </div>

    {{-- Empty state --}}
    <div x-show="trimmed.length === 0" class="rounded-xl border border-dashed border-gray-200 bg-gray-50 p-6 text-center text-sm text-gray-500">
        Type a message or pick an example above to see your empathy score, tone, and suggestions.
    </div>

    {{-- ─── Results ─── --}}
    <template x-if="trimmed.length > 0">
        <div class="space-y-5" x-cloak>

            {{-- Score + tone row --}}
            <div class="grid grid-cols-2 gap-3">
                <div class="rounded-xl border border-gray-200 bg-white p-4 text-center shadow-sm">
                    <div class="text-3xl font-bold tabular-nums" :class="scoreColorClass" x-text="result.overall"></div>
                    <div class="text-xs text-gray-500 mt-1">Empathy score</div>
                    <div class="mt-2 h-1.5 rounded-full bg-gray-100 overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-500" :class="overallBarClass" :style="'width:' + result.overall + '%'"></div>
                    </div>
                </div>
                <div class="rounded-xl border p-4 flex flex-col justify-center" :class="toneBoxClass">
                    <div class="text-[10px] font-semibold uppercase tracking-wide opacity-60">Tone</div>
                    <div class="text-base font-semibold mt-0.5" x-text="result.toneLabel"></div>
                    <p class="text-xs mt-1 opacity-80 leading-snug" x-text="result.toneHint"></p>
                </div>
            </div>

            {{-- Dimensions --}}
            <div class="rounded-xl border border-gray-200 bg-white overflow-hidden">
                <div class="px-4 py-2.5 bg-gray-50 border-b border-gray-100">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Score breakdown</span>
                </div>
                <div class="divide-y divide-gray-50">
                    <template x-for="d in result.dimensions" :key="d.key">
                        <div class="px-4 py-3">
                            <div class="flex items-center justify-between gap-2 mb-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium text-gray-900" x-text="d.title"></span>
                                    <span class="text-[10px] font-semibold uppercase tracking-wide px-1.5 py-0.5 rounded-full"
                                          :class="d.badgeClass" x-text="d.levelLabel"></span>
                                </div>
                                <span class="text-sm font-bold tabular-nums text-gray-900" x-text="d.score"></span>
                            </div>
                            <div class="h-1.5 rounded-full bg-gray-100 overflow-hidden mb-1">
                                <div class="h-full rounded-full transition-all duration-300"
                                     :class="d.barClass"
                                     :style="'width:' + d.score + '%'"></div>
                            </div>
                            <p class="text-[11px] text-gray-500 leading-snug" x-text="d.explanation"></p>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Strengths + issues --}}
            <div class="space-y-3">
                <div class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-4" x-show="result.strengths.length">
                    <h4 class="text-xs font-semibold uppercase tracking-wide text-emerald-800 mb-2">Strengths</h4>
                    <ul class="text-sm text-emerald-900 space-y-1">
                        <template x-for="(s, idx) in result.strengths" :key="'s'+idx">
                            <li class="flex items-start gap-2">
                                <span class="text-emerald-500 shrink-0 mt-0.5">✓</span>
                                <span x-text="s"></span>
                            </li>
                        </template>
                    </ul>
                </div>
                <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-4" x-show="result.issues.length">
                    <h4 class="text-xs font-semibold uppercase tracking-wide text-amber-900 mb-2">Issues</h4>
                    <ul class="text-sm text-amber-950 space-y-1">
                        <template x-for="(s, idx) in result.issues" :key="'i'+idx">
                            <li class="flex items-start gap-2">
                                <span class="text-amber-500 shrink-0 mt-0.5">!</span>
                                <span x-text="s"></span>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>

            {{-- Suggestions --}}
            <div class="rounded-xl border border-primary-100 bg-primary-50/40 p-4" x-show="result.suggestions.length">
                <h4 class="text-xs font-semibold uppercase tracking-wide text-primary-900 mb-2">Suggestions</h4>
                <ul class="text-sm text-primary-950 space-y-1.5">
                    <template x-for="(s, idx) in result.suggestions" :key="'sg'+idx">
                        <li class="flex items-start gap-2">
                            <span class="text-primary-400 shrink-0 mt-0.5">→</span>
                            <span x-text="s"></span>
                        </li>
                    </template>
                </ul>
            </div>

            {{-- Missing empathy checks --}}
            <div class="rounded-xl border border-gray-200 bg-white p-4">
                <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-3">Empathy checklist</h4>
                <ul class="space-y-2">
                    <template x-for="m in result.missing" :key="m.id">
                        <li class="flex items-center gap-2.5 text-sm">
                            <span class="shrink-0 w-5 h-5 rounded-full flex items-center justify-center text-[10px]"
                                  :class="m.pass ? 'bg-emerald-100 text-emerald-600' : 'bg-amber-100 text-amber-600'"
                                  x-text="m.pass ? '✓' : '!'"></span>
                            <span :class="m.pass ? 'text-gray-600' : 'text-gray-900 font-medium'" x-text="m.label"></span>
                        </li>
                    </template>
                </ul>
            </div>

            {{-- Anxiety triggers (only if any) --}}
            <div class="rounded-xl border border-rose-200 bg-rose-50 p-4" x-show="result.anxiety.length" x-cloak>
                <h4 class="text-xs font-semibold uppercase tracking-wide text-rose-900 mb-2">Anxiety triggers</h4>
                <ul class="space-y-1.5">
                    <template x-for="(a, idx) in result.anxiety" :key="'a'+idx">
                        <li class="flex items-start gap-2 text-sm text-rose-900">
                            <span class="text-rose-400 shrink-0 mt-0.5">▸</span>
                            <span x-text="a"></span>
                        </li>
                    </template>
                </ul>
            </div>

            {{-- Pattern chips --}}
            <div class="space-y-3">
                <div class="rounded-xl border border-emerald-100 bg-emerald-50/40 p-4" x-show="result.positiveHits.length">
                    <h4 class="text-xs font-semibold text-emerald-800 mb-2">Positive patterns detected</h4>
                    <div class="flex flex-wrap gap-1.5">
                        <template x-for="p in result.positiveHits" :key="'p'+p.phrase">
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-white border border-emerald-200 text-xs text-emerald-900" x-text="p.phrase"></span>
                        </template>
                    </div>
                </div>
                <div class="rounded-xl border border-rose-100 bg-rose-50/40 p-4" x-show="result.negativeHits.length" x-cloak>
                    <h4 class="text-xs font-semibold text-rose-900 mb-2">Harsh or cold patterns</h4>
                    <div class="flex flex-wrap gap-1.5">
                        <template x-for="p in result.negativeHits" :key="'n'+p.phrase">
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-white border border-rose-200 text-xs text-rose-950" x-text="p.phrase"></span>
                        </template>
                    </div>
                </div>
                <div class="rounded-xl border border-amber-100 bg-amber-50/30 p-4" x-show="result.vagueHits.length" x-cloak>
                    <h4 class="text-xs font-semibold text-amber-900 mb-2">Vague or anxiety-inducing</h4>
                    <div class="flex flex-wrap gap-1.5">
                        <template x-for="p in result.vagueHits" :key="'v'+p.phrase">
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-white border border-amber-200 text-xs text-amber-950" x-text="p.phrase"></span>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Phrase review (highlighted text) --}}
            <div class="rounded-xl border border-gray-200 bg-white p-4">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                    <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500">Phrase review</h4>
                    <div class="flex flex-wrap gap-3 text-[10px] text-gray-500">
                        <span><span class="inline-block w-3 h-3 rounded bg-emerald-200 align-middle mr-0.5"></span> Supportive</span>
                        <span><span class="inline-block w-3 h-3 rounded bg-amber-200 align-middle mr-0.5"></span> Vague</span>
                        <span><span class="inline-block w-3 h-3 rounded bg-rose-200 align-middle mr-0.5"></span> Harsh</span>
                    </div>
                </div>
                <div class="text-sm leading-relaxed text-gray-800 border border-gray-100 rounded-lg p-3 bg-gray-50 max-h-56 overflow-y-auto"
                     x-html="result.highlightHtml"></div>
            </div>
        </div>
    </template>

    {{-- Tip --}}
    <div class="rounded-xl p-4 text-sm bg-blue-50 border border-blue-200 text-blue-800">
        <span class="font-semibold">Pro tip:</span>
        Read your message aloud before sending. If "unfortunately" is the first thing a candidate hears, lead with thanks instead — then deliver the news. Pair every piece of bad news with one specific, sincere acknowledgement.
    </div>
</div>

@push('scripts')
@verbatim
<script>
(function () {
    const MAX_CHARS = 50000;

    const WEIGHTS = {
        compoundOverallBoost: 15,
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
        clarityExplicitDate: 6,
        respectBase: 56,
        respectSignOff: 12,
        respectPlease: 4,
        respectDifficultDecision: 9,
        respectPersonalised: 8,
        warmthBase: 50,
        warmthPerWarmExtra: 6,
        warmthWarmExtraCap: 18,
        warmthPerSupportive: 7,
        warmthSupportiveCap: 14,
        warmthRobotic: 11,
        warmthColdOpener: 8,
        warmthEmpathyQualifier: 8,
        focusBase: 44,
        focusYouYourBoost: 12,
        focusProcessJargon: 7,
        focusActionItem: 6,
    };

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
            { re: /don['']t\s+hesitate/i, label: "Don't hesitate" },
        ],
        warmExtra: [
            { re: /really\s+appreciated/i, label: 'Really appreciated' },
            { re: /great\s+to\s+learn/i, label: 'Great to learn' },
            { re: /enjoyed\s+speaking/i, label: 'Enjoyed speaking' },
            { re: /look\s+forward\s+to/i, label: 'Look forward' },
            { re: /enjoyed\s+learning/i, label: 'Enjoyed learning' },
            { re: /pleasure\s+(?:to\s+)?(?:meet|speak)/i, label: 'Pleasure to meet/speak' },
        ],
        empathyQualifier: [
            { re: /\bi\s+understand\b/i, label: 'I understand' },
            { re: /\bi\s+know\s+this\s+isn['']t\s+easy/i, label: "I know this isn't easy" },
            { re: /\bi\s+realise\b/i, label: 'I realise' },
            { re: /\bwe\s+understand\b/i, label: 'We understand' },
            { re: /this\s+(?:may|might)\s+be\s+disappointing/i, label: 'Acknowledges disappointment' },
            { re: /(?:not|never)\s+an?\s+easy\s+(?:decision|message)/i, label: 'Not an easy decision' },
        ],
        personalised: [
            { re: /^(?:hi|hello|dear)\s+[A-Z][a-z]+/m, label: 'Uses candidate name' },
        ],
        actionItem: [
            { re: /please\s+(?:complete|fill|submit|review|confirm|sign|reply|respond)/i, label: 'Clear action request' },
            { re: /click\s+(?:the|this)\s+link/i, label: 'Link action' },
            { re: /(?:attached|find\s+attached)/i, label: 'Attachment reference' },
        ],
    };

    const PHRASES = {
        positive: [
            'thank you for your time',
            'thank you for applying',
            'we appreciate your interest',
            'enjoyed learning more about your experience',
            'we will update you by',
            'feel free to reach out',
            'i understand',
            'we understand',
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
            'per our policy',
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

    function clamp(n, lo, hi) { return Math.max(lo, Math.min(hi, n)); }

    function matchPatternLabels(text, rows) {
        const labels = [];
        const seen = new Set();
        for (const { re, label } of rows) {
            if (new RegExp(re.source, 'i').test(text) && !seen.has(label)) {
                seen.add(label);
                labels.push(label);
            }
        }
        return labels;
    }

    function countInsensitive(haystack, needle) {
        if (!needle) return 0;
        const h = haystack.toLowerCase(), n = needle.toLowerCase();
        let c = 0, pos = 0;
        while (true) {
            const i = h.indexOf(n, pos);
            if (i === -1) break;
            c++; pos = i + 1;
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
        return hits;
    }

    function wordCountFn(t) { return t.trim().split(/\s+/).filter(Boolean).length; }

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

    function hasSignOff(text) {
        const tail = text.slice(Math.max(0, text.length - 420));
        return SIGN_OFF_RES.some(re => re.test(tail));
    }

    function applySeverityBuffer(lower, original, phrases, level, buf) {
        for (const phrase of phrases) {
            const p = phrase.toLowerCase();
            let pos = 0;
            while (true) {
                const i = lower.indexOf(p, pos);
                if (i === -1) break;
                for (let j = i; j < i + phrase.length && j < buf.length; j++) {
                    buf[j] = Math.max(buf[j], level);
                }
                pos = i + 1;
            }
        }
    }

    function applyRegexSeverityBuffer(text, rows, level, buf) {
        for (const { re } of rows) {
            const rx = new RegExp(re.source, 'gi');
            let m;
            while ((m = rx.exec(text)) !== null) {
                for (let j = m.index; j < m.index + m[0].length && j < buf.length; j++) {
                    buf[j] = Math.max(buf[j], level);
                }
                if (m[0].length === 0) rx.lastIndex++;
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
        const esc = s => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

        for (let i = 1; i <= original.length; i++) {
            const next = i < original.length ? buf[i] : -1;
            if (i === original.length || buf[i] !== cur) {
                const chunk = original.slice(start, i);
                html += cur > 0 ? open(cur) + esc(chunk) + '</mark>' : esc(chunk);
                start = i;
                cur = next;
            }
        }
        return html.replace(/\n/g, '<br>');
    }

    window.analyzeEmpathyMessage = function(raw) {
        const text = raw.length > MAX_CHARS ? raw.slice(0, MAX_CHARS) : raw;
        const trimmed = text.trim();
        const lower = text.toLowerCase();
        const wc = wordCountFn(trimmed);

        const empty = {
            overall: 0, toneLabel: '—', toneHint: '', dimensions: [],
            strengths: [], issues: [], suggestions: [], missing: [],
            anxiety: [], positiveHits: [], negativeHits: [], vagueHits: [],
            highlightHtml: '', msgType: '',
        };
        if (wc === 0) return empty;

        const msgType = inferMessageType(lower);
        const signOffOK = hasSignOff(text);
        const opening = lower.slice(0, Math.min(lower.length, 160));

        const gratLabels = matchPatternLabels(text, PATTERNS.gratitude);
        const effortLabels = matchPatternLabels(text, PATTERNS.effort);
        const supportiveLabels = matchPatternLabels(text, PATTERNS.supportive);
        const warmExtraLabels = matchPatternLabels(text, PATTERNS.warmExtra);
        const empathyQualLabels = matchPatternLabels(text, PATTERNS.empathyQualifier);
        const personalisedLabels = matchPatternLabels(text, PATTERNS.personalised);
        const actionLabels = matchPatternLabels(text, PATTERNS.actionItem);

        const hasGratitude = gratLabels.length > 0;
        const hasEffort = effortLabels.length > 0;
        const compoundPair = hasGratitude && hasEffort;
        const isPersonalised = personalisedLabels.length > 0;
        const hasEmpathyQual = empathyQualLabels.length > 0;

        const negHits = collectPhraseHits(lower, PHRASES.negative);
        const vagueHits = collectPhraseHits(lower, PHRASES.vague);
        const posHits = collectPhraseHits(lower, PHRASES.positive);

        const hasTimelineWord = PHRASES.timelineWords.some(t => lower.includes(t));
        const hasWeekdayDeadline = /\bby\s+(?:monday|tuesday|wednesday|thursday|friday|saturday|sunday)\b/i.test(lower);
        const hasUpdateBy = /(?:provide\s+)?(?:an\s+)?update\s+by|update\s+you\s+by|hear\s+from\s+us\s+by/i.test(lower);

        // ── Acknowledgement ──
        let acknowledgement = WEIGHTS.ackBase
            + Math.min(WEIGHTS.ackGratitudeCap, gratLabels.length * WEIGHTS.ackPerGratitude)
            + Math.min(WEIGHTS.ackEffortCap, effortLabels.length * WEIGHTS.ackPerEffort);
        if (/(thank|thanks|appreciate|grateful)/.test(opening)) acknowledgement += WEIGHTS.ackOpeningBonus;
        if (hasEmpathyQual) acknowledgement += 6;
        if (wc > 28 && !hasGratitude) acknowledgement -= msgType === 'rejection' ? 20 : 12;
        acknowledgement = clamp(acknowledgement, 0, 100);

        // ── Clarity ──
        let clarity = WEIGHTS.clarityBase;
        if (hasWeekdayDeadline) clarity += WEIGHTS.clarityByWeekday;
        else if (hasUpdateBy) clarity += WEIGHTS.clarityUpdateByPhrase;
        let tw = 0;
        for (const w of PHRASES.timelineWords) {
            if (hasWeekdayDeadline && w.startsWith('by ')) continue;
            if (lower.includes(w)) tw += WEIGHTS.clarityTimelineWord;
        }
        clarity += Math.min(WEIGHTS.clarityTimelineCap, tw);
        if (/\bnext steps\b/.test(lower)) clarity += WEIGHTS.clarityNextSteps;
        if (/\d{1,2}\/\d{1,2}|\d{4}-\d{2}-\d{2}/.test(text)) clarity += WEIGHTS.clarityExplicitDate;
        if (actionLabels.length) clarity += WEIGHTS.focusActionItem;

        const vagueOnly = /\b(soon|shortly|in due course)\b/.test(lower) && !hasTimelineWord && !hasWeekdayDeadline;
        const touchVague = /we\s+will\s+be\s+in\s+touch|we'll\s+be\s+in\s+touch/.test(lower)
            && !/\b(monday|tuesday|wednesday|thursday|friday|today|tomorrow|next week|\d)\b/i.test(lower);
        const vaguePenalty = msgType === 'progress_update' ? WEIGHTS.clarityVaguePenaltyProgress : WEIGHTS.clarityVaguePenalty;
        if (vagueOnly) clarity -= vaguePenalty;
        if (touchVague) clarity -= msgType === 'progress_update' ? 5 : WEIGHTS.clarityVaguePenalty;
        clarity = clamp(clarity, 0, 100);

        // ── Respect ──
        let respect = WEIGHTS.respectBase;
        if (signOffOK) respect += WEIGHTS.respectSignOff;
        if (/\bplease\b/.test(lower)) respect += WEIGHTS.respectPlease;
        if (/(difficult decision|not an easy decision)/.test(lower)) respect += WEIGHTS.respectDifficultDecision;
        if (isPersonalised) respect += WEIGHTS.respectPersonalised;
        if (msgType === 'rejection') {
            if (/not a good fit/.test(lower)) respect -= 9;
            if (/(you have not been selected|you were not selected)/.test(lower) && gratLabels.length === 0) respect -= 14;
            if (negHits.length > 2 && gratLabels.length < 2) respect -= 8;
        }
        if (!signOffOK && wc > 35) respect -= 10;
        respect = clamp(respect, 0, 100);

        // ── Warmth ──
        let warmth = WEIGHTS.warmthBase
            + Math.min(WEIGHTS.warmthWarmExtraCap, warmExtraLabels.length * WEIGHTS.warmthPerWarmExtra)
            + Math.min(WEIGHTS.warmthSupportiveCap, supportiveLabels.length * WEIGHTS.warmthPerSupportive);
        if (hasEmpathyQual) warmth += WEIGHTS.warmthEmpathyQualifier;
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

        // ── Candidate focus ──
        const youMatches = (lower.match(/\b(you|your|you're|you've)\b/g) || []).length;
        const weMatches = (lower.match(/\b(we|our|us|we're|we've)\b/g) || []).length;
        const focusRatio = youMatches / (youMatches + weMatches + 0.22);
        let candidateFocus = WEIGHTS.focusBase + Math.round((focusRatio - 0.42) * 50);
        if (/\b(your experience|your background|your application|insight you)\b/i.test(lower)) candidateFocus += WEIGHTS.focusYouYourBoost;
        if (actionLabels.length) candidateFocus += WEIGHTS.focusActionItem;
        if (/\bour (process|policy)\b/.test(lower) && youMatches < 4) candidateFocus -= WEIGHTS.focusProcessJargon;
        candidateFocus = clamp(candidateFocus, 0, 100);

        // ── Overall ──
        const dims = [
            { key: 'acknowledgement', title: 'Acknowledgement', score: acknowledgement },
            { key: 'clarity', title: 'Clarity', score: clarity },
            { key: 'respect', title: 'Respect', score: respect },
            { key: 'warmth', title: 'Warmth', score: warmth },
            { key: 'candidate_focus', title: 'Candidate focus', score: candidateFocus },
        ];
        const avgDim = dims.reduce((s, d) => s + d.score, 0) / dims.length;
        let overall = Math.round(avgDim + (compoundPair ? WEIGHTS.compoundOverallBoost : 0));
        overall = clamp(overall, 0, 100);

        // ── Tone ──
        const casualScore = PHRASES.casual.reduce((s, p) => s + countInsensitive(lower, p), 0)
            + ((text.match(/!/g) || []).length > 4 ? 3 : 0);
        const coldSignals = negHits.length + (!hasGratitude && wc > 35 && msgType === 'rejection' ? 3 : 0);
        const warmSignals = warmExtraLabels.length + supportiveLabels.length + empathyQualLabels.length + (warmth > 64 ? 1 : 0);

        let toneLabel = 'Neutral';
        let toneHint = 'Balanced — neutral is fine when timelines and respect are clear.';
        if (casualScore >= 4 || (casualScore >= 2 && /(lol|gonna|hey guys)/.test(lower))) {
            toneLabel = 'Overly casual';
            toneHint = 'Too informal for hiring communication.';
        } else if (coldSignals > warmSignals + 4 && warmth < 48 && msgType === 'rejection') {
            toneLabel = 'Cold';
            toneHint = 'Add acknowledgement and specificity for bad news.';
        } else if (warmSignals >= 3 && warmth >= 60 && hasGratitude) {
            toneLabel = 'Warm';
            toneHint = 'Sounds human and considerate. Keep dates concrete.';
        } else if ((signOffOK || /kind regards|best regards|sincerely|dear\s+\w/i.test(lower)) && casualScore < 2 && warmth < 72) {
            toneLabel = 'Professional';
            toneHint = 'Polite and appropriate for workplace email.';
        }

        // ── Positive pattern chips ──
        const positiveHits = [];
        gratLabels.forEach(l => positiveHits.push({ phrase: l }));
        effortLabels.forEach(l => positiveHits.push({ phrase: l }));
        supportiveLabels.forEach(l => positiveHits.push({ phrase: l }));
        empathyQualLabels.forEach(l => positiveHits.push({ phrase: l }));
        warmExtraLabels.forEach(l => positiveHits.push({ phrase: l }));
        if (isPersonalised) positiveHits.push({ phrase: 'Personalised greeting' });
        if (compoundPair) positiveHits.push({ phrase: 'Gratitude + effort combo' });

        // ── Strengths ──
        const strengths = [];
        if (isPersonalised) strengths.push('Uses the candidate\'s name — feels personal.');
        if (hasGratitude) strengths.push('Gratitude or appreciation is present.');
        if (hasEffort) strengths.push('Recognises the candidate\'s time or contribution.');
        if (hasEmpathyQual) strengths.push('Acknowledges difficulty or shows understanding.');
        if (supportiveLabels.length) strengths.push('Uses supportive, open-door language.');
        if (hasWeekdayDeadline || hasUpdateBy) strengths.push('Gives a specific update window or deadline.');
        if (signOffOK) strengths.push('Ends with a proper sign-off.');
        if (compoundPair) strengths.push('Combines thanks with effort recognition — strong empathy signal.');
        if (actionLabels.length) strengths.push('Includes a clear action item for the reader.');
        if (msgType === 'progress_update') strengths.push('Reads as a progress update — context-adjusted scoring.');

        // ── Issues ──
        const issues = [];
        if (negHits.length && msgType === 'rejection') issues.push(`${negHits.length} harsh rejection phrase(s) — balance with warmth.`);
        if (vagueHits.length && !hasTimelineWord && !hasWeekdayDeadline) issues.push('Vague timing — add a firm date.');
        if (!hasGratitude && wc > 30 && msgType === 'rejection') issues.push('No appreciation in a rejection — thank them for their time.');
        if (!isPersonalised && wc > 20) issues.push('No name used — "Dear Applicant" feels impersonal.');
        if (warmth < 44 && roboticHits) issues.push('Stock/process-heavy phrasing — add a human touch.');
        if (!hasEmpathyQual && msgType === 'rejection') issues.push('No empathy qualifier (e.g. "I understand", "not an easy decision").');

        const explainRejectOk = msgType !== 'rejection'
            || !PHRASES.rejectionSignals.some(r => lower.includes(r))
            || lower.length > 200
            || /(because|since|although|while we|difficult decision)/i.test(lower);

        // ── Missing checks ──
        const missing = [
            { id: 'thanks', label: 'Thank-you or appreciation', pass: hasGratitude },
            { id: 'name', label: 'Personalised greeting (uses name)', pass: isPersonalised },
            { id: 'time', label: 'Acknowledges time or effort', pass: hasEffort || /(time|effort|insight)/i.test(lower) },
            { id: 'empathy', label: 'Empathy qualifier (I understand, not easy)', pass: hasEmpathyQual || explainRejectOk },
            { id: 'next', label: 'Clear next step or status', pass: /\b(will|we'll|expect|interview|update|provide|questions)\b/i.test(lower) && wc > 12 },
            { id: 'timeline', label: 'Timeline or expected update', pass: hasTimelineWord || hasWeekdayDeadline || hasUpdateBy || /\d{1,2}\/\d{1,2}/.test(text) },
            { id: 'human', label: 'Human phrasing (not just jargon)', pass: warmth >= 46 || supportiveLabels.length > 0 },
            { id: 'signoff', label: 'Proper sign-off', pass: signOffOK },
        ];

        // ── Anxiety triggers ──
        const anxiety = [];
        if (msgType === 'progress_update' && /\b(soon|shortly)\b/.test(lower) && !hasWeekdayDeadline) {
            anxiety.push('"Soon" / "shortly" is softer than a date — name a day if anxiety is high.');
        } else if (/\b(soon|shortly)\b/.test(lower) && !hasTimelineWord && !hasWeekdayDeadline) {
            anxiety.push('"Soon" / "shortly" without a date keeps people guessing.');
        }
        if (touchVague && msgType !== 'progress_update') {
            anxiety.push('"We\'ll be in touch" without a timeframe feels open-ended.');
        }
        if (msgType === 'rejection' && PHRASES.rejectionSignals.some(r => lower.includes(r)) && gratLabels.length < 2) {
            anxiety.push('Rejection wording with limited appreciation can feel abrupt.');
        }
        if (!signOffOK && wc > 45) {
            anxiety.push('No sign-off — message may feel cut short.');
        }
        if (!isPersonalised && msgType === 'rejection' && wc > 40) {
            anxiety.push('Generic greeting on a rejection — feels like a mass email.');
        }

        // ── Suggestions ──
        const suggestions = [];
        if (!isPersonalised && wc > 15) suggestions.push('Use the candidate\'s first name in the greeting.');
        if (!hasGratitude && wc > 20) suggestions.push('Add a thank-you near the start, referencing their time or interest.');
        if (!hasEmpathyQual && msgType === 'rejection') suggestions.push('Add an empathy qualifier like "I understand this isn\'t the news you hoped for".');
        if (!hasTimelineWord && !hasWeekdayDeadline && msgType !== 'general') suggestions.push('Name a specific day or window for the next update.');
        if (negHits.length && msgType === 'rejection') suggestions.push('Soften template rejection lines with one specific, sincere sentence.');
        if (!signOffOK && wc > 30) suggestions.push('Close with Best, Thanks, or Kind regards on their own line.');
        if (roboticHits) suggestions.push('Replace process jargon with conversational language.');
        if (suggestions.length === 0) suggestions.push('Read aloud once — if anything sounds generic, swap in one concrete detail.');

        // ── Highlight HTML ──
        const buf = new Uint8Array(text.length);
        const oLower = text.toLowerCase();
        applySeverityBuffer(oLower, text, PHRASES.positive, 1, buf);
        applyRegexSeverityBuffer(text, PATTERNS.gratitude, 1, buf);
        applyRegexSeverityBuffer(text, PATTERNS.effort, 1, buf);
        applyRegexSeverityBuffer(text, PATTERNS.supportive, 1, buf);
        applyRegexSeverityBuffer(text, PATTERNS.empathyQualifier, 1, buf);
        applySeverityBuffer(oLower, text, PHRASES.vague, 2, buf);
        applySeverityBuffer(oLower, text, PHRASES.negative, 3, buf);
        const highlightHtml = buildHighlightHtml(text, buf);

        // ── Dimension display ──
        const levelMeta = score => {
            if (score >= 70) return { levelLabel: 'Strong', badgeClass: 'bg-emerald-100 text-emerald-800', barClass: 'bg-emerald-500' };
            if (score >= 40) return { levelLabel: 'Mixed', badgeClass: 'bg-amber-100 text-amber-800', barClass: 'bg-amber-400' };
            return { levelLabel: 'Weak', badgeClass: 'bg-rose-100 text-rose-800', barClass: 'bg-rose-400' };
        };

        const explanations = {
            acknowledgement: 'Gratitude and recognition of the reader\'s time and effort.',
            clarity: 'Concrete timelines, next steps, and specific language vs vague promises.',
            respect: 'Polite framing, personalisation, and considerate delivery.',
            warmth: 'Human, empathetic phrasing rather than transactional or robotic wording.',
            candidate_focus: 'Language centred on the reader (you/your) vs internal process-speak.',
        };

        const dimensionsOut = dims.map(d => {
            const lm = levelMeta(d.score);
            return { key: d.key, title: d.title, score: Math.round(d.score), ...lm, explanation: explanations[d.key] || '' };
        });

        return {
            overall, toneLabel, toneHint,
            dimensions: dimensionsOut,
            strengths: strengths.slice(0, 7),
            issues: issues.filter(i => !i.includes('No major')).slice(0, 6),
            suggestions: [...new Set(suggestions)].slice(0, 8),
            missing, anxiety,
            positiveHits: positiveHits.slice(0, 14),
            negativeHits: negHits.slice(0, 12),
            vagueHits: vagueHits.slice(0, 12),
            highlightHtml, msgType,
        };
    };
})();

function empathyChecker() {
    return {
        text: '',
        trimmed: '',
        wordCount: 0,
        charCount: 0,
        msgType: '',
        activeExample: null,
        result: {
            overall: 0, toneLabel: '—', toneHint: '', dimensions: [],
            strengths: [], issues: [], suggestions: [], missing: [],
            anxiety: [], positiveHits: [], negativeHits: [], vagueHits: [],
            highlightHtml: '', msgType: '',
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
                body: "Hi Jordan,\n\nThank you for taking the time to interview with us and for your patience while we completed the process. We enjoyed learning more about your experience.\n\nAfter careful consideration, we will not be moving forward with your application for this role. I know this isn't easy to hear, and the decision was difficult given the strength of the field.\n\nWe will update you by Friday if we open a role that better aligns with your background. Please don't hesitate to reach out if you have any questions. Thank you again for your interest.\n\nKind regards,\nAlex",
            },
            {
                id: 'vague',
                label: 'Vague follow-up',
                body: "Hi,\n\nThanks for coming in. We will be in touch soon with next steps.\n\nBest",
            },
            {
                id: 'invite',
                label: 'Thoughtful invite',
                body: "Hi Sam,\n\nThank you for applying — we really appreciated learning about your background. We would like to invite you to a 45-minute video interview on Thursday at 2pm. You will meet with two engineers; please confirm by Wednesday COB.\n\nIf you need an alternative time, feel free to reach out and we will find a slot.\n\nWe look forward to speaking with you.\n\nBest regards,\nPat",
            },
            {
                id: 'progress',
                label: 'Status update',
                body: "Hi Taylor,\n\nI wanted to give you a quick update on where things stand. We are still interviewing candidates and expect to provide an update by next Friday.\n\nI understand the wait can be frustrating, and I appreciate your patience. If you have any questions in the meantime, please don't hesitate to reach out.\n\nBest,\nJamie",
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

        get overallBarClass() {
            const s = this.result.overall;
            if (s >= 72) return 'bg-emerald-500';
            if (s >= 45) return 'bg-amber-400';
            return 'bg-rose-400';
        },

        get toneBoxClass() {
            const t = this.result.toneLabel;
            if (t === 'Warm') return 'bg-emerald-50 border-emerald-200 text-emerald-900';
            if (t === 'Professional') return 'bg-gray-50 border-gray-200 text-gray-900';
            if (t === 'Cold') return 'bg-gray-100 border-gray-300 text-gray-900';
            if (t === 'Overly casual') return 'bg-orange-50 border-orange-200 text-orange-900';
            return 'bg-gray-50 border-gray-200 text-gray-800';
        },

        init() {
            this.runAnalyze();
        },

        scheduleAnalyze() {
            this.activeExample = null;
            if (this.debounceId) clearTimeout(this.debounceId);
            this.debounceId = setTimeout(() => this.runAnalyze(), 280);
        },

        runAnalyze() {
            const t = this.text.length > 50000 ? this.text.slice(0, 50000) : this.text;
            this.text = t;
            this.trimmed = t.trim();
            this.wordCount = this.trimmed ? this.trimmed.split(/\s+/).filter(Boolean).length : 0;
            this.charCount = t.length;
            this.result = window.analyzeEmpathyMessage(t);
            this.msgType = this.result.msgType;
        },

        loadExample(ex) {
            this.activeExample = ex.id;
            this.text = ex.body;
            this.runAnalyze();
        },

        reset() {
            this.text = '';
            this.activeExample = null;
            this.runAnalyze();
        },

        copySummary() {
            const r = this.result;
            const lines = [
                `Empathy score: ${r.overall}/100`,
                `Tone: ${r.toneLabel} — ${r.toneHint}`,
                '',
                'Dimensions:',
                ...r.dimensions.map(d => `  • ${d.title}: ${d.score} (${d.levelLabel})`),
                '',
                r.strengths.length ? 'Strengths:' : null,
                ...r.strengths.map(s => `  ✓ ${s}`),
                '',
                r.issues.length ? 'Issues:' : null,
                ...r.issues.map(s => `  ! ${s}`),
                '',
                'Suggestions:',
                ...r.suggestions.map(s => `  → ${s}`),
                '',
                'Checklist:',
                ...r.missing.map(m => `  ${m.pass ? '✓' : '✗'} ${m.label}`),
            ].filter(l => l !== null);
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
