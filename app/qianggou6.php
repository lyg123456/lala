<?php

/*
用户抢购成功后，上面的我们也可以稍微优化下，比如我们可用将用户ID存入了order:1列表中。
接下来我们可以引导这些用户去完成订单的其他步骤，到这里才涉及到与数据库的交互。
最终只有很少的人走到这一步吧，也就解决的数据库的压力问题。
我们再改下上面的代码：

*/
$user_id =  \Session::get('user_id');//当前抢购用户id
/* 模拟抢购操作,抢购前判断redis队列库存量 */
$count=\Redis::lpop('goods_store:1');
if(!$count)
　　return '已经抢光了哦';

$result = \Redis::lpush('order:1',$user_id);
if($result)
　　return '恭喜您!抢到了哦';

?>

<?php
//抢购、描述逻辑
$conn=mysql_connect("localhost","big","123456"); 
if(!$conn){ 
    echo "connect failed"; 
    exit; 
} 
mysql_select_db("big",$conn); 
mysql_query("set names utf8");
 
$price=10;
$user_id=1;
$goods_id=1;
$sku_id=11;
$number=1;
 
//生成唯一订单号
function build_order_no(){
  return date('ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
}
//记录日志
function insertLog($event,$type=0){
    global $conn;
    $sql="insert into ih_log(event,type) 
    values('$event','$type')"; 
    mysql_query($sql,$conn); 
}
 
//模拟下单操作
//下单前判断redis队列库存量
$redis=new Redis();
$result=$redis->connect('127.0.0.1',6379);
$count=$redis->lpop('goods_store');
if(!$count){
    insertLog('error:no store redis');
    return;
}
 
//生成订单 
$order_sn=build_order_no();
$sql="insert into ih_order(order_sn,user_id,goods_id,sku_id,price) 
values('$order_sn','$user_id','$goods_id','$sku_id','$price')"; 
$order_rs=mysql_query($sql,$conn); 
 
//库存减少
$sql="update ih_store set number=number-{$number} where sku_id='$sku_id'";
$store_rs=mysql_query($sql,$conn); 
if(mysql_affected_rows()){ 
    insertLog('库存减少成功');
}else{ 
    insertLog('库存减少失败');
}