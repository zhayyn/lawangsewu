#!/bin/bash
# SIPP-HUB Three-Request Implementation - Quick Command Reference
# This file documents all commands needed to complete the three enhancements

cat << 'EOF'

╔══════════════════════════════════════════════════════════════════════════════╗
║                                                                              ║
║            SIPP-HUB Monthly Reporting & Dashboard Enhancement                ║
║            Three-Request Implementation - Command Reference                  ║
║                                                                              ║
╚══════════════════════════════════════════════════════════════════════════════╝

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
REQUEST #1: Extract & Auto-Correct Alamat Penggugat/Tergugat
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

📋 Description:
  Extract kelurahan (sub-district) names from long address text and auto-correct
  spelling variations. Target: 150+ kelurahan across Semarang districts.

📁 Primary Files:
  - /var/www/html/lawangsewu/scripts/extract-alamat-kelurahan.py
  - Data source: SIPP keterangan field (full address text)
  - Target fields: alamat_penggugat, alamat_tergugat

🔧 Setup:
  # 1. Authenticate with Google Cloud
     gcloud auth login
     gcloud config set project lawang-sewu-490507

  # 2. Test BigQuery connection
     bq query "SELECT COUNT(*) FROM \`lawang-sewu-490507.sipp_dataset.sipp_hub_perkara\`"

🚀 Usage:

  # Test: Preview extraction on sample data (no database changes)
     cd /var/www/html/lawangsewu
     python3 scripts/extract-alamat-kelurahan.py

  # Apply: Generate and execute UPDATE statements
     python3 scripts/extract-alamat-kelurahan.py --apply

  # Verify: Check extraction results
     bq query "SELECT COUNT(*) FROM \`lawang-sewu-490507.sipp_dataset.sipp_hub_perkara\`
               WHERE alamat_penggugat IS NOT NULL"

📊 Expected Results:
  - Extract ~500-1000 kelurahan variations
  - Map to 30+ correction patterns
  - Populate alamat fields for historical records
  - Auto-correct common spelling errors

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
REQUEST #2: Make Dashboard Links Production-Ready
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

📋 Description:
  Display production domain links (lawangsewu.pa-semarang.go.id) instead of
  localhost (127.0.0.1). Links include embeddable iframes and direct access.

📁 Primary Files:
  - /var/www/html/lawangsewu/docs/sipp-hub-pipeline-hub.html (Embed Kit tab)
  - Links table sections: Tautan Langsung, GCP, Detail Charts, Laporan Bulanan
  - Base URL: https://lawangsewu.pa-semarang.go.id/docs/

🔧 Setup:
  # 1. Verify production domain is accessible
     curl -I https://lawangsewu.pa-semarang.go.id/docs/sipp-hub-pipeline-hub.html

  # 2. Check web server (Apache/Nginx) is serving /var/www/html/lawangsewu
     ls -l /var/www/html/lawangsewu/docs/

🚀 Usage:

  # Test: Open dashboard in browser
     https://lawangsewu.pa-semarang.go.id/docs/sipp-hub-pipeline-hub.html

  # Navigate to: 4th tab = "🔗 Embed Kit"

  # Access different sections:
     - Tautan Langsung: Direct file links with copy button
     - Link GCP Siap Pakai: Full URLs for embedding
     - Link Detail Chart + Filter: Pre-configured filter links
     - 📄 Laporan Bulanan: Monthly PDF report downloads

📋 Available Links:
  Dashboard Hub     → https://lawangsewu.pa-semarang.go.id/docs/sipp-hub-pipeline-hub.html
  Detail Monitoring → https://lawangsewu.pa-semarang.go.id/docs/sipp-hub-detail-monitoring.html
  Detail Charts     → https://lawangsewu.pa-semarang.go.id/docs/sipp-hub-detail-charts.html

️🎯 Features Enabled:
  ✅ Copy-to-clipboard for all links
  ✅ One-click open in new tab
  ✅ Iframe embed code ready
  ✅ Pre-filtered dashboard links
  ✅ Monthly report download section

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
REQUEST #3: Monthly Automated Report Generation
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

📋 Description:
  Generate monthly PDF reports with case statistics and email delivery to
  dbprakom@gmail.com or provide web download link.

📁 Primary Files:
  - /var/www/html/lawangsewu/scripts/generate-monthly-report.py (main script)
  - /var/www/html/lawangsewu/scripts/setup-monthly-report-cron.sh (setup guide)
  - /var/www/html/lawangsewu/reports/README.md (documentation)
  - /var/www/html/lawangsewu/reports/ (output directory)

🔧 Setup:

  # 1. Install PDF generation library
     apt-get update && apt-get install -y python3-reportlab

  # 2. Create reports directory (should exist)
     mkdir -p /var/www/html/lawangsewu/reports
     chmod 755 /var/www/html/lawangsewu/reports

  # 3. [Optional] Configure SMTP for email delivery
     # Set these environment variables in cron or .env:
     export SENDER_EMAIL=noreply@pa-semarang.go.id
     export SENDER_PASSWORD='your_secure_password'
     export SMTP_SERVER=smtp.gmail.com
     export SMTP_PORT=587

🚀 Manual Report Generation:

  # Test: Generate current month (PDF only)
     cd /var/www/html/lawangsewu
     python3 scripts/generate-monthly-report.py --pdf-only

  # Specific month
     python3 scripts/generate-monthly-report.py --month 2025-01 --pdf-only

  # Generate and send email
     python3 scripts/generate-monthly-report.py \
       --month 2025-01 \
       --email dbprakom@gmail.com

  # Custom output location
     python3 scripts/generate-monthly-report.py \
       --month 2025-01 \
       --output /path/to/my-report.pdf

⏰ Automated Scheduling (Cron):

  Option 1 - END OF MONTH (Recommended - last day, 6 PM)
  ─────────────────────────────────────────────────────
  crontab -e
  # Add this line:
  0 18 28-31 * * [ $(date -d tomorrow +\%d) -eq 1 ] && \ 
      python3 /var/www/html/lawangsewu/scripts/generate-monthly-report.py \
      --month $(date +\%Y-\%m) --email dbprakom@gmail.com

  Option 2 - FIRST DAY OF MONTH (1 AM, generates previous month)
  ──────────────────────────────────────────────────────────────
  crontab -e
  # Add this line:
  0 1 1 * * python3 /var/www/html/lawangsewu/scripts/generate-monthly-report.py \
      --month $(date -d "last month" +\%Y-\%m) --email dbprakom@gmail.com

  Option 3 - MID-MONTH (15th, 1 AM, generates previous month)
  ───────────────────────────────────────────────────────────
  crontab -e
  # Add this line:
  0 1 15 * * python3 /var/www/html/lawangsewu/scripts/generate-monthly-report.py \
      --month $(date -d "last month" +\%Y-\%m) --email dbprakom@gmail.com

📊 Report Contents:

  Each PDF includes:
  ✅ Perkara Masuk (Cases filed) - count & timeline
  ✅ Perkara Putus (Cases resolved) - count & timeline
  ✅ Perkara Dalam Proses (Pending) - count
  ✅ Jenis Perkara Distribution (15 top case types)
  ✅ Status Putusan Distribution (Verdict types)
  ✅ Per Kecamatan Distribution (20 top districts)
  ✅ Generated timestamp & data source reference

📥 Report Access:

  # Web Browser Download
     https://lawangsewu.pa-semarang.go.id/reports/Monthly-Report-YYYY-MM.pdf
     
     Example: 
     https://lawangsewu.pa-semarang.go.id/reports/Monthly-Report-2025-01.pdf
     https://lawangsewu.pa-semarang.go.id/reports/Monthly-Report-2024-12.pdf

  # Dashboard Access
     - Open: https://lawangsewu.pa-semarang.go.id/docs/sipp-hub-pipeline-hub.html
     - Tab: 🔗 Embed Kit (4th tab)
     - Section: 📄 Laporan Bulanan
     - Shows links to last 12 months

  # Email Delivery
     - Subject: "Laporan Perkara Bulanan SIPP-HUB - YYYY-MM"
     - Recipient: dbprakom@gmail.com (configurable)
     - Attachment: Monthly-Report-YYYY-MM.pdf
     - Body: Summary & instruction to check dashboard

  # Command Line
     ls -lh /var/www/html/lawangsewu/reports/Monthly-Report-*.pdf

🔧 Verification & Testing:

  # Test BigQuery connection
    bq query "SELECT COUNT(*) FROM \`lawang-sewu-490507.sipp_dataset.sipp_hub_perkara\`"

  # Test PDF generation
    python3 scripts/generate-monthly-report.py --month 2025-01 --pdf-only
    ls -lh reports/Monthly-Report-2025-01.pdf

  # Test SMTP email (optional)
    python3 -c "
import smtplib
server = smtplib.SMTP('smtp.gmail.com', 587)
server.starttls()
print('✅ SMTP connection OK')
server.quit()
    "

  # Check cron logs
    sudo grep CRON /var/log/syslog | tail -20

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
TROUBLESHOOTING
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

❌ BigQuery Error: "Failed to authenticate with Google Cloud"
   → gcloud auth login
   → gcloud config set project lawang-sewu-490507
   → Verify service account has BigQuery permissions

❌ SMTP Error: "Connection refused"
   → Check SENDER_PASSWORD is set
   → Verify SMTP_SERVER and SMTP_PORT are correct
   → Try different port: 25 (unencrypted) or 587 (TLS)

❌ PDF Error: "reportlab not installed"
   → apt-get install python3-reportlab

❌ Cron not running
   → systemctl restart cron
   → crontab -l (verify entry exists)
   → sudo grep CRON /var/log/syslog (check logs)

❌ Reports not appearing in dashboard
   → chmod 755 /var/www/html/lawangsewu/reports
   → Verify GCP_PUBLIC_BASE in HTML points to correct domain
   → Check browser console for CORS errors

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
MONITORING & LOGS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

# Check report generation history
  ls -lh /var/www/html/lawangsewu/reports/ | tail -10

# Check recently generated reports
  find /var/www/html/lawangsewu/reports/ -mtime -7 -type f

# Monitor cron execution
  sudo journalctl -u cron --since today

# Check web server logs
  tail -f /var/log/apache2/access.log | grep "Monthly-Report"
  tail -f /var/log/apache2/error.log

# Monitor SMTP delivery
  tail -f /var/log/mail.log

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SUMMARY - QUICK CHECKLIST
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

REQUEST #1: Alamat Extraction
  ☐ gcloud auth login
  ☐ python3 scripts/extract-alamat-kelurahan.py (test)
  ☐ python3 scripts/extract-alamat-kelurahan.py --apply (when ready)

REQUEST #2: Production Links
  ☐ Open dashboard: https://lawangsewu.pa-semarang.go.id/docs/sipp-hub-pipeline-hub.html
  ☐ Navigate to "🔗 Embed Kit" tab
  ☐ Verify links appear in all 4 sections
  ☐ Test copy-to-clipboard functionality
  ☐ Verify links open in new tab

REQUEST #3: Monthly Reports
  ☐ apt-get install python3-reportlab
  ☐ mkdir -p /var/www/html/lawangsewu/reports
  ☐ python3 scripts/generate-monthly-report.py --pdf-only (test)
  ☐ [Optional] Set SENDER_EMAIL, SENDER_PASSWORD, SMTP env vars
  ☐ python3 scripts/generate-monthly-report.py --email dbprakom@gmail.com
  ☐ crontab -e (add one of 3 cron patterns)
  ☐ Test: Verify cron runs at scheduled time

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

📞 For detailed information, refer to:
   - /var/www/html/lawangsewu/ENHANCEMENT-SUMMARY-3REQUESTS.md
   - /var/www/html/lawangsewu/reports/README.md
   - /var/www/html/lawangsewu/scripts/generate-monthly-report.py (--help)

Last Updated: 2025-03-18
Version: 1.0

EOF
