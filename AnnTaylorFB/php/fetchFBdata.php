<?php

include_once '../DblogHeader.php';
include_once 'helpers.php';
include_once 'fetchATdata.php';
require_once '../src/facebook.php';

function getAccessToken($facebook) {
	$access_token = "";
	$sql = "INSERT INTO facebook_data VALUES(:accessToken)";
	$sql1 = "SELECT access_token FROM facebook_data";

	$db = getConnection();
	$stmt = $db->query($sql1);
	$access_token = $stmt->fetchColumn();
	if (strlen($access_token) > 0) {

	} else {
		$app_token_url = "https://graph.facebook.com/oauth/access_token?"
				. "client_id=" . $facebook->getAppId()
				. "&client_secret=" . $facebook->getAppSecret()
				. "&grant_type=client_credentials";

		$response = file_get_contents($app_token_url);
		$app_access_token = null;
		parse_str($response, $app_access_token);
		$access_token = $app_access_token['access_token'];
		$db = getConnection();
		$stmt = $db->prepare($sql);
		$stmt->bindParam("accessToken", $access_token);
		$stmt->execute();
	}
	$db = null;
	return $access_token;
}

function getDisplay($iden) {
	$sql = "SELECT COUNT(*) FROM at_products WHERE cat_id=:catid";

	try {
		$db = getConnection();
		$stmt = $db->prepare($sql);
		$stmt->bindParam("catid", $iden);
		$stmt->execute();
		$count = $stmt->fetchColumn();

		if($count > 0) {
			return 1;
		} else {
			return 0;
		}

		$db = NULL;
	} catch (PDOException $e) {
		echo $e->getMessage();
	}

	return 0;
}

function getPostType($url) {
	$typeID = 1;
	$typeName = "online-shopping";
	
	if ($url == "" || strpos($url, "bit.ly") > 0)
		return $typeID;
	
	if (strpos($url, "blog.anntaylor.com") > 0) {
		$typeName = "blog";
	} elseif (strpos($url, "pinterest") > 0) {
		$typeName = "pinterest";
	} elseif (strpos($url, "instagram") > 0) {
		$typeName = "instagram";
	} 
	
	$sql = "SELECT id FROM fb_post_types WHERE name=:typeName";
	
	try {
		$db = getConnection();
		$stmt = $db->prepare($sql);
		$stmt->bindParam("typeName", $typeName);
		$stmt->execute();
		$typeID = $stmt->fetchColumn();
		
		$db = null;
		
		return $typeID;
		
	} catch (PDOException $e) {
		create_log($e->getMessage());
	}
	
	return $typeID;
}

function addCategories($catId, $catName) {
	$selectsql = "SELECT id FROM at_categories WHERE id=:catID";
	$updatesql = "UPDATE at_categories SET Name=:name WHERE id=:catID";
	$addsql = "INSERT INTO at_categories VALUES (:id,:name)";
	try {
		$db = getConnection();
		$stmt = $db->prepare($selectsql);
		$stmt->bindParam("catID", $catId);
		$stmt->execute();
		$ID = $stmt->fetchColumn();
		if ($ID == $catId) {
			if ($catName != "") {
				$stmt = $db->prepare($updatesql);
				$stmt->bindParam("name", $catName);
				$stmt->bindParam("catID", $catId);
				$stmt->execute();
			}
		} else {
			$stmt1 = $db->prepare($addsql);
			$stmt1->bindParam("id", $catId);
			$stmt1->bindParam("name", $catName);
			$stmt1->execute();
		}
		$db = null;
	} catch (PDOException $e) {
		echo $e->getMessage();
	}
}

function addLikes($post_id, $user_id) {
	$getpostid = "SELECT post_id FROM fb_posts WHERE post_message_id=:postid";
	$getsql = "SELECT COUNT(*) FROM fb_likes WHERE fb_postid=:postID AND fb_userid=:userID";
	$addsql = "INSERT INTO fb_likes VALUES(:postID, :userID)";
	$exists = null;
	try {
		$db = getConnection();
		$stmt = $db->prepare($getpostid);
		$stmt->bindParam("postid", $post_id);
		$stmt->execute();
		$postid = $stmt->fetchColumn();
		$stmt = $db->prepare($getsql);
		$stmt->bindParam("postID", $postid);
		$stmt->bindParam("userID", $user_id);
		$stmt->execute();
		$exists = $stmt->fetchColumn();
		if ($exists > 0) {
			//This like is already recorded in the db
		} else {
			$stmt = $db->prepare($addsql);
			$stmt->bindParam("postID", $postid);
			$stmt->bindParam("userID", $user_id);
			$stmt->execute();
		}
		
	} catch (PDOException $e) {
		create_log($e->getMessage());
	}
}

function addComments($v_id, $com_id, $com_message, $com_likecount, $com_createdtime, $paramUid) {
	$getpostid = "SELECT post_id FROM fb_posts WHERE post_message_id=:postid";
	$addsql = "INSERT INTO fb_comments VALUES (:id, :com_id, :com_text, :com_like, :com_time, :fb_userid)";
	$getfbidsql = "SELECT count(*) FROM fb_comments WHERE comment_id=:id";
	$updatesql = "UPDATE fb_comments SET comment_likes=:likes WHERE comment_id =:comid";

	try {
		$db = getConnection();
		$stmt = $db->prepare($getpostid);
		$stmt->bindParam("postid", $v_id);
		$stmt->execute();
		$postid = $stmt->fetchColumn();
		$com_message = $com_message;

		$stmt = $db->prepare($getfbidsql);
		$stmt->bindParam("id", $com_id);
		$stmt->execute();
		$count = $stmt->fetchColumn();
		if ($count == 0) {
			$stmt1 = $db->prepare($addsql);
			$stmt1->bindParam("id", $postid);
			$stmt1->bindParam("fb_userid", $paramUid);
			$stmt1->bindParam("com_id", $com_id);
			$stmt1->bindParam("com_text", $com_message);
			$stmt1->bindParam("com_like", $com_likecount);
			$stmt1->bindParam("com_time", $com_createdtime);
			$stmt1->execute();
		} else {
			$stmt = $db->prepare($updatesql);
			$stmt->bindParam("comid", $com_id);
			$stmt->bindParam("likes", $com_likecount);
			$stmt->execute();
		}
		$db = null;
	} catch (PDOException $e) {
		echo $e->getMessage();
	}
}

function addPosts($createdtime, $msgid, $msg, $img_url, $post_ann_url, $type, $likes, $shares, $iden ,$display) {
	$selectsql = "SELECT post_message_id FROM fb_posts WHERE post_message_id =:id";
	$addsql = "INSERT INTO fb_posts(post_createdtime,post_message_id,post_message,post_img_url,post_ann_url,post_type,post_likes,post_shares, atID, display_post) VALUES (:post_createdtime,:post_message_id,:post_message,:post_img_url,:post_ann_url,:post_type,:post_likes,:post_share, :atid, :dis)";
	$updatesql = "UPDATE fb_posts SET post_likes=:likes,post_shares=:shares WHERE post_message_id =:id";
	try {

		$db = getConnection();
		$stmt = $db->prepare($selectsql);
		$stmt->bindParam("id", $msgid);
		$stmt->execute();
		$ID = $stmt->fetchColumn();

		if ($ID == $msgid) {
			$stmt = $db->prepare($updatesql);
			$stmt->bindParam("id", $msgid);
			$stmt->bindParam("likes", $likes);
			$stmt->bindParam("shares", $shares);
			$stmt->execute();
		} else {
			$stmt1 = $db->prepare($addsql);
			$stmt1->bindParam("post_createdtime", $createdtime);
			$stmt1->bindParam("post_message_id", $msgid);
			$stmt1->bindParam("post_message", $msg);
			$stmt1->bindParam("post_img_url", $img_url);
			$stmt1->bindParam("post_ann_url", $post_ann_url);
			$stmt1->bindParam("post_type", $type);
			$stmt1->bindParam("post_likes", $likes);
			$stmt1->bindParam("post_share", $shares);
			$stmt1->bindParam("atid", $iden);
			$stmt1->bindParam("dis", $display);
			$stmt1->execute();
		}
		$db = null;
	} catch (PDOException $e) {
		echo $e->getMessage();
	}
}



function fetchUserDetails($pId, $add, $facebook) {
	$fb_user = $facebook->api('/' . $pId, 'GET');
	$fb_fullname = "";
	$fb_name = "";
	$fb_locale = "";
	$fb_gender = "";
	$fb_birthday = "";
	$fb_email = "";
	$fb_location = "";
	$fb_pic = "";
	$fb_website = "";
	$fb_work = "";

	if (isset($fb_user['name'])) {
		$fb_fullname = $fb_user['name'];
	}
	if (isset($fb_user['username'])) {
		$fb_name = $fb_user['username'];
	}
	if (isset($fb_user['locale'])) {
		$fb_locale = $fb_user['locale'];
	}
	if (isset($fb_user['gender'])) {
		$fb_gender = $fb_user['gender'];
	}
	if (isset($fb_user['birthday'])) {
		$fb_birthday = $fb_user['birthday'];
	}
	if (isset($fb_user['email'])) {
		$fb_email = $fb_user['email'];
	}
	if (isset($fb_user['location'])) {
		$fb_location = $fb_user['location'];
	}
	if (isset($fb_user['picture'])) {
		$fb_pic = $fb_user['picture'];
	}
	if (isset($fb_user['website'])) {
		$fb_website = $fb_user['website'];
	}
	if (isset($fb_user['work'])) {
		$fb_work = $fb_user['work'];
	}
	create_log("fetch complete....");
	if ($add) {
		create_log("adding user....");
		addUserDetails($pId, $fb_fullname, $fb_name, $fb_gender, $fb_locale, $fb_birthday, $fb_location, $fb_pic, $fb_website, $fb_work, $fb_email);
	} else {
		create_log("updating user....");
		updateUserDetails($pId, $fb_fullname, $fb_name, $fb_gender, $fb_locale, $fb_birthday, $fb_location, $fb_pic, $fb_website, $fb_work, $fb_email);
	}
}

function getDbUserID($pId, $pName, $facebook) {

	$uidSelectQuery = "SELECT user_id FROM fb_user where fb_userid = " . $pId;
	$theDBUserID = null;
	
	try {
		$db = getConnection();
		
		$stmt = $db->query($uidSelectQuery);
		$theDBUserID = "";
		$theDBUserID = $stmt->fetchColumn();
		$add = FALSE;
		if ($theDBUserID != "") {
			// Update User Details
			//fetchUserDetails($pId, $add, $facebook);
		} else {
			// Add User Details
			$add = TRUE;
			//fetchUserDetails($pId, $add, $facebook);
			$addsql = "INSERT INTO `fb_user` (`fb_userid`, `user_fullname`) VALUES (:pid,:pname)";
			
			try {
				$db = getConnection();
				$stmt1 = $db->prepare($addsql);
				$stmt1->bindParam("pid",$pId);
				$stmt1->bindParam("pname",$pName);
				$stmt1->execute();
				$stmt = $db->query($uidSelectQuery);
				$theDBUserID = "";
				$theDBUserID = $stmt->fetchColumn();
			} catch (PDOException $e) {
				create_log($e->getMessage());
			}
		}
		return $theDBUserID;
		
	} catch(PDOException $e) {
		create_log($e->getMessage());
	}
	
	return $theDBUserID;
}

function fetchLikeDetails($postID, $likeCount, $facebook) {
	$like_id = "";
	$like_name = "";
	$user_id = null;

	$count = 0;
	$next = true;
	$url = "/" . $postID . "/likes";

	while($next) {
		$fb_likesdata = $facebook->api($url,'GET');
		
		if (isset($fb_likesdata['data'])) {
			$fb_likes = $fb_likesdata['data'];
			foreach($fb_likes as $fb_like) {
				if (isset($fb_like['name'])) {
					$like_name = $fb_like['name'];
					create_log($like_name . "\r\n");
				}
				if (isset($fb_like['id'])) {
					$like_id = $fb_like['id'];
					$user_id = getDbUserID($like_id, $like_name, $facebook);
				}
				if (isset($user_id)) {
					addLikes($postID, $user_id);
				}
				$count = $count + 1;
				if($count == $likeCount) {
					$next = false;
					break;
				}
			}
			if (isset($fb_likesdata['paging']['next'])) {
				$url = $fb_likesdata['paging']['next'];
				$pos = strpos($url, "graph.facebook.com");
				$url = substr($url, strpos($url, "/", $pos));
				create_log("next url: " . $url . "\r\n");
			} else {
				$next = false;
			}
		}
	}
}

function fetchCommentDetails($postID, $commentCount, $facebook) {
	$fb_commentsdata = null;
	$fb_comments = null;
	$comment_id = "";
	$comment_text = "";
	$comment_likes = "";
	$comment_createdtime = "";
	$comment_fromname = "";
	$comment_fromid = "";
	$user_id = null;
	
	$count = 0;
	$next = true;
	$url = "/" . $postID . "/comments";
	while($next) {
		$fb_commentsdata = $facebook->api($url,'GET');
		
		if (isset($fb_commentsdata['data'])) {
			$fb_comments = $fb_commentsdata['data'];
			
			foreach($fb_comments as $fb_comment) {
				if (isset($fb_comment['id'])) {
					$comment_id = $fb_comment['id'];
				}
				if (isset($fb_comment['message'])) {
					$comment_text = $fb_comment['message'];
				}
				if (isset($fb_comment['like_count'])) {
					$comment_likes = $fb_comment['like_count'];
				}
				if (isset($fb_comment['created_time'])) {
					$comment_createdtime = $fb_comment['created_time'];
				}
				if (isset($fb_comment['from']['name'])) {
					$comment_fromname = $fb_comment['from']['name'];
					create_log($comment_fromname . "\r\n");
				}
				if (isset($fb_comment['from']['id'])) {
					$comment_fromid = $fb_comment['from']['id'];
					$user_id = getDbUserID($comment_fromid, $comment_fromname, $facebook);
				}
				if (isset($user_id)) {
					addComments($postID, $comment_id, $comment_text, $comment_likes, $comment_createdtime, $user_id);
				}
				$count = $count + 1;
				if($count == $commentCount) {
					$next = false;
					break;
				}
			}
		}		
		if (isset($fb_commentsdata['paging']['next'])) {
			$url = $fb_commentsdata['paging']['next'];
			$pos = strpos($url, "graph.facebook.com");
			$url = substr($url, strpos($url, "/", $pos));
		} else {
			$next = false;
		}
	}
	
	return $count;
}

function getAnnFBPosts($geturl) {
	
	create_log("Fetching FB Posts ... \n");
	
	//AppID and AppSecret Pair
	$config = array(
			'appId' => '277392959071196',
			'secret' => '00bb366f3dd601e204ccb1360587770f'
	);
	
	$facebook = new Facebook($config);
	$app_token = getAccessToken($facebook);

	$facebook->setAccessToken($app_token);

	try {
		$user_profile = $facebook->api($geturl, 'GET');
		// Check whether fb api response has any error
		if (isset($user_profile['error_msg'])) {
			create_log("Unable to fetch FB Posts.Code Exit. \n");
			var_dump($user_profile);
			exit;
		}

		$user_data = $user_profile['data'];
		$cnt = count($user_data);
		
		create_log("Total Post count : " . $cnt . " on this page\n");
		
		$postCounter = 0;
		
		for ($i = 0; $i < $cnt; $i++) {
			$firstdata = $user_data[$i];
			if (isset($firstdata['id'])) {
				$v_id = $firstdata['id'];
				create_log($v_id . "\r\n");
			}

			if (isset($firstdata['shares'])) {
				$fd = $firstdata['shares'];
				$v_shares = $fd['count'];
			} else {
				$v_shares = 0;
			}
			if (isset($firstdata['likes'])) {
				$fl = $firstdata['likes'];
				$v_likes = $fl['count'];
				create_log("likes: " . $v_likes . "\r\n");
			} else {
				$v_likes = 0;
			}
			create_log($firstdata['message'] . "\r\n");

			if (isset($firstdata['message'])) {
				create_log($firstdata['message'] . "\r\n");
				$v_message = $firstdata['message'];
				$split = explode("http", $v_message);
				$v_msg = $split[0];
				$match = preg_match('/[a-zA-Z]+:\/\/[0-9a-zA-Z;.\/?:@=_#&%~,+$]+/', $v_message, $matches);
				if ($match > 0) {
					$v_ann_url = $matches[0];
					$longURL = traceUrl($v_ann_url, 4);
					$longURL = substr($longURL, strpos($longURL, "http"));
					$v_type = getPostType($longURL);
					$iden = "";
					$display= 0;
					if (stristr($longURL, 'anntaylor.com')) {
						$split = explode("?", $longURL);
						$pieces = explode("/", $split[0]);
						$count = count($pieces);
						$id = explode(";", $pieces[$count - 1]);
						$iden = $id[0];
						$prodName = $pieces[$count - 2];
						if ($iden == "searchResults.jsp" || $iden == "editorial.jsp") {
							$iden = "";
						} elseif ($iden == "outfit.jsp" || $iden == "category.jsp"|| $iden == "categorySaleViewAll.jsp") {
							if ($iden == "outfit.jsp") {
								$string = "outfitId";
								$prodName = "outfit";
							}
							else
								$string = "catid";

							$catpos = strpos($split[1], $string);
							$pos1 = strpos($split[1], "=", $catpos);
							$pos2 = strpos($split[1], "&", $catpos);

							$iden = substr($split[1], $pos1 + 1, $pos2 - ($pos1 + 1));
							$httppos = strpos($longURL, "http");
							$url = substr($longURL, $httppos);

							addCategories($iden, $prodName);
							if ($string == "outfitId") {
								scrapeOutfit($url, $iden);
							} else {
								scrapeCat($url, $iden);
							}
						} else {
							if ($iden != "" && strpos($iden, "cat") === FALSE) {
								$catpos = strpos($split[1], "catid");
								$pos1 = strpos($split[1], "=", $catpos);
								$pos2 = strpos($split[1], "&", $catpos);

								$catid = substr($split[1], $pos1 + 1, $pos2 - ($pos1 + 1));
								$catname = "";
								
								addCategories($catid, $catname);
								
								scrapeReview($iden, $prodName, $catid);
							} elseif ($iden != "") {
								addCategories($iden, $prodName);
								$httppos = strpos($longURL, "http");
								$url = substr($longURL, $httppos);
								scrapeCat($url, $iden);
							}
						}
					}
				} else
					$v_ann_url = " ";
			} else {
				create_log("No message!! :O\r\n");
			}
			if (isset($firstdata['picture'])) {
				$v_picture = $firstdata['picture'];
			}
			if (isset($firstdata['created_time'])) {
				$v_createdtime = $firstdata['created_time'];
			}

			if ($iden == "")
				$display = 0;
			else {
				if(strpos($iden, "cat") === FALSE)
					$display = 1;
				else
					$display = getDisplay($iden);
			}
			
			// Add the FB Posts to database
			addPosts($v_createdtime, $v_id, $v_msg, $v_picture, $v_ann_url, $v_type, $v_likes, $v_shares, $iden, $display);
			
			// Fetch like details
			$likeCount = 500;
			fetchLikeDetails($v_id,$likeCount,$facebook);

			// Fetch comment details 
			if (isset($firstdata['comments'])) {
				$v_comments = $firstdata['comments'];
				$cmntData = $v_comments['data'];
				$v_commentCount = count($cmntData);
				
				if ($v_commentCount > 0) {
					$commentCount = -1;
					$v_commentCount = fetchCommentDetails($v_id, $commentCount, $facebook);
				}
			} else {
				$v_commentCount = 0;
			}
		}
	} catch (FacebookApiException $e) {
		$login_url = $facebook->getLoginUrl();
		echo 'Please <a href="' . $login_url . '">login.</a>';
		error_log($e->getType());
		error_log($e->getMessage());
	}
	$paging = $user_profile['paging'];
	$next = $paging['next'];
	if (isset($next)) {
		$pos = strpos($next, "graph.facebook.com");
		$next = substr($next, strpos($next, "/", $pos));
		return $next;
	} else {
		return "";
	}
	return "";
}
?>
