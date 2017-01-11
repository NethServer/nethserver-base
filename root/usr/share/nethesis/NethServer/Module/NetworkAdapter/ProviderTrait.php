<?php

namespace NethServer\Module\NetworkAdapter;

/*
 * Copyright (C) 2017 Nethesis S.r.l.
 * http://www.nethesis.it - nethserver@nethesis.it
 *
 * This script is part of NethServer.
 *
 * NethServer is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License,
 * or any later version.
 *
 * NethServer is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with NethServer.  If not, see COPYING.
 */

trait ProviderTrait {

     public function getWeightAdapter()
     {
         return $this->getPlatform()->getMapAdapter(array($this, 'readWeight'), array($this, 'writeWeight'), array());
     }

     public function getProviderNameAdapter()
     {
         return $this->getPlatform()->getMapAdapter(array($this, 'readProviderName'), array($this, 'writeProviderName'), array());
     }

     public function createProviderNameValidator()
     {
         return $this->createValidator()->maxLength(5)->minLength(1)->regexp('/^(?:(?!main).)*$/')->regexp('/^(?:(?!local).)*$/')->regexp('/^(?:(?!\s).)*$/');
     }

     public function createWeightValidator()
     {
         return $this->createValidator()->integer()->greatThan(0)->lessThan(256);
     }
     
     public function createBandwidthValidator()
     {
         return $this->createValidator()->orValidator(
             $this->createValidator()->integer()->greatThan(0),
             $this->createValidator(\Nethgui\System\PlatformInterface::EMPTYSTRING)
         );
     }

     public function readProviderName()
     {
         foreach ($this->getPlatform()->getDatabase('networks')->getAll('provider') as $name=>$provider) {
             if ($provider['interface'] === $this->parameters['device']){
                 return $name;
             }
         }
     }

     public function writeProviderName($name)
     {
         if ($this->parameters['role']!='red') {
             $this->getPlatform()->getDatabase('networks')->deleteKey($name);
             return TRUE;
         }
         foreach ($this->getPlatform()->getDatabase('networks')->getAll('provider') as $name=>$provider) {
             if ($provider['interface'] === $this->parameters['device']){
                 if ($this->parameters['ProviderName']!=$name) {
                      $this->getPlatform()->getDatabase('networks')->deleteKey($name);
                 } else {
                     $this->getPlatform()->getDatabase('networks')->setProp($this->parameters['ProviderName'],array('interface'=>$this->parameters['device'],'weight'=>$this->parameters['Weight']));
                     return TRUE;
                 }
             }
         }
         $this->getPlatform()->getDatabase('networks')->setKey($this->parameters['ProviderName'],'provider',array());
         $this->getPlatform()->getDatabase('networks')->setProp($this->parameters['ProviderName'],array('interface'=>$this->parameters['device'], 'weight'=>$this->parameters['Weight'] ));
         return TRUE;
     }

     public function readWeight()
     {
         foreach ($this->getPlatform()->getDatabase('networks')->getAll('provider') as $name=>$provider) {
             if ($provider['interface'] === $this->parameters['device']) {
                 return $provider['weight'];
             }
         }
     }

     public function writeWeight()
     {
         foreach ($this->getPlatform()->getDatabase('networks')->getAll('provider') as $name=>$provider) {
             if ($provider['interface'] === $this->parameters['device']) {
                 $this->getPlatform()->getDatabase('networks')->setProp($name,array('weight'=>$this->parameters['Weight']));
                 return TRUE;
             }
         }
     }

     public function getDefaultProviderName(){
         $providers = $this->getPlatform()->getDatabase('networks')->getAll();
         for ($i = 1; $i <= count($providers) + 1; $i++) {
             if ( ! isset($providers['red' . $i])) {
                 return 'red' . $i;
             }
         }
     }
 }