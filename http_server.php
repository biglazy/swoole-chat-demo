<?php
    $http = new swoole_http_server("0.0.0.0",9501);
    $http->on('request',function($request,$response){
        ob_start();
        if($request->server['request_uri'] == '/favicon.ico'){
            echo file_get_contents('./favicon.ico');  
        }else{
            $route = trim($request->get['r']);
            $routeArr = explode('/',$route);
            $controllerName = $routeArr[0];
            $actionName = $routeArr[1];
            $controllerFile = './controllers/'.$controllerName.'Controller.php';
            $actionFullname = 'action'.ucfirst(strtolower($actionName));
            if(is_file($controllerFile)){
                require_once $controllerFile;
                $controllerClass = $controllerName.'Controller';
                $controller = new $controllerClass();
            $controller->$actionFullname();
            }
        }
        $content = ob_get_clean();
        $response->end($content);
    });
    $http->start();
