# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a WordPress plugin called "DeweloperJawneCeny" that automates the process of providing data in compliance with the Polish law on real estate price transparency (Ustawa z dnia 21 maja 2025 r. o zmianie ustawy o ochronie praw nabywcy lokalu mieszkalnego). The plugin generates XML and CSV files with property pricing data according to legal requirements.

## Architecture

### Freemium/Premium Model
The plugin uses a freemium model with premium features separated:
- **Freemium**: Core functionality (manual file generation, data management)
- **Premium**: Automation features (external cron, automated publishing)
- **Structure**: `includes/premium/` folder contains all premium features
- **Detection**: `PremiumHelper::is_premium()` checks for premium folder existence
- **Loading**: `includes/premium/loader.php` conditionally loads premium features

### Premium Features (includes/premium/)
- **Controllers**: `ExternalCronController`, `UJC_Automated_Generator`
- **Repositories**: `ExternalCronRepository`
- **Use Cases**: All External Cron and Automation Use Cases
- **Conditional Loading**: Only loaded when `includes/premium/` folder exists

### Naming Conventions
The codebase follows consistent, clean naming without prefixes:
- **Use Cases**: `{Action}{Entity}UseCase` (e.g., `GenerateCSVFileUseCase`, `SaveResourceUseCase`)
- **Services/Helpers**: `{Purpose}{Service}` (e.g., `CSVFormatter`, `FileManager`, `DateHelper`)
- **Repositories**: `{Entity}Repository` (e.g., `DeveloperRepository`, `ResourceRepository`)
- **Controllers**: `{Purpose}Controller` (e.g., `AutomatedGenerator`, `FileDownloadHandler`)
- **Database Tables**: `{table_name}` (no prefixes - e.g., `developers`, `resources`)
- **Constants**: `{CONSTANT_NAME}` (no prefixes - e.g., `PLUGIN_DIR`, `DB_VERSION`)
- **CSS**: `.{semantic-name}` or `#{semantic-name}` (no prefixes - e.g., `.admin-page`, `#resource-form`)
- **Variables**: Domain-specific names (`$resources`, not `$properties`)

### Use Case Pattern
The codebase follows a Use Case pattern for business logic:
- **Location**: `includes/UseCases/`
- **Pattern**: Each use case is a class handling a specific business operation
- **Key Use Cases**:
  - `SaveResourceUseCase` - Saves property resources
  - `GenerateCSVFileUseCase` - CSV file generation
  - `GenerateXMLFileUseCase` - XML file generation
  - `ToggleAutomationUseCase` - Toggle automated publishing
  - `ImportResourcesUseCase` - Import property data from CSV

### Repository Pattern
Data access is handled through repositories:
- **Location**: `includes/repositories/`
- **Key Repositories**:
  - `DeveloperRepository` - Developer data management
  - `InvestmentRepository` - Investment project management
  - `ResourceRepository` - Property resource management
  - `PriceHistoryRepository` - Price change tracking
  - `SettingsRepository` - Plugin settings

### Service Layer
Pure business logic and formatting services:
- **Location**: `includes/services/`
- **Key Services**:
  - `CSVFormatter` - Pure CSV generation logic
  - `XMLFormatter` - Pure XML generation logic
  - `FileManager` - File system operations
  - `DateHelper` - Date formatting utilities

### MVC Structure
- **Controllers** (`includes/controllers/`): Handle automation and orchestration
  - `AutomatedGenerator` - Automation management (cron, history, status)
  - `FileDownloadHandler` - File serving and download management
- **Views** (`includes/views/`): Admin interface and frontend display
  - Admin pages extend `AbstractAdminPage`
  - Components for modals and UI elements
- **Models**: Data structures are managed through repositories

### Database Architecture
- Custom WordPress tables: `developers`, `resources`, `investments`, `price_history`
- Versioning system: `DatabaseVersioning` manages schema updates
- Current DB version: 1.1 (defined in `DB_VERSION`)

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
- **Admin Interface**: `includes/views/admin/AdminInterface.php` - Admin menu and page routing
- **Database Schema**: `includes/core/SchemaManager.php` - Table creation and updates
- **Automated Publishing**: `includes/controllers/AutomatedGenerator.php` - Cron job management

## Data Flow

1. Developer/Investment data is entered via admin interface
2. Property resources are added with pricing information
3. `GenerateCSVFileUseCase` and `GenerateXMLFileUseCase` handle file generation
4. `AutomatedGenerator` manages automation (scheduling, history, status)
5. Files are stored in `/wp-content/uploads/ujc-data/`
6. Price changes are tracked in history table
7. Public data can be displayed via shortcode `[ujc_public_data]`

## Important Constants

- `PLUGIN_DIR` - Plugin directory path
- `PLUGIN_URL` - Plugin URL
- `DB_VERSION` - Database schema version (1.1)
- `VERSION` - Plugin version (check `ustawa-jawnosci-cen.php` for current)

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

## Shortcode [resources_list]

The plugin provides a powerful `[resources_list]` shortcode for displaying property listings on frontend pages with full customization options.

### Basic Usage
```
[resources_list]
```
Displays residential units with default columns and styling.

### Parameters

#### Property Types (`types`)
Comma-separated list of property types to display:
- `residential_unit` - Lokal mieszkalny (default)
- `single_family_house` - Dom jednorodzinny  
- `service_premises` - Lokal usługowy
- `parking_space` - Miejsce postojowe
- `storage_room` - Komórka lokatorska
- `garage` - Garaż

**Example:**
```
[resources_list types="residential_unit,parking_space"]
```

#### Columns (`columns`)
Comma-separated list in format `field:display_name`. **The order of elements determines the display order in the table**.

**Available Fields:**
- `nr_lokalu` - Unit number
- `rodzaj_nieruchomosci` - Property type
- `powierzchnia_uzytkowa` - Usable area (m²)
- `status` - Availability status
- `cena_m2` - Price per m²
- `cena_calkowita` - Total price
- `cena_z_dodatkami` - Price with extras
- `floor_number` - Floor number
- `room_count` - Number of rooms
- `additional_description` - Additional description
- `garden_area` - Garden area (m²)
- `floor_plan_pdf` - Floor plan PDF link
- `historia_cen` - Price history button

**Example:**
```
[resources_list columns="nr_lokalu:Numer,powierzchnia_uzytkowa:Powierzchnia,cena_calkowita:Cena,historia_cen:Historia"]
```

#### Clickable Rows (`detail_page_url`)
Optional parameter to make table rows clickable. When clicked, opens detail page in new tab.

**Format:** Base URL ending with `/`
**Result:** `detail_page_url` + `nr_lokalu` (e.g., `/mieszkania/A1`)

**Example:**
```
[resources_list detail_page_url="/mieszkania/"]
```

#### Styling Options
- `header_bg_color` - Table header background color (default: `#f9f9f9`)
- `header_text_color` - Header text color (default: `#333333`)
- `button_bg_color` - Button background color (default: `#007cba`)
- `button_text_color` - Button text color (default: `#ffffff`)
- `hover_bg_color` - Row hover background color (default: `#f5f5f5`)
- `text_color` - Table content text color (default: `#333333`)
- `header_font_family` - Header font family (default: `inherit`)
- `content_font_family` - Content font family (default: `inherit`)

### Advanced Examples

**Custom property types and columns:**
```
[resources_list
    types="residential_unit,parking_space"
    columns="nr_lokalu:Lokal,rodzaj_nieruchomosci:Typ,powierzchnia_uzytkowa:Powierzchnia,cena_calkowita:Cena_całkowita,status:Status,historia_cen:Historia"
    button_bg_color="#28a745"
    header_bg_color="#e9ecef"]
```

**Single property type with custom styling:**
```
[resources_list
    types="single_family_house"
    columns="nr_lokalu:Dom,powierzchnia_uzytkowa:Powierzchnia,garden_area:Ogród,cena_calkowita:Cena,floor_plan_pdf:Plan"
    header_bg_color="#343a40"
    header_text_color="#ffffff"
    button_bg_color="#dc3545"]
```

**Clickable rows with detail pages:**
```
[resources_list
    detail_page_url="/mieszkania/"
    types="residential_unit"
    columns="nr_lokalu:Numer,powierzchnia_uzytkowa:Powierzchnia,cena_calkowita:Cena"]
```

**All features combined:**
```
[resources_list
    detail_page_url="/nieruchomosci/"
    types="residential_unit,parking_space"
    columns="nr_lokalu:Lokal,rodzaj_nieruchomosci:Typ,cena_calkowita:Cena,historia_cen:Historia"
    header_bg_color="#2c3e50"
    header_text_color="#ffffff"
    button_bg_color="#e74c3c"]
```

### Column Ordering
**Important:** The order of fields in the `columns` parameter determines the order of columns in the displayed table. Clients can customize column order by rearranging the field names in the shortcode parameter.

**Example - Different column orders:**
```
[resources_list columns="cena_calkowita:Cena,nr_lokalu:Lokal,powierzchnia_uzytkowa:Powierzchnia"]
```
Will display: Price | Unit Number | Area

```
[resources_list columns="nr_lokalu:Lokal,powierzchnia_uzytkowa:Powierzchnia,cena_calkowita:Cena"]
```  
Will display: Unit Number | Area | Price

## Deployment

### Building Releases
Use the deployment script to build both premium and freemium versions:

```bash
./deploy.sh
```

**Outputs:**
- `build/DeweloperJawneCeny-premium-{version}.zip` - Full version with automation
- `build/DeweloperJawneCeny-Free-{version}.zip` - Freemium version without premium folder

**Installation folder name:** `Deweloper Jawne Ceny`

### Branch Strategy
- **develop**: Active development branch
- **production**: Stable releases for customers

### Version Management
- Version automatically extracted from `ustawa-jawnosci-cen.php` header
- Premium version: `1.24.2`
- Freemium version: `1.24.2-free`

## Notes

- Plugin follows WordPress coding standards with `UJC_` prefix for classes
- All database operations use WordPress `$wpdb` global
- File operations use WordPress filesystem functions
- Admin pages check capabilities before rendering
- Uninstall cleanup handled in `uninstall.php`
- Premium features cleanly separated for easy maintenance