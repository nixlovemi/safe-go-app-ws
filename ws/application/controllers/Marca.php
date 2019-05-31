<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Marca extends CI_Controller {
  public function __construct(){
    CI_Controller::__construct();

    $this->load->helper("utils_helper");
  }

  public function sincronizaMarca(){
    $arrRet         = [];
    $arrRet["erro"] = true;
    $arrRet["msg"]  = "";

    try{
      $objVars       = proccessPost();
      $strJsonMarcas = $objVars->jsonMarcas ?? "";
      $jsonMarcas    = json_decode($strJsonMarcas);

      $this->load->database();
      $this->db->trans_start();

      $arrMarIds = [];

      // insere/atualiza os que vieram
      foreach($jsonMarcas as $Marca){
        $mar_id        = $Marca->mar_id;
        $mar_descricao = $Marca->mar_descricao;

        $sql = "
          WITH dados(d_mar_id, d_mar_descricao) AS (
            SELECT $mar_id, '$mar_descricao'
          )
          ,update(upd_mar_id) AS (
            UPDATE tb_marca
            SET mar_id = d_mar_id, mar_descricao = d_mar_descricao
            FROM dados
            WHERE mar_id = d_mar_id
            RETURNING mar_id
          )

          INSERT INTO tb_marca(mar_id, mar_descricao)
          SELECT d_mar_id, d_mar_descricao
          FROM dados
          WHERE d_mar_id NOT IN (
            SELECT upd_mar_id FROM update
          )
        ";
        $ret = $this->db->query($sql);
        if($ret){
          $arrMarIds[] = $mar_id;
        }
      }
      // =============================

      // deleta os que nao vieram
      if(!empty($arrMarIds)) {
        $strMarIds = implode(",", $arrMarIds);
        $sqlDel    = "
          DELETE FROM tb_marca_produto WHERE mpr_mar_id NOT IN ($strMarIds);
          DELETE FROM tb_marca WHERE mar_id NOT IN ($strMarIds);
        ";
        $this->db->query($sqlDel);
      }
      // ========================

      $this->db->trans_complete();
      $arrRet["erro"] = false;
      $arrRet["msg"]  = "Marcas atualizadas com sucesso!";

      printaRetorno($arrRet);
    } catch (Exception $e) {

      $this->db->trans_rollback();
      $arrRet["erro"] = true;
      $arrRet["msg"]  = "Erro ao atualizar Marcas! Msg: " . $e->getMessage();
      printaRetorno($arrRet);

    }
  }
}
