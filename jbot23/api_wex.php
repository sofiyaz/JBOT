<?php
$version = "v2.3";

if(isset($_POST['method'])) { $method = $_POST['method']; } elseif(isset($_GET['method'])) { $method = $_GET['method']; } else { $method = ''; }
$method = htmlspecialchars(strip_tags(trim($method)));

//---------------------------- get Version

if ($method == 'ver') {
	echo $version;
	exit;
}

//---------------------------- get Chart

if ($method == 'getChart') {

	if(isset($_POST['pair'])) { $pair = $_POST['pair']; } elseif(isset($_GET['pair'])) { $pair = $_GET['pair']; } else { $pair = 0; }
	$pair = htmlspecialchars(strip_tags(trim($pair)));

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
	$fcontents = str_replace(" ", "", "$fcontents");
	$fcontents = str_replace("[", "", "$fcontents");
	$fcontents = str_replace("]", "", "$fcontents");
	$fcontents .= ",";

	$fcontents = explode (',', $fcontents);

	$i=0;
	$j=0;
	$volumechart="";

	$chart = "<html>
<head>
<title>Chart</title>
<style>
body, html {
    height:100%;
    margin:0;
    padding:0;
}
</style>
</head>
<bodycellspacing='0' cellpadding='0'>
<script type=\"text/javascript\" src=\"https://www.gstatic.com/charts/loader.js\"></script>
    <script type=\"text/javascript\">
      google.charts.load('current', {'packages':['corechart']});
      google.charts.setOnLoadCallback(drawChart);
	  google.charts.setOnLoadCallback(drawChartVolume);

  function drawChart() {
    var data = google.visualization.arrayToDataTable([";

	while ($fcontents[$i]) {
		$tcandle[$j] = $fcontents[$i];
		$i++;
		$lowcandle[$j] = $fcontents[$i];
		$i++;
		$opencandle[$j] = $fcontents[$i];
		$i++;
		$closecandle[$j] = $fcontents[$i];
		$i++;
		$highcandle[$j] = $fcontents[$i];
		$i++;
		$volumecandle[$j] = $fcontents[$i];
		$i++;
		$chart .= "[".$tcandle[$j].",".$lowcandle[$j].",".$opencandle[$j].",".$closecandle[$j].",".$highcandle[$j]."],";
		$volumechart .= "[".$tcandle[$j].",".$volumecandle[$j]."],";
		$j++;
	}

	$chart = substr ($chart, 0, -1);
	$volumechart = substr ($volumechart, 0, -1);

	$chart .= "    ], true);

    var options = {
      chartArea:{
		    left: 50,
		    top: 10,
		    width: 630,
		    height: 230
		  },
      legend:'none',
      colors: ['#515151', '#515151'],
      backgroundColor: {fill: '#222222', stroke: '#333333' },
      candlestick: {
            fallingColor: { strokeWidth: 0, fill: '#008000' },
            risingColor: { strokeWidth: 0, fill: '#ff0000' }
          },
      hAxis: {
	        textStyle: {color: '#666666', fontSize: 12},
	        slantedTextAngle: 90
	      },
	  vAxis: {
	        gridlines: {color: '#333333'},
	        textStyle: {color: '#666666', fontSize: 12}
	      },
	  series: {0: {type: 'candlesticks'}, 1: {type: 'bars', targetAxisIndex:1, color:'#ebebeb'}}

    };

    var chart = new google.visualization.CandlestickChart(document.getElementById('chart_div'));

    chart.draw(data, options);
  }

  function drawChartVolume() {
    var data = google.visualization.arrayToDataTable([
    $volumechart
        ], true);

    var options = {
      chartArea:{
		    left: 50,
		    top: 10,
		    width: 630,
		    height: 130
		  },
	  hAxis: {
	        textStyle: {color: '#666666', fontSize: 12},
	        slantedTextAngle: 90
	      },
	  vAxis: {
	        gridlines: {color: '#333333'},
	        textStyle: {color: '#666666', fontSize: 12}

	      },
      legend:'none',
      colors: ['#515151', '#515151'],
      backgroundColor: {fill: '#222222', stroke: '#333333' }

    };
      var chart = new google.visualization.ColumnChart(document.getElementById('chart_div_volume'));

      chart.draw(data, options);

  }
</script>
<div id=\"chart_div\" style=\"width: 700px; height: 300px;\"></div>
<div id=\"chart_div_volume\" style=\"width: 700px; height: 195px;\"></div>
</body></html>";

	echo $chart;
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
	$result = btce_query($method, array("pair" => "$pair"));

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
		$i = $i+3;
		$high[$j] = $fcontents[$i];
		$i = $i+2;
		$j++;
	}

	$imax = $j;

	if ($strategy == 0) {
		for ($i=0;$i<$imax;$i++){
			$low24[$i] = $low[$i];
			$high24[$i] = $high[$i];
		}
		$buyorder = min($low24);
		$sellorder = max($high24);
	}

	if ($strategy == 1) {
		for ($i=24;$i<$imax;$i++){
			$low12[$i] = $low[$i];
			$high12[$i] = $high[$i];
		}
		$buyorder = min($low12);
		$sellorder = max($high12);
	}

	if ($strategy == 2) {
		for ($i=36;$i<$imax;$i++){
			$low6[$i] = $low[$i];
			$high6[$i] = $high[$i];
		}
		$buyorder = min($low6);
		$sellorder = max($high6);
	}

	if ($strategy == 3) {
		for ($i=45;$i<$imax;$i++){
			$low2[$i] = $low[$i];
			$high2[$i] = $high[$i];
		}
		$buyorder = min($low2);
		$sellorder = max($high2);
	}

	if ($strategy == 4) {
		for ($i=0;$i<$imax;$i++){
			$low24[$i] = $low[$i];
			$high24[$i] = $high[$i];
		}
		for ($i=45;$i<$imax;$i++){
			$low2[$i] = $low[$i];
			$high2[$i] = $high[$i];
		}

		$maxhigh[2] = max($high2);
		$maxhigh[24] = max($high24);
		$minlow[2] = min($low2);
		$minlow[24] = min($low24);

		if (($maxhigh[2]/$maxhigh[24]) >= 0.85) {
			$lowFix = (min($low2) + min($low24))/2;
			$highFix = max($high2) + ($lowFix-min($low24));
		} elseif (($minlow[24]/$minlow[2]) >= 0.85) {
			$highFix = (max($high2) + max($high24))/2;
			$lowFix = min($low24) - (max($high24)-$highFix);
		} else {
			$highFix = $maxhigh[24];
			$lowFix = $minlow[24];
		}

		$buyorder = $lowFix;
		$sellorder = $highFix;
	}

echo "{\"buyprice\":" . $buyorder . ", \"sellprice\":" . $sellorder . ", \"success\": 1}";

}

?>