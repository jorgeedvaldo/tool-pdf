# ToolPDF

**ToolPDF** is an online, fast, and secure PDF toolkit designed to process your documents directly in your browser. With a classic 2013-inspired retro UI, but powered by modern web technologies, ToolPDF keeps your data safe without server-side processing overhead.

🌍 **Live Website:** [toolpdf.org](https://toolpdf.org)

## Features

- **Merge PDF**: Combine multiple PDF files into one easily with drag & drop functionality. Reorder files before merging.
- **Split PDF**: Extract all pages as separate PDF files, or selectively split by providing custom page ranges. All separate PDFs are efficiently zipped for a single download.
- **Client-Side Processing**: Utilizes `pdf-lib` to do all the heavy lifting locally in your browser. Your files never leave your computer, ensuring 100% privacy and security.
- **Multi-lingual Support**: Full support for 7 languages:
  1. English (EN)
  2. Portuguese (PT)
  3. Spanish (ES)
  4. French (FR)
  5. Chinese (ZH)
  6. Hindi (HI)
  7. Russian (RU)
- **Responsive 2013 Retro UI**: A charming aesthetic powered by Bootstrap 5, featuring engaging gradients and box shadows across desktop, tablet, and mobile devices.

*(More tools like Compress, Edit, Sign, and Convert coming soon!)*

## Technology Stack

- **Backend / Routing**: [Laravel 10+](https://laravel.com)
- **Frontend Framework**: [Bootstrap 5](https://getbootstrap.com) (via CDN)
- **PDF Manipulation**: [pdf-lib](https://pdf-lib.js.org/) for in-browser client-side document processing
- **Zip Generation**: [JSZip](https://stuk.github.io/jszip/) for packaging output files directly in the browser
- **Icons**: Bootstrap Icons

## Installation & Setup (Local Development)

If you'd like to work on this project locally, make sure you have PHP, Composer, and Node/NPM installed.

1. **Clone the generic repository**:
   ```bash
   git clone https://github.com/yourusername/toolpdf.git
   cd toolpdf
   ```

2. **Install PHP Dependencies**:
   ```bash
   composer install
   ```

3. **Set up Environment**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Serve the Application**:
   ```bash
   php artisan serve
   ```
   *The application will be accessible at `http://localhost:8000`.*

## Localization

Translations are managed via Laravel's language files located in the `lang/` directory.

To add or modify translations, navigate to the respective language folder (e.g., `lang/pt/messages.php` for Portuguese) and update the key-value pairs.

## License

This project is licensed under the MIT License - see the LICENSE file for details.
