<?php

include_once '../DblogHeader.php';
include_once 'helpers.php';
include_once '../simple_html_dom.php';

function getPins($url) {
	$json_string = file_get_contents('http://api.pinterest.com/v1/urls/count.json?callback=receiveCount&url=' . $url);
	$left_parantheses_pos = strpos($json_string, "(");
	$right_parantheses_pos = strpos($json_string, ")");
	$json_string = substr($json_string, $left_parantheses_pos + 1, $right_parantheses_pos - $left_parantheses_pos - 1);
	$json = json_decode($json_string, true);
	return intval($json['count']);
}

function addProductInfo($param1, $param2, $param3, $pin) {
	$getProd = "SELECT COUNT(*) FROM at_products WHERE id=:id";
	$addProd = "INSERT INTO at_products VALUES(:ID, :Name, :CatID, :pin)";
	$updateProd = "UPDATE at_products SET pin_count=:pin WHERE id=:ID";

	try {
		$db = getConnection();
		$stmt = $db->prepare($getProd);
		$stmt->bindParam("id", $param1);
		$stmt->execute();
		$exists = $stmt->fetchColumn();
		if ($exists == 0) {
			$stmt = $db->prepare($addProd);
			$stmt->bindParam("ID", $param1);
			$stmt->bindParam("Name", $param2);
			$stmt->bindParam("CatID", $param3);
			$stmt->bindParam("pin", $pin);
			$stmt->execute();
		} else {
			$stmt = $db->prepare($updateProd);
			$stmt->bindParam("ID", $param1);
			$stmt->bindParam("pin", $pin);
			$stmt->execute();
		}
	} catch (PDOException $e) {
		create_log($e->getMessage());
	}
}

function getATReDate($t) {
	$getsql1 = "SELECT MAX(review_date) FROM at_reviews WHERE id=:ProductID";
	try {
		$db = getConnection();
		$stmt = $db->prepare($getsql1);
		$stmt->bindParam("ProductID", $t);
		$stmt->execute();
		$reDate = $stmt->fetchColumn();
		$db = null;
		return $reDate;
	} catch (PDOException $e) {
		create_log($e->getMessage());
	}
}

function getATReview($t, $d) {
	$getsql1 = "SELECT review FROM at_reviews WHERE id=:ProductID and review_date =:Date1";
	try {
		$db = getConnection();
		$stmt = $db->prepare($getsql1);
		$stmt->bindParam("ProductID", $t);
		$stmt->bindParam("Date1", $d);
		$stmt->execute();
		$reFromDB = $stmt->fetchAll(PDO::FETCH_COLUMN);
		$db = null;
		return $reFromDB;
	} catch (PDOException $e) {
		create_log($e->getMessage());
	}
}

function addATReviews($revDate, $image_url, $id, $rating, $review, $catid, $nickname, $location, $age, $style, $body, $recomVal) {
	$getreviewer = "SELECT id FROM at_reviewer WHERE nickname=:name";
	$addreviewer = "INSERT INTO at_reviewer(nickname) VALUES(:name)";
	$addsql = "INSERT INTO at_reviews VALUES (:ID,:RevDate,:Image_url,:Rating,:Review,:ReviewerID,:Loc,:Age,:Style,:Body,:Recom)";
	$reviewerID = null;
	try {
		$db = getConnection();
		
		$stmt1 = $db->prepare($getreviewer);
		$stmt1->bindParam("name", $nickname);
		$stmt1->execute();
		$reviewerID = $stmt1->fetchColumn();
		if($reviewerID > 0) {
			
		} else {
			$stmt1 = $db->prepare($addreviewer);
			$stmt1->bindParam("name", $nickname);
			$stmt1->execute();
			$stmt1 = $db->prepare($getreviewer);
			$stmt1->bindParam("name", $nickname);
			$stmt1->execute();
			$reviewerID = $stmt1->fetchColumn();
		}

		$stmt1 = $db->prepare($addsql);
		$stmt1->bindParam("ID", $id);
		$stmt1->bindParam("RevDate", $revDate);
		$stmt1->bindParam("Image_url", $image_url);
		$stmt1->bindParam("Rating", $rating);
		$stmt1->bindParam("Review", $review);
		$stmt1->bindParam("ReviewerID",$reviewerID);
		$stmt1->bindParam("Loc", $location);
		$stmt1->bindParam("Age", $age);
		$stmt1->bindParam("Style", $style);
		$stmt1->bindParam("Body", $body);
		$stmt1->bindParam("Recom", $recomVal);
		$stmt1->execute();
		$db = null;
	} catch (PDOException $e) {
		create_log($e->getMessage());
	}
}

function scrapeReview($iden, $name, $catid) {
	create_log("in scrape reviews\r\n");
	//Adding pin count
	$pinCount = 0;
	$pinurl = 'http://www.anntaylor.com/' . $name . '/' . $iden;
	$pinCount = getPins($pinurl);
	addProductInfo($iden, $name, $catid, $pinCount);

	//Adding Reviews
	$revURL = 'http://reviews.anntaylor.com/0059-en_us/' . $iden . '/' . $name . '/reviews.htm';
	$curl2 = getHTML($revURL);
	$html1 = str_get_html($curl2);

	$reviews = $html1->find('div#BVSubmissionPopupContainer');
	$revcount = count($reviews);
	$images = $html1->find('div#BVExternalSubjectImageID a img');
	$image_url = htmlspecialchars_decode($images[0]->src);

	$revcounter = 0;
	for ($i = $revcount - 1; $i >= 0; $i--) {
		$nickname = $reviews[$i]->find('div.BVRRUserNicknameContainer span.BVRRNickname');
		$rating = $reviews[$i]->find('div.BVRRRatingNormalOutOf span.value');
		$review = $reviews[$i]->find('span.BVRRReviewText');
		$location = $reviews[$i]->find('div.BVRRUserLocationContainer span.BVRRValue');
		$age = $reviews[$i]->find('div.BVRRContextDataValueAgeContainer span.BVRRValue');
		$style = $reviews[$i]->find('div.BVRRContextDataValueStyleContainer span.BVRRValue');
		$body = $reviews[$i]->find('div.BVRRContextDataValueBodyTypeContainer span.BVRRValue');
		$recom = $reviews[$i]->find('div.BVRRRecommendedContainer span.BVRRValue');
		if(count($nickname) == 0)
			$nick = NULL;
		else {
			$nick = $nickname[0]->innertext;
		}
		if (count($location) == 0)
			$loc = NULL;
		else {
			$loc = $location[0]->innertext;
		}
		if (count($age) == 0)
			$age1 = NULL;
		else {
			$age1 = $age[0]->innertext;
		}
		if (count($style) == 0)
			$style1 = NULL;
		else {
			$style1 = $style[0]->innertext;
		}
		if (count($body) == 0)
			$body1 = NULL;
		else {
			$body1 = $body[0]->innertext;
		}
		$recomFlag = FALSE;
		if (count($recom) > 0) {
			$recomFlag = TRUE;
		}
		$date = $reviews[$i]->find('div.BVRRReviewDateContainer span.value-title');
		$revDate = $date[0]->title;
		$maxDate = getATReDate($iden);

		if (is_null($style1)) {
			$sss = "";
		} else {
			$sss = substr($style1, 0, strpos($style1, ":"));
		}

		if ($revDate > $maxDate) {
			addATReviews($revDate, $image_url, $iden, $rating[0]->innertext, htmlspecialchars_decode($review[0]->innertext), $catid, $nick, $loc, $age1, $sss, $body1, $recomFlag);
		} else {
			$reviewFromDB = getATReview($iden, $revDate);
			$exists = FALSE;
			$check = $review[0]->innertext;
			foreach ($reviewFromDB as $r) {
				if (stristr($r, substr($check, 0, 50))) {
					$exists = TRUE;
					break;
				}
			}
			if ($exists !== TRUE) {
				addATReviews($revDate, $image_url, $iden, $rating[0]->innertext, htmlspecialchars_decode($review[0]->innertext), $catid, $nick, $loc, $age1, $sss, $body1, $recomFlag);
			}
		}

		$revcounter++;
		if ($revcounter == 10) {
			break;
		}
	}
	$html1->clear();
	unset($html1);
}

function scrapeOutfit($url, $catid) {
	$curl2 = getHTML($url);
	$html1 = str_get_html($curl2);
	$outfits = $html1->find('ol.list-outfit li.outfitItem');
	$outfitcount = count($outfits);
	for ($i = 0; $i < $outfitcount; $i++) {
		$link = $outfits[$i]->find('div.hd-info a.clickthrough');
		$href = $link[0]->href;
		$pos1 = strpos($href, '/');
		$pos2 = strpos($href, '/', $pos1 + 1);
		$pos3 = strpos($href, '&', $pos2 + 1);
		$name = substr($href, $pos1 + 1, $pos2 - $pos1 - 1);
		$iden = substr($href, $pos2 + 1, $pos3 - $pos2 - 1);
		scrapeReview($iden, $name, $catid);
	}
}

function scrapeCat($url, $catid) {
	$curl1 = getHTML($url);
	$cat2html = str_get_html($curl1);
	$cathtmls = $cat2html->find('div.products div.gu');

	$flag = false;
	$counter = 0;

	for ($j = 0; $j < count($cathtmls); $j++) {
		$products = $cathtmls[$j];

		foreach ($products->find('div.fg img') as $e) {
			$link = $e->parent()->parent()->children(2)->href;
			$split = explode("?", $link);
			$pieces = explode("/", $split[0]);
			$count = count($pieces);
			$id = explode(";", $pieces[$count - 1]);
			$iden = $id[0];
			$name = $pieces[$count - 2];
			$counter++;
			scrapeReview($iden, $name, $catid);
		}
		if ($counter == 5) {
			break;
		}
	}
}

?>