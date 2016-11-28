<?php

include './libs/WsController.php';

class indexWsController extends WsController{

    public function getRoomList(){
        $dbh = new PDO('mysql:host=localhost;port=3306;dbname=chatdemo;charset=utf8','root','123456');
        $room_list = $dbh->query("SELECT id,room_name,room_desc,cover_img,user_num,user_num_limit,sort_num FROM chat_room "
                ."WHERE is_del=0 ORDER BY sort_num ASC");
        $data = $room_list->fetchAll(PDO::FETCH_ASSOC); 
        $dbh = null;
        return $data;
    }

    public function getRoomUsers($room_id=1){
        $dbh = new PDO('mysql:host=localhost;port=3306;dbname=chatdemo;charset=utf8','root','123456');
        $room_users = $dbh->query("SELECT cu.id,cu.`name`,cu.img FROM user_room AS ur JOIN chat_user AS cu ON ur.`user_id`=cu.`id` "
                ." WHERE cu.is_del=0 and ur.room_id={$room_id} ORDER BY ur.id ASC");
        $data = $room_users->fetchAll(PDO::FETCH_ASSOC); 
        $dbh = null;
        return $data;
    }

}
