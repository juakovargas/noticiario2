<?php

namespace App\Http\Controllers;

use App\Models\InternalMessage;
use App\Services\Messages\MessageRelatedLinkResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InternalMessageController extends Controller
{
    public function __construct(private readonly MessageRelatedLinkResolver $relatedLinkResolver)
    {
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        $box = $request->string('box')->toString() ?: 'inbox';

        $query = InternalMessage::query()->with(['sender:id,name,email', 'recipient:id,name,email']);
        if ($box === 'sent') {
            $query->where('sender_id', $user->id);
        } elseif ($box === 'archived') {
            $query->where('recipient_id', $user->id)->archived();
        } else {
            $box = 'inbox';
            $query->inboxFor($user);
        }

        if ($request->query('status') === 'unread') {
            $query->unread();
        } elseif ($request->query('status') === 'read') {
            $query->read();
        }

        if ($type = $request->query('message_type')) {
            $query->where('message_type', $type);
        }

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(fn ($q) => $q->where('subject', 'like', "%{$search}%")->orWhere('body', 'like', "%{$search}%"));
        }

        $messages = $query->latest()->paginate(20)->withQueryString();
        $messages->getCollection()->transform(function (InternalMessage $message) use ($user) {
            $message->setAttribute('related_link', $this->relatedLinkResolver->resolveForUser($message, $user));
            return $message;
        });

        return Inertia::render('Messages/Index', [
            'messages' => $messages,
            'filters' => ['box' => $box, 'status' => $request->query('status', 'all'), 'message_type' => $request->query('message_type'), 'search' => $request->query('search')],
            'counts' => [
                'unread_inbox' => InternalMessage::query()->inboxFor($user)->unread()->count(),
                'sent' => InternalMessage::query()->where('sender_id', $user->id)->count(),
                'archived' => InternalMessage::query()->where('recipient_id', $user->id)->archived()->count(),
            ],
        ]);
    }

    public function show(Request $request, InternalMessage $internalMessage): Response
    {
        $user = $request->user();
        abort_unless($internalMessage->recipient_id === $user->id || $internalMessage->sender_id === $user->id, 403);

        if ($internalMessage->recipient_id === $user->id && ! $internalMessage->read_at) {
            $internalMessage->update(['read_at' => now()]);
        }

        $internalMessage->load('sender:id,name,email', 'recipient:id,name,email');

        return Inertia::render('Messages/Show', [
            'message' => $internalMessage,
            'relatedLink' => $this->relatedLinkResolver->resolveForUser($internalMessage, $user),
            'recipientReason' => $internalMessage->metadata['recipient_reason'] ?? null,
        ]);
    }

    public function markRead(Request $request, InternalMessage $internalMessage): RedirectResponse
    {
        abort_unless($internalMessage->recipient_id === $request->user()->id, 403);
        if (! $internalMessage->read_at) {
            $internalMessage->update(['read_at' => now()]);
        }
        return back()->with('success', 'messages.marked_read');
    }

    public function archive(Request $request, InternalMessage $internalMessage): RedirectResponse
    {
        abort_unless($internalMessage->recipient_id === $request->user()->id, 403);
        $internalMessage->update(['archived_at' => now()]);
        return back()->with('success', 'messages.archived');
    }
}
