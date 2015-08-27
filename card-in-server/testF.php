<?php
$socket=socket_create(AF_INET, SOCK_STREAM,SOL_TCP);
//socket_set_option($socket,SOL_SOCKET, SO_RCVTIMEO, array("sec"=>3, "usec"=>0)); 
socket_connect($socket, '10.71.29.51','2023') or die('connect failed').socket_strerror(socket_last_error());
$timestamp = time();
$buffer='['.$timestamp.',OPEN,J61021]';
echo 'I am web server'."\r\n";
	if(socket_write($socket, $buffer,strlen($buffer)))
		echo "send:".$buffer."\r\n";
// 	$buffer='[F03,2,02AB0]';
// 	if(socket_write($socket, $buffer,strlen($buffer)))
// 		echo $buffer."\r\n";
while(TRUE){
	
	$buf=socket_read($socket,1024);
	if($buf)
	{
		echo 'recive'.$buf."\r\n";
	}
	else {
		echo 'no message!';
	}
		
		
}