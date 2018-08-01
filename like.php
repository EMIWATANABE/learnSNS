<?php
require_once("dbconnect.php");

$feed_id = $_POST["feed_id"];
$user_id = $_POST["user_id"];

if(isset($_POST["is_unlike"])){
    $sql = "DELETE FROM `likes` WHERE `user_id` =? and `feed_id` =?";
}else{
$sql = "INSERT INTO `likes` (`user_id`,`feed_id`) VALUES (?,?);";
}

$data = [$user_id,$feed_id];
$stmt = $dbh->prepare($sql);
$res = $stmt->execute($data);//うまくいくとtrue、失敗するとfalseが返ってくる

echo json_encode($res);

