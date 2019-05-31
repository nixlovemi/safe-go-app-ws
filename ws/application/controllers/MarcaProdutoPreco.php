<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class MarcaProdutoPreco extends CI_Controller {
  public function __construct(){
    CI_Controller::__construct();

    $this->load->helper("utils_helper");
  }

  public function sincronizaMarcaProdutoPrecoWis() {
    $arrRet         = [];
    $arrRet["erro"] = true;
    $arrRet["msg"]  = "";

    try{
      $this->load->database();
      $this->db->trans_start();

      $sql = "SELECT * FROM tb_marca_produto_preco WHERE mpp_wis_id IS NULL OR mpp_wis_id = 0";
      $ret = $this->db->query($sql);


      if($ret) {
        $arrRet["erro"] = false;
        $arrRet["msg"]  = "Dados da tabela marca_produto_preco obtidos com sucesso";
        $arrRet["data"] = json_encode($ret->result());
      }
      printaRetorno($arrRet);
    } catch(Exception $e) {
      $arrRet["erro"] = true;
      $arrRet["msg"]  = "Erro ao obter dados da tabela marca_produto_preco! Msg: " . $e->getMessage();
      printaRetorno($arrRet);
    }
  }

  public function sincronizaMarcaProdutoPrecoWisId() {
    $arrRet         = [];
    $arrRet["erro"] = true;
    $arrRet["msg"]  = "";




    try{
      $objVars                         = proccessPost();
      $strJsonMarcaProdutoPrecoRetorno = $objVars->jsonMarcaProdutoPrecoId ?? "";
      $jsonMarcaProdutoRetorno         = json_decode($strJsonMarcaProdutoPrecoRetorno);

      $this->load->database();
      $this->db->trans_start();
      foreach($jsonMarcaProdutoRetorno as $MarcaProdutoPrecoId) {
        $wisId = $MarcaProdutoPrecoId->idWis;
        $wsId  = $MarcaProdutoPrecoId->idWs;
        $sql = "UPDATE tb_marca_produto_preco SET mpp_wis_id = $wisId WHERE mpp_id = $wsId";
        $ret = $this->db->query($sql);


        if($ret) {
          $arrRet["erro"] = false;
          $arrRet["msg"]  = "tb_marca_produto_preco vinculo com o wis realizado com sucesso";
        }
      }
      $this->db->trans_complete();
      printaRetorno($arrRet);
    } catch(Exception $e) {
      $arrRet["erro"] = true;
      $arrRet["msg"]  = "Erro ao retornar os dados da tabela marca_produto_preco! Msg: " . $e->getMessage();
      printaRetorno($arrRet);
    }
  }
}
