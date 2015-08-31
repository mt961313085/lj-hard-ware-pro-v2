<?php
//header("Content-type:text/html;charset=utf-8");
require_once("db.php");
class cardApi{
	private $api_server_url;
	private $socket_server_url;
	private $socket_server_port;
	private $socket;
	private $token;
	private $user_login_token;
	private $config;
	private $db;
	public function __construct($config){
		$this->config = $config; // 载入配置文件
		$this->api_server_url = $this->config["api_server_url"]; //一卡通接口地址
		$this->washer_fee = $this->config["washer_fee"]; //洗衣机费
		$this->water_fee = $this->config["water_fee"]; //水费
		$this->token = $this->config["token"]; //水费
		$this->school_id = $this->config["school_id"]; //学校ID
		$this->socket_server_url = $this->config["socket_server_url"]; //智控系统地址
		$this->socket_server_port = $this->config["socket_server_port"]; //智控系统端口	
		$this->db = new db($this->config);				
	}
	

	//绑定设备
	public function bind_device_by_qrcode($student_no,$qrcode,$token){
		//$this->db = new db($this->config);
		$device_id = $qrcode;
		if (!preg_match('/^(H|J)[A-Z0-9]{5}$/', $qrcode)) {
		    echo '{
				"resp_desc" : "非法设备号",
				"resp_code" : "1",
				"data"      : "{}"
			}';
			return false;
		} 
		$row = $this->db->get_one("select * from devices where student_no='$student_no' and device_id='$device_id' limit 1");
		if($row){
			echo  '{
				"resp_desc" : "您已经绑定过了",
				"resp_code" : "1",
				"data"      : "{}"
			}';
		}else{
			$data = array("student_no"=>$student_no,"device_id"=>$device_id);
			$result = $this->db->insert("devices",$data);
			if($result){
				echo '{
					"resp_desc" : "绑定成功",
					"resp_code" : "0",
					"data"      : "{}"
				}';
			}else{
				echo '{
					"resp_desc" : "绑定失败",
					"resp_code" : "1",
					"data"      : "{}"
				}';
			}
		}		
		return true;
	}

		//解除设备
	public function unbind_device($student_no,$device_id,$token){
		//$this->db = new db($this->config);
		//echo "select * from devices where student_no='$student_no' and device_id='$device_id' limit 1";
		//$device_id = $device_id;
		$row = $this->db->get_one("select * from devices where student_no='$student_no' and device_id='$device_id' limit 1");
		if($row){
			if($row["flag"] == 1){
				echo '{
					"resp_desc" : "设备正在使用中，无法删除",
					"resp_code" : "1",
					"data"      : "{}"
				}';
			}else{
				$result = $this->db->delete("devices","device_id='$device_id' and student_no='$student_no'");
				if($result){
					echo '{
						"resp_desc" : "删除成功",
						"resp_code" : "0",
						"data"      : "{}"
					}';
				}else{
					echo '{
						"resp_desc" : "删除失败",
						"resp_code" : "1",
						"data"      : "{}"
					}';
				}
			}
			
		}else{
			echo '{
					"resp_desc" : "您没有绑定该设备",
					"resp_code" : "1",
					"data"      : "{}"
				}';
		}		
		return true;
	}

	//获取设备列表
	public function get_device_list($student_no,$token=""){
		//$this->db = new db($this->config);	
		$sql = "SELECT * FROM `devices` a left join (select student_no as device_owner, device_id as device_no, flag as onstate from devices where flag=1) as b on a.`device_id`=b.`device_no` WHERE `student_no`='$student_no'";
		$query = $this->db->query($sql);
		while ($row = $this->db->fetch_array($query)) {
			$building_info = $this->parse_device_by_device_id($row["device_id"]);


			$item["building"] = $this->config["build_map"][$building_info["building"]];
			$item["floor"] = $building_info["floor"];
			$item["room"] = $building_info["room"];

			$item["deviceName"] = $this->config["build_device_name"][$building_info["device_type"]];
			$item["deviceId"] = $row["device_id"];
			$item["deviceDesc"] = $item["building"].$building_info["device_desc"];
			//$device_onstate = 
			if($row["onstate"] == 2 || ($row["onstate"] == 1 && ($row["device_owner"] != $row["student_no"]))){
				$item["deviceStatus"] = 2;//设备被其他人占有
			}elseif($row["onstate"] == 1){
				$item["deviceStatus"] = 1;//设备被自己占用
			}else{
				$item["deviceStatus"] = 0;//设备未开启
			}			
			$item["deviceType"] = $building_info["device_type"];
			$item["deviceIcon"] = $this->config["build_device_icon"][$building_info["device_type"]];
			$devices[] = $item;
		}
		$result["resp_code"] = "0";
		$result["resp_desc"] = "";
		if(is_array($devices)){
			$result["data"] = $devices;
		}else{
			$result["data"] = "[]";
		}
		
		echo json_encode($result);
		return true;
	}
	
	public function get_version_info(){
		echo '{
			"resp_desc" : "获取成功",
			"resp_code" : "0",
			"data":{
				"android":{
					"version":1,
					"release_notes":"1:功能升级\n2：BUG修复",
					"download_url":"http://www.etzk.com/card/res/appnewest.apk"
				},
				"ios":{
					"version":1,
					"release_notes":"1:功能升级\n2：BUG修复",
					"download_url":"http://itunes.apple.com/cn/app/id474693318"
				}
			}		
		}';
	}

	public function get_carrier_info($token){
		echo '{
			"resp_desc" : "获取成功",
			"resp_code" : "0",
			"data":'.$this->config["carrier_account"].'
		}';
	}

	//修改用户信息
	public function edit_user_info($student_no,$user_info,$token){
		if($user_info["phone"]){
			$values["phone"] = $user_info["phone"];
		}
		if($user_info["wash_setting"]){
			$values["wash_setting"] = $user_info["wash_setting"];
		}
		if($user_info["carrier_account"]){
			$values["carrier_account"] = $user_info["carrier_account"];
		}
		if($user_info["email"]){
			$values["email"] = $user_info["email"];
		}
		if($user_info["nickName"]){
			$values["nickName"] = $user_info["nickName"];
		}
		//$this->db = new db($this->config);
		$condition = "studentNo='$student_no'";
		$query = $this->db->update("user_info",$values,$condition);
		if($query){
			$this->get_user_info($student_no, $token);
		}else{
			echo '{
				"resp_desc" : "更新失败",
				"resp_code" : "1001",
				"data"      : "{}"
			}';
		}
		return true;
	}

	//获取用户信息
	public function get_user_info($student_no,$token){
		//$this->db = new db($this->config);	
		$sql = "SELECT `id`,`studentNo`,`sex`,`phone`,`department`,`school_zone`,`from`,`graduated`,`home_address`,`nation`,`carrier_account`,`wash_setting`,`cardNo`,`userName`,`nickName`,`headImg`,`email`,`cardBalance`,`monthlyAmt`,`grade`,`major`,`token` FROM `user_info` WHERE `studentNo`='$student_no'";
		$row = $this->db->get_one($sql);
		$result["resp_desc"] = "";
		$result["resp_code"] = "0";
		$result["data"] = $row;
		echo json_encode($result);
		return true;
	}

	//保修及意见反馈
	public function feedback($student_no, $msg, $device_id, $desc,$type,$token){
		//$this->db = new db($this->config);	
		$data["student_no"] = $student_no;
		$data["device_id"] = $device_id;
		$data["post_desc"] = $desc;
		$data["msg"] = $msg;
		$data["type"] = $type;
		$data["post_time"] = time();
		$data["post_ip"] = "127.0.0.1";
		$query = $this->db->insert("feedback",$data);
		if($query){
			echo '{
				"resp_desc" : "提交成功",
				"resp_code" : "0",
				"data"      : ""
			}';
		}else{
			echo '{
				"resp_desc" : "提交失败",
				"resp_code" : "1001",
				"data"      : ""
			}';
		}
		
		return true;
	}

	//保修及意见反馈
	public function get_feedback_list($student_no,$type,$token){
		//$this->db = new db($this->config);	
		$sql = "SELECT * FROM `feedback`  WHERE `student_no`='$student_no' and type='$type'";
		$query = $this->db->query($sql);
		//$results = array();
		while ($row = $this->db->fetch_array($query)) {
			$item["student_no"] = $row["student_no"];
			$item["device_id"] = $row["device_id"];
			$item["desc"] = $row["post_desc"];
			$item["msg"] = $row["msg"];
			$item["reply"] = $row["reply"];
			$item["post_time"] = $row["post_time"];
			$item["type"] = $row["type"];
			$result[] = $item;
		}
		if(is_array($result)){
			$result["resp_code"] = "0";
			$result["resp_desc"] = "";
			$result["data"] = $result;
		}else{
			$result["resp_code"] = "1003";
			$result["resp_desc"] = "还没有数据";
			$result["data"] = "";
		}
		echo json_encode($result);
		return true;
	}


	//修改密码
	public function change_password($student_no, $student_password, $new_password){
		//$this->db = new db($this->config);
		$condition = "student_no='$student_no' and password='$student_password'";
		$values = array("password"=>"$new_password");
		$query = $this->db->update("user_info",$values,$condition);
		if($query){
			$result["resp_code"] = "0";
			$result["resp_desc"] = "修改成功";
			$result["data"] = "";
		}else{
			$result["resp_code"] = "1002";
			$result["resp_desc"] = "修改失败";
			$result["data"] = "";
		}
		echo json_encode($result);
		return true;
	}



	//开淋浴房
	public function open_shower($student_no,$device_id, $time, $delay_open, $delay_close,$token,$password=''){
		$begin_time = time();//先不考虑延时开和延时关的问题+$delay_open
		$pre_end_time = $begin_time + (intval($time) * 60);
		$this->operate_device_with_fee($student_no,$device_id,"OPEN",0,$password,$token,$begin_time, $pre_end_time);
	}
	
	//关淋浴房
	public function close_shower($student_no,$device_id,$token,$password){
		$end_time = time();
		echo $password;
		$this->operate_device_with_fee($student_no,$device_id,"CLOSE",0,$password,$token,0,$end_time);
	}
	
	//开洗衣机
	public function open_washer($student_no,$device_id,$token){
		$begin_time = time();//
		$pre_end_time = $begin_time + $this->config["washer_time"];
		$this->operate_device_with_fee($student_no,$device_id,"OPEN",0,"111111",$token,$begin_time, $pre_end_time);

	}

	//开洗衣机
	public function close_washer($student_no,$device_id,$token,$password){
		$end_time = time();
		//$this->socket = stream_socket_client("tcp://".$this->socket_server_url.":".$this->socket_server_port,$errno, $errstr,15);
		//$result = $this->oprate_device($device_id,"CLOSE");
		//读取设备状态
		
		//计算费用发起交易
		//如果交易成功，则开启成功
		
		$this->operate_device_with_fee($student_no,$device_id,"CLOSE",0,$password,$token,0,$end_time);
	}
	
	private function operate_device_with_fee($student_no,$device_id,$operate="OPEN",$fee,$password="",$token="",$begin_time=0,$end_time=0){
		$data = "{}";
		if(!$this->config["debug"]){
			$this->socket = stream_socket_client("tcp://".$this->socket_server_url.":".$this->socket_server_port,$errno, $errstr,15);
			$result = $this->oprate_device($device_id,$operate);
		}else{
			$result = 1;
		}
		if($result == 2){
			//$this->db = new db($this->config);
			$condition = "student_no='$student_no' and device_id='$device_id'";
			if($operate=="OPEN"){
				$values = array("flag"=>"2","begin_time"=>0,"pre_end_time"=>0,"end_time"=>0,"fee_flag"=>0);
				$query = $this->db->update("devices",$values,$condition);
				echo '{
				"resp_desc" : "设备已被占用",
				"resp_code" : "1",
				"data"      : "{}"
				}';
			}elseif($operate=="CLOSE"){
				$values = array("flag"=>"0","begin_time"=>0,"pre_end_time"=>0,"end_time"=>0,"fee_flag"=>0);
				$query = $this->db->update("devices",$values,$condition);
				echo '{
				"resp_desc" : "设备已关闭",
				"resp_code" : "1",
				"data"      : "{}"
				}';
			}else{
				echo '{
				"resp_desc" : "未知操作",
				"resp_code" : "1002",
				"data"      : "{}"
				}';
			}
			return;
		}elseif($result == 3){
			echo '{
			"resp_desc" : "操作失败,设备已断线，请联系管理人员报修",
			"resp_code" : "1002",
			"data"      : "{}"
			}';
			return;
		}elseif($result == 1){//操作成功
			//先更改数据库
			//$this->db = new db($this->config);
			$data = "{}";
			$condition = "student_no='$student_no' and device_id='$device_id'";
			if($operate == "OPEN"){
				$values = array("flag"=>"1","begin_time"=>$begin_time,"pre_end_time"=>$end_time,"end_time"=>0,"fee_flag"=>0);
				$query = $this->db->update("devices",$values,$condition);
				if($query){
					$result_msg = "开启成功，数据更新成功";
				}else{
					$result_msg = "开启成功，数据库更新失败";
				}
				
			}else{
				
				//计算本次费用
				$sql = "select * from devices where student_no='$student_no' and device_id='$device_id'";
				$device_item = $this->db->get_one($sql);
				if($device_item["begin_time"] == 0){
					$fee_time = 0;
				}else{
					$fee_time = ($end_time - $device_item["begin_time"]);
				}
				
				$fee_rate = $this->config["water_fee"];

				$total_fee = number_format(($fee_time * $fee_rate)/60,2)*100;
				$display_fee = number_format(($fee_time * $fee_rate)/60,2);
				$display_fee_time = floor($fee_time/60).":".($fee_time%60);
				$data = '{"fee_rate":"'.$this->config["water_fee"].'元/分钟","time":"'.$display_fee_time.'","total_fee":"'.$display_fee.'元"}';
				if($total_fee > 0){
					$t = time();
					if($t >1441296000){
						$trade_no = date("YmdHis").rand(1000,9999);
						$response = $this->tptrade($student_no, $token, $trade_no, $password, $this->config["branch_id"],  $total_fee);//向一卡通缴费
					}else{
						$response["resp_code"] = 0 ;
					}
					//$trade_no = date("YmdHis").rand(1000,9999);
					//$response = $this->tptrade($student_no, $token, $trade_no, $password, "4000002",  $total_fee);//向一卡通缴费
					//echo $response["resp_code"]."ddd";
					if($response["resp_code"] == 0){
						$values = array("fee_flag"=>"1","flag"=>"0","end_time"=>$end_time);
						$query = $this->db->update("devices",$values,$condition);
						if(!$query){
							//$this->oprate_device($device_id,"CLOSE");
							$result_msg = "计费成功，数据库更新失败";
						}
					}else{
						$result_msg = "计费失败";
					}
				}else{
					$values = array("fee_flag"=>"1","flag"=>"0","end_time"=>$end_time);
					$query = $this->db->update("devices",$values,$condition);
					$data = '{"fee_rate":"'.$this->config["water_fee"].'元/分钟","time":"'.$display_fee_time.'","total_fee":"'.$total_fee.'元"}';
				}
				
			}
			
			//如果交易成功，则开启成功
			if($query){
				echo '{
				"resp_desc" : "'.$result_msg.'",
				"resp_code" : "0",
				"data"      : '.$data.'
				}';
			}else{
				echo '{
				"resp_desc" : "'.$result_msg.'",
				"resp_code" : "1001",
				"data"      : {"fee_rate":"'.$this->config["water_fee"].'元/分钟","time":"00:00","total_fee":"0元"}
				}';
			}
		}else{
			echo '{
			"resp_desc" : "操作失败",
			"resp_code" : "1001",
			"data"      : {"fee_rate":"'.$this->config["water_fee"].'元/分钟","time":"00:00","total_fee":"0元"}
			}';
		}
	}

	private function oprate_device($room_id,$operate="OPEN"){
		//$this->socket = stream_socket_client("tcp://".$this->socket_server_url.":".$this->socket_server_port,$errno, $errstr,15);
		//
		/*if($operate == "OPEN"){
			$flag = 1;
		}else($operate == "CLOSE"){
			$flag = 0;
		}*/
		/*$this->db = new db($this->config);
		$sql = "SELECT device_id FROM `devices` WHERE `device_id`='$room_id' and flag=1";
		$row = $this->db->get_one($sql);
		if($row && $operate == 'OPEN'){
			return 2;//设备已被占用
		}*/
		$timestamp = time();
		$buff = "[$timestamp,$operate,$room_id]";		
		$result = 0;
		$try_count = 0;
		while($result==false && $try_count<3){
			$res = $this->send_message($buff);
			if($res == "[$timestamp,OK]"){
				$result = 1;//操作成功
			}elseif($res == "[$timestamp,BUSY]"){
				$result = 2;//设备被占用
			}elseif(strpos("$timestamp,CUT",$res)>0){
				$result = 3;//设备断线
				sleep(2);
			}else{
				sleep(2);
			}
			$try_count++;
		}
		return $result;
	}
	
	public function read_device_status($student_no, $device_id){
		$this->socket = stream_socket_client("tcp://".$this->socket_server_url.":".$this->socket_server_port,$errno, $errstr,15);

		$timestamp = time();
		$buff = "[$timestamp,READ,$device_id]";		
		$result = false;
		$try_count = 0;
		while(!$result && $try_count<3){
			$res = $this->send_message($buff);
			if($res == "[$timestamp,ON]"){
				echo '{
					"resp_desc" : "当前设备状态是ON",
					"resp_code" : "0",
					"data"      : "{\"status\":\"1\"}"
				}';
				$result = true;//操作成功
			}elseif($res == "[$timestamp,OFF]"){
				echo '{
					"resp_desc" : "当前设备状态是OFF",
					"resp_code" : "0",
					"data"      : "{\"status\":\"0\"}"
				}';
				$result = true;
			}else{
				//sleep(15);
			}
			$try_count++;
		}
		return $result;
	}

	//用户登录
	public function login($student_no, $student_password){
		$response = $this->signInAndGetUser($student_no, $student_password);
		$result["resp_desc"] = $response["resp_desc"];
		$result["resp_code"] = $response["resp_code"];
		if($result["resp_code"] > 0){
			$result["data"] = $response["data"];
			echo json_encode($result);
		}elseif($result["resp_code"] == 0){
			//同步用户信息
			//$this->db = new db($this->config);
			$user_map = $response["data"]["userMap"];
			$row = $this->db->get_one("select `id`,`studentNo`,`sex`,`phone`,`department`,`school_zone`,`from`,`graduated`,`home_address`,`nation`,`carrier_account`,`wash_setting`,`cardNo`,`userName`,`nickName`,`headImg`,`email`,`cardBalance`,`monthlyAmt`,`grade`,`major` FROM `user_info`  where studentNo='$student_no' limit 1");
			//更新
			if($user_map["cardNo"]){
				$user_info["cardNo"] = $user_map["cardNo"];
			}
			if($user_map["userName"]){
				$user_info["userName"] = $user_map["userName"];
			}
			if($user_map["nickName"]){
				$user_info["nickName"] = $user_map["nickName"];
			}
			if($user_map["cardBalance"]){
				$user_info["cardBalance"] = $user_map["cardBalance"];
			}
			if($user_map["monthlyAmt"]){
				$user_info["monthlyAmt"] = $user_map["monthlyAmt"];
			}
			if($student_password){
				$user_info["password"] = $student_password;
			}
			//加密token
			//$user_info["token"] = $user_map["token"];
			$auth_token = $student_no."|>|".$student_password."|>|".$user_map["token"];
			$encode_auth_token = $this->authcode($auth_token, "ENCODE", $encode_auth_key);
			$user_info["token"] = $encode_auth_token;
			if($row){
				
				$condition = "studentNo='$student_no'";
				$query = $this->db->update("user_info",$user_info,$condition);
				/*if($query){
					$this->get_user_info($student_no, $token);
				}else{
					$result["data"] = $response["data"]["userMap"];
				}*/
			}else{
				//插入
				$user_info["studentNo"] = $student_no;
				$user_info["headImg"] = $user_map["headImg"];
				$user_info["phone"] = $user_map["phone"];
				$user_info["email"] = $user_map["email"];
				$query = $this->db->insert("user_info",$user_info);

			}
			if($query){
				$sql = "SELECT `id`,`studentNo`,`sex`,`phone`,`department`,`school_zone`,`from`,`graduated`,`home_address`,`nation`,`carrier_account`,`wash_setting`,`cardNo`,`userName`,`nickName`,`headImg`,`email`,`cardBalance`,`monthlyAmt`,`grade`,`major`,`token` FROM `user_info` WHERE `studentNo`='$student_no'";
				$row = $this->db->get_one($sql);
				$result["resp_desc"] = "";
				$result["resp_code"] = "0";
				$result["data"] = $row;
				echo json_encode($result);
			}else{
				$result["data"] = $response["data"]["userMap"];
				echo json_encode($result);
			}			
		}else{
			echo '{
				"resp_desc" : "一卡通通信失败,请重新登录",
				"resp_code" : "1666",
				"data"      : "{}"
			}';
		}
		
		
	}

	public function get_card_transaction($student_no,$token,$page_index=1, $page_size=10, $begin_date=0, $end_date=0){
		$response = $this->getCardTransaction($student_no, $token, $page_index, $page_size, $begin_date, $end_date);
		echo json_encode($response);
	}

	public function hand_lost($student_no, $token, $card_no, $password, $opt_type){
		$response = $this->handLost($student_no, $token, $card_no, $password, $opt_type);
		echo json_encode($response);
	}

	public function get_subsidy_list($student_no, $token,
	$page_index,$page_size,$begin_date,$end_date){
		$response = $this->getSubsidyList($student_no, $token, $page_index, $page_size,$begin_date,$end_date);
		echo json_encode($response);
	}

	//充值
	public function recharge($student_no, $token, $password, $money,$name){
		$deposit_no = date("YmdHis").rand(1000,9999);
		$response = $this->tpdeposit($student_no, $token, $deposit_no, $password, $money,$name);

		echo json_encode($response);
	}

	public function trade($student_no, $token, $password, $trade_branch_id, $trade_money){
		$trade_no = date("YmdHis").rand(1000,9999);
		$response = $this->tptrade($student_no, $token, $trade_no, $password, $trade_branch_id,  $trade_money);
		echo json_encode($response);
	}	

	 /**
     * 验证配置接口信息
     * @param array 从微信接口发送来的信息，通过$_POST获得
     */
    public function interface_valid($get_request) {
            $signature = $get_request['sign'];
            $timestamp = $get_request['t'];
            $nonce = $get_request['n'];        

            $token = $this->token;
            $tmpArr = array($token, $timestamp, $nonce);
            $tmpStr = md5(implode( $tmpArr ));
            $tmpStr = sha1( $tmpStr );
            //echo  $signature."||".$tmpStr;
            if( $tmpStr != $signature ){
                echo '{
					"resp_desc" : "鉴权失败",
					"resp_code" : "1098",
					"data"      : "{}"
				}';
               exit;
            }else{
            	return true;
            }
    }

    /*
		* 与一卡通通信接口
     	* @param student_no 
    */
    private function signInAndGetUser($student_no, $password){
		$school_id = $this->school_id;
		$post_data = json_encode(array("body"=>array("studentNo" => $student_no,"password" => $password,"schoolId"=>$school_id)));
		$response = $this->http_post_json("signInAndGetUser",$post_data);
		//update token
		$token = $response["data"]["userMap"]["token"];
		if($token){
			$response = $this->http_post_json("signInAndGetUser",$post_data);
			//update token
			$token = $response["data"]["userMap"]["token"];
		}
		$this->token = $token;
		return $response;
	}

	/*
		* 消费记录查询
     	* @param student_no 
    */
	private function getCardTransaction($student_no, $token, $page_index, $page_size, $begin_date, $end_date){
		$school_id = $this->school_id;
		$post_data = json_encode(array("body"=>array("studentNo" => $student_no,"token" => $token,"pageIndex"=>$page_index,"pageSzie"=>"$page_size","beginDate"=>$begin_date,"endDate"=>$end_date,"schoolId"=>$school_id)));
		$data = $this->http_post_json("getCardTransaction",$post_data);
		if(is_array($data['data'])){
			$data["data"] = $data["data"]["list"];	
		}else{
			$data["resp_desc"] = "暂无消费记录";
		}
		return $data;
	}

	/*
		* 充值记录查询
     	* @param student_no 
    */
	private function getSubsidyList($student_no,$token, $page_index, $page_size,$begin_date,$end_date){
		$school_id = $this->school_id;
		$post_data = json_encode(array("body"=>array("studentNo" => $student_no,"token" => $token,"pageIndex"=>$page_index,"pageSzie"=>$page_size,"beginDate"=>$begin_date,"endDate"=>$end_date,"schoolId"=>$school_id)));
		$data = $this->http_post_json("getSubsidyList",$post_data);
		if(is_array($data['data'])){
			$data["data"] = $data["data"]["list"];	
		}
		return $result;
	}

	/*
		* 充值交易
     	* @param student_no 
    */
	private function tpdeposit($student_no, $token, $deposit_no, $password, $money,$name){
		$school_id = $this->school_id;
		$post_data = json_encode(array("body"=>array("studentNo" =>$student_no,"token" =>$token,"password"=>$password,"depositNo"=>$deposit_no,"depositMoney"=>$money,"schoolId"=>$school_id)));
		$data = $this->http_post_json("tpdeposit",$post_data);
		$sign = md5($deposit_no.$summary.$trade_money.$school_id.$this->config["pay_token"]);
		if($data["resp_code"] == 0){
			$trade_money = number_format((intval( $money)/100),2);
			$summary = "充值".$trade_money."元";
			$data["data"] = array(
			    "order_no"=>$deposit_no,
			    "summary"=>$summary,
			    "amount"=>$summary,
			    "school_code"=>$school_id,
			    "school_account"=>$this->config["school_account"],
			    "name"=>$name,
			    "sign"=>$sign
			);

		}
		return $data;
	}

	/*
		* 消费交易
     	* @param student_no 
    */
	private function tptrade($student_no, $token, $trade_no, $password, $trade_branch_id,  $trade_money){
		$school_id = $this->school_id;
		$post_data = json_encode(array("body"=>array("studentNo" => $student_no,"token" => $token,"password"=>$password,"tradeBranchId"=>$trade_branch_id,"tradeNo"=>$trade_no,"tradeMoney"=>$trade_money,"schoolId"=>$school_id)));
		$data = $this->http_post_json("tptrade",$post_data);
		return $data;
	}

	/*
		* 消费交易
     	* @param student_no 
    */
    private function handLost($student_no,$token, $card_no, $password, $opt_type){
    	$school_id = $this->school_id;
    	$post_data = json_encode(array("body"=>array("studentNo" => $student_no,"cardNo"=>$card_no,"token" => $token,"password"=>$password,"optType"=>$opt_type,"schoolId"=>$school_id)));
		$data = $this->http_post_json("tptrade",$post_data);
		return $data;
    }

    private function parse_card_data($data){
    	$data_obj = json_decode($data[1], true);
    	$data_obj_body = $data_obj["body"];
    	$data_obj_data = $data_obj_body["data"] == "" ? $data_obj_body["log"]:$data_obj_body["data"];
    	if($data_obj_body["resp_code"] > 0 && strpos($data_obj_data, $this->config["token_invaild_str"])>0){
    		$data_obj_body["resp_code"] = $this->config["token_invaild"];
    	}
    	$result = array("resp_desc"=>$data_obj_body["resp_desc"],"resp_code"=>$data_obj_body["resp_code"],"data"=>$data_obj_data);
    	return $result;
    }

	/*
		* json post 传输
     	* @param student_no 
    */
	private function http_post_json($key, $jsonStr){
	  $ch = curl_init();
	  curl_setopt($ch, CURLOPT_POST, 1);
	  curl_setopt($ch, CURLOPT_URL, $this->api_server_url."?key=".$key);
	  curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr);
	  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	  curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	      'Content-Type: application/json; charset=utf-8',
	      'Content-Length: ' . strlen($jsonStr),
		  "resTime"=>time(),
		  "key"=>$key
	    )
	  );
	  $response = curl_exec($ch);
	  $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	  $request_result = array($httpCode, $response);
	  $result = $this->parse_card_data($request_result);
	  return $result;
	}

	//与智控设备通信
	private function send_message($message){
		
		if(!$this->socket){
			$response = "erreur : $errno - $errstr<br />n";
		}else{
			fwrite($this->socket,"$message");
			stream_set_blocking($this->socket,1);
			$response =  fread($this->socket, 1024);
			stream_set_blocking($this->socket,0);
		}
		return $response;
	}

	//智控设备编码解码
	private function parse_device_by_device_id($device_id){
	    preg_match('/^([HJ])([A-Z0-9])([A-Z0-9])([A-Z0-9])([A-Z0-9])([A-Z0-9])$/', $device_id,$matchs);
	    if($matchs[1] == "J"){//表明是J
	        $result["building"] = "J";
	        $result["floor"] = $matchs[2];
	        if($matchs[4] == "L"){//洗衣机
	            $result["device_type"] = "2";
	            $slide = $matchs[7] == 1? "左边":"右边";
	            $result["room"] = $matchs[3]."楼";
	            $result["device_desc"] =  $result["floor"]."栋".$matchs[3]."楼".$slide."洗衣机";          
	        }else{
	            $result["device_type"] = "1";
	            $result["room"] = $matchs[3].$matchs[4].$matchs[5];
	            $result["device_desc"] = $result["floor"]."栋".$matchs[4].$matchs[5].$matchs[6]."热水器";
	        }
	    }elseif($matchs[1] == "H"){
	        $result["building"] = "H";
	        $result["floor"] = $matchs[2];
	        $room = hexdec($matchs[3]) - 9;
	        $room_id = $room."单元".$matchs[4]."0".$matchs[5];
	        $result["room"] = $matchs[4]."0".$matchs[5];
	        if($matchs[6] == "A"){//洗衣机
	            $result["device_type"] = "2";
	            $result["device_desc"] =  $result["floor"]."栋".$room_id."洗衣机";
	        }else{
	            $result["device_type"] = "1";
	            $result["device_desc"] =  $result["floor"]."栋".$room_id."热水器";
	        }
	    }
	    //print_r($matchs);
	    return $result;
	}


	/** 
	* $string 明文或密文 
	* $operation 加密ENCODE或解密HX_DECODE 
	* $key 密钥 
	* $expiry 密钥有效期 
	*/ 
	public function authcode($string, $operation = 'HX_DECODE', $key = '', $expiry = 0) { 
		// 动态密匙长度，相同的明文会生成不同密文就是依靠动态密匙 
		// 加入随机密钥，可以令密文无任何规律，即便是原文和密钥完全相同，加密结果也会每次不同，增大破解难度。 
		// 取值越大，密文变动规律越大，密文变化 = 16 的 $ckey_length 次方 
		// 当此值为 0 时，则不产生随机密钥 
		$ckey_length = 4; 
		
		// 密匙 这里可以根据自己的需要修改 
		$key = md5($key); 
		
		// 密匙a会参与加解密 
		$keya = md5(substr($key, 0, 16)); 
		// 密匙b会用来做数据完整性验证 
		$keyb = md5(substr($key, 16, 16)); 
		// 密匙c用于变化生成的密文 
		$keyc = $ckey_length ? ($operation == 'HX_DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : ''; 
		// 参与运算的密匙 
		$cryptkey = $keya.md5($keya.$keyc); 
		$key_length = strlen($cryptkey); 
		// 明文，前10位用来保存时间戳，解密时验证数据有效性，10到26位用来保存$keyb(密匙b)，解密时会通过这个密匙验证数据完整性 
		// 如果是解码的话，会从第$ckey_length位开始，因为密文前$ckey_length位保存 动态密匙，以保证解密正确 
		$string = $operation == 'HX_DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string; 
		$string_length = strlen($string); 
		$result = ''; 
		$box = range(0, 255); 
		$rndkey = array(); 
		// 产生密匙簿 
		for($i = 0; $i <= 255; $i++) { 
			$rndkey[$i] = ord($cryptkey[$i % $key_length]); 
		} 
		// 用固定的算法，打乱密匙簿，增加随机性，好像很复杂，实际上并不会增加密文的强度 
		for($j = $i = 0; $i < 256; $i++) { 
			$j = ($j + $box[$i] + $rndkey[$i]) % 256; 
			$tmp = $box[$i]; 
			$box[$i] = $box[$j]; 
			$box[$j] = $tmp; 
		} 
		// 核心加解密部分 
		for($a = $j = $i = 0; $i < $string_length; $i++) { 
			$a = ($a + 1) % 256; 
			$j = ($j + $box[$a]) % 256; 
			$tmp = $box[$a]; 
			$box[$a] = $box[$j]; 
			$box[$j] = $tmp; 
			// 从密匙簿得出密匙进行异或，再转成字符 
			$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256])); 
		} 
		if($operation == 'HX_DECODE') { 
		// substr($result, 0, 10) == 0 验证数据有效性 
		// substr($result, 0, 10) - time() > 0 验证数据有效性 
		// substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16) 验证数据完整性 
		// 验证数据有效性，请看未加密明文的格式 
			if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 	16)) { 
				return substr($result, 26); 
			} else { 
				return ''; 
			} 
		} else { 
			// 把动态密匙保存在密文里，这也是为什么同样的明文，生产不同密文后能解密的原因 
			// 因为加密后的密文可能是一些特殊字符，复制过程可能会丢失，所以用base64编码 
			return $keyc.str_replace('=', '', base64_encode($result)); 
		} 
	} 
}
?>