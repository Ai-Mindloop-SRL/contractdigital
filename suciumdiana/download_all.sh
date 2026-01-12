#!/bin/bash
FILES=(
    "ContractPDF.php"
    "INSTALLATION.md"
    "README.md"
    "auth_check.php"
    "contract_detail.php"
    "create_template.php"
    "download_pdf.php"
    "edit_template.php"
    "fill_and_sign.php"
    "index.php"
    "login.php"
    "logout.php"
    "send_contract.php"
    "sign_contract.php"
    "status.php"
)

for file in "${FILES[@]}"; do
    echo "Downloading $file..."
    curl -s "ftp://ftp.siteq.ro/suciumdiana/$file" --user claude_ai@siteq.ro:igkcwismekdgqndp > "$file"
done
echo "✅ All files downloaded"
