<?php
/*
 * user.class.php
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



    class bxUser{
        
        
        var $error = ''; //Текст ошибки
        
        /*
         * @param $login - логин пользователя в emp
         * @param $sessionId - ID сессии пользователя
         * @param $profile - опционально - данные профиля
         * @return true если всё ок
        */
        function login($login, $sessionId, $profile = array()){
            
            $bitrixLogin = "u$login";


            if(!isset($profile["personal"]["email"]) || !preg_match("#^[\d\w\-\.\_]+@[\d\w\-\.\_]+$#",$profile["personal"]["email"])){
                $this->error = 'Профиль пользователя не содержит корректный email';
                return false;
            }
            
            $password = substr(md5(time()),0,16);
            $email = $profile["personal"]["email"];
            $userData = array(
                "LOGIN"             =>  $bitrixLogin,
                "PASSWORD"          =>  $password,
                "CONFIRM_PASSWORD"  =>  $password,
                "EMAIL"             =>  $email,
                "GROUP_ID"          =>  array(2,3,4,6),
                "PERSONAL_GENDER"   =>  isset($profile["personal"]["sex"]) && $profile["personal"]["sex"]=='male'?'M':'F',
                "NAME"              =>  isset($profile["personal"]["firstname"]) && isset($profile["personal"]["middlename"])?$profile["personal"]["firstname"]." ".$profile["personal"]["middlename"]:'',
                "LAST_NAME"         =>  isset($profile["personal"]["surname"])?$profile["personal"]["surname"]:'',
                "PERSONAL_PHONE"    =>  isset($profile["personal"]["phone"])?$profile["surname"]["phone"]:'',
                "PERSONAL_BIRTHDAY" =>  isset($profile["personal"]["birthday"])?$profile["surname"]["birthday"]:'',
                "ACTIVE"            =>  "Y"
            );
            
            $objUser = new CUser;

            // Проверяем есть ли пользователь с таким логином в битриксе
            // Если пользователя нет - заводим
            $res = CUser::GetByLogin($bitrixLogin);
            if(!$arUser = $res->GetNext()){

                if(!$userId = $objUser->Add($userData)){
                    $this->error = "Ошибка добавления пользователя: ". print_r($objUser,1);
                    return false;
                }
                
                $res = $objUser->GetByID($iserId);
                $arUser = $res->GetNext();
                print_r($arUser);
            }
            else{
                if(!$objUser->Update($arUser["ID"], $userData)){
                    $this->error = "Ошибка обновления пользователя: ". print_r($objUser,1);
                    return false;
                }
                
            }
            $res = CUser::GetByLogin($bitrixLogin);
            $arUser = $res->GetNext();
            
            // Проверяем есть ли в жкрнале записи о его последних обновлениях профиля
            // Если информации об обновления профиля нет - заводим новую
            if(!$updateRecord = $this->getUpdateRecord($login,$email)){
                $this->createUpdateRecord($login, $email, $sessionId);
                $updateRecord = $this->getUpdateRecord($login,$email);
            }
            else{
                $this->setLastUpdateTime($login, $email);
            }
            
            // Авторизуемся
            $objUser->Authorize($arUser["ID"]);
            
            return true;
        }
        
        /*
         * Получение записи в реестре обновлений профиля пользователя
         * 
         * @param $login - логин пользователя в emp
         * @param $email - email         
         * @return массив с записью о последнем обновлении
        */
        
        private function getUpdateRecord($login, $email){
            global $DB;
            $query = "SELECT * FROM int_profile_import WHERE login='$login' AND email='$email' ORDER BY last_update DESC LIMIT 1";
            $res = $DB->Query($query);
            return $res->GetNext();
        }
        
        
        /*
         * Создание записи в реестре обновлений профиля пользователя
         * 
         * @param $login - логин пользователя в emp
         * @param $email - email         
         * @return ID вставленной записи
        */
        private function createUpdateRecord($login, $email, $sessionId){
            global $DB;
            $query = "INSERT INTO `int_profile_import`(`login`,`email`,`session_id`,`last_update`)
            VALUES('$login', '$email', '$sessionId',UNIX_TIMESTAMP(NOW()))";
            $DB->Query($query);
            return  $DB->LastID();
        }
        
        /*
         * Обновление у записи в реестре обновлений профиля пользователя времени последнего обновления
         * 
         * @param $login - логин пользователя в emp
         * @param $email - email         
         * @param $timeStamp - время, которое надо установить (если пусто - вставляется текущее)
         * @return 
        */
        private function setLastUpdateTime($login, $email, $timeStamp = ''){
            global $DB;
            $query = "UPDATE `int_profile_import` SET `last_update`=".($timeStamp?$timeStamp:"UNIX_TIMESTAMP(NOW())")."
            WHERE `login`='$login' AND `email`='$email' LIMIT 1";
            $DB->Query($query);
            return true;
        }
        
        
    }
