<?php

namespace App\Filters\Pos\Chat;

use App\Filters\BaseFilter;

class ChatConversationFilter extends BaseFilter
{
    public function search($search = null): void
    {
        $this->builder->when($search, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('external_id', 'like', "%{$search}%")
                    ->orWhere('profile_name', 'like', "%{$search}%")
                    ->orWhereHas('contact', function ($contactQuery) use ($search) {
                        $contactQuery->where('name', 'like', "%{$search}%");
                    });
            });
        });
    }

    public function channel($channel = null): void
    {
        if (empty($channel)) {
            return;
        }

        $this->builder->where('channel', $channel);
    }

    public function status($status = null): void
    {
        if (empty($status)) {
            return;
        }

        $this->builder->where('status', $status);
    }

    public function unassigned($value = null): void
    {
        if (!filter_var($value, FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        $this->builder->whereNull('assigned_agent_id');
    }

    public function assignedToMe($value = null): void
    {
        if (!filter_var($value, FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        $this->builder->where('assigned_agent_id', auth()->id());
    }
}
