# Generate PDF Tests

## Description
**Generate PDF Tests** is a WordPress plugin that allows users to generate PDF files from WordPress content. It provides an easy-to-use interface for exporting posts, pages, and custom content as downloadable PDFs.

## Features
- Export posts and pages as PDF
- Customize PDF layout and design
- Support for custom post types
- Easy integration with shortcodes
- Supports different PDF libraries like TCPDF or DomPDF

## Installation

### Instalare Standard
1. Download the plugin ZIP file or clone the repository.
2. Upload the plugin folder to `/wp-content/plugins/generate-pdf-tests/`.
3. Activate the plugin from the **Plugins** menu in WordPress.

### Instalare Dependențe Composer (pentru Update-uri Automate)

Pentru a activa sistemul de update automat din GitHub, trebuie să instalați dependențele Composer:

```bash
cd wp-content/plugins/generate-pdf-tests
composer install
```

Dacă nu aveți Composer instalat, îl puteți instala de la [getcomposer.org](https://getcomposer.org/download/).

### Configurare Update-uri din GitHub

După instalarea dependențelor, configurați repository-ul GitHub în fișierul `generate-pdf-tests.php`:

1. Deschideți `generate-pdf-tests.php`
2. Actualizați următoarele constante:
   - `GENERATE_PDF_TESTS_GITHUB_REPO` - URL-ul repository-ului GitHub
   - `GENERATE_PDF_TESTS_GITHUB_BRANCH` - Branch-ul pentru update-uri (ex: 'main', 'master')
   - `GENERATE_PDF_TESTS_GITHUB_TOKEN` - Token GitHub (doar pentru repository-uri private)
   - `GENERATE_PDF_TESTS_UPDATE_ENABLED` - Activează/dezactivează update-urile (true/false)

**Exemplu configurare:**
```php
define( 'GENERATE_PDF_TESTS_GITHUB_REPO', 'https://github.com/username/repository-name' );
define( 'GENERATE_PDF_TESTS_GITHUB_BRANCH', 'main' );
define( 'GENERATE_PDF_TESTS_GITHUB_TOKEN', '' ); // Lăsați gol pentru repository-uri publice
define( 'GENERATE_PDF_TESTS_UPDATE_ENABLED', true );
```

**Pentru repository-uri private:**
1. Creați un Personal Access Token în GitHub: Settings > Developer settings > Personal access tokens > Tokens (classic)
2. Acordați permisiunea `repo` pentru acces la repository-uri private
3. Adăugați token-ul în constanta `GENERATE_PDF_TESTS_GITHUB_TOKEN`

## Usage
1. Navigate to the plugin settings page under **Settings > Generate PDF Tests**.
2. Configure your PDF generation options.
3. Use the `[generate_pdf]` shortcode to add a download button on any page or post.

## Shortcodes
- `[generate_pdf]` – Adds a button to download the current page as a PDF.
- `[generate_pdf post_id="123"]` – Generates a PDF for a specific post ID.

## Hooks & Filters
### Actions
- `generate_pdf_before_export` – Fires before the PDF is generated.
- `generate_pdf_after_export` – Fires after the PDF has been generated.

### Filters
- `generate_pdf_filename` – Customize the output filename.
- `generate_pdf_content` – Modify the content before rendering the PDF.

## Requirements
- WordPress 5.0+
- PHP 7.4+
- TCPDF or DomPDF installed (optional for enhanced PDF generation)
- Composer (pentru update-uri automate din GitHub)

## Changelog

### Version 1.4 (Curent)

#### 🔧 Corecții și îmbunătățiri
- Actualizat numărul versiunii la 1.4
- Îmbunătățiri generale de stabilitate și performanță

#### 📝 Documentație
- Actualizat changelog-ul pentru versiunea 1.4

### Version 1.3

#### ✨ Funcționalități noi
- **Sistem de update automat din GitHub**: Integrare completă cu YahnisElsts Plugin Update Checker pentru update-uri automate din repository-ul GitHub
- **Suport Composer**: Adăugat `composer.json` pentru gestionarea dependențelor
- **Configurare centralizată**: Toate setările pentru update-uri sunt configurate prin constante în fișierul principal

#### 🔄 Modificări majore
- **Sistem de generare note îmbunătățit**: 
  - Înlocuit câmpul "Rată success" cu "Notă minimă" în interfața de administrare
  - Notele generate sunt acum aleatorii între nota minimă setată și 10
  - Logica de calcul a răspunsurilor corecte a fost optimizată pentru a garanta note în intervalul specificat
- **Algoritm de generare răspunsuri**: 
  - Reimplementat complet funcția `generate_random_answers()` pentru a genera note precise în intervalul dorit
  - Eliminată problema unde toate testele primeau aceeași notă (9)

#### 🛠️ Îmbunătățiri tehnice
- Adăugat `.gitignore` pentru excluderea directorului `vendor/` și fișierelor temporare
- Mesaje de avertizare în admin dacă biblioteca de update nu este instalată
- Suport pentru repository-uri GitHub publice și private
- Suport pentru release assets din GitHub

#### 📝 Documentație
- Actualizat README.md cu instrucțiuni detaliate pentru instalarea dependențelor Composer
- Adăugate exemple de configurare pentru sistemul de update
- Documentație pentru repository-uri private cu token GitHub

### Version 1.0
- Versiunea inițială a pluginului
- Generare PDF cu răspunsurile cursanților
- Sistem de generare răspunsuri aleatorii bazat pe rată de succes

## Contributing
1. Fork the repository.
2. Create a new branch: `git checkout -b feature-name`
3. Make your changes and commit: `git commit -m "Added new feature"`
4. Push to your branch: `git push origin feature-name`
5. Submit a pull request.

## License
This plugin is licensed under the **GPLv2 or later**.

## Author
Developed by [Iftodi Petru](https://github.com/iPetru21).

## Support
For issues and feature requests, please open an issue on [GitHub](https://github.com/iPetru21/wordpress-pdf-generator/issues).
