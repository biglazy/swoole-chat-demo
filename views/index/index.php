<html>
<head>
    <meta http-equiv="content-type" content="txt/html; charset=utf-8" />
    <title>swoole-chat-demo</title>
    <style>
        #msg_box {width:620px;height:550px;padding:10px;border:2px solid grey;}
        #user_list {width:200px;height:100%;float:left;border:1px solid silver;}
        #user_list img {width:30px;height:30px;}
        #msg_list {width:400px;height:100%;margin-left:10px;float:left;border:1px solid silver;}
        #history_msg {width:400px;height:415px;border:1px solid silver;}
        #history_msg ul {padding:10px;margin:0;overflow-x:hidden;height:395px;}
        #history_msg ul li {border:1px solid grey;list-style:none;margin:5px;padding:5px;}
        #history_msg ul li img {width:30px;height:30px;}
        #input_msg textarea{width:400px;height:100px;margin-top:33px;}
        ul li {list-style:none;}
        ul li a.cur {color:red;}
        .clear {clear:both;}
    </style>
</head>
<body>
    <h2>swoole-chat-demo</h2>
    <hr/>
    <strong><span>Chat room list:</span></strong>
    <div id="room_list">
        <ul>
        <?php 
            $dbh = new PDO('mysql:host=localhost;port=3306;dbname=chatdemo;charset=utf8','root','123456');
            $room_list = $dbh->query("SELECT * FROM chat_room WHERE is_del=0 ORDER BY id ASC");
            foreach($room_list as $index=>$room){
                echo "<li room_id='{$room['id']}'>".($index+1).". <a href='javascript:;' class='room'>"
                    ."{$room['room_name']} ({$room['user_num']}/{$room['user_num_limit']})</a></li>";
            }
            $dbh = null;
        ?>
        </ul>
    </div>

    <div id="msg_box">
        <div id="user_list">
            <ul></ul>
        </div>
        <div id="msg_list">
            <div id="history_msg">
                <ul></ul>
            </div>
            <div id="input_msg">
                <textarea placehoder="这里输入聊天内容" disabled="true"></textarea>
            </div>
        </div>
        <div class="clear"></div>
    </div>
    <script src="//cdn.bootcss.com/jquery/2.2.4/jquery.min.js"></script>
    <script>
        var cur_room_id = '';
        var ws = new WebSocket('ws://127.0.0.1:9502'); 
        ws.onopen = function(event){
            console.log('Connected to websocket server.');
            ws.send(JSON.stringify({route:'room_list',request:''}));
        };
        ws.onclose = function(event){
            console.log('Disconnected.');
        };
        ws.onmessage = function(event){
            console.log('Retrieved data from server:'+event.data);
            var msg = JSON.parse(event.data);
            var route = msg.route;
            var data = msg.data;
            switch(route){
                case 'room_list':
                    $('#room_list ul').empty();
                    var el_str = '';
                    for(var i=0;i<data.length;i++){
                        var cur_str = '';
                        if(cur_room_id == data[i]['id']){
                            cur_str = ' cur ';
                        }
                        el_str += '<li room_id="' +data[i]['id']+ '">' +(i+1)+ '. <a href="javascript:;" class="room' +cur_str+ '">' 
                            +data[i]['room_name']+ ' (' +data[i]['user_num']+ '/' +data[i]['user_num_limit']+ ')</a></li>';
                    }
                    $('#room_list ul').append(el_str);
                    break;
                case 'room_users':
                    var room_id = data.room_id;
                    $('#user_list ul').empty();
                    var el_str = '';
                    for(var i=0;i<data.length;i++){
                        el_str += '<li><img src="' +data[i]['img']+ '" /> ' +data[i]['name']+ '</li>'; 
                    }
                    $('#user_list ul').append(el_str);
                    break;
                case 'msg_list':
                    var room_id = data.room_id;
                    $('#history_msg ul').empty();
                    var el_str = '';
                    for(var i=0;i<data.length;i++){
                        var default_img = 'https://gss0.baidu.com/-vo3dSag_xI4khGko9WTAnF6hhy/zhidao/wh%3D600%2C800/sign=5af734278982b9013df8cb3543bd854f/71cf3bc79f3df8dc440b9091ce11728b471028ff.jpg';
                        var user_img = data[i]['sender_img'] ? data[i]['sender_img'] : default_img ;
                        var msg_time = new Date();
                        msg_time.setTime(data[i]['create_time'] * 1000);
                        el_str = '<li><img src="' +user_img+ '" /> &lt;' +data[i]['sender_name']+ '&gt; -- ' +msg_time.toLocaleString()+ '<br/>'
                            +' => ' +data[i]['msg_content']+ '</li>' + el_str; 
                    }
                    $('#history_msg ul').append(el_str);
                    $('#history_msg ul').scrollTop('100000');
                    break;
                case 'msg_add':
                    var room_id = data.room_id;
                    var default_img = 'https://gss0.baidu.com/-vo3dSag_xI4khGko9WTAnF6hhy/zhidao/wh%3D600%2C800/sign=5af734278982b9013df8cb3543bd854f/71cf3bc79f3df8dc440b9091ce11728b471028ff.jpg';
                    var user_img = data['sender_img'] ? data['sender_img'] : default_img ;
                    var msg_time = new Date();
                    msg_time.setTime(data['create_time'] * 1000);
                    var el_str = '<li><img src="' +user_img+ '" /> &lt;' +data['sender_name']+ '&gt; -- ' +msg_time.toLocaleString()+ '<br/>'
                        +' => ' +data['msg_content']+ '</li>'; 
                    $('#history_msg ul').append(el_str);
                    $('#history_msg ul').scrollTop('100000');
                    break;
                default:
                    ;
            }
        };
        ws.onerror = function(event,e){
            console.log('Error occured: '+event.data);
        };

        // room_list 列表点击事件  
        $('#room_list ul').on('click','a.room',function(){
            $('#input_msg textarea').attr('disabled',false);
            $('#user_list ul').empty();
            $('#room_list ul li a').removeClass('cur');
            $(this).addClass('cur');
            cur_room_id = $(this).parents('li').attr('room_id');
            ws.send(JSON.stringify({route:'room_users',request:{room_id:cur_room_id}}));
            ws.send(JSON.stringify({route:'msg_list',request:{room_id:cur_room_id}}));
        });

        // 消息输入框输入回车后
        $('#input_msg textarea').on('keypress',function(e){
            var keynum = window.event ? e.keyCode : e.which;
            if(keynum == 13){
                // 发送消息  
                var input_msg = $(this).val();
                ws.send(JSON.stringify({route:'msg_add',request:{room_id:cur_room_id,sender_id:1,msg_content:input_msg}}));
                $(this).val('');
                return false;
            }
        });
    </script>
</body>
</html>
