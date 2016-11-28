<?php 

include './libs/HttpController.php';

class indexController extends HttpController{

    public function actionIndex(){
        $this->show();
    }
}
    
