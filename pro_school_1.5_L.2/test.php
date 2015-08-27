<?php
/*$a[26]=array();
$a[5][]=13;
$a[5][]=3;
$a[5][]=7;
$a[5][]=8;
$str='0000000000000000';
foreach ($a[5] as $key => $value) {
	echo $value."\r\n";
	$str=substr_replace($str,'1',-($value),1);
	
}
echo $str;
*/
/*$i[03][]='5';
$i[02][]='6';
$i[03][]='3';
$i[2][]='4';
print_r($i);
foreach ($i as $key => $value) {
	//print_r($key);
	foreach ($value as $val) {
		echo $val."\r\n";
	}
}*/
/*$str="[1111111111111111111111111]]";
$str=trim($str,"[]");
//$data=trim($data,"[]");
	for($i=0;$i<25;$i++){
		$j=$i+1;
		$name["ID$j"]=$str[$i];
	}
	echo $result=json_encode($a);
echo $str;*/
for($j=1;$j<8;$j++){
	for($i=1;$i<=26;$i++){
		$time=$i.$j.time();
		$temp=$j.str_pad($i,2,'0',STR_PAD_LEFT);
		error_log("[$time,OPEN,$temp]",3,'checkopen.txt');
		error_log("[$time,CLOSE,$temp]",3,'checkclose.txt');
		error_log("[$time,READ,$temp]",3,'checkread.txt');
	}
}
for($j=2;$j<=7;$j++){
	for($i=1;$i<=4;$i++){
		$time=$i.$j.time();
		error_log("[$time,OPEN,$j"."L$i]",3,'checkopen.txt');
		error_log("[$time,CLOSE,$j"."L$i]",3,'checkclose.txt');
		error_log("[$time,READ,$temp]",3,'checkread.txt');
	}
}