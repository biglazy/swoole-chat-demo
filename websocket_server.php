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

        if($route == 'room_list'){
            $server->channels['room_list']->set($frame->fd,array('fd'=>$frame->fd));
        }elseif($route == 'room_users'){
            $cur_room_id = $request['room_id'];
            include_once __DIR__.'/controllers/indexWsController.php';
            $wsController = new indexWsController();
            $room_list = $wsController->getRoomList();
            foreach($room_list as $room){
                if($cur_room_id == $room['id']){
                    $server->channels['room_users:'.$room['id']]->set($frame->fd,array('fd'=>$frame->fd));
                }else{
                    $server->channels['room_users:'.$room['id']]->del($frame->fd);
                }
            }
        }elseif($route == 'msg_list'){
            // todo : 
        }else{
            // return nothing.
        }
    });

    $websocket->on('close',function($ser,$fd){
        echo "client {$fd} closed\n";
        $ser->channels['room_list']->del($fd);

        // 连接关闭时，如果其它channel中也保存了当前连接fd，需要一同删除
        include_once __DIR__.'/controllers/indexWsController.php';
        $wsController = new indexWsController();
        $room_list = $wsController->getRoomList();
        foreach($room_list as $room){
            $room_id = $room['id'];
            $ser->channels['room_users:'.$room_id]->del($fd);
        }
    });

    $websocket->on('WorkerStart',function(swoole_websocket_server $server,$worker_id){
        if($worker_id == 0){
            swoole_timer_tick(1500,function($timer_id)use($server){
                include_once __DIR__.'/controllers/indexWsController.php';
                $wsController = new indexWsController();
                $room_list = $wsController->getRoomList();
                $json = json_encode(array('route' => 'room_list','data'=>$room_list));
                foreach($server->channels['room_list'] as $item){
                    $server->push($item['fd'],$json);
                }
                foreach($room_list as $room){
                    $room_id = $room['id'];
                    $room_users = $wsController->getRoomUsers($room_id);
                    $json = json_encode(array('route' => 'room_users','data'=>$room_users,'room_id'=>$room_id));
                    if(isset($server->channels['room_users:'.$room_id])){
                        foreach($server->channels['room_users:'.$room_id] as $item){
                            $server->push($item['fd'],$json);
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

    include_once __DIR__.'/controllers/indexWsController.php';
    $wsController = new indexWsController();
    $room_list = $wsController->getRoomList();
    foreach($room_list as $room){
        $channel = new swoole_table(1024);    
        $channel->column('fd',swoole_table::TYPE_INT);
        $channel->create();
        $websocket->channels['room_users:'.$room['id']] = $channel;
    }

    $websocket->start();



