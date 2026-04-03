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

    /** Extend these arrays to tune detection without touching the rest of the engine. */
    const PHRASES = {
        positive: [
            'thank you for your time',
            'thank you for applying',
            'thanks for your patience',
            'we appreciate your interest',
            'we appreciate the effort',
            'appreciate you taking the time',
            'enjoyed learning more about your experience',
            'we enjoyed speaking with you',
            'we wanted to let you know',
            'we will update you by',
            'you will hear from us by',
            'thank you for interviewing',
            'grateful for the opportunity to',
            'value the time you invested',
            'thank you for your understanding',
        ],
        negative: [
            'unfortunately',
            'regret to inform you',
            'due to high volume',
            'due to the volume',
            'not a good fit',
            'not a strong fit',
            'we have decided to move forward with other candidates',
            'moving forward with other candidates',
            'we will be in touch soon',
            'we\'ll be in touch soon',
            'after careful consideration',
            'we are unable to proceed',
            'you have not been selected',
            'you were not selected',
        ],
        vague: [
            'we will be in touch',
            'we\'ll be in touch',
            'at your earliest convenience',
            'in due course',
            'stay tuned',
            'shortly',
            'soon',
            'as soon as possible',
            'touch base',
            'circle back',
        ],
        timeline: [
            'today',
            'tomorrow',
            'next week',
            'by friday',
            'by monday',
            'by tuesday',
            'by wednesday',
            'by thursday',
            'end of day',
            'eod',
            'end of week',
            'within 24 hours',
            'within 48 hours',
            'within 3 days',
            'within three days',
            'next steps',
            'calendar',
            'schedule a call',
        ],
        gratitude: [
            'thank you',
            'thanks for',
            'appreciate your',
            'we appreciate',
            'grateful',
        ],
        timeAck: [
            'your time',
            'time you spent',
            'time and effort',
            'effort you put',
            'invested in the process',
        ],
        warm: [
            'we wanted to reach out',
            'personally',
            'it was a pleasure',
            'great speaking',
            'impressed by',
        ],
        robotic: [
            'due to the large number',
            'high volume of applicants',
            'standard process',
        ],
        casual: [
            'lol',
            'omg',
            'gonna',
            'hey guys',
            'btw',
            'np',
            'asap',
        ],
        politeClose: [
            'kind regards',
            'best regards',
            'sincerely',
            'warm regards',
        ],
        rejectionSignals: [
            'not selected',
            'unable to offer',
            'will not be moving forward',
            'not move forward',
            'reject',
            'unsuccessful',
        ],
    };

    function clamp(n, lo, hi) {
        return Math.max(lo, Math.min(hi, n));
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
        const seen = {};
        for (const phrase of list) {
            const c = countInsensitive(text, phrase);
            if (c > 0) {
                hits.push({ phrase, count: c });
                seen[phrase] = c;
            }
        }
        hits.sort((a, b) => b.count - a.count || a.phrase.localeCompare(b.phrase));
        return { hits, map: seen };
    }

    function wordCountFn(t) {
        const w = t.trim().split(/\s+/).filter(Boolean);
        return w.length;
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

    function buildHighlightHtml(original, buf) {
        if (!original.length) return '';
        let html = '';
        let cur = buf[0];
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
            const ch = original[i - 1];
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

        const posH = collectPhraseHits(lower, PHRASES.positive);
        const negH = collectPhraseHits(lower, PHRASES.negative);
        const vagueH = collectPhraseHits(lower, PHRASES.vague);

        let gratitudeScore = 0;
        for (const g of PHRASES.gratitude) {
            gratitudeScore += countInsensitive(lower, g) * 6;
        }
        gratitudeScore = clamp(gratitudeScore, 0, 30);

        let timeAck = 0;
        for (const g of PHRASES.timeAck) {
            if (lower.includes(g)) timeAck += 12;
        }
        timeAck = clamp(timeAck, 0, 24);

        let acknowledgement = 42 + gratitudeScore + timeAck;
        if (wc > 20 && gratitudeScore === 0) acknowledgement -= 22;
        const opening = lower.slice(0, Math.min(lower.length, 140));
        if (/(thank|thanks|appreciate)/.test(opening)) acknowledgement += 12;
        acknowledgement = clamp(acknowledgement, 0, 100);

        let clarity = 48;
        for (const t of PHRASES.timeline) {
            if (lower.includes(t)) clarity += 5;
        }
        clarity = clamp(clarity, 0, 95);
        if (/\b(by|before)\s+(monday|tuesday|wednesday|thursday|friday|saturday|sunday)\b/.test(lower)) clarity += 10;
        if (/\d{1,2}\/\d{1,2}|\d{4}-\d{2}-\d{2}/.test(text)) clarity += 8;
        if (/\b(soon|shortly|in due course)\b/.test(lower) && !PHRASES.timeline.some((x) => lower.includes(x))) clarity -= 14;
        if (lower.includes('we will be in touch') || lower.includes('we\'ll be in touch')) {
            const hasDateNearby = /(monday|tuesday|wednesday|thursday|friday|today|tomorrow|next week|\d)/.test(lower);
            if (!hasDateNearby) clarity -= 12;
        }
        if (/\bnext steps\b/.test(lower)) clarity += 8;
        clarity = clamp(clarity, 0, 100);

        let respect = 52;
        if (PHRASES.politeClose.some((p) => lower.includes(p))) respect += 8;
        if (/\bplease\b/.test(lower)) respect += 4;
        if (/(difficult decision|not an easy decision)/.test(lower)) respect += 10;
        if (/not a good fit/.test(lower)) respect -= 8;
        if (/(you have not been selected|you were not selected)/.test(lower) && gratitudeScore < 6) respect -= 12;
        respect = clamp(respect, 0, 100);

        let warmth = 50;
        for (const w of PHRASES.warm) {
            if (lower.includes(w)) warmth += 6;
        }
        warmth += Math.min(posH.hits.length * 4, 16);
        for (const r of PHRASES.robotic) {
            if (lower.includes(r)) warmth -= 12;
        }
        if (/^unfortunately\b/m.test(trimmed) || lower.startsWith('unfortunately')) warmth -= 8;
        if (/regret to inform/.test(lower)) warmth -= 6;
        warmth = clamp(warmth, 0, 100);

        const youMatches = (lower.match(/\b(you|your|you're|you've)\b/g) || []).length;
        const weMatches = (lower.match(/\b(we|our|us|we're|we've)\b/g) || []).length;
        const focusRatio = youMatches / (youMatches + weMatches + 0.25);
        let candidateFocus = 45 + Math.round((focusRatio - 0.45) * 55);
        if (/\b(your experience|your background|your application)\b/.test(lower)) candidateFocus += 10;
        if (/\bour (process|policy|team's)\b/.test(lower) && youMatches < 3) candidateFocus -= 8;
        candidateFocus = clamp(candidateFocus, 0, 100);

        const dims = [
            { key: 'acknowledgement', title: 'Acknowledgement', score: acknowledgement },
            { key: 'clarity', title: 'Clarity', score: clarity },
            { key: 'respect', title: 'Respect', score: respect },
            { key: 'warmth', title: 'Warmth', score: warmth },
            { key: 'candidate_focus', title: 'Candidate focus', score: candidateFocus },
        ];

        const overall = Math.round(dims.reduce((s, d) => s + d.score, 0) / dims.length);

        const casualScore = PHRASES.casual.reduce((s, p) => s + countInsensitive(lower, p), 0)
            + ((text.match(/!/g) || []).length > 4 ? 3 : 0);
        const coldSignals = negH.hits.length + (gratitudeScore === 0 && wc > 30 ? 2 : 0) + (warmth < 38 ? 2 : 0);
        const warmSignals = posH.hits.length + (warmth > 62 ? 2 : 0);

        let toneLabel = 'Neutral';
        let toneHint = 'Balanced formality — add clearer timelines or gratitude to shift the feel.';
        if (casualScore >= 4 || (casualScore >= 2 && /(lol|gonna|hey guys)/.test(lower))) {
            toneLabel = 'Overly casual';
            toneHint = 'Slang or very informal markers may undermine professionalism in hiring mail.';
        } else if (coldSignals > warmSignals + 3 && warmth < 45) {
            toneLabel = 'Cold';
            toneHint = 'Reads distant or template-heavy — soften with acknowledgement and specificity.';
        } else if (warmSignals > coldSignals + 4 && warmth >= 58 && acknowledgement >= 55) {
            toneLabel = 'Warm';
            toneHint = 'Human and appreciative — ensure timelines stay concrete if next steps matter.';
        } else if (/(sincerely|kind regards|best regards|dear )/.test(lower) && casualScore < 2 && warmth < 70) {
            toneLabel = 'Professional';
            toneHint = 'Polished and conventional — good baseline for formal hiring communication.';
        }

        const strengths = [];
        if (posH.hits.length) strengths.push(`Uses supportive phrasing (${posH.hits.length} positive pattern${posH.hits.length > 1 ? 's' : ''} detected).`);
        if (PHRASES.timeline.some((t) => lower.includes(t))) strengths.push('Includes concrete timing or scheduling language.');
        if (/(thank|thanks|appreciate)/.test(opening)) strengths.push('Opens with appreciation — sets a respectful tone early.');
        if (/\bnext steps\b/.test(lower)) strengths.push('Mentions next steps, which reduces ambiguity.');
        if (strengths.length === 0) strengths.push('Message is readable — add explicit gratitude and timing to strengthen empathy.');

        const issues = [];
        if (negH.hits.length) issues.push(`Contains ${negH.hits.length} phrase${negH.hits.length > 1 ? 's' : ''} that often read as cold or corporate-default.`);
        if (vagueH.hits.length && !PHRASES.timeline.some((t) => lower.includes(t))) issues.push('Uses vague timing language without a clear date or window.');
        if (gratitudeScore === 0 && wc > 25) issues.push('Little or no explicit gratitude — risky for disappointing news.');
        if (warmth < 40) issues.push('Tone skews transactional; a human acknowledgement would help.');
        if (issues.length === 0) issues.push('No major red-flag phrases — focus on tightening timelines and closure.');

        const missing = [
            { id: 'thanks', label: 'Explicit thank-you or appreciation', pass: gratitudeScore > 5 || /(thank|thanks|appreciate)/.test(lower) },
            { id: 'time', label: 'Acknowledgement of their time or effort', pass: timeAck > 0 || /(time|effort|invested)/.test(lower) },
            { id: 'next', label: 'Clear next step or outcome', pass: /\b(will|we'll|please|schedule|calendar|interview|offer|decision)\b/.test(lower) && wc > 15 },
            { id: 'timeline', label: 'Timeline or date when follow-up is expected', pass: PHRASES.timeline.some((t) => lower.includes(t)) || /\b\d{1,2}\/\d{1,2}\b/.test(text) },
            { id: 'human', label: 'Human phrasing (not only process jargon)', pass: warmth >= 42 },
            { id: 'explain', label: 'Explanation when sharing disappointing news', pass: !PHRASES.rejectionSignals.some((r) => lower.includes(r)) || lower.length > 220 || /(because|since|although|while we)/.test(lower) },
        ];

        const anxiety = [];
        if (/\b(soon|shortly)\b/.test(lower) && !PHRASES.timeline.some((t) => lower.includes(t))) {
            anxiety.push('“Soon” or “shortly” without a date keeps people guessing.');
        }
        if (/we will be in touch|we'll be in touch/.test(lower) && !/\b(monday|tuesday|wednesday|thursday|friday|today|tomorrow|next week|\d)\b/.test(lower)) {
            anxiety.push('“We’ll be in touch” reads uncertain without a timeframe.');
        }
        if (PHRASES.rejectionSignals.some((r) => lower.includes(r)) && gratitudeScore < 8) {
            anxiety.push('Rejection language with little appreciation can feel abrupt.');
        }
        if (trimmed.length < 120 && PHRASES.rejectionSignals.some((r) => lower.includes(r))) {
            anxiety.push('Very short bad-news notes often feel like a template — even one human line helps.');
        }
        if (!/(\bregards\b|sincerely|best wishes|thank you$)/m.test(lower) && wc > 40) {
            anxiety.push('Ending without a warm sign-off can feel like the message was cut off.');
        }
        if (/passive|unable to|not able to proceed/.test(lower) && !/we (wanted|wish)/.test(lower)) {
            anxiety.push('Passive wording can sound like no one owns the decision — add a clear subject where possible.');
        }

        const suggestions = [];
        if (!missing[0].pass) suggestions.push('Add a thank-you line near the start, referencing their time or interest.');
        if (!missing[3].pass) suggestions.push('Include a specific timeline (“by Friday”, “within two business days”).');
        if (!missing[2].pass) suggestions.push('State what happens next (or confirm there are no further steps) in one sentence.');
        if (negH.hits.length) suggestions.push('Soften stock rejection lines — pair the outcome with one sincere sentence about their strengths or the competition of the process.');
        if (vagueH.hits.length && !PHRASES.timeline.some((t) => lower.includes(t))) suggestions.push('Replace vague “touch base soon” with a dated commitment or an honest “we cannot provide individual feedback”.');
        if (warmth < 50) suggestions.push('Swap a few internal phrases (“our process”) for reader-centred ones (“your application”, “your interview”).');
        if (suggestions.length === 0) suggestions.push('Polish by reading aloud — if any sentence feels like a form letter, add one specific detail.');

        const original = text;
        const buf = new Uint8Array(original.length);
        const oLower = original.toLowerCase();
        applySeverityBuffer(oLower, original, PHRASES.positive, 1, buf);
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
