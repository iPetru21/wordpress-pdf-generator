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
    }

	function cnp_settings_page() {
		// Obține toate rolurile
		global $wp_roles;
		$all_roles = $wp_roles->roles;

		$selected_role = $_POST['selected_role'] ?? '';
		$input_code = $_POST['input_code'] ?? '';
		$decrypted_cnp = '';
		$decrypted_user = null;

		if (!empty($selected_role) && !empty($input_code)) {
			$users = get_users(['role' => $selected_role]);

			$generator = new PDFGenerator();

			foreach ($users as $user) {
				$cnp = get_user_meta($user->ID, 'cnp', true);
				if (!empty($cnp)) {
					if ($generator->encrypt_cnp_to_id($cnp) === $input_code) {
						$decrypted_cnp = $cnp;
						$decrypted_user = $user;
						break;
					}
				}
			}
		}
		?>

		<div class="wrap">
			<h1>Decriptare CNP din Cod Numeric</h1>
			<form method="post">
				<table class="form-table">
					<tr>
						<th><label for="selected_role">Grupa (Rol):</label></th>
						<td>
							<select name="selected_role" id="selected_role">
								<option value="">Selectează un rol</option>
								<?php foreach ($all_roles as $key => $role): ?>
									<option value="<?php echo esc_attr($key); ?>" <?php selected($selected_role, $key); ?>>
										<?php echo esc_html($role['name']); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="input_code">Cod numeric criptat:</label></th>
						<td><input type="text" name="input_code" id="input_code" value="<?php echo esc_attr($input_code); ?>" /></td>
					</tr>
				</table>
				<?php submit_button('Decriptează'); ?>
			</form>

			<?php if (!empty($decrypted_cnp) && $decrypted_user): 
				$first_name = get_user_meta($decrypted_user->ID, 'first_name', true);
				$last_name = get_user_meta($decrypted_user->ID, 'last_name', true);
				$full_name = trim($first_name . ' ' . $last_name);
			?>
				<div class="notice notice-success">
					<p><strong>Utilizator găsit:</strong> <?php echo esc_html($full_name); ?></p>
					<p><strong>CNP decriptat:</strong> <?php echo esc_html($decrypted_cnp); ?></p>
				</div>
			<?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
				<div class="notice notice-error">
					<p>Codul nu a putut fi decriptat pentru rolul selectat.</p>
				</div>
			<?php endif; ?>
		</div>
		<?php
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

    // Înregistrăm setările
    function cursant_pdf_register_settings() {
        // Setări pagina Setări (GitHub Credentials)
        register_setting('cursant_pdf_settings_group', 'cursant_pdf_github_repo');
        register_setting('cursant_pdf_settings_group', 'cursant_pdf_github_branch');
        register_setting('cursant_pdf_settings_group', 'cursant_pdf_github_token');
        register_setting('cursant_pdf_settings_group', 'cursant_pdf_update_enabled', [
            'sanitize_callback' => function ($value) {
                return isset($_POST['cursant_pdf_update_enabled']) ? '1' : '';
            },
        ]);

    }

    // Adăugăm meniul pentru setări
    function add_cursant_pdf_menu() {
        add_menu_page(
            'PDF Cursanți',
            'PDF Cursanți',
            'manage_options',
            'cursant_pdf',
            [ $this, 'cursant_pdf_settings_page' ],
            'dashicons-pdf',
            30
        );
        add_submenu_page(
            'cursant_pdf',
            'Setări PDF Cursanți',
            'Setări',
            'manage_options',
            'cursant_pdf_settings',
            [ $this, 'cursant_pdf_settings_page' ]
        );
        add_submenu_page(
            'cursant_pdf',
            'Decriptare CNP',
            'Decriptare CNP',
            'manage_options',
            'decriptare-cnp',
            [ $this, 'cnp_settings_page' ]
        );
        add_submenu_page(
            'cursant_pdf',
            'Import utilizatori',
            'Import utilizatori',
            'manage_options',
            'cursant_pdf_import_users',
            [ $this, 'import_users_page' ]
        );
    }

    /**
     * Pagina Import utilizatori: încarcă .xlsx, setează grupa (rol), importă utilizatori.
     */
    function import_users_page() {
        global $wp_roles;
        $all_roles = $wp_roles->roles;

        $message = '';
        $message_type = '';

        if ( $_SERVER['REQUEST_METHOD'] === 'POST' && ! empty( $_POST['cursant_pdf_import_users_nonce'] ) ) {
            if ( ! wp_verify_nonce( $_POST['cursant_pdf_import_users_nonce'], 'cursant_pdf_import_users' ) ) {
                $message = 'Eroare de securitate. Încercați din nou.';
                $message_type = 'error';
            } elseif ( empty( $_POST['import_role'] ) ) {
                $message = 'Selectați grupa (rolul) pentru utilizatorii importați.';
                $message_type = 'error';
            } elseif ( empty( $_FILES['user_list_file']['tmp_name'] ) || ! is_uploaded_file( $_FILES['user_list_file']['tmp_name'] ) ) {
                $message = 'Încărcați un fișier .xlsx.';
                $message_type = 'error';
            } else {
                $role = sanitize_text_field( $_POST['import_role'] );
                $file = $_FILES['user_list_file'];

                if ( ! in_array( $role, array_keys( $all_roles ), true ) ) {
                    $message = 'Rol invalid.';
                    $message_type = 'error';
                } else {
                    $result = $this->process_user_import( $file['tmp_name'], $role );
                    $message = $result['message'];
                    $message_type = $result['success'] ? 'success' : 'error';
                }
            }
        }
        ?>
        <div class="wrap">
            <h1>Import utilizatori</h1>
            <p class="description">Încărcați un fișier Excel (.xlsx) în formatul listei de cursanți: antet cu <strong>EMAIL</strong>, <strong>CNP</strong>, <strong>NUME PRENUME</strong> (sau coloane separate <strong>nume</strong> / <strong>prenume</strong>). Antetul poate fi pe orice rând (se detectează automat). Utilizatorii vor fi creați sau actualizați și li se va atribui rolul (grupa) selectat.</p>

            <?php if ( $message ) : ?>
                <div class="notice notice-<?php echo esc_attr( $message_type ); ?> is-dismissible">
                    <p><?php echo esc_html( $message ); ?></p>
                </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" action="">
                <?php wp_nonce_field( 'cursant_pdf_import_users', 'cursant_pdf_import_users_nonce' ); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="import_role">Grupa (rol):</label></th>
                        <td>
                            <select name="import_role" id="import_role" required>
                                <option value="">Selectează grupa</option>
                                <?php foreach ( $all_roles as $key => $role ) : ?>
                                    <option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $role['name'] ); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">Rolul WordPress care va fi atribuit tuturor utilizatorilor importați.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="user_list_file">Fișier .xlsx:</label></th>
                        <td>
                            <input type="file" name="user_list_file" id="user_list_file" accept=".xlsx" required />
                        </td>
                    </tr>
                </table>
                <?php submit_button( 'Importă utilizatori' ); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Procesează fișierul .xlsx și creează/actualizează utilizatori cu rolul dat.
     *
     * @param string $file_path Calea temporară la fișierul încărcat.
     * @param string $role      Rolul WordPress de atribuit.
     * @return array{ success: bool, message: string }
     */
    function process_user_import( $file_path, $role ) {
        if ( ! class_exists( 'PhpOffice\PhpSpreadsheet\IOFactory' ) ) {
            $plugin_root = dirname( plugin_dir_path( __FILE__ ) );
            require_once $plugin_root . '/vendor/autoload.php';
        }

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load( $file_path );
            $sheet = $spreadsheet->getActiveSheet();
            $highest_row = $sheet->getHighestRow();
            $highest_col = $sheet->getHighestColumn();
            $col_index = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString( $highest_col );

            if ( $highest_row < 2 ) {
                return [ 'success' => false, 'message' => 'Fișierul nu conține date (necesar antet + cel puțin o linie de date).' ];
            }

            // Detectare rând antet: primul rând care conține atât "email" cât și "cnp" (ignorăm majuscule)
            $header_row = $this->find_import_header_row( $sheet, $highest_row, $col_index );
            if ( $header_row === null ) {
                return [ 'success' => false, 'message' => 'Nu s-a găsit un rând de antet care să conțină coloanele EMAIL și CNP.' ];
            }

            $headers = $this->get_import_headers( $sheet, $header_row, $col_index );

            $email_col = null;
            $cnp_col = null;
            $nume_col = null;
            $prenume_col = null;
            $nume_prenume_col = null;
            foreach ( $headers as $col => $name ) {
                if ( $name === 'email' ) {
                    $email_col = $col;
                } elseif ( $name === 'cnp' ) {
                    $cnp_col = $col;
                } elseif ( $name === 'nume' ) {
                    $nume_col = $col;
                } elseif ( $name === 'prenume' ) {
                    $prenume_col = $col;
                } elseif ( $name === 'nume prenume' ) {
                    $nume_prenume_col = $col;
                }
            }

            if ( $email_col === null || $cnp_col === null ) {
                return [ 'success' => false, 'message' => 'Lipsește coloana EMAIL sau CNP în antet.' ];
            }
            $has_separate = ( $nume_col !== null && $prenume_col !== null );
            $has_combined = ( $nume_prenume_col !== null );
            if ( ! $has_separate && ! $has_combined ) {
                return [ 'success' => false, 'message' => 'Lipsește coloana NUME PRENUME sau coloanele separate nume / prenume.' ];
            }

            $created = 0;
            $updated = 0;
            $skipped = 0;
            $errors = [];
            $data_start = $header_row + 1;

            for ( $row = $data_start; $row <= $highest_row; $row++ ) {
                $email_raw = $this->get_cell_value( $sheet, $email_col, $row );
                $cnp_raw   = $this->get_cell_value( $sheet, $cnp_col, $row );
                $email = sanitize_email( $email_raw );
                $cnp   = preg_replace( '/\D/', '', $cnp_raw );

                if ( ! empty( $cnp ) && strlen( $cnp ) !== 13 ) {
                    $errors[] = "Linia {$row}: CNP trebuie 13 cifre.";
                    continue;
                }

                // Email poate fi gol în fișier (listă FPC); fără email valid nu putem crea utilizator
                if ( empty( $email ) || ! is_email( $email ) ) {
                    $skipped++;
                    continue;
                }

                if ( $has_separate ) {
                    $nume = sanitize_text_field( $this->get_cell_value( $sheet, $nume_col, $row ) );
                    $prenume = sanitize_text_field( $this->get_cell_value( $sheet, $prenume_col, $row ) );
                } else {
                    $nume_prenume = $this->get_cell_value( $sheet, $nume_prenume_col, $row );
                    list( $nume, $prenume ) = $this->split_nume_prenume( $nume_prenume );
                    $nume = sanitize_text_field( $nume );
                    $prenume = sanitize_text_field( $prenume );
                }

                $user = get_user_by( 'email', $email );
                if ( $user ) {
                    $user_id = $user->ID;
                    wp_update_user( [
                        'ID'         => $user_id,
                        'first_name' => $prenume,
                        'last_name'  => $nume,
                    ] );
                    update_user_meta( $user_id, 'cnp', $cnp );
                    $user->set_role( $role );
                    $updated++;
                } else {
                    $user_id = wp_insert_user( [
                        'user_login'   => $email,
                        'user_email'   => $email,
                        'first_name'   => $prenume,
                        'last_name'    => $nume,
                        'user_pass'    => wp_generate_password( 24, true ),
                        'role'         => $role,
                    ] );
                    if ( is_wp_error( $user_id ) ) {
                        $errors[] = "Linia {$row}: " . $user_id->get_error_message();
                        continue;
                    }
                    update_user_meta( $user_id, 'cnp', $cnp );
                    $created++;
                }
            }

            $parts = [];
            if ( $created > 0 ) {
                $parts[] = $created . ' utilizatori creați';
            }
            if ( $updated > 0 ) {
                $parts[] = $updated . ' actualizați';
            }
            if ( $skipped > 0 ) {
                $parts[] = $skipped . ' rânduri sărite (fără email valid)';
            }
            $msg = empty( $parts ) ? 'Niciun utilizator importat.' : implode( ', ', $parts ) . '.';
            if ( ! empty( $errors ) ) {
                $msg .= ' Erori: ' . implode( ' ', array_slice( $errors, 0, 5 ) );
                if ( count( $errors ) > 5 ) {
                    $msg .= ' ... (+' . ( count( $errors ) - 5 ) . ' erori)';
                }
            }
            return [ 'success' => true, 'message' => $msg ];
        } catch ( \Throwable $e ) {
            return [ 'success' => false, 'message' => 'Eroare la citirea fișierului: ' . $e->getMessage() ];
        }
    }

    /**
     * Găsește rândul de antet: primul rând care conține atât "email" cât și "cnp" (ignoră majuscule).
     */
    private function find_import_header_row( $sheet, $highest_row, $col_index ) {
        $max_scan = min( 20, $highest_row );
        for ( $r = 1; $r <= $max_scan; $r++ ) {
            $values = [];
            for ( $c = 1; $c <= $col_index; $c++ ) {
                $val = $this->get_cell_value( $sheet, $c, $r );
                $values[] = preg_replace( '/\s+/', ' ', strtolower( trim( $val ) ) );
            }
            if ( in_array( 'email', $values, true ) && in_array( 'cnp', $values, true ) ) {
                return $r;
            }
        }
        return null;
    }

    /**
     * Returnează antetul normalizat: col_index => 'email'|'cnp'|'nume'|'prenume'|'nume prenume'.
     */
    private function get_import_headers( $sheet, $header_row, $col_index ) {
        $out = [];
        for ( $c = 1; $c <= $col_index; $c++ ) {
            $val = $this->get_cell_value( $sheet, $c, $header_row );
            $norm = preg_replace( '/\s+/', ' ', strtolower( trim( $val ) ) );
            $out[ $c ] = $norm;
        }
        return $out;
    }

    /**
     * Desparte "NUME PRENUME" (ex: "BORLA I. CONSTANTIN - MIRCEA") în nume și prenume (după ultimul " - ").
     */
    private function split_nume_prenume( $nume_prenume ) {
        $s = trim( (string) $nume_prenume );
        if ( $s === '' ) {
            return [ '', '' ];
        }
        if ( preg_match( '/\s+-\s+/', $s ) ) {
            $parts = preg_split( '/\s+-\s+/', $s, -1, PREG_SPLIT_NO_EMPTY );
            $prenume = trim( array_pop( $parts ) );
            $nume = trim( implode( ' - ', $parts ) );
            return [ $nume, $prenume ];
        }
        return [ $s, '' ];
    }

    /**
     * Returnează valoarea unei celule ca string.
     */
    private function get_cell_value( $sheet, $col, $row ) {
        $coord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex( $col ) . $row;
        $cell = $sheet->getCell( $coord );
        $value = $cell->getFormattedValue();
        return $value !== null ? trim( (string) $value ) : '';
    }

    // Pagina Setări: GitHub Credentials + setări generale plugin
    function cursant_pdf_settings_page() {
        ?>
        <div class="wrap">
            <h1>Setări PDF Cursanți</h1>

            <form method="post" action="options.php">
                <?php
                settings_fields('cursant_pdf_settings_group');
                do_settings_sections('cursant_pdf_settings');
                ?>
                <h2>GitHub Credentials</h2>
                <p class="description">Configurează actualizările automate ale pluginului din GitHub.</p>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="cursant_pdf_github_repo">URL Repository GitHub</label></th>
                        <td>
                            <input type="url" id="cursant_pdf_github_repo" name="cursant_pdf_github_repo"
                                   value="<?php echo esc_attr(get_option('cursant_pdf_github_repo', 'https://github.com/username/repository-name')); ?>"
                                   class="regular-text" placeholder="https://github.com/username/repo-name" />
                            <p class="description">URL-ul repository-ului GitHub (ex: https://github.com/username/repository-name)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="cursant_pdf_github_branch">Branch</label></th>
                        <td>
                            <input type="text" id="cursant_pdf_github_branch" name="cursant_pdf_github_branch"
                                   value="<?php echo esc_attr(get_option('cursant_pdf_github_branch', 'main')); ?>"
                                   class="regular-text" placeholder="main" />
                            <p class="description">Branch-ul pentru update-uri (ex: main, master, develop)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="cursant_pdf_github_token">Token GitHub</label></th>
                        <td>
                            <input type="password" id="cursant_pdf_github_token" name="cursant_pdf_github_token"
                                   value="<?php echo esc_attr(get_option('cursant_pdf_github_token', '')); ?>"
                                   class="regular-text" autocomplete="new-password" />
                            <p class="description">Personal Access Token pentru repository-uri private. Lăsați gol pentru repository-uri publice.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="cursant_pdf_update_enabled">Activează update-uri</label></th>
                        <td>
                            <label>
                                <input type="checkbox" id="cursant_pdf_update_enabled" name="cursant_pdf_update_enabled"
                                       value="1" <?php checked(get_option('cursant_pdf_update_enabled', '1'), '1'); ?> />
                                Verifică actualizări din GitHub
                            </label>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

}

$admin = new GeneratePdfAdmin();