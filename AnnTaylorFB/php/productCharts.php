<?php

include '../DblogHeader.php';
include '../sentiment.class.php';

function getProductCharts() {
	$postid = $_POST['postid'];

	$sentiment = new Sentiment();
	
	$sql = "SELECT b.id, name, image_url, rating, review 
			FROM fb_posts a 
			JOIN at_products b 
			ON a.atID = b.id 
			JOIN at_reviews c 
			ON b.id = c.id 
			WHERE a.post_id = :postid";
	
	$sql1 = "SELECT style, COUNT(style) AS style_count 
			FROM fb_posts a 
			JOIN 
			(SELECT id, style
			FROM at_reviews 
			WHERE style <> '') AS c 
			ON a.atID = c.id
			WHERE a.post_id = :postid
			GROUP BY style
			ORDER BY style";
	
	$sql2 = "SELECT age, COUNT(age) AS age_count 
			FROM fb_posts a 
			JOIN 
			(SELECT id, age
			FROM at_reviews 
			WHERE age <> '') AS c 
			ON a.atID = c.id
			WHERE a.post_id = :postid
			GROUP BY age
			ORDER BY age";
	
	$sql3 = "SELECT body_style, COUNT(body_style) AS body_style_count 
			FROM fb_posts a 
			JOIN 
			(SELECT id, body_style
			FROM at_reviews 
			WHERE body_style <> '') AS c 
			ON a.atID = c.id
			WHERE a.post_id = :postid
			GROUP BY body_style
			ORDER BY body_style";
	
	$sql4 = "SELECT location, COUNT(location) AS location_count 
			FROM fb_posts a 
			JOIN 
			(SELECT id, location
			FROM at_reviews 
			WHERE location <> '') AS c 
			ON a.atID = c.id
			WHERE a.post_id = :postid
			GROUP BY location
			ORDER BY location";
	
	$sql5 = "SELECT location, review 
			FROM fb_posts a 
			JOIN 
			(SELECT id, location, review 
			FROM at_reviews 
			WHERE location <> '') AS c 
			ON a.atID = c.id 
			WHERE a.post_id = :postid";
	
	$sql6 = "SELECT pin_count 
			FROM fb_posts a 
			JOIN at_products b 
			ON a.atID = b.id 
			WHERE a.post_id = :postid";
	
	$image_url = "";
	$ID = "";
	$Name = "";
	$avg_rating = 0.0;
	$map = array(array());
	$count = 0;
	$sen = array('pos' => 0, 'neg' => 0, 'neu' => 0);
	$style = array('Classic' => 0, 'Feminine' => 0, 'Modern' => 0, 'Buttonedup' => 0, 'Fashionforward' => 0);
	$age = array(0, 0, 0, 0, 0, 0, 0);
	$body = array('Slender' => 0, 'Straight' => 0, 'CurvyOnTop' => 0, 'CurvyOnBottom' => 0, 'Hourglass' => 0, 'Fullfigured' => 0, 'Petite' => 0, 'Tall' => 0);
	
	try {
		$db = getConnection();
		$stmt = $db->prepare($sql);
		$stmt->bindParam("postid", $postid);
		$stmt->execute();
		$rec = $stmt->fetchAll(PDO::FETCH_OBJ);
		
		$image_url = $rec[0]->image_url;
		$ID = $rec[0]->id;
		$Name = $rec[0]->name;
		
		foreach ($rec as $row) {
			$avg_rating = $avg_rating + $row->rating;
			$cat = $sentiment->categorise($row->review);
			$sen[$cat] = $sen[$cat] + 1;
		}
		$avg_rating = $avg_rating / (count($rec));
	
		$stmt = $db->prepare($sql1);
		$stmt->bindParam("postid", $postid);
		$stmt->execute();
		$style = $stmt->fetchAll(PDO::FETCH_OBJ);
	
		$stmt = $db->prepare($sql2);
		$stmt->bindParam("postid", $postid);
		$stmt->execute();
		$age = $stmt->fetchAll(PDO::FETCH_OBJ);
	
		$stmt = $db->prepare($sql3);
		$stmt->bindParam("postid", $postid);
		$stmt->execute();
		$body = $stmt->fetchAll(PDO::FETCH_OBJ);
	
		$stmt = $db->prepare($sql4);
		$stmt->bindParam("postid", $postid);
		$stmt->execute();
		$loc = $stmt->fetchAll(PDO::FETCH_OBJ);
	
		$stmt = $db->prepare($sql6);
		$stmt->bindParam("postid", $postid);
		$stmt->execute();
		$pin = $stmt->fetchColumn();
	
		$stmt = $db->prepare($sql5);
		$stmt->bindParam("postid", $postid);
		$stmt->execute();
		$atSen = $stmt->fetchAll(PDO::FETCH_OBJ);
		foreach($atSen as $s) {
			$map[$count][0] = $s->location;
			$cat = $sentiment->categorise($s->review);
			if ($cat == "pos")
				$map[$count][1] = 200 + round($sentiment->score($s->review),2);
			elseif ($cat == "neu") {
				$map[$count][1] = 100 + round($sentiment->score($s->review),2);
			} else {
				$map[$count][1] = 0 + round($sentiment->score($s->review),2);
			}
			$count = $count + 1;
		}
	
		$db = null;
	
		$arr = array('image_url' => $image_url, 'ID' => $ID, 'Name' => $Name, 'avg_rating' => $avg_rating, 'pos' => $sen['pos'], 'neg' => $sen['neg'], 'neu' => $sen['neu'], 'style' => $style, 'age' => $age, 'body' => $body, 'loc' => $loc, 'map' => $map,"pinCount" => $pin);
	
		echo json_encode($arr);
		
	} catch (PDOException $e) {
		create_log($e->getMessage());
	}
}

getProductCharts();
?>