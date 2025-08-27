# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a WordPress plugin called "DeweloperJawneCeny" that automates the process of providing data in compliance with the Polish law on real estate price transparency (Ustawa z dnia 21 maja 2025 r. o zmianie ustawy o ochronie praw nabywcy lokalu mieszkalnego). The plugin generates XML and CSV files with property pricing data according to legal requirements.

## Architecture

### Use Case Pattern
The codebase follows a Use Case pattern for business logic:
- **Location**: `includes/UseCases/`
- **Pattern**: Each use case is a class handling a specific business operation
- **Key Use Cases**:
  - `SaveResourceUseCase` - Saves property resources
  - `GenerateFilesUseCase` - File generation (CSV and XML)
  - `ToggleAutomationUseCase` - Toggle automated publishing
  - `ImportResourcesUseCase` - Import property data from CSV

### Repository Pattern
Data access is handled through repositories:
- **Location**: `includes/repositories/`
- **Key Repositories**:
  - `UJC_Developer_Repository` - Developer data management
  - `UJC_Investment_Repository` - Investment project management
  - `UJC_Resource_Repository` - Property resource management
  - `UJC_Price_History_Repository` - Price change tracking
  - `SettingsRepository` - Plugin settings

### MVC Structure
- **Controllers** (`includes/controllers/`): Handle automation and generation
  - `UJC_XML_Generator` - XML file generation logic
  - `UJC_CSV_Generator` - CSV file generation logic
  - `UJC_Automated_Generator` - Automation management (cron, history, status)
- **Views** (`includes/views/`): Admin interface and frontend display
  - Admin pages extend `abstract-ujc-admin-page.php`
  - Components for modals and UI elements
- **Models**: Data structures are managed through repositories

### Database Architecture
- Custom WordPress tables with prefix `ujc_`
- Versioning system: `UJC_Database_Versioning` manages schema updates
- Current DB version: 1.1 (defined in `UJC_DB_VERSION`)

## Development Commands

### Testing
No test framework detected. Manual testing through WordPress admin panel.

### Building/Packaging
Create plugin ZIP for distribution:
```bash
zip -r DeweloperJawneCeny-{version}.zip . -x "*.git*" -x "node_modules/*" -x "*.DS_Store" -x "website/*"
```

### WordPress Development
```bash
# Activate plugin
wp plugin activate ustawa-jawnosci-cen

# Deactivate plugin  
wp plugin deactivate ustawa-jawnosci-cen

# Check plugin status
wp plugin status ustawa-jawnosci-cen
```

## Key Files and Entry Points

- **Main Plugin File**: `ustawa-jawnosci-cen.php` - Plugin initialization and dependency loading
- **Admin Interface**: `includes/views/admin/class-ujc-admin.php` - Admin menu and page routing
- **Database Schema**: `includes/core/class-ujc-schema-manager.php` - Table creation and updates
- **Automated Publishing**: `includes/controllers/class-ujc-automated-generator.php` - Cron job management

## Data Flow

1. Developer/Investment data is entered via admin interface
2. Property resources are added with pricing information
3. `GenerateFilesUseCase` handles all file generation (CSV and XML)
4. `UJC_Automated_Generator` manages automation (scheduling, history, status)
5. Files are stored in `/wp-content/uploads/ujc-data/`
6. Price changes are tracked in history table
7. Frontend shortcode system prepared for future implementation

## Important Constants

- `UJC_PLUGIN_DIR` - Plugin directory path
- `UJC_PLUGIN_URL` - Plugin URL
- `UJC_DB_VERSION` - Database schema version (1.1)
- `UJC_VERSION` - Plugin version (check `ustawa-jawnosci-cen.php` for current)

## File Generation Format

The plugin generates XML files compliant with Polish government requirements. Reference files are in `materialydlaAI/`:
- Template: `Szablon_budowy_pliku_xml_v.1.13_21.08.2025 (3).xml`
- Example: `Przykład_3_kolejne_publikacje_v.1.13_21.08.2025 (1).xml`
- CSV template: `Wcorcowy_zakres_danych_dotyczących_cen_mieszkań (1).csv`

## WordPress Hooks

Key actions:
- `plugins_loaded` - Initialize plugin components
- `ujc_generate_files` - Cron hook for automated generation
- Admin menu added at priority 10

## Notes

- Plugin follows WordPress coding standards with `UJC_` prefix for classes
- All database operations use WordPress `$wpdb` global
- File operations use WordPress filesystem functions
- Admin pages check capabilities before rendering
- Uninstall cleanup handled in `uninstall.php`