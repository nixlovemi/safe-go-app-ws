<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class PaiLocalizacao extends CI_Controller {
  public function __construct(){
    CI_Controller::__construct();

    $this->load->helper("utils_helper");
  }

  public function estouChegando(){
    $arrRet         = [];
    $arrRet["erro"] = true;
    $arrRet["msg"]  = "";

    try{

      // variaveis do post
      $objVars   = proccessPost();
      $paiId     = $objVars->pai_id ?? "";
      $latitude  = $objVars->latitude ?? "";
      $longitude = $objVars->longitude ?? "";
      $problema  = $objVars->problema ?? false;
      // =================

      if($paiId == "" || $latitude == "" || $longitude == ""){
        $arrRet["erro"] = true;
        $arrRet["msg"]  = "Informe todos os dados para gravamos a localização! / Fill in all the data to save the location!";
      } else {
        $this->load->database();

        $paiIdEscaped     = $this->db->escape($paiId);
        $dataEscaped      = $this->db->escape(date('c'));
        $latitudeEscaped  = $this->db->escape($latitude);
        $longitudeEscaped = $this->db->escape($longitude);
        $problemaEscaped  = $this->db->escape($problema);

        $sql = "
          INSERT INTO tb_pai_localizacao(plo_pai_id, plo_datahora, plo_latitude, plo_longitude, plo_problema)
          VALUES ($paiIdEscaped, $dataEscaped, $latitudeEscaped, $longitudeEscaped, $problemaEscaped)
        ";
        $query = $this->db->query($sql);

        if($query != true){
          $arrRet["erro"] = true;
          $arrRet["msg"]  = "Erro ao enviar localização! / Error sending location!";
        } else {
          if($problema){
            $strMsg = "Sucesso! Problema reportado. / Success! Problem reported!";
          } else {
            $strMsg = "Sucesso! Localização enviada. / Success! Location sent.";
          }

          $arrRet["erro"] = false;
          $arrRet["msg"]  = $strMsg;
        }
      }

      printaRetorno($arrRet);
      gravaLog("Localização enviada. ArrRet: " . json_encode($arrRet));

    } catch (Exception $e) {

      $this->db->trans_rollback();
      $arrRet["erro"] = true;
      $arrRet["msg"]  = "Erro ao enviar localização! / Error sending location!";

      printaRetorno($arrRet);
      gravaLog("Localização não enviada. ArrRet: " . json_encode($arrRet));

    }
  }
}
