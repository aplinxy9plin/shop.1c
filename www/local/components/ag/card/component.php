<? if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();


$RU = $_SERVER["REQUEST_URI"];
// Значения по умолчанию
if(!isset($arParams["PRODUCT_CODE"]))$arParams["PRODUCT_CODE"] = '';
if(!isset($arParams["CATALOG_IBLOCK_ID"]))$arParams["CATALOG_IBLOCK_ID"] = 2;
if(!isset($arParams["OFFER_IBLOCK_ID"]))$arParams["OFFER_IBLOCK_ID"] = 3;
if(!isset($arParams["USER_ID"]))$arParams["USER_ID"] = $USER->GetId();
if(!isset($arParams["ALL_POINTS_LIMIT"]))$arParams["ALL_POINTS_LIMIT"] = 1000;


//Определяем сумму на счету пользователя
CModule::IncludeModule("sale");
CModule::IncludeModule("forum");
$res = CSaleUserAccount::GetList(array("TIMESTAMP_X"=>"DESC"),array("USER_ID"=>$arParams["USER_ID"]));
$arResult["ACCOUNT"] = $res->GetNext();


CModule::IncludeModule('iblock');
// Информация об элементе каталога
$resCatalog = CIBlockElement::GetList(
    array(),array(
        "IBLOCK_ID" =>  $arParams["CATALOG_IBLOCK_ID"],
        "CODE"      =>   $arParams["PRODUCT_CODE"]
    ),
    false,
    array("nTopCount"=>1)
);
$arResult["CATALOG_ITEM"] = $resCatalog->GetNext();


// Информацация о разделе
$arResult["CATALOG_ITEM"]["SECTION_INFO"] = CIBlockSection::GetList(
    array(),array(
        "IBLOCK_ID" =>  $arParams["CATALOG_IBLOCK_ID"],
        "ID"      =>    $arResult["CATALOG_ITEM"]["IBLOCK_SECTION_ID"]
    ),
    false,
    array("nTopCount"=>1)
)->GetNext();

// Сколько у товара всего желающих
$arFilter = array("IBLOCK_CODE"=>"whishes", "PROPERTY_WISH_PRODUCT"=>$arResult["CATALOG_ITEM"]["ID"]);
$res1 = CIBlockElement::GetList(array(),$arFilter,false, array());
$arResult["WISHES"] = $res1->SelectedRowsCount();

// Входит ли товар с писок моих желаний
$arFilter = array(
    "IBLOCK_CODE"=>"whishes", 
    "PROPERTY_WISH_USER"=>$arParams["USER_ID"],
    "PROPERTY_WISH_PRODUCT"=>$arResult["CATALOG_ITEM"]["ID"]);
$res1 = CIBlockElement::GetList(array(),$arFilter,false, array("nTopCount"=>1));
$arResult["MYWISH"] = $res1->SelectedRowsCount();

// Свойства элемента каталога
$arResult["CATALOG_ITEM"]["PROPERTIES"] = array();
$resProps = CIBlockElement::GetProperty($arParams["CATALOG_IBLOCK_ID"],$arResult["CATALOG_ITEM"]["ID"]);
while($arProp = $resProps->GetNext()){
    if(!isset($arResult["CATALOG_ITEM"]["PROPERTIES"]))
        $arResult["CATALOG_ITEM"]["PROPERTIES"][$arProp["CODE"]] = array();
    if($arProp["PROPERTY_TYPE"]=='F')
        $arProp["FILE_PATH"] = CFile::GetPath($arProp["VALUE"]);
    $arResult["CATALOG_ITEM"]["PROPERTIES"][$arProp["CODE"]][] = $arProp;
}

// Вычисляем рейтинг
$arResult["CATALOG_ITEM"]["RATING"] = round($arResult["CATALOG_ITEM"]["PROPERTIES"]["RATING"][0]["VALUE"]*5,2);

// Торговые предложения
$resOffers = CIBlockElement::GetList(
    array(),$arFilter = array(
        "IBLOCK_ID"         =>  $arParams["OFFER_IBLOCK_ID"],
        "PROPERTY_CML2_LINK"=>  $arResult["CATALOG_ITEM"]["ID"]
    ),
    false
);
$arResult["OFFERS"] = array();
$arResult["OFFERS_JSON"] = array();
$arResult["PROP1C"] = array();
$arResult["STORAGES"] = array();
while($arOffer = $resOffers->GetNext()){
    $arOfferJson= array("PICS"=>array(),"1C_PROPS"=>array(),"STORAGES"=>array());
    
    // Свойства предложения
    $arOffer["PROPERTIES"] = array();
    $resProps = CIBlockElement::GetProperty($arParams["OFFER_IBLOCK_ID"],$arOffer["ID"]);
    while($arProp = $resProps->GetNext()){
        if(!isset($arOffer["PROPERTIES"][$arProp["CODE"]]))
            $arOffer["PROPERTIES"][$arProp["CODE"]] = array();
        if($arProp["PROPERTY_TYPE"]=='F'){
            $arProp["FILE_PATH"] = CFile::GetPath($arProp["VALUE"]);
        }
        if($arProp["PROPERTY_TYPE"]=='F' && !$arProp["FILE_PATH"])
            continue;
        elseif($arProp["PROPERTY_TYPE"]=='F' && $arProp["FILE_PATH"] && $arProp["CODE"]=='MORE_PHOTO')
            $arOfferJson["PICS"][] = $arProp["FILE_PATH"];
        
        if(preg_match("#PROP1C_(.*?)#",$arProp["CODE"])){
            $arOfferJson["1C_PROPS"][$arProp["CODE"]] = array("ID"=>$arProp["VALUE"],"VALUE"=>$arProp["VALUE_ENUM"]);
            if(!isset($arResult["PROP1C"][$arProp["CODE"]]))
                $arResult["PROP1C"][$arProp["CODE"]] = array("NAME"=>$arProp["NAME"],"VALUES"=>array());
            if($arProp["VALUE"])
                $arResult["PROP1C"][$arProp["CODE"]]["VALUES"][$arProp["VALUE"]] = $arProp["VALUE_ENUM"];
            
        }
        
        $arOffer["PROPERTIES"][$arProp["CODE"]][] = $arProp;
    }
    // Склады предложения
    $arOffer["STORAGES"] = array();
    $resStorage = CCatalogStoreProduct::GetList(array(),array("PRODUCT_ID"=>$arOffer["ID"]));
    while($arStorage = $resStorage->GetNext()){
        
        if(!$arStorage["AMOUNT"])continue;
        $arOffer["STORAGES"][$arStorage["STORE_ID"]] = $arStorage["AMOUNT"];
        $arOfferJson["STORAGES"][$arStorage["STORE_ID"]] = $arStorage["AMOUNT"];
        
        // Пополняем справочник складов
        if(!isset($arResult["STORAGES"][$arStorage["STORE_ID"]])){
            $arResult["STORAGES"][$arStorage["STORE_ID"]] = 
                CCatalogStore::GetList(array(),array("ID"=>$arStorage["STORE_ID"]))->GetNext();
        }
        foreach($arResult["STORAGES"][$arStorage["STORE_ID"]] as $key=>$val)$arResult["STORAGES"][$arStorage["STORE_ID"]][$key] = trim($val);
        
    }
    
    
    $arOffer["RRICE_INFO"] = CPrice::GetList(array(),array("PRODUCT_ID"=>$arOffer["ID"]))->GetNext();
    $arOfferJson["PRICE"] = str_replace(",","",$arOffer["RRICE_INFO"]["PRICE"]);
    $arOfferJson["NAME"] = $arOffer["NAME"];
    
    $arResult["OFFERS"][] = $arOffer;
    $arResult["OFFERS_JSON"][$arOffer["ID"]] = $arOfferJson;
};

$arIBlock = CIBlock::GetList(array(),array("CODE"=>"marks"))->GetNext();
$iblockId = $arIBlock["ID"];
// Определяем ставил ли пользователь оценку этому товару
$arResult["MARK"] = CIBlockElement::GetList(
    array(), 
    $arField = array(
        "IBLOCK_ID"=>$iblockId,
        "PROPERTY_MARK_USER"=>$USER->GetId(),
        "PROPERTY_MARK_PRODUCT"=>$arResult["CATALOG_ITEM"]["ID"]
    ),
    false,
    array(),
    array("PROPERTY_MARK")
)->GetNext();

// Считаем количество отзывов
$resComments = CForumMessage::GetList(array("POST_DATE"=>"DESC"),array("TOPIC_ID"=>$arResult["CATALOG_ITEM"]["PROPERTIES"]["FORUM_TOPIC_ID"][0]["VALUE"]));
$arResult["MESSAGES"] = $resComments->SelectedRowsCount();

// Узнаём число заработанных баллов
$arResult["USER_INFO"] = CUser::GetList(
    ($by="personal_country"), ($order="desc"),
    array("ID"=>CUser::GetId()),
    array(
        "SELECT"=>array("UF_USER_ALL_POINTS"),
        "NAV_PARAMS"=>array("nTopCount"=>1)
    )
)->GetNext();

$this->IncludeComponentTemplate();
