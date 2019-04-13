<?php
session_start();
require "database.php";
if ($_POST){
    // Create an account with the data given from the post.
    $name = $_POST['name'];
    $email = $_POST['email'];
    $mobile = $_POST['mobile'];
    $password = MD5 ($_POST['password']);
    $description = $_POST['description'];
    $fileName = $_FILES['Filename']['name'];
    $tempFileName = $_FILES['Filename']['tmp_name'];
    $fileSize = $_FILES['Filename']['size'];
    $fileType = $_FILES['Filename']['type'];
    $pdo = Database::connect();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // put the content of the file into a variable, $content
    $fp      = fopen($tempFileName, 'r');
    $content = fread($fp, filesize($tempFileName));
    fclose($fp);
    // Add the data to the database.
    $sql = "INSERT INTO customers (name,email,mobile, password_hash, filename, filetype,content,filesize,description) values(?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $q = $pdo->prepare($sql);
    $q->execute(array($name,$email,$mobile, $password, $fileName, $fileType, $content, $fileSize, $description));
    // Now try to query that username / password combination to make sure the account was created successfully.
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $sql = "SELECT * FROM customers WHERE email = ? AND password_hash = ? LIMIT 1";
    $q = $pdo->prepare($sql);
    $q->execute(array($email,$password));
    $data = $q->fetch(PDO::FETCH_ASSOC);
    // If we got data back, the account was created successfully. Go to customer.php.
    if ($data) {
	$_SESSION["username"] = $username;
	header("Location: customer.php");
        $id = $data["id"];
        $fileLocation = "uploads/" . $id ."/";
        $fileFullPath = $fileLocation . $fileName;
        if (!file_exists($fileLocation))
            mkdir ($fileLocation, 0777, true); // create subdirectory, if necessary
        else
            array_map('unlink', glob($fileLocation . "*"));
        move_uploaded_file($tempFileName, $fileFullPath);
        chmod($fileFullPath, 0777);
        $absolutePath = realpath($fileFullPath);
        $sql = "UPDATE customers  set absolutepath = ? WHERE id = ?";
        $q = $pdo->prepare($sql);
        $q->execute(array($absolutePath, $id));
        Database::disconnect();
	header("Location: customer.php");
    }
    else
	header("location: createAccount.php?errorMessage=Something went wrong. Please try again.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset='UTF-8'>
    <script src=\"https://code.jquery.com/jquery-3.3.1.min.js\"
            integrity=\"sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=\"
            crossorigin=\"anonymous\"></script>
    <link href='https://stackpath.bootstrapcdn.com/bootstrap/4.1.2/css/bootstrap.min.css' rel='stylesheet'>
    <script src='https://stackpath.bootstrapcdn.com/bootstrap/4.1.2/js/bootstrap.min.js'></script>
    <style>label {width: 5em;}</style>
</head>

<div class="container">
    <h1>Join</h1>
    <form method="post" enctype="multipart/form-data" onsubmit="return Validate(this)">
        <img id=imgDisplay overflow=hidden width=200 height=200 src=""/><br>
        <input type="file" name="Filename" onchange="readURL(this);" required><br>
        Description: <br><input name="description" type="text" placeholder="description" required><br>
        Name: <br><input name="name" type="text" placeholder="name" required><br>
        Email: <br><input name="email" type="text" placeholder="me@email.com" required><br>
        Mobile (123-456-7890): <br><input name="mobile" type="tel" placeholder="123-456-7890" pattern="[0-9]{3}-[0-9]{3}-[0-9]{4}" required><br>
        Password: <br><input name="password" type="password" placeholder="password" required><br>
        <button type="submit" class="btn btn-success">Join</button>
        <?php
        // Display's an error message if there is one.
        if ($errorMessage) {
            echo "<p class=\"alert alert-danger\" role=\"alert\">$errorMessage</p>";
        }
        ?>
    </form>
</div>
</html>

<script type="text/javascript">
    function readURL(input) {
        if (input.files[0].size > 1000000) {
            input.value = null;
            alert("The picture cannot be larger than 1MB in size!");
        }
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                document.getElementById("imgDisplay").setAttribute("src", e.target.result);
            }
            reader.readAsDataURL(input.files[0]);
        } else {
            document.getElementById("imgDisplay").setAttribute("src", null);
        }
    }
    var _validFileExtensions = [".jpg", ".jpeg", ".gif", ".png"];
    function Validate(oForm) {
        var arrInputs = oForm.getElementsByTagName("input");
        for (var i = 0; i < arrInputs.length; i++) {
            var oInput = arrInputs[i];
            if (oInput.type == "file") {
                var sFileName = oInput.value;
                if (sFileName.length > 0) {
                    var blnValid = false;
                    for (var j = 0; j < _validFileExtensions.length; j++) {
                        var sCurExtension = _validFileExtensions[j];
                        if (sFileName.substr(sFileName.length - sCurExtension.length, sCurExtension.length).toLowerCase() == sCurExtension.toLowerCase()) {
                            blnValid = true;
                            break;
                        }
                    }
                    if (!blnValid) {
                        alert("Sorry, " + sFileName + " is invalid, allowed extensions are: " + _validFileExtensions.join(", "));
                        return false;
                    }
                }
            }
        }
        return true;
    }
</script>