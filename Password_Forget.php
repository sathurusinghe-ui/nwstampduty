<?php  
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    require 'src/Exception.php';
    require 'src/PHPMailer.php';
    require 'src/SMTP.php';

    function test_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';
 
$user = isset($_POST['uname']) ? test_input($_POST['uname']) : '';

$username_err = "";
$nicno_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    if (empty(trim($_POST["uname"]))) {
        $username_err = "Please enter username.";
    } else {
        $notusername = test_input(trim($_POST["uname"]));
    }
        
    if (empty(trim($_POST["nicno"]))) {
        $nicno_err = "Please enter your NIC No or Reg. No.";
    } else {
        $nicno = test_input(trim($_POST["nicno"]));
    }
        
    include_once ROOT_PATH_NOT ."DBPath/Conn_notspprd.php";
    
    if (empty($username_err) && empty($nicno_err)) {
        // SQL Injection වැලැක්වීම සඳහා Named Parameters භාවිතය
        $sql = "SELECT ID, UserName, Password, EmailAdd, Block, RequireReset, Activation, Pass, NICNo, MobNo FROM Notary_Details WHERE UserName = :username OR EmailAdd = :username";
                
        if ($stmt = $pdo_notsl->prepare($sql)) {
            $stmt->bindParam(":username", $notusername, PDO::PARAM_STR);
                    
            if ($stmt->execute()) {
                if ($stmt->rowCount() == 1) {
					
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    $nic_no = $row["NICNo"];
                    $block  = $row["Block"];
                    
                    // ගිණුම අක්‍රීය කර ඇත්නම් (Blocked)
                    if ($block == '1') {
                        $pdo_notsl = null; // නිවැරදි Connection එක වැසීම
                        header('Location: https://'.$provweb.'/Account_Disabled.php'); 
                        exit();
                    }
                    
                    // NIC අංකය සහ අවහිර නොකළ ගිණුමක්දැයි පරීක්ෂාව
                    if (strtoupper(trim($nicno)) == strtoupper(trim($nic_no)) && $block == '0') {
                        $email  = $row["EmailAdd"];
                        $notmob = $row["MobNo"];
                        
                        date_default_timezone_set("Asia/Colombo");
                        $today  = date("Y/m/d");
                        $totime = date("h:i:sa");
                        
                        $psw  = rand(100000, 999999);
                        $hash = password_hash($psw, PASSWORD_DEFAULT);
                        //$todaytime = $today . " " . $totime;
                        
                        // මුරපදය අලුත් කිරීමේ කේතය (Temporary Hash) සුරැකීම
                        $sqlAdd = "UPDATE Notary_Details SET LastVisitDate = ?, RequireReset = '1', Pass = ? WHERE UserName = ? OR EmailAdd = ?";
                        $stmtUpdate = $pdo_notsl->prepare($sqlAdd);
                        $stmtUpdate->execute([$todaytime, $hash, $notusername, $notusername]);
                        
                        // Email පණිවිඩය සකස් කිරීම
                        /*
                        $subject = "Password Renew Code";
                        $txt     = "Your Password Renew Code is " . $psw . "\n" . $provwebhttp . "\n\n OR \n\n" . " Click ".$provweb."/Login/User_Foget_Password_Verify.php?q=" . urlencode($hash) . "&&qu=" . urlencode($notusername);
                        
                        $headers = 'From: '.$provnoreply;

                        mail($email, $subject, $txt, $headers);*/
                        
                        $clean_provweb = trim($provweb);
                        $subject = "Password Renew Code :- https://" . trim($provweb);
                        $txt = "<html>
                            <head>
                            <title>Password Renew</title>
                            </head>
                            <body>
                            <p>Your Password Renew Code is $psw </p>
                            <p>OR </p>
                            <p><a href='https://{$clean_provweb}/Login/User_Foget_Password_Verify.php?qu={$notusername}&q={$hash}'>Click to Your Password Renew</a></p>
                            </body>
                            </html>";
                                
                        if ($provnoreply <> "") {
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
                        
                        
                        $towhour = date("H"); 
                        $msgid   = $notmob . "" . $today . "-" . $totime;
                        $msg     = "Please don’t share this with anyone. use " . $psw . " as OTP in Reset Your Password in Revenue Dept. or click ".$provweb."/Login/User_Foget_Password_Verify.php?q=$hash&&qu=$notusername";
                        
                        $msgExists = 0;
                        
                        if ($msgExists == 0) { 
                            // NULL නොවන බව සහ හිස් නොවන බව තහවුරු කරගැනීම
                            if (!empty($notmob) && $notmob <> '0710000000') { 
                                
                                if ($smsusername <> '') {
                                    include ROOT_PATH_NOT ."DBPath/ESMSWS.php";
                                    $session      = createSession('', $smsusername, $smspassword, '');
                                    $sendMessages = sendMessages($session, $smsmask, $msg, explode(",", $notmob), 0);
                                    closeSession($session);
                                }

                                // පරීක්ෂාව 1: Database Connection එක සක්‍රීයදැයි බැලීම
                                if (!isset($pdo_notsl) || $pdo_notsl === null) {
                                  /*  echo "<div style='color:red; background:#fff; padding:10px; border:1px solid red; margin:5px;'>";
                                    echo "<strong>[ERROR]</strong> \$pdo_msg සම්බන්ධතාවය (Connection Object) අර්ථ දක්වා නැත හෝ හිස් ය!";
                                    echo "</div>";
                                    exit();*/
                                }
                                
                                $sqlmsg = "INSERT INTO msg (MSGID, FileNo, MSGTo, MSG, MSGGenLoger, MSGGenDate, MSGGenTime, Action) 
                                           VALUES (:msgid, :fno, :msgto, :msga, :logloger, :logdate, :logtime, 'S')";
                                                                    
                                if ($stmtmsg = $pdo_notsl->prepare($sqlmsg)) {
                                    
                                    $stmtmsg->bindParam(':msgid', $msgid, PDO::PARAM_STR);
                                    $stmtmsg->bindParam(':fno', $nic_no, PDO::PARAM_STR);
                                    $stmtmsg->bindParam(':msgto', $notmob, PDO::PARAM_STR);
                                    $stmtmsg->bindParam(':msga', $msg, PDO::PARAM_STR);
                                    $stmtmsg->bindParam(':logloger', $notusername, PDO::PARAM_STR);
                                    $stmtmsg->bindParam(':logdate', $today, PDO::PARAM_STR);
                                    $stmtmsg->bindParam(':logtime', $totime, PDO::PARAM_STR);
                                    
                                    // පරීක්ෂාව 2: Execute වීමේදී සිදුවන දේ බැලීම
                                    try {
                                        if ($stmtmsg->execute()) {
                                          /*  echo "<div style='color:green; background:#fff; padding:10px; border:1px solid green; margin:5px;'>";
                                            echo "<strong>[SUCCESS]</strong> දත්ත සාර්ථකව msg වගුවට ඇතුළත් විය! (ID: " . htmlspecialchars($msgid) . ")";
                                            echo "</div>";
                                        exit();*/
                                        } else {
                                          /* // Execute අසාර්ථක වුවහොත් දෝෂය පෙන්වීම
                                            $errorInfo = $stmtmsg->errorInfo();
                                            echo "<div style='color:red; background:#fff; padding:10px; border:1px solid red; margin:5px;'>";
                                            echo "<strong>[EXECUTE ERROR]</strong> ASD" . $errorInfo[2];
                                            echo "</div>";
                                        exit();*/
                                        }
                                    } catch (PDOException $e) {
                                       /* // PDO Exception එකක් ආවොත් (උදා: වගුව නොමැති වීම, Column නම වැරදි වීම)
                                        echo "<div style='color:orange; background:#fff; padding:10px; border:1px solid orange; margin:5px;'>";
                                        echo "<strong>[DATABASE EXCEPTION]</strong> ASD" . $e->getMessage();
                                        echo "</div>";
                                        exit();*/
                                    }
                                
                                } else {
                                    // Prepare අදියරේදී දෝෂයක් ආවොත්
                                    /*$errorInfo = $pdo_notsl->errorInfo();
                                    echo "<div style='color:red; background:#fff; padding:10px; border:1px solid red; margin:5px;'>";
                                    echo "<strong>[PREPARE ERROR]</strong> SQL කේතයේ දෝෂයක් ඇත: " . $errorInfo[2];
                                    echo "</div>";
                                    exit();*/
                                }
                            }
                        }
                        
                         // පරිශීලක ක්‍රියාකාරකම් සටහන් කිරීම (History Log)
                        $addyear = date("Y");
                        
                                //echo "ABCDD".$addyear;
                        $target_dir_user = ROOT_PATH_NOT . "History/User/Notary/" . $addyear . "/";
                        
                        if (!is_dir($target_dir_user)) {
                            mkdir($target_dir_user, 0755, true);
                        } 
                        
                        $ipadd = '0.0.0.0';
                        if (!empty($_SERVER['HTTP_CLIENT_IP'])) { $ipadd = $_SERVER['HTTP_CLIENT_IP']; }
                        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) { $ipadd = $_SERVER['HTTP_X_FORWARDED_FOR']; }
                        else { $ipadd = $_SERVER['REMOTE_ADDR']; }
                        
                        $conna    = "Unknown";
                        $cityna   = "Unknown";
                        $clientna = gethostbyname(gethostname());
                        
                        $ip_info = @file_get_contents("http://ipinfo.io/$ipadd/json");
                        if ($ip_info) {
                            $clientDetails = json_decode($ip_info);
                            $conna  = $clientDetails->country ?? 'Unknown';
                            $cityna = $clientDetails->city ?? 'Unknown';
                        }
                        
                        // $username වෙනුවට නිවැරදි $notusername යොදා ඇත
                        $username_upper  = strtoupper($notusername);
                        $usernamea       = str_replace("/", "_", $username_upper);
                        
                        $myfile = fopen($target_dir_user . "" . $usernamea . ".txt", "a");
                        if ($myfile) {
                            $logTxt = "\nLog to Notary Forgot Password\t" . $ipadd . "\t" . $clientna . "\t" . $conna . "-" . $cityna . "\t" . $today . " - " . $totime;
                            fwrite($myfile, $logTxt);
                            fclose($myfile);
                        }
                        
                        $pdo_notsl = null; // නිවැරදි සම්බන්ධතාවය වැසීම
                        echo '<script type="text/javascript"> window.open("/index.php","_self");</script>';    
                        exit();
                    } else {
                        $pdo_notsl = null;
                        header("Location: /Password_Forget.php"); 
                        exit();
                    }
                }
				
				
				
				else {
					// Non Notary User
					// SQL Injection වැලැක්වීම සඳහා Named Parameters භාවිතය
					$sql = "SELECT ID, UserName, Password, EmailAdd, Block, RequireReset, Activation, Pass, NICNo, MobNo FROM User_All WHERE UserName = :username OR EmailAdd = :username";
							
					if ($stmt = $pdo_notsl->prepare($sql)) {
						$stmt->bindParam(":username", $notusername, PDO::PARAM_STR);
								
						if ($stmt->execute()) {
							if ($stmt->rowCount() == 1) {
								
								$row = $stmt->fetch(PDO::FETCH_ASSOC);
								$nic_no = $row["NICNo"];
								$block  = $row["Block"];
								
								// ගිණුම අක්‍රීය කර ඇත්නම් (Blocked)
								if ($block == '1') {
									$pdo_notsl = null; // නිවැරදි Connection එක වැසීම
									header('Location: https://'.$provweb.'/Account_Disabled.php'); 
									exit();
								}
								
								// NIC අංකය සහ අවහිර නොකළ ගිණුමක්දැයි පරීක්ෂාව
								if (strtoupper(trim($nicno)) == strtoupper(trim($nic_no)) && $block == '0') {
									$email  = $row["EmailAdd"];
									$notmob = $row["MobNo"];
									
									date_default_timezone_set("Asia/Colombo");
									$today  = date("Y/m/d");
									$totime = date("h:i:sa");
									
									$psw  = rand(100000, 999999);
									$hash = password_hash($psw, PASSWORD_DEFAULT);
									//$todaytime = $today . " " . $totime;
									
									// මුරපදය අලුත් කිරීමේ කේතය (Temporary Hash) සුරැකීම
									$sqlAdd = "UPDATE User_All SET LastVisitDate = ?, RequireReset = '1', Pass = ? WHERE UserName = ? OR EmailAdd = ?";
									$stmtUpdate = $pdo_notsl->prepare($sqlAdd);
									$stmtUpdate->execute([$todaytime, $hash, $notusername, $notusername]);
									
									// Email පණිවිඩය සකස් කිරීම
																		
									$clean_provweb = trim($provweb);
									$subject = "Password Renew Code :- https://" . trim($provweb);
									$txt = "<html>
										<head>
										<title>Password Renew</title>
										</head>
										<body>
										<p>Your Password Renew Code is $psw </p>
										<p>OR </p>
										<p><a href='https://{$clean_provweb}/Login/User_Foget_Password_Verify.php?qu={$notusername}&q={$hash}'>Click to Your Password Renew</a></p>
										</body>
										</html>";
											
									if ($provnoreply <> "") {
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
									
									
									$towhour = date("H"); 
									$msgid   = $notmob . "" . $today . "-" . $totime;
									$msg     = "Please don’t share this with anyone. use " . $psw . " as OTP in Reset Your Password in Revenue Dept. or click ".$provweb."/Login/User_Foget_Password_Verify.php?q=$hash&qu=$notusername";
									
									$msgExists = 0;
									
									if ($msgExists == 0) { 
										// NULL or Not
										if (!empty($notmob) && $notmob <> '0710000000') { 
											
											if ($smsusername <> '') {
												include ROOT_PATH_NOT ."DBPath/ESMSWS.php";
												$session      = createSession('', $smsusername, $smspassword, '');
												$sendMessages = sendMessages($session, $smsmask, $msg, explode(",", $notmob), 0);
												closeSession($session);
											}

											// පරීක්ෂාව 1: Database Connection එක සක්‍රීයදැයි බැලීම
											if (!isset($pdo_notsl) || $pdo_notsl === null) {
											  /*  echo "<div style='color:red; background:#fff; padding:10px; border:1px solid red; margin:5px;'>";
												echo "<strong>[ERROR]</strong> \$pdo_msg සම්බන්ධතාවය (Connection Object) අර්ථ දක්වා නැත හෝ හිස් ය!";
												echo "</div>";
												exit();*/
											}
											
											$sqlmsg = "INSERT INTO msg (MSGID, FileNo, MSGTo, MSG, MSGGenLoger, MSGGenDate, MSGGenTime, Action) 
													   VALUES (:msgid, :fno, :msgto, :msga, :logloger, :logdate, :logtime, 'S')";
																				
											if ($stmtmsg = $pdo_notsl->prepare($sqlmsg)) {
												
												$stmtmsg->bindParam(':msgid', $msgid, PDO::PARAM_STR);
												$stmtmsg->bindParam(':fno', $nic_no, PDO::PARAM_STR);
												$stmtmsg->bindParam(':msgto', $notmob, PDO::PARAM_STR);
												$stmtmsg->bindParam(':msga', $msg, PDO::PARAM_STR);
												$stmtmsg->bindParam(':logloger', $notusername, PDO::PARAM_STR);
												$stmtmsg->bindParam(':logdate', $today, PDO::PARAM_STR);
												$stmtmsg->bindParam(':logtime', $totime, PDO::PARAM_STR);
												
												// පරීක්ෂාව 2: Execute වීමේදී සිදුවන දේ බැලීම
												try {
													if ($stmtmsg->execute()) {
													  /*  echo "<div style='color:green; background:#fff; padding:10px; border:1px solid green; margin:5px;'>";
														echo "<strong>[SUCCESS]</strong> දත්ත සාර්ථකව msg වගුවට ඇතුළත් විය! (ID: " . htmlspecialchars($msgid) . ")";
														echo "</div>";
													exit();*/
													} else {
													  /* // Execute අසාර්ථක වුවහොත් දෝෂය පෙන්වීම
														$errorInfo = $stmtmsg->errorInfo();
														echo "<div style='color:red; background:#fff; padding:10px; border:1px solid red; margin:5px;'>";
														echo "<strong>[EXECUTE ERROR]</strong> ASD" . $errorInfo[2];
														echo "</div>";
													exit();*/
													}
												} catch (PDOException $e) {
												   /* // PDO Exception එකක් ආවොත් (උදා: වගුව නොමැති වීම, Column නම වැරදි වීම)
													echo "<div style='color:orange; background:#fff; padding:10px; border:1px solid orange; margin:5px;'>";
													echo "<strong>[DATABASE EXCEPTION]</strong> ASD" . $e->getMessage();
													echo "</div>";
													exit();*/
												}
											
											} else {
												// Prepare අදියරේදී දෝෂයක් ආවොත්
												/*$errorInfo = $pdo_notsl->errorInfo();
												echo "<div style='color:red; background:#fff; padding:10px; border:1px solid red; margin:5px;'>";
												echo "<strong>[PREPARE ERROR]</strong> SQL කේතයේ දෝෂයක් ඇත: " . $errorInfo[2];
												echo "</div>";
												exit();*/
											}
										}
									}
									
									 // පරිශීලක ක්‍රියාකාරකම් සටහන් කිරීම (History Log)
									$addyear = date("Y");
									
											//echo "ABCDD".$addyear;
									$target_dir_user = ROOT_PATH_NOT . "History/User/Dept/" . $addyear . "/";
									
									if (!is_dir($target_dir_user)) {
										mkdir($target_dir_user, 0755, true);
									} 
									
									$ipadd = '0.0.0.0';
									if (!empty($_SERVER['HTTP_CLIENT_IP'])) { $ipadd = $_SERVER['HTTP_CLIENT_IP']; }
									elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) { $ipadd = $_SERVER['HTTP_X_FORWARDED_FOR']; }
									else { $ipadd = $_SERVER['REMOTE_ADDR']; }
									
									$conna    = "Unknown";
									$cityna   = "Unknown";
									$clientna = gethostbyname(gethostname());
									
									$ip_info = @file_get_contents("http://ipinfo.io/$ipadd/json");
									if ($ip_info) {
										$clientDetails = json_decode($ip_info);
										$conna  = $clientDetails->country ?? 'Unknown';
										$cityna = $clientDetails->city ?? 'Unknown';
									}
									
									// $username වෙනුවට නිවැරදි $notusername යොදා ඇත
									$username_upper  = strtoupper($notusername);
									$usernamea       = str_replace("/", "_", $username_upper);
									
									$myfile = fopen($target_dir_user . "" . $usernamea . ".txt", "a");
									if ($myfile) {
										$logTxt = "\nLog to Notary Forgot Password\t" . $ipadd . "\t" . $clientna . "\t" . $conna . "-" . $cityna . "\t" . $today . " - " . $totime;
										fwrite($myfile, $logTxt);
										fclose($myfile);
									}
									
									$pdo_notsl = null; // නිවැරදි සම්බන්ධතාවය වැසීම
									echo '<script type="text/javascript"> window.open("/index.php","_self");</script>';    
									exit();
								} else {
									$pdo_notsl = null;
									header("Location: /Password_Forget.php"); 
									exit();
								}
							}
						else {
							
						}
						}
					}
				}
			}
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Password Request Form</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; background-color: #f4f7f6; }
        form { border: 1px solid #ddd; padding: 10px; border-radius: 12px; background: #fff; }
        
        input[type=text], select {
            width: 100%; 
            padding: 12px 20px; 
            margin: 8px 0;
            display: inline-block; 
            border: 1px solid #ccc; 
            box-sizing: border-box;
            border-radius: 20px; 
            outline: none;
            font-size: 15px;
            transition: 0.3s;
        }
        
        input[type=text]:focus {
            border-color: #32CD32;
            box-shadow: 0 0 5px rgba(50, 205, 50, 0.5);
        }

        .loginbtn {
            background-color: #32CD32; 
            color: white; 
            padding: 12px 20px;
            margin: 12px 0 8px 0; 
            border: none; 
            cursor: pointer; 
            width: 100%; 
            font-size: 16px;
            font-weight: bold;
            border-radius: 20px; 
            transition: 0.3s;
        }
        .loginbtn:hover { opacity: 0.9; background-color: #28a745; }
        
        .imgcontainer { text-align: center; margin: 15px 0 5px 0; }
        img.avatar { width: 35%; max-width: 150px; border-radius: 50%; }
        .container { padding: 16px; width: 100%; box-sizing: border-box; }
        .container1 { padding: 16px; width: 90%; max-width: 450px; margin: 40px auto; background-color:#f9f9f9; border-radius: 15px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
<div class="container1">
    <h2 style="text-align: center; color: #333; margin-bottom: 5px;">Password Request Form</h2>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <div class="imgcontainer">
            <img src="/Icon/Password/forgotpassword.jpg" alt="Avatar" class="avatar">
        </div>

        <div class="container">
            <?php if(!empty($username_err)) echo "<p style='color:red; font-size:14px; margin:0;'>$username_err</p>"; ?>
            <?php if(!empty($nicno_err)) echo "<p style='color:red; font-size:14px; margin:0;'>$nicno_err</p>"; ?>

            <label for="uname" style="font-weight: bold; color: #555;">Username</label>
            <input type="text" placeholder="Enter Username" name="uname" value="<?php echo htmlspecialchars($user); ?>" required>

            <label for="nicno" style="font-weight: bold; color: #555;">N.I.C. No / Reg. No</label>
            <input type="text" placeholder="Enter N.I.C. No or Reg. No" name="nicno" required>
                
            <input type="submit" name="send" value="Send OTP" class="loginbtn">
            
            <h4 style="color: green; text-align: center; font-weight: normal; margin-top: 15px;">Click 'Send' Button and Check Your Phone for OTP</h4>
        </div>
    </form>
</div>
</body>
</html>