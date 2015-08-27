<?php
header("Content-type:text/html;charset=utf-8");
require_once("config.php");
require_once("api.php");
$api = new cardApi($config);

//$api->bind_device_by_qrcode("201520152015","J65011");
//2015201520155D324367938AC9BCF93AE9BA6C19D0FD

//$api->oprate_device("J65011","OPEN");echo "sadf";

//$api->read_device_status("201520152015",'J65011');

//$api->login("201520152015", "111111");//{("000020300032","J65011");
//exit;20152705012420F7663B6DFDCEEC34C9B6656BB1E60A
http_post_json('{
  "password" : "111111",
  "stu_no" : "201520152015",
  "t" : "1439825959",
  "n" : "f0bdff",
  "sign" : "a606396017e1fb72c3f7ff004e81cbda9c3f7a05",
  "action" : "unbindDevice",
  "token":"70a1/NVf4ek2zGcjIqhZiGxPCqBCwnGXSLDpFhQZo8MpFNwy2fbO1p3DqBkLmFGdHXqInFAunVNegaYN0vqnQDeu2Ee+hwYQUujiaFHMXjKPLTvFpSzgGpveor2YtS3d9A",
  "qrcode" : "H3A112"
}');
/*http_post_json('{
  "stu_no" : "201520152015",
  "token" : "3a8bBpTxJifllJm+uj9I3omQb42wgGuXVZ2AZnfJIciQjMg6GUgtfcT84gacgDssBk9UQS3cB+d8OIx49uyNd3pOpqv2Ko3HeIYkGLTFlgEZSg2rZg5Q1m8KFnn20xneVQ",
  "device_id":"J61051",
  "time" :20,
  "delay_open":0,
  "delay_close":0,
  "t" : "1439825959",
  "n" : "f0bdff",
  "sign" : "a606396017e1fb72c3f7ff004e81cbda9c3f7a05",
  "action" : "closeShower"
}');*/
/*http_post_json('{
  "password" : "111111",
  "token":"000020300032439534BE304B925FD4089D69116C5205",
  "stu_no" : "201527050124",
  "page_index":1,
  "page_szie":10,
  "begin_date":"20150101000000",
  "end_date":"20150801000000",
  "t" : "1439825959",
  "n" : "f0bdff",
  "sign" : "a606396017e1fb72c3f7ff004e81cbda9c3f7a05",
  "action" : "getCardTransaction"
}');


*/

//$api->read_device_status("",'J65011');
function http_post_json($jsonStr){
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_URL, "http://218.6.163.88:50000/card/test/service.php");
  curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Content-Type: application/json; charset=utf-8',
      'Content-Length: ' . strlen($jsonStr),
	  "resTime"=>time()
    )
  );
  $response = curl_exec($ch);
  $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $request_result = array($httpCode, $response);print_r($request_result);
  //$result = $this->parse_card_data($request_result);
  return $result;
}

?>