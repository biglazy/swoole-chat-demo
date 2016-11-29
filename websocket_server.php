<?php
    $websocket = new swoole_websocket_server("0.0.0.0",9502);

    // 此选项在websocket下表现不稳定：<=10时，基本准确；>10时，误差非常大；???
    //$websocket->set(array('max_request'=>10));

    $websocket->on('open',function(swoole_websocket_server $server,$request){
        echo "server: handshake success with fd{$request->fd}\n";
    });

    $websocket->on('message',function(swoole_websocket_server $server,$frame){
        $frame_arr = json_decode($frame->data,true); 
        $route = $frame_arr['route'];
        $request = $frame_arr['request'];
        include_once __DIR__.'/libs/WsDataHelper.php';

        if($route == 'room_list'){
            $server->channels['room_list']->set($frame->fd,array('fd'=>$frame->fd));
        }elseif($route == 'room_users'){
            $cur_room_id = $request['room_id'];
            $room_list = WsDataHelper::getRoomList();
            foreach($room_list as $room){
                if($cur_room_id == $room['id']){
                    $server->channels['room_users:'.$room['id']]->set($frame->fd,array('fd'=>$frame->fd));
                }else{
                    $server->channels['room_users:'.$room['id']]->del($frame->fd);
                }
            }
        }elseif($route == 'msg_list'){
            $cur_room_id = $request['room_id'];
            // 立即返回最近的消息
            $latest_msgs = WsDataHelper::getRoomLatestMsgs($cur_room_id);
            $msgs_json = json_encode(array('route' => 'msg_list','data'=>$latest_msgs,'room_id'=>$cur_room_id));
            $server->push($frame->fd,$msgs_json);
            // 将当前fd加入指定room_id的消息推送列表中
            $room_list = WsDataHelper::getRoomList();
            foreach($room_list as $room){
                if($cur_room_id == $room['id']){
                    $server->channels['room_msgs:'.$room['id']]->set($frame->fd,array('fd'=>$frame->fd));
                }else{
                    $server->channels['room_msgs:'.$room['id']]->del($frame->fd);
                }
            }
        }elseif($route == 'msg_add'){
            $cur_room_id = $request['room_id'];
            $sender_id = $request['sender_id'];
            $sender_info = WsDataHelper::getUserInfo($sender_id);
            $msg['room_id'] = $request['room_id'];
            $msg['sender_id'] = $sender_id;
            $msg['sender_name'] = $sender_info['name'];
            $msg['sender_img'] = $sender_info['img'];
            $msg['msg_content'] = $request['msg_content'];
            $msg['create_time'] = time();

            // 添加数据到数据库中  
            $rst = WsDataHelper::addMessage($msg); 
            if($rst){
                // 推送收到的消息，到推送列表中的fd
                $msg_json = json_encode(array('route' => 'msg_add','data'=>$msg,'room_id'=>$cur_room_id));
                if(isset($server->channels['room_msgs:'.$cur_room_id])){
                    foreach($server->channels['room_msgs:'.$cur_room_id] as $item){
                        $server->push($item['fd'],$msg_json);
                    }
                }
            }
        }else{
            // do nothing.
        }
    });

    $websocket->on('close',function($ser,$fd){
        echo "client {$fd} closed\n";
        $ser->channels['room_list']->del($fd);

        // 连接关闭时，如果其它channel中也保存了当前连接fd，需要一同删除
        include_once __DIR__.'/libs/WsDataHelper.php';
        $room_list = WsDataHelper::getRoomList();
        foreach($room_list as $room){
            $room_id = $room['id'];
            $ser->channels['room_users:'.$room_id]->del($fd);
            $ser->channels['room_msgs:'.$room_id]->del($fd);
        }
    });
    // 定时器使用测试:定时更新room_list内容的更新；
    $websocket->on('WorkerStart',function(swoole_websocket_server $server,$worker_id){
        if($worker_id == 0){
            swoole_timer_tick(3000,function($timer_id)use($server){
                include_once __DIR__.'/libs/WsDataHelper.php';
                $room_list = WsDataHelper::getRoomList();
                $json = json_encode(array('route' => 'room_list','data'=>$room_list));
                foreach($server->channels['room_list'] as $item){
                    $server->push($item['fd'],$json);
                }
                foreach($room_list as $room){
                    $room_id = $room['id'];
                    $room_users = WsDataHelper::getRoomUsers($room_id);
                    $users_json = json_encode(array('route' => 'room_users','data'=>$room_users,'room_id'=>$room_id));
                    if(isset($server->channels['room_users:'.$room_id])){
                        foreach($server->channels['room_users:'.$room_id] as $item){
                            $server->push($item['fd'],$users_json);
                        }
                    }
                }
            });
        }
    });

    $websocket->channels = [];

    $room_list_channel = new swoole_table(1024);    
    $room_list_channel->column('fd',swoole_table::TYPE_INT);
    $room_list_channel->create();
    $websocket->channels['room_list'] = $room_list_channel;

    include_once __DIR__.'/libs/WsDataHelper.php';
    $room_list = WsDataHelper::getRoomList();
    foreach($room_list as $room){
        // 存储需要推送room_users信息的fd
        $channel1 = new swoole_table(1024);    
        $channel1->column('fd',swoole_table::TYPE_INT);
        $channel1->create();
        $websocket->channels['room_users:'.$room['id']] = $channel1;
        // 存储需要推送room_msgs信息的fd
        $channel2 = new swoole_table(1024);    
        $channel2->column('fd',swoole_table::TYPE_INT);
        $channel2->create();
        $websocket->channels['room_msgs:'.$room['id']] = $channel2;
    }

    $websocket->start();



