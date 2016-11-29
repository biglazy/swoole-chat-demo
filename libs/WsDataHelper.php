<?php

class WsDataHelper{

    public static function getRoomList(){
        $dbh = new PDO('mysql:host=localhost;port=3306;dbname=chatdemo;charset=utf8','root','123456');
        $room_list = $dbh->query("SELECT id,room_name,room_desc,cover_img,user_num,user_num_limit,sort_num FROM chat_room "
                ."WHERE is_del=0 ORDER BY sort_num ASC");
        $data = $room_list->fetchAll(PDO::FETCH_ASSOC); 
        $dbh = null;
        return $data;
    }

    public static function getRoomUsers($room_id=0){
        $dbh = new PDO('mysql:host=localhost;port=3306;dbname=chatdemo;charset=utf8','root','123456');
        $room_users = $dbh->query("SELECT cu.id,cu.`name`,cu.img FROM user_room AS ur JOIN chat_user AS cu ON ur.`user_id`=cu.`id` "
                ." WHERE cu.is_del=0 and ur.room_id={$room_id} ORDER BY ur.id ASC");
        $data = $room_users->fetchAll(PDO::FETCH_ASSOC); 
        $dbh = null;
        return $data;
    }

    public static function getRoomLatestMsgs($room_id=0){
        $dbh = new PDO('mysql:host=localhost;port=3306;dbname=chatdemo;charset=utf8','root','123456');
        $room_msgs = $dbh->query("SELECT id,sender_id,sender_name,sender_img,msg_content,create_time FROM room_msg "
                ." WHERE is_del=0 AND room_id={$room_id} ORDER BY create_time DESC,id DESC LIMIT 0,10");
        $data = $room_msgs->fetchAll(PDO::FETCH_ASSOC); 
        $dbh = null;
        return $data;
    }

    public static function getUserInfo($user_id=0){
        $dbh = new PDO('mysql:host=localhost;port=3306;dbname=chatdemo;charset=utf8','root','123456');
        $room_msgs = $dbh->query("SELECT id,`name`,`img` FROM chat_user WHERE is_del=0 AND id={$user_id} ");
        $data = $room_msgs->fetch(PDO::FETCH_ASSOC); 
        $dbh = null;
        return $data;
    }

    public static function addMessage($msg){
        $dbh = new PDO('mysql:host=localhost;port=3306;dbname=chatdemo;charset=utf8','root','123456');
        $insert_sql = "INSERT INTO room_msg (`room_id`,`sender_id`,`sender_name`,`sender_img`,`msg_content`,`create_time`) "
                ." VALUES('{$msg['room_id']}','{$msg['sender_id']}','{$msg['sender_name']}','{$msg['sender_img']}','{$msg['msg_content']}','{$msg['create_time']}')";
        $count = $dbh->exec($insert_sql);
        $dbh = null;
        return $count;
    }
}
