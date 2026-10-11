<?php

namespace App\Actions;

use App\Enums\TicketSourceEnum;
use App\Enums\TicketStatusEnum;
use App\Enums\TicketTypeEnum;
use App\Models\Ticket;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator as LaravelLengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class GetAdminTicketPage
{
    /** @param array{type?: string|null, status?: string|null, source?: string|null} $filters @return array<string, mixed> */
    public function handle(array $filters, ?Ticket $selectedTicket = null): array
    {
        $tickets = $this->paginateTickets($filters)
            ->withQueryString()
            ->through(fn (Ticket $ticket): array => $this->payload($ticket));

        $selectedTicket?->loadMissing([
            'user:id,full_name,email,avatar_url,user_code,lock_reason',
            'reviewer:id,full_name',
        ]);

        return [
            'filters' => [
                'type' => $filters['type'] ?? null,
                'status' => $filters['status'] ?? null,
                'source' => $filters['source'] ?? null,
            ],
            'selectedTicket' => $selectedTicket ? $this->payload($selectedTicket) : null,
            'tickets' => $tickets,
        ];
    }

    /**
     * @param  array{type?: string|null, status?: string|null, source?: string|null}  $filters
     * @return LengthAwarePaginator<int, Ticket>
     */
    private function paginateTickets(array $filters): LengthAwarePaginator
    {
        $perPage = 15;
        $page = Paginator::resolveCurrentPage();
        $offset = ($page - 1) * $perPage;
        $query = $this->ticketQuery($filters);
        $pendingQuery = (clone $query)->where('status', TicketStatusEnum::Pending);
        $reviewedQuery = (clone $query)->where('status', '!=', TicketStatusEnum::Pending);
        $pendingCount = (clone $pendingQuery)->count();
        $reviewedCount = (clone $reviewedQuery)->count();
        $tickets = new Collection;

        if ($offset < $pendingCount) {
            $tickets = $pendingQuery
                ->latest()
                ->offset($offset)
                ->limit($perPage)
                ->get();
        }

        $remaining = $perPage - $tickets->count();

        if ($remaining > 0) {
            $reviewedOffset = max(0, $offset - $pendingCount);
            $tickets = $tickets->concat(
                $reviewedQuery
                    ->latest()
                    ->offset($reviewedOffset)
                    ->limit($remaining)
                    ->get(),
            );
        }

        return new LaravelLengthAwarePaginator(
            $tickets,
            $pendingCount + $reviewedCount,
            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ],
        );
    }

    /**
     * @param  array{type?: string|null, status?: string|null, source?: string|null}  $filters
     * @return Builder<Ticket>
     */
    private function ticketQuery(array $filters): Builder
    {
        return Ticket::query()
            ->with(['user:id,full_name,email,avatar_url,user_code,lock_reason', 'reviewer:id,full_name'])
            ->when($filters['type'] ?? null, fn (Builder $query, string $type): Builder => $query->where('type', $type))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when($filters['source'] ?? null, fn (Builder $query, string $source): Builder => $query->where('source', $source));
    }

    /** @return array<string, mixed> */
    private function payload(Ticket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'type' => $ticket->type,
            'type_label' => TicketTypeEnum::label($ticket->type),
            'source' => $ticket->source,
            'source_label' => TicketSourceEnum::label($ticket->source),
            'message' => $ticket->message,
            'metadata' => $ticket->metadata,
            'status' => $ticket->status,
            'admin_note' => $ticket->admin_note,
            'reviewed_at' => $ticket->reviewed_at?->toISOString(),
            'created_at' => $ticket->created_at?->toISOString(),
            'user' => $ticket->user ? [
                'id' => $ticket->user->id,
                'full_name' => $ticket->user->full_name,
                'email' => $ticket->user->email,
                'user_code' => $ticket->user->user_code,
                'lock_reason' => $ticket->user->lock_reason,
                'avatar_url' => $ticket->user->toArray()['avatar_url'] ?? null,
            ] : null,
            'reviewer' => $ticket->reviewer ? ['full_name' => $ticket->reviewer->full_name] : null,
        ];
    }
}
