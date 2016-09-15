<?php
/*
 * 1c_catalog.ajax.php
 * 
 * Copyright 2016 Андрей Инюцин <inutcin@yandex.ru>
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 * 
 * 
 */
    require(
        $_SERVER["DOCUMENT_ROOT"].
        "/bitrix/modules/main/include/prolog_before.php"
    );
    
    $uploadDir = $_SERVER["DOCUMENT_ROOT"]."/upload/1c_catalog/";
    // Получаем имя файла каталога
    $dd = opendir($uploadDir);
    $importFilename = '';
    while($filename = readdir($dd))
        if(preg_match("#^import.*\.xml$#",$filename))
            {$importFilename = $filename;break;}
    closedir($dd);
    
    // Получаем имя файла товарных предложений
    $dd = opendir($uploadDir);
    $offersFilename = '';
    while($filename = readdir($dd))
        if(preg_match("#^offers.*\.xml$#",$filename))
            {$offersFilename = $filename;break;}
    closedir($dd);

    CModule::IncludeModule("catalog");
    CModule::IncludeModule("iblock");

    if($importFilename){
        include("includes/import.inc.php");
    }

    if($offersFilename){
        $xmlOffers = file_get_contents($uploadDir.$offersFilename);
        $obOffers = simplexml_load_string(
            $xmlOffers, "SimpleXMLElement" 
        );
        
        $arOffers = json_decode(json_encode((array)$obOffers), TRUE);        
        
        $arStorages = $arOffers["ПакетПредложений"]["Склады"]["Склад"];
        $arOffers = $arOffers["ПакетПредложений"]["Предложения"]["Предложение"];
        
        include("includes/storages.inc.php");
        include("includes/offers.inc.php");
    }


    
    echo json_encode($answer);
    require(
        $_SERVER["DOCUMENT_ROOT"].
        "/bitrix/modules/main/include/epilog_after.php"
    );
