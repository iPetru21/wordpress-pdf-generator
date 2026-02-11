<?php
defined( 'ABSPATH' ) || exit;

$questions = $test_data['questions'];
$options = $test_data['options'];
$commission = $test_data['commission'];
$examen = $test_data['examen'];
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
    </head>

    <body style="font-family:FreeSans;">
        <div class="container">
            <div class="header">
                <img width="100px" style="float: left;
margin-left: 45px;" src="<?= plugin_dir_url(__DIR__) ?>/assets/images/ue.jpg" alt="">
                <img width="100px" style="float: right;
margin-right: 45px;" src="<?= plugin_dir_url(__DIR__) ?>/assets/images/guvern.png" alt="">
            </div>
    
            <div class="info">
                <p>ID: <?= $id ?></p>
                <p>Link test: <?= $examen['link'] ?> </p>
            </div>
            <h3><?= $examen['title'] ?></h3>
            <table class="user-answers">
                <tr>
                    <th>ÎNTREBARE</th>
                    <th>RĂSPUNSUL CURSANTULUI</th>
                    <th>PUNCTAJ</th>
                </tr>
                <?php 
                    $score = $this->punctaj_oficiu;
                ?>
                <?php foreach( $questions as $index => $question ): 
                    $question_score = 0;

                    $question_text = $question[0];
                    $correct_answer = $question[1];
                    $user_answer = $answers[$index];

                    $answer_text = $options[$index][$user_answer];

                    if ($user_answer === $correct_answer) {
                        $score += $this->punctaj_intrebare;
                        $question_score = $this->punctaj_intrebare;
                    }
                    
                    ?>
                    <tr>
                        <td>
                            <?= $index + 1 . " " .$question_text ?>
                        </td>
                        <td>
                            <?= $user_answer. ") " .$answer_text ?>
                        </td>
                        <td class="score"><?= $question_score ?></td>
                    </tr>
                <?php endforeach; ?>

                </table>
                <table class="user-comission">
                <tr>
                    <th style="text-transform: uppercase;width:30%">COMISIE DE <?= $examen['type'] ?></th>
                    <th <?= ( $examen['type'] != 'examen' ) ? 'colspan="2"' : '' ?> style="font-weight: 300;text-align:left; width:40%">
                        <p>*Fiecare răspuns se notează cu <?= $this->punctaj_intrebare ?> puncte</p>
                        <p>*<?= $this->punctaj_oficiu ?> puncte din oficiu</p>
                        <p>*Doar un răspuns este corect</p>
                    </th>
                    <th style="width:30%">ID TEST: <?= $id ?></th>
                </tr>
                <?php foreach( $commission as $index => $committee): ?>
                <tr>
                    <td><?= $committee ?></td>
                    <td style="width:15%">
                        <?php if( $index != 2 ): ?>
                            NOTA <?= ++$index ?>: <?= $score ?>
                        <?php else: ?>
                            Media: <?= $score ?>
                        <?php endif; ?>
                    </td>
                    <td>Semnătură</td>
                    <?php if($index == 1): ?>
                        <?php if( $examen['type'] == 'evaluare' ): ?>
							<td rowspan="3">
								<p>Nume: <?= $user->last_name .' '. $user->first_name ?></p><br>
								<p>CNP: <?= $cnp ?></p>
							</td>
                        <?php endif; ?>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </body>
</html>