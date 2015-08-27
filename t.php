<?php

/*
$a = [0,1,2,3,4,5,6,7,8,9,10];

foreach( $a as $k => $v ) {
	echo "$v---";
	if( $v==5 )
		unset( $a[$k] );
}

echo "\r\n";

var_dump( $a );
*/
	$read_data = "[1,2,3][4,5,6][7,8,9]";
	$z = '/\[[^\]]*\]/';
	$res = preg_match_all( $z, $read_data, $instruct );
	echo "$res\r\n";
	if( $res==false ){}
	$instruct = $instruct[0];
	var_dump( $instruct );
?>