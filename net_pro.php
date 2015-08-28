<?php
	require_once( 'db.php' );
	
	// 形成用于select的 socket 链
	function gen_sock_chain( $sock_ids, $socket ) {
		$res = array();	
		foreach( $sock_ids as $v ) {
			if( $v->sock>=0 )
				$res[] = $v->sock;
		}	
		$res[] = $socket;	
		return $res;	
	}
	
	// 在 $sock_ids 中查找 $socket，返回索引号
	function search_sock( $sock_ids, $socket ) {
		$res = -1;
		foreach( $sock_ids as $k => $v ) {
			if( $v->sock==$socket )
				return $k;
		}	
	}
	
	// 解码单条指令，指令格式为[XX,XX,XX,XX]
	function decode_order( $order ) {
		$z = '/\[[^\]]*\]/';
		$res = preg_match_all( $z, $order, $ins );
		if( $res==false )
			return '';
		
		$res = array();
		
		$ins = $ins[0];
		
		//拆分每个字段
		foreach( $ins as $k => $v ) {
			
			$mid = explode( ',', trim($v,'[]') );
			$mid_order = new order();
			
			switch( $mid[0] ) {
				case 'web':				// 从web端来
					if( count($mid)==4 ) {
						$mid_order->id = 'web';
						$mid_order->t = floatval( $mid[1] );
						$mid_order->op = $mid[2];
						$mid_order->locate = $mid[3];
					}
					else
						return '';
					break;
				
				default:				// 从硬件设备来
					$mid_order->id = $mid[0];
					$mid_order->op = $mid[1];
					$mid_order->state = $mid[2];			
					$mid_order->t = time();
					break;
			}
			
			$res[] = $mid_order;
		}
		
		return $res;
	}
	
	// 清除超时 socket
	function clear_timeout_socket( &$sock_ids ) {
		foreach( $sock_ids as $k => $v ) {
			if( (time()-$v->lt)>=30 )
				unset( $sock_ids[$k] );
		}
	}
	
	// 获取剔除控制器id后的设备id，int形式
	function decode_dev_id( $dev_id ) {
		$mid = substr( $dev_id, 3 );
		return intval($mid);
	}
//------------------------------------------------------------------------------------
// 							数据库相关操作
//------------------------------------------------------------------------------------

	function pro_dev_state( $dev_id, $dev_state ) {
		
		global $config;
		
		if( $dev_id=='web' )
			return;
		
		// 此时 $dev_id 为箱子号（3位）
		
		$state = substr( $dev_state, 0, strlen($dev_state)-1 );
		$state = decbin( hexdec($state) );
		$state = strrev( $state );
		$state = str_split( $state );
		
		$st = array_fill( 0, 16, 0 );
		foreach( $state as $k => $v )
			$st[$k] = $v;
			
		// $st 索引号+1, 才是对应的电磁阀号
		// 将状态写入数据库
		// dev_id, student_no, order, dev_state, order_recv
		
		$db = new db( $config );
		$res = $db->get_all( "SELECT * FROM devices WHERE ctrl='$dev_id'" );
		
		foreach( $res as $k => $v ) {
			$d_id = decode_dev_id( $v['dev_id'] );
			echo $d_id."\r\n";
		}

		$db->free_result();
		$db->close();

		
	}

?>