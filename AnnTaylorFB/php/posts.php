<?php

include '../DblogHeader.php';

function getPosts() {
	$type = $_POST['type'];
	$limit = intval($_POST['limit']);
	$orderBy = $_POST['orderBy'];
	$order = "DESC";
	if($orderBy == "post_message")
		$order = "ASC";
	
	$column = "";
	$cond = "";
	$join = "";
	
	if ($type == "promotions") {
		$cond = " AND post_message LIKE '%\% off%' OR post_message LIKE '%win%'";
	} elseif ($type == "blog") {
		$cond = " AND post_type = '3'";
	} elseif ($type == "pinterest") {
		$cond = " AND post_type = '4'";
	} elseif ($type == "instagram") {
		$cond = " AND post_type = '5'";
	} elseif ($type == "catalog") {
		$column = ", c.name";
		$cond = " AND atID LIKE 'cat%'";
		$join = " JOIN at_categories c 
			ON a.atID = c.id";
	} elseif ($type == "product") {
		$column = ", c.name";
		$cond = " AND atID NOT LIKE 'cat%' AND atID <> ''";
		$join = " JOIN at_products c 
			ON a.atID = c.id";
	}
	
	$sql = "SELECT a.post_id, post_createdtime, post_message, post_likes, post_shares, b.post_comments" . $column .
			" FROM fb_posts a 
			LEFT JOIN (SELECT post_id, count( * ) AS post_comments 
			FROM fb_comments 
			GROUP BY post_id)b ON a.post_id = b.post_id" . $join . 
			" WHERE post_likes <>0" . $cond . 
			" ORDER BY " . $orderBy . " " . $order . "
			LIMIT :limit, 5";
	
	try {
		
		$db = getConnection();
		$stmt = $db->prepare($sql);
		$stmt->bindParam("limit", $limit, PDO::PARAM_INT);
		$stmt->execute();
		$posts = $stmt->fetchAll(PDO::FETCH_OBJ);
		
		echo json_encode($posts);
		
		$db = null;
		
	} catch(PDOException $e) {
		create_log($e->getMessage());
	}
}

getPosts();

?>