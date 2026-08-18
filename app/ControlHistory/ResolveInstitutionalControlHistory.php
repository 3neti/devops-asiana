<?php

namespace App\ControlHistory;

use App\ControlClosures\ResolvedControlReviewClosureEligibility;
use App\ControlDecisions\ResolvedControlReviewClosureDecisions;
use App\ControlReconciliation\ResolvedControlReviewClosureReconciliations;
use DateTimeImmutable;
use Throwable;

final class ResolveInstitutionalControlHistory
{
    public function handle(
        InstitutionalControlHistoryDefinition $definition,
        ResolvedControlReviewClosureEligibility $eligibility,
        ResolvedControlReviewClosureDecisions $decisions,
        ResolvedControlReviewClosureReconciliations $reconciliations,
    ): ResolvedInstitutionalControlHistory {
        /** @var list<array{code: string, message: string}> $conflicts */
        $conflicts = [];
        /** @var list<array{code: string, message: string}> $historyGaps */
        $historyGaps = [];
        /** @var list<array<string, mixed>> $events */
        $events = [];
        $eventKinds = array_fill_keys($definition->eventKinds, true);

        foreach ($eligibility->eligibilityReviews as $review) {
            $this->appendEvent(
                events: $events,
                eventKinds: $eventKinds,
                event: [
                    'event_key' => 'eligibility:'.($review['key'] ?? 'unknown'),
                    'event_kind' => 'eligibility_review',
                    'source_reference' => $review['key'] ?? null,
                    'occurred_at' => $review['reviewed_at'] ?? null,
                    'actor' => $review['reviewed_by'] ?? null,
                    'state' => ($review['closure_eligible'] ?? false) === true ? 'eligible' : 'not_eligible',
                ],
                historyGaps: $historyGaps,
            );
        }
        foreach ($decisions->resolvedDecisions as $decision) {
            $this->appendEvent(
                events: $events,
                eventKinds: $eventKinds,
                event: [
                    'event_key' => 'decision:'.($decision['key'] ?? 'unknown'),
                    'event_kind' => 'closure_decision',
                    'source_reference' => $decision['key'] ?? null,
                    'occurred_at' => $decision['decided_at'] ?? null,
                    'actor' => $decision['decided_by'] ?? null,
                    'state' => $decision['decision'] ?? null,
                ],
                historyGaps: $historyGaps,
            );
        }
        foreach ($reconciliations->resolvedReconciliations as $reconciliation) {
            $this->appendEvent(
                events: $events,
                eventKinds: $eventKinds,
                event: [
                    'event_key' => 'reconciliation:'.($reconciliation['key'] ?? 'unknown'),
                    'event_kind' => 'closure_reconciliation',
                    'source_reference' => $reconciliation['key'] ?? null,
                    'occurred_at' => $reconciliation['reconciled_at'] ?? null,
                    'actor' => $reconciliation['reconciled_by'] ?? null,
                    'state' => ($reconciliation['reconciled'] ?? false) === true ? 'reconciled' : 'discrepancy',
                ],
                historyGaps: $historyGaps,
            );
        }

        usort($events, function (array $left, array $right): int {
            return strcmp((string) ($left['occurred_at'] ?? ''), (string) ($right['occurred_at'] ?? ''));
        });

        return new ResolvedInstitutionalControlHistory(
            schemaVersion: $definition->schemaVersion,
            historyKey: $definition->historyKey,
            source: $definition->source,
            payloadsExcluded: ! $definition->includePayloads,
            eventKinds: $definition->eventKinds,
            events: $events,
            conflicts: $conflicts,
            historyGaps: $historyGaps,
        );
    }

    /**
     * @param  list<array<string, mixed>>  $events
     * @param  array<string, true>  $eventKinds
     * @param  array<string, mixed>  $event
     * @param  list<array{code: string, message: string}>  $historyGaps
     *
     * @param-out list<array<string, mixed>> $events
     * @param-out list<array{code: string, message: string}> $historyGaps
     */
    private function appendEvent(array &$events, array $eventKinds, array $event, array &$historyGaps): void
    {
        $kind = (string) ($event['event_kind'] ?? '');
        if (! isset($eventKinds[$kind])) {
            $historyGaps[] = $this->issue('unsupported_history_event_kind', "History event kind {$kind} is not configured.");
        }
        if (! $this->validDate($event['occurred_at'] ?? null)) {
            $historyGaps[] = $this->issue('history_event_time_missing', "History event {$event['event_key']} lacks a valid timestamp.");
        }
        if (empty($event['actor'])) {
            $historyGaps[] = $this->issue('history_event_actor_missing', "History event {$event['event_key']} lacks an actor.");
        }
        $events[] = $event;
    }

    private function validDate(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }
        try {
            new DateTimeImmutable($value);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /** @return array{code: string, message: string} */
    private function issue(string $code, string $message): array
    {
        return compact('code', 'message');
    }
}
