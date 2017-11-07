<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
// Редирект в каталоге без слеша -> со слешем
if(
    preg_match("#/catalog/(.*?)$#",$_SERVER["REQUEST_URI"],$m)
    &&
    !preg_match("#/catalog/(.*?)/$#",$_SERVER["REQUEST_URI"])

){
    LocalRedirect("/catalog/".$m[1]."/");
    die;
}

// Определяемся с именами свойст для предложений 1С
$res = CIBlockProperty::GetList(array(),array("IBLOCK_ID"=>3));

$offerProps = array();
while($arrProp = $res->GetNext())
    if(preg_match("#^PROP1C_.*#i", $arrProp["CODE"]))
        $offerProps[] = $arrProp["CODE"];
    
$product_code = '';
$catalog_code = '';
if(preg_match("#^/catalog/(.*?)/(.*?)/.*#i", $_SERVER["REQUEST_URI"], $matches)){
    $catalog_code = $matches[1];
    $product_code = $matches[2];
}elseif(preg_match("#^/catalog/(.*?)/.*#i", $_SERVER["REQUEST_URI"], $matches)){
    $catalog_code = $matches[1];
}

$arIBlock = CIBlock::GetList(array(),array("CODE"=>"clothes"))->GetNext();
$catalogIblockId = $arIBlock["ID"];

$arIBlockOffer = CIBlock::GetList(array(),array("CODE"=>"clothes_offers"))->GetNext();
$offerIblockId = $arIBlockOffer["ID"];

// 404-я ошибка
if(
    (
    !CIBlockElement::GetList(
        array(),
        array("IBLOCK_ID"=>$catalogIblockId, "CODE"=>$product_code),
        false,
        array(),
        array("ID")
    )->GetNext()
    ||
    (
        $catalog_code!='root'
        &&
        !CIBlockSection::GetList(
            array(),
            array("IBLOCK_ID"=>$catalogIblockId, "CODE"=>$catalog_code),
            false,
            array(),
            array("ID")
        )->GetNext()
    )
    )
){
    include($_SERVER["DOCUMENT_ROOT"]."/404.php");
    die;
}




?>

<? if(0 && !$USER->IsAuthorized()):?>
<? else: ?>

    <?
        
        $sProductTitle = '';
        if($product_code){
            $arProductMeta = CIBlockElement::GetList(
                array(),
                array(
                    "IBLOCK_ID" =>  CATALOG_IB_ID,
                    "CODE"      =>  $product_code
                ),
                false,
                array("nTopCount"=>1)
            )->GetNext();
            if(isset($arProductMeta["NAME"]) && trim($arProductMeta["NAME"]))
               $sProductTitle = $arProductMeta["NAME"]; 
        }

        if($catalog_code){
            $arCatalogMeta = CIBlockSection::GetList(
                array(),
                array(
                    "IBLOCK_ID" =>  CATALOG_IB_ID,
                    "CODE"      =>  $catalog_code
                ),
                false,
                array("NAME"),
                array("nTopCount"=>1)
            )->GetNext();
            if(isset($arCatalogMeta["NAME"]) && trim($arCatalogMeta["NAME"]))
               $sCatalogTitle = $arCatalogMeta["NAME"]; 
        }


        // Вычисляем метатеги для браузера
        if($sProductTitle && $sCatalogTitle){
            $sTitle  = "$sProductTitle";
            $sTitle .= " / $sCatalogTitle";
            $sTitle .= " / Магазин поощрений &laquo;Активный Гражданин&raquo;";
        }
        elseif($sCatalogTitle){
            $sTitle = "$sCatalogTitle";
            $sTitle.= " / Магазин поощрений &laquo;Активный Гражданин&raquo;";
        }
        else{
            $sTitle = "Магазин поощрений &laquo;Активный Гражданин&raquo;";
        }
        $APPLICATION->SetTitle($sTitle);
    ?>

    <? if(!$product_code && $catalog_code){?>
        <div class="ag-shop-content">
            <? include("filter.inc.php")?>
            <? include("sorting.inc.php")?>
            
            <div class="ag-shop-catalog">
            <? include("container.inc.php")?>
            </div>
        </div>
    <? }elseif($product_code){ ?>
        <div class="ag-shop-content">
            <div class="ag-shop-content__limited-container">
            <?$APPLICATION->IncludeComponent("ag:card", "", array(
                "CATALOG_IBLOCK_ID" =>  $catalogIblockId,
                "OFFER_IBLOCK_ID"   =>  $offerIblockId,
                "PRODUCT_CODE"      =>  $product_code,
                "ALL_POINTS_LIMIT"  =>  1000    // Сколько нужно набрать баллов за
                                                // время регистрации для покупок в магазине
                ),
                false
            );?>
            </div>
        </div>
    <? } ?>

<? endif ?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
