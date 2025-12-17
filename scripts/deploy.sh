#!/bin/bash
# ContractDigital Platform - Deployment Script
# Usage: ./deploy.sh [mindloop|roseupadvisors|all]

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# FTP Configuration
FTP_HOST="ftp.siteq.ro"
FTP_USER="claude_ai@siteq.ro"
FTP_PASS="igkcwismekdgqndp"
FTP_REMOTE_DIR=""

# Function to print colored output
print_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to deploy a project
deploy_project() {
    local project=$1
    print_info "Deploying ${project}..."
    
    # Upload files via FTP
    lftp -c "
    set ftp:ssl-allow no;
    open -u ${FTP_USER},${FTP_PASS} ${FTP_HOST};
    mirror -R --verbose --delete --exclude .git/ --exclude .gitignore ${project}/ ${project}/;
    bye
    "
    
    if [ $? -eq 0 ]; then
        print_info "${project} deployed successfully!"
    else
        print_error "Failed to deploy ${project}"
        exit 1
    fi
}

# Main deployment logic
case "$1" in
    mindloop)
        deploy_project "mindloop"
        ;;
    roseupadvisors)
        deploy_project "roseupadvisors"
        ;;
    all)
        print_info "Deploying all projects..."
        deploy_project "mindloop"
        deploy_project "roseupadvisors"
        deploy_project "includes"
        print_info "All projects deployed successfully!"
        ;;
    *)
        print_error "Usage: $0 {mindloop|roseupadvisors|all}"
        exit 1
        ;;
esac

print_info "Deployment completed!"
