<?php

include_once 'fetchFBdata.php';
set_time_limit(3000);

function main() {
	$next = '/AnnTaylor/posts';
	$count = 0;
	while ($next != "") {
		$next = getAnnFBPosts($next);
		$count++;
		if ($count == -1) {
			break;
		}
	}
}

main();

?>
