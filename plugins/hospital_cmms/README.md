# Hospital CMMS - Computerized Maintenance Management System for Hospitals

A GLPI plugin to convert GLPI into a hospital-focused CMMS for managing medical equipment, preventive maintenance, and departmental assets.

## Features

### Medical Equipment Management
- Register all medical equipment under hospital departments
- Track equipment types, models, manufacturers, and serial numbers
- Monitor equipment status, location, and assigned technicians
- Track purchase dates, warranty information, and commissioning dates
- Manage equipment calibration schedules

### Department Organization
- Hierarchical department structure (e.g., Surgery > Cardiac Surgery)
- Equipment categorized by department for easy tracking
- Department-based reporting and analytics

### Preventive Maintenance Scheduling
- Create maintenance tasks with customizable frequency
- Track maintenance history and execution
- Automatic next-execution date calculation
- Overdue maintenance alerts

### Calibration Management
- Track calibration dates for medical equipment
- Alerts for upcoming calibrations
- Calibration history logging

### Dashboard
- Total equipment count
- Active vs. maintenance-due equipment
- Upcoming maintenance schedule (next 30 days)
- Equipment distribution by department
- Calibration due alerts (next 90 days)

## Installation

1. Copy the `hospital_cmms` folder to GLPI's `plugins/` directory
2. Go to **Setup > Plugins** in GLPI
3. Find **Hospital CMMS** and click **Install**
4. Activate the plugin

## Database Tables Created

| Table | Description |
|-------|-------------|
| `glpi_plugin_hospital_cmms_categories` | Hospital departments (hierarchical) |
| `glpi_plugin_hospital_cmms_types` | Medical equipment types |
| `glpi_plugin_hospital_cmms_models` | Medical equipment models |
| `glpi_plugin_hospital_cmms_equipments` | Medical equipment records |
| `glpi_plugin_hospital_cmms_maintenance_tasks` | Preventive maintenance schedules |
| `glpi_plugin_hospital_cmms_maintenance_history` | Maintenance execution history |

## Default Data

### Pre-configured Departments
- Emergency Department
- Intensive Care Unit (ICU)
- Surgery
- Radiology
- Laboratory
- Pharmacy
- Cardiology
- Oncology
- Pediatrics
- Obstetrics
- Orthopedics
- Neurology
- Dental
- Ophthalmology
- Physical Therapy
- Administration

### Pre-configured Equipment Types
- Imaging Equipment
- Patient Monitoring
- Surgical Instruments
- Laboratory Equipment
- Life Support
- Diagnostic Equipment
- Therapeutic Equipment
- Dental Equipment
- Emergency Equipment
- Sterilization Equipment
- Rehabilitation Equipment
- Medical Furniture
- Respiratory Equipment
- Cardiology Equipment
- Ophthalmology Equipment

## Menu Items

After installation, the following menus will be available:

### Assets > Medical Equipment
- Register and manage medical equipment
- View equipment details and history
- Track calibration schedules

### Tools > Maintenance Tasks
- Create preventive maintenance schedules
- View upcoming and overdue tasks
- Record maintenance execution

### Administration > Hospital CMMS
- Configure plugin settings
- Manage departments
- Manage equipment types and models

## Permission System

The Hospital CMMS includes a role-based access control system for department-based visibility:

### User Roles

| Role | Description |
|------|-------------|
| **Administrator** | GLPI admins can see and modify all equipment across all departments |
| **Department Head** | Can see all equipment in their assigned department(s) |
| **Technician** | Can see all equipment in their department and modify assigned equipment |
| **Staff** | Can only see equipment assigned to them personally |

### Access Rules

1. **GLPI Administrators**: Full access to all departments and equipment
2. **Department Heads**: See all equipment in departments they manage
3. **Technicians**: See all equipment in their department, can modify assigned items
4. **Staff**: Only see equipment assigned to them (users_id or users_id_tech)

### Managing User-Department Assignments

1. Go to **Administration > Hospital CMMS > Department Management**
2. Select a department to manage
3. Add users and assign their role:
   - **Department Head**: Full access to department equipment
   - **Technician**: View all, modify assigned
   - **Staff**: View assigned only

### Example Access Matrix

| User Role | Department | Can See |
|-----------|------------|---------|
| Admin | All | All equipment |
| Department Head | ICU | All ICU equipment |
| Technician | ICU | All ICU equipment |
| Staff | ICU | Only equipment assigned to them |

---

## Configuration

### Hiding IT-Specific Menus

The plugin can automatically hide IT-specific menus (Computers, Monitors, Printers, etc.) to focus on medical equipment management. This is enabled by default.

To disable this feature:
1. Go to **Administration > Hospital CMMS**
2. Uncheck "Hide IT Menus"

### Customizing Departments

Departments can be customized via **Administration > Departments**:
- Add new departments
- Create sub-departments (hierarchical structure)
- Enable/disable departments
- Set department descriptions

## Search Options

The following search options are available for medical equipment:

| ID | Field | Type |
|----|-------|------|
| 2 | ID | Number |
| 3 | Name | String |
| 4 | Department | Dropdown |
| 5 | Equipment Type | Dropdown |
| 6 | Equipment Model | Dropdown |
| 7 | Manufacturer | Dropdown |
| 8 | Serial Number | String |
| 9 | Inventory Number | String |
| 10 | Location | Dropdown |
| 11 | Status | Dropdown |
| 12 | Technician in Charge | Dropdown |
| 13 | Group in Charge | Dropdown |
| 14 | Purchase Date | Date |
| 15 | Warranty Expiration | Date |
| 16 | Commissioning Date | Date |
| 17 | Value | Number |
| 18 | Last Calibration Date | Date |
| 19 | Next Calibration Date | Date |
| 20 | Comment | Text |

## API/Programmatic Access

### Get Equipment by Department
```php
$equipment = PluginHospitalCmmsMedicalEquipment::getEquipmentByDepartment($departmentId);
```

### Get Equipment Needing Calibration
```php
$equipment = PluginHospitalCmmsMedicalEquipment::getEquipmentNeedingCalibration($days = 30);
```

### Get Upcoming Maintenance Tasks
```php
$tasks = PluginHospitalCmmsMaintenanceTask::getUpcomingTasks($days = 30);
```

### Get Overdue Maintenance Tasks
```php
$tasks = PluginHospitalCmmsMaintenanceTask::getOverdueTasks();
```

## Branding

The plugin updates GLPI's branding to reflect Hospital CMMS:
- App name changed to "Hospital CMMS"
- Color scheme updated to medical blue theme
- Logo references updated for hospital branding

## Requirements

- GLPI 10.0.0 or higher
- PHP 8.1 or higher
- MySQL/MariaDB database

## License

GPLv2+ (same as GLPI)

## Credits

- GLPI Project for the base framework
- Hospital CMMS Contributors
