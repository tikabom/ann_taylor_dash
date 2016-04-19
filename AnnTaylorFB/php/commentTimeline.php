<?php

include '../DblogHeader.php';

function getCommentTimeline() {
	$postid = $_POST['postid'] . "%";
	
	$sql1 = "SELECT post_createdtime 
			FROM fb_posts 
			WHERE post_id = :postid";
	
	$sql2 = "SELECT no_of_comments
			FROM fb_posts a
			JOIN (		
			SELECT post_id, COUNT( * ) AS no_of_comments
			FROM fb_comments b
			WHERE comment_created_datetime
			BETWEEN :starttime
			AND :endtime
			GROUP BY post_id
			) AS c ON a.post_id = c.post_id
			WHERE a.post_id = :postid";
	
	$comments_per_hr = array();
	
	try {
		$db = getConnection();
		
		$stmt = $db->prepare($sql1);
		$stmt->bindParam("postid",$postid);
		$stmt->execute();
		$postDet = $stmt->fetchAll(PDO::FETCH_OBJ);
		$post_createdtime = $postDet[0]->post_createdtime;
		
		$stmt = $db->prepare($sql2);
		
		$stmt->bindParam("postid",$postid);
		$starttime = $post_createdtime;
		$endtime = date("Y-m-d H:i:s", strtotime($post_createdtime) + 1 * 3600);
		$stmt->bindParam("starttime", $starttime);
		$stmt->bindParam("endtime", $endtime);
		$stmt->execute();
		$comments_per_hr[0] = $stmt->fetchColumn();
		if(!$comments_per_hr[0])
			$comments_per_hr[0] = 0;
		$starttime = $endtime;
		$endtime = date("Y-m-d H:i:s", strtotime($post_createdtime) + 10 * 3600);
		$stmt->bindParam("starttime", $starttime);
		$stmt->bindParam("endtime", $endtime);
		$stmt->execute();
		$comments_per_hr[1] = $stmt->fetchColumn();
		if(!$comments_per_hr[1])
			$comments_per_hr[1] = 0;
		$starttime = $endtime;
		$endtime = date("Y-m-d H:i:s", strtotime($post_createdtime) + 24 * 3600);
		$stmt->bindParam("starttime", $starttime);
		$stmt->bindParam("endtime", $endtime);
		$stmt->execute();
		$comments_per_hr[2] = $stmt->fetchColumn();
		if(!$comments_per_hr[2])
			$comments_per_hr[2] = 0;
		$starttime = $endtime;
		$endtime = date("Y-m-d H:i:s", strtotime($post_createdtime) + 48 * 3600);
		$stmt->bindParam("starttime", $starttime);
		$stmt->bindParam("endtime", $endtime);
		$stmt->execute();
		$comments_per_hr[3] = $stmt->fetchColumn();
		if(!$comments_per_hr[3])
			$comments_per_hr[3] = 0;
		$starttime = $endtime;
		$endtime = date("Y-m-d H:i:s", strtotime($post_createdtime) + 60 * 3600);
		$stmt->bindParam("starttime", $starttime);
		$stmt->bindParam("endtime", $endtime);
		$stmt->execute();
		$comments_per_hr[4] = $stmt->fetchColumn();
		if(!$comments_per_hr[4])
			$comments_per_hr[4] = 0;
		$starttime = $endtime;
		$endtime = date("Y-m-d H:i:s", strtotime($post_createdtime) + 72 * 3600);
		$stmt->bindParam("starttime", $starttime);
		$stmt->bindParam("endtime", $endtime);
		$stmt->execute();
		$comments_per_hr[5] = $stmt->fetchColumn();
		if(!$comments_per_hr[5])
			$comments_per_hr[5] = 0;
		$starttime = $endtime;
		$endtime = date("Y-m-d H:i:s", strtotime($post_createdtime) + 84 * 3600);
		$stmt->bindParam("starttime", $starttime);
		$stmt->bindParam("endtime", $endtime);
		$stmt->execute();
		$comments_per_hr[6] = $stmt->fetchColumn();
		if(!$comments_per_hr[6])
			$comments_per_hr[6] = 0;
		$starttime = $endtime;
		$endtime = date("Y-m-d H:i:s", strtotime('NOW'));
		$stmt->bindParam("starttime", $starttime);
		$stmt->bindParam("endtime", $endtime);
		$stmt->execute();
		$comments_per_hr[7] = $stmt->fetchColumn();
		if(!$comments_per_hr[7])
			$comments_per_hr[7] = 0;
		
		$db = null;
		
		echo json_encode($comments_per_hr);
		
	} catch(PDOException $e) {
		create_log($e->getMessage());
	}
}

getCommentTimeline();

?>