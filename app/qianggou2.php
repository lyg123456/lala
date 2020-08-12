<?php

//优化方案2：使用MySQL的事务，锁住操作的行

include('./mysql.php');

//生成唯一订单号

function build_order_no(){

  return date('ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);

}

//记录日志

function insertLog($event,$type=0){

    global $conn;

    $sql="insert into ih_log(event,type)    values('$event','$type')";

    mysqli_query($conn,$sql);

}

//模拟下单操作

//库存是否大于0

mysqli_query($conn,"BEGIN");  //开始事务

$sql="select number from ih_store where goods_id='$goods_id' and sku_id='$sku_id' FOR UPDATE";

//此时这条记录被锁住,其它事务必须等待此次事务提交后才能执行

$rs=mysqli_query($conn,$sql);

$row=$rs->fetch_assoc();

if($row['number']>0){

    //生成订单

    $order_sn=build_order_no();

    $sql="insert into ih_order(order_sn,user_id,goods_id,sku_id,price)

    values('$order_sn','$user_id','$goods_id','$sku_id','$price')";

    $order_rs=mysqli_query($conn,$sql);

    //库存减少

    $sql="update ih_store set number=number-{$number} where sku_id='$sku_id'";

    $store_rs=mysqli_query($conn,$sql);

    if($store_rs){

      echo '库存减少成功';

        insertLog('库存减少成功');

        mysqli_query($conn,"COMMIT");//事务提交即解锁

    }else{

      echo '库存减少失败';

        insertLog('库存减少失败');

    }

}else{

  echo '库存不够';

    insertLog('库存不够');

    mysqli_query($conn,"ROLLBACK");

}

?>