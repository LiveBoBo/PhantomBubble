<?php  
	session_start();
	include 'db.php';

	if (!isset($_SESSION['logged_in']))
		header("Location: index.php");


	if(isset($_POST["roomName"]) && $_POST["roomName"]) {
		$roomName = $_POST["roomName"];
		$userId= $_SESSION["user_id"];
		
		$query = $conn->prepare("
			SELECT name, created_by
			FROM rooms
			WHERE name=:roomName and created_by=:userId
		");
		$query->bindParam(":roomName", $roomName);
		$query->bindParam(":userId", $userId);
		$query->execute();
		$result = $query->fetch(PDO::FETCH_ASSOC);

		//If the room name is used dont allow to create another room with the same name
		if(!$result){
			define('AES_256_CBC', 'aes-256-cbc');
			$encryption_key = openssl_random_pseudo_bytes(32);
			$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(AES_256_CBC));

			$query = $conn->prepare("
				INSERT INTO `rooms`(`name`, `created_by`, `room_key`, `room_iv`) 
				VALUES (:room_name, :created_by, :encryption_key, :room_iv)
			");
			$query->bindParam(":created_by", $userId);
			$query->bindParam(":room_name", $roomName);
			$query->bindParam(":encryption_key", $encryption_key);
			$query->bindParam(":room_iv", $iv);
			$query->execute();

			$query = $conn->prepare("
				SELECT id, created_by
				FROM rooms
				WHERE `name`=:room_name and `created_by`=:created_by 
			");
			$query->bindParam(":created_by", $userId);
			$query->bindParam(":room_name", $roomName);
			$query->execute();
			$result = $query->fetch(PDO::FETCH_ASSOC);
			
			$newroomId = $result['id'];
			$roomAdmin = $result['created_by'];

			$query = $conn->prepare("
				INSERT INTO `rooms_users`(`user_id`, `room_id`) 
				VALUES (:user_id,:room_id)
			");
			$query->bindParam(":user_id", $roomAdmin);
			$query->bindParam(":room_id", $newroomId);
			$query->execute();

			$_SESSION['room_number'] = $newroomId;
			$response = "Success";
		} else {
			$response = "Duplicate";
		}
	}

	// Check if the user exists in that specific room. If yes, send him there.
	if(isset($_POST["roomId"]) && $_POST["roomId"]) {
		$roomId = $_POST["roomId"];
		$userId= $_SESSION['user_id'];

		$query = $conn->prepare("
				SELECT room_id
				FROM rooms_users 
				WHERE user_id=:temp_userid
		");
		$query->bindParam(":temp_userid", $userId);
		$query->execute();

		while ($result = $query->fetch(PDO::FETCH_ASSOC)) {
			$result_room = $result['room_id'];

			if($roomId == $result_room) {
				$_SESSION['room_number'] = $result_room;
				$response = "Advance";
			}
			
			if(empty($result)){
				$response = "Invalid_room";
			}
		}
	}
	echo json_encode($response);
?>