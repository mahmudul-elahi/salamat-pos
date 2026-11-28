<?php

namespace App\Services\AiAssistant\Tools;

use Laravel\Ai\Approvals\Approval;
use Laravel\Ai\Concerns\InteractsWithApprovals;
use Laravel\Ai\Contracts\Approvable;
use Laravel\Ai\Tools\Request;

/**
 * A tool that changes data, so the user must approve each call before it runs.
 *
 * A call the user is not permitted to make, or whose arguments are invalid, skips the approval
 * step and is answered straight away with the reason, so the user is never asked to approve
 * an action that cannot succeed.
 */
abstract class ApprovableAssistantTool extends AssistantTool implements Approvable
{
    use InteractsWithApprovals;

    /**
     * A one-line, human-readable summary of what approving this call will do.
     *
     * @param  array<string, mixed>  $arguments
     */
    abstract protected function approvalSummary(array $arguments): string;

    protected function needsApproval(Request $request): Approval|bool
    {
        if (! $this->isPermitted()) {
            return false;
        }

        $validator = $this->validator($request);

        if ($validator->fails()) {
            return false;
        }

        return Approval::required($this->approvalSummary($validator->validated()));
    }
}
