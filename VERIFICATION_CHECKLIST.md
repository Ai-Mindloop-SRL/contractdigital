# ✅ Verification Checklist - Before GitHub Push

Run these checks **before** pushing to GitHub:

## 🔍 **1. No Sensitive Files**

```bash
# Should return NOTHING:
find . -name "*.log" -o -name "*password*" -o -name "database.php" -o -name "email.php" | grep -v ".example"

# Should be EMPTY:
ls uploads/contracts/*.pdf 2>/dev/null
ls uploads/signatures/*.png 2>/dev/null | grep -v "signature.png"
```

## 🔍 **2. .gitignore Is Present**

```bash
cat .gitignore | grep -E "database.php|email.php|uploads.*pdf"
# Should show: config/database.php, config/email.php, uploads/contracts/*.pdf
```

## 🔍 **3. .example Files Exist**

```bash
ls config/*.example.php
# Should show: database.example.php, email.example.php, security.example.php
```

## 🔍 **4. Documentation Files Present**

```bash
ls -1 *.md
# Should show: README.md, CHANGELOG.md, DEPLOYMENT.md
```

## 🔍 **5. No Personal Data**

```bash
# Search for email addresses (should find only noreply@ and examples)
grep -r "@" config/*.example.php README.md

# Search for phone numbers (should find only examples)
grep -rE "[0-9]{10}" mindloop/ roseupadvisors/

# Search for CNP patterns (should find none)
grep -rE "[0-9]{13}" mindloop/ roseupadvisors/
```

## ✅ **All Checks Passed?**

If all checks above return NO sensitive data, you're ready to push!

```bash
git init
git add .
git commit -m "Initial commit: ContractDigital Platform v1.2.0"
git remote add origin https://github.com/Ai-Mindloop-SRL/contractdigital.git
git branch -M main
git push -u origin main
```

