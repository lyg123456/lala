<?php

//优化方案1：将库存字段number字段设为unsigned，

//当库存为0时，因为字段不能为负数，将会返回false

include('./mysql.php');

$username = 'wang'.rand(0,1000);

//生成唯一订单

function build_order_no(){

  return date('ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);

}

//记录日志

function insertLog($event,$type=0,$username){

    global $conn;

    $sql="insert into ih_log(event,type,usernma)    values('$event','$type','$username')";

    return mysqli_query($conn,$sql);

}

function insertOrder($order_sn,$user_id,$goods_id,$sku_id,$price,$username,$number)

{

      global $conn;

      $sql="insert into ih_order(order_sn,user_id,goods_id,sku_id,price,username,number)
	  values('$order_sn','$user_id','$goods_id','$sku_id','$price','$username','$number')";

     return  mysqli_query($conn,$sql);

}

//模拟下单操作

//库存是否大于0

$sql="select number from ih_store where goods_id='$goods_id' and sku_id='$sku_id' ";

$rs=mysqli_query($conn,$sql);

$row = $rs->fetch_assoc();

  if($row['number']>0){//高并发下会导致超卖

      if($row['number']<$number){

        return insertLog('库存不够',3,$username);

      }

      $order_sn=build_order_no();

      //库存减少

      $sql="update ih_store set number=number-{$number} where sku_id='$sku_id' and number>0";

      $store_rs=mysqli_query($conn,$sql);

      if($store_rs){

          //生成订单

          insertOrder($order_sn,$user_id,$goods_id,$sku_id,$price,$username,$number);

          insertLog('库存减少成功',1,$username);

      }else{

          insertLog('库存减少失败',2,$username);

      }

  }else{

      insertLog('库存不够',3,$username);

  }

?>