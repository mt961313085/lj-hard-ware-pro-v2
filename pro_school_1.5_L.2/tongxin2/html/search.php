<?php
set_time_limit(0);

date_default_timezone_set("Asia/Shanghai");

$val=@$_POST['name'];

/*if($val='name')
	exit();*/
//echo "haha\r\n";
$ipconfig=parse_ini_file("ipconfig.ini");

$ip=$ipconfig['ip'];

$port=$ipconfig['port'];

$socket=socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

socket_set_option( $socket, SOL_SOCKET, SO_RCVTIMEO, array("sec"=>3, "usec"=>0 ) );

$con=socket_connect($socket,$ip,$port);

$buffer="[FIN]";

socket_write($socket, $buffer,strlen($buffer));

$data=socket_read($socket,1024);

$data=trim($data,"[]");

$name="";

for($i=0;$i<25;$i++){

		$j=$i+1;

		$name['ID'.$j]=$data[$i];
}

echo $result=json_encode($name);
//echo "*****************\r\n";


?>
