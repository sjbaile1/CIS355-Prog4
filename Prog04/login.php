<?php
session_start();
require "database.php";

if ($_POST){
    
    $username = $_POST['username'];
    $password = MD5 ($_POST['password']);
    $pdo = Database::connect();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
   
    $sql = "SELECT * FROM customers WHERE email='$username' AND password_hash ='$password' LIMIT 1";
    $q = $pdo -> prepare($sql);
    $q -> execute(array());
    $data = $q->fetch(PDO::FETCH_ASSOC);
  
    if ($data) {
        $_SESSION["username"] = $username;
      
        header("Location: customer.php");
    } else // Otherwise, try to log in again.
        header("Location: login.php?errorMessage=Incorrect username and or password!");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <link   href="css/bootstrap.min.css" rel="stylesheet">
    <script src="js/bootstrap.min.js"></script>

</head>

<body>
    <div class="container">

		<div class="span10 offset1">
		


			<div class="row">
				<h3>Prog04 Uploading Files</h3>
			</div>

			<form class="form-horizontal" action="login.php" method="post">
								  
				<div class="control-group">
					<label class="control-label">Username (Email)</label>
					<div class="controls">
						<input name="username" type="text"  placeholder="username@svsu.edu" required> 
					</div>	
				</div> 
				
				<div class="control-group">
					<label class="control-label">Password</label>
					<div class="controls">
						<input name="password" type="password" placeholder="used generic remember password" required> 
					</div>	
				</div> 

				<div class="form-actions">
					<button type="submit" class="btn btn-success">Sign in</button>
					&nbsp; &nbsp;
					<a class="btn btn-primary" href="createNewPerson.php">Join</a>
				</div>
				

				

				
			</form>


		</div> 
				
    </div> 

  </body>
  
</html>