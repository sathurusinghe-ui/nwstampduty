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
    
    function test_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }    
 $psw = '';   
$user_notsl = $_SESSION['notuse_notsl'] ?? '';
$per        = $_SESSION['per_notsl'] ?? '';
$ipadd      = $_SESSION['ipadd_notsl'] ?? '';
$clientna   = $_SESSION['clientna_notsl'] ?? '';
$conna      = $_SESSION['conna_notsl'] ?? '';
$cityna     = $_SESSION['cityna_notsl'] ?? '';
$path_notsl = $_SESSION['path_notsl'] ?? '';
$msg        = $_SESSION["msg_notsl"] ?? '';
$ref        = $_SESSION["ref_notsl"] ?? '';
       
    $LineNo = array(""); 
    $LineE  = array(""); 
    $LineS  = array(""); 
    $LineT  = array(""); 
    $LineH  = array("");  
    $LineP  = array(""); 
    $LineA  = array(""); 
    $DeptWebRaw = 0;
    
    $sql = "SELECT * FROM Web_Online_User_Reg ORDER BY No";

    $result = $pdo_notsl->query($sql);
    
    if ($result) {
        $DeptWebRaw = $result->rowCount();
        
        $lang = isset($_GET['lang']) ? $_GET['lang'] : '';  
        
        if ($DeptWebRaw > 0) {
            while($row = $result->fetch(PDO::FETCH_ASSOC)) {
                array_push($LineNo, $row["No"]);
                array_push($LineE, $row["NameE"]);
                array_push($LineS, $row["NameS"]);
                array_push($LineT, $row["NameT"]);
                array_push($LineH, $row["NameH"]);
                array_push($LineA, $row["LinkA"]);
                
                if ($lang == 'si') {  
                    array_push($LineP, $row["NameS"]);
                } else if ($lang == 'ta') {  
                    array_push($LineP, $row["NameT"]);
                } else {  
                    array_push($LineP, $row["NameE"]);
                }
            }
        } else { 
            echo "0 results - Web_Online_User_Reg"; 
        }
    } else {
        echo "Query Execution Failed.";
    }
    
    
   
if(isset($_POST['send']))   // it checks whether the user clicked login button or not 
{
    $nameErr = "";
    $newuser = test_input($_POST['uname']);
    
    $lrnotno   = test_input($_POST['lrnotno'] ?? '');
    $notregno  = test_input($_POST['notregno'] ?? '');
    $seccopy   = test_input($_POST['seccopy'] ?? '');
    
    // Trilingual රෙජිස්ටර් අංක ලබා ගැනීම
    $notregnoe = test_input($_POST['notregnoe'] ?? '');
    $notregnos = test_input($_POST['notregnos'] ?? '');
    $notregnot = test_input($_POST['notregnot'] ?? '');
    
    $notnic = test_input($_POST['notnic'] ?? '');
    $nottitle = test_input($_POST['nottitle'] ?? '');
    $notfnae = test_input($_POST['notfnae'] ?? '');
    if (!preg_match("/^[a-zA-Z' ]*$/", $notfnae)) {
        $nameErr .= $LineE[9]." :- Only English letters, and white space allowed.</br>";
    }
    $notfnas = test_input($_POST['notfnas'] ?? '');
    $notfnat = test_input($_POST['notfnat'] ?? '');
    $notsnae = test_input($_POST['notsnae'] ?? '');
    if (!preg_match("/^[a-zA-Z.' ]*$/",$notsnae)) {
      $nameErr .= $LineE[10]." :- Only letters, .(dot) and white space allowed.</br>";
    }
    $notsnas = test_input($_POST['notsnas'] ?? '');
    $notsnat = test_input($_POST['notsnat'] ?? '');
    
    $notadd1e = test_input($_POST['notadd1e'] ?? '');
    $notadd1s = test_input($_POST['notadd1s'] ?? '');
    $notadd1t = test_input($_POST['notadd1t'] ?? '');
    $notadd2e = test_input($_POST['notadd2e'] ?? '');
    $notadd2s = test_input($_POST['notadd2s'] ?? '');
    $notadd2t = test_input($_POST['notadd2t'] ?? '');
    $notadd3e = test_input($_POST['notadd3e'] ?? '');
    $notadd3s = test_input($_POST['notadd3s'] ?? '');
    $notadd3t = test_input($_POST['notadd3t'] ?? '');
    $email = test_input($_POST['notemail'] ?? '');

    if (empty($email)) {
        $emailErr .= $LineE[14]." :- විද්‍යුත් තැපැල් ලිපිනය අනිවාර්ය වේ (Email is required).</br>";
    } 
    else {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailErr .= $LineE[14]." :- Invalid email format (e.g., name@domain.com).</br>"; 
        }
    }
    
    $nottel = test_input($_POST['nottel']);
    if ($nottel <> '') {
        if (!preg_match("/^[0-9]{10}$/", $nottel)) {
            $telErr .= $LineE[12]." :- Invalid phone number. Only 10 digits allowed (e.g., 0912223484).</br>";
        }
    }
    
    $notmob = test_input($_POST['notmob']);
    if ($notmob <> '') {
        if (!preg_match("/^[0-9]{10}$/", $notmob)) {
            $telErr .= $LineE[13]." :- Invalid phone number. Only 10 digits allowed (e.g., 0719207422).</br>";
        }
    }
        
    // Check Land Reg No
    if (substr($lrnotno,1,1) <>"/" ) { echo " Pl Enter Valid Code No of Land Registry"; exit; }
    
     //////// Login Details
	$nowday = $nowtime = $arrivaltime = $arrivaldate =  $addyear = $nowhour = '';
    date_default_timezone_set("Asia/Colombo");
    $nowday = date("Y/m/d") ?? '';
    $nowtime = date("H:i:s") ?? '';
    $arrivaltime = $_SESSION['nowtime'] ?? '';
    $arrivaldate = $_SESSION['nowday'] ?? '';
    $addyear=date("Y") ?? '';
    $nowhour = date("h") ?? '';
    
    if ( $lrnotno <> '' ) {
        
        if (preg_match('/^([0-9])\/([0-9]{1,2})\/([0-9]{2})\/([0-9]+)$/', $lrnotno, $matches)) {
            
            $lrProv = $matches[1];
            $lrDist = $matches[2];
            $lrLand = $matches[3];
            $lrCode = $matches[4];
    
            if ($lrProv > 9 || $lrDist > 25 || $lrLand > 60 || $lrCode > 1000000) {
                echo "Pl. Enter Correct Notary Code"; 
                exit;
            }
    
            $sqlNList = "SELECT LandRegCode FROM NotaryList WHERE LandRegCode = :lrnotno";
            $stmt = $pdo_notsl->prepare($sqlNList);
            $stmt->execute([':lrnotno' => $lrnotno]);
            
            if (!$stmt->fetch()) { 
            
                $sqlNDetails = "SELECT NotaryCode FROM Notary_Details WHERE NotaryCode = :lrnotno";
                $stmtDetails = $pdo_notsl->prepare($sqlNDetails);
                $stmtDetails->execute([':lrnotno' => $lrnotno]);
                
                if (!$stmtDetails->fetch()) {
                }
            }
        
        } else {
            echo "Pl. Enter Correct Notary Code"; 
            exit;
        }
    
        // 1. Check NotaryList using secure prepared statements
        $sqlNList = "SELECT LandRegCode FROM NotaryList WHERE LandRegCode = :lrnotno";
        $stmt = $pdo_notsl->prepare($sqlNList);
        $stmt->execute(['lrnotno' => $lrnotno]);
        
        if ($stmt->rowCount() == 0) { 
            
            // 2. Check notary_details table
            $sqlNDetails = "SELECT NotaryCode FROM Notary_Details WHERE NotaryCode = :lrnotno";
            $stmt = $pdo_notsl->prepare($sqlNDetails);
            $stmt->execute(['lrnotno' => $lrnotno]);
            
            if ($stmt->rowCount() == 0) { 
                
                // Validation rules check
                if ($lrProv == '' || $lrProv > 9 || $lrDist == '' || $lrDist > 25 || $lrLand == '' || $lrLand > 60 || $lrCode == '' || $lrCode > 1000000) {
                    
                    $connaip = $conna . "-" . $ipadd . "-" . $nowday;
                    
                    if ($ipadd != '') { 
                        $sqlBLItem = "SELECT IPAddTime FROM BlackList WHERE Black_List_Item = :connaip";
                        $stmtBL = $pdo_notsl->prepare($sqlBLItem);
                        $stmtBL->execute(['connaip' => $connaip]);
                        $rowBLItem = $stmtBL->fetch(PDO::FETCH_ASSOC);
                     
                        if (!$rowBLItem) { 
                            $sqlBlackList = "INSERT INTO BlackList (Black_List_Item, IPAdd, IPAddTime, AddDate) 
                                             VALUES (:connaip, :ipadd, :ipaddtime, :nowday)";
                            $pdo_notsl->prepare($sqlBlackList)->execute([
                                'connaip'   => $connaip,
                                'ipadd'     => $ipadd,
                                'ipaddtime' => $ipaddtime,
                                'nowday'    => $nowday
                            ]);
                        } else {
                            $new_ipaddtime = (int)$rowBLItem['IPAddTime'] + 1;
                            $sqlBlackList = "UPDATE BlackList SET IPAddTime = :ipaddtime WHERE Black_List_Item = :connaip";
                            $pdo_notsl->prepare($sqlBlackList)->execute([
                                'ipaddtime' => $new_ipaddtime,
                                'connaip'   => $connaip
                            ]);
                            $ipaddtime = $new_ipaddtime; // keeping local variable updated
                        }
                    }
                
                    // If spam threshold hit, flag identifier fields in blacklist
                    if ($ipaddtime >= 500) {
                        $itemsToBlacklist = [
                            'nottel'  => $nottel,
                            'notmob'  => $notmob,
                            'email'   => $email,
                            'lrnotno' => $lrnotno,
                            'notnic'  => $notnic
                        ];
        
                        foreach ($itemsToBlacklist as $item) {
                            if ($item != '') {
                                $sqlCheck = "SELECT 1 FROM BlackList WHERE Black_List_Item = :item";
                                $stmtCheck = $pdo_notsl->prepare($sqlCheck);
                                $stmtCheck->execute(['item' => $item]);
                                
                                if ($stmtCheck->rowCount() == 0) {
                                    $sqlInsert = "INSERT INTO BlackList (Black_List_Item, IPAdd, IPAddTime, AddDate) 
                                                  VALUES (:item, :ipadd, '1', :nowday)";
                                    $pdo_notsl->prepare($sqlInsert)->execute([
                                        'item'   => $item,
                                        'ipadd'  => $ipadd,
                                        'nowday' => $nowday
                                    ]);
                                }
                            }
                        }
                    }
                    
                    // Build fallback output UI elements
                    echo " Pl Send Whatsapp Your Flowing Details to ".$whatsappno." \n Land Reg Code- \n Full Name \n Name With Initial\n Address \n Tel No \n Mobile No \n E-mail address"; 
                    
                    $msg = urlencode("*") . "I Can t Create User Name in ".$provweb." My Details as Following, Land Reg Code : $lrnotno" . urlencode("*") . "" . urlencode("\n") . ", Full Name : $notfnae" . urlencode("\n") . ", Name with Initials : $notsnae" . urlencode("\n") . ", Address : $notadd1e, $notadd2e, $notadd3e " . urlencode("\n") . ", Tel No : $nottel" . urlencode("\n") . ", Mobile No : $notmob" . urlencode("\n") . ", Email Address : $email";
                    $msgAA = "I Can't Create User Name in ".$provweb." My Details as Following, </br> Land Reg Code : $lrnotno </br> Full Name : $notfnae </br> Name with Initials : $notsnae </br> Address : $notadd1e, $notadd2e, $notadd3e  </br>  Tel No : $nottel </br> Mobile No : $notmob </br> Email Address : $email";
                    
                    echo "<table>";
                    echo "<tr> <td> <h1> MSG : </h1></td> <td>  $msgAA </td> </tr>";
                    echo "<tr> <td> <h1> Send : </h1> </td> <td><a href='https://web.whatsapp.com/send/?phone=['.$whatsappno.']&text=$msg'>Send Whatsapp to '.$whatsappno.' </a> </td> </tr>";
                    echo "</table>";
                    
                    // Format descriptive system logs
                    $notiflink = "https://t.spprd.lk/" . $noofid;
                    $notifmsg = 'Notary Without Land Reg Code - Try To Create Login ';
                    if ($lrnotno != '') { $notifmsg .= "\nLand Reg Code -" . $lrnotno; }
                    if ($notnic  != '') { $notifmsg .= "\nNIC -" . $notnic; }
                    if ($notfnae != '') { $notifmsg .= "\nFull Name -" . $notfnae; }
                    if ($notfnas != '') { $notifmsg .= "\nFull Name -" . $notfnas; }
                    if ($notfnat != '') { $notifmsg .= "\nFull Name -" . $notfnat; }
                    if ($notsnae != '') { $notifmsg .= "\nFull Name -" . $notsnae; }
                    if ($notsnas != '') { $notifmsg .= "\nFull Name -" . $notsnas; }
                    if ($notsnat != '') { $notifmsg .= "\nFull Name -" . $notsnat; }
                    if ($notmob  != '') { $notifmsg .= "\nMob -" . $notmob; }
                    if ($nottel  != '') { $notifmsg .= "\nTel -" . $nottel; }
                    if ($email   != '') { $notifmsg .= "\nEmail -" . $email; }
                                        
                    $nextime = date('Y-m-d H:i:s');    
                    
                    $sqlnotif = "INSERT INTO Notification (title, notif_msg, notif_time, notif_repeat, notif_loop, publish_date, username, Link, AddLoger, AddDate, AddTime) 
                                 VALUES (:title, :msg, :notif_time, '2', '1', :publish_date, 'Kapila', :link, :ip, :nowday, :nowtime)";
                    
                    $pdo_notsl->prepare($sqlnotif)->execute([
                        'title'        => $fno,
                        'msg'          => $notifmsg,
                        'notif_time'   => $nextime,
                        'publish_date' => $nextime,
                        'link'         => $notiflink,
                        'ip'           => $ipadd,
                        'nowday'       => $nowday,
                        'nowtime'      => $nowtime
                    ]);
                    
                    // In PDO, closing connection means destroying references/setting to null
                    $pdo_notsl = null; 
                                        
                    exit; 
                }
            }
        }
    }
    
    
    
    
    
    if ($nameErr <> '') { 
        echo "<h2>Your Following Input: Invalid</h2></br>";
        echo $nameErr; exit; }
    
    $notna = $notfnae." ".$notfnas." ".$notfnat;
    
    // 1. Program Logging Directory Initialization
    $target_dir_pro = ROOT_PATH_NOT . "History/Program/" . $addyear . "/";
   
    // Recursively create the entire directory path if it doesn't exist (0755 permissions are standard)
    if (!is_dir($target_dir_pro)) {
    	if (!@mkdir($target_dir_pro, 0777, true) && !is_dir($target_dir_pro)) {
        	error_log("Failed to create directory: " . $target_dir_pro); 
    	}
    } 
    if (!is_dir($target_dir_pro)) {
    @mkdir($target_dir_pro, 0777, true);
}




    // 2. Format the Log Content
    $file_path = $target_dir_pro . "New_User_Notary.txt";
    
    $txt_content  = "\n----------------------------------------------------------------------";
    $txt_content .= "\nCreate New Notary User - " . $newuser;
    $txt_content .= "\nLoger\t" . $newuser . "\t" . $ipadd . "\t" . $clientna . "\t" . $conna . "-" . $cityna . "\t" . $nowday . " - " . $nowtime;
    
    // 3. Write securely to file (creates the file automatically if missing)
    // LOCK_EX prevents two operations from writing to the log file at the exact same millisecond
   // file_put_contents($log_file, $txt, FILE_APPEND | LOCK_EX);
    // 2. File එක ලිවීමට උත්සාහ කරන්න
if (is_dir($target_dir_pro)) {
    file_put_contents($file_path, $txt_content); // $txt_content යනු ඔබ ලියන දත්තයි
} else {
    error_log("Directory missing: " . $target_dir_pro);
}
    ///// End Login Details


    $sql = "SELECT * FROM Notary_Details WHERE UserName = :username LIMIT 1";
    $stmt = $pdo_notsl->prepare($sql);
    $stmt->execute(['username' => $newuser]);
    
    // 2. Use PDO's fetch() or rowCount() to verify existence
    if ($stmt->fetch()) { 
        $qu = "Username " . htmlspecialchars($newuser, ENT_QUOTES, 'UTF-8') . " is already taken. Please try another username.";
        $ref = "ref";
        
        $_SESSION["msg_notsl"] = $qu; 
        $_SESSION["ref_notsl"] = $ref;
        
        echo "<script type='text/javascript'>window.open('/NewUser.php','_self');</script>"; 
        exit; 
    }

    
        
    else {
        
        $sql = "SELECT * FROM Notary_Details WHERE NotaryCode = :notcode LIMIT 1";
        $stmt = $pdo_notsl->prepare($sql);
        $stmt->execute(['notcode' => $lrnotno]);
        
        // Fix: Use PDO's fetch() method instead of the mysqli $result->num_rows
        if ($stmt->fetch()) { 
            $qu = "Notary Code " . urlencode($lrnotno) . " already has an account. Please use your correct Notary Code or contact support.";
            $ref = "ref";
            
            // Clean up URL parameter syntax (changed '&&' to '&')
            echo "<script type='text/javascript'>window.open('/NewUser.php?q=$qu&ref=$ref','_self');</script>"; 
            exit; 
        }
              
        else {
        
            $sql = "SELECT * FROM Notary_Details WHERE EmailAdd = :email LIMIT 1";
            $stmt = $pdo_notsl->prepare($sql);
            $stmt->execute(['email' => $email]);
            
            // Fix: Use PDO's fetch() method to see if the email exists
            if ($stmt->fetch()) { 
                $qu = "E-mail Address " . urlencode($email) . " is already registered. Please use a different E-mail Address.";
                $ref = "ref";
                
                // Fixed double ampersand '&&' to standard single '&' in URL parameters
                echo "<script type='text/javascript'>window.open('/NewUser.php?q=$qu&ref=$ref','_self');</script>"; 
                exit; 
            }
                 
             else {
                $sql = "SELECT * FROM Notary_Details WHERE NICNo = :notnic LIMIT 1";
                $stmt = $pdo_notsl->prepare($sql);
                $stmt->execute(['notnic' => $notnic]);
                
                // Fix: Use PDO's fetch() method to see if the email exists
                if ($stmt->fetch()) { 
                    $qu = "NIC/ Reg No " . urlencode($notnic) . " is already registered. Please use a different NIC.";
                    $ref = "ref";
                    
                    // Fixed double ampersand '&&' to standard single '&' in URL parameters
                    echo "<script type='text/javascript'>window.open('/NewUser.php?q=$qu&ref=$ref','_self');</script>"; 
                    exit; 
                }
    
                     
                 else {
                    
                    $sqlBlackList = "SELECT 1 FROM BlackList 
                    WHERE Black_List_Item IN (:notmob, :nottel, :email, :lrnotno, :notnic) 
                    LIMIT 1";
                    
                    $stmtBL = $pdo_notsl->prepare($sqlBlackList);
                    $stmtBL->execute([
                        'notmob'  => $notmob,
                        'nottel'  => $nottel,
                        'email'   => $email,
                        'lrnotno' => $lrnotno,
                        'notnic'  => $notnic
                    ]);
                    
                    // Fix: Read the PDO statement response cleanly instead of using ->num_rows
                    if ($stmtBL->fetch()) { 
                        
                        $notiflink = "https://t.spprd.lk/" . $noofid;
                        $notifmsg = "Notary - Black List Try To Create Login";
                        
                        if ($notmob  != '') { $notifmsg .= "\nMob -" . $notmob; }
                        if ($nottel  != '') { $notifmsg .= "\nTel -" . $nottel; }
                        if ($email   != '') { $notifmsg .= "\nEmail -" . $email; }
                        if ($lrnotno != '') { $notifmsg .= "\nLand Reg Code -" . $lrnotno; }
                        if ($notnic  != '') { $notifmsg .= "\nNIC -" . $notnic; }
                        
                        // Simplified the redundant strtotime(date()) execution
                        $nextime = date('Y-m-d H:i:s');    
                        
                        // Secure notification insert using PDO prepared statements
                        $sqlnotif = "INSERT INTO Notification (title, notif_msg, notif_time, notif_repeat, notif_loop, publish_date, username, Link, AddLoger, AddDate, AddTime) 
                                     VALUES (:title, :msg, :notif_time, '2', '1', :publish_date, 'Kapila', :link, :ip, :nowday, :nowtime)";
                        
                        $stmtNotif = $pdo_notsl->prepare($sqlnotif);
                        $stmtNotif->execute([
                            'title'        => $fno,
                            'msg'          => $notifmsg,
                            'notif_time'   => $nextime,
                            'publish_date' => $nextime,
                            'link'         => $notiflink,
                            'ip'           => $ipadd,
                            'nowday'       => $nowday,
                            'nowtime'      => $nowtime
                        ]);
                        
                        // Removed broken mysqli_close($conn_tracker) — PDO connection handles closing automatically at end of context lifecycle
                        
                        // Build Output elements
                        echo " Pl Send Whatsapp Your Flowing Details to ".$whatsappno." \n Land Reg Code- \n Full Name \n Name With Initial\n Address \n Tel No \n Mobile No \n E-mail address"; 
                        
                        $msg = urlencode("*") . "I Can't Create User Name in ".$provweb." My Details as Following, Land Reg Code : $lrnotno" . urlencode("*") . urlencode("\n") . ", Full Name : $notfnae" . urlencode("\n") . ", Name with Initials : $notsnae" . urlencode("\n") . ", Address : $notadd1e, $notadd2e, $notadd3e " . urlencode("\n") . ", Tel No : $nottel" . urlencode("\n") . ", Mobile No : $notmob" . urlencode("\n") . ", Email Address : $email";
                        
                        $msgAA = "I Can't Create User Name in ".$provweb." My Details as Following, </br> Land Reg Code : $lrnotno </br> Full Name : $notfnae </br> Name with Initials : $notsnae </br> Address : $notadd1e, $notadd2e, $notadd3e  </br>  Tel No : $nottel </br> Mobile No : $notmob </br> Email Address : $email";
                        
                        echo "<table>";
                        echo "<tr> <td> <h1> MSG : </h1></td> <td>  $msgAA </td> </tr>";
                        echo "<tr> <td> <h1> Send : </h1> </td> <td><a href='https://api.whatsapp.com/send/?phone=['.$whatsappno.']&text=$msg'>Send Whatsapp to '.$whatsappno.' </a> </td> </tr>";
                        echo "</table>";
                        
                        exit; // Halt execution after blocking unauthorized register attempts
                    }
                    else {
        
                        $hash = password_hash($psw, PASSWORD_DEFAULT);
                        $otpno = rand(100000, 999999);
                        $regdate = $nowday . ' ' . $nowtime;
                        
                        $pc = substr($lrnotno,0,1);
                        
                        try {
                            // PDO එකට වැරදි තිරය මත පෙන්වීමට (Exceptions) විධාන කිරීම
                            $pdo_notsl->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                        
                            $sqlnot = "INSERT INTO Notary_Details (
                                NotaryCode, PC, NotaryRegNoE, NotaryRegNoS, NotaryRegNoT, NICNo, NotaryName, Title, 
                                NotaryFullNameE, NotaryFullNameS, NotaryFullNameT, NotaryNameE, NotaryNameS, NotaryNameT, 
                                NotaryAdd1E, NotaryAdd1S, NotaryAdd1T, NotaryAdd2E, NotaryAdd2S, NotaryAdd2T, 
                                NotaryAdd3E, NotaryAdd3S, NotaryAdd3T, TelNo, FaxNo, MobNo, EmailAdd, LandReg, Active, 
                                UserName, AddLoger, AddDate, AddTime, Password, Block, RegisterDate, Activation, Pass, Per
                            ) VALUES (
                                :lrnotno, :pc, :notregnoe, :notregnos, :notregnot, :notnic, :notna, :nottitle, 
                                :notfnae, :notfnas, :notfnat, :notsnae, :notsnas, :notsnat, 
                                :notadd1e, :notadd1s, :notadd1t, :notadd2e, :notadd2s, :notadd2t, 
                                :notadd3e, :notadd3s, :notadd3t, :nottel, :faxno, :notmob, :email, :seccopy, 'A', 
                                :newuser, 'Online', :nowday, :nowtime, :hash, '0', :regdate, '1', :otpno, 'LAW'
                            )";
                        
                            $stmt = $pdo_notsl->prepare($sqlnot);
                        
                            $executeSuccess = $stmt->execute([
                                'lrnotno'     => $lrnotno,
                                'pc'     => $pc,
                                'notregnoe'   => $notregnoe,
                                'notregnos'   => $notregnos,
                                'notregnot'   => $notregnot,
                                'notnic'      => $notnic,
                                'notna'       => $notna,
                                'nottitle'    => $nottitle,
                                'notfnae'     => $notfnae,
                                'notfnas'     => $notfnas,
                                'notfnat'     => $notfnat,
                                'notsnae'     => $notsnae,
                                'notsnas'     => $notsnas,
                                'notsnat'     => $notsnat,
                                'notadd1e'    => $notadd1e,
                                'notadd1s'    => $notadd1s,
                                'notadd1t'    => $notadd1t,
                                'notadd2e'    => $notadd2e,
                                'notadd2s'    => $notadd2s,
                                'notadd2t'    => $notadd2t,
                                'notadd3e'    => $notadd3e,
                                'notadd3s'    => $notadd3s,
                                'notadd3t'    => $notadd3t,
                                'nottel'      => $nottel,
                                'faxno'      => '',
                                'notmob'      => $notmob,
                                'email'       => $email,
                                'seccopy'     => $seccopy,
                                'newuser'     => $newuser,
                                'nowday'      => $nowday,
                                'nowtime'     => $nowtime,
                                'hash'        => $hash,
                                'regdate'     => $regdate,
                                'otpno'       => $otpno
                            ]);
                        
                            if ($executeSuccess) {
                                //echo "Success!";
                            }
                        
                        } catch (PDOException $e) {
                            echo "<h1>Database Error!</h1>";
                            echo "Error: " . $e->getMessage();
                            exit;
                        }
                       
                        $msgid = $notmob."".$nowday."".$nowtime;
                        $msg = "Please don’t share this with anyone. use ".$otpno." as OTP in Create user in Revenue Dept. or click ".$provweb."/Login/User_Verify.php?q=$hash&&qu=$newuser ";
                                
                            // Send OTP Msg
                            // 1. Secure verification check using PDO
                        $sqlCheckMSG = "SELECT 1 FROM msg WHERE MSGID = :msgid LIMIT 1";
                        $stmtCheck = $pdo_notsl->prepare($sqlCheckMSG);
                        $stmtCheck->execute(['msgid' => $msgid]);
                        
                        // If the message has NOT been sent yet
                        if (!$stmtCheck->fetch()) { 
                            
                            // Explicit comparison check for non-empty or test placeholder value
                           if ($notmob != '' && $notmob != '0710000000') { 
                                
                                // Formulate SMS message content payload parameters safely
                                $msg = str_replace($notmob, "", $msg);
                                
                                // Define paths cleanly
                                include_once ROOT_PATH_NOT ."DBPath/ESMSWS.php";
                                                                
                                $session = createSession('', $smsusername, $smspassword, '');
                                $sendMessages = sendMessages($session, $smsmask, $msg, explode(",", $notmob), 0);
                                closeSession($session);
                                
                                // 2. Log OTP record inside 'msg' table cleanly using PDO
                                $sql_otp = "INSERT INTO msg (MSGID, FileNo, MSGTo, MSG, MSGGenLoger, MSGGenDate, MSGGenTime, Action) 
                                            VALUES (:msgid, :lrnotno, :notmob, :msg, :newuser, :nowday, :nowtime, 'S')";
                                
                                $stmtOtp = $pdo_notsl->prepare($sql_otp);
                                $stmtOtp->execute([
                                    'msgid'    => $msgid,
                                    'lrnotno'  => $lrnotno,
                                    'notmob'   => $notmob,
                                    'msg'      => $msg,
                                    'newuser'  => $newuser,
                                    'nowday'   => $nowday,
                                    'nowtime'  => $nowtime
                                ]);
                            }
                        }                            
                        // End OTP
                            
                        // Send Email
                        $clean_provweb = trim($provweb);
                                                            
                        $subject = "Welcome ".$newuser."! your action is required.";

                        $txt = "";
                        $provweb1 = "https://{$clean_provweb}/Icon/forgotpassword.jpg";
                        
  
                        $txt = "
                        <html>
                        <head>
                        <title>HTML email</title>
                        </head>
                        <body>
                        <img src='$provweb1' alt='Avatar' class='avatar'>
                        <p>Welcom to Southern Revenue Family</p>
                        
                        </body>
                        </html>
                        ";
                        $txt.="Please do not reply to this email as it will not be viewed.\n";
                        $txt.= "We’re super excited to have you as part of the Southern Revenue family! One last thing before you embark on your online journey: verify your email address, Change Your Password and Details.\n";
                        
                        $txt.= "https://{$clean_provweb}/Login/User_Verify.php?q=$otpno&&qu=$newuser";
                        
                        if ($provnoreply <> "") {
                            $headers = "MIME-Version: 1.0" . "\r\n";
                            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n"; 
                            
                            $headers .= "From: " . trim($provnoreply) . "\r\n";
                            $headers .= "Reply-To: " . trim($provnoreply) . "\r\n";
                            
                            if (mail($email, $subject, $txt, $headers)) {
                                 $qu= "New User Create Sucessfuly";
                                $ref="ref";
                           // echo "<script type='text/javascript'> window.open('https://notary.spprd.lk/Login/User_Verify.php?q=$hash&&qu=$newuser','_self');</script>";   
                                echo "<script type='text/javascript'> window.open('https://$provweb/index.php?ref=$ef&qu=$newuser', '_self'); </script>";  
                                exit;  
                            } else {
                                error_log("PHP mail() failed to send email to: " . $email);
                            }
                        }
                        else {
				if ($gmailusername <> '') {

					$mail = new PHPMailer(true);

					try {
    						// --- Server Settings ---
    						$mail->isSMTP();
    						$mail->Host       = 'smtp.gmail.com';
    						$mail->SMTPAuth   = true;
    						$mail->Username   = $gmailusername; // උදා: 'your-email@gmail.com'
    						$mail->Password   = $gmailpassword; // 👈 16-Digit App Password එක (හිස්තැන් නැතුව)
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
    						// --- Recipients & Content ---$gmailheadline
    						$mail->setFrom($gmailusername, 'System Admin' );
    						$mail->addAddress($email); 

    						$mail->isHTML(true);
    						$mail->Subject = $subject;
    						$mail->Body    = $txt;

    						$mail->send();
    
    						// echo "Email sent successfully!";
						$_SESSION['notuse_notsl'] = $newuser;
						$_SESSION['ref'] = "ref";

                                    		$qu= "New User Create Sucessfuly";
                                    		$ref="ref";
                                    		echo "<script type='text/javascript'> window.open('https://$provweb/index.php?ref=$ef&qu=$newuser', '_self'); </script>";  
                                    		exit;
					} catch (Exception $e) {
    						error_log("Mail Error: " . $mail->ErrorInfo);
    						echo "Mail Error: " . $mail->ErrorInfo;
					}
                               }                          
                        }
            
           
      
                    }
            // End Email
                    }
                 }
            }
        }
    }
 
?>



<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
    body {font-family: Arial, Helvetica, sans-serif; background-color: #f1f1f1;}
        .container {
            padding: 16px;
            width: 95%;
            max-width: 800px;
            margin: auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        input[type=text], select {
            width: 100%;
            padding: 12px 15px;
            margin: 4px 0;
            display: inline-block;
            border: 1px solid #ccc;
            box-sizing: border-box;
            border-radius: 20px; 
            outline: none;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .form-group {
    display: flex;
    flex-direction: row;
    margin-bottom: 15px;
    align-items: flex-start;
}

.label-cell {
    width: 25%; 
    font-weight: bold;
    padding-top: 8px;
}

.input-container {
    display: flex;
    flex-direction: column;
    gap: 10px; 
    width: 75%;
}

.input-cell {
    position: relative;
    flex: 1; 
}

input[type=text]:required:not(:placeholder-shown):invalid,
input[type=text]:required:invalid:focus-within {
    border: 2px solid #ff4d4d;
    background-color: #fff5f5;
}

select:required:not(:focus):invalid,
select:required:invalid:focus-within {
    border: 2px solid #ff4d4d;
    background-color: #fff5f5;
}

.error-msg {
    color: #ff4d4d;
    font-size: 13px;
    margin-top: 5px;
    margin-left: 10px;
    display: none; 
    font-weight: bold;
}

input[type=text]:required:not(:placeholder-shown):invalid + .error-msg {
    display: block;
}
select:required:not(:focus):invalid + .error-msg {
    display: block;
}

.error { color: red; }

@media screen and (max-width: 768px) {
    .form-group {
        flex-direction: column;
    }

    .label-cell {
        width: 100%; 
        margin-bottom: 5px;
    }

    .input-container {
        flex-direction: column; 
        width: 100%;
        gap: 8px;
    }
    
    .input-cell {
        width: 100%;
    }
}
        
        
        
button {
  background-color: #04AA6D;
  color: white;
  padding: 14px 20px;
  margin: 8px 0;
  border: none;
  cursor: pointer;
  width: 100%;
}

button:hover {
  opacity: 0.8;
}

.loginbtn {
  width: auto;
  padding: 10px 18px;
  background-color: #32CD32;
  margin: auto;
}
.cancelbtn {
  width: auto;
  padding: 10px 18px;
  background-color: #f44336;
}

.imgcontainer {
  text-align: center;
  margin: 24px 0 12px 0;
}

img.avatar {
  width: 30%;
  border-radius: 50%;
}

span.psw {
  float: right;
  padding-top: 16px;
}

/* Change styles for span and cancel button on extra small screens */
@media screen and (max-width: 400px) {
  span.psw {
     display: block;
     float: none;
  }
  .cancelbtn {
     width: 100%;
  }
}

#StafSi {background-image: linear-gradient(#ffffff,#99ffeb);}


.error {
    color: #FF0000;
    font-size: 16px;
}


/* The message box is shown when the user clicks on the password field */
#message {
  display:none;
  background: #f1f1f1;
  color: #000;
  position: relative;
  padding: 0px;
  margin-top: 0px;
}

#message p {
  padding: 0px 0px;
  font-size: 16px;
}

/* Add a green text color and a checkmark when the requirements are right */
.valid {
  color: green;
}

.valid:before {
  position: relative;
  left: -15px;
  content: "✔";
}

/* Add a red text color and an "x" when the requirements are wrong */
.invalid {
  color: red;
}

.invalid:before {
  position: relative;
  left: -15px;
  content: "✖";
}
</style>

<Script>
function showUserDetails(str) {
    document.getElementById("txtUser").innerHTML = "";
    if (str.length<1) {
        document.getElementById("txtUser").innerHTML = "";
        return;
     } else { 
        if (window.XMLHttpRequest) {
            xmlhttp = new XMLHttpRequest();
        } else {
            xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
         }
        xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                document.getElementById("txtUser").innerHTML = this.responseText;
             }
        };
        xmlhttp.open("GET","/Login/New_User_Search.php?q="+str,true);
        xmlhttp.send();
    }
}

function showMobNoDetails(str) {
    document.getElementById("txtMobNo").innerHTML = "";
    if (window.XMLHttpRequest) {
        xmlhttp = new XMLHttpRequest();
    } else {
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            document.getElementById("txtMobNo").innerHTML = this.responseText;
        }
    };
    xmlhttp.open("GET","/Login/New_User_MobNo_Search.php?q="+str,true);
    xmlhttp.send();
}


function showEmailDetails(str) {
    document.getElementById("txtEmail").innerHTML = "";
    if (window.XMLHttpRequest) {
        xmlhttp = new XMLHttpRequest();
    } else {
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            document.getElementById("txtEmail").innerHTML = this.responseText;
        }
    };
    xmlhttp.open("GET","/Login/New_User_Email_Search.php?q="+str,true);
    xmlhttp.send();
}


function showLandRegCode(str) {
    document.getElementById("LandRegCodeCheck").innerHTML = "";
    if (window.XMLHttpRequest) {
        xmlhttp = new XMLHttpRequest();
    } else {
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            document.getElementById("LandRegCodeCheck").innerHTML = this.responseText;
        }
    };
    xmlhttp.open("GET","/Login/New_User_LandRegCode_Search.php?q="+str,true);
    xmlhttp.send();
}


function showNotNIC(str) {
    document.getElementById("txtNotNIC").innerHTML = "";
    if (window.XMLHttpRequest) {
        xmlhttp = new XMLHttpRequest();
    } else {
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            document.getElementById("txtNotNIC").innerHTML = this.responseText;
        }
    };
    xmlhttp.open("GET","/Login/New_User_NotNIC_Search.php?q="+str,true);
    xmlhttp.send();
}


</Script>

</head>

<body>
<div class="container1" style="background-color:#f1f1f1">
    <form action="" method="post">
    <?php
    
        date_default_timezone_set("Asia/Colombo");
        $add_date="".date("Y/m/d")."" ;
        $add_time="".date("H:i:sa")."" ;
        
        echo "<div  id='StafSi' class ='container'>";
   
            echo"<label for='msgform'><b>$msg</b></label>";
            
            echo "<h2 style='text-align: center;'> $LineP[2] </h2>";

            echo "<div class='imgcontainer'>";
                echo "<img src='/Icon/forgotpassword.jpg' alt='Avatar' class='avatar'>";
            echo "</div>";
    ?>
    
     <!-- NIC No 
        <div class="form-group">
            <div class="label-cell"><?php echo $LineP[7]; ?> <span class='error'>*</span> :</div>
            
            <div class="input-container">
                <div class="input-cell">
                    <input type="text" name="notnic" id="notnic" onKeyup="showNotNIC(this.value)" autocomplete="off" placeholder=" " required>
                    <span class="error-msg">⚠️ මෙම ක්ෂේත්‍රය අනිවාර්ය වේ (Required)</span>
                </div>
                
            </div>
        </div> -->
    
        <!-- Land Reg No -->
        <div class="form-group">
        <div class="label-cell"><?php echo $LineP[3]; ?> <span class='error'>*</span> :</div>
        
        <div class="input-container">
            <div class="input-cell">
                <input type="text" name="lrnotno" id="lrnotno"  onKeyup="showLandRegCode(this.value)" placeholder="<?php echo $LineP[3] ?>" autocomplete="off"  required>
                <span class="error-msg">⚠️ මෙම ක්ෂේත්‍රය අනිවාර්ය වේ (Required)</span>
            </div>
                <div id="LandRegCodeCheck" style="margin-top: 1px; margin-left: 1px;"></div>
        </div>
        </div>
        
        <div class="form-group">
        <div class="label-cell"> <?php echo $LineP[4] ?> :</div>
        <div class="input-cell">
            <input type="text" name="notregnoe" placeholder= "<?php echo $LineE[4] ?> ">
        </div>
        <div class="input-cell">
            <input type="text" name="notregnos" placeholder= "<?php echo $LineS[4] ?> ">
        </div>
        <div class="input-cell">
            <input type="text" name="notregnot" placeholder= "<?php echo $LineT[4] ?> ">
        </div>
        </div>
        
        <div class="form-group">
        <div class="label-cell"> <?php echo $LineP[5] ?> <span class='error'>*</span>  :</div>
        <div class="input-container">
            <div class="input-cell">
                <select name="seccopy" id="seccopy" required>
                <?php 
                echo "<option value='".$LinkA[6]."'>-- " . $LineP[6] . " --</option>";
                $sqldt = "SELECT LandReg_No, LandRegName, LandRegE, LandRegS, LandRegT FROM Land_Reg Where NonLandReg <>'Y' Order by LandRegName";
                $resultdt = $pdo_notsl->query($sqldt);
                while($rowdt = $resultdt->fetch(PDO::FETCH_ASSOC)) {
                    echo "<option value='".htmlspecialchars($rowdt['LandReg_No'], ENT_QUOTES, 'UTF-8')."'>".htmlspecialchars($rowdt['LandRegName'], ENT_QUOTES, 'UTF-8')."</option>";
                } 
                ?>
                </select>
                <span class="error-msg">⚠️ මෙම ක්ෂේත්‍රය අනිවාර්ය වේ (Required)</span>
            </div>
        </div>
        </div>
        
        <!-- NIC No -->
        <div class="form-group">
            <div class="label-cell"><?php echo $LineP[7]; ?> <span class='error'>*</span> :</div>
            
            <div class="input-container">
                <div class="input-cell">
                    <input type="text" name="notnic" id="notnic" onKeyup="showNotNIC(this.value)" autocomplete="off" placeholder=" " required>
                    <span class="error-msg">⚠️ මෙම ක්ෂේත්‍රය අනිවාර්ය වේ (Required)</span>
                </div>
                
                <div id="txtNotNIC" style="margin-top: 1px; margin-left: 1px;"></div>
            </div>
        </div>
        
        <!-- Title -->
        <div class="form-group">
        <div class="label-cell"> <?php echo $LineP[8] ?> <span class='error'>*</span>  :</div>
        <div class="input-container">
            <div class="input-cell">
                <select name="nottitle" id="nottitle" required>
                <?php 
                echo "<option value='".$LinkA[6]."'>-- " . $LineP[6] . " --</option>";
                $sqldt = "SELECT TitleID, Title, TitleE, TitleS, TitleT FROM Title Where TitleE <>'' Order by TitleE";
                $resultdt = $pdo_notsl->query($sqldt);
                if ($resultdt) {
                    while($rowdt = $resultdt->fetch(PDO::FETCH_ASSOC)) {
                        $lrno = $rowdt['TitleID'];
                        $lrna = $rowdt['Title'];
                        
                        if ($lrna == ''){
                            $lrna = $rowdt['TitleE'] . " " . $rowdt['TitleS'] . " " . $rowdt['TitleT'];
                        }
                        
                        echo "<option value='".htmlspecialchars($lrno, ENT_QUOTES, 'UTF-8')."'>".htmlspecialchars($lrna, ENT_QUOTES, 'UTF-8')."</option>";
                    } 
                }
                ?>
                </select>
                <span class="error-msg">⚠️ මෙම ක්ෂේත්‍රය අනිවාර්ය වේ (Required)</span>
            </div>
        </div>
        </div>
        
        <!-- Notary Name English-->
        <div class="form-group">
            <div class="label-cell"><?php echo $LineE[9]; ?> <span class='error'>*</span> :</div>
            
            <div class="input-container">
                <div class="input-cell">
                    <input type="text" name="notfnae" id="notfnae" oninput="this.value = this.value.replace(/[^A-Za-z ]/g, '');" 
                   pattern="[A-Za-z ]+" placeholder="Only English Letters" required>
                    <span class="error-msg">⚠️ මෙම ක්ෂේත්‍රය අනිවාර්ය වේ (Required)</span>
                </div>
            </div>
        </div>
        
        <!-- Notary Name Sinhala-->
        <div class="form-group">
            <div class="label-cell"><?php echo $LineS[9]; ?> <span class='error'>*</span> :</div>
            
            <div class="input-container">
                <div class="input-cell">
                    <input type="text" name="notfnas" id="notfnas" oninput="this.value = this.value.replace(/[^ \u0D80-\u0DFF]/g, '');" 
                   pattern="[\u0D80-\u0DFF ]+" placeholder="Only SinhalaLetters" required>
                    <span class="error-msg">⚠️ මෙම ක්ෂේත්‍රය අනිවාර්ය වේ (Required)</span>
                </div>
            </div>
        </div>
        
        <!-- Notary Name Tamil-->
        <div class="form-group">
            <div class="label-cell"><?php echo $LineT[9]; ?>  :</div>
            
            <div class="input-container">
                <div class="input-cell">
                    <input type="text" name="notfnat" id="notfnat" oninput="this.value = this.value.replace(/[^ \u0B80-\u0BFF]/g, '');" 
                   pattern="[\u0B80-\u0BFF ]+" placeholder="Only Tamil Letters" >
                </div>
            </div>
        </div>
    
        <!-- Notary Name With Initials English-->
        <div class="form-group">
            <div class="label-cell"><?php echo $LineE[10]; ?> <span class='error'>*</span> :</div>
            
            <div class="input-container">
                <div class="input-cell">
                    <input type="text" name="notsnae" id="notsnae" oninput="this.value = this.value.replace(/[^A-Za-z. ]/g, '');" 
                    pattern="[A-Za-z. ]+" placeholder="Only English Letters" required>
                    <span class="error-msg">⚠️ මෙම ක්ෂේත්‍රය අනිවාර්ය වේ (Required)</span>
                </div>
            </div>
        </div>
        
        <!-- Notary Name With Initials Sinhala-->
        <div class="form-group">
            <div class="label-cell"><?php echo $LineS[10]; ?> <span class='error'>*</span> :</div>
            
            <div class="input-container">
                <div class="input-cell">
                    <input type="text" name="notsnas" id="notsnas" oninput="this.value = this.value.replace(/[^ \u0D80-\u0DFF.]/g, '');" 
                    pattern="[\u0D80-\u0DFF. ]+" placeholder="Only Sinhala Letters and dot(.)" required>
                    <span class="error-msg">⚠️ මෙම ක්ෂේත්‍රය අනිවාර්ය වේ (Required)</span>
                </div>
            </div>
        </div>
        
        <!-- Notary Name With Initials Tamil-->
        <div class="form-group">
            <div class="label-cell"><?php echo $LineT[10]; ?> :</div>
            
            <div class="input-container">
                <div class="input-cell">
                    <input type="text" name="notsnat" id="notsnat" oninput="this.value = this.value.replace(/[^ \u0B80-\u0BFF.]/g, '');" 
                    pattern="[^ \u0B80-\u0BFF. ]+" placeholder="Only Tamil Letters and dot(.)" >
                </div>
            </div>
        </div>
        
        <!-- Notary Address English-->
        <div class="form-group">
            <div class="label-cell"><?php echo $LineE[11]; ?> <span class='error'>*</span> :</div>
            
            <div class="input-container">
                <div class="input-cell">
                    <input type="text" name="notadd1e" id="notadd1e" oninput="this.value = this.value.replace(/[^A-Za-z0-9.\/, ]/g, '');" 
                    pattern="[A-Za-z0-9./, ]+" placeholder="Only English Letters" required>
                    <span class="error-msg">⚠️ මෙම ක්ෂේත්‍රය අනිවාර්ය වේ (ඉංග්‍රීසි අකුරු, ඉලක්කම් සහ ./, පමණක් ඇතුළත් කරන්න)</span>
                </div>
            </div>
        </div>
        <div class="form-group">
            <div class="label-cell"></div>
            
            <div class="input-container">
                <div class="input-cell">
                    <input type="text" name="notadd2e" id="notadd2e" oninput="this.value = this.value.replace(/[^A-Za-z0-9.\/, ]/g, '');" 
                    pattern="[A-Za-z0-9./, ]+" placeholder="Only English Letters" >
                </div>
            </div>
        </div>
        <div class="form-group">
            <div class="label-cell"></div>
            
            <div class="input-container">
                <div class="input-cell">
                    <input type="text" name="notadd3e" id="notadd3e" oninput="this.value = this.value.replace(/[^A-Za-z0-9.\/, ]/g, '');" 
                    pattern="[A-Za-z0-9./, ]+" placeholder="Only English Letters" >
                </div>
            </div>
        </div>
        
        <!-- Notary Address Sinhala-->
        <div class="form-group">
            <div class="label-cell"><?php echo $LineS[11]; ?> <span class='error'>*</span> :</div>
            
            <div class="input-container">
                <div class="input-cell">
                    <input type="text" name="notadd1s" id="notadd1s" oninput="this.value = this.value.replace(/[^A-Za-z0-9 \u0D80-\u0DFF.\/,]/g, '');" 
                   pattern="[A-Za-z0-9\u0D80-\u0DFF./, ]+" placeholder="Only Sinhala Letters and dot(.)" required>
                    <span class="error-msg">⚠️ මෙම ක්ෂේත්‍රය අනිවාර්ය වේ (Required)</span>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <div class="label-cell"></div>
            
            <div class="input-container">
                <div class="input-cell">
                    <input type="text" name="notadd2s" id="notadd2s" oninput="this.value = this.value.replace(/[^A-Za-z0-9 \u0D80-\u0DFF.\/,]/g, '');" 
                   pattern="[A-Za-z0-9\u0D80-\u0DFF./, ]+" placeholder="Only Sinhala Letters and dot(.)" >
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <div class="label-cell"></div>
            
            <div class="input-container">
                <div class="input-cell">
                    <input type="text" name="notadd3s" id="notadd3s" oninput="this.value = this.value.replace(/[^A-Za-z0-9 \u0D80-\u0DFF.\/,]/g, '');" 
                   pattern="[A-Za-z0-9\u0D80-\u0DFF./, ]+" placeholder="Only Sinhala Letters and dot(.)" >
                </div>
            </div>
        </div>
        
        <!-- Notary Address Tamil-->
        <div class="form-group">
            <div class="label-cell"><?php echo $LineT[11]; ?> :</div>
            
            <div class="input-container">
                <div class="input-cell">
                    <input type="text" name="notadd1t" id="notadd1t" oninput="this.value = this.value.replace(/[^A-Za-z0-9 \u0B80-\u0BFF.\/,]/g, '');" 
                    pattern="[A-Za-z0-9\u0B80-\u0BFF./, ]+" placeholder="Only Tamil Letters and dot(.)" >
                </div>
            </div>
        </div>
        <div class="form-group">
            <div class="label-cell"></div>
            
            <div class="input-container">
                <div class="input-cell">
                    <input type="text" name="notadd2t" id="notadd2t" oninput="this.value = this.value.replace(/[^A-Za-z0-9 \u0B80-\u0BFF.\/,]/g, '');" 
                    pattern="[A-Za-z0-9\u0B80-\u0BFF./, ]+" placeholder="Only Tamil Letters and dot(.)" >
                </div>
            </div>
        </div>
        <div class="form-group">
            <div class="label-cell"></div>
            
            <div class="input-container">
                <div class="input-cell">
                    <input type="text" name="notadd3t" id="notadd3t" oninput="this.value = this.value.replace(/[^A-Za-z0-9 \u0B80-\u0BFF.\/,]/g, '');" 
                    pattern="[A-Za-z0-9\u0B80-\u0BFF./, ]+" placeholder="Only Tamil Letters and dot(.)" >
                </div>
            </div>
        </div>
        
        <!-- Fixed Tel No -->
        <div class="form-group">
            <div class="label-cell"><?php echo $LineP[12]; ?> :</div>
            
            <div class="input-container">
                <div class="input-cell">
                    <input type="text" name="nottel" id="nottel" oninput="this.value = this.value.replace(/[^0-9]/g, '');" 
                    pattern="[0-9]{10}" maxlength="10" autocomplete="on" placeholder="0912223484" >
                </div>
            </div>
        </div>
        <!-- Mobile Tel No -->
        <div class="form-group">
            <div class="label-cell"><?php echo $LineP[13]; ?> <span class='error'>*</span> :</div>
            
            <div class="input-container">
                <div class="input-cell">
                    <input type="text" name="notmob" id="notmob" oninput="this.value = this.value.replace(/[^0-9]/g, '');" 
                    pattern="[0-9]{10}" maxlength="10" autocomplete="on" placeholder="0719207422" onKeyup="showMobNoDetails(this.value)" required>
                    <span class="error-msg">⚠️ මෙම ක්ෂේත්‍රය අනිවාර්ය වේ (Required)</span>
                </div>
                
                <div id="txtMobNo" style="margin-top: 1px; margin-left: 1px;"></div>
            </div>
        </div>
        
        <!--  EMAIL ADDRESS-->
        <div class="form-group">
            <div class="label-cell"><?php echo $LineP[14]; ?> <span class='error'>*</span> :</div>
            
            <div class="input-container">
                <div class="input-cell">
                    <input type="text" name="notemail" id="notemail" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}"
                    maxlength="100" autocomplete="on" placeholder="prdcom@gmail.com" onKeyup="showEmailDetails(this.value)" required>
                    <span class="error-msg">⚠️ මෙම ක්ෂේත්‍රය අනිවාර්ය වේ (Required)</span>
                </div>
            <div id="txtEmail" style="margin-top: 1px; margin-left: 1px;"></div>
            </div>
        </div>
        
        <!-- username-->
        <div class="form-group">
            <div class="label-cell"><?php echo $LineP[15]; ?> <span class='error'>*</span> :</div>
            
            <div class="input-container">
                <div class="input-cell">
                    <input type="text" name="uname" id="uname" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}"
                    maxlength="50" autocomplete="on" placeholder="prdcom@gmail.com" onKeyup="showUserDetails(this.value)" required>
                    <span class="error-msg">⚠️ මෙම ක්ෂේත්‍රය අනිවාර්ය වේ (Required)</span>
                </div>
                
                <div id="txtUser" style="margin-top: 1px; margin-left: 1px;"></div>
            </div>
        </div>
        
    <?php
            echo "<div id='LandRegCodeCheck'>";
            echo "<h4 font color = 'Green'>Click 'Create New User' Button then Check Your Phone, Enter OTP and Verify (or  Check Email and Verify)</h4>";
            echo "<input type='submit' name='send' value='Create New User' class='loginbtn'>";
            echo "</div>";

    ?>
        
            
                
            
            
        </div>
  
    </form>
</div>
			
<Script>
var myInput = document.getElementById("psw");
var myInputA = document.getElementById("pswa");
var letter = document.getElementById("letter");
var capital = document.getElementById("capital");
var number = document.getElementById("number");
var length = document.getElementById("length");
var passsame = document.getElementById("passsame");

// When the user clicks on the password field, show the message box
myInput.onfocus = function() {
  document.getElementById("message").style.display = "block";
}
// When the user clicks outside of the password field, hide the message box
myInput.onblur = function() {
  document.getElementById("message").style.display = "none";
}

// When the user starts to type something inside the password field
myInput.onkeyup = function() {
  // Validate lowercase letters
  var lowerCaseLetters = /[a-z]/g;
  if(myInput.value.match(lowerCaseLetters)) {  
    letter.classList.remove("invalid");
    letter.classList.add("valid");
  } else {
    letter.classList.remove("valid");
    letter.classList.add("invalid");
  }
  
  // Validate capital letters
  var upperCaseLetters = /[A-Z]/g;
  if(myInput.value.match(upperCaseLetters)) {  
    capital.classList.remove("invalid");
    capital.classList.add("valid");
  } else {
    capital.classList.remove("valid");
    capital.classList.add("invalid");
  }

  // Validate numbers
  var numbers = /[0-9]/g;
  if(myInput.value.match(numbers)) {  
    number.classList.remove("invalid");
    number.classList.add("valid");
  } else {
    number.classList.remove("valid");
    number.classList.add("invalid");
  }
  
  // Validate length
  if(myInput.value.length >= 8) {
    length.classList.remove("invalid");
    length.classList.add("valid");
  } else {
    length.classList.remove("valid");
    length.classList.add("invalid");
  }
}

// When the user starts to type something inside the password field
myInputA.onkeyup = function() {
  // Validate length
  if(myInput.value == myInputA.value) {
    pswa.classList.remove("invalid");
    pswa.classList.add("valid");
  } else {
    pswa.classList.remove("valid");
    pswa.classList.add("invalid");
  }
  
}

</script>
</body>
</html>