<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Usuario extends CI_Controller {
  public function __construct(){
    CI_Controller::__construct();

    $this->load->helper("utils_helper");
  }

  public function getAll(){
    $arrRet = [];
    $arrRet["erro"]         = true;
    $arrRet["msg"]          = "";
    $arrRet["jsonUsuarios"] = "";

    $objVars = proccessPost();
    $this->load->database();

    $sql   = "
      SELECT array_to_json(array_agg(row_to_json(t))) AS result
      FROM (
        SELECT usu_id, usu_login, usu_senha, usu_nome, usu_nomecompleto, usu_validade, usu_ativo
        FROM tb_usuario
      )t
    ";
    $query = $this->db->query($sql);
    $row   = $query->row();

    if($row){
      $arrRet["erro"]         = false;
      $arrRet["jsonUsuarios"] = $row->result;
    } else {
      $arrRet["erro"] = true;
      $arrRet["msg"]  = "Erro ao executar consulta!";
    }

    printaRetorno($arrRet);
  }

  public function sincronizaUsuario() {
    $arrRet         = [];
    $arrRet["erro"] = true;
    $arrRet["msg"]  = "";

    try{
      $objVars        = proccessPost();
      $strJsonUsuario = $objVars->jsonUsuario ?? "";
      $jsonUsuario    = json_decode($strJsonUsuario);

      $this->load->database();
      $this->db->trans_start();

      $arrUsuIds = [];

      foreach($jsonUsuario as $Usuario) {
        $usu_id           = $Usuario->usu_id;
        $usu_login        = $Usuario->usu_login;
        $usu_senha        = $Usuario->usu_senha;
        $usu_nome         = $Usuario->usu_nome;
        $usu_nomecompleto = $Usuario->usu_nomecompleto;
        $usu_validade     = $Usuario->usu_validade;
        $usu_ativo        = $Usuario->usu_ativo;


        $sql = "
          WITH dados(d_usu_id, d_usu_login, d_usu_senha, d_usu_nome, d_usu_nomecompleto, d_usu_validade, d_usu_ativo) AS (
              SELECT $usu_id, '$usu_login', '$usu_senha', '$usu_nome', '$usu_nomecompleto', '$usu_validade'::date, '$usu_ativo'
          ),
          update (upd_usu_id) AS (
                UPDATE tb_usuario
                SET usu_id = d_usu_id, usu_login = d_usu_login, usu_senha = d_usu_senha, usu_nome = d_usu_nome, usu_nomecompleto = d_usu_nomecompleto, usu_validade = d_usu_validade, usu_ativo = d_usu_ativo
                FROM dados
                WHERE usu_id = d_usu_id
                RETURNING usu_id
          )

          INSERT INTO tb_usuario (usu_id, usu_login, usu_senha, usu_nome, usu_nomecompleto, usu_validade, usu_ativo)
          SELECT d_usu_id, d_usu_login, d_usu_senha, d_usu_nome, d_usu_nomecompleto, d_usu_validade::date, d_usu_ativo
          FROM dados
          WHERE d_usu_id NOT IN (
              SELECT upd_usu_id FROM update
          )
          ";

          $ret = $this->db->query($sql);
          if($ret){
            $arrUsuIds[] = $usu_id;
          }
      }

      //DELETA OS USUARIO QUE NÃO VIEREM
      if(!empty($arrUsuIds)) {
        $strUsuId = implode(",", $arrUsuIds);

        $sqlDelete = "
        DELETE FROM tb_usuario WHERE usu_id NOT IN ($strUsuId)
        ";
        $this->db->query($sqlDelete);
      }
      // ========================

      $this->db->trans_complete();
      $arrRet["erro"] = false;
      $arrRet["msg"]  = "Usuario atualizado com sucesso!";

      printaRetorno($arrRet);
    } catch(Exception $e) {
      $this->db->trans_rollback();
      $arrRet["erro"] = true;
      $arrRet["msg"]  = "Erro ao atualizar Usuarios! Msg: " . $e->getMessage();
      printaRetorno($arrRet);
    }
  }
}
