<?php

defined( 'ABSPATH' ) || exit;

/**
 * Custom Post Type: PDF Test Template
 * Stores exam title, link, type, commission, and questions with options + correct answer.
 */
class PDF_Test_Template_CPT {

	const POST_TYPE = 'pdf_test_template';

	const META_EXAMEN_LINK   = '_pdf_template_examen_link';
	const META_EXAMEN_TYPE   = '_pdf_template_examen_type';
	const META_COMMISSION   = '_pdf_template_commission';
	const META_QUESTIONS    = '_pdf_template_questions';

	public function __construct() {
		add_action( 'init', [ $this, 'register_post_type' ] );
		add_action( 'admin_menu', [ $this, 'add_import_submenu' ], 20 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_list_page_import_button' ], 10 );
		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
		add_action( 'save_post_' . self::POST_TYPE, [ $this, 'save_meta' ], 10, 2 );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', [ $this, 'list_columns' ] );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', [ $this, 'list_column_content' ], 10, 2 );
	}

	/**
	 * Add "Import template-uri" under the main PDF Cursanți menu (admin.php) so the page loads correctly.
	 * The submenu under edit.php?post_type=... can cause "Sorry, you are not allowed" on some setups.
	 */
	public function add_import_submenu() {
		add_submenu_page(
			'cursant_pdf',
			'Import template-uri',
			'Import template-uri',
			'manage_options',
			'pdf_template_import',
			[ $this, 'render_import_page' ]
		);
	}

	/**
	 * On the Template-uri Test PDF list page, add an "Import" button next to "Adaugă template" (like WooCommerce Products).
	 */
	public function enqueue_list_page_import_button( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen || $screen->id !== 'edit-' . self::POST_TYPE ) {
			return;
		}
		$import_url = add_query_arg( 'page', 'pdf_template_import', admin_url( 'admin.php' ) );
		wp_add_inline_script( 'jquery', sprintf(
			'(function($){ $(function(){ var btn = $("<a></a>").attr("href", %s).addClass("page-title-action").text("Import"); $(".page-title-action").first().after(" ").after(btn); }); })(jQuery);',
			wp_json_encode( $import_url )
		) );
	}

	public function register_post_type() {
		register_post_type( self::POST_TYPE, [
			'labels' => [
				'name'               => 'Template-uri Test PDF',
				'singular_name'      => 'Template Test PDF',
				'add_new'            => 'Adaugă template',
				'add_new_item'       => 'Adaugă template nou',
				'edit_item'          => 'Editează template',
				'new_item'           => 'Template nou',
				'view_item'          => 'Vezi template',
				'search_items'       => 'Caută template-uri',
				'not_found'          => 'Niciun template găsit',
				'not_found_in_trash' => 'Niciun template în coș',
			],
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => 'cursant_pdf',
			'menu_position' => 5,
			'menu_icon'    => 'dashicons-media-document',
			'supports'     => [ 'title' ],
			'capability_type' => 'post',
			'capabilities' => [
				'create_posts' => 'manage_options',
			],
			'map_meta_cap' => true,
		] );
	}

	public function add_meta_boxes() {
		add_meta_box(
			'pdf_template_examen',
			'Setări examen',
			[ $this, 'render_examen_meta_box' ],
			self::POST_TYPE,
			'normal'
		);
		add_meta_box(
			'pdf_template_commission',
			'Comisie',
			[ $this, 'render_commission_meta_box' ],
			self::POST_TYPE,
			'normal'
		);
		add_meta_box(
			'pdf_template_questions',
			'Întrebări și răspunsuri',
			[ $this, 'render_questions_meta_box' ],
			self::POST_TYPE,
			'normal'
		);
	}

	public function render_examen_meta_box( $post ) {
		wp_nonce_field( 'pdf_template_examen', 'pdf_template_examen_nonce' );
		$link = get_post_meta( $post->ID, self::META_EXAMEN_LINK, true );
		$type = get_post_meta( $post->ID, self::META_EXAMEN_TYPE, true );
		if ( $type === '' ) {
			$type = 'examen';
		}
		?>
		<p>
			<label for="pdf_template_examen_link"><strong>Link test:</strong></label><br>
			<input type="url" id="pdf_template_examen_link" name="pdf_template_examen_link" value="<?php echo esc_attr( $link ); ?>" class="large-text" placeholder="<?php echo esc_attr( site_url( 'examen-...' ) ); ?>">
		</p>
		<p>
			<label for="pdf_template_examen_type"><strong>Tip:</strong></label>
			<select id="pdf_template_examen_type" name="pdf_template_examen_type">
				<option value="examen" <?php selected( $type, 'examen' ); ?>>Examen</option>
				<option value="evaluare" <?php selected( $type, 'evaluare' ); ?>>Evaluare</option>
			</select>
		</p>
		<p class="description">Titlul examenului este titlul acestui template (câmpul de mai sus).</p>
		<?php
	}

	public function render_commission_meta_box( $post ) {
		wp_nonce_field( 'pdf_template_commission', 'pdf_template_commission_nonce' );
		$commission = get_post_meta( $post->ID, self::META_COMMISSION, true );
		if ( is_string( $commission ) ) {
			$commission = json_decode( $commission, true );
		}
		if ( ! is_array( $commission ) ) {
			$commission = [ '', '', '' ];
		}
		?>
		<p class="description">Câte un membru pe linie (ex: Membru 1 - NUME, Președinte - NUME).</p>
		<?php
		foreach ( $commission as $i => $member ) :
			$idx = $i + 1;
			?>
			<p>
				<label for="pdf_template_commission_<?php echo (int) $i; ?>">Membru <?php echo (int) $idx; ?>:</label><br>
				<input type="text" id="pdf_template_commission_<?php echo (int) $i; ?>" name="pdf_template_commission[]" value="<?php echo esc_attr( $member ); ?>" class="large-text">
			</p>
		<?php endforeach; ?>
		<p><button type="button" class="button" id="pdf_template_add_commission">+ Adaugă membru comisie</button></p>
		<script>
		(function(){
			document.getElementById('pdf_template_add_commission').addEventListener('click', function(){
				var wrap = this.previousElementSibling;
				if (!wrap) wrap = this.parentElement;
				var count = wrap.querySelectorAll('input[name="pdf_template_commission[]"]').length;
				var p = document.createElement('p');
				p.innerHTML = '<label>Membru ' + (count + 1) + ':</label><br><input type="text" name="pdf_template_commission[]" value="" class="large-text">';
				this.parentElement.insertBefore(p, this);
			});
		})();
		</script>
		<?php
	}

	public function render_questions_meta_box( $post ) {
		wp_nonce_field( 'pdf_template_questions', 'pdf_template_questions_nonce' );
		$questions = get_post_meta( $post->ID, self::META_QUESTIONS, true );
		if ( is_string( $questions ) ) {
			$questions = json_decode( $questions, true );
		}
		if ( ! is_array( $questions ) ) {
			$questions = [
				[ 'text' => '', 'options' => [ 'a' => '', 'b' => '', 'c' => '' ], 'correct' => 'a' ],
			];
		}
		// Fix diacritics stored as u021b etc. (from old imports or DB encoding)
		$questions = self::decode_unicode_escapes_in_data( $questions );
		$labels = [ 'a' => 'A', 'b' => 'B', 'c' => 'C' ];
		?>
		<p class="description">Adaugă întrebări. Pentru fiecare întrebare, completați variantele A, B, C și selectați <strong>răspunsul corect</strong>.</p>
		<div id="pdf_template_questions_list">
			<?php foreach ( $questions as $qindex => $q ) :
				$opt = isset( $q['options'] ) && is_array( $q['options'] ) ? $q['options'] : [ 'a' => '', 'b' => '', 'c' => '' ];
				$correct = isset( $q['correct'] ) && in_array( $q['correct'], [ 'a', 'b', 'c' ], true ) ? $q['correct'] : 'a';
				$text = isset( $q['text'] ) ? $q['text'] : '';
			?>
				<div class="pdf-template-question-block" style="border:1px solid #ccc; padding:12px; margin-bottom:12px; background:#f9f9f9;">
					<p>
						<label><strong>Întrebare <?php echo (int) ( $qindex + 1 ); ?></strong></label><br>
						<textarea name="pdf_template_questions[<?php echo (int) $qindex; ?>][text]" rows="2" class="large-text" placeholder="Text întrebare"><?php echo esc_textarea( $text ); ?></textarea>
					</p>
					<table class="widefat" style="max-width:600px;">
						<?php foreach ( [ 'a', 'b', 'c' ] as $key ) : ?>
							<tr>
								<td style="width:80px;"><strong>Varianta <?php echo esc_html( $labels[ $key ] ); ?>:</strong></td>
								<td>
									<label style="display:flex;align-items:center;gap:8px;">
										<input type="radio" name="pdf_template_questions[<?php echo (int) $qindex; ?>][correct]" value="<?php echo esc_attr( $key ); ?>" <?php checked( $correct, $key ); ?>>
										<span>Răspuns corect</span>
									</label>
								</td>
								<td>
									<input type="text" name="pdf_template_questions[<?php echo (int) $qindex; ?>][options][<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( isset( $opt[ $key ] ) ? $opt[ $key ] : '' ); ?>" class="large-text" placeholder="Text varianta <?php echo esc_attr( $labels[ $key ] ); ?>">
								</td>
							</tr>
						<?php endforeach; ?>
					</table>
					<p style="margin-top:8px;">
						<button type="button" class="button button-small pdf_template_remove_question">Șterge întrebarea</button>
					</p>
				</div>
			<?php endforeach; ?>
		</div>
		<p><button type="button" class="button button-primary" id="pdf_template_add_question">+ Adaugă întrebare</button></p>

		<script>
		(function(){
			var list = document.getElementById('pdf_template_questions_list');
			function nextQuestionIndex() {
				var max = -1;
				list.querySelectorAll('textarea[name^="pdf_template_questions["]').forEach(function(t){
					var m = t.name.match(/pdf_template_questions\[(\d+)\]/);
					if (m) max = Math.max(max, parseInt(m[1], 10));
				});
				return max + 1;
			}
			function addQuestion() {
				var qIndex = nextQuestionIndex();
				var block = document.createElement('div');
				block.className = 'pdf-template-question-block';
				block.style.cssText = 'border:1px solid #ccc; padding:12px; margin-bottom:12px; background:#f9f9f9;';
				block.innerHTML =
					'<p><label><strong>Întrebare ' + (list.querySelectorAll('.pdf-template-question-block').length + 1) + '</strong></label><br><textarea name="pdf_template_questions[' + qIndex + '][text]" rows="2" class="large-text" placeholder="Text întrebare"></textarea></p>' +
					'<table class="widefat" style="max-width:600px;">' +
					'<tr><td style="width:80px;"><strong>Varianta A:</strong></td><td><label style="display:flex;align-items:center;gap:8px;"><input type="radio" name="pdf_template_questions[' + qIndex + '][correct]" value="a" checked> <span>Răspuns corect</span></label></td><td><input type="text" name="pdf_template_questions[' + qIndex + '][options][a]" value="" class="large-text" placeholder="Text varianta A"></td></tr>' +
					'<tr><td><strong>Varianta B:</strong></td><td><label style="display:flex;align-items:center;gap:8px;"><input type="radio" name="pdf_template_questions[' + qIndex + '][correct]" value="b"> <span>Răspuns corect</span></label></td><td><input type="text" name="pdf_template_questions[' + qIndex + '][options][b]" value="" class="large-text" placeholder="Text varianta B"></td></tr>' +
					'<tr><td><strong>Varianta C:</strong></td><td><label style="display:flex;align-items:center;gap:8px;"><input type="radio" name="pdf_template_questions[' + qIndex + '][correct]" value="c"> <span>Răspuns corect</span></label></td><td><input type="text" name="pdf_template_questions[' + qIndex + '][options][c]" value="" class="large-text" placeholder="Text varianta C"></td></tr>' +
					'</table>' +
					'<p style="margin-top:8px;"><button type="button" class="button button-small pdf_template_remove_question">Șterge întrebarea</button></p>';
				list.appendChild(block);
				block.querySelector('.pdf_template_remove_question').addEventListener('click', function(){ block.remove(); renumberQuestions(); });
				renumberQuestions();
			}
			function renumberQuestions() {
				var blocks = list.querySelectorAll('.pdf-template-question-block');
				blocks.forEach(function(b, i){
					var lbl = b.querySelector('label strong');
					if (lbl) lbl.textContent = 'Întrebare ' + (i + 1);
				});
			}
			document.getElementById('pdf_template_add_question').addEventListener('click', addQuestion);
			list.querySelectorAll('.pdf_template_remove_question').forEach(function(btn){
				btn.addEventListener('click', function(){ btn.closest('.pdf-template-question-block').remove(); renumberQuestions(); });
			});
		})();
		</script>
		<?php
	}

	public function save_meta( $post_id, $post ) {
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['pdf_template_examen_nonce'] ) && wp_verify_nonce( $_POST['pdf_template_examen_nonce'], 'pdf_template_examen' ) ) {
			update_post_meta( $post_id, self::META_EXAMEN_LINK, esc_url_raw( $_POST['pdf_template_examen_link'] ?? '' ) );
			$type = isset( $_POST['pdf_template_examen_type'] ) ? sanitize_text_field( $_POST['pdf_template_examen_type'] ) : 'examen';
			if ( in_array( $type, [ 'examen', 'evaluare' ], true ) ) {
				update_post_meta( $post_id, self::META_EXAMEN_TYPE, $type );
			}
		}

		if ( isset( $_POST['pdf_template_commission_nonce'] ) && wp_verify_nonce( $_POST['pdf_template_commission_nonce'], 'pdf_template_commission' ) ) {
			$commission = isset( $_POST['pdf_template_commission'] ) && is_array( $_POST['pdf_template_commission'] ) ? array_map( 'sanitize_text_field', $_POST['pdf_template_commission'] ) : [];
			$commission = array_values( array_filter( $commission ) );
			if ( empty( $commission ) ) {
				$commission = [ '' ];
			}
			update_post_meta( $post_id, self::META_COMMISSION, wp_json_encode( $commission, JSON_UNESCAPED_UNICODE ) );
		}

		if ( isset( $_POST['pdf_template_questions_nonce'] ) && wp_verify_nonce( $_POST['pdf_template_questions_nonce'], 'pdf_template_questions' ) ) {
			$raw = isset( $_POST['pdf_template_questions'] ) && is_array( $_POST['pdf_template_questions'] ) ? $_POST['pdf_template_questions'] : [];
			$questions = [];
			foreach ( $raw as $q ) {
				$text = isset( $q['text'] ) ? sanitize_textarea_field( $q['text'] ) : '';
				$options = [
					'a' => isset( $q['options']['a'] ) ? sanitize_text_field( $q['options']['a'] ) : '',
					'b' => isset( $q['options']['b'] ) ? sanitize_text_field( $q['options']['b'] ) : '',
					'c' => isset( $q['options']['c'] ) ? sanitize_text_field( $q['options']['c'] ) : '',
				];
				$correct = isset( $q['correct'] ) && in_array( $q['correct'], [ 'a', 'b', 'c' ], true ) ? $q['correct'] : 'a';
				$questions[] = [ 'text' => $text, 'options' => $options, 'correct' => $correct ];
			}
			$questions = array_values( array_filter( $questions, function ( $q ) { return $q['text'] !== ''; } ) );
			update_post_meta( $post_id, self::META_QUESTIONS, wp_json_encode( $questions, JSON_UNESCAPED_UNICODE ) );
		}
	}

	public function list_columns( $columns ) {
		$new = [];
		$new['cb'] = $columns['cb'];
		$new['title'] = $columns['title'];
		$new['questions_count'] = 'Întrebări';
		$new['date'] = $columns['date'];
		return $new;
	}

	public function list_column_content( $column, $post_id ) {
		if ( $column === 'questions_count' ) {
			$q = get_post_meta( $post_id, self::META_QUESTIONS, true );
			if ( is_string( $q ) ) {
				$q = json_decode( $q, true );
			}
			echo is_array( $q ) ? count( $q ) : 0;
		}
	}

	/**
	 * Import page: upload JSON file with one or more templates.
	 */
	public function render_import_page() {
		$message = '';
		$message_type = '';

		if ( $_SERVER['REQUEST_METHOD'] === 'POST' && ! empty( $_POST['pdf_template_import_nonce'] ) ) {
			if ( ! wp_verify_nonce( $_POST['pdf_template_import_nonce'], 'pdf_template_import' ) ) {
				$message = 'Eroare de securitate. Încercați din nou.';
				$message_type = 'error';
			} elseif ( empty( $_FILES['import_file']['tmp_name'] ) || ! is_uploaded_file( $_FILES['import_file']['tmp_name'] ) ) {
				$message = 'Selectați un fișier JSON.';
				$message_type = 'error';
			} else {
				$result = $this->process_import( $_FILES['import_file']['tmp_name'] );
				if ( $result['created'] > 0 ) {
					$message = sprintf(
						'Import reușit: %d template(e) create.',
						$result['created']
					);
					if ( ! empty( $result['errors'] ) ) {
						$message .= ' Erori: ' . implode( '; ', array_slice( $result['errors'], 0, 5 ) );
						if ( count( $result['errors'] ) > 5 ) {
							$message .= ' ... (+' . ( count( $result['errors'] ) - 5 ) . ' erori)';
						}
					}
					$message_type = empty( $result['errors'] ) ? 'success' : 'warning';
				} else {
					$message = ! empty( $result['errors'] ) ? implode( ' ', $result['errors'] ) : 'Fișierul JSON nu conține template-uri valide.';
					$message_type = 'error';
				}
			}
		}
		?>
		<div class="wrap">
			<h1>Import template-uri din JSON</h1>
			<p class="description">
				Încărcați un fișier JSON cu unul sau mai multe template-uri. Format așteptat: un array de obiecte, fiecare cu
				<code>title</code>, <code>examen_link</code>, <code>examen_type</code>, <code>commission</code> (array de string-uri),
				<code>questions</code> (array de obiecte cu <code>text</code>, <code>options</code> { "a", "b", "c" }, <code>correct</code> "a"|"b"|"c").
			</p>
			<?php if ( $message ) : ?>
				<div class="notice notice-<?php echo esc_attr( $message_type ); ?> is-dismissible">
					<p><?php echo esc_html( $message ); ?></p>
				</div>
			<?php endif; ?>
			<form method="post" enctype="multipart/form-data" action="">
				<?php wp_nonce_field( 'pdf_template_import', 'pdf_template_import_nonce' ); ?>
				<table class="form-table">
					<tr>
						<th><label for="import_file">Fișier JSON</label></th>
						<td>
							<input type="file" name="import_file" id="import_file" accept=".json,application/json" required />
						</td>
					</tr>
				</table>
				<?php submit_button( 'Importă template-uri' ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Convert one \uXXXX hex code to UTF-8 character (safe fallback if mbstring missing).
	 *
	 * @param string $hex 4 hex digits.
	 * @return string
	 */
	private static function unicode_hex_to_utf8( $hex ) {
		$codepoint = (int) hexdec( $hex );
		if ( $codepoint <= 0 ) {
			return '';
		}
		if ( $codepoint < 0x80 ) {
			return chr( $codepoint );
		}
		if ( function_exists( 'mb_convert_encoding' ) ) {
			$utf8 = @mb_convert_encoding( pack( 'n', $codepoint ), 'UTF-8', 'UTF-16BE' );
			if ( $utf8 !== false && $utf8 !== '' ) {
				return $utf8;
			}
		}
		// Fallback: use JSON decode for a single escape
		$decoded = json_decode( '"\\u' . $hex . '"' );
		return is_string( $decoded ) ? $decoded : '';
	}

	/**
	 * Convert literal \uXXXX or uXXXX (missing backslash) sequences to UTF-8 characters (fixes diacritics).
	 *
	 * @param string $str String that may contain \u0219 or u0219.
	 * @return string
	 */
	private static function decode_unicode_escapes( $str ) {
		if ( ! is_string( $str ) || $str === '' ) {
			return $str;
		}
		// With backslash: \u0219
		$str = preg_replace_callback( '/\\\\u([0-9a-fA-F]{4})/', function ( $m ) {
			return self::unicode_hex_to_utf8( $m[1] );
		}, $str );
		// Without backslash (corrupted): u0219, u021b, u00ee – u + exactly 4 hex digits (no lookahead so "u021bie" → "ție")
		$str = preg_replace_callback( '/(?<![\x5C])u([0-9a-fA-F]{4})/', function ( $m ) {
			return self::unicode_hex_to_utf8( $m[1] );
		}, $str );
		return $str;
	}

	/**
	 * Recursively apply decode_unicode_escapes to all string values in an array or object.
	 *
	 * @param array $data Decoded JSON data.
	 * @return array
	 */
	private static function decode_unicode_escapes_in_data( $data ) {
		if ( is_string( $data ) ) {
			return self::decode_unicode_escapes( $data );
		}
		if ( is_array( $data ) ) {
			$out = [];
			foreach ( $data as $k => $v ) {
				$out[ $k ] = self::decode_unicode_escapes_in_data( $v );
			}
			return $out;
		}
		return $data;
	}

	/**
	 * Process uploaded JSON and create template posts.
	 *
	 * @param string $file_path Temporary path to uploaded file.
	 * @return array{ created: int, errors: string[] }
	 */
	public function process_import( $file_path ) {
		$created = 0;
		$errors = [];
		$json = file_get_contents( $file_path );
		if ( $json === false ) {
			return [ 'created' => 0, 'errors' => [ 'Nu s-a putut citi fișierul.' ] ];
		}
		// Ensure UTF-8: use only encodings that exist on this system (Windows-1250/ISO-8859-2 may be missing)
		if ( function_exists( 'mb_detect_encoding' ) && function_exists( 'mb_convert_encoding' ) && function_exists( 'mb_list_encodings' ) ) {
			$allowed = array_intersect( [ 'UTF-8', 'ISO-8859-1', 'ISO-8859-2', 'Windows-1250', 'CP1250' ], mb_list_encodings() );
			if ( ! empty( $allowed ) ) {
				$enc = @mb_detect_encoding( $json, array_values( $allowed ), true );
				if ( is_string( $enc ) && $enc !== '' && $enc !== 'UTF-8' ) {
					$converted = @mb_convert_encoding( $json, 'UTF-8', $enc );
					if ( is_string( $converted ) ) {
						$json = $converted;
					}
				}
			}
		}
		$data = json_decode( $json, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return [ 'created' => 0, 'errors' => [ 'JSON invalid: ' . json_last_error_msg() ] ];
		}
		// Fix diacritics: convert literal \uXXXX in strings to actual UTF-8 characters
		$data = self::decode_unicode_escapes_in_data( $data );
		// Accept both single object and array
		if ( isset( $data['title'] ) || isset( $data['questions'] ) ) {
			$data = [ $data ];
		}
		if ( ! is_array( $data ) ) {
			return [ 'created' => 0, 'errors' => [ 'Fișierul trebuie să conțină un array de template-uri.' ] ];
		}
		foreach ( $data as $index => $item ) {
			if ( ! is_array( $item ) ) {
				$errors[] = "Template {$index}: nu este un obiect.";
				continue;
			}
			$title = isset( $item['title'] ) ? sanitize_text_field( $item['title'] ) : '';
			if ( $title === '' ) {
				$errors[] = "Template {$index}: lipsește sau este gol câmpul 'title'.";
				continue;
			}
			$questions = isset( $item['questions'] ) && is_array( $item['questions'] ) ? $item['questions'] : [];
			$normalized_questions = [];
			foreach ( $questions as $q ) {
				if ( ! is_array( $q ) ) {
					continue;
				}
				$text = isset( $q['text'] ) ? sanitize_textarea_field( $q['text'] ) : '';
				if ( $text === '' ) {
					continue;
				}
				$opt = isset( $q['options'] ) && is_array( $q['options'] ) ? $q['options'] : [];
				$options = [
					'a' => isset( $opt['a'] ) ? sanitize_text_field( $opt['a'] ) : '',
					'b' => isset( $opt['b'] ) ? sanitize_text_field( $opt['b'] ) : '',
					'c' => isset( $opt['c'] ) ? sanitize_text_field( $opt['c'] ) : '',
				];
				$correct = isset( $q['correct'] ) && in_array( $q['correct'], [ 'a', 'b', 'c' ], true ) ? $q['correct'] : 'a';
				$normalized_questions[] = [ 'text' => $text, 'options' => $options, 'correct' => $correct ];
			}
			if ( empty( $normalized_questions ) ) {
				$errors[] = "Template \"{$title}\": nu are întrebări valide.";
				continue;
			}
			$link = isset( $item['examen_link'] ) ? esc_url_raw( $item['examen_link'] ) : '';
			$type = isset( $item['examen_type'] ) && in_array( $item['examen_type'], [ 'examen', 'evaluare' ], true ) ? $item['examen_type'] : 'examen';
			$commission = isset( $item['commission'] ) && is_array( $item['commission'] ) ? array_map( 'sanitize_text_field', $item['commission'] ) : [];
			$commission = array_values( array_filter( $commission ) );
			if ( empty( $commission ) ) {
				$commission = [ '' ];
			}
			$post_id = wp_insert_post( [
				'post_title'   => $title,
				'post_type'   => self::POST_TYPE,
				'post_status' => 'publish',
				'post_author' => get_current_user_id(),
			] );
			if ( is_wp_error( $post_id ) ) {
				$errors[] = "Template \"{$title}\": " . $post_id->get_error_message();
				continue;
			}
			update_post_meta( $post_id, self::META_EXAMEN_LINK, $link );
			update_post_meta( $post_id, self::META_EXAMEN_TYPE, $type );
			update_post_meta( $post_id, self::META_COMMISSION, wp_json_encode( $commission, JSON_UNESCAPED_UNICODE ) );
			update_post_meta( $post_id, self::META_QUESTIONS, wp_json_encode( $normalized_questions, JSON_UNESCAPED_UNICODE ) );
			$created++;
		}
		return [ 'created' => $created, 'errors' => $errors ];
	}

	/**
	 * Get test data in the format expected by PDFGenerator and pdf-template.php
	 *
	 * @param int $post_id Template post ID.
	 * @return array{ questions: array, options: array, commission: array, examen: array }|false
	 */
	public static function get_test_data_from_template( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || $post->post_type !== self::POST_TYPE || $post->post_status !== 'publish' ) {
			return false;
		}

		$link = get_post_meta( $post_id, self::META_EXAMEN_LINK, true );
		$type = get_post_meta( $post_id, self::META_EXAMEN_TYPE, true );
		if ( $type === '' ) {
			$type = 'examen';
		}

		$commission = get_post_meta( $post_id, self::META_COMMISSION, true );
		if ( is_string( $commission ) ) {
			$commission = json_decode( $commission, true );
		}
		if ( ! is_array( $commission ) ) {
			$commission = [];
		}
		$commission = array_map( [ __CLASS__, 'decode_unicode_escapes' ], $commission );

		$questions_raw = get_post_meta( $post_id, self::META_QUESTIONS, true );
		if ( is_string( $questions_raw ) ) {
			$questions_raw = json_decode( $questions_raw, true );
		}
		if ( ! is_array( $questions_raw ) || empty( $questions_raw ) ) {
			return false;
		}

		$questions = [];
		$options = [];
		foreach ( $questions_raw as $q ) {
			$text = isset( $q['text'] ) ? self::decode_unicode_escapes( $q['text'] ) : '';
			$opt = isset( $q['options'] ) && is_array( $q['options'] ) ? $q['options'] : [ 'a' => '', 'b' => '', 'c' => '' ];
			$correct = isset( $q['correct'] ) && in_array( $q['correct'], [ 'a', 'b', 'c' ], true ) ? $q['correct'] : 'a';
			$questions[] = [ $text, $correct ];
			$options[] = [
				'a' => isset( $opt['a'] ) ? self::decode_unicode_escapes( $opt['a'] ) : '',
				'b' => isset( $opt['b'] ) ? self::decode_unicode_escapes( $opt['b'] ) : '',
				'c' => isset( $opt['c'] ) ? self::decode_unicode_escapes( $opt['c'] ) : '',
			];
		}

		$examen = [
			'title' => self::decode_unicode_escapes( $post->post_title ),
			'link'  => $link ? $link : site_url( '/' ),
			'type'  => $type,
		];

		return [
			'questions' => $questions,
			'options'   => $options,
			'commission' => $commission,
			'examen'    => $examen,
		];
	}
}
