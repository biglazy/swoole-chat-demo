<html>
<head>
    <meta http-equiv="content-type" content="txt/html; charset=utf-8" />
    <title>swoole-chat-demo</title>
    <style>
        #msg_box {width:620px;height:400px;padding:10px;border:2px solid grey;}
        #user_list {width:200px;height:100%;float:left;border:1px solid silver;}
        #user_list img {width:30px;height:30px;}
        #msg_list {width:400px;height:100%;margin-left:10px;float:left;border:1px solid silver;}
        ul li {list-style:none;}
        ul li a.cur {color:red;}
        .clear {clear:both;}
    </style>
</head>
<body>
    <h1>swoole-chat-demo</h1>
    <hr/>
    <h3>Chat room list:</h3>
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
            <ul></ul>
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
                case 'room_msgs':

                    break;
                default:
                    ;
            }
        };
        ws.onerror = function(event,e){
            console.log('Error occured: '+event.data);
        };

        $('#room_list ul').on('click','a.room',function(){
            $('#user_list ul').empty();
            $('#room_list ul li a').removeClass('cur');
            $(this).addClass('cur');
            cur_room_id = $(this).parents('li').attr('room_id');
            ws.send(JSON.stringify({route:'room_users',request:{room_id:cur_room_id}}));
        });
    </script>
</body>
</html>
