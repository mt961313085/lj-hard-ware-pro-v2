<?php

	require_once( 'config.php' );
	require_once( 'api.php' );
		
	$api = new cardApi( $config );
	
	
	$str = '{
		  "action" : "getDeviceList",
		  "stu_no" : "201521040196",
		  "n" : "85ae8f",
		  "sign" : "c5f2e02331b878eb076156e1516ac2c5c0995cfb",
		  "token" : "201521040196AE22A25AE01824BD061C423436DF3089",
		  "t" : "1440482594"
		}';
	
	
	//$post["token"] = '201521040196AE22A25AE01824BD061C423436DF3089';
	//$authcode = $api->authcode( $post["token"], 'HX_DECODE', $config['hx_auth_key'] );
	//echo "$authcode\r\n";
		
	//token的组合规则 stu_no|>|password|>|token
	//$auth = explode("|>|", $authcode);
	//$stu_no = $auth[0];
	//$password = $auth[1];
	//$token = $auth[2];
	
	
	http_post_json( $str );
	
/*
	http_post_json( '{
						"password" : "111111",
						"stu_no" : "201520152015",
						"t" : "1439825959",
						"n" : "f0bdff",
						"sign" : "a606396017e1fb72c3f7ff004e81cbda9c3f7a05",
						"action" : "login"
					}' );


	$str = '{
			  "action" : "getVersionInfo",
			  "stu_no" : "201521040196",
			  "n" : "47fc7a",
			  "sign" : "3d5f93f13ffe1d1f7cbda266906b83806e96bb1b",
			  "token" : "2015210401964035FDA0E6147C304B021C4CE9283B00",
			  "t" : "1440478013"
			}';
	
	$str = str_replace("\\\"", "'", $str);
	$post = json_decode( $str, true );
	$api->interface_valid( $post );

	$action = $post["action"];
	$post["token"] = str_replace(" ","+",$post["token"]);
	
	//authcode 的 加密 在 login方法里面，验证了一卡通的账号有效性后实现
	$authcode = authcode( $post["token"], 'HX_DECODE', $config['hx_auth_key'] );
	
	echo "$authcode\r\n";
		
	//token的组合规则 stu_no|>|password|>|token
	//$auth = explode("|>|", $authcode);
	//$stu_no = $auth[0];
	//$password = $auth[1];
	//$token = $auth[2];
*/

/*
	{
	  "action" : "getDeviceList",
	  "stu_no" : "201521040196",
	  "n" : "ae2bbf",
	  "sign" : "e4a1a638f77b5d381ebdaf75fef351c61219e9b1",
	  "token" : "201521040196B8BB471A0B2873590FFA1000B7088346",
	  "t" : "1440478015"
	}
*/
/*
	$ta = array( $config['token'], '1440478013', '47fc7a' );
	$ta = md5( implode($ta) );
	$sign = sha1( $ta );
	echo $sign."\r\n";
*/	
/*
	$post['token'] = '201521040196B8BB471A0B2873590FFA1000B7088346';
	$authcode = authcode( $post['token'], 'HX_DECODE', $config['hx_auth_key'] );
	var_dump( $authcode );
*/	
//---------------------------------------------------------

	//authcode的组合规则 stu_no|>|password|>|token	
	//$auth = explode( "|>|", $authcode );
	//$stu_no = $auth[0];
	//$password = $auth[1];
	//$token = $auth[2];
	
	/*		
	$stu_no = '201520152015';
	$passwd = '1111111';
	
	// 'f75ddaaeeeea79acf2fe1aa7200bf8c0f1ebc670'
	$ta = array( $config['token'], $stu_no, $passwd );
	$ta = md5( implode($ta) );
	$sign = sha1( $ta );
	
	*/

	function http_post_json( $jsonStr ) {
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_POST, 1 );
		curl_setopt( $ch, CURLOPT_TIMEOUT, 5 );
		//curl_setopt( $ch, CURLOPT_URL, "http://218.6.163.88:50000/card/test/service.php" );
		curl_setopt( $ch, CURLOPT_URL, "http://127.0.0.1/web-server/service.php" );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $jsonStr );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Content-Type: application/json; charset=utf-8',
													 'Content-Length: '.strlen($jsonStr),
													 'resTime'=>time() ) 
					);
					
		$response = curl_exec( $ch );
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$request_result = array($httpCode, $response);
		print_r($request_result);
		//$result = $this->parse_card_data($request_result);
		//return $request_result;
	}
	
	
//$api->bind_device_by_qrcode("201520152015","J65011");
//2015201520155D324367938AC9BCF93AE9BA6C19D0FD

//$api->oprate_device("J65011","OPEN");echo "sadf";

//$api->read_device_status("201520152015",'J65011');

//$api->login("201520152015", "111111");//{("000020300032","J65011");
//exit;20152705012420F7663B6DFDCEEC34C9B6656BB1E60A

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



?>