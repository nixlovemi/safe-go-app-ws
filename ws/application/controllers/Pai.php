<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Pai extends CI_Controller {
  public function __construct(){
    CI_Controller::__construct();

    $this->load->helper("utils_helper");
  }

  public function verificaLogin(){
    $arrRet         = [];
    $arrRet["erro"] = true;
    $arrRet["msg"]  = "";
    $arrRet["Pai"]  = "";

    try{

      // variaveis do post
      $objVars  = proccessPost();
      $usuario  = $objVars->usuario ?? "";
      $senha    = $objVars->senha ?? "";

      $md5Senha = md5($senha);
      // =================

      if($usuario == "" || $senha == ""){
        $arrRet["erro"] = true;
        $arrRet["msg"]  = "Informe o usuário e a senha para prosseguir!";
      } else {
        $this->load->database();

        $usuarioEscaped = $this->db->escape($usuario);
        $senhaEscaped   = $this->db->escape($md5Senha);

        $sql = "
          SELECT pai_id
                ,pai_nome
                ,pai_validade
                ,pai_qr
                ,pai_id_solicitacao
                ,pai_aprovado
          FROM tb_pai
          WHERE pai_login = $usuarioEscaped
          AND pai_senha = $senhaEscaped
        ";
        $query = $this->db->query($sql);
        $row   = $query->row();

        if(!$row){
          $arrRet["erro"] = true;
          $arrRet["msg"]  = "Usuário ou senha inválidos!";
        } else {
          if($row->pai_aprovado == NULL){
            $arrRet["erro"] = true;
            $arrRet["msg"]  = "Esse usuário está aguardando aprovação!";
          } else if ($row->pai_aprovado == 0){
            $arrRet["erro"] = true;
            $arrRet["msg"]  = "Esse usuário não está aprovado para fazer login!";
          } else {
            $arrRow     = (array) $row;
            $jsonStrPai = json_encode($arrRow);

            $arrRet["erro"] = false;
            $arrRet["msg"]  = "Login efetuado com sucesso";
            $arrRet["Pai"]  = $jsonStrPai;
          }
        }
      }

      printaRetorno($arrRet);

    } catch (Exception $e) {

      $this->db->trans_rollback();
      $arrRet["erro"] = true;
      $arrRet["msg"]  = "Erro ao atualizar Marcas! Msg: " . $e->getMessage();
      printaRetorno($arrRet);

    }
  }
}
