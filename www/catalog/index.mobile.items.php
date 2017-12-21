<?
    /**
     * Формирование json для поиска
    */
    
    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
    require("mobile.filter.params.php");

    require_once($_SERVER["DOCUMENT_ROOT"]
        ."/local/libs/classes/CAGShop/CCatalog/CCatalogProduct.class.php"
    );
    use AGShop\Catalog as Catalog;
    
    $objProduct = new \Catalog\CCatalogProduct;
    
    // Получаем IDs продуктов, подходящих под условия
    $arParams["pagination"] =  ["page"=>1,"onpage"=>1000];
    $arProducts = $objProduct->getTeasers($arParams);
    
    $arResult = [];
    foreach($arProducts["items"] as $arProduct)$arResult[] = [
        "item"=>$arProduct["NAME"]
    ];
    
    echo json_encode($arResult);

/*
[
{ "item": "Билет в музей" },
{ "item": "Рюкзак зеленый" },
{ "item": "Рюкзак белый" },
{ "item": "Холщовая сумка с символикой проекта" },
{ "item": "Парковочное пространство" },
{ "item": "Монопод для смартфона" }
]
*/
