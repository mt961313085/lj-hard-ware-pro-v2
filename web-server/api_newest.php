<?php

	require_once( 'db.php' );
	
	class cardApi{
		
		private $api_server_url;
		private $socket_server_url;
		private $socket_server_port;
		private $socket;
		private $token;
		private $user_login_token;
		private $config;
		private $db;
		
		public function __construct( $config ) {
			$this->config = $config; // 载入配置文件
			$this->api_server_url = $this->config['api_server_url']; //一卡通接口地址
			$this->washer_fee = $this->config['washer_fee']; //洗衣机费
			$this->water_fee = $this->config['water_fee']; //水费
			$this->token = $this->config['token']; //水费
			$this->school_id = $this->config['school_id']; //学校ID
			$this->socket_server_url = $this->config['socket_server_url']; //智控系统地址
			$this->socket_server_port = $this->config['socket_server_port']; //智控系统端口	
			$this->db = new db($this->config);				
		}
	
	
	//用户登录
	public function login( $student_no, $student_password ) {
	
		$response = $this->signInAndGetUser( $student_no, $student_password );
		$result['resp_desc'] = $response['resp_desc'];
		$result['resp_code'] = $response['resp_code'];
		
		if( $result['resp_code']>0 ) {
			$result['data'] = $response['data'];
			echo json_encode( $result );
		}
		elseif( $result['resp_code']==0 ) {
			//同步用户信息
			//$this->db = new db($this->config);
			$user_map = $response['data']['userMap'];
			$row = $this->db->get_one( "select `id`,`studentNo`,`sex`,`phone`,`department`,`school_zone`,`from`,`graduated`,`home_address`,`nation`,`carrier_account`,`wash_setting`,`cardNo`,`userName`,`nickName`,`headImg`,`email`,`cardBalance`,`monthlyAmt`,`grade`,`major` FROM `user_info`  where studentNo='$student_no' limit 1" );
			
			//更新
			if( $user_map['cardNo'] ) {
				$user_info['cardNo'] = $user_map['cardNo'];
			}
			
			if( $user_map['userName'] ) {
				$user_info['userName'] = $user_map['userName'];
			}
			
			if( $user_map['nickName'] ) {
				$user_info['nickName'] = $user_map['nickName'];
			}
			
			if( $user_map['cardBalance'] ) {
				$user_info['cardBalance'] = $user_map['cardBalance'];
			}
			
			if( $user_map['monthlyAmt'] ) {
				$user_info['monthlyAmt'] = $user_map['monthlyAmt'];
			}
			
			if( $student_password ) {
				$user_info['password'] = $student_password;
			}
			
			$user_info['token'] = $user_map['token'];
			
			if( $row ) {
				$condition = "studentNo='$student_no'";
				$query = $this->db->update( 'user_info', $user_info, $condition );
				/*if($query){
					$this->get_user_info($student_no, $token);
				}else{
					$result['data'] = $response['data']['userMap'];
				}*/
			}
			else {
				//插入
				$user_info['studentNo'] = $student_no;
				$user_info['headImg'] = $user_map['headImg'];
				$user_info['phone'] = $user_map['phone'];
				$user_info['email'] = $user_map['email'];
				$query = $this->db->insert( 'user_info', $user_info );
			}
			
			if( $query ) {
				$sql = "SELECT `id`,`studentNo`,`sex`,`phone`,`department`,`school_zone`,`from`,`graduated`,`home_address`,`nation`,`carrier_account`,`wash_setting`,`cardNo`,`userName`,`nickName`,`headImg`,`email`,`cardBalance`,`monthlyAmt`,`grade`,`major`,`token` FROM `user_info` WHERE `studentNo`='$student_no'";
				$row = $this->db->get_one( $sql );
				$result['resp_desc'] = '';
				$result['resp_code'] = '0';
				$result['data'] = $row;
				echo json_encode ($result );
			}else{
				$result['data'] = $response['data']['userMap'];
				echo json_encode( $result );
			}			
		}
		else {
			echo "{
				'resp_desc' : '一卡通通信失败,请重新登录',
				'resp_code' : '1666',
				'data'      : '{}'
			}";
		}	
	}
	
	/*
		* 与一卡通通信接口
     	* @param student_no 
    */
    private function signInAndGetUser( $student_no, $password ) {
		
		$school_id = $this->school_id;
		$post_data = json_encode( array('body'=>array('studentNo'=>$student_no, 'password'=>$password, 'schoolId'=>$school_id)) );
		$response = $this->http_post_json( 'signInAndGetUser', $post_data );
		
		//update token
		$token = $response['data']['userMap']['token'];
		if( $token ) {
			$response = $this->http_post_json( 'signInAndGetUser', $post_data );
			//update token
			$token = $response['data']['userMap']['token'];
		}
		$this->token = $token;
		return $response;
	}
	
	
}
?>