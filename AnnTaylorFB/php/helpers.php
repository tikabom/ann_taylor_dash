<?php

function getHTML($url) {
	$curl = curl_init();

	$header[0] = "Accept: text/xml,application/xml,application/json,application/xhtml+xml,";
	$header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
	$header[] = "Cache-Control: max-age=0";
	$header[] = "Connection: keep-alive";
	$header[] = "Keep-Alive: 100000";
	$header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
	$header[] = "Accept-Language: en-us,en;q=0.5";
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; U; Linux i686; pl-PL; rv:1.9.0.2) Gecko/2008092313 Ubuntu/9.25 (jaunty) Firefox/3.8');
	curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
	curl_setopt($curl, CURLOPT_HEADER, 0);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($curl, CURLOPT_TIMEOUT, 100);
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 100);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

	$html = curl_exec($curl);
	curl_close($curl);

	return $html;
}

function traceUrl($url, $hops) {
	if ($hops == 5) {
		throw new Exception('TOO_MANY_HOPS');
	}
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_NOBODY, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	$r = curl_exec($ch);

	if (preg_match('/Location: (?P<url>.*)/i', $r, $match)) {
		return $match[0];
	}
	return $url;
}

?>