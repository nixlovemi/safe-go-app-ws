<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class MarcaProduto extends CI_Controller {
  public function __construct(){
    CI_Controller::__construct();

    $this->load->helper("utils_helper");
  }

  public function sincronizaMarcaProduto() {
      $arrRet         = [];
      $arrRet["erro"] = true;
      $arrRet["msg"]  = "";

      try {
        $objVars             = proccessPost();
        $strJsonMarcaProduto = $objVars->jsonMarcaProduto ?? "";
        $jsonMarcaProduto    = json_decode($strJsonMarcaProduto);

        $this->load->database();
        $this->db->trans_start();

        $arrMarcaProdutoIds = [];

        foreach($jsonMarcaProduto as $MarcaProduto) {
            $mpr_id         = $MarcaProduto->mprId;
            $mpr_mar_id     = $MarcaProduto->mprMarId;
            $mpr_descricao  = $MarcaProduto->mprDesc;

            $sql = "
              WITH dados(d_mpr_id, d_mpr_mar_id, d_mpr_descricao) AS (
                  SELECT $mpr_id, $mpr_mar_id, '$mpr_descricao'
              ),
              update (upd_mpr_id) AS (
                  UPDATE tb_marca_produto
                  SET mpr_id = d_mpr_id, mpr_mar_id = d_mpr_mar_id, mpr_descricao = d_mpr_descricao
                  FROM dados
                  WHERE mpr_id = d_mpr_id
                  RETURNING mpr_id
              )

              INSERT INTO tb_marca_produto (mpr_id, mpr_mar_id, mpr_descricao)
              SELECT d_mpr_id, d_mpr_mar_id, d_mpr_descricao
              FROM dados
              WHERE d_mpr_id NOT IN (
                SELECT upd_mpr_id FROM update
              )
            ";

            $ret = $this->db->query($sql);
            if($ret) {
              $arrMarcaProdutoIds[] = $mpr_id;
            }
        }

        //DELETA AS MARCAS PRODUTOS
        if(!empty($arrMarcaProdutoIds)) {
          $strMarcaProdutoId = implode(",", $arrMarcaProdutoIds);

          $sqlDelete = "
            DELETE FROM tb_marca_produto WHERE mpr_id NOT IN ($strMarcaProdutoId)
          ";

          $this->db->query($sqlDelete);
        }
        // ====================================

        $this->db->trans_complete();
        $arrRet["erro"] = false;
        $arrRet["msg"]  = "Marca produto atualizado com sucesso!";

        printaRetorno($arrRet);

      } catch(Exception $e) {
          $this->db->trans_rollback();
          $arrRet["erro"] = true;
          $arrRet["msg"]  = "Erro ao atualiza Marca produto! Msg: ".$e->getMessage();
          printaRetorno($arrRet);
      }
  }

}
