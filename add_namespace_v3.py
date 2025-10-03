#!/usr/bin/env python3
"""
Add namespace JawneCeny to PHP files after ABSPATH check
Handles both single-line and multi-line ABSPATH formats
"""

import os
import re

# Files to process
files = [
    # DTOs
    "includes/dto/PublicationHistoryDto.php",
    "includes/dto/PriceHistoryDto.php",
    "includes/dto/XmlResourceDto.php",
    "includes/dto/InvestmentDto.php",
    "includes/dto/ResourceDto.php",
    "includes/dto/SupplierDto.php",

    # Enums
    "includes/enums/PropertyType.php",
    "includes/enums/ResourceStatus.php",
    "includes/enums/TriggerType.php",
    "includes/enums/PublicationStatus.php",

    # Core (exclude JawneCeny_SchemaManager and JawneCeny_AdminPage)
    "includes/core/Result.php",
    "includes/core/DIContainer.php",

    # Models
    "includes/models/Resource.php",
    "includes/models/Investment.php",
    "includes/models/Supplier.php",
    "includes/models/PriceHistory.php",
    "includes/models/XmlResource.php",
    "includes/models/Config.php",
    "includes/models/AutomationSettings.php",
    "includes/models/FrontendSettings.php",
    "includes/models/PublicationHistory.php",
    "includes/models/ExternalCron.php",
    "includes/models/PropertySettings.php",
    "includes/models/ColumnSettings.php",
    "includes/models/StylingSettings.php",
    "includes/models/ResourceFilters.php",

    # Repositories
    "includes/repositories/InvestmentRepository.php",
    "includes/repositories/ResourceRepository.php",
    "includes/repositories/SupplierRepository.php",
    "includes/repositories/DeveloperRepository.php",
    "includes/repositories/PriceHistoryRepository.php",
    "includes/repositories/XmlResourceRepository.php",
    "includes/repositories/PublicationHistoryRepository.php",

    # Services
    "includes/services/CSVFormatter.php",
    "includes/services/XMLFormatter.php",
    "includes/services/FileManager.php",
    "includes/services/Logger.php",

    # Helpers
    "includes/helpers/DateHelper.php",
    "includes/helpers/PremiumHelper.php",
    "includes/helpers/ResourceCardRenderer.php",
    "includes/helpers/ResourceTableRenderer.php",

    # Use Cases
    "includes/UseCases/GenerateCSVFileUseCase.php",
    "includes/UseCases/GenerateXMLFileUseCase.php",
    "includes/UseCases/GetAllResourcesUseCase.php",
    "includes/UseCases/GetResourceByIdUseCase.php",
    "includes/UseCases/SaveResourceUseCase.php",
    "includes/UseCases/DeleteResourceUseCase.php",
    "includes/UseCases/GetPriceHistoryUseCase.php",
    "includes/UseCases/ImportResourcesFromCSVUseCase.php",
    "includes/UseCases/GetAllResourcesForFrontendUseCase.php",
    "includes/UseCases/GetResourcesForFrontendUseCase.php",
    "includes/UseCases/GetXmlResourcesUseCase.php",
    "includes/UseCases/GetPublicationHistoryUseCase.php",
    "includes/UseCases/SaveAutomationSettingsUseCase.php",
    "includes/UseCases/GetAutomationSettingsUseCase.php",
    "includes/UseCases/ToggleAutomationUseCase.php",
    "includes/UseCases/SaveFrontendSettingsUseCase.php",
    "includes/UseCases/GetFrontendSettingsUseCase.php",
    "includes/UseCases/GetInvestmentDetailsUseCase.php",
    "includes/UseCases/SaveInvestmentUseCase.php",
    "includes/UseCases/GetSupplierDetailsUseCase.php",
    "includes/UseCases/SaveSupplierUseCase.php",
    "includes/UseCases/ValidateCSVStructureUseCase.php",

    # Premium
    "includes/premium/controllers/ExternalCronController.php",
    "includes/premium/controllers/UJC_Automated_Generator.php",
    "includes/premium/repositories/ExternalCronRepository.php",
    "includes/premium/UseCases/SaveExternalCronUseCase.php",
    "includes/premium/UseCases/GetExternalCronUseCase.php",
    "includes/premium/UseCases/DisableExternalCronUseCase.php",
    "includes/premium/UseCases/DeleteExternalCronUseCase.php",
    "includes/premium/UseCases/ToggleExternalCronUseCase.php",

    # Blocks
    "includes/blocks/ResourcesListBlock.php",

    # Shortcodes
    "includes/shortcodes/ResourcesListShortcode.php",

    # Controllers
    "includes/controllers/AdminController.php",

    # Views - Admin Interface
    "includes/views/admin/AdminInterface.php",
    "includes/views/admin/supplier-data/SupplierDataPage.php",
    "includes/views/admin/pages/resources-page/ResourcesPage.php",
    "includes/views/admin/pages/publication-page/PublicationPage.php",
    "includes/views/admin/pages/publication-page/csv-files-section/CsvFilesSection.php",
    "includes/views/admin/components/resource-modal/ResourceModal.php",
    "includes/views/admin/components/investment-modal/InvestmentModal.php",
    "includes/views/admin/components/resource-item/ResourceItem.php",
    "includes/views/admin/components/history-modal/HistoryModal.php",
    "includes/views/admin/Dashboard/DashboardPage.php",
    "includes/views/admin/Dashboard/automation-tile/AutomationTile.php",
    "includes/views/admin/Dashboard/csv-files-section/CsvFilesSection.php",
    "includes/views/admin/frontend-management/FrontendManagementPage.php",
    "includes/views/admin/shortcode-generator/ShortcodeGeneratorPage.php",
]

# Files that extend JawneCeny_AdminPage and need use statement
files_needing_adminpage_use = [
    "includes/views/admin/supplier-data/SupplierDataPage.php",
    "includes/views/admin/pages/resources-page/ResourcesPage.php",
    "includes/views/admin/pages/publication-page/PublicationPage.php",
    "includes/views/admin/Dashboard/DashboardPage.php",
    "includes/views/admin/frontend-management/FrontendManagementPage.php",
    "includes/views/admin/shortcode-generator/ShortcodeGeneratorPage.php",
]

def add_namespace_to_file(filepath):
    """Add namespace JawneCeny to a PHP file after ABSPATH check"""

    full_path = f"/Users/bartoszkulasiewicz/Desktop/DeweloperJawneCeny/{filepath}"

    if not os.path.exists(full_path):
        print(f"❌ File not found: {filepath}")
        return False

    with open(full_path, 'r', encoding='utf-8') as f:
        content = f.read()

    # Check if namespace already exists
    if re.search(r'^\s*namespace\s+JawneCeny\s*;', content, re.MULTILINE):
        print(f"⏭️  Already has namespace: {filepath}")
        return True

    # Pattern to match ABSPATH check (both single-line and multi-line)
    # Matches: if (!defined('ABSPATH')) { exit; }
    # OR:      if (!defined('ABSPATH')) {
    #              exit;
    #          }
    pattern = r'(<\?php\s*\n\s*\n?\s*)if\s*\(\s*!\s*defined\s*\(\s*[\'"]ABSPATH[\'"]\s*\)\s*\)\s*\{\s*exit;\s*\}'

    # For multi-line format, we need a more complex pattern
    multiline_pattern = r'(<\?php\s*\n\s*\n?\s*)if\s*\(\s*!\s*defined\s*\(\s*[\'"]ABSPATH[\'"]\s*\)\s*\)\s*\{\s*\n\s*exit;\s*\n\s*\}'

    # Try single-line pattern first
    if re.search(pattern, content):
        # Insert namespace after single-line ABSPATH check
        new_content = re.sub(
            pattern,
            r'\1if (!defined(\'ABSPATH\')) { exit; }\n\nnamespace JawneCeny;\n',
            content
        )
    elif re.search(multiline_pattern, content):
        # Insert namespace after multi-line ABSPATH check
        new_content = re.sub(
            multiline_pattern,
            r'\1if (!defined(\'ABSPATH\')) {\n    exit;\n}\n\nnamespace JawneCeny;\n',
            content
        )
    else:
        print(f"⚠️  Could not find ABSPATH check pattern in: {filepath}")
        return False

    # Add use statement for JawneCeny_AdminPage if needed
    if filepath in files_needing_adminpage_use:
        # Add use statement after namespace
        new_content = re.sub(
            r'(namespace JawneCeny;\n)',
            r'\1\nuse JawneCeny_AdminPage;\n',
            new_content
        )

    # Write back
    with open(full_path, 'w', encoding='utf-8') as f:
        f.write(new_content)

    print(f"✅ Added namespace: {filepath}")
    return True

def main():
    print("🚀 Adding namespace JawneCeny to PHP files...\n")

    success_count = 0
    fail_count = 0

    for filepath in files:
        if add_namespace_to_file(filepath):
            success_count += 1
        else:
            fail_count += 1

    print(f"\n📊 Results:")
    print(f"   ✅ Success: {success_count}")
    print(f"   ❌ Failed: {fail_count}")
    print(f"   📁 Total: {len(files)}")

if __name__ == "__main__":
    main()
