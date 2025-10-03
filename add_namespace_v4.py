#!/usr/bin/env python3
"""
Add namespace JawneCeny to PHP files after ABSPATH check
Handles both single-line and multi-line ABSPATH formats
"""

import os
import re

base_dir = "/Users/bartoszkulasiewicz/Desktop/DeweloperJawneCeny"

# Files that need namespace (from check_namespaces.py output)
files = [
    "includes/blocks/ResourcesFiltersBlock.php",
    "includes/blocks/BlocksManager.php",
    "includes/config/TableNames.php",
    "includes/repositories/SettingsRepository.php",
    "includes/models/FilterCriteria.php",
    "includes/models/ShortcodeResource.php",
    "includes/models/DaneGovXmlDataset.php",
    "includes/models/UsageRightFormData.php",
    "includes/models/BelongingRoomFormData.php",
    "includes/models/PresentableResource.php",
    "includes/models/FileData.php",
    "includes/models/SortOptions.php",
    "includes/models/FileGenerationResult.php",
    "includes/models/OtherServiceFormData.php",
    "includes/models/PresentablePriceHistory.php",
    "includes/models/ResourceFormData.php",
    "includes/models/PropertyPartFormData.php",
    "includes/shortcodes/ShortcodeManager.php",
    "includes/views/admin/Dashboard/HistoryTile.php",
    "includes/views/admin/pages/DevConsoleTile.php",
    "includes/views/admin/pages/DashboardPage.php",
    "includes/premium/controllers/WpCronFallbackController.php",
    "includes/premium/UseCases/UnregisterExternalCronUseCase.php",
    "includes/premium/UseCases/RegisterExternalCronUseCase.php",
    "includes/premium/UseCases/WpCronFallbackUseCase.php",
    "includes/premium/UseCases/UpdateExternalCronScheduleUseCase.php",
    "includes/helpers/Logger.php",
    "includes/UseCases/Developer/SaveDeveloperInfoUseCase.php",
    "includes/UseCases/Developer/GetDeveloperInfoUseCase.php",
    "includes/UseCases/Investment/UpdateInvestmentUseCase.php",
    "includes/UseCases/Investment/CreateInvestmentUseCase.php",
    "includes/UseCases/Investment/GetInvestmentUseCase.php",
    "includes/UseCases/Frontend/GetResourcesForFrontendUseCase.php",
    "includes/UseCases/PriceHistory/GetPriceHistoryUseCase.php",
    "includes/UseCases/PublicationHistory/AddPublicationHistoryUseCase.php",
    "includes/UseCases/PublicationHistory/GetPublicationHistoryUseCase.php",
    "includes/UseCases/Resources/UpdateResourceUseCase.php",
    "includes/UseCases/Resources/DeleteResourceUseCase.php",
    "includes/UseCases/Resources/CreateResourceUseCase.php",
    "includes/UseCases/Resources/GetResourceByIdUseCase.php",
    "includes/UseCases/Resources/GetAllResourcesUseCase.php",
    "includes/UseCases/Resources/GetResourcesForGovernmentUseCase.php",
    "includes/UseCases/System/ImportResourcesUseCase.php",
    "includes/UseCases/System/ResetDatabaseUseCase.php",
    "includes/UseCases/XmlResource/AddXmlResourceUseCase.php",
    "includes/UseCases/FileGeneration/CreateDaneGovSubmissionFilesUseCase.php",
    "includes/UseCases/FileGeneration/GenerateCSVFileUseCase.php",
    "includes/UseCases/FileGeneration/GenerateFilesUseCase.php",
    "includes/services/DateHelper.php",
]

# Files that extend JawneCeny_AdminPage and need use statement
files_needing_adminpage_use = [
    "includes/views/admin/pages/DashboardPage.php",
]

def add_namespace_to_file(filepath):
    """Add namespace JawneCeny to a PHP file after ABSPATH check"""

    full_path = os.path.join(base_dir, filepath)

    if not os.path.exists(full_path):
        print(f"❌ File not found: {filepath}")
        return False

    with open(full_path, 'r', encoding='utf-8') as f:
        content = f.read()

    # Check if namespace already exists
    if re.search(r'^\s*namespace\s+JawneCeny\s*;', content, re.MULTILINE):
        print(f"⏭️  Already has namespace: {filepath}")
        return True

    # Pattern 1: Single-line ABSPATH check
    # if (!defined('ABSPATH')) { exit; }
    single_line_pattern = r'(<\?php\s*\n\s*\n?)if\s*\(\s*!\s*defined\s*\(\s*[\'"]ABSPATH[\'"]\s*\)\s*\)\s*\{\s*exit;\s*\}'

    # Pattern 2: Multi-line ABSPATH check
    # if (!defined('ABSPATH')) {
    #     exit;
    # }
    multi_line_pattern = r'(<\?php\s*\n\s*\n?)if\s*\(\s*!\s*defined\s*\(\s*[\'"]ABSPATH[\'"]\s*\)\s*\)\s*\{\s*\n\s*exit;\s*\n\s*\}'

    replaced = False

    # Try multi-line pattern first (more specific)
    if re.search(multi_line_pattern, content):
        new_content = re.sub(
            multi_line_pattern,
            r'\1if (!defined(\'ABSPATH\')) {\n    exit;\n}\n\nnamespace JawneCeny;\n',
            content
        )
        replaced = True
    # Try single-line pattern
    elif re.search(single_line_pattern, content):
        new_content = re.sub(
            single_line_pattern,
            r'\1if (!defined(\'ABSPATH\')) { exit; }\n\nnamespace JawneCeny;\n',
            content
        )
        replaced = True
    else:
        print(f"⚠️  Could not find ABSPATH check pattern in: {filepath}")
        return False

    if not replaced:
        print(f"⚠️  Pattern not replaced in: {filepath}")
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
