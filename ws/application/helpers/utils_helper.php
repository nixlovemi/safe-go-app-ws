<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
* valida e retorna as variaveis
* $return->nome, por exemplo
*/
function proccessPost(){
  $postdata = file_get_contents("php://input");
  if($postdata != ""){
    $_SESSION["postData"] = $postdata;
    $jsonVars             = json_decode($postdata);
  } else {
    $jsonStr              = json_encode($_REQUEST);
    $_SESSION["postData"] = $jsonStr;
    $jsonVars             = json_decode($jsonStr);
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

function gravaLog($texto=""){

  $CI  =& get_instance();

  $dataHora   = $CI->db->escape(date('c'));
  $ip         = $CI->db->escape($CI->input->ip_address());
  $controller = $CI->db->escape($CI->router->fetch_class());
  $action     = $CI->db->escape($CI->router->fetch_method());
  $vars       = $CI->db->escape($_SESSION["postData"] ?? "");
  $vTexto     = $CI->db->escape($texto);

  $sql = "
    INSERT INTO tb_log(log_datahora, log_ip, log_controller, log_action, log_vars, log_texto)
    VALUES ($dataHora, $ip, $controller, $action, $vars, $vTexto);
  ";
  $CI->db->query($sql);

}
