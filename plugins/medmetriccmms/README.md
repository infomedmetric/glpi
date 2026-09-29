# MedMetric CMMS for GLPI

**MedMetric CMMS** turns GLPI into a Computerized Maintenance Management System (CMMS)
for hospitals: medical equipment registry, corrective/preventive maintenance, work orders,
spare parts stock, vendors and contracts, KPI analytics, and AI-assisted diagnosis.

Works with **GLPI 11** (min PHP 8.2).

## Features

- **Equipment registry** - medical devices with serial numbers, asset tags, types, models,
  departments, structures (buildings/floors/rooms), criticality, warranty and maintenance dates.
- **Departments & structures** - hospital departments/wards mapped to GLPI locations, plus an
  installation tree (building/floor/room) on top of locations.
- **Work orders** - corrective and preventive jobs with lifecycle (new, assigned, in progress,
  waiting parts, done, closed, cancelled), priorities, internal technicians or external vendors,
  downtime tracking, costs, and spare-part consumption linked to stock.
- **Preventive maintenance plans** - periodicity in days/weeks/months with a daily cron that
  generates planned occurrences and rolls schedules forward automatically.
- **Maintenance records** - interventions with kind (preventive/corrective/calibration/inspection),
  duration, technician and checklist.
- **Notifications & alerts** - fault reports and automated cron alerts for maintenance due and
  warranty expiry, with acknowledge workflow.
- **Solutions knowledge base** - reusable troubleshooting entries, captured automatically when a
  work order is closed with a solution description.
- **Spare parts inventory** - stock levels, min/ordered thresholds, unit prices, low-stock badges.
- **Vendors & contracts** - suppliers/service providers with warranty, maintenance, full-service
  and calibration contracts and expiry tracking.
- **Analytics** - KPI dashboard (open/overdue work orders, broken equipment, MTTR, downtime,
  low stock, expiring contracts), status/kind breakdowns, most-failing equipment.
- **Reports** - filterable report tables with CSV export.
- **AI assistance** - AI failure diagnosis, preventive plan suggestions and work order summaries
  through any OpenAI-compatible API (OpenAI, Groq, Azure OpenAI, Ollama).

## Requirements

| Component | Version |
|-----------|---------|
| GLPI      | >= 11.0 |
| PHP       | >= 8.2  |
| PHP curl  | required for AI features (optional otherwise) |

## Installation

1. Copy or clone this folder into `plugins/medmetriccmms`.
2. In GLPI go to **Setup > Plugins**, find **MedMetric CMMS** and click **Install**.
   The schema (17 tables), default configuration and cron tasks are created automatically.
3. Grant rights to profiles from the plugin rights entry on each profile
   (standard view/add/update/delete rights under `plugin_medmetriccmms`).
4. The plugin adds menu entries under Assets, Helpdesk, Management and Tools.

## AI configuration

The AI features work with any OpenAI-compatible chat completions endpoint:

1. Open **Setup > Plugins > MedMetric CMMS > Configuration**
   (or *Setup > General > plugin tab* via the CONFIG_PAGE hook).
2. Choose a provider:
   - **OpenAI** - base URL `https://api.openai.com/v1`, models like `gpt-4o-mini`.
   - **Groq** - base URL `https://api.groq.com/openai/v1`, models like `llama-3.3-70b-versatile`.
3. Paste the API key and save.
4. Optionally register dedicated models (per purpose: diagnosis, planning, reporting) under
   **Tools > AI models**; an active model with its own key overrides the global fallback.

Without a key everything else keeps working; the AI panels simply show a hint to configure it.

## Directory layout

```
medmetriccmms/
├── setup.php                     # Plugin version, hooks, install/uninstall
├── hook.php                      # GLPI hook callbacks
├── README.md
├── LICENSE
├── CHANGELOG.md
├── composer.json                 # Optional, for dev/tests
│
├── sql/
│   ├── install.sql               # Full schema + seed data
│   ├── uninstall.sql             # Drop all plugin tables
│   └── upgrade/
│       ├── 1.0.0_to_1.0.1.sql
│       ├── 1.0.1_to_1.0.2.sql
│       └── 1.0.2_to_1.0.3.sql
│
├── inc/                          # Domain classes + GLPI integration
│   ├── config.class.php          # Plugin settings, AI keys, defaults
│   ├── menu.class.php            # Menu registration helpers
│   ├── profile.class.php         # Profile rights / permissions
│   ├── department.class.php      # Departments / wards / units
│   ├── equipment.class.php       # Medical equipment / assets
│   ├── structure.class.php       # Location hierarchy / installation tree
│   ├── maintenance.class.php     # Maintenance tasks / interventions
│   ├── maintenanceplan.class.php # Preventive maintenance schedules
│   ├── workorder.class.php       # Work orders (corrective + preventive)
│   ├── notification.class.php    # Fault reports / alerts
│   ├── solution.class.php        # Solutions / knowledge base
│   ├── inventory.class.php       # Spare parts, consumables, stock
│   ├── vendor.class.php          # External service providers
│   ├── contract.class.php        # Warranty / service contracts
│   ├── analytics.class.php       # KPIs, dashboards, reports
│   ├── ai.class.php              # AI orchestration (Groq/OpenAI)
│   ├── aimodel.class.php         # AI model registry
│   ├── log.class.php             # Audit / AI request logs
│   ├── report.class.php          # Exportable reports
│   └── toolbox.class.php         # Shared helpers
│   ├── equipmenttype.class.php
│   └── equipmentmodel.class.php
│
├── front/                        # User-facing pages
├── ajax/                         # Dynamic endpoints (dropdowns, AI, charts)
├── css/  js/  pictures/          # Assets
├── locales/                      # pot, en_GB, fr_FR, am_ET
├── templates/                    # Twig templates (GLPI 10+)
└── tests/                        # bootstrap + Unit + Integration
```

## Tests

```bash
composer install
vendor/bin/phpunit --bootstrap tests/bootstrap.php tests
```

Unit tests run standalone (no database). Integration tests require a configured GLPI instance.

## License

GPL v3 or later - see [LICENSE](LICENSE).
