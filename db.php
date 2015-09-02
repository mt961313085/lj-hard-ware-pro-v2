<?php
/*
* db 单例
*/
class db{
    private $host; //数据库主机
    private $user; //数据库用户名
    private $pwd; //数据库用户名密码
    private $database; //数据库名
    private $charset = 'utf8'; //数据库编码，GBK,UTF8,gb2312
    private $link;             //数据库连接标识;
    private $rows;             //查询获取的多行数组
    //static $_instance; //存储对象
    /**
     * 构造函数
     * 私有
     */
    public function __construct($config, $pconnect=false) {
        $this->config = $config;
        if (!$pconnect) {
            $this->link = mysql_connect($this->config["dbhost"], $this->config["dbuser"], $this->config["dbpwd"]);
        } else {
            $this->link = mysql_pconnect($this->host, $this->user, $this->pwd);
        }
        mysql_select_db($this->config["database"]);
        $this->query("SET NAMES '{$this->charset}'", $this->link);
        return $this->link;
    }

    //查询 
    public function query($sql) {
        $this->write_log("查询 ".$sql);
        $query = mysql_query($sql,$this->link);
        if(!$query) $this->halt('Query Error: ' . $sql);
        return $query;
    }
     
    //获取一条记录（MYSQL_ASSOC，MYSQL_NUM，MYSQL_BOTH）              
    public function get_one($sql,$result_type = MYSQL_ASSOC) {
        $query = $this->query($sql);
        $rt =& mysql_fetch_array($query,$result_type);
        $this->write_log("获取一条记录 ".$sql);
        return $rt;
    }
 
    //获取全部记录
    public function get_all($sql,$result_type = MYSQL_ASSOC) {
        $query = $this->query($sql);
        $i = 0;
        $rt = array();
        while($row =& mysql_fetch_array($query,$result_type)) {
            $rt[$i]=$row;
            $i++;
        }
        $this->write_log("获取全部记录 ".$sql);
        return $rt;
    }
     
    //插入
    public function insert($table,$dataArray) {
        $field = "";
        $value = "";
        if( !is_array($dataArray) || count($dataArray)<=0) {
            $this->halt('没有要插入的数据');
            return false;
        }
        while(list($key,$val)=each($dataArray)) {
            $field .="$key,";
            $value .="'$val',";
        }
        $field = substr( $field,0,-1);
        $value = substr( $value,0,-1);
        $sql = "insert into $table($field) values($value)";
        $this->write_log("插入 ".$sql);
        if(!$this->query($sql)) return false;
        return true;
    }
 
    //更新
    public function update( $table,$dataArray,$condition="") {
        if( !is_array($dataArray) || count($dataArray)<=0) {
            $this->halt('没有要更新的数据');
            return false;
        }
        $value = "";
        while( list($key,$val) = each($dataArray))
        $value .= "$key = '$val',";
        $value .= substr( $value,0,-1);
        $sql = "update $table set $value where 1=1 and $condition";
        $this->write_log("更新 ".$sql);
        if(!$this->query($sql)) return false;
        return true;
    }
 
    //删除
    public function delete( $table,$condition="") {
        if( empty($condition) ) {
            $this->halt('没有设置删除的条件');
            return false;
        }
        $sql = "delete from $table where 1=1 and $condition";
        $this->write_log("删除 ".$sql);
        if(!$this->query($sql)) return false;
        return true;
    }
 
    //返回结果集
    public function fetch_array($query, $result_type = MYSQL_ASSOC){
        $this->write_log("返回结果集");
        return mysql_fetch_array($query, $result_type);
    }
 
    //获取记录条数
    public function num_rows($results) {
        if(!is_bool($results)) {
            $num = mysql_num_rows($results);
            $this->write_log("获取的记录条数为".$num);
            return $num;
        } else {
            return 0;
        }
    }
 
    //释放结果集
    public function free_result() {
        $void = func_get_args();
        foreach($void as $query) {
            if(is_resource($query) && get_resource_type($query) === 'mysql result') {
                return mysql_free_result($query);
            }
        }
        $this->write_log("释放结果集");
    }
 
    //获取最后插入的id
    public function insert_id() {
        $id = mysql_insert_id($this->link_id);
        $this->write_log("最后插入的id为".$id);
        return $id;
    }
 
    //关闭数据库连接
    public function close() {
        $this->write_log("已关闭数据库连接");
        return @mysql_close($this->link_id);
    }
 
    //错误提示
    private function halt($msg='') {
        $msg .= "\r\n".mysql_error();
        $this->write_log($msg);
        return $msg;
    }

    //写入日志文件
    public function write_log($msg=''){
        //echo "$msg";
        /*if($this->is_log){
            $text = date("Y-m-d H:i:s")." ".$msg."\r\n";
            fwrite($this->handle,$text);
        }*/
    }
}
/*//用例
$db = db::getInstance();
$db2 = db::getInstance();
$data = $db->getRows('select * from blog');
//print_r($data);
//判断两个对象是否相等
if($db === $db2){
    echo 'true';
}*/
?>