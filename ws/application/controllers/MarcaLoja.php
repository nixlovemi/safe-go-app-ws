<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class MarcaLoja extends CI_Controller {
  public function __construct(){
    CI_Controller::__construct();

    $this->load->helper("utils_helper");
  }


  public function sincronizaMarcaLoja() {
    $arrRet         = [];
    $arrRet["erro"] = true;
    $arrRet["msg"]  = "";

    try{
      $objVars          = proccessPost();
      $strJsonMarcaLoja = $objVars->jsonMarcaLoja ?? "";
      $jsonMarcaLoja    = json_decode($strJsonMarcaLoja);

      $this->load->database();
      $this->db->trans_start();


      $arrMarcaLojaIds = [];

      foreach($jsonMarcaLoja as $MarcaLoja) {
          $mlo_id        = $MarcaLoja->mloId;
          $mlo_descricao = $MarcaLoja->mloDescricao;
          $mlo_red_id    = $MarcaLoja->mloRedId;
          $mlo_cli_id    = ($MarcaLoja->mloCliId == NULL) ? "NULL::integer" : $MarcaLoja->mloCliId;

          $sql = "WITH dados(d_mlo_id, d_mlo_descricao, d_mlo_red_id, d_mlo_cli_id) AS (
                SELECT $mlo_id, '$mlo_descricao', $mlo_red_id, $mlo_cli_id
            ),
            update (upd_mlo_id) AS (
              UPDATE tb_marca_loja
              SET mlo_id = d_mlo_id, mlo_descricao = d_mlo_descricao, mlo_red_id = d_mlo_red_id, mlo_cli_id = d_mlo_cli_id
              FROM dados
              WHERE mlo_id = d_mlo_id
              RETURNING mlo_id
            )
            INSERT INTO tb_marca_loja (mlo_id, mlo_descricao, mlo_red_id, mlo_cli_id)
            SELECT d_mlo_id, d_mlo_descricao, d_mlo_red_id, d_mlo_cli_id
            FROM dados
            WHERE d_mlo_id NOT IN (SELECT upd_mlo_id FROM update);";
          $ret = $this->db->query($sql);
          if($ret){
            $arrMarcaLojaIds[] = $mlo_id;
          }
      }
      //DELETA AS MARCAS LOJAS QUE NÃO VIEREM
      if(!empty($arrMarcaLojaIds)) {
        $strMarcaLojaId = implode(",", $arrMarcaLojaIds);

        $sqlDelete = "
          DELETE FROM tb_marca_loja WHERE mlo_id NOT IN ($strMarcaLojaId)
        ";
        $this->db->query($sqlDelete);
      }

      // ========================

      $this->db->trans_complete();
      $arrRet["erro"] = false;
      $arrRet["msg"]  = "MarcaLoja atualizado com sucesso!";

      printaRetorno($arrRet);
    }catch(Exception $e) {
      $this->db->trans_rollback();
      $arrRet["erro"] = true;
      $arrRet["msg"]  = "Erro ao atualizar Marca loja! Msg: ". $e->getMessage();
      printaRetorno($arrRet);
    }
  }
}
