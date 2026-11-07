<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'src/Exception.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';

$mail = new PHPMailer(true);

try {
    // --- DEBUGGING (Exact Error එක බලාගැනීමට) ---
    $mail->SMTPDebug = 2; // Screen එකේ error එක පෙන්වයි
    $mail->Debugoutput = 'html';

    // --- SERVER SETTINGS ---
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    
    // ඔබගේ Gmail ලිපිනය සහ සාදාගත් 16-digit App Password එක
    $mail->Username   = 'she80@gmail.com'; 
    $mail->Password   = 'gqri qtwc'; // 👈 මෙතනට App Password එක දාන්න
    
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
    $mail->Port       = 587;

    // Localhost SSL Certificates Verify Issue එක මඟහැරීමට (Docker/Localhost සඳහා)
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true
        )
    );

    // --- RECIPIENTS ---
    $mail->setFrom('sathure80@gmail.com', 'System Admin');
    $mail->addAddress('sasinghe@gmail.com'); 

    // --- CONTENT ---
    $mail->isHTML(true);
    $mail->Subject = 'Localhost Test Email';
    $mail->Body    = 'This is a test email sent from Localhost!';

    $mail->send();
    echo 'Email sent successfully!';

} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
?>
