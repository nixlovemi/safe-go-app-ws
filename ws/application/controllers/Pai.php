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
      gravaLog("Login executado. ArrRet: " . json_encode($arrRet));

    } catch (Exception $e) {

      $this->db->trans_rollback();
      $arrRet["erro"] = true;
      $arrRet["msg"]  = "Erro ao executar Login! Msg: " . $e->getMessage();
      printaRetorno($arrRet);
      gravaLog("Login não executado. ArrRet: " . json_encode($arrRet));

    }
  }

  public function cadLoginTemporario(){
    $arrRet         = [];
    $arrRet["erro"] = true;
    $arrRet["msg"]  = "";

    try{

      // variaveis do post
      $objVars  = proccessPost();

      $usuario       = $objVars->login ?? "";
      $senha         = $objVars->senha ?? "";
      $nome          = $objVars->nome ?? "";
      $validade      = $objVars->validade ?? "";
      $idSolicitacao = $objVars->id_solicitacao ?? "";

      $md5Senha = md5($senha);
      // =================

      if($usuario == "" || $senha == "" || $nome == "" || $validade == "" || $idSolicitacao == ""){
        $arrRet["erro"] = true;
        $arrRet["msg"]  = "Informe todos os campos para prosseguir!";
      } else {
        $this->load->database();

        // validacoes =====
        $sql = "
          SELECT COUNT(*) AS cnt
          FROM tb_pai
          WHERE pai_login = ".$this->db->escape($usuario)."
        ";
        $query = $this->db->query($sql);
        $row   = $query->row();

        $usuarioMinMaxTamanho = (strlen($usuario) < 3 || strlen($usuario) > 60);
        $usuarioJaExiste      = ($row->cnt > 0);
        $senhaMinima          = (strlen($senha) < 6);
        $nomeMinMaxTamanho    = (strlen($nome) < 3 || strlen($nome) > 40);
        $validadeInvalida     = !is_date($validade) || $validade < date("Y-m-d");
        $idSolicInvalida      = !is_numeric($idSolicitacao);
        // ================

        if($usuarioMinMaxTamanho){
          $arrRet["erro"] = true;
          $arrRet["msg"]  = "Informe o usuário com no mínimo 3 e no máximo 60 caracteres!";
        } else if($usuarioJaExiste){
          $arrRet["erro"] = true;
          $arrRet["msg"]  = "Usuário já existente!";
        } else if($senhaMinima){
          $arrRet["erro"] = true;
          $arrRet["msg"]  = "Informe a senha com no mínimo 6 caracteres!";
        } else if($nomeMinMaxTamanho){
          $arrRet["erro"] = true;
          $arrRet["msg"]  = "Informe o nome com no mínimo 3 e no máximo 40 caracteres!";
        } else if($validadeInvalida){
          $arrRet["erro"] = true;
          $arrRet["msg"]  = "Informe a validade para uma data futura!";
        } else if($idSolicInvalida){
          $arrRet["erro"] = true;
          $arrRet["msg"]  = "Não conseguimos encontrar a ID do solicitante!";
        } else {
          /*$usuarioEscaped = $this->db->escape($usuario);
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
          }*/
        }
      }

      printaRetorno($arrRet);
      gravaLog("Cadastro temporário executado. ArrRet: " . json_encode($arrRet));

    } catch (Exception $e) {

      $this->db->trans_rollback();
      $arrRet["erro"] = true;
      $arrRet["msg"]  = "Erro ao cadastrar usuário temporário! Msg: " . $e->getMessage();
      printaRetorno($arrRet);
      gravaLog("Cadastro temporário não executado. ArrRet: " . json_encode($arrRet));

    }
  }
}
