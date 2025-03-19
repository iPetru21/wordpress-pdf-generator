<?php 

defined( 'ABSPATH' ) || exit;

/**
 * Admin
 */

class GeneratePdfAdmin {

    function __construct() {
        add_action('admin_menu', [ $this, 'add_cursant_pdf_menu' ]);
        add_action('admin_init', [ $this, 'cursant_pdf_register_settings' ]);
        add_action('show_user_profile', [ $this, 'add_cnp_field_to_user_profile' ]);
        add_action('edit_user_profile', [ $this, 'add_cnp_field_to_user_profile' ]);
        add_action('personal_options_update', [ $this, 'save_cnp_field' ]);
        add_action('edit_user_profile_update', [ $this, 'save_cnp_field' ]);
        add_action('personal_options_update', [ $this, 'validate_cnp_field' ]);
        add_action('edit_user_profile_update', [ $this, 'validate_cnp_field' ]);
        add_action('admin_post_cursant_pdf_generate_report', [ $this, 'cursant_pdf_generate_report' ]);    
    }

    function validate_cnp_field($user_id) {
        if (!empty($_POST['cnp']) && !preg_match('/^[0-9]{13}$/', $_POST['cnp'])) {
            wp_die('Eroare: CNP-ul trebuie să conțină exact 13 cifre!');
        }
    }

    // Salvează câmpul CNP când se editează profilul utilizatorului
    function save_cnp_field($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }
        update_user_meta($user_id, 'cnp', sanitize_text_field($_POST['cnp']));
    }

    // Adaugă câmpul CNP în profilul utilizatorului
    function add_cnp_field_to_user_profile($user) {
        ?>
        <table class="form-table">
            <tr>
                <th><label for="cnp">Cod Numeric Personal (CNP)</label></th>
                <td>
                    <input type="text" name="cnp" id="cnp" value="<?php echo esc_attr(get_user_meta($user->ID, 'cnp', true)); ?>" class="regular-text" />
                    <p class="description">Introduceți CNP-ul utilizatorului.</p>
                </td>
            </tr>
        </table>
        <?php
    }

    function cursant_pdf_generate_report() {
        // Verificare securitate
        if (!isset($_POST['cursant_pdf_nonce']) || !wp_verify_nonce($_POST['cursant_pdf_nonce'], 'cursant_pdf_action')) {
            wp_die('Security check failed');
        }
    
        // Aici poți adăuga logica necesară (ex: generare PDF)
        update_option('cursant_last_pdf_generated', current_time('mysql'));
        
        // Aici se vor rula toate actiunile necesare la apasarea butonului
        do_action('cursant_pdf_generate_report_run');

        // Redirecționează înapoi la pagina de setări cu un mesaj de succes
        wp_redirect(admin_url('options-general.php?page=cursant_pdf_settings&success=1'));
        exit;
    }    

    // Adăugăm setările pe pagina de admin
    function cursant_pdf_settings_page() {
        // Obținem toate rolurile de pe site
        $roles = get_editable_roles();
        $last_generated = get_option('cursant_last_pdf_generated', 'Niciodată');

        // Caută fișiere care respectă formatul "test-{id}.php"
        $test_files = glob(plugin_dir_path(__DIR__) . 'tests/test-*.php');
        $tests = [];
    
        foreach ($test_files as $file) {
            if (preg_match('/test-(\d+)\.php$/', basename($file), $matches)) {
                $test_id = $matches[1]; // Extragem doar numărul de ID din numele fișierului
                $tests[$test_id] = "Test #$test_id";
            }
        }
    
        ?>
        <div class="wrap">
            <h1>Setări PDF Cursant</h1>
    
            <!-- Afișează un mesaj de succes dacă acțiunea s-a executat -->
            <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
                <div class="updated notice is-dismissible"><p>Acțiunea a fost executată cu succes!</p></div>
            <?php endif; ?>
    
            <form method="post" action="options.php">
                <?php
                settings_fields('cursant_pdf_settings_group');
                do_settings_sections('cursant_pdf_settings');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="cursant_grupa">Grupa (Roluri disponibile)</label></th>
                        <td>
                            <select name="cursant_grupa" id="cursant_grupa">
                                <?php
                                $selected_grupa = get_option('cursant_grupa', '');
                                foreach ($roles as $role_key => $role) {
                                    $selected = ($selected_grupa == $role_key) ? 'selected' : '';
                                    echo "<option value='{$role_key}' {$selected}>{$role['name']}</option>";
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="test_activ">Test activ</label></th>
                        <td>
                            <select name="test_activ" id="test_activ">
                                <option value="">Selectează un test</option>
                                <?php
                                $selected_test = get_option('test_activ', '');
                                foreach ($tests as $test_id => $test_name) {
                                    $selected = ($selected_test == $test_id) ? 'selected' : '';
                                    echo "<option value='{$test_id}' {$selected}>{$test_name}</option>";
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="punctaj_intrebare">Punctaj per întrebare</label></th>
                        <td>
                            <input type="number" id="punctaj_intrebare" name="punctaj_intrebare" value="<?php echo esc_attr(get_option('punctaj_intrebare', '')); ?>" class="regular-text" step="0.1" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="punctaj_oficiu">Puncte din oficiu</label></th>
                        <td>
                            <input type="number" id="punctaj_oficiu" name="punctaj_oficiu" value="<?php echo esc_attr(get_option('punctaj_oficiu')); ?>" class="regular-text" step="1" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="success_rate">Rată success</label></th>
                        <td>
                            <input type="number" id="success_rate" name="success_rate" value="<?php echo esc_attr(get_option('success_rate')); ?>" class="regular-text" step="1" min="0" max="100"/>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>

            <!-- Afișare ultima generare -->
            <p><strong>Ultima generare:</strong> <?php echo esc_html($last_generated); ?></p>

            <!-- Formular pentru butonul care declanșează o acțiune -->
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <?php wp_nonce_field('cursant_pdf_action', 'cursant_pdf_nonce'); ?>
                <input type="hidden" name="action" value="cursant_pdf_generate_report">
                <input type="submit" class="button button-primary" value="Generează testele PDF">
            </form>
        </div>
        <?php
    }
    

    // Înregistrăm setările
    function cursant_pdf_register_settings() {
        register_setting('cursant_pdf_settings_group', 'cursant_grupa');
        register_setting('cursant_pdf_settings_group', 'punctaj_intrebare');
        register_setting('cursant_pdf_settings_group', 'punctaj_oficiu');
        register_setting('cursant_pdf_settings_group', 'test_activ');
        register_setting('cursant_pdf_settings_group', 'success_rate');
    }

    // Adăugăm meniul pentru setări
    function add_cursant_pdf_menu() {
        add_menu_page('Setări Generare PDF Cursanți', 'Setări PDF Cursanți', 'manage_options', 'cursant_pdf_settings', [ $this, 'cursant_pdf_settings_page' ]);
    }
}

$admin = new GeneratePdfAdmin();