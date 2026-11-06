<?php
ob_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';

// Login Page එකේ ඇති Session Handler එකම මෙතැනටද යොදන්න
if (session_status() === PHP_SESSION_NONE) {
    require_once ROOT_PATH_NOT . 'DBPath/SessionHandler.php';
    include_once ROOT_PATH_NOT . "DBPath/Conn_notspprd.php";
    
    $handler = new DatabaseSessionHandler($pdo_notsl);
    session_set_save_handler($handler, true);
    
    session_start();
}

$captcha_image_height = 45;
$captcha_image_width = 130;
$total_characters_on_image = 6;

$possible_captcha_letters = 'bdfghmnqrtABDEFGHLMNRT23456789';

// Font Path එක නිවැරදි කිරීම (Slash එකක් එකතු කරන ලදී)
$captcha_font = __DIR__ . '/monofont.ttf'; 

$random_captcha_dots = 50;
$random_captcha_lines = 5;

// CAPTCHA කේතය සෑදීම
$captcha_code = '';
$count = 0;
while ($count < $total_characters_on_image) { 
    $captcha_code .= substr(
        $possible_captcha_letters,
        mt_rand(0, strlen($possible_captcha_letters)-1),
        1
    );
    $count++;
}

$_SESSION['captcha'] = $captcha_code;

$captcha_font_size = $captcha_image_height * 0.60;

// TrueColor රූපයක් සෑදීම
$captcha_image = imagecreatetruecolor($captcha_image_width, $captcha_image_height);

// RGB පසුබිම් වර්ණය
$background_color = imagecolorallocate($captcha_image, 255, 255, 255); // සුදු පසුබිම
imagefill($captcha_image, 0, 0, $background_color);

// අකුරු සහ Noise වර්ණ
$text_color_final = imagecolorallocate($captcha_image, 20, 40, 100); 
$noise_color_final = imagecolorallocate($captcha_image, 20, 40, 100);

/* තිත් එකතු කිරීම (Dots) */
for($count=0; $count<$random_captcha_dots; $count++) {
    imagefilledellipse(
        $captcha_image,
        mt_rand(0, $captcha_image_width),
        mt_rand(0, $captcha_image_height),
        2,
        2,
        $noise_color_final
    );
}

/* ඉරි එකතු කිරීම (Lines) */
for($count=0; $count<$random_captcha_lines; $count++) {
    imageline(
        $captcha_image,
        mt_rand(0, $captcha_image_width),
        mt_rand(0, $captcha_image_height),
        mt_rand(0, $captcha_image_width),
        mt_rand(0, $captcha_image_height),
        $noise_color_final
    );
}

/* අකුරු ලිවීම */
if(file_exists($captcha_font)) {
    $text_box = imagettfbbox($captcha_font_size, 0, $captcha_font, $captcha_code); 
    
    $text_width = $text_box[4] - $text_box[0];
    $text_height = $text_box[1] - $text_box[7]; 
    
    $x = ($captcha_image_width - $text_width) / 2;
    $y = ($captcha_image_height / 2) + ($text_height / 2) - $text_box[1];

    imagettftext(
        $captcha_image,
        $captcha_font_size,
        0,
        $x,
        $y,
        $text_color_final,
        $captcha_font,
        $captcha_code
    );
} else {
    // ෆොන්ට් එක නැතිනම් සාමාන්‍ය ෆොන්ට් එකෙන් ලියන්න
    imagestring($captcha_image, 5, 30, 15, $captcha_code, $text_color_final);
}

while (ob_get_level() > 0) {
    ob_end_clean();
}

// Image Header සහ Render කිරීම
header('Content-Type: image/jpeg'); 
imagejpeg($captcha_image); 
imagedestroy($captcha_image); 
exit();
?>