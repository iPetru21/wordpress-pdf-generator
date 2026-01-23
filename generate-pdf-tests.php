<?php
/**
 * Plugin Name: Cursant PDF Generator
 * Description: Generează un PDF cu răspunsurile cursanților.
 * 
 * Version: 1.3
 * Author: Iftodi Petru
 * Requires Plugins: generate-pdf-using-contact-form-7
 */

defined( 'ABSPATH' ) || exit;

// ============================================================================
// CONFIGURARE UPDATE DIN GITHUB
// ============================================================================
// Configurează aici detaliile repository-ului GitHub pentru update-uri automate

// URL-ul repository-ului GitHub (ex: https://github.com/username/repository-name)
define( 'GENERATE_PDF_TESTS_GITHUB_REPO', 'https://github.com/username/repository-name' );

// Branch-ul de pe care să se facă update-urile (ex: 'main', 'master', 'develop')
define( 'GENERATE_PDF_TESTS_GITHUB_BRANCH', 'main' );

// Token de acces GitHub (opțional, pentru repository-uri private)
// Lăsați gol pentru repository-uri publice
// Pentru repository-uri private, creați un Personal Access Token în GitHub Settings > Developer settings > Personal access tokens
define( 'GENERATE_PDF_TESTS_GITHUB_TOKEN', '' );

// Activare update-uri (true/false)
define( 'GENERATE_PDF_TESTS_UPDATE_ENABLED', true );

// ============================================================================
// INTEGRARE PLUGIN UPDATE CHECKER
// ============================================================================

if ( GENERATE_PDF_TESTS_UPDATE_ENABLED ) {
    // Încarcă biblioteca Plugin Update Checker dacă există
    $update_checker_file = __DIR__ . '/vendor/yahnis-elsts/plugin-update-checker/plugin-update-checker.php';
    
    if ( file_exists( $update_checker_file ) ) {
        require $update_checker_file;
        
        // Obține valorile constantelor
        $github_repo = GENERATE_PDF_TESTS_GITHUB_REPO;
        $github_branch = GENERATE_PDF_TESTS_GITHUB_BRANCH;
        $github_token = GENERATE_PDF_TESTS_GITHUB_TOKEN;
        
        // Inițializează sistemul de update folosind repository-ul GitHub direct
        $update_checker = Puc_v4_Factory::buildUpdateChecker(
            $github_repo,
            __FILE__,
            'generate-pdf-tests'
        );
        
        // Setează branch-ul dacă este specificat
        if ( ! empty( $github_branch ) ) {
            $update_checker->setBranch( $github_branch );
        }
        
        // Setează token-ul pentru repository-uri private
        if ( ! empty( $github_token ) ) {
            $update_checker->setAuthentication( $github_token );
        }
        
        // Activează release assets pentru GitHub
        if ( method_exists( $update_checker, 'getVcsApi' ) ) {
            $vcs_api = $update_checker->getVcsApi();
            if ( $vcs_api && method_exists( $vcs_api, 'enableReleaseAssets' ) ) {
                $vcs_api->enableReleaseAssets();
            }
        }
    } else {
        // Dacă biblioteca nu există, afișează un mesaj în admin (doar pentru administratori)
        add_action( 'admin_notices', function() {
            if ( current_user_can( 'manage_options' ) ) {
                ?>
                <div class="notice notice-warning is-dismissible">
                    <p>
                        <strong>Cursant PDF Generator:</strong> 
                        Sistemul de update automat nu este disponibil. 
                        Pentru a activa update-urile din GitHub, instalați dependențele Composer:
                        <code>composer install</code> în directorul pluginului.
                    </p>
                </div>
                <?php
            }
        });
    }
}

// ============================================================================
// LOAD PLUGIN FUNCTIONALITY
// ============================================================================

// Load Admin Functionality
require __DIR__ . '/admin/Admin.php';

// PDF test generator
require __DIR__ . '/inc/PDFGenerator.php';

?>
