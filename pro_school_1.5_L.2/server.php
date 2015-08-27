<?php
require_once 'data.php';
date_default_timezone_set( 'Asia/Chongqing' );
set_time_limit(0);
$ipconfig=parse_ini_file('ipconfig.ini');
$ip=$ipconfig['ip'];
//$ip='';
$port=$ipconfig['port'];
//error_log(date('Y-m-d H:i:s')."\r\n",3,'error_log.txt');
//创建套接字，基于tcp的流式套接字
$con=socket_create( AF_INET, SOCK_STREAM, SOL_TCP);
//echo $con;
if($con<0)
	//创建套接字失败，将失败原因写入套接字
	error_log('socket create failed:'.socket_strerror(socket_last_error()).date('Y-m-d H:i:s')."\r\n",3,'error_log.txt');

 //socket端口30秒没有数据发送，断开连接
socket_set_option( $con, SOL_SOCKET, SO_RCVTIMEO, array("sec"=>3, "usec"=>0 ) );
//设置输出流因流量拥塞控制而阻塞多久而发送报告的时间
//socket_set_option( $con, SOL_SOCKET, SO_SNDTIMEO, array("sec"=>3, "usec"=>0 ) );
//报告地址是否允许重复使用
socket_set_option( $con, SOL_SOCKET, SO_REUSEADDR, 1 );
//绑定con套接字
if(!socket_bind($con,$ip,$port)) {
	//绑定失败，写入日志
	error_log('socket bing failed:'.socket_strerror(socket_last_error()).date('Y-m-d H:i:s')."\r\n",3,'error_log.txt');
	echo "bind failed!\r\n";
	return;
}
//监听端口
if(!socket_listen($con))
	error_log('socket listen failed:'.socket_strerror(socket_last_error()).date('Y-m-d H:i:s')."\r\n",3,'error_log.txt');
echo "server has been started!\r\n";
//记录每一个连进来的socket的编号
$client=array();
//记录所有socket的编号
$sockets_id=array($con);
echo '$con---------'.$con."\r\n";
//记录每个硬件设备、web服务器对应的套接字编号,[1`25]为硬件设备，[0 ]为web服务器套接字编号
while(true){
	$read=$sockets_id;
	//print_r($read);

	$write=NULL;
	$except=NULL;
	//开始观察所有套接字情况，如果都没有发状态改变，则结束本轮循环，开始下一轮循环
	$i=@socket_select($read,$write,$except,3);
	$jisuan=time();
	//echo "$i-mt\r\n";
	//$jisuan=time();
	if($i>=1){	
		
	//判断是否有新的客户端连接进来，有就给他创建一个新的套接字用于通信 ,并剔除con套接字，以便遍历read
	if(in_array($con, $read)){
		$sockets_id[]=$client[]=$read[]=$new=socket_accept($con);
		//给新链接来的套接字设置读超时，当10秒内不发送来信息，就关闭连接。
		//socket_set_option( $new, SOL_SOCKET, SO_RCVTIMEO, array("sec"=>3, "usec"=>0 ) );
		echo "$new connect into \r\n";
		//print_r($read);
		$key=array_search($con, $read);
		unset($read[$key]);
		//print_r($sockets_id);
		}
	//遍历read数组
	foreach($read as $socket_now){
		//读取字节流中的数据
		$rtime=time();
		$buff=socket_read($socket_now,1024*5);
		$rtime2=time();
		$rtime3=time()-$rtime2;
		//echo "-----$rtime************$rtime2--******-$rtime3-------\r\n";
		if($buff==FALSE){
			//找出断开连接端口所对应的套接字编号
			$box=array_search($socket_now,$table_socket);
			$log="NO.$box  box is disconnected";
			echo $log."\r\n";
			error_log($log.date('Y-m-d H:i:s')."\r\n",3,'error_log.txt');
			socket_close($socket_now);
			//查找键值
			$k=array_search($socket_now, $sockets_id);
			//删除该键
			//var_dump($sockets_id);
			unset($sockets_id[$k]);
			//var_dump($sockets_id);
			$key=array_search($socket_now, $client);
			unset($client[$key]);
			//记录日志
			//关闭断开连接的客户端，**************
			//  **********
			//  *******
			//  *************并且把该套接字对应的硬件设备编号找到
		}
		else{
				//连接保持 没有消息发送过来，则开始下一论循环
				if($buff==""){
					echo "\t\t\t\tgot empty data\r\n";
					continue;
				}
				//有消息时处理消息
				$now = time();
				get_instruct($buff, $socket_now,$table_socket,$table,$sockets_id);
				$ji=$now-$jisuan;
				if( $ji>1 )
					echo "\t\t\t\t-----------$ji\r\n";
				//echo "zenmel  $buff";
				//暂时不管
				
				
				
		}
	}
		
	}
	//检查心跳
	$t1 = time();
	$table->compare_table($table_socket);
	$table->check_heart($table_socket,$sockets_id);
	//$now=time();
	$ji=time()-$jisuan;
	if( $ji>1 )
		echo "\t\t\t\t================$ji\r\n";
	//echo "------$now------$jisuan------$ji-\r\n";
	//查表
	
}


?>
