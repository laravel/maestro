#!/bin/bash

RED='\033[0;31m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BUILD_DIR="$ROOT_DIR/build"

if [ ! -d "$BUILD_DIR" ]; then
    echo -e "${RED}The build folder does not exist. Please run 'php artisan build' first.${NC}"
    exit 1
fi

cd "$BUILD_DIR" || exit 1

echo -e "${BLUE}Running composer setup...${NC}"
composer setup

if [ -f ".env" ]; then
    sed -i 's|APP_URL=http://localhost|APP_URL=http://localhost:8000|g' .env
fi

echo -e "${GREEN}Starting development server...${NC}"
composer dev
