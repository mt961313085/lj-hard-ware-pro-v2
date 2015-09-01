<?php
	require_once( 'db.php' );
	
	$T_OUT = 16;		// 指令重发超时设置
	
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
	
	// $ctrl - 控制板唯一编号
	// 返回找到的 sock，否则返回空
	function search_sock_with_ctrl( $sock_ids, $ctrl ) {
		$res = -1;
		foreach( $sock_ids as $k => $v ) {
			if( $v->id==$ctrl )
				return $v->sock;
		}
		return NULL;
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
						$mid_order->dev_id = $mid[3];
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
			if( (time()-$v->lt)>=30 ) {
				socket_close( $v->sock );
				error_log( "\tcase-".$v->id." was timeout at\t".date('Y-m-d H:i:s')."\r\n", 3, 'error_log.txt' );
				unset( $sock_ids[$k] );
			}
		}
	}
	
	// 获取剔除控制器id后的设备id，int形式
	function decode_dev_id( $dev_id ) {
		$mid = substr( $dev_id, 3 );
		return intval($mid);
	}
	
	// 处理来自一个web或硬件连接的请求
	// 返回需要操作的控制板号
	function pro_ins( $one_client_order, $read_sock ) {
		
		$ctrl_ids = array();
		
		foreach( $one_client_order as $k => $v ) {
			if( $v->id=='web' ) {							// 来自服务器,立即回复
				$buff = "[web,".$v->t.",GOT]";
				socket_write( $read_sock, $buff );
				
				// 记录需要操作的设备编号
				// 如果需要，添加服务器读设备状态代码
				switch( $v->op ) {
					case 'OPEN':
					case 'CLOSE':
						$ctrl_ids[] = substr( $v->dev_id, 0, 3 );
						break;
				}
			}
			else {					// 处理来自硬件的反馈数据(指令反馈、心跳、读状态)
				
				$buff = '';
				switch($v->op) {
					case '6':			// 设备心跳
					case '3':			// 控制指令返回
						set_dev_state( $v->id, $v->state );
						$ctrl_ids[] = $v->id;
						break;
					
					case '0':			// 设备请求读状态,返回[id,1,xxxxC](未完成)
						set_dev_state( $v->id, $v->state );
						$state = read_hw_state( $v->id );
						$buff = "[".$v->id.",1,$state]";
						break;
					
					case '4':			// 开箱异常,返回[ID,5,AAAAC](未完成)
						$buff = "[".$v->id.",5,AAAAC]";
						error_log( "\t\t\tcase-".$v->id." was opened at\t".date('Y-m-d H:i:s')."\r\n", 3, 'error_log.txt' );
						break;
					
					default:
						break;
				}
				
				if( !empty($buff) )
					socket_write( $read_sock, $buff );
			}
		}
		return $ctrl_ids;
	}
//------------------------------------------------------------------------------------
// 							数据库相关操作
//------------------------------------------------------------------------------------
	// 从数据库中读取设备需要进入的状态
	// 即：如果 student_no==-1， 应为CLOSE-0;
	//	   否则，如果ins==OPEN， 应为 OPEN-1;
	//			 如果ins==CLOSE，应为 CLOSE-0;
	// 	函数返回16进制状态码，case状态用C占位;
	function read_hw_state( $case_id ) {
		
		global $config;
		
		$st = array_fill( 0, 16, 0 );
		
		$db = new db( $config );
		$res = $db->get_all( "SELECT dev_id, student_no, ins FROM devices_ctrl WHERE ctrl='$case_id'" );
		
		foreach( $res as $k => $v ) {
			$d_id = decode_dev_id( $v['dev_id'] );		// 设备在控制器内的id 
			if( $v['student_no']!='-1' && $v['ins']=='OPEN' )
				$st[$d_id-1] = 1;
		}
		
		$st = strrev( implode('',$st) );
		$st = sprintf( "%04XC", bindec($st) );
		return $st;
	}
	
	// 生成收费记录
	// $info - 用于计算费用的所有信息，关联数组类型
	// $type -用于记录生成此费用的途径，取值为 fee-1  fee-2  fee-3   fee-4
	// $type 用于确定 close_t， 并且用于调试
	function gen_fee_record( $info, $type ) {
		global $config;
		
		$db = new db( $config );
		$data = array( 'price'=>$info['price'], 'student_no'=>$info['student_no'], 'dev_id'=>$info['dev_id'], 'open_t'=>$info['open_t'] );
		$data['break_t'] = $info['break_t'];
		$data['fee_type'] = $type;
		
		switch( $type ) {
			case 'fee-1':			// 关闭时间为 time()-30
				if( $info['ins']=='OPEN' )
					$data['close_t'] = time() - 30;
				else
					$data['close_t'] = $info['ins_recv_t'];
				break;
			
			case 'fee-2':			// 关闭时间为 close_t
				$data['close_t'] = $info['close_t'];
				break;
				
			case 'fee-3':
			case 'fee-4':			// 关闭时间为 ins_recv_t
				$data['close_t'] = $info['ins_recv_t'];
				break;
			
			default:
				$db->close();
				return 0;
		}
		
		$data['sum_t'] = $data['close_t'] - $data['open_t'] - $data['break_t'];			// 单位：秒
		$data['fee'] = round( $data['price'] * $data['sum_t'] / 60 );					// 单位：分（金额）
		
		if( $info['dev_type']=='washer' && $data['sum_t']>2400 ) {			// 工作时常大于40分钟，就计费
			$data['fee'] = 400;												// 洗衣机，统一收取4元/50分钟
		}
			
		if( $data['fee']>0 )	
			$db->insert( 'fee_record', $data );
		
		$db->free_result();
		$db->close();
		
		return 1;
	}
	
	// 更新控制板 $dev_id 上设备状态
	function set_dev_state( $dev_id, $dev_state ) {
		
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
		// dev_id, student_no, ins, dev_state, ins_recv
		
		$db = new db( $config );
		$res = $db->get_all( "SELECT * FROM devices_ctrl WHERE ctrl='$dev_id'" );
		
		foreach( $res as $k => $v ) {
			$d_id = decode_dev_id( $v['dev_id'] );		// 设备在控制器内的id 
			$cur_state = $st[$d_id-1];					// 最新设备状态
			
			$con = "dev_id='".$v['dev_id']."'";
			$data = array( 'dev_state'=>$cur_state, 'state_recv_t'=>time() );
			
			switch( $cur_state ) {
				case '0':
					if( $v['dev_state']==1 && $v['student_no']!='-1' && $v['ins']=='CLOSE' && $v['close_t']==0 )
						$data['close_t'] = time();
					$db->update( 'devices_ctrl', $data, $con );
					break;
				
				case '1':
					if( $v['dev_state']==0 && $v['ins']=='OPEN' && $v['student_no']!='-1' ) {
						if( $v['open_t']==0 )
							$data['open_t'] = time();
						elseif( $v['close_t']>0 ) {				// 计算中断时间
							$data['break_t'] = time() - $v['close_t'] + $v['break_t'];
							$data['close_t'] = 0; 
						}	
					}
						
					$db->update( 'devices_ctrl', $data, $con );
					break;
			}
		}

		$db->free_result();
		$db->close();
	}
	
	// 数据库遍历，处理设备事务
	// $dev_ids - 需要处理的设备id，数组
	// 为空，则遍历全部设备，并且进行硬件设备连接超时处理
	function check_db( $dev_ids ) {
		
		global $config, $sock_ids;
		
		$db = new db( $config );
		
		$dev_ids = array_unique( $dev_ids );
		
		$timeout_check = 0;
		
		if( count($dev_ids)==0 ) {			// 遍历全部设备
			$timeout_check = 1;
			$mid = $db->get_all( 'select distinct ctrl from devices_ctrl' );
			$dev_ids = array();
			foreach( $mid as $v ) {
				$dev_ids[] = $v['ctrl'];
			}
		}

		if( count($dev_ids)>0 ) {									// 遍历指定设备
			$sql = 'SELECT * FROM devices_ctrl WHERE ';
			foreach( $dev_ids as $v ) {			// 以ctrl为单位遍历设备
				$sql_in = $sql."ctrl='$v'";
				$ins = array_fill( 0, 16, 0 );	// 用于设备控制
				
				$res = $db->get_all( $sql_in );
				$need_ctrl = 0;
				
				foreach( $res as $v2 ) {
					
					$d_id = decode_dev_id( $v2['dev_id'] ) - 1;  // 设备在控制器上的id，从1开始编号，共16个
					
					if( $v2['ins']=='OPEN' )
						$ins[$d_id] = 1;
					
					// 仅处理设备断线，心跳超时情况的处理
					if( $timeout_check==1 && (time()-$v2['state_recv_t'])>=30 ) {		// 如需要，首先进行设备连接中断处理
											
						if( $v2['student_no']!='-1' ) {
							
							// 产生计费，当前指令为 OPEN，关闭时间为 time()-30；为CLOSE 关闭时间为指令接收时间
							if( $v2['open_t']>0 && $v2['remark']!='gen_fee' ) {
								gen_fee_record( $v2, 'fee-1' );
								echo "\tfee-1: dev_id-".$v2['dev_id']."  open_t-".$v2['open_t']."  close_t-".(time()-30)."  ".time()."\r\n";
							}
							
							// 恢复设备至未占用状态
							$con = "dev_id='".$v2['dev_id']."'";
							$data = array('student_no'=>'-1','ins'=>'NONE','ins_recv_t'=>0,'ins_send_t'=>0,'open_t'=>0,'close_t'=>0,'break_t'=>0,'remark'=>'');
							$db->update( 'devices_ctrl', $data, $con );

						}
					}
				
					$need_ctrl = $need_ctrl || pro_dev_work( $v2, $db );
				}
								
				if( $need_ctrl ) {						// 需要控制
					$ins = strrev( implode('',$ins) );
					$buff = sprintf( "[$v,2,%04XC]", bindec($ins) );
					echo "instruct - $buff    $ins\r\n";
					// 根据 ctrl 查找 socket
					$socket = search_sock_with_ctrl( $sock_ids, $v );
					if( !empty($socket) ) {
						socket_write( $socket, $buff );
					}
				}
			}
		}
		
		$db->close();
	}
	
	// 根据设备状态,处理事务
	// $rec - 一个设备记录
	// 如果需要发送指令,返回 1; 否则返回0
	function pro_dev_work( $rec, $db ) {
		
		global $T_OUT;
		
		$need_ctrl = 0;
		$con = "dev_id='".$rec['dev_id']."'";
		
		switch( $rec['student_no'] ) {
			case -1:
				switch( $rec['dev_state'] ) {
					case 0:
						$sig = empty($rec['remark']) || empty($rec['ins_recv_t']) || empty($rec['ins_send_t']) || empty($rec['open_t']) || empty($rec['close_t']);
						if( $sig==1 ) {
							$data = array('remark'=>'', 'ins_recv_t'=>0, 'ins_send_t'=>0, 'open_t'=>0, 'close_t'=>0, 'ins'=>'NONE');
							$db->update( 'devices_ctrl', $data, $con );
						}
						break;
						
					case 1:
						if( $rec['ins_recv_t']==0 ) {
							$need_ctrl = 1;
							$db->update( 'devices_ctrl', array('ins_recv_t'=>time(),'ins_send_t'=>time()), $con );
						}
						else {
							if( (time()-$rec['ins_recv_t'])<=$T_OUT ) {
								if( (time()-$rec['ins_send_t'])>=5 ) {
									$data = array( 'ins_send_t'=>time() );
									$db->update( 'devices_ctrl', $data, $con );
									$need_ctrl = 1;
								}
							}
							else {			// 超时
								if( $rec['remark']=='' )
									$db->update( 'devices_ctrl', array('remark'=>'err'), $con );
							}
						}
						break;
				}
				break;
			
			default:
				switch( $rec['ins'] ) {
					case 'OPEN':
						if( $rec['dev_state']==0 ) {									// 中断计时，首次开启功能，逻辑复杂
							if( $rec['open_t']==0 ) {
								if( (time()-$rec['ins_recv_t'])<=$T_OUT ) {
									if( (time()-$rec['ins_send_t'])>=5 ) {
										$need_ctrl = 1;
										$data = array( 'ins_send_t'=>time() );
										$db->update( 'devices_ctrl', $data, $con );
									}
								}
								else {					// 超时			
									// 恢复设备至未占用状态
									echo "timeout--".time()."---".$rec['ins_recv_t']."----".$rec['ins_send_t']."\r\n";
									$data = array('student_no'=>'-1','ins'=>'NONE','ins_recv_t'=>0,'ins_send_t'=>0,'open_t'=>0,'close_t'=>0,'break_t'=>0,'remark'=>'');
									$db->update( 'devices_ctrl', $data, $con );
								}	
							}
							else {						// open_t>0  开启后又异常中断
								if( $rec['close_t']<=0 ) {
									$data = array( 'close_t'=>time() );
									$db->update( 'devices_ctrl', $data, $con );
								}
								else {
									// 仅处理设备正常发送心跳，但控制状态不对时的处理
									if( (time()-$rec['close_t'])>30 && (time()-$rec['state_recv_t'])<30 ) {
										
										// 产生计费 关闭时间为 close_t
										if( $rec['open_t']>0 ) {
											gen_fee_record( $rec, 'fee-2' );
											echo "\tfee-2: dev_id-".$rec['dev_id']."  open_t-".$rec['open_t']."  close_t-".$rec['close_t']."  ".time()."\r\n";
										}
										
										// 恢复设备至未占用状态
										$data = array('student_no'=>'-1','ins'=>'NONE','ins_recv_t'=>0,'ins_send_t'=>0,'open_t'=>0,'close_t'=>0,'break_t'=>0,'remark'=>'');
										$db->update( 'devices_ctrl', $data, $con );
										
									}
								}
							}
						}
						else {					// dev_state==1
							if( (time()-$rec['open_t'])>=$rec['pre_close_t'] ) {	// 开启时间超过最大允许开启时间
								$need_ctrl = 1;
								$data = array( 'ins'=>'CLOSE', 'ins_recv_t'=>time(), 'ins_send_t'=>time() );
								$db->update( 'devices_ctrl', $data, $con );
							}
						}
						break;
					
					case 'CLOSE':
						if( $rec['dev_state']==0 ) {
							
							// 产生计费，close_t - open_t
							// 仅处理硬件设备正常连接时的费用处理
							if( (time()-$rec['state_recv_t'])<30 && $rec['open_t']>0 && $rec['remark']!='gen_fee' ) {
								gen_fee_record( $rec, 'fee-3' );
								echo "\t\tfee-3: dev_id-".$rec['dev_id']."  open_t-".$rec['open_t']."  close_t-".$rec['close_t']."\r\n";
							}
							
							// 恢复设备至未占用状态
							$data = array('student_no'=>'-1','ins'=>'NONE','ins_recv_t'=>0,'ins_send_t'=>0,'open_t'=>0,'close_t'=>0,'break_t'=>0,'remark'=>'');
							$db->update( 'devices_ctrl', $data, $con );
						}
						else {
							
							// 产生计费 关闭时间为 ins_recv_t
							// 仅处理硬件设备正常连接时的费用处理						
							if( $rec['remark']!='gen_fee' && (time()-$rec['state_recv_t'])<30 && $rec['open_t']>0 ) {
									$data = array( 'remark'=>'gen_fee' );
									$db->update( 'devices_ctrl', $data, $con );
									
									gen_fee_record( $rec, 'fee-4' );
									echo "\t\tfee-4: dev_id-".$rec['dev_id']."  ins_recv_t-".$rec['ins_recv_t']."  open_t-".$rec['open_t']."  close_t-".$rec['ins_recv_t']."\r\n";	
							}
								
							if( (time()-$rec['ins_recv_t'])<=$T_OUT ) {
								if( (time()-$rec['ins_send_t'])>=5 ) {
									$need_ctrl = 1;
									$data = array( 'ins_send_t'=>time() );
									$db->update( 'devices_ctrl', $data, $con );
								}
							}
							else {					// 超时
								// 恢复设备至未占用状态
								$data = array('student_no'=>'-1','ins'=>'NONE','ins_recv_t'=>0,'ins_send_t'=>0,'open_t'=>0,'close_t'=>0,'break_t'=>0,'remark'=>'');
								$db->update( 'devices_ctrl', $data, $con );
							}
						}
						break;
				}
				break;
		}
		
		return $need_ctrl;
	}
?>