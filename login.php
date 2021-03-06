<?php
	session_start();
	date_default_timezone_set('Europe/Copenhagen');
	include 'db.php';

	if(isset($_POST["user"]) && $_POST["user"] &&
	   isset($_POST["pass"]) && $_POST["pass"]) {

		$user = $_POST["user"];
		$pass = $_POST["pass"];

		$query = $conn->prepare("SELECT * FROM users WHERE username= :username LIMIT 1");
		$query->bindParam(":username", $user);
		$query->execute();

		$result = $query->fetch(PDO::FETCH_ASSOC);
		$password = $result['password'];
		$username = $result['username'];
		$user_id = $result['id'];

		$check_attempt = $result['attempt'];
		$number_attempts = $check_attempt ? $check_attempt : 0;
		$last_attempt = $result['date'];
		$now = (new DateTime())->format('Y-m-d H:i:s');

		$lastDate=	strtotime($last_attempt);
		$newDate=  strtotime($now);
		$interval = abs($newDate - $lastDate);
		$difference = round($interval/60);
		$time_left = 5-$difference;
		
		$passwordCheck = password_verify($pass, (string)$password);

		function update_db($number_attempts, $username) {
			include 'db.php';
			$attempt_time = (new DateTime())->format('Y-m-d H:i:s');
			$query = $conn->prepare("
				UPDATE users 
				SET `attempt`=:number_attempts , `date`=:attempt_time
				WHERE `username`=:username ");

			$query->bindParam(":number_attempts", $number_attempts);
			$query->bindParam(":attempt_time", $attempt_time);
			$query->bindParam(":username", $username);
			$query->execute();
		}

		if($difference >= 5 || $number_attempts < 3){
			if ($passwordCheck) {
				$_SESSION["logged_in"] = "true";
				$_SESSION['username'] = $username;
				$_SESSION['user_id'] = $user_id;
				$response = "Success";
			} elseif ($username === $user && !$passwordCheck)  {
				if($number_attempts == 2) {

					$number_attempts++;
					update_db($number_attempts, $username);
					$response = "Account_locked";
				} else {

					$difference >= 5 ? $number_attempts = 1 : $number_attempts++;
					update_db($number_attempts, $username);
					$response = "Wrong_password";
				}	
			} else {
				$response = "Invalid_information";
			}
		} else {
			$response = "Locked";
		}
	}
	echo json_encode($response);
?>