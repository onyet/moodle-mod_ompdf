<div align="center">
  <img src="pix/ompdf-logo.png" alt="OMPDF Logo" width="200">
  <h1>OMPDF – Onyet Moodle PDF Viewer</h1>
  <p>A powerful Moodle activity plugin for delivering PDFs with Smart Notes, Analytics, and Enterprise DRM protection.</p>

  <a href="https://github.com/onyet/moodle-mod_ompdf/releases/latest"><img src="https://img.shields.io/github/v/release/onyet/moodle-mod_ompdf?label=release&color=blue" alt="Latest Release"></a>
  <a href="https://moodle.org"><img src="https://img.shields.io/badge/Moodle-4.1%2B-orange" alt="Moodle 4.1+"></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-GPL%20v3-green" alt="License: GPL v3"></a>
</div>

---

## ✨ Features

### 📄 PDF Viewer
- Renders PDFs directly in the browser via **[PDF.js](https://github.com/mozilla/pdf.js) 3.11.174**
- Works on desktop and mobile without any browser plugin
- Open PDF in current tab or new tab — configurable per activity
- Show folder contents inline on the course page or on a separate page
- Support for sub-folders (expandable/collapsible)
- Optional per-file download links as fallback for unsupported devices

### 🔒 Enterprise DRM Protection
- Per-activity toggle to **disable print, download, copy, and text selection**
- Hides toolbar buttons and removes context menu when DRM is enabled
- Applies access checks and protected file delivery; browser-side DRM options are not a substitute for server-side security

### 📝 Smart Notes & Hints
- Teachers can add **per-page hints** visible to all students
- Students can add **private notes** per page with color labels
- Notification badge on the toolbar shows note count for the current page
- Collapsible side drawer with card-style note display

### 📊 Student Engagement Analytics
- Tracks **time-on-page** per student per PDF
- Interactive **activity heatmap** showing reading patterns
- Per-student and per-PDF progress summary for teachers
- Dashboard accessible from the activity view

### 🌙 Dark / Night Mode
- One-click toggle between light and dark theme
- Preference persisted in `localStorage`

### 🌍 Languages
- English (`en`) and Indonesian (`id`) are maintained in the repository.
- Additional starter translations are included for Simplified Chinese (`zh_cn`), Arabic (`ar`), German (`de`), Dutch (`nl`), and Japanese (`ja`).
- Missing translations fall back to English, as supported by Moodle's language system.
- Arabic uses Moodle's RTL page direction and logical CSS properties for the OMPDF layout.

### 🧩 Moodle integration
- Supports Moodle activity backup and restore.
- Implements the null Privacy API provider because OMPDF does not export personal data.
- Uses Moodle templates, Output API, AMD modules, and a registered External Service for activity actions.
- State-changing actions require authenticated access, the module capability, and a valid sesskey.

---

## 🖥️ Requirements

| Requirement | Version |
|-------------|---------|
| **Moodle** | 4.1 LTS or later |
| **PHP** | 7.4 or later |
| **Browser** | Any modern browser (Chrome, Firefox, Safari, Edge) |

> ⚠️ **Not supported:** Internet Explorer and Moodle 3.x or below. The bundled PDF.js distribution is declared in [`thirdpartylibs.xml`](thirdpartylibs.xml).

---

## 📦 Installation

### Option 1 — Upload ZIP via Moodle Admin (Recommended)
1. Download the latest `ompdf_vX.X.X.zip` from the [Releases page](https://github.com/onyet/moodle-mod_ompdf/releases/latest)
2. Go to **Site Administration → Plugins → Install plugins**
3. Upload the ZIP and follow the on-screen instructions

### Option 2 — Manual Install
1. Download and extract the ZIP
2. Copy the `ompdf` folder to `{moodle_root}/mod/`
3. Go to **Site Administration → Notifications** to trigger the DB upgrade

### Post-Installation
Configure default settings at:
**Site Administration → Plugins → Activity Modules → OMPDF**

---

## ⚙️ Activity Settings

| Setting | Description |
|---------|-------------|
| **Open in new tab** | Open PDFs in a new browser tab |
| **Display inline** | Show folder contents on the course page |
| **Show expanded** | Auto-expand sub-folders |
| **Download links** | Show download link per file as fallback |
| **Read-only protection** | Enable DRM — disables print/download/copy |

---

## 🗄️ Database Tables

| Table | Purpose |
|-------|---------|
| `mdl_ompdf` | Activity instance settings |
| `mdl_ompdf_analytics` | Per-user, per-page reading time tracking |
| `mdl_ompdf_annotations` | Smart Notes & Hints storage |

## 🔌 External API

Interactive viewer actions are exposed through the registered Moodle External Service
`mod_ompdf_execute_action` and called by the AMD/API integration. The legacy
`api.php` endpoint remains as a compatibility fallback for the bundled PDF.js viewer.
All write actions validate the logged-in user, course-module context, capability,
and sesskey.

## 🧪 Development and quality checks

The repository includes GitHub Actions configuration in [`.github/workflows/ci.yml`](.github/workflows/ci.yml)
for Moodle plugin checks. Before submitting changes, run the available checks with
`moodle-plugin-ci` and rebuild AMD files with Moodle Grunt tasks.

The bundled PDF.js update and licensing instructions are documented in
[`pdfjs/readme_moodle.txt`](pdfjs/readme_moodle.txt). Do not update the distribution
without updating [`thirdpartylibs.xml`](thirdpartylibs.xml) and verifying the build.

---

## 🐳 Docker (Local Development)

A Docker Compose setup is included for local development:

```bash
# Start
docker compose up -d

# Stop
docker compose down
```

See [README_DOCKER.md](README_DOCKER.md) for full setup instructions.

---

## 📜 Changelog

### v2.2.0 (2026-09-03)
- ✅ Added Chinese, Arabic, German, Dutch, and Japanese language packs
- ✅ Added Arabic RTL layout support
- ✅ Completed Indonesian Privacy API strings
- ✅ Added production compliance tests and updated Moodle documentation

### v2.1.0 (2026-07-29)
- ✨ Smart Notes & Hints system with side drawer and notification badge
- ✨ Student Engagement Analytics dashboard
- ✨ Enterprise DRM read-only protection
- ✨ Dark / Night Mode toggle
- ⬆️ PDF.js 3.11.174 declared as a bundled third-party library
- ✅ Added activity backup/restore and Privacy API support
- ✅ Added registered External Service, CSRF protection, and capability checks
- ✅ Migrated activity rendering to Mustache templates and AMD modules
- ✅ Added Moodle CI workflow and plugin quality checks
- 🐛 Fix CSS syntax error on toolbar icon mask-image
- 🐛 Fix `db/install.xml` missing `</INDEXES>` tag (moodle-plugin-ci validate)

### v2.0.0 (2026-07-28)
- 🚀 Major rewrite with modern UI

### v1.01
- Maintenance release

### v1.0
- Initial release based on [PDF.js Folder](https://moodle.org/plugins/mod_pdfjsfolder) by [Jonas Nockert](https://github.com/lnockert)

---

## 📄 License

This plugin is licensed under the **GNU General Public License v3.0 or later**.
See the [LICENSE](LICENSE) file for full details.

---

## 🙏 Credits

- Original concept based on [mod_pdfjsfolder](https://moodle.org/plugins/mod_pdfjsfolder) by Jonas Nockert
- PDF rendering powered by [PDF.js](https://github.com/mozilla/pdf.js) — Mozilla
- Maintained by [Dian Mukti Wibowo](https://github.com/onyet) (onyetcorp@gmail.com)
