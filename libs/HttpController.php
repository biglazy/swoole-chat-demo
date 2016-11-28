<?php 

class HttpController {

    public function init(){

    }

    public function show($view=''){
        if(!$view){
            include './views/index/index.php';
        }else{
            echo 'Has no view file.';
        }
    }
}
