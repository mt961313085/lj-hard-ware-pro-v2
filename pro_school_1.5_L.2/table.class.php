<?php
class table{
	//定义设备端口
	public $table_old;
	public $table_new;
	public $table_time;
	public $table_ins_flag;//唯一指令标识
	public $table_cnt;//计数器标识
	public $table_config;
	public $table_box=array();//记录箱子状态
	public $ins_cache=array();

	function __construct(){
		//初始化服务器表和硬件表为0
		
		for($i=0;$i<26;$i++)
		{
			for ($j=0;$j<17;$j++)
			{
				$this->table_old[$i][$j]=0;
				$this->table_new[$i][$j]=0;
				$this->table_cnt[$i][$j]=0;
				$this->table_time[$i][$j]=0;
			}
			//初始化所有箱子状态为CLOSE
			$this->table_box[$i]='C';
		}
		//var_dump($this->table_box);
		//$this->table_new[1][1]=1;
		//查配置表，将表信息载入内存，下次查表只需查内存
		$this->table_config=parse_ini_file('config.ini');
		//var_dump($this->table_config);
	}
	//更新旧表
	function set_table_old($id,$num,$value){
			$this->table_old[$id][$num]=$value;
			$this->table_time[$id][$num]="";
	}
	//跟新新表
	function set_table_new($id,$num,$value,$ins_flag,$table_socket){
			//判断设备是否在线
		//echo "***********".$id."*********\r\n";
		
			if(!isset($table_socket[$id])||$table_socket[$id]<=0){
				$floor=$this->table_config[$id];
				$time=time();
				$response="[$time,CUT,$id,$floor]";
				socket_write($table_socket[0], $response,strlen($response));
				//echo "websend: $response\r\n";
				return 'ret';
			}
			$temp=$this->table_new[$id][$num];
			//如果新来的任务和任务表中的任务一致，立即返回执行成功
			//echo "ccccccccc$temp cccc$value \r\n";
			if($temp==$value){
				$response="[$ins_flag,BUSY]";
				socket_write($table_socket[0],$response,strlen($response));
				return 'ret';
			}
			//$this->table_ins_flag[$id][$num]=$ins_flag;
			$this->table_new[$id][$num]=$value;
	}
	//读旧表
	function get_table_old($id,$num){
		return $this->table_old[$id][$num];
	}
	//获取箱子和继电器状态
	function get_boxj_stat($box){
		$str2="";
		for($m=4;$m>0;$m--){
			$temp='';
			for($n=$m*4;$n>($m-1)*4;$n--){
				$temp.=$this->table_new[$box][$n];
			}
			$str2.=base_convert($temp, 2, 16);
		}
		$str2=str_pad($str2,4,'0',STR_PAD_LEFT);
		//获取箱子状态
		$stat=$str2.($this->table_box[$box]);
		$num=strtoupper($stat);
		return $num;
	}
	//新旧表同步
	function compare_table(&$table_socket){
		$now=time();//获取系统时间
		for($i=1;$i<26;$i++)
		{
			for($j=1;$j<17;$j++)
			{
				//当硬件表和服务器表不一致时，三次重发
				if($this->table_old[$i][$j]!=$this->table_new[$i][$j]){	
					//echo "=====DIC======$i $j\r\n";
						if($this->table_time[$i][$j]!=0){
							if($this->table_cnt[$i][$j]>4){
								//超时15秒，上报服务器和硬件断开连接
								//echo "3333333\r\n";
								$this->table_new[$i][$j]=$this->table_old[$i][$j];
								if($this->table_ins_flag!=""){
									$this->table_new[$i][$j]="";
									$tutc=time();
									$floor=$this->check_table($i);
									$response="[$tutc,CUT,$i,$floor]";
									if($table_socket[0]>0){	
										$mid=@socket_write($table_socket[0],$response,strlen($response));
										if($mid==FALSE){
											$table_socket[0]=0;
											//echo $table_socket[0]."   eeeeeeeeee\r\n";
										}
									}
								}
								//$this->table_old[$i][$j]=$this->table_new[$i][$j];
								//时间清零
								$this->table_time[$i][$j]=0;
								//计数器清零
								$this->table_cnt[$i][$j]=0;
								error_log("$i box has disconnected!".date('Y-m-d H:i:s')."\r\n",3,'error_log.txt');
							}
							else{//在这里修改超时时间
								//echo "---ji shu---zhi -".$this->table_cnt[$i][$j]."---\r\n";
								$temp=intval($this->table_time[$i][$j]);
								//echo 'zhidao'.$this->table_cnt[$i][$j]."\r\n";
								$temp1=$temp+intval($this->table_cnt[$i][$j])*5;
								if($now>=$temp1){
									//echo "$now-------$temp\r\n";
									$this->table_cnt[$i][$j]+=1;
									//echo 'timeout'."\r\n";
									$key=$this->table_new[$i][$j];
									$m=sprintf("%02d",$i);
									$num_stat=$this->get_boxj_stat($i);
									//echo "chaoshi------$num_stat\r\n";
									//获取心跳前四位
									$response="[0$m,2,$num_stat]";
									$response=strtoupper($response);
									if(isset($table_socket[$i])&&$table_socket[$i]>0){
										socket_write(@$table_socket[$i],$response,strlen($response));
										//echo "chongfa ----$response\r\n";
									}
								}
							}
						}
						else{
							$this->table_time[$i][$j]=time();
							//echo 'fuzhi'."\r\n";
							$this->table_cnt[$i][$j]=1;
						}
				}
			}
		}
	}
	//查表
	
	function check_heart(&$table_socket,&$sockets_id){
		$now=time();
		for($i=1;$i<=25;$i++){
			if($this->table_time[$i][0]==0)
				continue;
			//echo "************************\r\n";
			//如果60秒都还没有收到心跳
			$temp=$this->table_time[$i][0]+40;
			if($now>$temp){
				error_log("NO.$i box disconnect! ".date('Y-m-d H:i:s')."\r\n",3,'error_log.txt');
				$floor=$this->check_table($i);
				
				//var_dump($sockets_id);
				//关闭该套接字
				/*if(isset($$table_socket[$i]) && $table_socket[$i]>0){
					socket_close($table_socket[$i]);
					$table_socket[$i]=0;
				}*/
				$key=array_search($table_socket[$i], $sockets_id);
				if($key!=FALSE){
					echo '$i'."*********$i       $key*\r\n";
					//var_dump($sockets_id);
					unset($sockets_id[$key]);
					//var_dump($sockets_id);
					socket_close($table_socket[$i]);
				}
				$table_socket[$i]='';
				//上报服务器
				$response="[$now,CUT,$i,$floor]";
				//上报成功
				if($table_socket[0]>0){
					if(socket_write($table_socket[0],$response,strlen($response))>0)
						//将心跳设为空
						$this->table_time[$i][0]=0;
					}
				
			}
		}
	}
	
	
	function check_table($num,&$box=null,&$ibox=null){
		$temp=$this->table_config[$num];
		if(strlen($num)>3){
			//查宿舍号所在的箱子和继电器编号
			$box=substr($temp, 0,2);
			$ibox=substr($temp,2,2);
			//echo "***$box**$ibox**\r\n";
		}
		else{
			//查箱子所在楼层
			return $temp;
		}
	}
	
}
?>
