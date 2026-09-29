# Changelog

All notable changes to MedMetric CMMS are documented here.
Format based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [1.0.3] - 2026-09-29

### Fixed
- Fatal `Attempted to call an undefined method named "getMenuContent"` on every
  page load: GLPI calls `$type::getMenuContent()` on every class listed in the
  `MENU_TOADD` hook (`src/Html.php`), but `Analytics` is a plain helper class
  that does not extend `CommonDBTM` and therefore does not inherit
  `CommonGLPI::getMenuContent()`. `Analytics` now implements it, so the Tools >
  Analytics entry is rendered and the menu no longer fatals.

### Notes
- Upgrading from 1.0.2 runs `sql/upgrade/1.0.2_to_1.0.3.sql`, which adds the
  `calibration_reminder_days` column to maintenance plans. It is additive with a
  default and unused by the current UI. Fresh installs are unaffected.

## [1.0.2] - 2026-09-29

### Fixed
- Fatal `Class "Analytics" not found` when rendering the central dashboard:
  the `DISPLAY_CENTRAL` widget called the unqualified `Analytics` from the
  global namespace, where no such class exists. It is now fully qualified.

### Notes
- Upgrading from 1.0.1 runs `sql/upgrade/1.0.1_to_1.0.2.sql`, which adds the
  `sla_hours` / `sla_breached` columns to work orders. Both are additive with
  defaults and unused by the current UI. Fresh installs are unaffected.

## [1.0.1] - 2026-09-29

### Fixed
- "Plugin MedMetric CMMS has no install function!" on install: plugin classes
  are now loaded from `plugin_init_medmetriccmms()` (GLPI's plugin autoloader
  only maps `src/`, not the classic `inc/` layout) and defensively at the top
  of `hook.php`, so lifecycle functions are always defined.
- `plugin_init` no longer performs database side effects (cron registration
  moved to the install function), so a transient DB issue can never abort
  plugin loading and leave `hook.php` unloaded.
- `Migration::updateDisplayPrefs()` was called statically in
  install/uninstall; it is an instance method and raised a fatal error.
- Unknown `infocom` attribute warning on the plugins list: Inventory is now
  registered with the correct `infocom_types` key.
- Prerequisites check now only verifies the PHP version; it can no longer
  block installation.
- Install failed with `Duplicate entry for key 'unicity'` in
  `glpi_profilerights`: `ProfileRight::addProfileRights()` expects a plain list
  of right names but was given a `name => rights` map, so the numeric
  `ALLSTANDARDRIGHT` value was inserted as a right name. Rights are now
  created idempotently (one row per profile) and full rights are granted to the
  Super-Admin profile, so reinstalling or upgrading no longer throws.

## [1.0.0] - 2026-09-28

### Added
- Initial release.
- Equipment registry with types, models, departments, structures, criticality,
  warranty and maintenance bookkeeping.
- Work orders with lifecycle, priorities, internal/vendor technicians, downtime,
  costs and spare-part consumption.
- Preventive maintenance plans with daily cron generation of occurrences.
- Maintenance intervention records with kinds and checklists.
- Notifications: fault reports plus cron alerts for maintenance due and warranty expiry.
- Solutions knowledge base with automatic capture from closed work orders.
- Spare parts inventory with min/ordered stock thresholds.
- Vendors and contracts with expiry tracking.
- Analytics dashboard: open/overdue work orders, broken equipment, MTTR, downtime,
  low stock, expiring contracts, status/kind breakdowns, most-failing equipment.
- Reports with CSV export.
- AI assistance (diagnosis, plan suggestions, summaries) via OpenAI-compatible APIs
  (OpenAI, Groq, Azure OpenAI, Ollama) with per-purpose model registry.
- Audit/AI request logging.
- Translations: en_GB, fr_FR, am_ET + POT template.

## [Unreleased]

### Planned
- Calendar view for planned maintenance.
- QR-code asset labels.
- Amharic .mo compiled catalogs in the package.
