<?php
require_once( 'db.php' );
/*
$a = [0,1,2,3,4,5,6,7,8,9,10];

foreach( $a as $k => $v ) {
	echo "$v---";
	if( $v==5 )
		unset( $a[$k] );
}

echo "\r\n";

var_dump( $a );

	$read_data = "[1,2,3][4,5,6][7,8,9]";
	$z = '/\[[^\]]*\]/';
	$res = preg_match_all( $z, $read_data, $instruct );
	echo "$res\r\n";
	if( $res==false ){}
	$instruct = $instruct[0];
	var_dump( $instruct );

	echo decbin( hexdec('1a0') )."\r\n";
*/		

$config['dbhost'] = 'localhost'; 
$config['dbuser'] = 'root';
$config['dbpwd'] = 'blue';
$config['database'] = 'school_device_db';

$db = new db( $config );

$res = $db->get_all( 'select distinct ctrl from devices' );
var_dump( $res );
/*
$db->free_result();

$data = array('dev_state'=>3,'open_t'=>123);
$db->update( 'devices', $data, 'dev_id=00102' );

$res = $db->get_all( 'select * from devices' );

echo count($res)."\r\n";
echo count($res[0])."\r\n";
foreach( $res[0] as $k => $v ) {
	echo "$k-------$v----\r\n";
	
}
*/

$db->close();
echo time();
?>