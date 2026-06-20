<?php
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        // 'secure' => true,  // enable once always served over HTTPS
    ]);
    session_start();

    if (!empty($_SESSION['is_logged'])) {
        header("Location: index.php");
        exit;
    }

    $error_msg = '';
    if (isset($_POST['login'])) {
        // Keep the redirect target a local script name (prevents open redirect).
        $redirect = 'index';
        if (isset($_GET['redirect'])) {
            $redirect = preg_replace('/[^A-Za-z0-9_]/', '', $_GET['redirect']);
        }
        if ($redirect === '') {
            $redirect = 'index';
        }

        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $password = isset($_POST['password']) ? (string) $_POST['password'] : '';

        include_once("db_connect.php");
        $db = new db();

        // Parameterized query — user input is bound, never interpolated (S1).
        $user_rs = $db->select(
            "select firstname, lastname, is_super, password from auth_users where is_active = '1' and username = ?",
            "s",
            [$username]
        );

        $authenticated = false;
        $user_rec = null;
        if ($user_rs && mysqli_num_rows($user_rs)) {
            $user_rec = mysqli_fetch_object($user_rs);
            $stored = (string) $user_rec->password;

            if (preg_match('/^[0-9a-f]{40}$/i', $stored)) {
                // Legacy unsalted SHA1: verify, then transparently upgrade to bcrypt (S3).
                if (hash_equals(strtolower($stored), sha1($password))) {
                    $authenticated = true;
                    $new_hash = password_hash($password, PASSWORD_DEFAULT);
                    $db->execute(
                        "update auth_users set password = ? where username = ?",
                        "ss",
                        [$new_hash, $username]
                    );
                }
            } else {
                // Modern bcrypt/argon2 hash.
                $authenticated = password_verify($password, $stored);
            }
        }

        if ($authenticated && $user_rec) {
            session_regenerate_id(true); // prevent session fixation

            $_SESSION['firstname'] = $user_rec->firstname;
            $_SESSION['lastname']  = $user_rec->lastname;
            $_SESSION['is_super']  = $user_rec->is_super;
            $_SESSION['is_logged'] = true;

            header("Location: $redirect.php");
            exit;
        } else {
            $error_msg = "Invalid Username or Password.";
        }
    }
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Log In | Personal Accounting Management</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<link type="text/css" rel="stylesheet" href="./media/css/main.css" />
<script type="text/javascript" src="./media/js/jquery.min.js"></script>
<script type="text/javascript">
$(document).ready(function() {
    $('#username').focus();
});
</script>
</head>
<body>
<div id="header">
<div id="header-top"></div>
<div id="login">
<form action="" method="POST">
    <table align="center">
		<tr>
			<th colspan="2" align="center">
				<img class="logo" border="0" alt="PERSONAL ACCOUNTING MANAGEMENT" src="./media/images/logo.png" />
			</th>
        <tr height="25px">
            <th colspan="2" align="left">Login</th>
        </tr>
        <tr height="25px">
            <th align="left">Username:</th>
            <td align="left"><input type="text" name="username" id="username" value="" /></td>
        </tr>
        <tr height="25px">
            <th align="left">Password:</th>
            <td align="left"><input type="password" name="password" id="password" value="" /></td>
        </tr>
        <tr height="25px">
            <th colspan="2" align="center"><input type="submit" name="login" value="Login" /></th>
        </tr>
        <tr height="25px">
            <th colspan="2" align="center">
            <?php
                if ($error_msg) {
                    echo '<lable style="color:#FF0000">'.$error_msg.'</lable>';
                }
            ?>
            </th>
        </tr>
    </table>
</form>
</div> <!-- END of div login -->
<?php include("footer.php")?>
