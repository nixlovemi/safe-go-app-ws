<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
* valida e retorna as variaveis
* $return->nome, por exemplo
*/
function proccessPost(){
  $postdata = file_get_contents("php://input");
  if($postdata != ""){
    $jsonVars  = json_decode($postdata);
  } else {
    $jsonStr  = json_encode($_REQUEST);
    $jsonVars = json_decode($jsonStr);
  }

  if(!isset($jsonVars->appkey) || $jsonVars->appkey != APP_KEY){
    $arrRet         = [];
    $arrRet["erro"] = true;
    $arrRet["msg"]  = "Key de acesso inválida!" ;

    echo json_encode($arrRet);
    die();
  } else {
    return $jsonVars;
  }
}

/**
* executa o retorno padrão do WS
* $arrRet = array com as informações de retorno
*/
function printaRetorno($arrRet){
  if(!is_array($arrRet)){
    $arrRet         = [];
    $arrRet["erro"] = true;
    $arrRet["msg"]  = "Variável do retorno deve ser um array";

    echo json_encode($arrRet);
    die();
  } else {
    echo json_encode($arrRet);
  }
}
