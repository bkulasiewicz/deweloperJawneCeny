#!/usr/bin/env python3
"""Check which files need namespace"""

import os
import re

base_dir = "/Users/bartoszkulasiewicz/Desktop/DeweloperJawneCeny"

# Read the list of class files
with open('/tmp/php_classes.txt', 'r') as f:
    files = [line.strip() for line in f if 'vendor' not in line and line.strip()]

files_without_namespace = []
files_with_namespace = []

for filepath in files:
    full_path = os.path.join(base_dir, filepath)

    if not os.path.exists(full_path):
        continue

    with open(full_path, 'r', encoding='utf-8') as f:
        content = f.read()

    if re.search(r'^\s*namespace\s+JawneCeny\s*;', content, re.MULTILINE):
        files_with_namespace.append(filepath)
    else:
        files_without_namespace.append(filepath)

print(f"Files WITH namespace: {len(files_with_namespace)}")
print(f"Files WITHOUT namespace: {len(files_without_namespace)}")
print("\nFiles needing namespace:\n")
for f in files_without_namespace:
    print(f"  {f}")
