<?php

include '../DblogHeader.php';

function getPostCount() {
	
	$type = $_POST['type'];
	
	$cond = "";
	
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
	
	$sql = "SELECT COUNT(*) 
			FROM fb_posts 
			WHERE post_likes <> 0" . $cond;
	
	try {
		
		$db = getConnection();
		$stmt = $db->query($sql);
		$count = $stmt->fetchColumn();

		echo $count;
		
		$db = null;
		
	} catch(PDOException $e) {
		create_log($e->getMessage());
	}
}

getPostCount();

?>