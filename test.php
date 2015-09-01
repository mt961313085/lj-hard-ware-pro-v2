<?php
require_once( 'db.php' );

$config['dbhost'] = 'localhost'; 
$config['dbuser'] = 'root';
$config['dbpwd'] = 'blue';
$config['database'] = 'yunkadb';

$db = new db( $config );

// 测试 00101 00106 设备开, 30s 后关
// 不用开启硬件仿真终端, 不进行计费，超时后退出
// 硬件仿真终端进行指令正确响应，以 CLOSE 指令接收到的时间进行计费，进入fee-3或fee-4 计费模式
// 此情况下, 进入 fee-3 还是 fee-4，随机，由程序轮询时机确定，但两种模式下，计费结果相同；
echo "OPEN dev-00101\r\n";
$data = array( 'student_no'=>2, 'ins'=>'OPEN', 'dev_state'=>0, 'open_t'=>0, 'ins_recv_t'=>time(), 'state_recv_t'=>time() );
$db->update( 'devices_ctrl', $data, 'dev_id="00101"' );

web_send_ins( "[web,".time().",OPEN,00101]" );

sleep( 6 );
echo "OPEN dev-00106\r\n";
$data = array( 'student_no'=>2, 'ins'=>'OPEN', 'dev_state'=>0, 'open_t'=>0, 'ins_recv_t'=>time(), 'state_recv_t'=>time() );
$db->update( 'devices_ctrl', $data, 'dev_id="00106"' );

web_send_ins( "[web,".time().",OPEN,00106]" );

sleep( 30 );
$res = $db->get_all( 'select student_no, ins, ins_recv_t, dev_state, open_t from devices_ctrl where dev_id="00101" or dev_id="00106"' );
foreach( $res as $v0 ) {
	foreach( $v0 as $k => $v ) {
		echo "$k: $v\t\t";
	}
	echo "\r\n";
}
echo "\r\n";
$db->free_result();	

echo "CLOSE dev-00101\r\n";
$data = array( 'student_no'=>2, 'ins'=>'CLOSE', 'ins_recv_t'=>time() );
$db->update( 'devices_ctrl', $data, 'dev_id="00101"' );

web_send_ins( "[web,".time().",CLOSE,00101]" );

echo "CLOSE dev-00106\r\n";
$data = array( 'student_no'=>2, 'ins'=>'CLOSE', 'ins_recv_t'=>time() );
$db->update( 'devices_ctrl', $data, 'dev_id="00106"' );

web_send_ins( "[web,".time().",CLOSE,00106]" );

sleep( 10 );
$res = $db->get_all( 'select student_no, ins, ins_recv_t, dev_state, open_t from devices_ctrl where dev_id="00101" or dev_id="00106"' );
foreach( $res as $v0 ) {
	foreach( $v0 as $k => $v ) {
		echo "$k: $v\t\t";
	}
	echo "\r\n";
}


/*
// 测试 00102 设备开，并且进入三次重发
// 进行此项测试，必须将hard_ware.php反馈状态设为0000C
echo "OPEN dev-00102\r\n";
$data = array( 'student_no'=>2, 'ins'=>'OPEN', 'dev_state'=>0, 'open_t'=>0, 'ins_recv_t'=>time(), 'ins_send_t'=>0 );
$db->update( 'devices_ctrl', $data, 'dev_id="00102"' );

web_send_ins( "[web,".time().",OPEN,00102]" );

sleep( 6 );
$res = $db->get_all( 'select student_no, ins, ins_recv_t, dev_state, open_t from devices_ctrl where dev_id="00103"' );
foreach( $res as $v0 ) {
	foreach( $v0 as $k => $v ) {
		echo "$k: $v\t\t";
	}
	echo "\r\n";
}
*/

/*
// 测试 00103 设备正确开启，然后硬件异常关闭
// 开启硬件设备终端，且保持状态为0，则触发 fee-2 计费
// 不开硬件设备终端，则心跳超时机制处理，由 fee-1 计费
echo "OPEN dev-00103 successfully and then break \r\n";
$data = array( 'student_no'=>2, 'ins'=>'OPEN', 'dev_state'=>0, 'open_t'=>time(), 'ins_recv_t'=>time(), 'ins_send_t'=>0, 'state_recv_t'=>time() );
$db->update( 'devices_ctrl', $data, 'dev_id="00103"' );

sleep( 40 );
$res = $db->get_all( 'select student_no, ins, ins_recv_t, dev_state, open_t, close_t from devices_ctrl where dev_id="00103"' );
foreach( $res as $v0 ) {
	foreach( $v0 as $k => $v ) {
		echo "$k: $v\t\t";
	}
	echo "\r\n";
}
*/

/*
// 测试 00104 设备关闭，但状态为1，且硬件设备心跳超时
// 不开启硬件仿真终端程序
// 正常表现: 发送3次关闭指令，然后恢复为未占用模式，但设备状态为1；
//			触发 fee-4 计费;
//			系统再次重发几次关闭指令，然后将设置 remark=‘err’，标识设备故障
echo "CLOSE dev-00104 and dev_state always 1 \r\n";
$data = array( 'student_no'=>2, 'ins'=>'CLOSE', 'dev_state'=>1, 'open_t'=>time(), 'ins_recv_t'=>time(), 'ins_send_t'=>0, 'state_recv_t'=>time() );
$db->update( 'devices_ctrl', $data, 'dev_id="00104"' );

sleep( 60 );
$res = $db->get_all( 'select student_no, ins, ins_recv_t, dev_state, open_t, close_t from devices_ctrl where dev_id="00104"' );
foreach( $res as $v0 ) {
	foreach( $v0 as $k => $v ) {
		echo "$k: $v\t\t";
	}
	echo "\r\n";
}
*/
$db->close();

//-------------------------------------------------------------------------------

function web_send_ins( $buff ) {
	$fp = stream_socket_client( "tcp://127.0.0.1:2023", $errno, $errstr, 30 );
	stream_set_timeout( $fp, 3 );
	
	if (!$fp) {
		echo "$errstr ($errno)\r\n";
	} else {
		fwrite( $fp, $buff );
		echo "\t\t\t".fread($fp, 1024)."\r\n";
	
		fclose($fp);
	}
}




?>