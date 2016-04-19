<?php

include '../DblogHeader.php';
include '../sentiment.class.php';

function getSentiments() {
	$postid = $_POST['postid'] . "%";

	$sql = "SELECT comment_text, user_fullname 
			FROM fb_posts a 
			JOIN fb_comments b 
			ON a.post_id = b.post_id 
			JOIN fb_user c 
			ON b.fb_userid = c.user_id 
			WHERE a.post_id = :postid";
	
	$sen = array('pos' => 0, 'neu' => 0, 'neg' => 0);
	$senScore = array('pos' => array('score' => array(), 'name' => array()), 'neu' => array('score' => array(), 'name' => array()), 'neg' => array('score' => array(), 'name' => array()));
	
	try {
		$db = getConnection();
		$stmt = $db->prepare($sql);
		$stmt->bindParam("postid", $postid);
		$stmt->execute();
		$comments = $stmt->fetchAll(PDO::FETCH_OBJ);
		
		$sentiment = new Sentiment();
		
		foreach($comments as $comment) {
			$cat = $sentiment->categorise($comment->comment_text);
			$scores = $sentiment->score($comment->comment_text);
			$score = floatval($scores[$cat]) * 100;
			$senScore[$cat]['score'][$sen[$cat]] = $score;
			$senScore[$cat]['name'][$sen[$cat]] = $comment->user_fullname;
			$sen[$cat] = $sen[$cat] + 1;
		}
		
		echo json_encode(array('sentiments' => $sen, 'scores' => $senScore));
		
		$db = null;
		
	} catch (PDOException $e) {
		create_log($e->getMessage());
	}
}

getSentiments();

?>