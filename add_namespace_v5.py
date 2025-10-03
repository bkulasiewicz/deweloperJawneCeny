#!/usr/bin/env python3
"""
Add namespace JawneCeny to PHP files after ABSPATH check
Handles both single-line and multi-line ABSPATH formats
v5 - Fixed string escaping issue
"""

import os
import re

base_dir = "/Users/bartoszkulasiewicz/Desktop/DeweloperJawneCeny"

# Read the list of class files
with open('/tmp/php_classes.txt', 'r') as f:
    files = [line.strip() for line in f if 'vendor' not in line and line.strip()]

# Files that extend JawneCeny_AdminPage and need use statement
files_needing_adminpage_use = [
    "includes/views/admin/supplier-data/SupplierDataPage.php",
    "includes/views/admin/pages/resources-page/ResourcesPage.php",
    "includes/views/admin/pages/publication-page/PublicationPage.php",
    "includes/views/admin/pages/DashboardPage.php",
    "includes/views/admin/frontend-management/FrontendManagementPage.php",
    "includes/views/admin/shortcode-generator/ShortcodeGeneratorPage.php",
]

def add_namespace_to_file(filepath):
    """Add namespace JawneCeny to a PHP file after ABSPATH check"""

    full_path = os.path.join(base_dir, filepath)

    if not os.path.exists(full_path):
        return False

    with open(full_path, 'r', encoding='utf-8') as f:
        content = f.read()

    # Check if namespace already exists
    if re.search(r'^\s*namespace\s+JawneCeny\s*;', content, re.MULTILINE):
        return True

    # Find the end of ABSPATH check block
    # Look for closing brace after ABSPATH check
    match = re.search(
        r'(<\?php\s*\n\s*\n?)(if\s*\(\s*!\s*defined\s*\(\s*[\'"]ABSPATH[\'"]\s*\)\s*\)\s*\{[^}]*\})',
        content,
        re.DOTALL
    )

    if not match:
        return False

    # Build the new content
    php_tag = match.group(1)
    abspath_block = match.group(2)
    rest_of_file = content[match.end():]

    # Normalize ABSPATH block format (single-line)
    abspath_normalized = "if (!defined('ABSPATH')) { exit; }"

    # Build new file content
    new_content = php_tag + abspath_normalized + "\n\nnamespace JawneCeny;\n" + rest_of_file

    # Add use statement for JawneCeny_AdminPage if needed
    if filepath in files_needing_adminpage_use:
        new_content = new_content.replace(
            "namespace JawneCeny;\n",
            "namespace JawneCeny;\n\nuse JawneCeny_AdminPage;\n"
        )

    # Write back
    with open(full_path, 'w', encoding='utf-8') as f:
        f.write(new_content)

    return True

def main():
    print("🚀 Adding namespace JawneCeny to PHP files (v5)...\n")

    success_count = 0
    fail_count = 0

    for filepath in files:
        if add_namespace_to_file(filepath):
            success_count += 1
            print(f"✅ {filepath}")
        else:
            fail_count += 1
            print(f"❌ {filepath}")

    print(f"\n📊 Results:")
    print(f"   ✅ Success: {success_count}")
    print(f"   ❌ Failed: {fail_count}")
    print(f"   📁 Total: {len(files)}")

if __name__ == "__main__":
    main()
