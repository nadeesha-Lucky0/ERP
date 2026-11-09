# EGL Enterprise FIFO ERP & Inventory Management System

An Enterprise FIFO (First-In, First-Out) Inventory Management and Store Operations Platform designed for Electricity Generation Lanka (EGL) and power stations (Victoria, Kotmale, Randenigala, Rantembe, Ukuwela, Bowatenna). Built with pure PHP 8.2, MySQL/MariaDB, and a responsive Desk interface inspired by Frappe / ERPNext.

---

## 🚀 Key Modules & Capabilities

### 1. FIFO Inventory Engine & Stock Valuation
- **Strict FIFO Costing**: Automatically issues materials from the oldest active stock batches first.
- **Batch Tracking**: Unique batch tracking with unit cost, remaining quantity, expiry dates, supplier details, and warehouse bins (`Bin A-01` to `Bin E-20`).
- **Comprehensive Stock Ledger**: Real-time audit trail of all receipts, issues, adjustments, and revaluations.

### 2. Physical Stock Verification (Board of Survey & AV 7)
- **Freeze / Unfreeze Protocol**: Lock batches during verification audits to prevent unauthorized stock movement.
- **Statutory Count Sheets**: Generates official count sheets strictly chunked into **21 items per sheet** conforming to CEB/EGL store verification standards.
- **Multi-Sheet ZIP Export**: Instant download of all verification sheets as vector PDFs bundled into a single ZIP archive along with a verification index CSV and auditor instructions.
- **Tri-Party Endorsements**: Designated signature verification blocks for Verifiers, Stores Officers, and Board of Survey Auditors.
- **Discrepancy & GL Posting**: Automatic surplus/deficit calculations and reconciliation GL journal entries.

### 3. Store Transactions & Material Flow
- **Good Receipt Notes (GRN)**: Multi-item receipt vouchers with automatic batch creation and bin allocation.
- **Material Issue Notes**: Issues with real-time FIFO allocation, cost center tagging, and stock balance validation.
- **CPC Fuel & Diesel Management**: Separate dedicated tracking for bulk fuel depot receipts, issues, and cost tracking.

### 4. Reports & Analytics
- **Average Consumption Report**: Multi-month consumption trends, safety stock, and reorder point alerts.
- **Form AV 7 Stores Summary**: Surplus and deficit valuation reports according to Sri Lanka Public Stores Regulations.
- **GL Transfer & Revaluation Previews**: Preview journal vouchers before posting to the general ledger.

---

## 📁 Repository Structure

```text
ERP/
├── FIFOERP/                     # Core Web Application
│   ├── api/                     # REST JSON & Export APIs (phv.php, inventory.php, etc.)
│   ├── app/                     # MVC Backend Application
│   │   ├── config/              # Database and App Configuration
│   │   ├── core/                # Auth, Database PDO, FIFO Engine, Response Handlers
│   │   └── helpers/             # Icon Registry, PhvSheetPdf Generator
│   ├── assets/                  # CSS Styles, JavaScript Desk SPA, Icons & Images
│   │   ├── css/erp-desk.css     # Clean ERPNext Desk Theme & Print Media Styles
│   │   ├── js/erp-desk.js       # SPA Controller, Sheet Pager, Modal Managers
│   │   └── images/              # Official EGL Branding Assets
│   ├── database/                # Database Schemas and Seed Scripts
│   │   └── fifonew.sql          # Full MySQL Schema and Seed Data
│   ├── views/                   # UI Templates, Layouts, and Components
│   ├── index.php                # Main Single-Page Desk Interface
│   ├── login.php                # Authentication Screen
│   ├── logout.php               # Session Logout
│   └── setup_database.php       # Automatic Database Setup Script
└── frappe_docker/               # Frappe/ERPNext Docker Deployment Configurations
```

---

## 🛠️ Setup & Installation

### Requirements
- **PHP**: 8.1 or higher (with `pdo_mysql`, `zip`, `mbstring` extensions enabled)
- **Database**: MySQL 5.7+ or MariaDB 10.4+
- **Web Server**: Apache 2.4+ (XAMPP recommended on Windows)

### Quick Start
1. Clone the repository into your web server directory (e.g. `C:\xampp\htdocs\FIFOERP` or link from `ERP/FIFOERP`):
   ```bash
   git clone https://github.com/nadeesha-Lucky0/ERP.git
   ```

2. Configure Database:
   - Create a database named `fifonew` in phpMyAdmin or MySQL CLI:
     ```sql
     CREATE DATABASE fifonew CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
     ```
   - Import the database schema from `FIFOERP/database/fifonew.sql`.

3. Configure Database Connection:
   - Check `FIFOERP/app/config/database.php` for database credentials (default: host `localhost`, user `root`, no password).

4. Access the Application:
   - Open your browser at `http://localhost/FIFOERP/`
   - Default login: `admin` / `admin123`

---

## 📜 License
Developed for EGL (Electricity Generation Lanka) Power Stations Management. All rights reserved.
