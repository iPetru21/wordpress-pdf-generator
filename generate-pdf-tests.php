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
// INTEGRARE PLUGIN UPDATE CHECKER
// ============================================================================
// Setările se configurează din PDF Cursanți -> Setări -> GitHub Credentials

add_action( 'plugins_loaded', function () {
    $update_enabled = get_option( 'cursant_pdf_update_enabled', '1' );
    if ( $update_enabled !== '1' ) {
        return;
    }

    $update_checker_file = __DIR__ . '/vendor/yahnis-elsts/plugin-update-checker/plugin-update-checker.php';
    if ( ! file_exists( $update_checker_file ) ) {
        add_action( 'admin_notices', function () {
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
        } );
        return;
    }

    require $update_checker_file;

    $github_repo   = get_option( 'cursant_pdf_github_repo', 'https://github.com/username/repository-name' );
    $github_branch = get_option( 'cursant_pdf_github_branch', 'main' );
    $github_token  = get_option( 'cursant_pdf_github_token', '' );

    // Nu inițializa dacă repo-ul nu a fost configurat
    if ( empty( $github_repo ) || strpos( $github_repo, 'username/repository-name' ) !== false ) {
        return;
    }

    $update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
        $github_repo,
        __FILE__,
        'generate-pdf-tests'
    );

    if ( ! empty( $github_branch ) ) {
        $update_checker->setBranch( $github_branch );
    }

    if ( ! empty( $github_token ) ) {
        $update_checker->setAuthentication( $github_token );
    }

    if ( method_exists( $update_checker, 'getVcsApi' ) ) {
        $vcs_api = $update_checker->getVcsApi();
        if ( $vcs_api && method_exists( $vcs_api, 'enableReleaseAssets' ) ) {
            $vcs_api->enableReleaseAssets();
        }
    }
}, 20 );

// ============================================================================
// LOAD PLUGIN FUNCTIONALITY
// ============================================================================

// Load Admin Functionality
require __DIR__ . '/admin/Admin.php';

// PDF test generator
require __DIR__ . '/inc/PDFGenerator.php';

?>
