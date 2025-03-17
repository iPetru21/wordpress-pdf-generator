<?php

defined( 'ABSPATH' ) || exit;
define('CNP_SECRET_KEY', '63f4945d921d599f27ae4fdf5bada3f1');

class PDFGenerator {

    public $grupa;
    public $punctaj_intrebare;
    public $punctaj_oficiu;

    function __construct() {
        $this->grupa = get_option('cursant_grupa');
        $this->punctaj_intrebare = get_option('punctaj_intrebare');
        $this->punctaj_oficiu = get_option('punctaj_oficiu');

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

        $test_data = $this->include_selected_test();

        foreach ($users as $user) {
            $user_id = $user->ID;
            $cnp = get_user_meta($user_id, 'cnp', true);
            $id = $this->encrypt_cnp_to_id($cnp);
            $answers = $this->generate_random_answers($test_data['questions'], 90);

            $mpdf = new \Mpdf\Mpdf();
            
            //     $html .= "<h2>Nota finală: {$score}</h2>";
            $css = file_get_contents(plugin_dir_path(__DIR__) . 'assets/style.css');

            // Apply CSS styles
            $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);

            ob_start();
            include plugin_dir_path(__DIR__) . 'templates/pdf-template.php';
            $html = ob_get_clean();
            $mpdf->WriteHTML($html);

            $upload_dir = wp_upload_dir();
            $pdf_filename = sanitize_file_name("/{$user->display_name}-{$test_data['examen']['title']}.pdf");
            $pdf_temp_path = $upload_dir['path'] . $pdf_filename; // Calea fișierului PDF
            
            // Generează fișierul PDF
            $mpdf->Output($pdf_temp_path, \Mpdf\Output\Destination::FILE);
            
            // Creați un tablou de date pentru atașament
            $attachment = array(
                'guid'           => $upload_dir['url'] . $filename, // URL-ul fișierului
                'post_mime_type' => 'application/pdf', // Tipul MIME
                'post_title'     => "Test {$user->display_name} - Grupă: {$this->grupa}",
                'post_content'   => '',
                'post_status'    => 'inherit'
            );
            
            // Inserarea fișierului ca atașament
            $attachment_id = wp_insert_attachment($attachment, $pdf_temp_path);
            
            if (!is_wp_error($attachment_id)) {
                // Nu este nevoie de wp_generate_attachment_metadata() pentru fișiere PDF
                echo "Fișierul PDF a fost adăugat ca atașament.";
            } else {
                echo "A apărut o eroare la crearea atașamentului.";
            }
        }

        echo 'PDF-urile au fost generate.';
    }

    // Generarea răspunsurilor aleatorii
    function generate_random_answers($questions, $accuracy = 90) {
        $answers = [];
        foreach ($questions as $index => $question) {
            $correct_answer = $question[1]; // Răspunsul corect este al doilea element din fiecare întrebare
            $options = ['a', 'b', 'c', 'd'];
            
            if (rand(1, 100) <= $accuracy) {
                $answers[$index] = $correct_answer;
            } else {
                $wrong_options = array_diff($options, [$correct_answer]);
                $answers[$index] = $wrong_options[array_rand($wrong_options)];
            }
        }
        return $answers;
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
            } else {
                die('<p style="color: red;">Nu există fișierul selectat!</p>');
            }
        } else {
            die('<p style="color: red;">Nu a fost selectat niciun test!</p>');
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
            if (encrypt_cnp_to_id($cnp) === $numeric_id) {
                return $cnp; // Găsit în lista originală
            }
        }
        return null; // Dacă nu se găsește
    }
}

$generator = new PDFGenerator();
