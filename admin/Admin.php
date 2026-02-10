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
            } elseif ( empty( $_FILES['user_list_file']['tmp_name'] ) || ! is_uploaded_file( $_FILES['user_list_file']['tmp_name'] ) ) {
                $message = 'Încărcați un fișier .xlsx.';
                $message_type = 'error';
            } else {
                $role_source = isset( $_POST['import_role_source'] ) ? $_POST['import_role_source'] : 'existing';
                $file = $_FILES['user_list_file'];

                if ( $role_source === 'new' ) {
                    $new_role_name = isset( $_POST['import_role_new_name'] ) ? trim( sanitize_text_field( $_POST['import_role_new_name'] ) ) : '';
                    if ( $new_role_name === '' ) {
                        $message = 'Introduceți denumirea noului rol (grupă).';
                        $message_type = 'error';
                    } else {
                        $role = $this->ensure_import_role_exists( $new_role_name );
                        $result = $this->process_user_import( $file['tmp_name'], $role );
                        $message = $result['message'];
                        $message_type = $result['success'] ? 'success' : 'error';
                    }
                } else {
                    $role = isset( $_POST['import_role'] ) ? sanitize_text_field( $_POST['import_role'] ) : '';
                    if ( $role === '' || ! in_array( $role, array_keys( $all_roles ), true ) ) {
                        $message = 'Selectați grupa (rolul) pentru utilizatorii importați.';
                        $message_type = 'error';
                    } else {
                        $result = $this->process_user_import( $file['tmp_name'], $role );
                        $message = $result['message'];
                        $message_type = $result['success'] ? 'success' : 'error';
                    }
                }
            }
        }
        ?>
        <div class="wrap">
            <h1>Import utilizatori</h1>
            <p class="description">Încărcați un fișier Excel (.xlsx) în formatul listei de cursanți: antet cu <strong>CNP</strong>, <strong>NUME PRENUME</strong> (sau coloane separate nume/prenume). <strong>EMAIL</strong> și <strong>TELEFON</strong> sunt opționale. Dacă emailul lipsește, se generează unul automat (ex: import-CNP@import.local). Antetul poate fi pe orice rând (se detectează automat). Utilizatorii vor fi creați sau actualizați și li se va atribui rolul (grupa) selectat.</p>

            <?php if ( $message ) : ?>
                <div class="notice notice-<?php echo esc_attr( $message_type ); ?> is-dismissible">
                    <p><?php echo esc_html( $message ); ?></p>
                </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" action="" id="cursant-pdf-import-form">
                <?php wp_nonce_field( 'cursant_pdf_import_users', 'cursant_pdf_import_users_nonce' ); ?>
                <table class="form-table">
                    <tr>
                        <th><label>Grupa (rol):</label></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="radio" name="import_role_source" value="existing" id="import_role_source_existing" checked />
                                    Rol existent
                                </label>
                                <br />
                                <label>
                                    <input type="radio" name="import_role_source" value="new" id="import_role_source_new" />
                                    Rol nou (creează automat)
                                </label>
                            </fieldset>
                            <p class="description">Alegeți un rol deja existent sau introduceți denumirea unui rol nou care va fi creat.</p>
                        </td>
                    </tr>
                    <tr id="row_import_role_existing">
                        <th><label for="import_role">Selectează rolul:</label></th>
                        <td>
                            <select name="import_role" id="import_role">
                                <option value="">Selectează grupa</option>
                                <?php foreach ( $all_roles as $key => $r ) : ?>
                                    <option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $r['name'] ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr id="row_import_role_new" style="display: none;">
                        <th><label for="import_role_new_name">Denumire rol nou:</label></th>
                        <td>
                            <input type="text" name="import_role_new_name" id="import_role_new_name" class="regular-text" placeholder="ex: Cursant Seria 2 2026" value="" />
                            <p class="description">Rolul va fi creat cu permisiuni de tip Abonat (subscriber), apoi utilizatorii vor primi acest rol.</p>
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
            <script>
            (function(){
                var form = document.getElementById('cursant-pdf-import-form');
                if (!form) return;
                var existing = document.getElementById('import_role_source_existing');
                var newRole = document.getElementById('import_role_source_new');
                var rowExisting = document.getElementById('row_import_role_existing');
                var rowNew = document.getElementById('row_import_role_new');
                var selectRole = document.getElementById('import_role');
                var inputNewName = document.getElementById('import_role_new_name');
                function toggle() {
                    var isNew = newRole && newRole.checked;
                    if (rowExisting) rowExisting.style.display = isNew ? 'none' : '';
                    if (rowNew) rowNew.style.display = isNew ? '' : 'none';
                    if (selectRole) selectRole.required = !isNew;
                    if (inputNewName) inputNewName.required = isNew;
                }
                if (existing) existing.addEventListener('change', toggle);
                if (newRole) newRole.addEventListener('change', toggle);
                toggle();
            })();
            </script>
        </div>
        <?php
    }

    /**
     * Asigură că rolul există: dacă nu există, îl creează cu permisiuni de subscriber.
     * Returnează slug-ul rolului (pentru use în get_users etc.).
     *
     * @param string $display_name Denumirea afișată a rolului (ex: "Cursant Seria 2 2026").
     * @return string Slug-ul rolului (ex: "cursant-seria-2-2026").
     */
    function ensure_import_role_exists( $display_name ) {
        $slug = sanitize_title( $display_name );
        if ( empty( $slug ) ) {
            $slug = 'cursant-' . substr( uniqid( '', true ), -6 );
        }
        $role_object = get_role( $slug );
        if ( $role_object !== null ) {
            return $slug;
        }
        $subscriber = get_role( 'subscriber' );
        $capabilities = $subscriber ? $subscriber->capabilities : [ 'read' => true ];
        add_role( $slug, $display_name, $capabilities );
        return $slug;
    }

    /**
     * Procesează fișierul .xlsx și creează/actualizează utilizatori cu rolul dat.
     *
     * @param string $file_path Calea temporară la fișierul încărcat.
     * @param string $role      Rolul WordPress de atribuit (slug).
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

            // Detectare rând antet: conține "cnp" și cel puțin una dintre "email", "nume prenume", "nume"
            $header_row = $this->find_import_header_row( $sheet, $highest_row, $col_index );
            if ( $header_row === null ) {
                return [ 'success' => false, 'message' => 'Nu s-a găsit un rând de antet cu coloana CNP și nume (NUME PRENUME sau nume/prenume).' ];
            }

            $headers = $this->get_import_headers( $sheet, $header_row, $col_index );

            $email_col = null;
            $cnp_col = null;
            $nume_col = null;
            $prenume_col = null;
            $nume_prenume_col = null;
            $telefon_col = null;
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
                } elseif ( $name === 'telefon' ) {
                    $telefon_col = $col;
                }
            }

            if ( $cnp_col === null ) {
                return [ 'success' => false, 'message' => 'Lipsește coloana CNP în antet.' ];
            }
            $has_separate = ( $nume_col !== null && $prenume_col !== null );
            $has_combined = ( $nume_prenume_col !== null );
            if ( ! $has_separate && ! $has_combined ) {
                return [ 'success' => false, 'message' => 'Lipsește coloana NUME PRENUME sau coloanele separate nume / prenume.' ];
            }

            $created = 0;
            $updated = 0;
            $errors = [];
            $data_start = $header_row + 1;

            for ( $row = $data_start; $row <= $highest_row; $row++ ) {
                $email_raw = $email_col !== null ? $this->get_cell_value( $sheet, $email_col, $row ) : '';
                $cnp_raw   = $this->get_cell_value( $sheet, $cnp_col, $row );
                $email = sanitize_email( $email_raw );
                $cnp   = preg_replace( '/\D/', '', $cnp_raw );

                if ( ! empty( $cnp ) && strlen( $cnp ) !== 13 ) {
                    $errors[] = "Linia {$row}: CNP trebuie 13 cifre.";
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

                $telefon = '';
                if ( $telefon_col !== null ) {
                    $telefon = sanitize_text_field( $this->get_cell_value( $sheet, $telefon_col, $row ) );
                }

                // Nume utilizator (user_login) dedus din nume și prenume; dacă lipsesc, generat
                $login = $this->unique_login_from_name( $nume, $prenume, $cnp, $row );

                $has_valid_email = ( $email !== '' && is_email( $email ) );
                if ( ! $has_valid_email ) {
                    $email = $this->generate_import_email( $login );
                }

                $user = $this->find_import_user( $has_valid_email ? $email : null, $login, $cnp );
                if ( $user ) {
                    $user_id = $user->ID;
                    wp_update_user( [
                        'ID'         => $user_id,
                        'user_email' => $email,
                        'first_name' => $prenume,
                        'last_name'  => $nume,
                    ] );
                    update_user_meta( $user_id, 'cnp', $cnp );
                    if ( $telefon !== '' ) {
                        update_user_meta( $user_id, 'telefon', $telefon );
                    }
                    $user->set_role( $role );
                    $updated++;
                } else {
                    $user_id = wp_insert_user( [
                        'user_login'   => $login,
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
                    if ( $telefon !== '' ) {
                        update_user_meta( $user_id, 'telefon', $telefon );
                    }
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
     * Găsește rândul de antet: primul rând care conține "cnp" și cel puțin una dintre "email", "nume prenume", "nume".
     */
    private function find_import_header_row( $sheet, $highest_row, $col_index ) {
        $max_scan = min( 20, $highest_row );
        for ( $r = 1; $r <= $max_scan; $r++ ) {
            $values = [];
            for ( $c = 1; $c <= $col_index; $c++ ) {
                $val = $this->get_cell_value( $sheet, $c, $r );
                $values[] = preg_replace( '/\s+/', ' ', strtolower( trim( $val ) ) );
            }
            if ( ! in_array( 'cnp', $values, true ) ) {
                continue;
            }
            if ( in_array( 'email', $values, true ) || in_array( 'nume prenume', $values, true ) || in_array( 'nume', $values, true ) ) {
                return $r;
            }
        }
        return null;
    }

    /**
     * Generează un user_login unic din nume și prenume. Dacă ambele lipsesc, folosește un login generat.
     *
     * @param string $nume    Nume (familie).
     * @param string $prenume Prenume.
     * @param string $cnp     CNP (pentru fallback).
     * @param int    $row     Număr rând (pentru fallback).
     * @return string user_login unic.
     */
    private function unique_login_from_name( $nume, $prenume, $cnp, $row ) {
        $raw = trim( $prenume . ' ' . $nume );
        if ( $raw === '' ) {
            $gen = $this->generate_import_login_email( $cnp, $row );
            return $gen['login'];
        }
        $raw = str_replace( [ 'ă', 'â', 'î', 'ș', 'ț', 'Ă', 'Â', 'Î', 'Ș', 'Ț' ], [ 'a', 'a', 'i', 's', 't', 'a', 'a', 'i', 's', 't' ], $raw );
        $base = sanitize_user( str_replace( ' ', '-', $raw ), true );
        $base = preg_replace( '/[^a-z0-9\-_.]/', '-', strtolower( $base ) );
        $base = preg_replace( '/-+/', '-', trim( $base, '-' ) );
        if ( $base === '' ) {
            $gen = $this->generate_import_login_email( $cnp, $row );
            return $gen['login'];
        }
        $candidate = $base;
        $suffix = 0;
        while ( get_user_by( 'login', $candidate ) ) {
            $suffix++;
            $candidate = $base . '-' . $suffix;
        }
        return $candidate;
    }

    /**
     * Generează adresă de email pentru import (când nu e furnizată). Folosește login-ul existent.
     *
     * @param string $login user_login.
     * @return string Adresă email validă (login@import.local).
     */
    private function generate_import_email( $login ) {
        return $login . '@import.local';
    }

    /**
     * Generează login și email unice pentru import (fallback când nume/prenume lipsesc).
     *
     * @param string $cnp CNP (13 cifre) sau gol.
     * @param int    $row Număr rând (pentru unicitate).
     * @return array{ login: string, email: string }
     */
    private function generate_import_login_email( $cnp, $row ) {
        $base = 'import';
        if ( ! empty( $cnp ) && strlen( $cnp ) === 13 ) {
            $base .= '-' . $cnp;
        } else {
            $base .= '-row-' . $row . '-' . substr( uniqid( '', true ), -6 );
        }
        $login = $base;
        $suffix = 0;
        while ( get_user_by( 'login', $login ) || get_user_by( 'email', $login . '@import.local' ) ) {
            $suffix++;
            $login = $base . '-' . $suffix;
        }
        return [ 'login' => $login, 'email' => $login . '@import.local' ];
    }

    /**
     * Caută un utilizator existent pentru import: după email (dacă e valid), după login, sau după CNP.
     *
     * @param string|null $email_valid Email dacă a fost furnizat și valid, altfel null.
     * @param string      $login_or_email Login sau email (folosit pentru căutare când nu e email valid).
     * @param string      $cnp CNP.
     * @return WP_User|null
     */
    private function find_import_user( $email_valid, $login_or_email, $cnp ) {
        if ( $email_valid !== null && $email_valid !== '' ) {
            $user = get_user_by( 'email', $email_valid );
            if ( $user ) {
                return $user;
            }
        }
        $user = get_user_by( 'login', $login_or_email );
        if ( $user ) {
            return $user;
        }
        if ( ! empty( $cnp ) && strlen( $cnp ) === 13 ) {
            $users = get_users( [
                'meta_key'   => 'cnp',
                'meta_value' => $cnp,
                'number'     => 1,
            ] );
            if ( ! empty( $users ) ) {
                return $users[0];
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