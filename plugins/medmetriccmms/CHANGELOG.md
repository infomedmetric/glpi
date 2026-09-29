# Changelog

All notable changes to MedMetric CMMS are documented here.
Format based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

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
