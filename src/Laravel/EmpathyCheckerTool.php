<?php

declare(strict_types=1);

namespace URLCV\EmpathyChecker\Laravel;

use App\Tools\Contracts\ToolInterface;

/**
 * Empathy Checker — frontend-only rules-based analysis for hiring and workplace messages.
 */
class EmpathyCheckerTool implements ToolInterface
{
    public function slug(): string
    {
        return 'empathy-checker';
    }

    public function name(): string
    {
        return 'Empathy Checker';
    }

    public function summary(): string
    {
        return 'Paste rejection emails, invites, or follow-ups and get a fast empathy score, tone read, and practical fixes — rules-based, no AI.';
    }

    public function descriptionMd(): ?string
    {
        return <<<'MD'
## Empathy Checker

Check whether an email or note feels **empathetic, clear, respectful, and human** before you send it.

This tool is built for **recruiters, hiring managers, interviewers, HR teams, and job seekers** who want to avoid cold, vague, robotic, or anxiety-inducing wording — especially in rejection, follow-up, interview, and offer-stage messages.

### What you get

- **Overall empathy score** (0–100) with a **tone label** (cold → professional → warm, plus casual detection)
- **Five dimensions**: acknowledgement, clarity, respect, warmth, and candidate focus — each with a short explanation
- **Pattern highlights** — common helpful phrases vs. phrases that often land badly
- **Missing empathy checks** — e.g. no thank-you, no timeline, no next steps
- **Anxiety triggers** — vague timelines, passive wording, abrupt endings
- **Practical suggestions** — deterministic tips and phrase-level ideas (not generated text)

### Privacy

**All analysis runs in your browser.** No message text is sent to a server. The scoring engine uses **rules and phrase lists**, not large language models or external APIs.

### Limitations

This is a **heuristic** helper — it cannot understand full context (relationship, legal constraints, or company policy). Use it as a second pair of eyes, not as legal or HR advice.
MD;
    }

    public function categories(): array
    {
        return ['recruiting', 'text'];
    }

    public function tags(): array
    {
        return ['empathy', 'email', 'communication', 'feedback', 'hiring', 'writing'];
    }

    public function inputSchema(): array
    {
        return [
            'text' => [
                'type' => 'text',
                'label' => 'Message',
                'placeholder' => 'Paste your email or message here…',
                'rows' => 12,
                'required' => true,
                'max_length' => 50_000,
                'help' => 'Maximum 50,000 characters. Processed locally in your browser.',
            ],
        ];
    }

    public function run(array $input): array
    {
        return [];
    }

    public function mode(): string
    {
        return 'frontend';
    }

    public function isAsync(): bool
    {
        return false;
    }

    public function isPublic(): bool
    {
        return true;
    }

    public function frontendView(): ?string
    {
        return 'empathy-checker::empathy-checker';
    }

    public function rateLimitPerMinute(): int
    {
        return 30;
    }

    public function cacheTtlSeconds(): int
    {
        return 0;
    }

    public function sortWeight(): int
    {
        return 92;
    }
}
