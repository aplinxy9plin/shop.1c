<?php
    namespace SSAG;
    require_once(realpath(__DIR__)."/CSSAG.class.php");
    require_once(realpath(__DIR__."/..")."/CCurl/CCurlSimple.class.php");
    require_once(realpath(__DIR__."/..")."/CLog/CSSAGLog.class.php");
    
    use AGShop as AGShop;
    use AGShop\SSAG as SSAG;
    use AGShop\Curl as Curl;
    use AGShop\Log as Log; 


    /**
        Класс для работой с историей транзакций СС АГ
    */
    class CSSAGHistory extends CSSAG{
        
        private $sHistoryMethod = '/mvag/billing/getHistory';
        
        function __construct($sSessionId = '',$nUserId = 0){
            parent::__construct($sSessionId);
        }

        /**
            Получение истории начисления/списания
            @param $nPage - номер страницы(по умолчанию 1)
            @$bDebit - выводить только начисления
                - null(по умолчанию) выводить всё
                - true - только начисления
                - false - только списания
            @param $nOnPage - число записей на страницу (по умолчанию 20)
        */
        function get(
            $nPage = 1,
            $bDebit = null,
            $nOnPage = 30
        ){
            $arSign = $this->getSignature($this->nAGID);
            $sUrl = $this->sDomain.":".$this->sPort.$this->sHistoryMethod;
            $arRequest = [
                "ag_id"     =>  $this->nAGID,
                "nonce"     =>  $arSign["nonce"],
                "signature" =>  $arSign["signature"]
            ];
            if($nPage)$arRequest["page"] = $nPage;
            if($nOnPage)$arRequest["onpage"] = $nOnPage;
            if(!is_null($bDebit))$arRequest["debit"] = $bDebit;
            
            $sRequest = json_encode($arRequest);
            
            $objCurl = new \Curl\CCurlSimple;
            $sResult = $objCurl->post($sUrl, $sRequest);
            \Log\CSSAGLog::addLog($sUrl, $sRequest, $sResult);            
            
            $objAnswer = json_decode($sResult);
            if(!$objAnswer)return $this->addError(
                'Ошибка парсинга JSON ответа CCАГ'
            );
            $arAnswer = json_decode(json_encode((array)$objAnswer), TRUE);        
            if(!isset($arAnswer["result"]["history"]))return 
                $this->addError("Отсутствует история начислений/списаний");
            if(!isset($arAnswer["result"]["pagination"]))return 
                $this->addError("Отсутствует пагинация");
            
            return $arAnswer;
        }
        
    }
   
