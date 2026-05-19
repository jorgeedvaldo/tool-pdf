# ToolPDF

**ToolPDF** is a free, fast, and privacy-focused PDF toolkit built with Laravel. It provides 20+ tools for editing, converting, and managing PDF documents — most operations run entirely in the browser (no file upload required), while server-side tools like PDF↔Word conversion use pure-PHP processing.

🌍 **Live Website:** [toolpdf.org](https://toolpdf.org)

---

## Tools

### Organize & Edit
| Tool | Description | Processing |
|------|-------------|------------|
| Merge PDF | Combine multiple PDFs with drag-and-drop reordering | Browser |
| Split PDF | Extract pages or ranges; downloads as a ZIP | Browser |
| Compress PDF | Reduce file size with configurable quality | Browser |
| Rotate Pages | Rotate individual or all pages | Browser |
| Remove Pages | Delete unwanted pages from a PDF | Browser |
| Extract Pages | Save a subset of pages as a new PDF | Browser |
| Reorganize Pages | Drag-and-drop page reordering | Browser |
| Edit PDF | Add text boxes, shapes, highlights, images, and signatures | Browser |
| Sign PDF | Draw, type, or upload a signature and place it on any page | Browser |
| Overlay PDFs | Stamp one PDF on top of another | Browser |

### Convert
| Tool | Description | Processing |
|------|-------------|------------|
| PDF to Word | Convert PDF to editable `.docx` | **Server** (smalot/pdfparser + phpoffice/phpword) |
| Word to PDF | Convert `.docx`, `.doc`, `.odt`, `.rtf` to PDF | **Server** (phpoffice/phpword + mPDF) |
| PDF to Images | Export each page as a PNG/JPEG image | Browser |
| Images to PDF | Combine JPG/PNG images into a single PDF | Browser |

### Security
| Tool | Description | Processing |
|------|-------------|------------|
| Protect PDF | Password-encrypt a PDF | Browser |
| Unlock PDF | Remove password from an owned PDF | Browser |
| Add Watermark | Stamp text or image watermark on every page | Browser |
| Add Page Numbers | Insert page numbers in a chosen position | Browser |

### Advanced
| Tool | Description | Processing |
|------|-------------|------------|
| PDF OCR | Extract text from scanned PDFs using Tesseract.js | Browser |
| Compare PDF | Side-by-side visual and text diff of two PDFs | Browser |

---

## Technology Stack

### Backend
| Package | Version | Purpose |
|---------|---------|---------|
| [Laravel](https://laravel.com) | 9.x | Routing, views, localization |
| [phpoffice/phpword](https://github.com/PHPOffice/PHPWord) | ^1.4 | Read/write Word documents |
| [mpdf/mpdf](https://mpdf.github.io) | ^8.3 | Render Word → PDF server-side |
| [smalot/pdfparser](https://github.com/smalot/pdfparser) | ^2.12 | Extract text from PDFs |
| [intervention/image](https://image.intervention.io) | 3.x | Image processing |
| [filament/filament](https://filamentphp.com) | 2.x | Admin panel |

### Frontend (CDN — no build step for tools)
| Library | Purpose |
|---------|---------|
| [PDF.js](https://mozilla.github.io/pdf.js/) 3.11 | Render PDF pages in browser |
| [pdf-lib](https://pdf-lib.js.org/) 1.17 | Create and modify PDFs in browser |
| [Bootstrap 5.3](https://getbootstrap.com) | UI framework |
| [Bootstrap Icons](https://icons.getbootstrap.com) | Icon set |
| [Tesseract.js](https://tesseract.projectnaptha.com) 5.x | In-browser OCR |
| [diff](https://github.com/kpdecker/jsdiff) 5.x | Word-level text diffing (Compare PDF) |
| [pixelmatch](https://github.com/mapbox/pixelmatch) 6.x | Pixel-level image diffing (Compare PDF) |

### Languages supported
English · Portuguese · Spanish · French · Chinese · Hindi · Russian

---

## Requirements

- **PHP** ≥ 8.0.2
- **Composer** 2.x
- **Database** — SQLite (default for local dev), MySQL, or PostgreSQL
- A web server (Apache / Nginx) or `php artisan serve` for development

> **Note:** PDF↔Word conversion runs entirely in PHP with no system binaries required. LibreOffice is **not** needed.

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

Visit `http://localhost:8000` — the app redirects to your browser's locale automatically.

---

## Project Structure

```
app/
  Http/Controllers/
    ConvertController.php   # PDF↔Word server-side conversion
    PostController.php      # Blog
    SitemapController.php   # XML sitemap
    FeedController.php      # RSS feed
lang/
  en/ es/ fr/ pt/ zh/ hi/ ru/
    messages.php            # UI strings and tool descriptions
public/
  js/tools/                 # One JS file per tool (loaded via CDN libraries)
    merge-pdf.js
    split-pdf.js
    compress-pdf.js
    edit-pdf.js
    compare-pdf.js
    convert-pdf-word.js     # fetch() → ConvertController
    ...
resources/views/
  layouts/app.blade.php     # Base layout with navbar and footer
  tools/                    # One blade view per tool
  home.blade.php            # Tool grid with category filters
routes/
  web.php                   # All routes, grouped under /{locale}/
tests/
  Feature/                  # HTTP tests for routes, views, POST endpoints
  Unit/                     # Algorithm tests (line grouping, alignment, etc.)
```

---

## How browser-side tools work

All tools that don't require server processing use a pattern of:

1. User drops a file into the drop zone
2. A static JS file in `public/js/tools/` reads the file via the File API
3. PDF.js renders pages; pdf-lib modifies the PDF bytes
4. The result is offered as a download via `URL.createObjectURL()`

**No file is ever uploaded** for these tools — everything stays on the user's device.

## How server-side conversion works (PDF↔Word)

```
Browser  →  POST /convert/pdf-to-word  →  ConvertController
                                               ↓
                                         smalot/pdfparser  (extract text)
                                         phpoffice/phpword  (write .docx)
                                               ↓
                                         response()->download()
                                               ↓
Browser  ←  .docx binary  ←────────────────────
```

Files are stored in an isolated temporary directory, processed, streamed back, and deleted immediately after the response is sent.

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
- All tool page routes return HTTP 200 across locales
- POST conversion endpoints validate file types and reject invalid inputs
- Conversion routes enforce POST method
- Home page lists all tools with correct links
- Core JS algorithm logic: line grouping, paragraph detection, alignment detection, coordinate mapping

---

## Localization

Translations live in `lang/{locale}/messages.php`. Each key maps to a tool name, description, or UI string.

To add a new language:

```bash
cp -r lang/en lang/de        # duplicate English as base
# Edit lang/de/messages.php
```

Then add the locale to the route constraint regex in `routes/web.php` and register it in the language switcher in `resources/views/layouts/app.blade.php`.

---

## Deployment

```bash
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
```

Set these in `.env` for production:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
```

---

## License

This project is licensed under the [MIT License](LICENSE).
