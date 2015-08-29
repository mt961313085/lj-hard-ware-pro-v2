<?php
require_once( 'db.php' );

$config['dbhost'] = 'localhost'; 
$config['dbuser'] = 'root';
$config['dbpwd'] = 'blue';
$config['database'] = 'school_device_db';

$db = new db( $config );
/*
// 测试 00101 00106 设备开, 30s 后关
echo "OPEN dev-00101\r\n";
$data = array( 'student_no'=>2, 'ins'=>'OPEN', 'dev_state'=>0, 'open_t'=>0, 'ins_recv_t'=>time() );
$db->update( 'devices', $data, 'dev_id="00101"' );

echo "OPEN dev-00106\r\n";
$data = array( 'student_no'=>2, 'ins'=>'OPEN', 'dev_state'=>0, 'open_t'=>0, 'ins_recv_t'=>time() );
$db->update( 'devices', $data, 'dev_id="00106"' );

sleep( 30 );
$res = $db->get_all( 'select student_no, ins, ins_recv_t, dev_state, open_t from devices where dev_id="00101" or dev_id="00106"' );
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
$db->update( 'devices', $data, 'dev_id="00101"' );

echo "CLOSE dev-00106\r\n";
$data = array( 'student_no'=>2, 'ins'=>'CLOSE', 'ins_recv_t'=>time() );
$db->update( 'devices', $data, 'dev_id="00106"' );

sleep( 10 );
$res = $db->get_all( 'select student_no, ins, ins_recv_t, dev_state, open_t from devices where dev_id="00101" or dev_id="00106"' );
foreach( $res as $v0 ) {
	foreach( $v0 as $k => $v ) {
		echo "$k: $v\t\t";
	}
	echo "\r\n";
}
*/

// 测试 00102 设备开，并且进入三次重发
// 进行此项测试，必须将hard_ware.php反馈状态设为0000C
echo "OPEN dev-00102\r\n";
$data = array( 'student_no'=>2, 'ins'=>'OPEN', 'dev_state'=>0, 'open_t'=>0, 'ins_recv_t'=>time() );
$db->update( 'devices', $data, 'dev_id="00102"' );
sleep( 6 );
$res = $db->get_all( 'select student_no, ins, ins_recv_t, dev_state, open_t from devices where dev_id="00102"' );
foreach( $res as $v0 ) {
	foreach( $v0 as $k => $v ) {
		echo "$k: $v\t\t";
	}
	echo "\r\n";
}
$db->close();
?>