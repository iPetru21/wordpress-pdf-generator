<?php

defined( 'ABSPATH' ) || exit;
define('CNP_SECRET_KEY', '63f4945d921d599f27ae4fdf5bada3f1');

class PDFGenerator {

    public $grupa;
    public $punctaj_intrebare;
    public $punctaj_oficiu;

    function __construct() {
        $this->grupa = get_option('cursant_grupa');
        $this->punctaj_intrebare = floatval( get_option( 'punctaj_intrebare', 1 ) );
        $this->punctaj_oficiu = floatval( get_option( 'punctaj_oficiu', 1 ) );

        if ( $this->punctaj_intrebare <= 0 ) {
            $this->punctaj_intrebare = 1.0;
        }
        if ( $this->punctaj_oficiu < 0 ) {
            $this->punctaj_oficiu = 0.0;
        }

        add_action( 'cursant_pdf_generate_report_run', [$this, 'generate'] );
        
    }

    function generate() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Nu ai permisiunea să accesezi această pagină.'));
        }

        global $wpdb;
        require WP_CF7_PDF_DIR . 'inc/lib/mpdf/vendor/autoload.php';

        // Obține utilizatorii în funcție de rolul selectat
        $users = get_users(['role' => $this->grupa]);

        if (empty($users)) {
            echo 'Nu există utilizatori cu rolul selectat.';
            return;
        }

        $test_data = $this->get_test_data();
        if ( $test_data === false ) {
            echo 'Selectați un template de test valid în pagina Generare PDF (sau configurați un test activ din fișiere).';
            return;
        }

        foreach ($users as $user) {
            $user_id = $user->ID;
            $cnp = get_user_meta($user_id, 'cnp', true);
            $id = $this->encrypt_cnp_to_id($cnp);
            $nota_minima = floatval(get_option('nota_minima', 8));
            $answers = $this->generate_random_answers($test_data['questions'], $nota_minima);
        
            $css = file_get_contents(plugin_dir_path(__DIR__) . 'assets/style.css');
            $upload_dir = wp_upload_dir();
        
            // Tipuri de fișiere de generat
            $tipuri = ['evaluare', 'examen'];
        
            foreach ($tipuri as $tip) {
                $test_data['examen']['type'] = $tip; // Setează tipul curent în test_data
        
                $mpdf = new \Mpdf\Mpdf();
                $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
        
                ob_start();
                include plugin_dir_path(__DIR__) . 'templates/pdf-template.php';
                $html = ob_get_clean();
                $mpdf->WriteHTML($html);
        
                $filename_label = get_option( 'cursant_pdf_filename_label', '' );
                $filename_part = $filename_label !== '' ? $filename_label : $test_data['examen']['title'];
                $pdf_filename = sanitize_file_name("/{$user->display_name}-{$filename_part}-{$tip}.pdf");
                $pdf_temp_path = $upload_dir['path'] . '/' . $pdf_filename;
        
                $mpdf->Output($pdf_temp_path, \Mpdf\Output\Destination::FILE);
        
                $attachment = array(
                    'guid'           => $upload_dir['url'] . '/' . $pdf_filename,
                    'post_mime_type' => 'application/pdf',
                    'post_title'     => "Test {$user->display_name} - {$tip} - Grupă: {$this->grupa}",
                    'post_content'   => '',
                    'post_status'    => 'inherit'
                );
        
                $attachment_id = wp_insert_attachment($attachment, $pdf_temp_path);
        
                if (!is_wp_error($attachment_id)) {
                    echo "Fișierul PDF pentru tipul <strong>{$tip}</strong> a fost adăugat ca atașament.<br>";
                } else {
                    echo "Eroare la crearea atașamentului pentru tipul <strong>{$tip}</strong>.<br>";
                }
            }
        }


        echo 'PDF-urile au fost generate.';
    }

    // Generarea răspunsurilor aleatorii pentru a obține o notă între nota_minima și 10
    function generate_random_answers($questions, $nota_minima = 8) {
        $answers = [];
        $options = ['a', 'b', 'c'];
        $total_questions = count($questions);
        $punctaj_oficiu = floatval($this->punctaj_oficiu);
        $punctaj_intrebare = floatval($this->punctaj_intrebare);

        // Calculăm intervalul de note posibile (între nota_minima și 10)
        $nota_maxima = 10.0;

        // Calculăm câte puncte trebuie să obțină din răspunsuri pentru nota minimă și maximă
        $min_score_needed = max(0, $nota_minima - $punctaj_oficiu);
        $max_score_needed = $nota_maxima - $punctaj_oficiu;

        // Calculăm câte răspunsuri corecte sunt necesare pentru a obține notele min și max
        $min_correct_answers = max(0, ceil($min_score_needed / $punctaj_intrebare));
        $max_correct_answers = min($total_questions, floor($max_score_needed / $punctaj_intrebare));
        
        // Asigurăm că avem un interval valid
        if ($min_correct_answers > $max_correct_answers) {
            $min_correct_answers = $max_correct_answers;
        }
        
        // Generăm un număr aleatoriu de răspunsuri corecte între min și max
        $target_correct_answers = rand($min_correct_answers, $max_correct_answers);
        
        // Generăm un array cu indecși aleatori pentru răspunsurile corecte
        $all_indices = range(0, $total_questions - 1);
        shuffle($all_indices);
        $correct_indices = array_slice($all_indices, 0, $target_correct_answers);
    
        // Generăm răspunsurile
        foreach ($questions as $index => $question) {
            $correct_answer = $question[1];
    
            if (in_array($index, $correct_indices)) {
                // Răspuns corect
                $answers[$index] = $correct_answer;
            } else {
                // Răspuns greșit - alegem aleatoriu unul din răspunsurile greșite
                $wrong_options = array_diff($options, [$correct_answer]);
                $reindexed_wrong_options = array_values($wrong_options);
                $answers[$index] = $reindexed_wrong_options[array_rand($reindexed_wrong_options)];
            }
        }
    
        return $answers;
    }    

    /**
     * Load test data: from CPT template if one is selected, otherwise from file (test_activ).
     *
     * @return array|false Same structure as include_selected_test(), or false on failure.
     */
    function get_test_data() {
        $template_id = get_option( 'cursant_pdf_template_id', 0 );
        if ( $template_id > 0 && class_exists( 'PDF_Test_Template_CPT' ) ) {
            $data = PDF_Test_Template_CPT::get_test_data_from_template( $template_id );
            if ( $data !== false ) {
                return $data;
            }
        }
        return $this->include_selected_test();
    }

    function include_selected_test() {
        $test_id = get_option('test_activ');

        if (!empty($test_id)) {
            $test_file = plugin_dir_path(__DIR__) . "/tests/test-{$test_id}.php";
    
            if (file_exists($test_file)) {
                include_once $test_file;
                return [
                    'questions' => $questions,
                    'options' => $options,
                    'commission' => $commission,
                    'examen' => $examen
                ];
            }
        }

        return false;
    }

    // 🔒 Criptare CNP într-un ID numeric
    function encrypt_cnp_to_id($cnp) {
        $key = CNP_SECRET_KEY;
        
        // 1. Hash SHA256 pentru siguranță
        $hash = hash_hmac('sha256', $cnp, $key);

        // 2. Convertim hash-ul într-un număr (folosind doar cifre)
        $numeric_id = base_convert(substr($hash, 0, 10), 16, 10); 

        return $numeric_id; // Returnează un ID numeric scurt
    }

    // 🔑 Decriptare CNP din ID numeric
    function decrypt_cnp_from_id($numeric_id, $original_cnp_list) {
        foreach ($original_cnp_list as $cnp) {
            if ($this->encrypt_cnp_to_id($cnp) === $numeric_id) {
                return $cnp; // Găsit în lista originală
            }
        }
        return null; // Dacă nu se găsește
    }
}

$generator = new PDFGenerator();
