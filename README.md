# ToolPDF

<div align="center">
  <h1>Free PDF Tools — No Upload Required</h1>
  <p>
    <strong>Edit, Convert & Secure PDF files — Private, Fast, Free</strong>
  </p>
  <p>
    Most operations run entirely in your browser. Server-side tools use pure PHP — no LibreOffice or system binaries needed.
  </p>
</div>

<div align="center">

[![Website](https://img.shields.io/website?url=https%3A%2F%2Ftoolpdf.org)](https://toolpdf.org)
![Laravel](https://img.shields.io/badge/Laravel-9.x-red?style=flat-square&logo=laravel)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-blue?style=flat-square&logo=php)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-purple?style=flat-square&logo=bootstrap)
![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)

</div>

---

## About

**ToolPDF** is a privacy-first PDF toolkit built with Laravel. Over 25+ tools for editing, converting, and managing PDF files — most run entirely in the browser via PDF.js and pdf-lib (no server upload needed). Server-side tools like PDF↔Word conversion use pure PHP with no system binaries required.

🌍 **Live Website:** [toolpdf.org](https://toolpdf.org)

### Key Features

- **🔒 100% Private (browser tools)**: Files never leave your device for browser-side operations.
- **🚀 Zero dependencies for server tools**: No LibreOffice, Ghostscript, or system binaries — pure PHP.
- **🛠️ 25+ Tools**: Organize, convert, secure, and edit PDF files.
- **🌐 Multi-language**: English · Portuguese · Spanish · French · Chinese · Hindi · Russian.
- **📊 Smart Conversion**: PDF→Word preserves headings, tables, bullet lists, and paragraphs.

---

## 🧰 Complete Tool List

### 📁 Organize & Edit (10 tools)
| Tool | Description | Processing |
|------|-------------|------------|
| **Merge PDF** | Combine multiple PDFs with drag-and-drop reordering | Browser |
| **Split PDF** | Extract pages or ranges; downloads as a ZIP | Browser |
| **Compress PDF** | Reduce file size with configurable quality | Browser |
| **Rotate Pages** | Rotate individual or all pages (90°, 180°, 270°) | Browser |
| **Remove Pages** | Delete unwanted pages from a PDF | Browser |
| **Extract Pages** | Save a subset of pages as a new PDF | Browser |
| **Reorganize Pages** | Drag-and-drop page reordering | Browser |
| **Edit PDF** | Add text boxes, shapes, highlights, images, and signatures | Browser |
| **Sign PDF** | Draw, type, or upload a signature and place it on any page | Browser |
| **Overlay PDFs** | Stamp one PDF on top of another | Browser |

### 🔄 Convert to PDF (6 tools)
| Tool | Description | Processing |
|------|-------------|------------|
| **Word to PDF** | Convert `.docx`, `.doc`, `.odt`, `.rtf` to PDF — fonts and layout preserved | **Server** |
| **Excel to PDF** | Convert `.xlsx`, `.xls`, `.ods`, `.csv` spreadsheets to PDF | **Server** |
| **PowerPoint to PDF** | Convert `.pptx`, `.ppt`, `.odp` presentations to PDF | **Server** |
| **HTML to PDF** | Convert any public webpage URL to a PDF | **Server** |
| **Images to PDF** | Combine JPG/PNG/WebP images into a single PDF | Browser |
| **Flatten PDF** | Merge form fields and annotations into the page content | Browser |

### 📤 Convert from PDF (7 tools)
| Tool | Description | Processing |
|------|-------------|------------|
| **PDF to Word** | Convert PDF to editable `.docx` — headings, tables, and lists detected | **Server** |
| **PDF to Excel** | Extract text and table data into an `.xlsx` spreadsheet | **Server** |
| **PDF to PowerPoint** | Convert PDF pages into editable `.pptx` slides | **Server** |
| **PDF to Images** | Export each page as PNG or JPEG images | Browser |
| **PDF OCR** | Extract text from scanned PDFs using Tesseract.js | Browser |
| **Compare PDF** | Side-by-side visual and text diff of two PDF files | Browser |
| **Repair PDF** | Attempt to recover and re-save a corrupted PDF | Browser |

### 🔒 Security (4 tools)
| Tool | Description | Processing |
|------|-------------|------------|
| **Protect PDF** | Password-encrypt a PDF with AES-128 | Browser |
| **Unlock PDF** | Remove password from an owned PDF | Browser |
| **Add Watermark** | Stamp text or image watermark on every page | Browser |
| **Add Page Numbers** | Insert page numbers in a chosen position and style | Browser |

---

## 💻 Technology Stack

### Backend
| Package | Version | Purpose |
|---------|---------|---------|
| [Laravel](https://laravel.com) | 9.x | Routing, views, localization, ORM |
| [phpoffice/phpword](https://github.com/PHPOffice/PHPWord) | ^1.4 | Read/write Word documents (PDF→DOCX, DOCX→PDF) |
| [mpdf/mpdf](https://mpdf.github.io) | ^8.3 | Render Word/HTML/PPT → PDF server-side |
| [smalot/pdfparser](https://github.com/smalot/pdfparser) | ^2.12 | Extract structured text from PDFs |
| [phpoffice/phpspreadsheet](https://github.com/PHPOffice/PhpSpreadsheet) | ^2.x | Read/write spreadsheet files |
| [phpoffice/phppresentation](https://github.com/PHPOffice/PHPPresentation) | ^0.9 | Read/write PowerPoint presentations |
| [guzzlehttp/guzzle](https://github.com/guzzle/guzzle) | ^7.x | Fetch remote URLs for HTML→PDF |
| [intervention/image](https://image.intervention.io) | 3.x | Image processing |
| [filament/filament](https://filamentphp.com) | 2.x | Admin panel |

### Frontend (CDN — no build step required for tools)
| Library | Version | Purpose |
|---------|---------|---------|
| [PDF.js](https://mozilla.github.io/pdf.js/) | 3.11 | Render PDF pages in-browser |
| [pdf-lib](https://pdf-lib.js.org/) | 1.17 | Create and modify PDFs in-browser |
| [Bootstrap](https://getbootstrap.com) | 5.3 | UI framework |
| [Bootstrap Icons](https://icons.getbootstrap.com) | — | Icon set |
| [Tesseract.js](https://tesseract.projectnaptha.com) | 5.x | In-browser OCR for scanned PDFs |
| [jsdiff](https://github.com/kpdecker/jsdiff) | 5.x | Word-level text diffing (Compare PDF) |
| [pixelmatch](https://github.com/mapbox/pixelmatch) | 6.x | Pixel-level image diffing (Compare PDF) |

---

## How It Works

### Browser-side tools (no upload)
1. User drops a file into the drop zone
2. A JS file in `public/js/tools/` reads the file via the File API
3. PDF.js renders pages; pdf-lib modifies the PDF bytes in-memory
4. The result is downloaded via `URL.createObjectURL()`

**No file is ever sent to the server** for these tools.

### Server-side conversion (PDF↔Office)

Inspired by [pdfcraft](https://github.com/jorgeedvaldo/pdfcraft)'s structured document analysis approach, the conversion pipeline uses smart text analysis:

```
Browser  →  POST /convert/pdf-to-word
                   ↓
             smalot/pdfparser      → extract raw text per page
             ConvertController     → analyzeTextBlocks()
                   ↓
             Detect headings (H1/H2/H3 by length + casing heuristics)
             Detect bullet / numbered lists
             Detect table rows (2+ space-aligned columns)
             Group remaining lines into paragraphs
                   ↓
             phpoffice/phpword     → render structured .docx
                   ↓
Browser  ←  .docx binary (auto-deleted immediately after send)
```

Files are stored in an isolated temporary directory, processed, streamed back, then deleted on PHP shutdown.

---

## Requirements

- **PHP** ≥ 8.0.2
- **Composer** 2.x
- **Database** — SQLite (local dev default), MySQL, or PostgreSQL

> No LibreOffice, Ghostscript, or system binaries required.

---

## Installation

```bash
# 1. Clone the repository
git clone https://github.com/jorgeedvaldo/tool-pdf.git
cd tool-pdf

# 2. Install PHP dependencies
composer install

# 3. Set up environment
cp .env.example .env
php artisan key:generate

# 4. Run database migrations
php artisan migrate

# 5. Create the storage symlink
php artisan storage:link

# 6. Start the development server
php artisan serve
```

Visit `http://localhost:8000` — the app auto-redirects to your browser's locale.

---

## Project Structure

```
app/
  Http/Controllers/
    ConvertController.php      # PDF↔Office server-side conversions (Word, Excel, PPT, HTML)
    PostController.php         # Blog posts
    SitemapController.php      # XML sitemap generator
    FeedController.php         # RSS feed
lang/
  en/ pt/ es/ fr/ zh/ hi/ ru/
    messages.php               # UI strings and tool descriptions per locale
public/
  js/tools/                    # One JS file per browser-side tool
    merge-pdf.js
    split-pdf.js
    compress-pdf.js
    edit-pdf.js
    compare-pdf.js
    convert-pdf-word.js        # fetch() → ConvertController endpoints
    convert-generic.js         # Generic fetch() handler for Excel/PPT routes
    ...
resources/views/
  layouts/app.blade.php        # Base layout (navbar, footer, locale switcher)
  tools/                       # One Blade view per tool
    convert_pdf_word.blade.php # Tabbed PDF↔Word UI
    convert_generic.blade.php  # Reusable single-file upload UI for other conversions
  home.blade.php               # Tool grid with category filters
routes/
  web.php                      # All routes under /{locale}/, conversion POST endpoints
tests/
  Feature/                     # HTTP tests: routes, views, POST endpoints, validations
  Unit/                        # Algorithm tests: line grouping, paragraph detection
```

---

## Running Tests

```bash
# All tests
php artisan test

# Feature tests only (HTTP layer)
php artisan test tests/Feature/

# Unit tests only (algorithm logic)
php artisan test tests/Unit/
```

The test suite covers:
- All tool page routes return HTTP 200 across all locales
- POST conversion endpoints validate file types and reject invalid inputs
- Conversion routes enforce POST method (GET returns 405)
- Home page lists all tools with correct links
- Core JS algorithm logic: line grouping, paragraph detection, alignment detection

---

## Localization

Translations live in `lang/{locale}/messages.php`. Each key maps to a tool name, description, or UI string.

To add a new language:

```bash
cp -r lang/en lang/de
# Edit lang/de/messages.php
```

Then add the locale code to the route constraint regex in `routes/web.php` and register it in the language switcher in `resources/views/layouts/app.blade.php`.

---

## Deployment

```bash
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
```

`.env` settings for production:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
```

---

## Contributing

Contributions are welcome! Please open a Pull Request.

1. Fork the project
2. Create your feature branch: `git checkout -b feature/my-feature`
3. Commit your changes: `git commit -m 'Add my feature'`
4. Push to the branch: `git push origin feature/my-feature`
5. Open a Pull Request

---

## Acknowledgements

The structured document analysis approach in `ConvertController` was inspired by [PDFCraft](https://github.com/jorgeedvaldo/pdfcraft)'s multi-layer document processing model, adapted for a PHP/server-side context.

---

## License

This project is licensed under the [MIT License](LICENSE).
