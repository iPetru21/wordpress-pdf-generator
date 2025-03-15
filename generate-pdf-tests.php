<?php
/**
 * Plugin Name: Cursant PDF Generator
 * Description: Generează un PDF cu răspunsurile cursanților.
 * 
 * Version: 1.0
 * Author: Iftodi Petru
 * Requires Plugins: generate-pdf-using-contact-form-7
 */

 defined( 'ABSPATH' ) || exit;

// Load Admin Functionality
require __DIR__ . '/admin/Admin.php';

// PDF test generator
require __DIR__ . '/inc/PDFGenerator.php';


?>
