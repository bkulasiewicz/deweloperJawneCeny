#!/bin/bash

# Deploy Script for DeweloperJawneCeny Plugin
# Creates both Premium and Freemium versions from single codebase

set -e  # Exit on any error

# Extract version from main plugin file
VERSION=$(grep "Version:" ustawa-jawnosci-cen.php | head -1 | sed 's/.*Version: //' | sed 's/[[:space:]]*$//')

if [ -z "$VERSION" ]; then
    echo "❌ Error: Could not extract version from ustawa-jawnosci-cen.php"
    exit 1
fi

echo "🚀 Building Deweloper Jawne Ceny Plugin v${VERSION}"

# Create build directory
BUILD_DIR="build"
mkdir -p ${BUILD_DIR}

echo "📦 Building Premium version..."
# Build Premium version (full codebase)
mkdir -p ${BUILD_DIR}/"Deweloper Jawne Ceny"
cp -r . ${BUILD_DIR}/"Deweloper Jawne Ceny"/

# Cleanup premium temp
cd ${BUILD_DIR}/"Deweloper Jawne Ceny"
rm -rf .git build
rm -f deploy.sh test.sh

# Create premium ZIP with proper folder structure
cd ${BUILD_DIR}
zip -r "DeweloperJawneCeny-premium-${VERSION}.zip" "Deweloper Jawne Ceny" -x "*.DS_Store"
rm -rf "Deweloper Jawne Ceny"
cd ..

echo "📦 Building Freemium version..."
# Build Freemium version (no premium folder)
mkdir -p ${BUILD_DIR}/"Deweloper Jawne Ceny"
cp -r . ${BUILD_DIR}/"Deweloper Jawne Ceny"/

# Remove premium folder from freemium
rm -rf ${BUILD_DIR}/"Deweloper Jawne Ceny"/includes/premium/

# Update plugin header for freemium
cd ${BUILD_DIR}/"Deweloper Jawne Ceny"
sed -i.bak 's/Plugin Name: DeweloperJawneCeny/Plugin Name: Deweloper Jawne Ceny - Free/' ustawa-jawnosci-cen.php
sed -i.bak "s/Version: ${VERSION}/Version: ${VERSION}-free/" ustawa-jawnosci-cen.php
sed -i.bak 's/Description: Plugin do automatyzacji/Description: Darmowa wersja - ręczne generowanie plików zgodnie z ustawą/' ustawa-jawnosci-cen.php

# Cleanup
rm -rf .git build
rm -f deploy.sh test.sh *.bak

# Create freemium ZIP with proper folder structure
cd ${BUILD_DIR}
zip -r "DeweloperJawneCeny-Free-${VERSION}.zip" "Deweloper Jawne Ceny" -x "*.DS_Store"
rm -rf "Deweloper Jawne Ceny"
cd ..

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