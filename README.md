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
1. Download the plugin ZIP file or clone the repository.
2. Upload the plugin folder to `/wp-content/plugins/generate-pdf-tests/`.
3. Activate the plugin from the **Plugins** menu in WordPress.

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
