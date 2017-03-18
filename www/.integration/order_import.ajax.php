<?php
    if(!isset($_SERVER["DOCUMENT_ROOT"]) || !$_SERVER["DOCUMENT_ROOT"])
        $_SERVER["DOCUMENT_ROOT"] = realpath(dirname(__FILE__)."/..");
    
    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

    $uploadDir = $_SERVER["DOCUMENT_ROOT"]."/upload/1c_exchange/";
    $CatalogIblockId = CATALOG_IB_ID;
    $OfferIblockId = OFFER_IB_ID;
    
    $res = CSaleOrder::GetList(array("DATE_INSERT"=>"ASC"));
    
    // Получаем имя файла заказов
    $ordersFilename = $_GET["filename"];
    if(!$ordersFilename){
        $dd = opendir($uploadDir);
        while($filename = readdir($dd))
            if(preg_match("#^orders.*\.xml$#",$filename))
                {$ordersFilename = $filename;break;}
        closedir($dd);
    }
   
    // Если имя файла ZIP, распаковываем перед употреблением
    if(preg_match("#^.*\.zip$#",$ordersFilename)){
	    $zipFilename = $uploadDir.$ordersFilename;
	    if(!file_exists($zipFilename)){
	        echo "failed\n$zipFilename is not exists";
	        die;
	    }
	
        $zip = new ZipArchive();
        if(!$zip->open($zipFilename)){
            echo "failed\n";
            echo "Cant open $zipFilename";
            die;
        }
        if(!$nZipFilesCount = $zip->numFiles){
            echo "failed\n";
            echo "Archive hasnt any files";
            die;
        }
        if($nZipFilesCount>1){
            echo "failed\n";
            echo "Archive has more than 1 file";
            die;
        }
        $arZipStat = $zip->statIndex (0);
        if(!$arZipStat["name"] || !preg_match("#^.*\.xml$#",$arZipStat["name"])){
            echo "failed\n";
            echo "Archive hasnt xml file";
            die;
        }
        if(!$zip->extractTo($uploadDir)){
            echo "failed\n";
            echo "Cant extract archive";
            die;
        }
        
        $ordersFilename = $arZipStat["name"];
    }
    
    CModule::IncludeModule("sale");
    CModule::IncludeModule("catalog");
    CModule::IncludeModule("iblock");
    CModule::IncludeModule("price");
    global $DB;
    $objOrder   = new CSaleOrder;
    $objUser    = new CUser;
    $objBasket  = new CSaleBasket;
    $objIBlockElement = new CIBlockElement;
    $objPrice = new CPrice;
    
   
    header("Content-type: text/plain; charset=UTF-8");
//    echo file_get_contents($uploadDir.$ordersFilename);
//    die;
    if(file_exists($uploadDir.$ordersFilename)){
        $xmlOrders = file_get_contents($uploadDir.$ordersFilename);
        $arOrders = simplexml_load_string($xmlOrders, "SimpleXMLElement" );
        $arOrders = json_decode(json_encode((array)$arOrders), TRUE);        

        // Нормализуем массив заказов
        if(!isset($arOrders["Документ"][0]))
            $arOrders["Документ"] = array($arOrders["Документ"]);

        foreach($arOrders["Документ"] as $ccc=>$arDocument){
            $arDocument["Телефон"] = preg_replace("#[^\d]#","",$arDocument["Телефон"]);
            if(0 && $ccc>5){break;}else{
                //echo "      ".round(($t1-$t0)*1000,2)."ms\n$ccc) ";
            }
            $t0 = microtime(true);
            // Поиск заказа под XML-Ид
            $res = CSaleOrder::GetList(
                array(),array("XML_ID"=>$arDocument["Ид"]),false,array("nTopCount"=>1),
                array("ID","PAYED","STATUS_ID","ADDITIONAL_INFO")
            );
            $existsOrder = $res->GetNext();

            // Поиск заказа по номеру
            if(!$existsOrder){
                $res = CSaleOrder::GetList(
                    array(),array("ADDITIONAL_INFO"=>$arDocument["Номер"]),false,
                    array("nTopCount"=>1),
                    array("ID","PAYED","STATUS_ID","ADDITIONAL_INFO")
                );
                $existsOrder = $res->GetNext();
            }
                        
            
            // Бортуем заказы с неверно указанным телефоном
            if(!preg_match("#^\d{7,11}$#",$arDocument["Телефон"])){
                echo "Order_num=".$arDocument["Номер"].
                        ": Incorrect phone ".print_r($arDocument["Телефон"],1)."\n";
                $t1 = microtime(true);
                continue;
            }

            // Нормализация товаров
            if(!isset($arDocument["Товары"]["Товар"][0]))
                $arDocument["Товары"]["Товар"] = array($arDocument["Товары"]["Товар"]);
            // пОЛУЧЕНИЕ МАССИВА ТОВАРОВ КОРЗИНЫ
            $basketProducts = array();
            foreach($arDocument["Товары"]["Товар"] as $product){
                if(!isset($product["Ид"])){
                    echo "Order_num=".$arDocument["Номер"].
                        ":  Incorrect product XML_ID
                        ".print_r($product["Ид"],1)."\n";
                    continue;
                }
                
                $XML_ID = $product["Ид"];
                $resOffer = CIblockElement::GetList(
                    array(),array("IBLOCK_ID"=>$OfferIblockId,"XML_ID"=>$XML_ID),false,
                    array("nTopCount"=>1),array("ID")
                );
                $existsOffer = $resOffer->GetNext();
                // Если продукта нет - создаём его прототип
                if(!$existsOffer){
                    $product["product_id"] = explode("-",$product["Ид"]);
                    $product["product_id"] = $product["product_id"][0];
                    $product["product_xml_id"] = explode("#",$product["Ид"]);
                    $product["product_xml_id"] = $product["product_xml_id"][0];
                    // Создаём элемент каталога
                    $arrFields = array(
                        "SITE_ID"       =>  "s1",
                        "XML_ID"        =>  $product["product_xml_id"],
                        "NAME"          =>  $product["Наименование"],
                        "CODE"          =>  Cutil::translit($product["Наименование"],"ru",
                            array("replace_space"=>"-","replace_other"=>"-")
                        )."-".$product["product_id"],
                        "IBLOCK_ID"     =>  $CatalogIblockId,
                        "DETAIL_TEXT"   =>  '',
                        "PREVIEW_TEXT"  =>  '',
                        "IBLOCK_SECTION_ID" =>  1,//$categoryId,
                        "SECTION_ID"    =>  1,//$categoryId,
                        "PREVIEW_TEXT_TYPE" =>  'html',
                        "DETAIL_TEXT_TYPE"  =>  'html'
                    );
                    
                    $resCatalog = CIblockElement::GetList(array(),array(
                        "CODE"=>$arrFields["CODE"]
                    ),false,array("nTopCount"=>1));
                    $arCatalog = $resCatalog->GetNext();
                    
                    if (isset($arCatalog["ID"])) {
                        $id = $arCatalog["ID"];
                    }
                    elseif(!$id = $objIBlockElement->Add($arrFields)){
                        echo "Order_num=".$arDocument["Номер"].
                            ": Cant create catalog item ".print_r($arrFields, 1)."\n";
                        $t1 = microtime(true);
                        continue;
                    }
                    else{
                        //print_r($arrFields);
                        //print_r($objIBlockElement);
                        //print_r($product);
                        //echo "failed\n";
                        //echo "Error: ".__FILE__.":".__LINE__;
                        //continue;
                    }
                    
                    // Создаём торговое предложение
                    $arrFields["IBLOCK_ID"] = $OfferIblockId;
                    $arrFields["PRICE"] = $product["ЦенаЗаЕдиницу"];
                    $arrFields["XML_ID"] = $product["Ид"];
                    if(!$offerId = $objIBlockElement->Add($arrFields)){
                        echo "Order_num=".$arDocument["Номер"].
                            ": Cant offer item ".print_r($arrFields, 1)."\n";
                        $t1 = microtime(true);
                        continue;
                    }
                    
                    // Назначаем свойства торгового предложения
                    CIBlockElement::SetPropertyValueCode($offerId,"CML2_LINK",$id);
                    
                    // Создаём цену
                    $objPrice->Add(array(
                        "PRODUCT_ID"=>$offerId,
                        "CATALOG_GROUP_ID"=>1,
                        "PRICE"=>$product["ЦенаЗаЕдиницу"],
                        "CURRENCY"=>"BAL",
                    ),true);
                    
                    // Создаём товар на складе
                    CCatalogProduct::Add(array(
                        "ID"=>$offerId,
                        "QUANTITY"=>0,
                        "QUANTITY_TRACE"=>"Y",
                        "CAN_BUY_ZERO"=>"N",
                    ));
                    
                    // Получаем инормацию о получившемся торговом предложении
                    $resOffer = CIBlockElement::GetList(
                        array(),array("IBLOCK_ID"=>$OfferIblockId,"ID"=>$offerId),
                        false,array("nTopCount"=>1)
                    );
                    $existsOffer = $resOffer->GetNext(); 
                }
                // Запоминаем товар для добавления в корзину
                $basketProducts[$existsOffer["ID"]] = array(
                    "count" => $product["Количество"],
                    "name"  => $product["Наименование"],
                    "price" => $product["ЦенаЗаЕдиницу"]
                );
            }
            
            
            // Считаем сумму заказа
            $sum = 0;
            foreach($basketProducts as $product)$sum+=$product["count"]*$product["price"];
            // Бортуем заказы с нулевой суммой
            /*
            if(!$sum){
                //echo "Empty order sum, OrderId =".$arDocument["Ид"];
                $t1 = microtime(true);
                continue;
            }
            */

            // Выделяем из ФИО фамилию и Имя-Отчество
            $tmpName = explode(" ", $arDocument["Клиент"]);
            $userLastName = $tmpName[0];unset($tmpName[0]);
            $userName = implode(" ",$tmpName);
            // Делаем пользователю случайный пароль
            $password = mb_substr(md5(rand()),0,10);
            
            // Данные для добавления пользователя
            $userData = array(
                "LOGIN"             =>  "u".$arDocument["Телефон"],
                "PASSWORD"          =>  $password,
                "CONFIRM_PASSWORD"  =>  $password,
                "EMAIL"             =>  $arDocument["ЭлектроннаяПочта"],
                "GROUP_ID"          =>  array(2,3,4,6),
                "NAME"              =>  $userName, 
                "LAST_NAME"         =>  $userLastName,
                "PERSONAL_PHONE"    =>  $arDocument["Телефон"],
                "ACTIVE"            =>  "Y"
            );
            
            // Определяем пользователя, если нет - создаём
            $resUser = CUser::GetByLogin($userData["LOGIN"]);
            $existsUser = $resUser->GetNext();
            // Если пользователя нет - создаём
            
            
            if(!$existsUser){
                // Если создание провалилось - сообщаем об ошибке
                if(!$userId = $objUser->Add($userData)){
                    echo "Order_num=".$arDocument["Номер"].
                        ": Cant create user ".print_r($UserData, 1)."\n";
                    $t1 = microtime(true);
                    continue;
                }
            }
            else{
                $userId = $existsUser["ID"];
                $objUser->Update($userId, $userData);
            }
            
            // Вычисляем флаги статуса
            if(!isset($arDocument["История"]["Состояние"][0]))
                $arDocument["История"]["Состояние"] = array($arDocument["История"]["Состояние"]);
            // Состояние заказа по умолчанию
            if(!isset($arDocument["История"]["Состояние"][0]["СостояниеЗаказа"]))
                $arDocument["История"]["Состояние"][0]["СостояниеЗаказа"] = 'В работе';
            
            // Статус и опрлата по умолчанию    
            $statusId = "N";$canceled = "N";
            switch($arDocument["История"]["Состояние"][0]["СостояниеЗаказа"]){
                case 'В работе':
                    $statusId = "N";$canceled = "N";
                break;
                case 'Аннулирован':
                    $statusId = "AI";$canceled = "N";
                break;
                case 'Брак':
                    $statusId = "AC";$canceled = "N";
                break;
                case 'Выполнен':
                    $statusId = "F";$canceled = "N";
                break;
                case 'Отменен':
                    $statusId = "AG";$canceled = "Y";
                break;
            }
            
            $arOrder = array(
                "ADDITIONAL_INFO"    =>  $arDocument["Номер"],
                "LID"                =>  "s1",
                "XML_ID"             =>  $arDocument["Ид"],
                "PERSON_TYPE_ID"     =>  1,
                "PAYED"              =>  isset($existsOrder["PAYED"])?$existsOrder["PAYED"]:"N",
                "CANCELED"           =>  $canceled,
                "STATUS_ID"          =>  $statusId,
                "CURRENCY"           =>  "BAL",
                "USER_ID"            =>  $userId,
                "PAY_SYSTEM_ID"      =>  9,
                "PRICE_DELIVERY"     =>  0,
                "DELIVERY_ID"        =>  3,
                "DISCOUNT_VALUE"     =>  0,
                "TAX_VALUE"          =>  0,
                "DATE_INSERT"        =>  $DB->FormatDate(
                    $arDocument["Дата"]." ".$arDocument["Время"],
                    "YYYY-MM-DD HH:MI:SS",
                    "DD.MM.YYYY HH:MI:SS"
                ),
                "DATE_UPDATE"        =>  $DB->FormatDate(
                    trim($arDocument["Дата"])." ".trim($arDocument["Время"]),
                    "YYYY-MM-DD HH:MI:SS",
                    "DD.MM.YYYY HH:MI:SS"
                )
            );
            if($sum){
                $arOrder["SUM_PAID"] = $sum;
                $arOrder["PRICE"] = $sum;
            }
            
            // Определяем ID склада
            $resStorage = CCatalogStore::GetList(array(),array("XML_ID"=>$arDocument["Склад"]),
                false,array("nTopCount"=>1),array("ID"));
            $arStorage = $resStorage->GetNext();
            $storeId = 0;
            if(!isset($arStorage["ID"]))$storeId = $arStorage["ID"];
            if($storeId)$arOrder["STORE_ID"] = $storeId;
            
            // Если заказа нет - создаём, есть - обновляем
            if(!$existsOrder && !preg_match("#^.*\-\d+$#i", $arOrder["ADDITIONAL_INFO"])){
                if(!$orderId = $objOrder->Add($arOrder)){
                    echo "failed\n";
                    echo "Not created";
                    $t1 = microtime(true);
                    continue;
                }

                //echo "Add order_id=$orderId  ";

                // Прицепить сессии корзину
                $userBasketId = $objBasket->GetBasketUserID();
                // Добавляем в корзину продукты
                foreach($basketProducts as $productId=>$item){
            	    $strSql = "INSERT INTO b_sale_basket(FUSER_ID, ORDER_ID, PRODUCT_ID, QUANTITY, NAME, PRICE, DATE_UPDATE, CURRENCY, LID, MODULE, CAN_BUY, DELAY)
            	    VALUES(
                    '".$userId."', 
                	'".$orderId."', 
                        '".$productId."',
                        '".$item["count"]."',
                        '".$DB->ForSql($item["name"])."',
                        '".$item["price"]."',
                        '".$DB->GetNowFunction()."',
                        'BAL',
                        's1',
                        'catalog',
                        'Y',
                        'N'
                    )";
            	    $DB->Query($strSql);
                }
                CSaleBasket::OrderBasket($orderId, $userBasketId);
                //CSaleOrder::PayOrder($orderId,"Y",false,false); //?????
                // Удаляем транзакцию, вызвагую этим заказом (ибо через импорт баллов она придёт)
                /*
                $objTransact = new CSaleUserTransact;
                $arTransact = $objTransact->GetList(array(),array("ORDER_ID"=>$orderId))->GetNext();
                if(isset($arTransact["ID"]))$objTransact->Delete($arTransact["ID"]);
                */
            }
            elseif($existsOrder){
                $orderId = $existsOrder["ID"];
                //echo "Update order_id = $orderId ";
                
                // Обрабатываем все статусы кроме отмены
                CSaleOrder::Update($orderId, $arOrder);
                if($existsOrder["STATUS_ID"]!=$statusId && $statusId!='AG'){
                    // Меняем статус
                    CSaleOrder::StatusOrder($orderId, $statusId);
                    eventOrderStatusSendEmail($orderId, $statusId, ($arFields = array()), $statusId);
                }
                // Обрабатываем отмену
                elseif($existsOrder["STATUS_ID"]!=$statusId && $statusId=='AG'){
                    
    
                    $login = "u".$arDocument["Телефон"];
                    // Считаем сумму заказа
                    $orderSum = $arOrder["SUM_PAID"];

                    // Отменяем оплату и возвращаем баллы только если заказ сделан из битрикса
                    $moneyBack = false;
                    if(preg_match("#^.*\-\d+$$#", $existsOrder["ADDITIONAL_INFO"])){
                        require_once($_SERVER["DOCUMENT_ROOT"]."/.integration/classes/order.class.php");
                        $obOrder = new bxOrder();
                        if(!$obOrder->addEMPPoints($orderSum,"Отмена заказа Б-".$existsOrder["ID"]." в магазине поощрений АГ",$login)){
                            echo "Points transaction error: ".$obOrder->error;
                        }
                        $moneyBack = true;
                    }

                    CSaleOrder::PayOrder($existsOrder["ID"],"N",true,false);
                    CSaleOrder::StatusOrder($existsOrder["ID"], $statusId);
                    if(!CSaleOrder::CancelOrder($existsOrder["ID"],"Y","Передумал")){
                        $answer["error"] .= "Заказ не был отменён.";
                    }
                }


                
                // Ищем корзину для этого заказа
                //// $resBasket = CSaleBasket::GetList(array(),array("ORDER_ID"=>$orderId),false,array("nTopCount"=>1));
                // Удаляем корзины заказа
                //// while($arBasket = $resBasket->GetNext())CSaleBasket::Delete($arBasket["ID"]);
                
                /*
                foreach($basketProducts as $productId=>$item){
            	    
            	    $strSql = "INSERT INTO b_sale_basket(FUSER_ID, ORDER_ID, PRODUCT_ID, QUANTITY, NAME, PRICE, DATE_UPDATE, CURRENCY, LID, MODULE, CAN_BUY, DELAY)
            	    VALUES(
        		'".$userId."', 
                	'".$orderId."', 
                        '".$productId."',
                        '".$item["count"]."',
                        '".$DB->ForSql($item["name"])."',
                        '".$item["price"]."',
                        '".$DB->GetNowFunction()."',
                        'BAL',
                        's1',
                        'catalog',
                        'Y',
                        'N'
                    )";
            	    $DB->Query($strSql);
                }
                */
            }
            $t1 = microtime(true);
        }
        
    }
    
    echo "success";
?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");?>

