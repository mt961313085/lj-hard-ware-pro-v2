<?php
/*
	web端指令格式为：[web,timestamp,operate,locate]
	operate - CLOSE、OPEN
	locate - ‘build-roomid’
	
	收到web端指令，立即返回 [web, timestamp(同收到的指令), operate, GOT]
*/
	require_once( 'config.php' );
	require_once( 'net_pro.php' );
	
	class sock_info {
		public $sock = -1;
		public $lt = 0;
		public $c_id = '';					// 控制板编号, 或服务器连接编号
	}
	
	class order {
		public $id = '';
		public $t = 0;
		public $op = '';
		public $state = '';
		public $locate = '';
	}
	
	pcntl_signal( SIGCHLD, SIG_IGN );
	date_default_timezone_set( 'Asia/Chongqing' );

		
	pro_dev_state( '001', '1A01C' );
	//echo decode_dev_id('001012')."\r\n";
	exit;
	
	$l_ip = $config['ip'];
	$l_port = $config['port'];
	
	$sock = socket_create( AF_INET, SOCK_STREAM, 0 );
	socket_set_option( $sock, SOL_SOCKET, SO_RCVTIMEO, array("sec"=>10, "usec"=>0 ) );
	socket_set_option( $sock, SOL_SOCKET, SO_SNDTIMEO, array("sec"=>3, "usec"=>0 ) );
	socket_set_option( $sock, SOL_SOCKET, SO_REUSEADDR, 1 );
	
	if( socket_bind($sock, $l_ip, $l_port)===FALSE ) {       		// 绑定 ip、port
		error_log( "hardware-server socket_bind failed!\t\t".date("Y-m-d H:i:s")."\r\n", 3, '/tmp/hardware-server.log' );
		exit;
	}
	
	socket_listen( $sock );      						// 监听端口
	echo "hareware_server is running!\t\t".date("Y-m-d H:i:s")."\r\n";
	
	$sock_ids = array();								// 对应每个连接进来的 sock
	$order_chain = array();								// 对应每个解码成功的指令
	
    while(TRUE) {
		
		$read = gen_sock_chain( $sock_ids, $sock );
		
        if( socket_select($read, $write=NULL, $except=NULL, NULL)<1 )
            continue;
		
        if( in_array($sock, $read) ) {
            $mid = new sock_info();
			$mid->sock = socket_accept( $sock );
			$mid->lt = time();
			$sock_ids[] = $mid;
			
            $key = array_search( $sock, $read );
            unset( $read[$key] );
        }

		// loop through all the clients that have data to read from
        foreach( $read as $read_sock ) {

            $data = @socket_read( $read_sock, 1024*5, PHP_BINARY_READ );
			$key = search_sock( $sock_ids, $read_sock );
			$sock_ids[$key]->lt = time();
			
            // check if the client is disconnected
            if( $data===false ) {
				unset( $sock_ids[$key] );
				socket_close( $read_sock );

				echo "client disconnected!\t\t".date("Y-m-d H:i:s")."\r\n";	
                continue;
            }
			else {
				if( !empty( $data ) ) {
					if( empty($sock_ids[$key]->recv) )
						$sock_ids[$key]->recv = $data;
					else
						$sock_ids[$key]->recv .= $data;	
					
					// 处理接收到的指令
					$one_client_order = decode_order( $data );
					foreach( $one_client_order as $k => $v ) {
						if( $v->id=='web' ) {							// 来自服务器,立即回复
							$buff = "[web,".$v->t.",".$v->op.",GOT]";
							socket_write( $read_sock, $buff );
							
							if( $v->op!='OPEN' && $v->op!='CLOSE' )
								unset( $one_client_order[$k] );	
							
						}
						else {					// 处理来自硬件的反馈数据(指令反馈、心跳、读状态)
							$buff = '';
							switch($v->op) {
								case '6':			// 设备心跳
								case '3':			// 控制指令返回
									unset( $one_client_order[$k] );	
									break;
								
								case '0':			// 设备请求读状态,返回[id,1,xxxxC]
									$buff = "[".$v->id.",1,0000C]";
									unset( $one_client_order[$k] );
									break;
								
								case '4':			// 开箱异常,返回[ID,5,AAAAC]
									$buff = "[".$v->id.",5,AAAAC]";
									unset( $one_client_order[$k] );	
									break;
								
								default:
									unset( $one_client_order[$k] );	
									break;
							}
							
							if( !empty($buff) )
								socket_write( $read_sock, $buff );
						}
					}
					
					$order_chain = array_merge( $order_chain, $one_client_order );
					
					//echo "e1-\tclient send: ".$ds[$k2]."\t".date("Y-m-d H:i:s")."\r\n";
				}
				else {
					// 添加数据处理
					socket_close( $read_sock );
					unset( $sock_ids[$key] );
				}
					
			}
			
		}			
		
		echo "\t\tsockets num:".count($sock_ids)."\r\n";
		
		// 统一处理设备控制指令（实际发送控制指令）
			
		// 轮询数据表（实际发送控制指令）
		
		// 检查清理 socket 超时（不操作数据库，不发送指令）
		clear_timeout_socket( $sock_ids );
		echo "\t\tafer clear sockets num:".count(sock_ids)."\r\n";
		
		$order_chain = [];
	}
	
	socket_close( $sock );

?>