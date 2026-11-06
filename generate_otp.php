<?php
ob_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';

include_once ROOT_PATH_NOT . 'DBPath/Conn_notspprd.php'; 
// Session Handler
if (session_status() === PHP_SESSION_NONE) {
    require_once ROOT_PATH_NOT . 'DBPath/SessionHandler.php';
 
    
    $handler = new DatabaseSessionHandler($pdo_notsl);
    session_set_save_handler($handler, true);
    
    session_start();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mobile'])) {
    $mobile = trim($_POST['mobile']);
    
    if (empty($mobile)) {
        while (ob_get_level()) { ob_end_clean(); }
        echo json_encode(['status' => 'error', 'message' => 'දුරකථන අංකය හිස් විය නොහැක.']);
        exit;
    }

    // Database Connection
   // include_once ROOT_PATH_NOT . "DBPath/Conn_notspprd.php";
      
    $sql = "SELECT ID, PC, UserName, LastLogIP, LastLogDate, Per FROM Notary_Details WHERE MobNo = :mobile AND Active = 'A'";
    
    if ($stmt = $pdo_notsl->prepare($sql)) {
        $stmt->bindParam(":mobile", $mobile, PDO::PARAM_STR);
        
        if ($stmt->execute()) {
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        
                $userid = $row["ID"];
                $pc = $row["PC"];
                $username_logged = $row["UserName"];
                $last_ip = $row["LastLogIP"];
                $last_date = $row["LastLogDate"];
                $per = $row["Per"];
                $level = '';
                
                // IP Address එක ලබා ගැනීම
                $client_ip = $_SERVER['REMOTE_ADDR'];
                if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                    $client_ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
                }


                // 1. IP එක සමාන නම් කෙළින්ම Auto-Login (Direct Redirect)
                if ($last_ip === $client_ip && !empty($last_ip)) {
                    
                    if ($per === 'LAW') {
                        $_SESSION["loggedin_notsl"] = true;
                        $_SESSION["notuseid_notsl"] = $userid;
                        $_SESSION["notuse_pc"] = $pc; 
                        $_SESSION["notuse_notsl"] = $username_logged; 
                        $_SESSION['notper_notsl'] = $per;
                        $_SESSION['per_notsl'] = $per;  
                        $_SESSION['ipadd'] = $last_ip;

                        while (ob_get_level()) { ob_end_clean(); }
                        echo json_encode([
                            'status' => 'success', 
                            'redirect' => true, 
                            'url' => '/Login/home_select_pc.php'
                        ]);
                        exit;
                    }
                    
                    if ($per === 'LAND') {
                        $_SESSION["loggedin_notsl"] = true;
                        $logofftime = 180;
                        $_SESSION['logofftime'] = $logofftime;
                        $_SESSION['last_timestamp'] = time();
                        $_SESSION["landuseid"] = $userid;
                        $_SESSION["landuse"] = $username_logged;   
                        $_SESSION['landlevel'] = $level;
                        $_SESSION['per'] = $per;

                        while (ob_get_level()) { ob_end_clean(); }
                        echo json_encode([
                            'status' => 'success', 
                            'redirect' => true, 
                            'url' => 'https://landreg.spprd.lk/Online/index.php'
                        ]);
                        exit;
                    }
                }
                
                // **සූක්ෂ්ම වෙනස:** IP එක වෙනස් නම් මෙතැනින් පහළට කේතය දිගටම ක්‍රියාත්මක වී OTP එක Generate වේ.
                
            } else {
                // පරිශීලකයා හමු නොවූයේ නම්
                while (ob_get_level()) { ob_end_clean(); }
                echo json_encode([
                    'status' => 'error', 
                    'message' => 'ඇතුළත් කළ දුරකථන අංකය පද්ධතියේ ලියාපදිංචි කර නැත, අත්හිටුවා ඇත හෝ අක්‍රිය ගිණුමකි.'
                ]);
                exit;
            }
        
        } else {
            while (ob_get_level()) { ob_end_clean(); }
            echo json_encode(['status' => 'error', 'message' => 'දත්ත සමුදායේ විමසුම් දෝෂයක් (Query Execution Failed).']);
            exit;
        }
    } else {
        while (ob_get_level()) { ob_end_clean(); }
        echo json_encode(['status' => 'error', 'message' => 'දත්ත සමුදා සම්බන්ධතා දෝෂයක්.']);
        exit;
    }


    // 2. IP එක වෙනස් අවස්ථාවලදී OTP Generate කර SMS යැවීම
    $otp = rand(100000, 999999);
    
    $_SESSION['generated_otp'] = $otp;
    $_SESSION['otp_mobile']    = $mobile;
    $_SESSION['otp_expiry']    = time() + (10 * 60); // විනාඩි 10යි
    $_SESSION['otp_attempts']  = 0;

    $msg = "Your Verification OTP Code is: " . $otp;
   
    // ESMS Gateway හරහා SMS යැවීම
    include_once ROOT_PATH_NOT . "DBPath/ESMSWS.php";
            
    if (function_exists('createSession')) {
        $session = createSession('', $smsusername, $smspassword, '');
        $sendMessages = sendMessages($session, $smsmask, $msg, explode(",", $mobile), 0);
        closeSession($session);
    }
     

    while (ob_get_level()) { ob_end_clean(); }
    echo json_encode([
        'status' => 'success', 
        'redirect' => false, 
        'message' => 'OTP කේතය ඔබගේ දුරකථනයට සාර්ථකව යොමු කරන ලදී.'
    ]);
    exit;

} else {
    while (ob_get_level()) { ob_end_clean(); }
    echo json_encode(['status' => 'error', 'message' => 'අවලංගු ඉල්ලීමකි (Invalid Request).']);
    exit;
}
?>