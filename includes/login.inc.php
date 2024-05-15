<?php
session_start();

// Check if form is submitted
if (isset($_POST['mailuid']) && isset($_POST['pwd'])) {
    if (!(empty($_POST['mailuid']) || trim($_POST['mailuid']) == '') && !(empty($_POST['pwd']) || trim($_POST['pwd']) == '')) {
        try {
            $db = new PDO('mysql:host=localhost;dbname=loginsystemtut;charset=utf8', 'root', '', array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
        } catch (Exception $err) {
            die('Connection Failed: ' . $err->getMessage());
        }

        $req = $db->prepare('SELECT * FROM users WHERE uidUsers=:u OR emailUsers=:e');
        $req->execute(array('u' => $_POST['mailuid'], 'e' => $_POST['mailuid']));

        if ($enreg = $req->fetch()) {
            // Check if the account is blocked
            if ($enreg['status'] === 'blocked') {
                header("Location: ../index.php?error=blocked");
                exit();
            }

            $pwdCheck = password_verify($_POST['pwd'], $enreg['pwdUsers']);
            if ($pwdCheck == false) {
                // Increase login attempt count
                $loginAttempts = $enreg['login_attempts'] + 1;
                // Check if login attempts exceed limit
                if ($loginAttempts >= 3) {
                    // Block the account
                    $sql = "UPDATE users SET status = 'blocked' WHERE idUsers = :id";
                    $stmt = $db->prepare($sql);
                    $stmt->execute(array(':id' => $enreg['idUsers']));
                    header("Location: ../index.php?error=blocked");
                    exit();
                } else {
                    // Update login attempts in the database
                    $sql = "UPDATE users SET login_attempts = :login_attempts WHERE idUsers = :id";
                    $stmt = $db->prepare($sql);
                    $stmt->execute(array(':login_attempts' => $loginAttempts, ':id' => $enreg['idUsers']));
                    header("Location: ../index.php?error=wrongpwd&mail=" . $_POST['mailuid']);
                    exit();
                }
            } elseif ($pwdCheck == true) {
                // Reset login attempt count
                $sql = "UPDATE users SET login_attempts = 0 WHERE idUsers = :id";
                $stmt = $db->prepare($sql);
                $stmt->execute(array(':id' => $enreg['idUsers']));
                $_SESSION['userId'] = $enreg['idUsers'];
                $_SESSION['userUid'] = $enreg['uidUsers'];
                $_SESSION['userEmail'] = $enreg['emailUsers'];

                // Send email
                $to = "yashtyagis2003@gmail.com";
                $subject = "Login Attempt Successful";
                $msg = "You have successfully logged in!";
                
                // Store email details in session variables
                $_SESSION['emailDetails'] = array(
                    'to' => $to,
                    'subject' => $subject,
                    'msg' => $msg
                );

                header("Location: ../loggedin.php?login=success");
                exit();
            }
        } else {
            header("Location: ../index.php?error=nomatch");
            exit();
        }
    } else {
        if (!(empty($_POST['mailuid']) || trim($_POST['mailuid']) == '')) {
            header("Location: ../index.php?error=emptyfields&mail=" . $_POST['mailuid']);
        } else {
            header("Location: ../index.php?error=emptyfields");
        }
        exit();
    }
}
?>
