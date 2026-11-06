<?php 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'src/Exception.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php'; 

ob_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';
include_once ROOT_PATH_NOT . "DBPath/Conn_notspprd.php"; 

if (session_status() === PHP_SESSION_NONE) {
    require_once ROOT_PATH_NOT . 'DBPath/SessionHandler.php';
    $handler = new DatabaseSessionHandler($pdo_notsl);
    session_set_save_handler($handler, true);
    session_start();
}

date_default_timezone_set("Asia/Colombo");

// Helper function for general inputs (DO NOT use on passwords)
function clean_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

if (isset($_POST['send'])) {
    $npsw     = $_POST['npsw'] ?? '';
    $npswa    = $_POST['npswa'] ?? '';
    $newuser  = clean_input($_POST['uname'] ?? '');
    $ipadd    = clean_input($_POST['ipadd'] ?? '');
    $clientna = clean_input($_POST['clientna'] ?? '');
    $conna    = clean_input($_POST['conna'] ?? '');
    $cityna   = clean_input($_POST['cityna'] ?? '');

    if ($npsw !== $npswa) {
        header("Location: /Login_Web/Data_Insert_Form/User_Verify.php"); 
        exit;
    } else {
        $sql = "SELECT EmailAdd, Pass FROM Notary_Details WHERE (UserName = :username OR EmailAdd = :username) AND Block = '1' AND Pass <> ''";
        $stmt = $pdo_notsl->prepare($sql);
        $stmt->execute([':username' => $newuser]);

        if ($stmt->rowCount() > 0) {
            $row   = $stmt->fetch(PDO::FETCH_ASSOC);
            $email = $row["EmailAdd"];

            $todaytime = date("Y-m-d H:i:s");
            $hash      = password_hash($npsw, PASSWORD_DEFAULT);

            // Update Notary Details
            $sqlAdd = "UPDATE Notary_Details 
                       SET LastVisitDate = :todaytime, 
                           Password = :hash, 
                           Activation = '2', 
                           Block = '0', 
                           Pass = '', 
                           InvalidAtt = '0' 
                       WHERE UserName = :newuser OR EmailAdd = :newuser";

            try {
                $stmtUpdate = $pdo_notsl->prepare($sqlAdd);
                $stmtUpdate->execute([
                    ':todaytime' => $todaytime,
                    ':hash'      => $hash,
                    ':newuser'   => $newuser
                ]);
            } catch (PDOException $e) {
                die("Database Update Failed: " . $e->getMessage());
            }




            if ($provnoreply <> "") {
                $clean_provweb = trim($provweb);
            	$subject = "Your Account Activate Success :- https://" . $clean_provweb;
                $encoded_username = urlencode($username);
                $encoded_hash = urlencode($hash);
                                                            
                $txt = "<html>
                <head>
                	<title>Account Activate - Success</title>
                </head>
                <body>
                       	<p>{$deptname}</p>
			<p>Account Activated on  {$todaytime}</p>
                        <p><a href='https://{$clean_provweb}/index.php'>Goto Web</a></p>

                </body>
                </html>";
                                                                                                    
               	$headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                $headers .= "From: " . trim($provnoreply) . "\r\n";
                $headers .= "Reply-To: " . trim($provnoreply) . "\r\n";
                                                            
                mail($email, $subject, $txt, $headers);
          }
                                                        
          else {
               if ($gmailusername <> '') {
               		$mail = new PHPMailer(true);
                                                               
                        try {
                              $clean_provweb = trim($provweb);
                              $subject = "Your Account Activate Success :- https://" . $clean_provweb;
                              $encoded_username = urlencode($username);
                              $encoded_hash = urlencode($hash);
                                                                    
                              $txt = "<html>
                              <head>
                                    <title>Account Activate - Success</title>
                              </head>
                              <body>
                                    <p>{$deptname}</p>
				    <p>Account Activated on  {$todaytime}</p>
                                    <p><a href='https://{$clean_provweb}/index.php'>Goto Web</a></p>
                              </body>
                              </html>";
                                                                    
                             	$mail->isSMTP();
                                $mail->Host       = 'smtp.gmail.com';
                                $mail->SMTPAuth   = true;
                                $mail->Username   = $gmailusername;
                                $mail->Password   = $gmailpassword; 
                                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                                $mail->Port       = 587;
                                    
    			    	// --- Localhost / Docker SSL Verification Bypass ---
    			    	$mail->SMTPOptions = array(
        		     	    'ssl' => array(
            		    	    'verify_peer'       => false,
            		    	    'verify_peer_name'  => false,
            		    	    'allow_self_signed' => true
        		     	    )
    			     	);
    
                        
                                $sender_name = !empty($gmailheadline) ? $gmailheadline : 'Stampduty System';
                            	$mail->setFrom($gmailusername, $sender_name);
                                $mail->addAddress($email); 
                                                                
                                $mail->isHTML(true);
                                $mail->Subject = $subject;
                                $mail->Body    = $txt;
                                                                
                                $mail->send();

                         } catch (Exception $e) {
                                error_log("Mail Error: " . $mail->ErrorInfo);
                         }
                 }
         }


	// Write User History Log File
            $addyear = date("Y");
            $target_dir_user = ROOT_PATH_NOT . "History/User/" . $addyear . "/";

            if (!is_dir($target_dir_user)) {
                mkdir($target_dir_user, 0755, true);
            }

            $user_file = $target_dir_user . $newuser . ".txt";
            $log_entry = "\nLog to Notary User Activate\t" . $ipadd . "\t" . $clientna . "\t" . $conna . "-" . $cityna . "\t" . $todaytime;
            
            file_put_contents($user_file, $log_entry, FILE_APPEND | LOCK_EX);

            echo '<script type="text/javascript">window.open("/index.php","_self");</script>';
            exit;



        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Account Activation</title>
<style>
body { font-family: Arial, Helvetica, sans-serif; }
form { border: 3px solid #f1f1f1; }
input[type=text], input[type=password] {
  width: 100%;
  padding: 12px 20px;
  margin: 8px 0;
  display: inline-block;
  border: 1px solid #ccc;
  box-sizing: border-box;
}
button, .loginbtn {
  background-color: #04AA6D;
  color: white;
  padding: 14px 20px;
  margin: 8px 0;
  border: none;
  cursor: pointer;
  width: 100%;
}
.loginbtn {
  background-color: #32CD32;
}
.imgcontainer { text-align: center; margin: 24px 0 12px 0; }
img.avatar { width: 50%; border-radius: 50%; }
.container { padding: 16px; width: 100%; }
.container1 { padding: 16px; width: 90%; max-width: 500px; margin: auto; }

#message { display:none; background: #f1f1f1; color: #000; padding: 10px; margin-top: 5px; }
.valid { color: green; }
.valid:before { content: "✔ "; }
.invalid { color: red; }
.invalid:before { content: "✖ "; }
</style>
</head>

<body>
<div class="container1" style="background-color:#f1f1f1">
    <h2>Activate My Account</h2>

    <form action="" method="post">
        <?php
            $ip_address = $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            
            // Safe IP Lookup with Timeout
            $contry_name = 'Unknown';
            $city_name   = 'Unknown';
            $ctx = stream_context_create(['http' => ['timeout' => 2]]);
            $details_raw = @file_get_contents("https://ipinfo.io/$ip_address/json", false, $ctx);
            
            if ($details_raw !== false) {
                $clientDetails = json_decode($details_raw);
                $contry_name = $clientDetails->country ?? 'Unknown';
                $city_name   = $clientDetails->city ?? 'Unknown';
            }

            $client_name = gethostbyname(gethostname());

            $newuser   = clean_input($_GET['q'] ?? '');
            $password1 = clean_input($_GET['qq'] ?? '');

            if (!empty($newuser)) {
                $sql = "SELECT ID, UserName, Password, EmailAdd, Block, Pass 
                        FROM Notary_Details 
                        WHERE UserName = :u1 OR EmailAdd = :u2";

                $stmt = $pdo_notsl->prepare($sql);
                $stmt->execute([':u1' => $newuser, ':u2' => $newuser]);

                if ($stmt->rowCount() === 1) {
                    $row   = $stmt->fetch(PDO::FETCH_ASSOC);
                    $pass  = $row["Pass"];
                    $block = $row["Block"];

                    if ($block == '1' && $pass === $password1) {
        ?>
                        <input type="hidden" name="ipadd" value="<?php echo $ip_address; ?>">
                        <input type="hidden" name="clientna" value="<?php echo $client_name; ?>">
                        <input type="hidden" name="conna" value="<?php echo $contry_name; ?>">
                        <input type="hidden" name="cityna" value="<?php echo $city_name; ?>">

                        <div class="imgcontainer">
                            <img src="/Icon/Password/activate.jpg" alt="Avatar" class="avatar">
                        </div>

                        <div class="container">
                            <label for="uname"><b>Username</b></label>
                            <input type="text" name="uname1" value="<?php echo $newuser; ?>" disabled>
                            <input type="hidden" name="uname" value="<?php echo $newuser; ?>">

                            <label for="npsw"><b>New Password</b></label>
                            <input type="password" placeholder="Enter Password" name="npsw" id="npsw" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" title="Must contain at least one number, one uppercase and lowercase letter, and at least 8 or more characters" required autocomplete="off">

                            <label for="npswa"><b>Confirm New Password</b></label>
                            <input type="password" placeholder="Re-Type Password" name="npswa" id="npswa" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" required autocomplete="off">
                        </div>

                        <div id="message">
                            <h3>Password must contain:</h3>
                            <p id="letter" class="invalid">A <b>lowercase</b> letter</p>
                            <p id="capital" class="invalid">A <b>capital (uppercase)</b> letter</p>
                            <p id="number" class="invalid">A <b>number</b></p>
                            <p id="length" class="invalid">Minimum <b>8 characters</b></p>
                        </div>

                        <input type="submit" name="send" value="Activate" class="loginbtn">
        <?php
                    } else {
                        echo "<p class='invalid'>This user is already activated or link is invalid.</p>";
                        exit;
                    }
                } else {
                    echo "<p class='invalid'>User not found.</p>";
                    exit;
                }
            }
        ?>
    </form>
</div>

<script>
    var myInput  = document.getElementById("npsw");
    var myInputA = document.getElementById("npswa");
    var letter   = document.getElementById("letter");
    var capital  = document.getElementById("capital");
    var number   = document.getElementById("number");
    var length   = document.getElementById("length");

    if (myInput) {
        myInput.onfocus = function() {
            document.getElementById("message").style.display = "block";
        }
        myInput.onblur = function() {
            document.getElementById("message").style.display = "none";
        }

        myInput.onkeyup = function() {
            // Validate lowercase
            letter.className = myInput.value.match(/[a-z]/g) ? "valid" : "invalid";
            // Validate uppercase
            capital.className = myInput.value.match(/[A-Z]/g) ? "valid" : "invalid";
            // Validate numbers
            number.className = myInput.value.match(/[0-9]/g) ? "valid" : "invalid";
            // Validate length
            length.className = myInput.value.length >= 8 ? "valid" : "invalid";
        }
    }

    if (myInputA) {
        myInputA.onkeyup = function() {
            if (myInput.value === myInputA.value && myInputA.value !== '') {
                myInputA.style.borderColor = "green";
            } else {
                myInputA.style.borderColor = "red";
            }
        }
    }
</script>
</body>
</html>