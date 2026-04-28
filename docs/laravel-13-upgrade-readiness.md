# Laravel 13 Upgrade Readiness Audit

Date: 2026-04-28  
Branch: `feature/laravel-13-upgrade-readiness`

## Scope

This branch performs a **readiness audit only** for a future Laravel 13 upgrade. No framework/package upgrades were applied in this branch.

## Environment & Baseline

### Runtime checks executed

- `php --version` ✅
  - Result in this container: **PHP 8.5.3-dev**
- `php artisan --version` ❌
  - Blocked because `vendor/autoload.php` is missing (Composer install could not complete in this environment).
- `composer show laravel/framework --locked` ✅
  - Current locked framework: **laravel/framework v12.57.0**
- `composer outdated --direct --locked` ❌
  - Failed due Packagist network/proxy restriction (`CONNECT tunnel failed, response 403`).
- `composer why-not --locked laravel/framework ^13.0` ❌
  - Failed due Packagist network/proxy restriction (`CONNECT tunnel failed, response 403`).
- `composer validate` ✅
  - `composer.json` is valid.
- `php artisan about` ❌
  - Blocked because `vendor/autoload.php` is missing.

### Important note about PHP versions

- Project local target remains **PHP 8.3.30** (per project context).
- Laravel 13 requires **PHP 8.3+** (requirement satisfied by 8.3.30 and by this audit container's 8.5.3-dev runtime).

## Laravel 13 Official Requirements Review

Reference sources:
- Laravel 13 Upgrade Guide (official): <https://laravel.com/docs/13.x/upgrade>
- Laravel 13 Release Notes (official): <https://laravel.com/docs/13.x/releases>

### Key upgrade items from Laravel 13 docs

1. **Dependency updates required** (high impact in official guide):
   - `laravel/framework` -> `^13.0`
   - `laravel/tinker` -> `^3.0`
   - `phpunit/phpunit` -> `^12.0`
   - (`pestphp/pest` only if Pest is used)
2. **Request forgery protection changes**:
   - New / formalized `PreventRequestForgery` middleware behavior (origin-aware checks while keeping token CSRF compatibility).
3. **Cache/session defaults**:
   - Default generated cache and session prefixes changed to hyphenated variants.
   - Impact is typically low if app-level config already defines values.
4. **Symfony polyfill PHP 8.5 conflict risk**:
   - Potential collisions for globally defined helper function names like `array_first` / `array_last`.

## Codebase Inspection Results (Laravel 13-Relevant)

### Config and bootstrap shape

Inspected files:
- `bootstrap/app.php`
- `config/app.php`
- `config/cache.php`
- `config/session.php`
- `config/filesystems.php`
- `routes/web.php`
- `app/Providers/*`
- `app/Http/Middleware/*`
- `app/Console/Commands/*`
- `phpunit.xml`
- Vite config (`vite.config.js`)

Findings:
- App already uses modern Laravel bootstrap style (`Application::configure(...)->withRouting()->withMiddleware()`), which is aligned with recent Laravel skeletons.
- Cache prefix and session cookie are explicitly configured in app config using app-name based values; risk from framework fallback default changes is low.
- Custom middleware stack includes locale and Inertia middleware; no obvious Laravel 13-incompatible registration pattern identified.

### Global helper conflict search (`array_first`, `array_last`)

Searches were run for:
- `function array_first`
- `function array_last`
- helper file patterns (`helpers.php`, `app/helpers.php`, bootstrap helpers)

Result:
- No conflicting global helper function declarations were found in application paths searched.

## Composer Dependency Compatibility Audit

Direct dependencies audited from `composer.json` / lock metadata.

| Package | Current | Laravel 13 compatibility | Required change for upgrade branch | Risk |
|---|---:|---|---|---|
| php | `^8.2` (constraint) | Laravel 13 requires 8.3+ | Update constraint to `^8.3` | Low |
| laravel/framework | `^12.0` / locked `12.57.0` | No (current constraint) | Set to `^13.0` | Medium |
| inertiajs/inertia-laravel | `^2.0` / locked `2.0.24` | Yes (`^10|^11|^12|^13`) | Optional: keep `^2` or move to `^3` later | Low |
| spatie/laravel-permission | `^7.3` / locked `7.3.0` | Yes (`^12|^13`) | No required change | Low |
| laravel/sanctum | `^4.0` / locked `4.3.1` | Yes (`^11|^12|^13` illuminate components) | No required change | Low |
| tightenco/ziggy | `^2.0` / locked `2.6.2` | Yes (`laravel/framework >=9`) | No required change | Low |
| laravel/breeze (dev) | `^2.4` / locked `2.4.1` | Yes (`^11|^12|^13` illuminate components) | No required change | Low |
| laravel/pail (dev) | `^1.2.2` / locked `1.2.6` | Yes (`^10.24|^11|^12|^13` illuminate components) | No required change | Low |
| laravel/pint (dev) | `^1.13` / locked `1.29.1` | Framework-independent tooling | No required change | Low |
| laravel/sail (dev) | `^1.41` / locked `1.57.0` | Yes (`^9|^10|^11|^12|^13` illuminate components) | No required change | Low |
| nunomaduro/collision (dev) | `^8.6` / locked `8.9.4` | Compatible line exists for Laravel 13 | Keep current major; update if composer requests | Low |
| phpunit/phpunit (dev) | `^11.5.3` / locked `11.5.55` | Laravel 13 guide requires `^12.0` | Bump to `^12.0` | Medium |
| laravel/tinker | `^2.10.1` / locked `2.11.1` | Current line stops at Laravel 12 | Bump to `^3.0` | **High** |

### Primary blockers identified

- `laravel/framework` still constrained to `^12.0`.
- `laravel/tinker` must move from 2.x to 3.x for Laravel 13.
- `phpunit/phpunit` should be moved from 11.x to 12.x per official guide.

## Frontend Build Compatibility Audit

### Files reviewed

- `package.json`
- `package-lock.json`
- `vite.config.js`

### Build run

- `npm run build` ✅ passed.
- One npm warning observed:
  - `npm warn Unknown env config "http-proxy"` (environment-level warning; build still successful).

### Frontend readiness notes

- Current stack (`vite` 6, React 18, TypeScript 5, `@inertiajs/react` 2) builds successfully with current Laravel 12 backend artifacts.
- No Laravel 13-specific frontend package changes are required for readiness documentation at this time.

## Test Baseline

Command attempted:
- `php artisan test` ❌

Result:
- Could not run because `vendor/autoload.php` is missing in this environment (Composer install unable to complete due restricted GitHub/Packagist network path).

Risk implication:
- Test baseline in this environment is incomplete. Upgrade execution should only proceed in an environment where Composer install succeeds and full tests can run.

## Laravel AI SDK Evaluation (No Implementation in This Branch)

Current recommendation:
1. Upgrade framework first (Laravel 12 -> 13) with existing custom AI integration unchanged.
2. Keep existing `ai_providers` and `ai_request_logs` model/tables and workflows intact during framework upgrade.
3. After stable Laravel 13 rollout, evaluate first-party Laravel AI SDK in a dedicated branch:
   - `feature/laravel-ai-sdk-evaluation`

Assessment:
- A **gradual migration** is preferred: adapter/wrapper approach first, then selective endpoint migration.
- Existing request logs remain valuable regardless of SDK adoption.

## Proposed Upgrade Plan (Execution Branch)

Use a dedicated execution branch: `feature/upgrade-laravel-13`

### Step 1
- Ensure clean working tree.
- Ensure dependencies install cleanly.
- Ensure full test suite passes on Laravel 12 before dependency upgrades.

### Step 2
Update `composer.json` constraints:
- `php` -> `^8.3`
- `laravel/framework` -> `^13.0`
- `laravel/tinker` -> `^3.0`
- `phpunit/phpunit` -> `^12.0`
- Any additional package adjustments identified by `composer why-not laravel/framework ^13.0` in a network-enabled environment.

### Step 3
- Run `composer update`.

### Step 4
Review and apply framework-level changes:
- Cache prefix / session cookie default behavior changes (if app relies on fallback defaults).
- Request forgery protection (`PreventRequestForgery`) and origin checks.
- Skeleton/config deltas between `laravel/laravel` 12.x and 13.x (only meaningful diffs).
- Verify providers and middleware ordering remains correct.

### Step 5
Run validation commands:
- `php artisan optimize:clear`
- `php artisan migrate`
- `php artisan test`
- `npm run build`

### Step 6
Manual QA checklist:
- Login flow
- Admin dashboard
- Editor dashboard/workbench
- Prompt run generation
- AI provider response generation (use fake/test provider where possible)
- Source verification flows
- World map
- Theme switcher
- Public home page
- Translation rendering (en/es/fr)

### Step 7
- Merge only after all automated + manual checks pass.

## Final Readiness Verdict

**Readiness status: Conditionally ready**

- Technically feasible based on dependency landscape and Laravel 13 requirements.
- Blocked in this environment by Composer network restrictions, which prevented full PHP command/test baselines.
- No major code-level incompatibilities were detected during static/config audit.
- Proceed with actual upgrade in a dedicated, fully network-enabled branch/environment.
