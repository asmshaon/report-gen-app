# Abu Report Generator Application

A stock market research report generation tool that creates professional HTML, PDF, and interactive flipbook reports with real-time TradingView charts.

---

## Tech Stack

| Technology | Version | Purpose | Why Chosen | Link |
|------------|---------|---------|------------|------|
| **PHP** | 5.5+ | Backend Logic | Lightweight, runs on virtually any hosting, no complex setup needed | [php.net](https://www.php.net/) |
| **mPDF** | 6.x / 7.1.9 | PDF Generation | Converts HTML to PDF, supports CSS, images, and complex layouts | [mpdf.github.io](https://mpdf.github.io/) |
| **Bootstrap** | 4.3.1 | UI Framework | Responsive design, pre-built components, cross-browser compatible | [getbootstrap.com](https://getbootstrap.com/) |
| **Alpine.js** | 3.x | Frontend Interactivity | Lightweight (15KB), no build step, easy to embed | [alpinejs.dev](https://alpinejs.dev/) |
| **jQuery** | 1.11.0 | DOM Manipulation | Required by turn.js flipbook library | [jquery.com](https://jquery.com/) |
| **turn.js** | - | Flipbook Effect | Creates page-flipping animation for reports | [turnjs.com](http://turnjs.com/) |
| **TradingView Widgets** | - | Stock Charts | Real-time, interactive financial charts | [tradingview.com/widget](https://www.tradingview.com/widget/) |

---

## Requirements

### Server Requirements
- **PHP 5.5 or higher**
- **PHP Extensions:**
  - `zip` - For PDF compression
  - `intl` - For internationalization
  - `mbstring` - For string handling
  - `gd` - For image processing

### File Permissions
- Writable `reports/` directory for generated reports
- Writable `db/` directory for configurations
- Writable `logs/` directory for application logs

### Optional (Docker)
- Docker Engine 20.10+
- Docker Compose 1.29+

---

## Installation

### Option 1: Traditional Hosting (Recommended)

This application can be deployed to **root domain** (e.g., `https://example.com/`) or a **subfolder** (e.g., `https://example.com/reports/`).

#### Step 1: Upload Files

**To Root Directory:**
```bash
# Upload all project files to your server's public root
# Example: /var/www/html/ or /home/user/public_html/
```

**To Subfolder:**
```bash
# Upload all project files to a subdirectory
# Example: /var/www/html/reports/ or /home/user/public_html/reports/
```

You can upload via:
- FTP/SFTP client (FileZilla, WinSCP, Cyberduck)
- cPanel File Manager

#### Step 2: Install Dependencies

#### Step 3: Set File Permissions

```bash
# Make directories writable
chmod 755 reports db logs
chmod 644 reports/* db/* logs/* 2>/dev/null || true
```

#### Step 4: Access the Application

- **Root deployment:** `https://your-domain.com/report_manager.php`
- **Subfolder deployment:** `https://your-domain.com/subfolder/report_manager.php`

---

### Option 2: Docker Setup (Optional)

For local development or environments with Docker.

```bash
# Build and start the container
docker-compose up -d

# Access the application
http://localhost:8081/report_manager.php

# Stop the container
docker-compose down
```

---

## Usage

### Creating a Report Configuration

1. Navigate to `report_manager.php`
2. Fill in the form:
   - **Report File Name**: A unique identifier for this report
   - **Upload Article Image**: Upload an image to be displayed in the report
   - **PDF Cover Image**: Upload an image to be displayed as the cover page of the PDF
3. Click **Save Report Configuration**
4. Report configurations are saved in the `db/report_settings.json` file

### Generating Reports

Reports can be generated in two ways:

**Option 1: Generate Directly from Form**
1. Navigate to `report_manager.php`
2. Fill in the form fields with your desired data (no need to save)
3. Choose report type: [optional]
   - **HTML** - Web page with embedded charts
   - **PDF** - Downloadable PDF document
   - **Flipbook** - Interactive page-flipping book
4. Click **"Generate Report"** button to generate the report
5. Reports are saved in the `reports` folder

**Option 2: Generate from Saved Configuration**
1. Click the edit icon next to any saved configuration
2. Modify the form values if needed (or use as-is)
3. Choose report format: [optional]
   - **HTML** - Web page with embedded charts
   - **PDF** - Downloadable PDF document
   - **Flipbook** - Interactive page-flipping book
4. Click **Generate Report**
5. Reports are saved in the `reports/` directory

### Managing Configurations

- **Edit**: Click the edit icon next to any configuration
- **Delete**: Click the delete icon to remove a configuration
- **Upload Manual PDF**: Use the uploader to add pre-made PDFs

---

## Directory Structure

```
awesome-report-gen-app/
├── app/
│   ├── api/                    # API endpoints
│   │   ├── generate_reports.php
│   │   ├── post_configuration.php
│   │   ├── get_configurations.php
│   │   ├── delete_configuration.php
│   │   └── upload_manual_pdf.php
│   └── services/
│       └── ReportGeneratorService.php   # Core report generation logic
├── db/
│   ├── data.csv               # Stock data source
│   └── report_settings.json   # Saved configurations
├── public/
│   ├── css/styles.css         # Application styles
│   └── js/api.js              # Frontend JavaScript
├── partials/                  # HTML partials
│   ├── config-list.php
│   ├── filter.php
│   ├── form-manager.php
│   └── manual-pdf-uploader.php
├── reports/                   # Generated reports (writable)
├── logs/                      # Application logs (writable)
├── images/                    # Image assets
├── index.php                  # Landing page
├── report_manager.php         # Main application
├── composer.json              # PHP dependencies
├── Dockerfile                 # Docker configuration (optional)
└── docker-compose.yml         # Docker compose setup (optional)
```

---

## Troubleshooting

### PDF Generation Fails

**Issue:** Reports generate but PDF creation fails

**Solutions:**
1. Check file permissions: `chmod 755 reports/`
2. Increase PHP memory limit in `php.ini`: `memory_limit = 256M`

### Charts Not Displaying

**Issue:** TradingView charts don't appear in reports

**Solutions:**
1. Check internet connection (charts load from TradingView CDN)
2. Verify ticker symbol is valid
3. Check browser console for JavaScript errors

### Permission Errors

**Issue:** Cannot save configurations or reports

**Solution:**
```bash
chmod 755 reports db logs
chown www-data:www-data reports db logs  # For Apache/Nginx
```

---

## Support

For issues or questions, please contact the with me.
