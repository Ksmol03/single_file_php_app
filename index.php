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
    exit();
}

//Redirect / to /panel
if (empty($segments[0])) {
    header("Location: " . $basePath . "/panel");
    exit();
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
                $sql = "SELECT user_id, password FROM users WHERE username = ?";
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

    //Get items form database
    $sql = "SELECT i.item_id, i.item_name, i.serial_number, d.department_name FROM items i JOIN departments d USING (department_id) ORDER BY i.item_id";
    $stmt = $conn->query($sql);
    $items = $stmt;

    //Handle log out form
    $error = "";
    if (isset($_POST["log-out-form"])) {
        session_destroy();
        header("Location: " . $GLOBALS["basePath"] . "/login");
        exit();
    }
?>
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
        <table>
            <thead>
                <tr>
                    <th>Item ID</th>
                    <th>Item Name</th>
                    <th>Serial Number</th>
                    <th>Department</th>
                </tr>
            </thead>
            <tbody>
                <?php
                while ($item = $items->fetch_assoc()) {
                ?>
                    <tr>
                        <td><?= $item["item_id"] ?></td>
                        <td><?= $item["item_name"] ?></td>
                        <td><?= $item["serial_number"] ?></td>
                        <td><?= $item["department_name"] ?></td>
                        <td><a href="<?= $basePath ?>/panel/edit-item/<?= $item["item_id"] ?>">Edit</a></td>
                    </tr>
                <?php
                }
                ?>
            </tbody>
        </table>
    </body>

    </html>
<?php
    exit();

    //Edit item page
} elseif ($segments[0] === "panel" && $segments[1] === "edit-item" && isset($segments[2]) && is_numeric($segments[2]) && empty($segments[3])) {

    //Get item data
    $itemID = $segments[2];
    $sql = "SELECT * FROM items WHERE item_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $itemID);
    $stmt->execute();
    $result = $stmt->get_result();

    //Check if item exists
    if ($result->num_rows != 1) {
        header("Location: " . $basePath . "/panel");
        exit();
    }
    $item = $result->fetch_assoc();

    //Get departments names
    $sql = "SELECT * FROM departments";
    $stmt = $conn->query($sql);
    $departments = $stmt;

    //Handle save item form
    $error = "";
    if (isset($_POST["save-item-form"])) {
        $item["item_name"] = $_POST["item_name"];
        $item["serial_number"] = $_POST["serial_number"];
        $item["department_id"] = $_POST["department_id"];

        if (!empty($item["item_name"])) {
            if (!empty($item["serial_number"])) {
                if (!empty($item["department_id"])) {
                    $error = "Success";
                } else {
                    $error = "Department ID is required";
                }
            } else {
                $error = "Serial number is required";
            }
        } else {
            $error = "Item name is required";
        }
    }

?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Edit Item</title>
        <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    </head>

    <body>
        <p>Edit item <?= $item["item_id"] ?></p>
        <form action="" method="POST">
            <input type="text" name="item_name" value="<?= $item["item_name"] ?>">
            <input type="text" name="serial_number" value="<?= $item["serial_number"] ?>">
            <select name="department_id">
                <?php
                while ($department = $departments->fetch_assoc()) {
                ?>
                    <option
                        value="<?= $department["department_id"] ?>"
                        <?= $item["department_id"] == $department["department_id"] ? "selected" : "" ?>>
                        <?= $department["department_name"] ?>
                    </option>
                <?php
                }
                ?>
            </select>
            <input type="submit" name="save-item-form" value="save">
        </form>
        <p class="text-red-500"><?= $error ?></p>
    </body>

    </html>
<?php

    //Page not found page
} else {
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>404 Error</title>
        <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    </head>

    <body>
        Page not found
    </body>

    </html>
<?php
}
?>