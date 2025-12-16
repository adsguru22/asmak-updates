#!/bin/bash
# Verification script for ASMAK Updates setup

echo "🔍 ASMAK Updates - Production Readiness Check"
echo "=============================================="
echo ""

# Color codes
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

PASSED=0
FAILED=0

# Function to check file exists
check_file() {
    if [ -f "$1" ]; then
        echo -e "${GREEN}✓${NC} $1 exists"
        ((PASSED++))
        return 0
    else
        echo -e "${RED}✗${NC} $1 missing"
        ((FAILED++))
        return 1
    fi
}

# Function to check JSON validity
check_json() {
    if python3 -m json.tool "$1" > /dev/null 2>&1; then
        echo -e "${GREEN}✓${NC} $1 is valid JSON"
        ((PASSED++))
        return 0
    else
        echo -e "${RED}✗${NC} $1 has invalid JSON syntax"
        ((FAILED++))
        return 1
    fi
}

# Function to check PHP syntax
check_php() {
    if php -l "$1" > /dev/null 2>&1; then
        echo -e "${GREEN}✓${NC} $1 has valid PHP syntax"
        ((PASSED++))
        return 0
    else
        echo -e "${RED}✗${NC} $1 has PHP syntax errors"
        ((FAILED++))
        return 1
    fi
}

echo "📋 Checking Core Files..."
echo "-------------------------"
check_file "index.html"
check_file "info.json"
check_file "README.md"
check_file "SETUP_GUIDE.md"
check_file "GITHUB_PAGES_SETUP.md"
check_file ".gitignore"
echo ""

echo "📋 Checking GitHub Actions..."
echo "-----------------------------"
check_file ".github/workflows/update-info.yml"
echo ""

echo "📋 Checking Plugin Integration Files..."
echo "---------------------------------------"
check_file "plugin-integration/updater.php"
check_file "plugin-integration/example-integration.php"
echo ""

echo "📋 Checking Assets..."
echo "--------------------"
check_file "assets/README.md"
echo ""

echo "🔬 Validating File Formats..."
echo "----------------------------"
check_json "info.json"
check_php "plugin-integration/updater.php"
check_php "plugin-integration/example-integration.php"
echo ""

echo "📊 Checking info.json Content..."
echo "--------------------------------"
if [ -f "info.json" ]; then
    # Check for required fields
    REQUIRED_FIELDS=("name" "version" "download_url" "requires" "tested" "requires_php")
    for field in "${REQUIRED_FIELDS[@]}"; do
        if grep -q "\"$field\"" info.json; then
            echo -e "${GREEN}✓${NC} Field '$field' present in info.json"
            ((PASSED++))
        else
            echo -e "${RED}✗${NC} Field '$field' missing in info.json"
            ((FAILED++))
        fi
    done
fi
echo ""

echo "🔍 Checking HTML Structure..."
echo "----------------------------"
if [ -f "index.html" ]; then
    if grep -q "<!DOCTYPE html>" index.html; then
        echo -e "${GREEN}✓${NC} HTML has DOCTYPE declaration"
        ((PASSED++))
    else
        echo -e "${RED}✗${NC} HTML missing DOCTYPE"
        ((FAILED++))
    fi
    
    if grep -q "<title>" index.html; then
        echo -e "${GREEN}✓${NC} HTML has title tag"
        ((PASSED++))
    else
        echo -e "${RED}✗${NC} HTML missing title tag"
        ((FAILED++))
    fi
    
    if grep -q "version-badge" index.html; then
        echo -e "${GREEN}✓${NC} HTML has version badge"
        ((PASSED++))
    else
        echo -e "${YELLOW}⚠${NC} HTML missing version badge (optional)"
    fi
fi
echo ""

echo "=============================================="
echo "📊 Test Results Summary"
echo "=============================================="
echo -e "${GREEN}Passed: $PASSED${NC}"
echo -e "${RED}Failed: $FAILED${NC}"
echo ""

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}🎉 All checks passed! System is production-ready.${NC}"
    echo ""
    echo "Next steps:"
    echo "1. Enable GitHub Pages (see GITHUB_PAGES_SETUP.md)"
    echo "2. Create your first release"
    echo "3. Integrate updater into WordPress plugin"
    exit 0
else
    echo -e "${RED}❌ Some checks failed. Please fix the issues above.${NC}"
    exit 1
fi
