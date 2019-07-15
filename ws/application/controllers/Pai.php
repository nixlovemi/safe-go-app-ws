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
        $arrRet["msg"]  = "Informe o usuário e a senha para prosseguir! / Enter the username and password to proceed!";
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
          $arrRet["msg"]  = "Usuário ou senha inválidos! / Invalid username or password!";
        } else {
          if($row->pai_aprovado == NULL){
            $arrRet["erro"] = true;
            $arrRet["msg"]  = "Esse usuário está aguardando aprovação! / This user is waiting for approval!";
          } else if ($row->pai_aprovado == 0){
            $arrRet["erro"] = true;
            $arrRet["msg"]  = "Esse usuário não está aprovado para fazer login! / This user is not approved to login!";
          } else {
            $arrRow     = (array) $row;
            $jsonStrPai = json_encode($arrRow);

            $arrRet["erro"] = false;
            $arrRet["msg"]  = "Login efetuado com sucesso! / Login done successfully!";
            $arrRet["Pai"]  = $jsonStrPai;
          }
        }
      }

      printaRetorno($arrRet);
      gravaLog("Login executado. ArrRet: " . json_encode($arrRet));

    } catch (Exception $e) {

      $this->db->trans_rollback();
      $arrRet["erro"] = true;
      $arrRet["msg"]  = "Erro ao executar Login! / Error while executing Login!";
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
        $arrRet["msg"]  = "Informe todos os campos para prosseguir! / Please fill all fields to proceed!";
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
          $arrRet["msg"]  = "Informe o usuário com no mínimo 3 e no máximo 60 caracteres! / Fill the user with a minimum of 3 and a maximum of 60 characters!";
        } else if($usuarioJaExiste){
          $arrRet["erro"] = true;
          $arrRet["msg"]  = "Usuário já existente! / User already exist!";
        } else if($senhaMinima){
          $arrRet["erro"] = true;
          $arrRet["msg"]  = "Informe a senha com no mínimo 6 caracteres! / Enter the password with at least 6 characters!";
        } else if($nomeMinMaxTamanho){
          $arrRet["erro"] = true;
          $arrRet["msg"]  = "Informe o nome com no mínimo 3 e no máximo 40 caracteres! / Enter the name with a minimum of 3 and a maximum of 40 characters!";
        } else if($validadeInvalida){
          $arrRet["erro"] = true;
          $arrRet["msg"]  = "Informe a validade para uma data futura! / Please fill in a future date!";
        } else if($idSolicInvalida){
          $arrRet["erro"] = true;
          $arrRet["msg"]  = "Não conseguimos encontrar a ID do solicitante! / We could not find the requester ID!";
        } else {
          $usuarioEscaped       = $this->db->escape($usuario);
          $senhaEscaped         = $this->db->escape($md5Senha);
          $nomeEscaped          = $this->db->escape($nome);
          $validadeEscaped      = $this->db->escape($validade);
          $idSolicitacaoEscaped = $this->db->escape($idSolicitacao);

          $sql2 = "
            INSERT INTO tb_pai(pai_login, pai_senha, pai_nome, pai_validade, pai_id_solicitacao, pai_qr)
            VALUES ($usuarioEscaped, $senhaEscaped, $nomeEscaped, $validadeEscaped, $idSolicitacaoEscaped, $usuarioEscaped);
          ";
          $this->db->query($sql2);

          if($this->db->error()["message"] != ""){
            $arrRet["erro"] = true;
            $arrRet["msg"]  = "Erro ao cadastrar login temporário! / Error registering temporary login!";
          } else {
            $arrRet["erro"] = false;
            $arrRet["msg"]  = "Login temporário cadastrado com sucesso! / Temporary login registered successfully!";
          }
        }
      }

      printaRetorno($arrRet);
      gravaLog("Cadastro temporário executado. ArrRet: " . json_encode($arrRet));

    } catch (Exception $e) {

      $this->db->trans_rollback();
      $arrRet["erro"] = true;
      $arrRet["msg"]  = "Erro ao cadastrar usuário temporário! / Error registering temporary user!";
      printaRetorno($arrRet);
      gravaLog("Cadastro temporário não executado. ArrRet: " . json_encode($arrRet));

    }
  }

  public function solicitaResetSenha(){

    $arrRet         = [];
    $arrRet["erro"] = true;
    $arrRet["msg"]  = "";

    try{

      // variaveis do post
      $objVars = proccessPost();
      $login   = $objVars->login ?? "";
      // =================

      if($login == ""){
        $arrRet["erro"] = true;
        $arrRet["msg"]  = "Informe o usuário prosseguir! / Please inform the user to proceed!";
      } else {
        $this->load->database();
        $loginEscaped = $this->db->escape($login);

        $sql = "
          SELECT pai_id
          FROM tb_pai
          WHERE pai_login = $loginEscaped
        ";
        $query = $this->db->query($sql);
        $row   = $query->row();

        if(!$row){
          $arrRet["erro"] = true;
          $arrRet["msg"]  = "Usuário inválido! Verifique se você digitou o usuário corretamente. / Invalid user! Make sure you typed the user correctly.";
        } else {
          $paiId = $row->pai_id;

          $sql2 = "
            SELECT COUNT(*) AS cnt
            FROM tb_pai_reset_senha
            WHERE prs_pai_id = $paiId
            AND prs_dtatendido IS NULL
          ";
          $query2 = $this->db->query($sql2);
          $row2   = $query2->row();

          if(!$row2 || $row2->cnt > 0){
            $arrRet["erro"] = true;
            $arrRet["msg"]  = "Você já tem uma solicitação de senha não atendida! Aguarde a escola entrar em contato. / You already have an unanswered password request! Wait for the school to contact you.";
          } else {
            $paiIdEscaped = $this->db->escape($paiId);
            $hojeEscaped  = $this->db->escape(date("Y-m-d H:i:s"));

            $sql3 = "
              INSERT INTO tb_pai_reset_senha(prs_pai_id, prs_dtsolicitado)
              VALUES($paiIdEscaped, $hojeEscaped);
            ";
            $this->db->query($sql3);

            if($this->db->error()["message"] != ""){
              $arrRet["erro"] = true;
              $arrRet["msg"]  = "Erro ao solicitar alteração de senha! / Error requesting password change!";
            } else {
              $arrRet["erro"] = false;
              $arrRet["msg"]  = "Solicitação de senha enviada com sucesso! A escola entrará em contato em breve. / Password request sent successfully! The school will contact you shortly.";
            }
          }
        }
      }

      printaRetorno($arrRet);
      gravaLog("Solicitação de senha executada. ArrRet: " . json_encode($arrRet));

    } catch (Exception $e) {

      $this->db->trans_rollback();
      $arrRet["erro"] = true;
      $arrRet["msg"]  = "Erro ao executar Login! / Error while executing Login!";
      printaRetorno($arrRet);
      gravaLog("Solicitação de senha não executada. ArrRet: " . json_encode($arrRet));

    }
  }
}
