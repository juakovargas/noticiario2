const statusTranslationMap: Record<string, string> = {
    draft: 'Draft',
    prompt_ready: 'Prompt ready',
    waiting_ai_response: 'Waiting for AI response',
    response_received: 'Response received',
    script_created: 'Script created',
    completed: 'Completed',
    cancelled: 'Cancelled',
    failed: 'Failed',
    archived: 'Archived',
    approved: 'Approved',
    rejected: 'Rejected',
    pending: 'Pending',
    in_review: 'In review',
    verified: 'Verified',
    weak: 'Weak',
    missing: 'Missing',
    broken: 'Broken',
    not_required: 'Not required',
    active: 'Active',
    inactive: 'Inactive',
    collected: 'Collected',
    selected: 'Selected',
};

export function statusTranslationKey(status: string | null | undefined): string {
    if (!status) {
        return '-';
    }

    return statusTranslationMap[status] ?? status.replaceAll('_', ' ');
}
