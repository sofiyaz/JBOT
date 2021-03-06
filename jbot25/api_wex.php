<?php
$version = "v2.5";

if(isset($_POST['method'])) { $method = $_POST['method']; } elseif(isset($_GET['method'])) { $method = $_GET['method']; } else { $method = ''; }
$method = htmlspecialchars(strip_tags(trim($method)));

//---------------------------- get Version

if ($method == 'ver') {
	echo $version;
	exit;
}

//---------------------------- get Rules

if ($method == 'getRules') {

	$link = "https://wex.nz/api/3/info";

	$fcontents = implode ('', file ($link));

	echo $fcontents;
	exit;
}

//---------------------------- QUERY

function btce_query($method, array $req = array()) {

	if(isset($_POST['key'])) { $key = $_POST['key']; } elseif(isset($_GET['key'])) { $key = $_GET['key']; } else { $key = 0; }
	if(isset($_POST['secret'])) { $secret = $_POST['secret']; } elseif(isset($_GET['secret'])) { $secret = $_GET['secret']; } else { $secret = 0; }

	$key = htmlspecialchars(strip_tags(trim($key)));
	$secret = htmlspecialchars(strip_tags(trim($secret)));

	$req['method'] = $method;
	$mt = explode(' ', microtime());
	$req['nonce'] = $mt[1];

	$post_data = http_build_query($req, '', '&');
    $sign = hash_hmac("sha512", $post_data, $secret);
	$headers = array(
		'Sign: '.$sign,
		'Key: '.$key,
	);

	$ch = null;
	if (is_null($ch)) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; BTCE PHP client; '.php_uname('s').'; PHP/'.phpversion().')');
	}
	curl_setopt($ch, CURLOPT_URL, 'https://wex.nz/tapi');
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

	$res = curl_exec($ch);

	if ($res === false) throw new Exception('Could not get reply: '.curl_error($ch));
	$dec = json_decode($res, true);
	if (!$dec) throw new Exception('Invalid data received, please make sure connection is working and requested API exists');
	return $dec;
}

//---------------------------- get Info

if ($method == 'getInfo'){

	$result = btce_query($method);
	echo json_encode($result);

}

//---------------------------- get History

if ($method == 'TradeHistory'){

	if(isset($_POST['pair'])) { $pair = $_POST['pair']; } elseif(isset($_GET['pair'])) { $pair = $_GET['pair']; } else { $pair = 0; }
	if(isset($_POST['since'])) { $since = $_POST['since']; } elseif(isset($_GET['since'])) { $since = $_GET['since']; } else { $since = 0; }
	$result = btce_query($method, array("pair" => "$pair", "since" => $since));
	echo json_encode($result);

}

//---------------------------- get Active Orders

if ($method == 'ActiveOrders') {

	if(isset($_POST['pair'])) { $pair = $_POST['pair']; } elseif(isset($_GET['pair'])) { $pair = $_GET['pair']; } else { $pair = 0; }
	$result = btce_query($method, array("pair" => "$pair"));
	echo json_encode($result);


}

//---------------------------- cancel Order

if ($method == 'CancelOrder') {

	if(isset($_POST['order_id'])) { $order_id = $_POST['order_id']; } elseif(isset($_GET['order_id'])) { $order_id = $_GET['order_id']; } else { $order_id = 0; }
	$result = btce_query($method, array("order_id" => "$order_id"));
	echo json_encode($result);
}

//---------------------------- TRADE

if ($method == 'Trade') {

	if(isset($_POST['pair'])) { $pair = $_POST['pair']; } elseif(isset($_GET['pair'])) { $pair = $_GET['pair']; } else { $pair = 0; }
	if(isset($_POST['type'])) { $type = $_POST['type']; } elseif(isset($_GET['type'])) { $type = $_GET['type']; } else { $type = 0; }
	if(isset($_POST['amount'])) { $amount = $_POST['amount']; } elseif(isset($_GET['amount'])) { $amount = $_GET['amount']; } else { $amount = 0; }
	if(isset($_POST['rate'])) { $rate = $_POST['rate']; } elseif(isset($_GET['rate'])) { $rate = $_GET['rate']; } else { $rate = 0; }
	$result = btce_query("Trade", array("pair" => "$pair", "type" => "$type", "amount" => $amount, "rate" => $rate));
	echo json_encode($result);

}

//---------------------------- get Prices

if ($method == 'getView') {

	if(isset($_POST['pair'])) { $pair = $_POST['pair']; } elseif(isset($_GET['pair'])) { $pair = $_GET['pair']; } else { $pair = 0; }
	$pair = htmlspecialchars(strip_tags(trim($pair)));

	if(isset($_POST['strategy'])) { $strategy = $_POST['strategy']; } elseif(isset($_GET['strategy'])) { $strategy = $_GET['strategy']; } else { $strategy = 0; }
	$strategy = htmlspecialchars(strip_tags(trim($strategy)));

	$link = "https://wex.nz/exchange/$pair?old_charts=1";

	$fcontents = implode ('', file ($link));
	$fcontents = stristr ($fcontents, 'pairs');
	$fcontents = stristr ($fcontents, 'clear');
	$fcontents = stristr ($fcontents, '<script');
	$pos = strpos($fcontents, '<div id');
	$fcontents = substr ($fcontents, 0, $pos);
	$fcontents = stristr ($fcontents, '[[');
	$pos = strpos($fcontents, ']]');
	$fcontents = substr ($fcontents, 0, $pos);
	$fcontents .= ",";

	$fcontents = explode (',', $fcontents);

	$i=0;
	$j=0;



while ($fcontents[$i]) {
	$i++;
	$low[$j] = $fcontents[$i];
	$i++;
	$close[$j] = $fcontents[$i];
	$i++;
	$open[$j] = $fcontents[$i];
	$i++;
	$high[$j] = $fcontents[$i];
	$i = $i+2;
	$j++;
}

$lastprice = end($close);

$imax = $j;
$i=0;
$j=0;

$highsqueeze = max($high);
$lowsqueeze = min($low);
$rangeprice = max(max($open),max($close)) - min(min($open),min($close));


	if ($strategy == 0) {
		for ($i=0;$i<$imax;$i++){
			$body24[$j] = $open[$i];
			$j++;
			$body24[$j] = $close[$i];
			$j++;
		}
		$buyorder = min($body24);
		$sellorder = max($body24);
	}

	if ($strategy == 1) {
		$istart = (int)($imax*0.5);
		for ($i=$istart;$i<$imax;$i++){
			$body12[$j] = $open[$i];
			$j++;
			$body12[$j] = $close[$i];
			$j++;
		}
		$buyorder = min($body12);
		$sellorder = max($body12);
	}

	if ($strategy == 2) {
		$istart = (int)($imax*0.75);
		for ($i=$istart;$i<$imax;$i++){
			$body6[$j] = $open[$i];
			$j++;
			$body6[$j] = $close[$i];
			$j++;
		}
		$buyorder = min($body6);
		$sellorder = max($body6);
	}

	if ($strategy == 3) {
	$istart = $imax-3;
		for ($i=$istart;$i<$imax;$i++){
			$body2[$j] = $open[$i];
			$j++;
			$body2[$j] = $close[$i];
			$j++;
		}
		$buyorder = min($body2);
		$sellorder = max($body2);
	}

	if ($strategy == 4) {
		$istart = $imax-3;
		for ($i=0;$i<$imax;$i++){
			$body24[$j] = $open[$i];
			$j++;
			$body24[$j] = $close[$i];
			$j++;
		}
		for ($i=$istart;$i<$imax;$i++){
			$body2[$j] = $open[$i];
			$j++;
			$body2[$j] = $close[$i];
			$j++;
		}

		$maxhigh[2] = max($body2);
		$maxhigh[24] = max($body24);
		$minlow[2] = min($body2);
		$minlow[24] = min($body24);
		$spread24 = $maxhigh[24]-$minlow[24];
		$deltaHigh = $maxhigh[24]-$maxhigh[2];
		$deltaLow = $minlow[2]-$minlow[24];

		if (($deltaHigh/$spread24) <= 0.15) {
			$lowFix = (min($body2) + min($body24))/2;
			$highFix = max($body2) + ($lowFix-min($body24));
		} elseif (($deltaLow/$spread24) <= 0.15) {
			$highFix = (max($body2) + max($body24))/2;
			$lowFix = min($body24) - (max($body24)-$highFix);
		} else {
			$highFix = $maxhigh[24];
			$lowFix = $minlow[24];
		}

		$buyorder = $lowFix;
		$sellorder = $highFix;
	}

echo "{\"buyprice\":" . $buyorder . ", \"sellprice\":" . $sellorder . ", \"highsqueeze\":" . $highsqueeze . ", \"lowsqueeze\":" . $lowsqueeze . ", \"lastprice\":" . $lastprice . ", \"rangeprice\":" . $rangeprice . ", \"success\": 1}";

}

?>