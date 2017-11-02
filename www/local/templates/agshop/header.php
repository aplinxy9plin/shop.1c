<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

CUtil::InitJSCore(['ajax']);

if(preg_match("#^/partners#",$_SERVER["REQUEST_URI"])){
    $APPLICATION->SetAdditionalCSS("/local/assets/styles/partners.css");
    $APPLICATION->SetAdditionalCSS("/local/assets/bootstrap/css/bootstrap.min.css");
    $APPLICATION->SetAdditionalCSS("/local/assets/bootstrap/css/bootstrap-theme.min.css");
}


$APPLICATION->SetAdditionalCSS("/local/assets/styles/fonts.css");
$APPLICATION->SetAdditionalCSS("/local/assets/styles/components.css");
$APPLICATION->SetAdditionalCSS("/local/assets/styles/profile.css");
$APPLICATION->SetAdditionalCSS("/local/assets/styles/catalog.css");
$APPLICATION->SetAdditionalCSS("/local/assets/styles/rules.css");
$APPLICATION->SetAdditionalCSS("/local/assets/styles/card.css");
$APPLICATION->SetAdditionalCSS("/local/assets/libs/jquery-ui.css");
$APPLICATION->SetAdditionalCSS("/local/assets/libs/slick.css");
$APPLICATION->SetAdditionalCSS("/local/assets/styles/mod.css");
$APPLICATION->SetAdditionalCSS("/local/assets/styles/troika.css");
$APPLICATION->SetAdditionalCSS("/local/assets/styles/faq.css");
$APPLICATION->SetAdditionalCSS("/local/assets/styles/semantic.css");


$APPLICATION->AddHeadScript("/local/assets/libs/jquery.min.js");
$APPLICATION->AddHeadScript("/local/assets/libs/jquery-ui.js");
$APPLICATION->AddHeadScript("/local/assets/libs/slick.min.js");
$APPLICATION->AddHeadScript("/local/assets/scripts/index.js");
$APPLICATION->AddHeadScript("/local/assets/scripts/scripts.js");
$APPLICATION->AddHeadScript("/local/assets/scripts/common.js");
$APPLICATION->AddHeadScript("/local/assets/scripts/troika.js");
$APPLICATION->AddHeadScript("/local/assets/scripts/faq.js");

setcookie("LOGIN", CUser::GetLogin(),time()+600*24*60*60,"/");

?>
<!DOCTYPE html>
<html lang="ru">
  <head>
    <meta charset="utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="HandheldFriendly" content="True"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="format-detection" content="telephone=no"/>
    <meta name="format-detection" content="address=no"/>
    <meta name="msapplication-tap-highlight" content="no"/>
    <meta name="description" content=""/>
    <meta name="keywords" content=""/>
    <? $APPLICATION->ShowHead(); ?>                                                                                                                                                           
    <title><?$APPLICATION->ShowTitle()?></title>

  </head>
  <body>
    <?  $APPLICATION->ShowPanel(); ?>

<? if(
    // Заглушка
    0 
    &&
    !$USER->IsAuthorized()
    &&
    !preg_match("#^/partners/#", $_SERVER["REQUEST_URI"])
):?>
<h2 style="width: 80%;margin: 100px auto; text-align: center;">Раздел<br/> "Магазина<br/> поощрений"<br/> временно<br/> недоступен<br/>
в связи<br/> с обновлениями.
<br/>Приносим свои<br/> извинения.</h2>
<? else: ?>
    <div class="ag-shop">
    <? if(
        !preg_match("#^/partners/#", $_SERVER["REQUEST_URI"])
    ):?>
    <? if(IS_MOBILE && $_SERVER["REQUEST_URI"]!='/catalog/'):?>
      <div class="ag-shop-mob-nav">
        <a class="ag-shop-mob-back ag-shop-mob-nav" href="<? 
//            echo "/catalog/";
            echo "#";
            /*
            if(
                $arPath = explode("/",$_SERVER["REQUEST_URI"])
            ){
                if($arPath[1]!='catalog'){
                    echo "/catalog/";
                }
                elseif($arPath[1]=='catalog' && trim($arPath[2]) &&
                !trim($arPath[3])){
                    echo "/catalog/";
                }
                elseif($arPath[1]=='catalog' && trim($arPath[2]) &&
                trim($arPath[3])){
                    echo "/catalog/".$arPath[2]."/";
                }
                else{
                    echo "/catalog/";
                }

            }
            */
        ?>" onclick="window.history.go(-1);">
        <?  
            if(
                $arPath = explode("/",$_SERVER["REQUEST_URI"])
            ){
                if($arPath[1]!='catalog'){
                    //echo "Назад";
                    echo "Главная";
                }
                elseif($arPath[1]=='catalog' && trim($arPath[2]) &&
                !trim($arPath[3])){
                    //echo "Назад";
                    echo "Главная";
                }
                elseif(
                    $arPath[1]=='catalog' 
                    && 
                    trim($arPath[2]) 
                    &&
                    trim($arPath[3])
                ){
                    /*    
                    $arCatalogMeta = CIBlockSection::GetList(
                        array(),
                        array(
                            "IBLOCK_ID" =>  CATALOG_IB_ID,
                            "CODE"      =>  $DB->ForSql($arPath[2])
                        ),
                        false,
                        array("NAME"),
                        array("nTopCount"=>1)
                    )->GetNext();

                    if(
                        isset($arCatalogMeta["NAME"]) 
                        && 
                        trim($arCatalogMeta["NAME"])
                    )
                    echo $arCatalogMeta["NAME"];
                    */
                    echo "Главная";
                    
                    //echo "Назад";
                }
                else{
                    //echo "Назад";
                    echo "Главная";
                }

            }
        ?>
        </a>
      </div>
      <div class="ag-shop-mob-top-spacer">
        &#160;
      </div>
    <? endif ?>
      <div class="ag-shop__sidebar">
        <!-- Sidebar {{{-->
        <div class="ag-shop-sidebar">
          <div class="ag-shop-sidebar__logo-container"><a 
          class="ag-shop-sidebar__logo" href="http://ag.mos.ru/"></a></div>
          <div class="ag-shop-sidebar__social-container">
            <div class="ag-shop-sidebar__social-link"><a target="_blank"
            class="ag-shop-social-link ag-shop-social-link--vk"
            href="https://vk.com/citizenmoscow"></a></div>
            <div class="ag-shop-sidebar__social-link"><a target="_blank"
            class="ag-shop-social-link ag-shop-social-link--fb"
            href="https://www.facebook.com/citizenmoscow"></a></div>
            <div class="ag-shop-sidebar__social-link"><a target="_blank"
            class="ag-shop-social-link ag-shop-social-link--tw"
            href="https://twitter.com/citizenmoscow"></a></div>
            <div class="ag-shop-sidebar__social-link"><a target="_blank"
            class="ag-shop-social-link ag-shop-social-link--inst"
            href="https://www.instagram.com/citizenmoscow/"></a></div>
          </div>
          <button class="ag-shop-sidebar__up" type="button"></button>
        </div>
        <!-- }}} Sidebar-->
      </div>
    <? endif ?>


      <div class="ag-shop__main">
    <? if(
        !preg_match("#^/partners/#", $_SERVER["REQUEST_URI"])
        &&
        !IS_MOBILE
    ):?>
    <?$APPLICATION->IncludeComponent("ag:menu.top", "", array(
            "CACHE_TIME"      =>  COMMON_CACHE_TIME
        ),
        false
    );?>
    <? endif ?>

    <? if(IS_MOBILE):?>
        <?
        
            $APPLICATION->IncludeComponent("ag:menu.catalog", "", array(
                "CACHE_TIME"      =>  COMMON_CACHE_TIME
            ),
            false
        );
        
        ?>

    <? endif ?>

    <? if(
        !IS_MOBILE
        &&
        preg_match("#^/catalog/#", $_SERVER["REQUEST_URI"])
    ):?>
    <?$APPLICATION->IncludeComponent("ag:menu.catalog", "", array(
            "CACHE_TIME"      =>  COMMON_CACHE_TIME
        ),
        false
    );?>
    <? endif ?>

<? endif ?>


