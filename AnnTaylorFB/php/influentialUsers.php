<?php

include '../DblogHeader.php';

function getInfluentialUsers() {
	$postid = $_POST['postid'] . "%";
	
	$sql1 = "SELECT user_fullname, comment_likes 
			FROM fb_posts a 
			JOIN fb_comments b 
			ON a.post_id = b.post_id 
			JOIN fb_user c 
			ON b.fb_userid = c.user_id 
			WHERE a.post_id = :postid 
			AND comment_likes > 0 
			ORDER BY comment_likes DESC";
	
	$sql2 = "SELECT COUNT(*) 
			FROM 
			(SELECT DISTINCT post_id
			FROM fb_comments a 
			JOIN fb_user b 
			ON a.fb_userid = b.user_id 
			WHERE user_fullname=:fullname) 
			AS c";
	
	$sql3 = "SELECT COUNT(*) 
			FROM 
			(SELECT DISTINCT fb_postid 
			FROM fb_likes a 
			JOIN fb_user b 
			ON a.fb_userid = b.user_id 
			WHERE user_fullname=:fullname) 
			AS c";
	
	$commentCount = array();
	$likeCount = array();
	
	try {
		$db = getConnection();
		$stmt = $db->prepare($sql1);
		$stmt->bindParam("postid", $postid);
		$stmt->execute();
		$users = $stmt->fetchAll(PDO::FETCH_OBJ);
		
		foreach($users as $i => $user) {
			$fullname = $user->user_fullname;
			$stmt = $db->prepare($sql2);
			$stmt->bindParam("fullname", $fullname);
			$stmt->execute();
			$commentCount[$i] = $stmt->fetchColumn();
			$stmt = $db->prepare($sql3);
			$stmt->bindParam("fullname", $fullname);
			$stmt->execute();
			$likeCount[$i] = $stmt->fetchColumn();
		}
		
		$db = null;
		
		$resp = array("users" => $users, "commentCount" => $commentCount, "likeCount" => $likeCount);
		
		echo json_encode($resp);
		
	} catch (PDOException $e) {
		create_log($e->getMessage());
	}
}

getInfluentialUsers();

?>