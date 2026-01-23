<?php
/**
 * Plugin Update Checker
 * 
 * Această bibliotecă permite pluginului să se actualizeze automat din repository-ul GitHub.
 * 
 * Pentru a instala dependențele, rulează în directorul pluginului:
 * composer install
 * 
 * Sau descarcă manual din: https://github.com/YahnisElsts/plugin-update-checker
 */

// Fișierul principal va include automat această bibliotecă dacă există
if (file_exists(__DIR__ . '/vendor/yahnis-elsts/plugin-update-checker/plugin-update-checker.php')) {
    require __DIR__ . '/vendor/yahnis-elsts/plugin-update-checker/plugin-update-checker.php';
}
