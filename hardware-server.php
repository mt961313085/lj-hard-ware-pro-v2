<?php
/*
	web端指令格式为：[web,timestamp,operate,dev_id]
	其中，OPEN CLOSE操作已经事先写入数据库中
	
	收到web端指令，立即返回 [web, timestamp(同收到的指令), GOT]
*/
	require_once( 'config.php' );
	require_once( 'net_pro.php' );
	
	class sock_info {
		public $sock = -1;
		public $lt = 0;
		public $id = '';					// 控制板编号, 或服务器连接编号
	}
	
	class order {
		public $id = '';
		public $t = 0;
		public $op = '';
		public $state = '';
		public $dev_id = '';
	}
	
	pcntl_signal( SIGCHLD, SIG_IGN );
	date_default_timezone_set( 'Asia/Chongqing' );
	
	//pro_dev_state( '001', '1A01C' );
	//echo decode_dev_id('001012')."\r\n";
	check_db( ['001','002'] );
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
	$check_db_t = 0;
	
    while(TRUE) {
		
		$read = gen_sock_chain( $sock_ids, $sock );
		
        if( socket_select($read, $write=NULL, $except=NULL, 5)<1 )
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
					//echo "e1-\tclient send: ".$data."\t".date("Y-m-d H:i:s")."\r\n";
					// 处理接收到的指令
					$one_client_order = decode_order( $data );	
					
					if( empty($sock_ids[$key]->id) ) {				// 表明此socket是第一次发送数据
						$sock_ids[$key]->id = $one_client_order[0]->id;
						$sock_ids[$key]->sock = $read_sock;
					}
												
					pro_ins( $one_client_orde, $read_sock );				
					unset( $one_client_order );
				}
				else {
					socket_close( $read_sock );
					unset( $sock_ids[$key] );
				}	
			}
			
		}			
		
		echo "\t\tsockets num:".count($sock_ids)."\r\n";
		
		// 根据socket,检查是否有指令需要发送（实际发送控制指令）
		
		
		// 每5秒轮询数据表（实际发送控制指令）
		if( (time()-$check_db_t)>=5 ) {
			$check_db_t = time();
			// 检查全部数据库	
		}
		
		// 检查清理 socket 超时（不操作数据库，不发送指令）
		clear_timeout_socket( $sock_ids );
		echo "\t\tafer clear sockets num:".count(sock_ids)."\r\n";

	}
	
	socket_close( $sock );

?>