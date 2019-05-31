<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Rede extends CI_Controller {
  public function __construct(){
    CI_Controller::__construct();

    $this->load->helper("utils_helper");
  }


  public function sincronizaRede() {
    $arrRet         = [];
    $arrRet["erro"] = true;
    $arrRet["msg"]  = "";

    try{
      $objVars     = proccessPost();
      $strJsonRede = $objVars->jsonRede ?? "";
      $jsonRede    = json_decode($strJsonRede);

      $this->load->database();
      $this->db->trans_start();

      $arrRedeIds = [];

      foreach ($jsonRede as $Rede) {
          $red_id = $Rede->redId;
          $red_descricao = $Rede->redDesc;

          $sql = "
            WITH dados(d_red_id, d_red_descricao) AS (
              SELECT $red_id, '$red_descricao'
            ),
            update (upd_red_id) AS (
              UPDATE tb_rede
              SET red_id = d_red_id, red_descricao = d_red_descricao
              FROM dados
              WHERE red_id = d_red_id
              RETURNING red_id
            )

            INSERT INTO tb_rede (red_id, red_descricao)
            SELECT d_red_id, d_red_descricao
            FROM dados
            WHERE d_red_id NOT IN (
                  SELECT upd_red_id FROM update
            )
          ";

          $ret = $this->db->query($sql);
          if($ret){
            $arrRedeIds[] = $red_id;
          }
      }

      //DELETA AS REDES QUE NÃO VIEREM
      if(!empty($arrRedeIds)) {
        $strRedeId = implode(",", $arrRedeIds);

        $sqlDelete = "
          DELETE FROM tb_marca_loja WHERE mlo_red_id NOT IN ($strRedeId);
          DELETE FROM tb_rede WHERE red_id NOT IN ($strRedeId)
        ";
        $this->db->query($sqlDelete);
      }
      // ========================

      $this->db->trans_complete();
      $arrRet["erro"] = false;
      $arrRet["msg"]  = "Rede atualizado com sucesso!";

      printaRetorno($arrRet);
    }catch(Exception $e) {
      $this->db->trans_rollback();
      $arrRet["erro"] = true;
      $arrRet["msg"]  = "Erro ao atualizar Redes! Msg: ". $e->getMessage();
      printaRetorno($arrRet);
    }

  }
}
