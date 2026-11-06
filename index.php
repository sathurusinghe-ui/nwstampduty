<?php  
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'src/Exception.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';
//phpinfo();
ob_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';
include_once ROOT_PATH_NOT . "DBPath/Conn_notspprd.php"; 

if (session_status() === PHP_SESSION_NONE) {
    require_once ROOT_PATH_NOT . 'DBPath/SessionHandler.php';    
    $handler = new DatabaseSessionHandler($pdo_notsl);
    session_set_save_handler($handler, true);
    
    session_start();
	/*if (!empty($_SESSION)) {
        
        $_SESSION = array();
        session_destroy();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_start();
    	}*/
}

date_default_timezone_set("Asia/Colombo");

// --- දැන් ඔබට සාමාන්‍ය පරිදි $_SESSION භාවිත කළ හැක ---
/*$_SESSION['user_id'] = 100000000;
$_SESSION['user_role'] = 'Notary';
$_SESSION['last_timestamp_notsl'] = time();*/
$notuser = $_SESSION['notuse_notsl'] ?? '';

$status = '';

function test_input($data) {
    $data = trim($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}
 
$add_dateA  = date("Y-m-d");
$add_time   = date("H:i:s"); 

$username = $password = "";
$username_err = $password_err = $login_err = "";
 
if($_SERVER["REQUEST_METHOD"] == "POST"){
    if ( isset($_POST['captcha_input']) && ($_POST['captcha_input']!="") ){
          
        if (empty($_POST['captcha_input']) || strtolower($_SESSION['captcha']) !== strtolower($_POST['captcha_input'])) {
            ?>
            <script>
                alert("ඇතුළත් කළ Verification Code (CAPTCHA) එක වැරදියි! නැවත උත්සාහ කරන්න.");
                window.history.back(); 
            </script>
            <?php
        }
        else {
            if(empty(trim($_POST["username"]))){
                $username_err = "Please enter username or email.";
            } else{
                $username = test_input(trim($_POST["username"]));
            }
            
            if(empty(trim($_POST["password"]))){
                $password_err = "Please enter your password.";
            } else{
                $password = test_input(trim($_POST["password"]));
            }
    
            if(empty($username_err) && empty($password_err)){
                 
                // පළමු වගුව පරීක්ෂාව (Notary_Details)
                $sql = "SELECT ID, PC, UserName, Password, EmailAdd, Block, RequireReset, Activation, Pass, Per FROM Notary_Details WHERE UserName = :username OR EmailAdd = :username";
                if($stmt = $pdo_notsl->prepare($sql)){
                    $param_username = trim($_POST["username"]);
                    $stmt->bindParam(":username", $param_username, PDO::PARAM_STR);
                    
                    
                  /*  try {
                        if($stmt->execute()){
                            echo "3. Query එක සාර්ථකව Execute වුණා.<br>";
                            
                            $count = $stmt->rowCount();
                            echo "4. ලැබුණු මුළු වාර්තා ගණන (Row Count): " . $count . "<br>";
                            



                            if($count == 1){
                                // ... ඉතිරි කේතය ...
                            }
                        } else {
                            echo "Query එක execute වුණේ නැත (False ලැබුණා).<br>";
                        }
                    } catch (PDOException $e) {
                        echo "<h1>SQL ධාවනය වීමේ දෝෂයක් (Database Error):</h1>";
                        echo "Error Message: " . $e->getMessage() . "<br>";
                        echo "Error Code: " . $e->getCode() . "<br>";
                    }
                    */
                    
                    
                    if($stmt->execute()){
                        $count = $stmt->rowCount();
                        if($stmt->rowCount() == 1){
                        
                            if($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                                $userid = $row["ID"];
                                $pc = $row["PC"];
                                $per = $row["Per"];
                                $psw = $row["Password"];
                                $email = $row["EmailAdd"];
                                $block = $row["Block"];
                                $otp = $row["Pass"];

                                if ($per == '') { $per ='Limit'; }
                                if ($block <> '1' ) {  
                                    $reqreset =  $row["RequireReset"];
                                    $activation =  $row["Activation"];
                                    // OTP පරීක්ෂාව (ආරක්ෂිතව Hash කර ඇත්නම් password_verify භාවිත කරන්න, නැතහොත් තාවකාලිකව මෙලෙස පවතී)
                                    if ($password == $otp) {
                                        $_SESSION['notuse_notsl'] = $username;
                                        $_SESSION['notpass_notsl'] = $otp;

                                        header("Location: /Login/User_Verify.php?from=otp"); 
                                        exit;
                                    }
            
                                    if ($activation =='1' && $reqreset =='0') {
                                        header("Location: /Login/User_Verify.php?qa=" . urlencode($activation));  
                                        exit;
                                    }
                                    else {
                                        if ($reqreset <> '0') {
                                            $pswc = $row["Pass"];
                                            if (!password_verify($password, $pswc)) {
                                                header("Location: /index.php?q=" . urlencode('Pl. Insert Correct Password Reset Code'));  
                                                exit;
                                            }
                                            else {
                                                $_SESSION["loggedin_notsl"] = true;
                                                $_SESSION["notuseid_notsl"] = $userid;
                                                $_SESSION["notuse_notsl"] = $username;   
                                                $_SESSION['notper_notsl'] = $per;
                                                $_SESSION['notper_pc'] = $pc;
                                                $_SESSION['per_notsl'] = $per;  
                                                    
                                                echo '<script type="text/javascript"> window.open("/Login/Password_Reset_With_Code.php","_self");</script>';
                                                exit;
                                            }
                                        }
                                        else {


                                            if (!password_verify($password, $psw)) {
                                                $login_err = "Invalid username or password.";

                                                
                                                // Invalid Attempts පරීක්ෂාව - PDO Prepared Statements මගින් සුරක්ෂිත කර ඇත
                                                $sqlInvalidAtt = "SELECT InvalidAtt, InvalidAttAll, InvalidAttDate FROM Notary_Details WHERE UserName = :username";
                                                $stmtGetAtt = $pdo_notsl->prepare($sqlInvalidAtt);
                                                $stmtGetAtt->bindParam(':username', $username, PDO::PARAM_STR);
                                                $stmtGetAtt->execute();
                                                $rowInvalidAtt = $stmtGetAtt->fetch(PDO::FETCH_ASSOC);
                                                
                                                $InvalidAttDate = $rowInvalidAtt['InvalidAttDate'] ?? '';
                                                $InvalidAttAll = $rowInvalidAtt['InvalidAttAll'] ?? 0;
                                                $InvalidAtt = 0;
                                                
                                                if ($InvalidAttDate == $add_dateA) { 
                                                    $InvalidAtt = $rowInvalidAtt['InvalidAtt'] ?? 0;
                                                }
                                                
                                                $InvalidAtt++;
                                                $InvalidAttAll++;
                                        
                                                // උත්සාහයන් ගණන යාවත්කාලීන කිරීම (PDO මගින්)
                                                $sqlInvalidAttAdd = "UPDATE Notary_Details SET InvalidAtt = :invalid_att, InvalidAttDate = :att_date, InvalidAttAll = :invalid_all WHERE UserName = :username";
                                                $stmtUpdAtt = $pdo_notsl->prepare($sqlInvalidAttAdd);
                                                $stmtUpdAtt->bindParam(':invalid_att', $InvalidAtt, PDO::PARAM_INT);
                                                $stmtUpdAtt->bindParam(':att_date', $add_dateA, PDO::PARAM_STR);
                                                $stmtUpdAtt->bindParam(':invalid_all', $InvalidAttAll, PDO::PARAM_INT);
                                                $stmtUpdAtt->bindParam(':username', $username, PDO::PARAM_STR);
                                                $stmtUpdAtt->execute();
                                                
                                                if ($InvalidAtt > 3) {
                                                    $login_err = "There are three Invalid Attempts today. Account Blocked. Please Check Your e-mail.";
                                                    
                                                    if ($InvalidAtt == 4) {
                                                        $psw_rand = rand(100000, 999999);
                                                        $hash = password_hash($psw_rand, PASSWORD_DEFAULT);
                                                        
                                                        $sqlBlock = "UPDATE Notary_Details SET Block = '1', Pass = :hash WHERE UserName = :username";
                                                        $stmtBlock = $pdo_notsl->prepare($sqlBlock);
                                                        $stmtBlock->bindParam(':hash', $hash, PDO::PARAM_STR);
                                                        $stmtBlock->bindParam(':username', $username, PDO::PARAM_STR);
                                                        $stmtBlock->execute();


                                                        if ($provnoreply <> "") {
                                                            $subject = "Inactivate Your Account :- https://" . trim($provweb);
                                                            $encoded_username = urlencode($username);
                                                            $encoded_hash = urlencode($hash);
                                                            $clean_provweb = trim($provweb);
                                                            
                                                            $txt = "<html>
                                                            <head>
                                                                <title>Account Disabled</title>
                                                            </head>
                                                            <body>
                                                                <p>There are Three Invalid Attempts Today.</p>
                                                                <p><a href='https://{$clean_provweb}/Activate_My_Account.php?q={$encoded_username}&qq={$encoded_hash}'>Activate My Account</a></p>
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
									$subject = "Inactivate Your Account "; //:- https://" . trim($provweb);
									$encoded_username = urlencode($username);
									$encoded_hash = urlencode($hash);
									$clean_provweb = trim($provweb);

									$txt = "<html>
										<head>
										<title>Account Disabled</title>
										</head>
										<body>
										<p>There are Three Invalid Attempts Today.</p>
										<p><a href='https://{$clean_provweb}/Activate_My_Account.php?q={$encoded_username}&qq={$encoded_hash}'>Activate My Account</a></p>
										</body>
									</html>";

    									// --- Server Settings ---
    									$mail->isSMTP();
    									$mail->Host       = 'smtp.gmail.com';
    									$mail->SMTPAuth   = true;
    									$mail->Username   = $gmailusername; // උදා: 'your-email@gmail.com'
    									$mail->Password   = $gmailpassword; // 👈 16-Digit App Password එක (හිස්තැන් නැතුව)
    									$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    									$mail->Port       = 587;

    									// --- Localhost / Docker SSL Verification Bypass ---
   									 // (Localhost හිදී SSL Certificate Error එන එක වළක්වයි)
    									$mail->SMTPOptions = array(
        									'ssl' => array(
            									'verify_peer'       => false,
            									'verify_peer_name'  => false,
            									'allow_self_signed' => true
        									)
    									);
    									// --- Recipients & Content 
									$sender_name = !empty($gmailheadline) ? $gmailheadline : 'Stampduty System';
                            						$mail->setFrom($gmailusername, $sender_name);
    									$mail->addAddress($email); 

    									$mail->isHTML(true);
    									$mail->Subject = $subject;
    									$mail->Body    = $txt;

    									$mail->send();
    
    									 // echo "Email sent successfully!";

								} catch (Exception $e) {
    									error_log("Mail Error: " . $mail->ErrorInfo);
    									echo "Mail Error: " . $mail->ErrorInfo;
								}
                                                            }
                                                        }
                                                        
                                                    } else {
                                                        $sqlBlockOnly = "UPDATE Notary_Details SET Block = '1' WHERE UserName = :username";
                                                        $stmtBlockOnly = $pdo_notsl->prepare($sqlBlockOnly);
                                                        $stmtBlockOnly->bindParam(':username', $username, PDO::PARAM_STR);
                                                        $stmtBlockOnly->execute();
                                                    }
                                                }
                                                header("Location: https://".$provweb."/index.php?err=" . urlencode($login_err));  
                                                exit;
                                            }
                                            else {
						$client_ip = $_SERVER['REMOTE_ADDR'];
                				if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                    					$client_ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
                				}

                                                $_SESSION["loggedin_notsl"] = true;
                                                $_SESSION["notuseid_notsl"] = $userid;
                                                $_SESSION["notuse_notsl"] = $username; 
                                                $_SESSION['notper_notsl'] = $per; 
                                                $_SESSION['notuse_pc'] = $pc;
                                                $_SESSION['ipadd'] = $client_ip;   


                                        
                                                $todaytime = $add_dateA . " " . $add_time;
                                                $sqlAdd = "UPDATE Notary_Details SET LastVisitDate = :last_visit, InvalidAtt = 0 WHERE UserName = :username";
                                                $stmtTime = $pdo_notsl->prepare($sqlAdd);
                                                $stmtTime->bindParam(':last_visit', $todaytime, PDO::PARAM_STR);
                                                $stmtTime->bindParam(':username', $username, PDO::PARAM_STR);
                                                $stmtTime->execute();
                                                echo '<script type="text/javascript"> window.open("/Login/home_select_pc.php","_self");</script>';
                                                exit;
                                            }
                                        } 
                                    }
                                }
                            }
                        }

		// Notary Table Test End and Officer Table Test Start
		else {


		    // Department and Other Logers

 		    $sql = "SELECT ID, UserName, Password, EmailAdd, Block, RequireReset, Activation, Pass, Per, OfficeNo FROM User_All WHERE UserName = :username OR EmailAdd = :username";
                
                    if($stmt = $pdo_notsl->prepare($sql)){
                    	$param_username = trim($_POST["username"]);
                    	$stmt->bindParam(":username", $param_username, PDO::PARAM_STR);
                    
                    	/*
                    	try {
                        	if($stmt->execute()){
                            	echo "3. Query එක සාර්ථකව Execute වුණා.<br>";
                            
                            	$count = $stmt->rowCount();
                            	echo "4. ලැබුණු මුළු වාර්තා ගණන (Row Count): " . $count . "<br>";
                            
                            	if($count == 1){
                                	// ... ඉතිරි කේතය ...
                            	
                        	} else {
                            	echo "Query එක execute වුණේ නැත (False ලැබුණා).<br>";
                        	}
                    	} catch (PDOException $e) {
                        	// දත්ත සමුදායේ ඇති සැබෑ දෝෂය මෙතැනින් Print වේ
                        	echo "<h1>SQL ධාවනය වීමේ දෝෂයක් (Database Error):</h1>";
                        	echo "Error Message: " . $e->getMessage() . "<br>";
                        	echo "Error Code: " . $e->getCode() . "<br>";
                    	}*/
                                       
                    
                    	if($stmt->execute()){
                        	$count = $stmt->rowCount();
                        	if($stmt->rowCount() == 1){
                        
                            	if($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                                	$userid = $row["ID"];
                                	$offno = $row["OfficeNo"];
                                	$per = $row["Per"];
                                	$psw = $row["Password"];
                                	$email = $row["EmailAdd"];
                                	$block = $row["Block"];
                                	$otp = $row["Pass"];
                                
                               		// echo "AS".$per; exit;
                                
                                	if ($per == '') { $per ='Limit'; }
                                	if ($block <> '1' ) {  
                                    	$reqreset =  $row["RequireReset"];
                                    	$activation =  $row["Activation"];
                                    
                                    	// OTP පරීක්ෂාව (ආරක්ෂිතව Hash කර ඇත්නම් password_verify භාවිත කරන්න, නැතහොත් තාවකාලිකව මෙලෙස පවතී)
                                    	if ($password == $otp) {
                                        	$_SESSION['temp_verify_user'] = $username;
                                        	header("Location: /Login/User_Verify.php?from=otp"); 
                                        	exit;
                                    	}
            
                                    	if ($activation =='1' && $reqreset =='0') {
                                        	header("Location: /Login/User_Verify.php?qa=" . urlencode($activation));  
                                        	exit;
                                    	}
                                    	else {
                                        	if ($reqreset <> '0') {
                                            		$pswc = $row["Pass"];
                                            		if (!password_verify($password, $pswc)) {
                                                		header("Location: /index.php?q=" . urlencode('Pl. Insert Correct Password Reset Code'));  
                                                		exit;
                                            		}
                                            		else {
                                                		$_SESSION["loggedin_notsl"] = true;
                                                		$_SESSION["offuseid_sd"] = $userid;
                                                		$_SESSION["offuse_sd"] = $username;   
                                                		$_SESSION['offper_sd'] = $per;
                                                		$_SESSION['offoffno_sd'] = $offno;  
                                                    
                                                		echo '<script type="text/javascript"> window.open("/Login/Password_Reset_With_Code.php","_self");</script>';
                                                		exit;
                                            		}
                                        	}
                                        	else {
                                            		if (!password_verify($password, $psw)) {
                                                		$login_err = "Invalid username or password.";
                                                
                                                		// Invalid Attempts පරීක්ෂාව - PDO Prepared Statements මගින් සුරක්ෂිත කර ඇත
                                                		$sqlInvalidAtt = "SELECT InvalidAtt, InvalidAttAll, InvalidAttDate FROM User_All WHERE UserName = :username";
                                                		$stmtGetAtt = $pdo_notsl->prepare($sqlInvalidAtt);
                                                		$stmtGetAtt->bindParam(':username', $username, PDO::PARAM_STR);
                                                		$stmtGetAtt->execute();
                                                		$rowInvalidAtt = $stmtGetAtt->fetch(PDO::FETCH_ASSOC);
                                                
                                                		$InvalidAttDate = $rowInvalidAtt['InvalidAttDate'] ?? '';
                                                		$InvalidAttAll = $rowInvalidAtt['InvalidAttAll'] ?? 0;
                                                		$InvalidAtt = 0;
                                                
                                                		if ($InvalidAttDate == $add_dateA) { 
                                                    			$InvalidAtt = $rowInvalidAtt['InvalidAtt'] ?? 0;
                                                		}
                                                
                                                		$InvalidAtt++;
                                                		$InvalidAttAll++;
                                        
                                                		// උත්සාහයන් ගණන යාවත්කාලීන කිරීම (PDO මගින්)
                                                		$sqlInvalidAttAdd = "UPDATE User_All SET InvalidAtt = :invalid_att, InvalidAttDate = :att_date, InvalidAttAll = :invalid_all WHERE UserName = :username";
                                                		$stmtUpdAtt = $pdo_notsl->prepare($sqlInvalidAttAdd);
                                                		$stmtUpdAtt->bindParam(':invalid_att', $InvalidAtt, PDO::PARAM_INT);
                                                		$stmtUpdAtt->bindParam(':att_date', $add_dateA, PDO::PARAM_STR);
                                                		$stmtUpdAtt->bindParam(':invalid_all', $InvalidAttAll, PDO::PARAM_INT);
                                                		$stmtUpdAtt->bindParam(':username', $username, PDO::PARAM_STR);
                                                		$stmtUpdAtt->execute();
                                                
                                                		if ($InvalidAtt > 3) {
                                                    			$login_err = "There are three Invalid Attempts today. Account Blocked. Please Check Your e-mail.";
                                                    
                                                    			if ($InvalidAtt == 4) {
                                                        			$psw_rand = rand(100000, 999999);
                                                        			$hash = password_hash($psw_rand, PASSWORD_DEFAULT);
                                                        
                                                        			$sqlBlock = "UPDATE User_All SET Block = '1', Pass = :hash WHERE UserName = :username";
                                                        			$stmtBlock = $pdo_notsl->prepare($sqlBlock);
                                                        			$stmtBlock->bindParam(':hash', $hash, PDO::PARAM_STR);
                                                        			$stmtBlock->bindParam(':username', $username, PDO::PARAM_STR);
                                                        			$stmtBlock->execute();
                                                        
                                                        			if ($provnoreply <> "") {
                                                            				$subject = "Inactivate Your Account :- https://" . trim($provweb);
                                                            				$encoded_username = urlencode($username);
                                                            				$encoded_hash = urlencode($hash);
                                                            				$clean_provweb = trim($provweb);
                                                            
                                                            				$txt = "<html>
                                                            				<head>
                                                                			<title>Account Disabled</title>
                                                            				</head>
                                                            				<body>
                                                                			<p>There are Three Invalid Attempts Today.</p>
                                                                			<p><a href='https://{$clean_provweb}/Activate_My_Account.php?q={$encoded_username}&qq={$encoded_hash}'>Activate My Account</a></p>
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
                                                                    					$subject = "Inactivate Your Account :- https://" . trim($provweb);
                                                                    					$encoded_username = urlencode($username);
                                                                    					$encoded_hash = urlencode($hash);
                                                                    					$clean_provweb = trim($provweb);
                                                                    
                                                                    					$txt = "<html>
                                                                    					<head>
                                                                        				<title>Account Disabled</title>
                                                                    					</head>
                                                                    					<body>
                                                                        				<p>There are Three Invalid Attempts Today.</p>
                                                                        				<p><a href='https://{$clean_provweb}/Activate_My_Account.php?q={$encoded_username}&qq={$encoded_hash}'>Activate My Account</a></p>
                                                                    					</body>
                                                                    					</html>";
                                                                    
                                                                    					$mail->isSMTP();
                                                                    					$mail->Host       = 'smtp.gmail.com';
                                                                    					$mail->SMTPAuth   = true;
                                                                    					$mail->Username   = $gmailusername;
                                                                    					$mail->Password   = $gmailpassword; 
                                                                    					$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                                                                    					$mail->Port       = 587;
                                                                
                                                                    					$mail->setFrom($gmailusername, $gmailheadline);
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
                                                       
                                                        
                                                    			} else {
                                                        			// සාමාන්‍ය Block කිරීම් සඳහා
                                                        			$sqlBlockOnly = "UPDATE User_All SET Block = '1' WHERE UserName = :username";
                                                        			$stmtBlockOnly = $pdo_notsl->prepare($sqlBlockOnly);
                                                        			$stmtBlockOnly->bindParam(':username', $username, PDO::PARAM_STR);
                                                        			$stmtBlockOnly->execute();
                                                    			}
                                                		}
                                                		header("Location: https://".$provweb."/index.php?err=" . urlencode($login_err));  
                                                		exit;
                                            		}
                                            		else {
                                                		// සාර්ථක ලොගින් වීම
								$client_ip = $_SERVER['REMOTE_ADDR'];
                						if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                    							$client_ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
                						}


                                                		$_SESSION["loggedin_sd"] = true;
                                                		$_SESSION["offuseid_sd"] = $userid;
                                                		$_SESSION["offuse_sd"] = $username; 
                                                		$_SESSION['offper_sd'] = $per; 
                                                		$_SESSION['offoffno_sd'] = $offno;
                                                		$_SESSION['ipadd'] = $client_ip;

                                        
                                                		$todaytime = $add_dateA . " " . $add_time;
                                                		$sqlAdd = "UPDATE User_All SET LastVisitDate = :last_visit, InvalidAtt = 0 WHERE UserName = :username";
                                                		$stmtTime = $pdo_notsl->prepare($sqlAdd);
                                                		$stmtTime->bindParam(':last_visit', $todaytime, PDO::PARAM_STR);
                                                		$stmtTime->bindParam(':username', $username, PDO::PARAM_STR);
                                                		$stmtTime->execute();
                                               			// echo "ASD"; exit;
                                                		echo '<script type="text/javascript"> window.open("/Login/home.php","_self");</script>';
                                                		exit;
                                            		}
                                        	} 
                                    	}
                                }
                            }
			}}}

                        
		










			} // Officer Table Test End

                    }
                }

            }
        }
    }
    else if ( isset($_POST['otp']) && ($_POST['otp']!="") ){
        $user_otp = trim($_POST["otp"]); // පරිශීලකයා Form එකේ Input කළ OTP එක
    
    /*
    $a = $_SESSION['generated_otp'];
    $b = $_SESSION['otp_mobile'];
    $c = $_SESSION['otp_expiry']; */
    
  //  echo $user_otp."-".$a."-".$b."-".$c; 
    // Session එකේ පවතින දත්ත සමඟ සැසඳීම
    if (isset($_SESSION['generated_otp']) && isset($_SESSION['otp_expiry'])) {
        
        // වලංගු කාලය ඉකුත් වී ඇත්දැයි පරීක්ෂාව
        if (time() > $_SESSION['otp_expiry']) {
            $login_err = "ඇතුළත් කළ OTP කේතයේ වලංගු කාලය අවසන් වී ඇත. කරුණාකර නව කේතයක් ලබා ගන්න.";
            unset($_SESSION['generated_otp']);
            unset($_SESSION['otp_expiry']);
            unset($_SESSION['otp_attempts']);
        } 
        // OTP එක ගැලපේදැයි පරීක්ෂාව
        else{
            
            if ($user_otp == $_SESSION['generated_otp']) {
                include_once ROOT_PATH_NOT ."DBPath/Conn_notspprd.php";  
                 
                // ------ [අලුතින් එකතු කළ කොටස - IP සහ User Agent ලබා ගැනීම] ------
                $client_ip = $_SERVER['REMOTE_ADDR'];
                if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                    $client_ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
                }
                $user_agent = $_SERVER['HTTP_USER_AGENT']; // Browser, OS විස්තර සහිත Unique String එක
                $mobile_logged = $_SESSION['otp_mobile'] ?? ''; // OTP යැවූ දුරකථන අංකය
                $log_date = date("Y-m-d");
                $log_time = date("H:i:s");
                $log_date_time = date("Y-m-d H:i:s");
                
                // දත්ත සමුදායේ (Database) ලොග් එකක් ලෙස සුරැකීම (PDO Prepared Statement මඟින්)
                // *මෙහි Notary_Logs වෙනුවට ඔබ කැමති වගුවක නමක් දිය හැක
                $sqlLog = "INSERT INTO Notary_Logs (MobileNo, IPAddress, UserAgent, LoggedDate, LoggedTime, Status) 
                           VALUES (:mobile, :ip, :agent, :logdate, :logtime, 'OTP_SUCCESS')";
                           
                if ($stmtLog = $pdo_notsl->prepare($sqlLog)) {
                    $stmtLog->bindParam(':mobile', $mobile_logged, PDO::PARAM_STR);
                    $stmtLog->bindParam(':ip', $client_ip, PDO::PARAM_STR);
                    $stmtLog->bindParam(':agent', $user_agent, PDO::PARAM_STR);
                    $stmtLog->bindParam(':logdate', $log_date, PDO::PARAM_STR);
                    $stmtLog->bindParam(':logtime', $log_time, PDO::PARAM_STR);
                    $stmtLog->execute();
                }
                
                $mobile_logged = $_SESSION['otp_mobile'] ?? ''; // OTP යැවූ දුරකථන අංකය

                if (!empty($mobile_logged)) {
                    // 2. Notary_Details වගුවේ Fields යාවත්කාලීන කිරීම (UPDATE Query)
                    $sqlUpd = "UPDATE Notary_Details 
                           SET LastVisitDate = :ldatetime, 
                               LastLogIP = :ip,
                               LastUserAgent = :agent, 
                               LastLogDate = :ldate, 
                               LastLogTime = :ltime 
                           WHERE MobNo = :mobile";
                               
                    if ($stmtUpd = $pdo_notsl->prepare($sqlUpd)) {
                        $stmtUpd->bindParam(':ldatetime', $log_date_time, PDO::PARAM_STR);
                        $stmtUpd->bindParam(':ip', $client_ip, PDO::PARAM_STR);
                        $stmtUpd->bindParam(':agent', $user_agent, PDO::PARAM_STR);
                        $stmtUpd->bindParam(':ldate', $log_date, PDO::PARAM_STR);
                        $stmtUpd->bindParam(':ltime', $log_time, PDO::PARAM_STR);
                        $stmtUpd->bindParam(':mobile', $mobile_logged, PDO::PARAM_STR);
                        $stmtUpd->execute();
                    }
                }
                
                $mobno = $_SESSION['otp_mobile'];
                $sql = "SELECT ID, PC, UserName, Password, Per FROM Notary_Details WHERE MobNo = :mobno";
                if($stmt = $pdo_notsl->prepare($sql)){
                    $stmt->bindParam(":mobno", $mobno, PDO::PARAM_STR);
                    
                    if($stmt->execute()){
                        $count = $stmt->rowCount();
                        if($stmt->rowCount() == 1){
                        
                            if($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                                $userid = $row["ID"];
                                $username = $row["UserName"];
                                $pc = $row["PC"];
                                $per = $row["Per"];
                            }
                        }
                    }
                }
                
                // OTP එක සම්පූර්ණයෙන්ම නිවැරදියි!
                // සාර්ථක නිසා OTP සම්බන්ධ Session දත්ත මකා දමන්න
                unset($_SESSION['generated_otp']);
                unset($_SESSION['otp_expiry']);
                unset($_SESSION['otp_attempts']);

                $client_ip = $_SERVER['REMOTE_ADDR'];
                if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                    $client_ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
                }
                $_SESSION["loggedin_notsl"] = true;
                $_SESSION["notuseid_notsl"] = $userid;
                $_SESSION["notuse_notsl"] = $username; 
                $_SESSION['notper_notsl'] = $per; 
                $_SESSION['notuse_pc'] = $pc;
                $_SESSION['ipadd'] = $client_ip;   
                          
                echo '<script type="text/javascript"> window.open("/Login/home_select_pc.php","_self");</script>';
                exit;
                // [මෙතැන සිට ඉදිරියට] Username/Password පරීක්ෂාව හෝ Login කරවීම සිදු කරන්න...
                
            } else {
                // OTP එක වැරදියි! - දැන් උත්සාහ කළ වාර ගණන (Attempts) ගණනය කරමු
                
                // ප්‍රථම වතාවට වැරදුණේ නම් Counter එක 0 ලෙස අරඹන්න
                if (!isset($_SESSION['otp_attempts'])) {
                    $_SESSION['otp_attempts'] = 0;
                }
                
                // වාර ගණන 1 කින් වැඩි කරන්න
                $_SESSION['otp_attempts']++;
                
                $remaining_attempts = 5 - $_SESSION['otp_attempts']; // ඉතිරි වාර ගණන
                
                if ($_SESSION['otp_attempts'] >= 5) {
                    // අවස්ථා 3ම අවසන් වූ විට ක්‍රියාත්මක වන කොටස
                    $login_err = "ඔබ වැරදි OTP කේතයන් 5 වතාවක් ඇතුළත් කර ඇත. මෙම කේතය අවලංගුයි. කරුණාකර  නව OTP කේතයක් ලබා ගන්න.";
                    
                    // ආරක්ෂාව සඳහා දැනට ඇති OTP එක සර්වර් එකෙන් මකා දමන්න (එවිට එම OTP එක නැවත භාවිත කළ නොහැක)
                    unset($_SESSION['generated_otp']);
                    unset($_SESSION['otp_expiry']);
                    unset($_SESSION['otp_attempts']);
                } else {
                    // තවම අවස්ථා ඉතිරිව ඇති විට පෙන්වන පණිවිඩය
                    $login_err = "ඇතුළත් කළ OTP කේතය වැරදියි! ඔබට තව අවස්ථා " . $remaining_attempts . " ක් ඉතිරිව ඇත.";
                }
            }
        /*if ($user_otp != $_SESSION['generated_otp']) {
            $login_err = "ඇතුළත් කළ OTP කේතය වැරදියි!";
        } 
        else {
            // OTP එක සම්පූර්ණයෙන්ම නිවැරදියි!
            // භාවිතයෙන් පසු Session OTP දත්ත මකා දැමීම ආරක්ෂිතයි
            unset($_SESSION['generated_otp']);
            unset($_SESSION['otp_expiry']);
            echo '<script type="text/javascript"> window.open("/Login/home.php","_self");</script>';
            exit;
            // දැන් මෙතැන සිට ප්‍රධාන Username/Password පරීක්ෂාවට පාලනය රැගෙන යා හැක...*/
        }
    } else {
       //$login_err = "කරුණාකර ප්‍රථමයෙන් OTP කේතයක් ලබා ගන්න.";
    }
        
        
        
        
        if(empty(trim($_POST["mobile"]))){
                $username_err = "Please enter username or email.";
            } else{
                $username = test_input(trim($_POST["mobile"]));
            }
    }
    
    else {
        $status = "<p style='color:#FF0000; font-size:18px'><span>Please enter captcha code!</span></p>"; 
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notary Login Form</title>
    <link rel="stylesheet" href="CSS/style.css">
</head>
<body>

    <div class="login-container">
        <div class="left-banner">
            <h3 class="logo-text">SP-PRD</h3>
            <h3>Log In</h3>
            <p>Create Stampduty Pay in Slip with us before? Log in with your details</p>
            
            <div class="register-section">
                <p>New member?</p>
                <?php $provweb1 = "https://" . trim($provweb) . "/NewUser.php"; echo "<a href='$provweb1' class='btn-register'>Click here to register</a>"; ?>
            </div>
            
            <div class="help-section">
                <p>Have trouble logging in? Call us on <br><strong>0912223484 0412231496 0718296331</strong> (Monday to Friday (Only Working day) hours 9.00a.m to 4.00p.m)</p>
            </div>
        </div>

        <div class="right-form">
            <?php if(!empty($login_err)){ echo '<div style="color:red; margin-bottom:15px; font-weight:bold;">'.htmlspecialchars($login_err, ENT_QUOTES, 'UTF-8').'</div>'; } ?>
            <?php echo $status; ?>
            
            <form action="" method="POST" class="email-login">
                <div class="form-row">
                    <div class="form-group">
                        <label>Email or Username</label>
                        <input type="text" name="username" id="login_username" value = "<?php echo $notuser; ?>" placeholder="Email or Username" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" placeholder="Password" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Verification Code</label>
                        <div class="captcha-img-box">
                            <img src="captcha.php?rand=<?php echo rand(); ?>" id="captcha_image" alt="CAPTCHA">
                            <a href="javascript:void(0);" onclick="refreshCaptcha();" class="btn-refresh-captcha" title="Refresh Code">🔄</a>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Verification Code Enter Here</label>
                        <input type="text" name="captcha_input" placeholder="Enter Code" required>
                    </div>
                </div>
                
                <div class="action-row">
                    <span class='signup-notice'>New here? 
                    <?php $provweb1 = "https://" . trim($provweb) . "/NewUser.php"; echo "<a href='$provweb1' >Sign up with your email/phone number to get started.</a>"; ?>
                    </span>
                    <div class="login-btn-wrapper">
                        <?php  $provweb1 = "https://" . trim($provweb) . "/Password_Forget.php"; echo "<a href='$provweb1' class='forgot-pass'>Forgot Password?</a>"; ?>
                        <button type="submit" class="btn-login">Log In</button>
                    </div>
                </div>
            </form>

            <div class="or-separator">Or log in with your mobile number</div>

            <form action="" method="POST" class="mobile-login">
                <label>Enter your Registered Mobile number</label>
                <div class="mobile-row">
                    <div class="country-code">
                        <span>Sri Lanka</span>
                    </div>
                    <input type="text" name="mobile" placeholder="07XXXXXXXX" id="mobile_number" class="mobile-input">
                    <button type="button" class="btn-verify" id="btn_verify" onclick="sendOTP()">Verify Number</button>
                </div>

                <div class="otp-row">
                    <div class="form-group">
                        <label>OTP Number</label>
                        <input type="text" name="otp" id="otp_input" placeholder="OTP Number" class="otp-input" 
                            <?php if(!isset($_SESSION['generated_otp'])) { echo 'disabled'; } ?> 
                            style="<?php if(isset($_SESSION['generated_otp'])) { echo 'background-color: #fff;'; } ?>">
                    </div>
                    
                    <button type="submit" class="btn-submit" id="btn_submit" 
                        <?php if(!isset($_SESSION['generated_otp'])) { echo 'disabled'; } ?>> Submit</button>
                </div>

<!--
                <div class="otp-row">
                    <div class="form-group">
                        <label>OTP Number</label>
                        <input type="text" name="otp" id="otp_input" placeholder="OTP Number" class="otp-input" disabled>
                    </div>
                    <button type="submit" class="btn-submit" id="btn_submit" disabled> Submit</button>
                </div>
            </form>
        </div> -->
    </div>

<script type="text/javascript">
function refreshCaptcha() {
    var img = document.getElementById('captcha_image');
    img.src = 'captcha.php?rand=' + new Date().getTime();
}

function send__OTP() {
    var mobileInput = document.getElementById('mobile_number').value;
    if (mobileInput.trim() === "") {
        alert("කරුණාකර ප්‍රථමයෙන් දුරකථන අංකය ඇතුළත් කරන්න.");
        return;
    }
    document.getElementById('otp_input').removeAttribute('disabled');
    document.getElementById('btn_submit').removeAttribute('disabled');
    document.getElementById('otp_input').style.backgroundColor = "#fff"; 
}


function sendOTP() {
    var mobileInput = document.getElementById('mobile_number').value;
    
    // දුරකථන අංකය පරීක්ෂාව
    if (mobileInput.trim() === "") {
        alert("කරුණාකර ප්‍රථමයෙන් දුරකථන අංකය ඇතුළත් කරන්න.");
        return;
    }

    // Button එක තාවකාලිකව Disable කිරීම (නැවත නැවත Click කිරීම වැළැක්වීමට)
    var btnVerify = document.getElementById('btn_verify');
    btnVerify.innerText = "Sending...";
    btnVerify.disabled = true;

    // AJAX (Fetch) මඟින් පසුබිමෙන් PHP පිටුවකට දත්ත යැවීම
    fetch('generate_otp.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'mobile=' + encodeURIComponent(mobileInput)
    })
    .then(response => response.json())
    .then(data => {
        if(data.status === 'success') {
            
            // [අලුතින් එකතු කළ කොටස] IP එක සමාන නිසා කෙළින්ම Home එකට යාමට පැවසුවහොත්:
            if(data.redirect === true) {
                window.location.href = data.url; 
                return;
            }

            // IP වෙනස් නම් සාමාන්‍ය පරිදි OTP Input එක විවෘත වේ:
            alert("OTP කේතය ඔබගේ ජංගම දුරකථනයට සාර්ථකව යොමු කරන ලදී.");
            document.getElementById('otp_input').removeAttribute('disabled');
            document.getElementById('btn_submit').removeAttribute('disabled');
            document.getElementById('otp_input').style.backgroundColor = "#fff";
            btnVerify.innerText = "Resend OTP";
            btnVerify.disabled = false;
        } else {
            alert("දෝෂයක් ඇති විය: " + data.message);
            btnVerify.innerText = "Verify Number";
            btnVerify.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert("සම්බන්ධතා දෝෂයක් ඇති  විය!");
        btnVerify.disabled = false;
    });
}
</script>
</body>
</html>