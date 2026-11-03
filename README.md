# Barangay Facility & Management System (BFMS)
### Barangay Lublub (P.D. Monfort North, Dumangas, Iloilo)
**Social Services & Resident Profiling Management Platform**

---

## 🏛️ Project Architecture & Categorized File Structure

The system is organized into a clean **Front Controller & Component/Action/View Architecture**:

```
d:/xampp/htdocs/BFMS/
├── index.php                      <-- SINGLE ENTRY POINT & FRONT CONTROLLER
├── .htaccess                      <-- Clean URLs & Hostinger rewrite rules
├── install.php                    <-- 1-Click web-based MySQL setup
│
├── config/                        <-- SYSTEM CONFIGURATION
│   ├── app.php                    <-- Base URL detection, routing & helpers
│   ├── database.php               <-- MySQL 8.0 connection
│   └── db_config.php              <-- Hostinger/local generated credentials
│
├── components/                    <-- REUSABLE UI BLOCKS & PARTIALS
│   ├── header.php                 <-- HTML head, meta, styles & layout wrap
│   ├── sidebar.php                <-- Role-based dynamic navigation
│   ├── navbar.php                 <-- Top navigation & user profile dropdown
│   └── footer.php                 <-- Footer copyright & JavaScript bundles
│
├── actions/                       <-- FORM PROCESSING & BACKEND CONTROLLERS
│   ├── auth_action.php            <-- Login, logout, demo switcher handling
│   ├── resident_action.php        <-- Approve, reject, add & edit resident logic
│   ├── social_service_action.php  <-- Releasing ayuda, distributing aid
│   └── sitio_action.php           <-- Kagawad territorial jurisdiction assignment
│
├── views/                         <-- DYNAMIC PAGE VIEWS (HTML PRESENTATION)
│   ├── home.php                   <-- Public landing & resident portal front
│   ├── dashboard.php              <-- Master role-based dashboard
│   ├── sitios.php                 <-- 7 Sitios directory
│   ├── officials.php              <-- Sangguniang Barangay directory
│   ├── auth/
│   │   ├── login.php              <-- Login screen
│   │   ├── signup.php             <-- 5-step registration wizard
│   │   └── logout.php             <-- Logout handler
│   ├── residents/
│   │   ├── list.php               <-- Master resident profiling registry
│   │   ├── view.php               <-- Profile details & verification assets
│   │   ├── add.php                <-- Walk-in assisted encoder
│   │   ├── edit.php               <-- Edit profile & Household No assigner
│   │   ├── approvals.php          <-- Sitio Kagawad approval queue
│   │   └── my_profile.php         <-- Resident self-service portal
│   ├── social_services/
│   │   ├── list.php               <-- Assistance programs roster
│   │   ├── create.php             <-- Launch targeted assistance drive
│   │   ├── distribute.php         <-- Ayuda distribution desk
│   │   └── requests.php           <-- Resident assistance applications
│   ├── certificates/
│   │   └── list.php               <-- Document requests hub
│   └── reports/
│       ├── demographic_report.php <-- Vulnerability report
│       └── profiling_summary.php  <-- Visual analytics summary
│
├── prints/                        <-- DEDICATED PRINTABLE DOCUMENT TEMPLATES
│   ├── certificate_clearance.php  <-- Official Barangay Clearance
│   ├── certificate_indigency.php  <-- Free Certificate of Indigency
│   ├── certificate_residency.php  <-- Official Certificate of Residency
│   ├── resident_id_card.php       <-- Printable laminated Resident ID Card
│   └── audit_payroll.php          <-- Printable COA liquidation payroll
│
├── uploads/                       <-- SECURE ASSET STORAGE
│   ├── residents/                 <-- Live camera selfies
│   └── ids/                       <-- Government & student ID attachments
│
└── assets/                        <-- FRONTEND ASSETS
    ├── css/style.css              <-- Custom emerald & responsive theme
    └── js/main.js                 <-- Webcam capture & UI interactions
```

## 🌟 Key Features

### 1. Decentralized Sitio Approval Workflow (7 Kagawads & 7 Sitios)
* **The 7 Sitios of Barangay Lublub**:
  1. `Sitio Centro (Proper)` &bull; Assigned Kagawad: **Hon. Eduardo P. Decolongon**
  2. `Sitio Ilaya` &bull; Assigned Kagawad: **Hon. Maria Fe L. Divinagracia**
  3. `Sitio Ilawod` &bull; Assigned Kagawad: **Hon. Roberto S. Celis**
  4. `Sitio Tabucan` &bull; Assigned Kagawad: **Hon. Jocelyn D. Baldevia**
  5. `Sitio Crossing` &bull; Assigned Kagawad: **Hon. Arnel G. Dumalag**
  6. `Sitio Malipayon` &bull; Assigned Kagawad: **Hon. Salvacion T. Labordo**
  7. `Sitio Pag-asa` &bull; Assigned Kagawad: **Hon. Nelson B. Cordero**
* **Approval Queue**: When a resident signs up and selects their Sitio, the registration automatically enters the **Approval Queue of the Kagawad assigned to that Sitio**.
* Kagawads inspect resident identity, address, and vulnerability claims before granting verified resident status.
* **Barangay Captain (Admin)** and **Secretary** hold executive oversight and can review across all 7 Sitios.
### 1. Centralized Executive Approvals & Separation of Roles
* **Centralized Chart of Authority & Multi-Tier Approvals**:
  - `Order 0`: Draft / Pending Preparation
  - `Order 1-3`: Multi-Stage Administrative Review & Verification
  - `Code 777`: Official Executive Approval (Punong Barangay)
  - `Code 555`: Rejected / Disapproved
* **Role Hierarchy**:
  1. **Punong Barangay (Captain)**: Executive Dashboard, Centralized Approvals Hub (Authorization Code 777), and Executive Reports (Ayuda, Programs, Masterlist, Issued Certificates).
  2. **Barangay Secretary**: Resident signup verification, assistance programs encoding, registry records, and document certification.
  3. **Barangay Treasurer**: Financial Dashboard, Ayuda Distribution Desk (strictly for Treasurer), and Disbursement / Liquidation Reports.
  4. **Barangay Health Workers (BHW)**: Field documentation, household survey proof, and resident health profiling strictly within their assigned Sitio.
  5. **Residents**: View verified profiles, inspect posted ayuda programs, and access electronic credentials.

### 2. Multi-Sector Social Welfare Profiling (Main Purpose)
* **Persons with Disability (PWD)**: Disability classification (Mobility, Visual, Hearing, Speech, Psychosocial), PWD ID registry.
* **Solo Mothers / Solo Parents**: Number of dependent children, Solo Parent ID registry.
* **Indigent / Low-Income Families**: Monthly income tracking, livelihood source verification.
* **Senior Citizens (60+ yrs)**: OSCA ID tracking and automatic age calculation.
* **4Ps Beneficiaries**: DSWD 4Ps Household ID registry.
* **Registered Voters**: Precinct assignment in Barangay Lublub.
* **Health & Medical**: Blood type, chronic health conditions, PhilHealth ID, emergency contact info.

### 3. Social Services & Ayuda Releasing Desk
* **Assistance Programs Management**: Create cash subsidies, rice and grocery relief packs, medical aid, and calamity assistance.
* **Automated Demographic Targeting**: Automatically enroll all PWDs, Solo Mothers, Seniors, or Indigents with 1 click based on profiling tags.
* **Real-time Ayuda Distribution Desk**: Track claimed vs. unclaimed relief with remarks and timestamp.
* **Printable COA Audit Payroll**: Generate official disbursement rosters with resident signature lines for government liquidation.
* **Resident Assistance Applications**: Online application for Medical Aid, Burial Assistance, and Emergency Cash.

### 4. Official Barangay Document Issuance (Printable)
* **Certificate of Indigency**: Free document for hospitals, PhilHealth, PAO, and DSWD assistance.
* **Barangay Clearance**: For employment and local requirements.
* **Certificate of Residency**: Official proof of residency in Barangay Lublub.
* **Digital Resident ID Card**: Official ID card with front and back layout, emergency numbers, and Punong Barangay signature.

---

## 🔑 Default Demo User Accounts
All accounts share the default password: **`password123`**
*(A 1-Click Quick Demo Switcher is also provided on the login page for testing!)*

| Role | Username | Assigned Sitio | Name |
| Role | Username | Assigned Scope | Name |
| :--- | :--- | :--- | :--- |
| **Barangay Captain (Admin)** | `captain` | All Sitios | Hon. Rafael M. Monfort |
| **Barangay Secretary** | `secretary` | All Sitios | Ma. Theresa B. Alipao |
| **Kagawad 1** | `kagawad_centro` | Sitio Centro (Proper) | Hon. Eduardo P. Decolongon |
| **Kagawad 2** | `kagawad_ilaya` | Sitio Ilaya | Hon. Maria Fe L. Divinagracia |
| **Kagawad 3** | `kagawad_ilawod` | Sitio Ilawod | Hon. Roberto S. Celis |
| **Kagawad 4** | `kagawad_tabucan` | Sitio Tabucan | Hon. Jocelyn D. Baldevia |
| **Kagawad 5** | `kagawad_crossing` | Sitio Crossing | Hon. Arnel G. Dumalag |
| **Kagawad 6** | `kagawad_malipayon` | Sitio Malipayon | Hon. Salvacion T. Labordo |
| **Kagawad 7** | `kagawad_pagasa` | Sitio Pag-asa | Hon. Nelson B. Cordero |
| **Barangay Captain (Admin)** | `captain` | All Sitios (Executive Approver) | Hon. Eliseo Rodriguez |
| **Barangay Secretary** | `secretary` | All Sitios (Verification & Registry) | Ms. Annielor Gulbique |
| **Barangay Treasurer** | `treasurer` | All Sitios (Ayuda Distribution & Payroll) | Ms. Joy Valiente |
| **BHW (Proper)** | `bhw_proper` | Sitio Proper (Field Profiling) | BHW Assigned Officer |
| **BHW (Centro)** | `bhw_centro` | Sitio Centro (Field Profiling) | BHW Assigned Officer |
| **Resident (PWD)** | `maricel_pwd` | Sitio Centro | Maricel Soriano Dable |
| **Resident (Solo Mother)** | `gloria_solo` | Sitio Ilaya | Gloria Ramirez Perez |
| **Resident (Pending Test)** | `ramon_pending` | Sitio Ilawod | Ramon Bautista Castro *(Awaiting Kagawad Celis Approval)* |
| **Resident (Pending Test)** | `ramon_pending` | Sitio Weapons | Ramon Bautista Castro *(Awaiting Secretary Verification)* |

---

## 🚀 How to Run Locally

### Option A: Running via Terminal Built-in PHP Server (`php -S`)
1. Open your terminal / command prompt and navigate to the project directory:
   ```bash
   cd d:\xampp\htdocs\BFMS
   ```
2. Start the PHP server:
   ```bash
   php -S 127.0.0.1:8000
   ```
3. Open your browser:
   ```
   http://127.0.0.1:8000/
   ```
*(The system automatically detects that it is running via terminal server and sets the base routing to root `""` without any manual configuration!)*

### Option B: Running via XAMPP Apache
1. Access directly through Apache:
   ```
   http://localhost/BFMS/
   ```
*(The system automatically detects Apache and sets `BASE_URL` to `"/BFMS"`)*

---

## ☁️ How to Deploy to Hostinger

The project is pre-configured for Hostinger Shared Hosting / Cloud Hosting.

### Step 1: Upload Files
1. In your **Hostinger hPanel**, open **File Manager**.
2. Navigate to `public_html` (or create a subfolder if desired, e.g. `public_html/bfms`).
3. Upload and extract the project files (`index.php`, `config/`, `modules/`, `assets/`, `includes/`, `database/`, `.htaccess`, `install.php`).

### Step 2: Create MySQL Database in Hostinger
1. In Hostinger hPanel, go to **Databases &rarr; Management &rarr; Create a New MySQL Database and Database User**.
2. Note down your:
   * **Database Name** (e.g., `u123456789_lublub`)
   * **Database Username** (e.g., `u123456789_admin`)
   * **Database Password**
   * **Database Host** (`localhost` on Hostinger)

### Step 3: Run the 1-Click Web Setup Wizard
1. Open your browser and navigate to:
   ```
   https://yourdomain.com/install.php
   ```
   *(or `https://yourdomain.com/bfms/install.php` if placed in a subfolder)*
2. Enter your Hostinger Database Host (`localhost`), Database Name, Username, and Password.
3. Click **"Test Connection & Install Database"**.
4. The setup wizard will automatically:
   * Connect to your Hostinger database.
   * Import all tables and seed data (`schema.sql`).
   * Automatically generate and save `config/db_config.php`.
5. You can now immediately use the system on Hostinger!
#   b f m s  
 