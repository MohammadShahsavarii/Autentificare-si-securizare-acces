<?php
session_start();

$host = 'localhost';
$username = 'root';
$password = '12345678';
$database = 'tema3';

// connect to database
$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// check registration form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    //  check CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF Token");
    }

    $username = htmlspecialchars($_POST["username"]);
    $password = htmlspecialchars($_POST["password"]);

    // check reCAPTCHA
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

    // make safe query
    $query = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $query->bind_param("ss", $username, $password);

    // run query
    if ($query->execute()) {
        
        header("Location: first.php");
        exit();

    } else {
        echo "Error in sign up" . $query->error;
    }

    // close query
    $query->close();
}

// close connection to database
$conn->close();

// make token CSRF
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGN UP</title>
    <!-- add script reCAPTCHA -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
    <h2>sign up</h2>
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

        <input type="submit" value="sign up">
    </form>
</body>
</html>
