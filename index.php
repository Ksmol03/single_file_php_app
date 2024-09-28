<?php

session_start();

//Connect to database
$dbhost = 'localhost';
$dbname = 'single_file_php_app';
$dbusername = 'root';
$dbpassword = '';

$conn = new mysqli($dbhost, $dbusername, $dbpassword, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

//Base path of the project
$basePath = "/second";

//Get request route
$request = trim($_SERVER["REQUEST_URI"], "/");
$request = strtok($request, "?");
$request = substr($request, strlen($basePath));
$segments = explode("/", $request);

//Authenticate user
if (!isset($_SESSION["user_id"]) && !($segments[0] === "login" && empty($segments[1]))) {
    header("Location: " . $basePath . "/login");
}

//Login page
if ($segments[0] === "login") {

    //Handle log in form
    $error = "";
    if (isset($_POST["log-in-form"])) {

        $username = $_POST["username"];
        $password = $_POST["password"];

        //Check if username is empty
        if (!empty($username)) {

            //Check if password is empty
            if (!empty($password)) {

                //Check if username exists
                $sql = "SELECT id, password FROM users WHERE username = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $stmt->store_result();
                if ($stmt->num_rows > 0) {

                    //Check if password is valid
                    $stmt->bind_result($userID, $hashedPassword);
                    $stmt->fetch();
                    if (password_verify($password, $hashedPassword)) {

                        //Log in user
                        $_SESSION["user_id"] = $userID;
                        header("Location: " . $basePath . "/panel");
                        exit();
                    } else {
                        $error = "Invalid password";
                    }
                } else {
                    $error = "Invalid username";
                }
            } else {
                $error = "Password is required";
            }
        } else {
            $error = "Username is required";
        }
    }


?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login</title>
        <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    </head>

    <body>
        <p>Log In</p>
        <form action="" method="POST">
            <input type="text" name="username" placeholder="Enter username" value="<?= isset($username) ? $username : "" ?>">
            <input type="password" name="password" placeholder="Enter password" value="<?= isset($password) ? $password : "" ?>">
            <input type="submit" name="log-in-form" value="Log In">
        </form>
        <p class="text-red-500">
            <?= $error ?>
        </p>
    </body>

    </html>
<?php
    exit();

    //Panel page
} elseif ($segments[0] === "panel" && empty($segments[1])) {

    //Handle log out form
    $error = "";
    if (isset($_POST["log-out-form"])) {
        session_destroy();
        header("Location: " . $GLOBALS["basePath"] . "/login");
        exit();
    } ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Panel</title>
        <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    </head>

    <body>
        Panel
        <form action="" method="POST">
            <input type="submit" name="log-out-form" value="Log Out">
        </form>
    </body>

    </html>
<?php
}
?>