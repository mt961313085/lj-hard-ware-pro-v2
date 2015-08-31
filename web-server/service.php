<?php
	header( 'Content-type:text/html;charset=utf-8' );
	
	require_once( 'config.php' );
	require_once( 'api.php' );
	
	$api = new cardApi( $config );
	$str = file_get_contents( 'php://input' );
	$str = str_replace( "\\\"", "'", $str );
	$post = json_decode( $str, true );
	
	error_log( $str, 3, '1.txt' );
	$api->interface_valid( $post );
	
	$action = $post['action'];
	
	//防止非法篡改学号和token
	if( $action!='login' ) {
		
		$post['token'] = str_replace( " ", "+", $post['token'] );
	
		//authcode 的 加密 在 login方法里面，验证了一卡通的账号有效性后实现
		$authcode = $api->authcode( $post['token'], 'HX_DECODE', $config['hx_auth_key'] );
	
		//authcode的组合规则 stu_no|>|password|>|token	
		$auth = explode( "|>|", $authcode );
		$stu_no = $auth[0];
		$password = $auth[1];
		$token = $auth[2];
	
		if( $stu_no!=$post['sut_no'] ) {
			echo '{
				"resp_desc" : "非法操作,登录的学号和操作时的学号不匹配",
				"resp_code" : "1666",
				"data"      : "{}"
				}';
			exit;
		}
		
		$post['sut_no'] = $stu_no;
		$post['password'] = $password;
		$post['token'] = $token;
	}
	else {							// login 进行 sign 验证
									// login 时，无post['token']，而是post['sign']
		$ta = array( $config['token'], $post['t'], $post['n'] );
		$ta = md5( implode($ta) );
		$sign = sha1( $ta );
		
		if( $sign!=$post['sign'] ) {
			echo '{
					"resp_desc" : "鉴权失败",
					"resp_code" : "1098",
					"data"      : "{}"
				}';		
			exit;
		}
	}

	switch( $action ) {
		
		case 'login':
			$student_no = $post['stu_no'];
			$student_password = $post['password'];
			$api->login( $student_no, $student_password );
			break;
		
		case 'bindDevice':
			$student_no = $post['stu_no'];
			$qrcode = $post['qrcode'];
			$api->bind_device_by_qrcode($student_no, $qrcode);
			break;
		
		case 'unbindDevice':
			$student_no = $post['stu_no'];
			$device_id = $post['device_id'];
			$api->unbind_device($student_no, $device_id);
			break;
		
		case 'readDeviceStatus':
			$student_no = $post['stu_no'];
			$qrcode = $post['device_id'];
			$token = $post['token'];
			$api->read_device_status($student_no, $device_id,$token);
			break;
		
		case 'getDeviceList':
			$student_no = $post['stu_no'];
			$token = $post['token'];
			$api->get_device_list($student_no, $token);
			break;
		
		case 'getVersionInfo':
			$api->get_version_info();
			break;
		
		case 'changePassword':
			$student_no = $post['stu_no'];
			$token = $post['token'];
			$password = $post['token'];
			$new_password = $post['token'];		
			$api->change_password($student_no, $password, $new_password);
			break;
		
		case 'editUserInfo':
			$student_no = $post['stu_no'];
			$token = $post['token'];
			$api->edit_user_info($student_no,$post,$token);
			break;
		
		case 'getUserInfo':
			$student_no = $post['stu_no'];
			$token = $post['token'];
			$api->get_user_info($student_no,$token);
			break;
		
		case 'feedback':
			$student_no = $post['stu_no'];
			$token = $post['token'];
			$type = $post['type'];
			$msg = $post['msg'];
			$desc = $post['desc'];
			$device_id = $post['device_id'];
			$api->feedback($student_no, $msg, $device_id, $desc,$type);
			break;
		
		case 'getFeedbackList':
			$student_no = $post['stu_no'];
			$token = $post['token'];
			$type = $post['type'];
			$api->get_feedback_list($student_no,$type,$token);
			break;
		
		case 'getCarrierInfo':
			$token = $post['token'];
			$api->get_carrier_info($token);
			break;
		
		case 'openShower':
			$student_no = $post['stu_no'];
			$device_id = $post['device_id'];
			$time = $post['time'];//分钟
			$token = $post['token'];
			$delay_open = $post['delay_open'];
			$delay_close = $post['delay_close'];
			$api->open_shower($student_no,$device_id, $time, $delay_open, $delay_close);
			break;
		
		case 'closeShower':
			$student_no = $post['stu_no'];
			$device_id = $post['device_id'];
			$token = $post['token'];
			$password = $post['password'];
			$api->close_shower($student_no,$device_id,$password);
			break;
		
		case 'openWasher':
			$device_id = $post['device_id'];
			$token = $post['token'];
			$api->open_washer($student_no,$device_id);
			break;
		
		case 'closeWasher':
			$device_id = $post['device_id'];
			$token = $post['token'];
			$password = $post['password'];
			$end_time = time();
			$api->close_washer($student_no,$device_id,$end_time,$token,$password);
			break;
		
		case 'getCardTransaction':
			$student_no = $post['stu_no'];
			$token = $post['token'];
			$page_index = $post['page_index'];
			$page_size = $post['page_size'];
			$begin_date = $post['begin_date'];
			$end_date = $post['end_date'];
			$api->get_card_transaction($student_no, $token, $page_index, $page_size, $begin_date, $end_date);
			break;
		
		case 'handLost':
			$student_no = $post['stu_no'];
			$token = $post['token'];
			$card_no = $post['card_no'];
			$password = $post['password'];
			$opt_type = $post['op'];
			$api->hand_lost($student_no, $token, $card_no, $password, $opt_type);
			break;
		
		case 'getSubsidyList':
			$student_no = $post['stu_no'];
			$token = $post['token'];
			$page_index = $post['page_index'];
			$page_size = $post['page_size'];
			$begin_date = $post['begin_date'];
			$end_date = $post['end_date'];
			$api->get_subsidy_list($student_no, $token, $page_index, $page_size,$begin_date,$end_date);
			break;
		
		case 'recharge':
			$student_no = $post['stu_no'];
			$token = $post['token'];
			$password = $post['password'];
			$money = $post['money'];
			$name = $post['name'];
			$api->recharge($student_no, $token, $password, $money,$name);
			break;
		
		case 'trade':
			$student_no = $post['stu_no'];
			$token = $post['token'];
			$password = $post['password'];
			$trade_branch_id = $post['branch'];
			$trade_money = $post['fee'];
			$api->trade($student_no, $token, $password, $trade_branch_id,  $trade_money);
			break;
	}

?>