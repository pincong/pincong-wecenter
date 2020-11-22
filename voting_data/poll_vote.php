
<?php

include("connect.php");
$id = htmlspecialchars($_REQUEST['id']);
$uid = htmlspecialchars($_REQUEST['uid']);
$value = htmlspecialchars($_REQUEST['value']);
$message = htmlspecialchars($_REQUEST['message']);
$sql = "select * from aws_voting where id = '$id'";
$result = mysqli_query($link, $sql);
$row = mysqli_fetch_assoc($result);
$message_count = explode(',', $row['message_count']);

if(empty($row['message_vote'])){
    $message_vote = array();  
}else{
    $message_vote = explode(',',$row['message_vote']);
}

if(!in_array($uid,$message_vote)){
    
    $message_count[$value] = $message_count[$value] + 1;
    array_push($message_vote, $uid);
    
    $message_count = implode(',', $message_count);
    $message_vote = implode(',', $message_vote);
    
    $sql = "update aws_voting set message_count = '$message_count', message_vote = '$message_vote' where id = '$id'";
    $message_count = explode(',', $message_count);
    $result = mysqli_query($link,$sql);
}

mysqli_close($link);

?>

<form>
    <?php
    $message_array = explode(",", $message);
    
    for($i = 0; $i < count($message_array);$i++){
        echo '<input type="radio" disabled />
                <label>'  . $message_array[$i] . " 票数: " . $message_count[$i] . '</label><br>';
    }
    
    ?>
</form>