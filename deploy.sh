#!/bin/bash

# Deploy Script for DeweloperJawneCeny Plugin
# Creates both Premium and Freemium versions from single codebase

set -e  # Exit on any error

# Get absolute paths
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BUILD_DIR="${SCRIPT_DIR}/build"

# Check if version argument provided
if [ -n "$1" ]; then
    VERSION="$1"
    echo "🔧 Setting version to ${VERSION} in all files..."
    
    # Update main plugin header
    sed -i '' "s/ \* Version: .*/ * Version: ${VERSION}/" "${SCRIPT_DIR}/ustawa-jawnosci-cen.php"

    # Update VERSION constant
    sed -i '' "s/define('VERSION', '[^']*')/define('VERSION', '${VERSION}')/" "${SCRIPT_DIR}/ustawa-jawnosci-cen.php"

    # Update readme.txt
    sed -i '' "s/Stable tag: .*/Stable tag: ${VERSION}/" "${SCRIPT_DIR}/readme.txt"
    
    echo "✅ Version updated to ${VERSION} in all files"
else
    # Extract version from main plugin file header (existing logic)
    VERSION=$(grep "Version:" "${SCRIPT_DIR}/ustawa-jawnosci-cen.php" | sed "s/.*Version: //" | tr -d '\r\n ')
    
    if [ -z "$VERSION" ]; then
        echo "❌ Error: Could not extract version from ustawa-jawnosci-cen.php header"
        exit 1
    fi
fi

echo "🚀 Building Deweloper Jawne Ceny Plugin v${VERSION}"

# Create build directory
mkdir -p "${BUILD_DIR}"

echo "📦 Building Premium version..."
# Build Premium version (full codebase)
PREMIUM_DIR="${BUILD_DIR}/Deweloper Jawne Ceny"
mkdir -p "${PREMIUM_DIR}"

# Copy all files except build directory
rsync -av --exclude='build' --exclude='.git' --exclude='.claude' --exclude='CLAUDE.md' --exclude='.DS_Store' --exclude='**/.gitignore' "${SCRIPT_DIR}/" "${PREMIUM_DIR}/"

# Cleanup premium temp
rm -f "${PREMIUM_DIR}/deploy.sh" "${PREMIUM_DIR}/test.sh"

# Create premium ZIP with proper folder structure
cd "${BUILD_DIR}"
zip -r "DeweloperJawneCeny-premium-${VERSION}.zip" "Deweloper Jawne Ceny" -x "*.DS_Store"
rm -rf "Deweloper Jawne Ceny"

echo "📦 Building Freemium version..."
# Build Freemium version (no premium folder)
FREEMIUM_DIR="${BUILD_DIR}/Deweloper Jawne Ceny"
mkdir -p "${FREEMIUM_DIR}"

# Copy all files except build directory and premium folder
rsync -av --exclude='build' --exclude='.git' --exclude='includes/premium' --exclude='.claude' --exclude='CLAUDE.md' --exclude='.DS_Store' --exclude='**/.gitignore' "${SCRIPT_DIR}/" "${FREEMIUM_DIR}/"

# Update plugin header for freemium
sed -i '' 's/Plugin Name: DeweloperJawneCeny/Plugin Name: Deweloper Jawne Ceny - Free/' "${FREEMIUM_DIR}/ustawa-jawnosci-cen.php"
sed -i '' 's/Description: Plugin do automatyzacji/Description: Darmowa wersja - ręczne generowanie plików zgodnie z ustawą/' "${FREEMIUM_DIR}/ustawa-jawnosci-cen.php"

# Cleanup
rm -f "${FREEMIUM_DIR}/deploy.sh" "${FREEMIUM_DIR}/test.sh"

# Create freemium ZIP with proper folder structure
cd "${BUILD_DIR}"
zip -r "DeweloperJawneCeny-Free-${VERSION}.zip" "Deweloper Jawne Ceny" -x "*.DS_Store"
rm -rf "Deweloper Jawne Ceny"

echo "✅ Build completed successfully!"
echo "📁 Premium version: ${BUILD_DIR}/DeweloperJawneCeny-premium-${VERSION}.zip"
echo "📁 Freemium version: ${BUILD_DIR}/DeweloperJawneCeny-Free-${VERSION}.zip"

# Show file sizes
echo ""
echo "📊 File sizes:"
ls -lh ${BUILD_DIR}/*.zip

echo ""
echo "📁 Folder structure inside ZIP:"
echo "   wp-content/plugins/Deweloper Jawne Ceny/"