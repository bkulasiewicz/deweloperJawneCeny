#!/usr/bin/env python3
"""
Add namespace JawneCeny to PHP files - CORRECT VERSION
Namespace MUST be right after <?php, then ABSPATH check
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
    """Add namespace JawneCeny right after <?php"""

    full_path = os.path.join(base_dir, filepath)

    if not os.path.exists(full_path):
        return False

    with open(full_path, 'r', encoding='utf-8') as f:
        content = f.read()

    # Check if namespace already exists
    if re.search(r'^\s*namespace\s+JawneCeny\s*;', content, re.MULTILINE):
        return True

    # Pattern: <?php followed by newlines, then rest of file
    # We want: <?php\n\nnamespace JawneCeny;\n\n[rest]

    # Match <?php at the start
    match = re.match(r'^(<\?php)(\s*)(.*)', content, re.DOTALL)

    if not match:
        return False

    php_open = match.group(1)  # <?php
    rest = match.group(3)  # everything after <?php and whitespace

    # Build new content with namespace right after <?php
    new_content = php_open + "\n\nnamespace JawneCeny;\n\n"

    # Add use statement if needed
    if filepath in files_needing_adminpage_use:
        new_content += "use JawneCeny_AdminPage;\n\n"

    # Add the rest of the file
    new_content += rest

    # Write back
    with open(full_path, 'w', encoding='utf-8') as f:
        f.write(new_content)

    return True

def main():
    print("🚀 Adding namespace JawneCeny (FINAL VERSION)...\n")

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
