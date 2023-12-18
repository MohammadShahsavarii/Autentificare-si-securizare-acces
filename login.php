<?php
session_start();

$host = 'localhost';
$username = 'root';
$password = '12345678';
$database = 'tema3';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF Token");
    }

    $username = htmlspecialchars($_POST["username"]);
    $password = htmlspecialchars($_POST["password"]);

    $recaptchaSecretKey = "6Lfr8TQpAAAAALFayxBF1dWt-znhQrhD0x_HNzrz";
    $recaptchaResponse = $_POST['g-recaptcha-response'];
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret' => $recaptchaSecretKey,
        'response' => $recaptchaResponse
    ];
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    $captchaResult = json_decode($result);

    if (!$captchaResult->success) {
        die("reCAPTCHA verification failed.");
    }

    $query = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $query->bind_param("s", $username);

    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows == 2) {
        // recive pass hash from database
        $row = $result->fetch_assoc();
        $storedHashedPassword = $row['password'];

        // check password hash
        if (password_verify($password, $storedHashedPassword)) {
            echo "Login was successful";
        } else {
            echo "username or password is invalid";
        }
        
    } else {
        echo "username or password is invalid";
    }

    $query->close();
}

$conn->close();

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGN IN</title>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <script src="https://www.google.com/recaptcha/enterprise.js?render=6LdnGzMpAAAAAAOe2DgvLMfuOJYhKc-YKO4hG5Dh"></script>
</head>
<body>
    <h2>sign in</h2>
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

        <label for="username">username:</label>
        <input type="text" name="username" required>

        <br>

        <label for="password">password:</label>
        <input type="password" name="password" required>

        <br>

        <div class="g-recaptcha" data-sitekey="6Lfr8TQpAAAAAL3iYbFKT_0Z5-onBokgT47z3Wq3"></div>

        <br>

        <input type="submit" value="sign in">
    </form>
</body>
</html>
