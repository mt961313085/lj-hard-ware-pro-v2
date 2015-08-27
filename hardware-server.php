<?php
	require_once( 'config.php' );
	require_once( 'net_pro.php' );
	
	class sock_info {
		public $sock = -1;
		public $lt = 0;
		public $c_id = '';					// 控制板编号, 或服务器连接编号
		public $recv = '';					// 收到的数据
	}
	
	pcntl_signal( SIGCHLD, SIG_IGN );
	date_default_timezone_set( 'Asia/Chongqing' );

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

            $data = @socket_read( $read_sock, 1400, PHP_BINARY_READ );
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
					
					// 处理来自web服务器的指令
					
					// 处理来自硬件的反馈数据(指令反馈、心跳、读状态)
					
					/*
					echo "e1-\tclient send: ".$ds[$k2]."\t".date("Y-m-d H:i:s")."\r\n";
					if( $ds[$k2]=="[001,0,0000c]" ) {
						$buff = "[001,1,0101c]";
						socket_write( $read_sock, $buff );
					}
					
					if( $ds[$k2]=="[001,6,0000c]" ) {
						$buff = "[001,3,000c]";
						socket_write( $read_sock, $buff );
					}
					
					$ds[$k2] = '';
					*/
					//$sock_ids[$key]->recv = ''
				}
				else {
					socket_close( $read_sock );
					unset( $sock_ids[$key] );
				}
					
			}
			
		}			
		 
		// 统一处理设备控制指令
		
		// 检查socket超时
		
		// 轮询数据表
	}
	
	socket_close( $sock );

?>