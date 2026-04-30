export type PageHelpDefinition = {
    titleKey: string;
    summaryKey: string;
    sections: Array<{ titleKey: string; contentKey: string }>;
    tipsKeys?: string[];
    nextStepsKeys?: string[];
};

const FALLBACK: PageHelpDefinition = {
    titleKey: 'help.common.pageHelp',
    summaryKey: 'help.fallback.summary',
    sections: [{ titleKey: 'help.common.whatIsThisPageFor', contentKey: 'help.fallback.notConfigured' }],
};

export const PAGE_HELP: Record<string, PageHelpDefinition> = {
    'editor.dashboard': {
        titleKey: 'help.editor.dashboard.title',
        summaryKey: 'help.editor.dashboard.summary',
        sections: [
            { titleKey: 'help.common.whatIsThisPageFor', contentKey: 'help.editor.dashboard.what' },
            { titleKey: 'help.common.whatCanIDoHere', contentKey: 'help.editor.dashboard.actions' },
        ],
        nextStepsKeys: ['help.editor.dashboard.next'],
    },
    'editor.workbench.index': {
        titleKey: 'help.editor.workbench.title',
        summaryKey: 'help.editor.workbench.summary',
        sections: [
            { titleKey: 'help.common.whatIsThisPageFor', contentKey: 'help.editor.workbench.what' },
            { titleKey: 'help.common.whatCanIDoHere', contentKey: 'help.editor.workbench.actions' },
            { titleKey: 'help.common.nextSteps', contentKey: 'help.editor.workbench.next' },
        ],
    },
    'editor.bulletinpromptruns.index': {
        titleKey: 'help.editor.promptRuns.title',
        summaryKey: 'help.editor.promptRuns.summary',
        sections: [
            { titleKey: 'help.common.whatIsThisPageFor', contentKey: 'help.editor.promptRuns.what' },
            { titleKey: 'help.common.whatCanIDoHere', contentKey: 'help.editor.promptRuns.actions' },
        ],
        nextStepsKeys: ['help.editor.promptRuns.next'],
    },
    'editor.bulletinpromptruns.show': {
        titleKey: 'help.editor.promptRunShow.title',
        summaryKey: 'help.editor.promptRunShow.summary',
        sections: [
            { titleKey: 'help.common.whatIsThisPageFor', contentKey: 'help.editor.promptRunShow.what' },
            { titleKey: 'help.common.whatCanIDoHere', contentKey: 'help.editor.promptRunShow.actions' },
            { titleKey: 'help.common.nextSteps', contentKey: 'help.editor.promptRunShow.next' },
        ],
    },
    'editor.worldmap.index': {
        titleKey: 'help.editor.worldMap.title',
        summaryKey: 'help.editor.worldMap.summary',
        sections: [
            { titleKey: 'help.common.whatIsThisPageFor', contentKey: 'help.editor.worldMap.what' },
            { titleKey: 'help.common.whatCanIDoHere', contentKey: 'help.editor.worldMap.actions' },
        ],
    },
    'editor.bulletintypes.index': {
        titleKey: 'help.editor.bulletinTypes.title',
        summaryKey: 'help.editor.bulletinTypes.summary',
        sections: [
            { titleKey: 'help.common.whatIsThisPageFor', contentKey: 'help.editor.bulletinTypes.what' },
            { titleKey: 'help.common.whatCanIDoHere', contentKey: 'help.editor.bulletinTypes.actions' },
        ],
    },
    'editor.editorialschedules.index': {
        titleKey: 'help.editor.schedules.title',
        summaryKey: 'help.editor.schedules.summary',
        sections: [
            { titleKey: 'help.common.whatIsThisPageFor', contentKey: 'help.editor.schedules.what' },
            { titleKey: 'help.common.whatCanIDoHere', contentKey: 'help.editor.schedules.actions' },
            { titleKey: 'help.common.nextSteps', contentKey: 'help.editor.schedules.next' },
        ],
    },
    'editor.automation.index': {
        titleKey: 'help.editor.automation.title',
        summaryKey: 'help.editor.automation.summary',
        sections: [
            { titleKey: 'help.common.whatIsThisPageFor', contentKey: 'help.editor.automation.what' },
            { titleKey: 'help.common.whatCanIDoHere', contentKey: 'help.editor.automation.actions' },
            { titleKey: 'help.common.nextSteps', contentKey: 'help.editor.automation.next' },
        ],
        tipsKeys: ['help.editor.automation.warningAi', 'help.editor.automation.warningInactive', 'help.editor.automation.warningFailures'],
    },
    'editor.editorialscheduleruns.index': {
        titleKey: 'help.editor.scheduleRuns.title',
        summaryKey: 'help.editor.scheduleRuns.summary',
        sections: [
            { titleKey: 'help.common.whatIsThisPageFor', contentKey: 'help.editor.scheduleRuns.what' },
            { titleKey: 'help.common.whatCanIDoHere', contentKey: 'help.editor.scheduleRuns.actions' },
        ],
    },
    'admin.dashboard': {
        titleKey: 'help.admin.dashboard.title',
        summaryKey: 'help.admin.dashboard.summary',
        sections: [
            { titleKey: 'help.common.whatIsThisPageFor', contentKey: 'help.admin.dashboard.what' },
            { titleKey: 'help.common.whatCanIDoHere', contentKey: 'help.admin.dashboard.actions' },
        ],
    },
    'viewer.dashboard': {
        titleKey: 'help.viewer.dashboard.title',
        summaryKey: 'help.viewer.dashboard.summary',
        sections: [{ titleKey: 'help.common.whatIsThisPageFor', contentKey: 'help.viewer.dashboard.what' }],
    },
    'viewer.published-content': FALLBACK,
    'viewer.world-map.index': FALLBACK,

    'admin.aiproviders.index': FALLBACK,
    'admin.aiproviders.show': FALLBACK,
    'admin.aiproviders.form': FALLBACK,
    'admin.airequestlogs.index': FALLBACK,
    'admin.airequestlogs.show': FALLBACK,
    'editor.aiprompttemplates.index': FALLBACK,
    'editor.scripts.index': { titleKey: 'help.editor.scripts.title', summaryKey: 'help.editor.scripts.summary', sections: [{ titleKey: 'help.common.whatIsThisPageFor', contentKey: 'help.editor.scripts.what' }, { titleKey: 'help.common.whatCanIDoHere', contentKey: 'help.editor.scripts.actions' }, { titleKey: 'help.common.nextSteps', contentKey: 'help.editor.scripts.next' }], tipsKeys: ['help.editor.scripts.warning1','help.editor.scripts.warning2'] },
    'editor.scripts.productionedit': FALLBACK,
    'editor.scripts.review': FALLBACK,
    'editor.scripts.show': FALLBACK,
    'editor.sourcereferences.index': { titleKey: 'help.editor.sources.title', summaryKey: 'help.editor.sources.summary', sections: [{ titleKey: 'help.common.whatIsThisPageFor', contentKey: 'help.editor.sources.what' }, { titleKey: 'help.common.whatCanIDoHere', contentKey: 'help.editor.sources.actions' }], nextStepsKeys: ['help.editor.sources.next'] },
    'editor.newssources.index': FALLBACK,
    'editor.newscategories.index': FALLBACK,
    'editor.locations.index': FALLBACK,
    'editor.editorialtemplates.index': FALLBACK,
};
