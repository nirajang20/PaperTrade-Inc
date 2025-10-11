<?php
require "lib/Core.php";
require PATH_LIB . "PAGE-top.php"; ?>

<?php
// Initialize variables and error messages
$name = $email = $password = "";
$nameErr = $emailErr = $passwordErr = "";
$contactName = $contactMessage = "";
$contactNameErr = $contactMessageErr = "";

// Check which form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // FORM 1: Registration
    if (isset($_POST['register'])) {

        // Name validation
        if (empty($_POST["name"])) {
            $nameErr = "Name is required";
        } else {
            $name = htmlspecialchars($_POST["name"]);
            if (!preg_match("/^[a-zA-Z-' ]*$/", $name)) {
                $nameErr = "Only letters and white space allowed";
            }
        }

        // Email validation
        if (empty($_POST["email"])) {
            $emailErr = "Email is required";
        } else {
            $email = htmlspecialchars($_POST["email"]);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $emailErr = "Invalid email format";
            }
        }

        // Password validation
        if (empty($_POST["password"])) {
            $passwordErr = "Password is required";
        } else {
            $password = htmlspecialchars($_POST["password"]);
            if (strlen($password) < 6) {
                $passwordErr = "Password must be at least 6 characters long";
            }
        }

        // Success message
        if (empty($nameErr) && empty($emailErr) && empty($passwordErr)) {
            echo "<p style='color:green;'>Registration Successful!</p>";
            echo "<p>Name: $name</p>";
            echo "<p>Email: $email</p>";

            echo '<script>
Swal.fire({
  title: "Good job!",
  text: "You clicked the button!",
  icon: "success"
}).then(() => {
  // This runs after user clicks OK or outside
  alert("done");
});
</script>';
        }

    }

    // FORM 2: Contact Form
    if (isset($_POST['contact'])) {

        // Name validation
        if (empty($_POST["contactName"])) {
            $contactNameErr = "Name is required";
        } else {
            $contactName = htmlspecialchars($_POST["contactName"]);
        }

        // Message validation
        if (empty($_POST["contactMessage"])) {
            $contactMessageErr = "Message cannot be empty";
        } else {
            $contactMessage = htmlspecialchars($_POST["contactMessage"]);
            if (strlen($contactMessage) < 10) {
                $contactMessageErr = "Message must be at least 10 characters long";
            }
        }

        // Success message
        if (empty($contactNameErr) && empty($contactMessageErr)) {
            echo "<p style='color:green;'>Message sent successfully!</p>";
            echo "<p>Name: $contactName</p>";
            echo "<p>Message: $contactMessage</p>";
        }
    }
}
?>


    <style>
        body { font-family: Arial; margin: 40px; }
        form { border: 1px solid #ccc; padding: 20px; width: 350px; margin-bottom: 30px; }
        .error { color: red; font-size: 14px; }
        h2 { color: #333; }
    </style>


<h2>Registration Form</h2>
<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
    Name: <br>
    <input type="text" name="name" value="<?php echo $name; ?>">
    <span class="error"><?php echo $nameErr; ?></span><br><br>

    Email: <br>
    <input type="text" name="email" value="<?php echo $email; ?>">
    <span class="error"><?php echo $emailErr; ?></span><br><br>

    Password: <br>
    <input type="password" name="password">
    <span class="error"><?php echo $passwordErr; ?></span><br><br>

    <input type="submit" name="register" value="Register">
</form>


<h2>Contact Form</h2>
<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
    Name: <br>
    <input type="text" name="contactName" value="<?php echo $contactName; ?>">
    <span class="error"><?php echo $contactNameErr; ?></span><br><br>

    Message: <br>
    <textarea name="contactMessage" rows="4" cols="30"><?php echo $contactMessage; ?></textarea>
    <span class="error"><?php echo $contactMessageErr; ?></span><br><br>

    <input type="submit" name="contact" value="Send Message">
</form>

<script>
Swal.fire({
  title: "Alert!",
  text: "Sweet Alert is working!",
  icon: "success"
}).then(() => {
  // This runs after user clicks OK or outside
  alert("done");
});
</script>
<?php require PATH_LIB . "PAGE-bottom.php"; ?>
