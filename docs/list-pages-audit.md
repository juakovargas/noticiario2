# List/Table Pages Audit

## Admin panel

| Route | Controller | React page | Model/table | Type | Strategy | Filters/actions added | ID downplayed | Tests |
|---|---|---|---|---|---|---|---|---|
| admin.users.index | Admin\\UserController@index | Admin/Users/Index.tsx | users | system | is_active (no archive) | search/role/active/locale reviewed (existing backlog) | yes | AdminUserManagementTest existing |
| admin.roles.index | Admin\\RoleController@index | Admin/Roles/Index.tsx | roles | system/config | no archive | search backlog tracked | yes | existing |
| admin.permissions.index | Admin\\PermissionController@index | Admin/Permissions/Index.tsx | permissions | system/config | no archive | search backlog tracked | yes | existing |
| admin.languages.index | Admin\\LanguageController@index | Admin/Languages/Index.tsx | languages | configuration | is_active | active/inactive reviewed | yes | AdminLanguageManagementTest |
| admin.media-files.index | Admin\\MediaFileController@index | Admin/MediaFiles/Index.tsx | media_files | system/operational | status archived | status/media type/collection/uploader | yes | AdminMediaFileManagementTest |
| admin.prompt-profiles.index | Admin\\PromptProfileController@index | Admin/PromptProfiles/Index.tsx | prompt_profiles | configuration | is_active | active/default filters reviewed | yes | PromptProfileManagementTest |
| admin.ai-providers.index | Admin\\AiProviderController@index | Admin/AiProviders/Index.tsx | ai_providers | configuration | is_active | active/default/type filters reviewed | yes | AiProviderManagementTest |

## Editor panel

| Route | Controller | React page | Model/table | Type | Strategy | Filters/actions added | ID downplayed | Tests |
|---|---|---|---|---|---|---|---|---|
| editor.dashboard | Editor\\DashboardController@index | Editor/Dashboard.tsx | mixed counters | operational aggregate | exclude archived | dashboard counts exclude archived | n/a | BulletinPromptRunListManagementTest |
| editor.bulletin-types.index | Editor\\BulletinTypeController@index | Editor/BulletinTypes/Index.tsx | bulletin_types | configuration | is_active | active/inactive pattern tracked | yes | existing |
| editor.bulletin-prompt-runs.index | Editor\\BulletinPromptRunController@index | Editor/BulletinPromptRuns/Index.tsx | bulletin_prompt_runs | operational | status=archived | full filters + archive/restore/complete/cancel | yes | BulletinPromptRunListManagementTest |
| editor.scripts.index | Editor\\ScriptController@index | Editor/Scripts/Index.tsx | scripts | operational | status=archived | filters + archive/restore | yes | EditorListingFiltersTest |
| editor.editions.index | Editor\\EditionController@index | Editor/Editions/Index.tsx | editions | operational | status=archived | filters + archive/restore | yes | EditorListingFiltersTest |
| editor.news-items.index | Editor\\NewsItemController@index | Editor/NewsItems/Index.tsx | news_items | operational | status=archived | filters + archive/restore | yes | EditorListingFiltersTest |
| editor.news-sources.index | Editor\\NewsSourceController@index | Editor/NewsSources/Index.tsx | news_sources | configuration | is_active | active filters reviewed | yes | existing |
| editor.news-categories.index | Editor\\NewsCategoryController@index | Editor/NewsCategories/Index.tsx | news_categories | configuration | is_active | active filters reviewed | yes | existing |
| editor.locations.index | Editor\\LocationController@index | Editor/Locations/Index.tsx | locations | configuration | is_active | active filters reviewed | yes | existing |
| editor.editorial-templates.index | Editor\\EditorialTemplateController@index | Editor/EditorialTemplates/Index.tsx | editorial_templates | configuration | is_active | active filters reviewed | yes | EditorialTemplateManagementTest |
| editor.ai-prompt-templates.index | Editor\\AiPromptTemplateController@index | Editor/AiPromptTemplates/Index.tsx | ai_prompt_templates | configuration | is_active | active filters reviewed | yes | AiPromptTemplateManagementTest |
| editor.editorial-schedules.index | Editor\\EditorialScheduleController@index | Editor/EditorialSchedules/Index.tsx | editorial_schedules | configuration/operational | is_active | schedule filters reviewed | yes | EditorialScheduleWorkflowTest |
| editor.editorial-schedule-runs.index | Editor\\EditorialScheduleRunController@index | Editor/EditorialScheduleRuns/Index.tsx | editorial_schedule_runs | operational | status=archived | added search/status/schedule/date + show/only archived + archive/restore | yes | added test |
| editor.editorial-requests.index | Editor\\EditorialRequestController@index | Editor/EditorialRequests/Index.tsx | editorial_requests | operational | status=archived | added search/status/location/category/language + show/only archived + archive/restore | yes | added test |
| editor.source-references.index | Editor\\SourceReferenceController@index | Editor/SourceReferences/Index.tsx | source_references | operational | archived_at/archived_by | added show/only archived + archive/restore + existing verification filters | yes | added test |

## Viewer panel

| Route | Controller | React page | Model/table | Type | Strategy | Filters/actions added | ID downplayed | Tests |
|---|---|---|---|---|---|---|---|---|
| viewer.dashboard | Viewer\\DashboardController@index | Viewer/Dashboard.tsx | mixed | system | no archive action | reviewed list/cards | n/a | existing |
| viewer.published-content | Viewer route closure | Viewer/PublishedContent.tsx | content list placeholder | system | no archive needed | n/a | n/a | n/a |
