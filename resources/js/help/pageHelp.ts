import { PageHelpData } from '@/Components/Help/PageHelp';

export function getPageHelp(t: (key: string) => string, key?: string): PageHelpData | null {
    if (!key) return null;

    const data: Record<string, PageHelpData> = {
        'admin.dashboard': {
            title: t('Admin Dashboard help'),
            summary: t('This page helps you understand system health and AI usage before managing configuration.'),
            sections: [
                { title: t('What is this page for?'), content: t('Use this dashboard for technical overview, editorial status and AI usage indicators.') },
                { title: t('What can I do here?'), content: t('Review risks, failures and provider readiness before changing users, providers, SEO or home settings.') },
            ],
            nextSteps: [t('Open AI Providers to review limits and keys.'), t('Open AI Request Logs to inspect errors and costs.')],
        },
        'admin.aiproviders.index': {
            title: t('AI Providers help'),
            summary: t('Configure providers, models, limits and estimated costs safely.'),
            sections: [{ title: t('What can I do here?'), content: t('Activate providers, set defaults, configure token prices and define request or cost limits.') }],
            tips: [t('API keys are stored in environment variables.'), t('Costs are estimates.')],
        },
        'admin.airequestlogs.index': {
            title: t('AI Request Logs help'),
            summary: t('Monitor AI requests, failures, blocked events, tokens, duration and estimated costs.'),
            sections: [{ title: t('What can I do here?'), content: t('Use filters and summary cards to audit provider behavior and investigate issues.') }],
            tips: [t('Blocked requests do not call the AI provider.'), t('Estimated cost may be unavailable when token usage is not returned.')],
        },
        'editor.bulletinpromptruns.show': {
            title: t('Prompt Run help'),
            summary: t('Follow the full run lifecycle from prompt generation to script creation and source verification.'),
            sections: [{ title: t('What can I do here?'), content: t('Generate AI responses, paste manual responses, parse output and continue to scripts.') }],
            nextSteps: [t('Run source verification before approving scripts.'), t('Complete production metadata when the script is ready.')],
        },
        'viewer.dashboard': {
            title: t('Viewer Dashboard help'),
            summary: t('Read-only overview of completed content and coverage status.'),
            sections: [{ title: t('What can I do here?'), content: t('Review published content summaries and open map or content pages without editing data.') }],
        },
        'viewer.published-content': {
            title: t('Published Content help'),
            summary: t('Read-only list of completed or published editorial output.'),
            sections: [{ title: t('What can I do here?'), content: t('Inspect available content and navigate to related records when available.') }],
        },
        'viewer.world-map.index': {
            title: t('Viewer World Map help'),
            summary: t('Explore coverage and available content by geographic location.'),
            sections: [{ title: t('What can I do here?'), content: t('Use markers to understand where current noticiario content is available.') }],
        },
    };

    return data[key] ?? {
        title: t('Page help'),
        summary: t('This page helps you understand goals, actions and the recommended next step.'),
        sections: [
            { title: t('What is this page for?'), content: t('This page helps you manage this module safely and consistently.') },
            { title: t('What can I do here?'), content: t('Review records, run key actions and continue the workflow from this panel.') },
        ],
        nextSteps: [t('Recommended next action')],
    };
}
