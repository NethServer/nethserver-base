<?php
/**
 * @package Directory

 */

/**
 * Change the system time settings
 *
 * @package Module
 * @author Giacomo Sanchietti<giacomo.sanchietti@nethesis.it>
 */
class NethServer_Module_Directory extends Nethgui_Core_Module_Standard implements Nethgui_Core_TopModuleInterface
{
    public function getParentMenuIdentifier()
    {
        return 'Administration';
    }

    public function initialize()
    {
        parent::initialize();
        $this->declareParameter('defaulCity', self::VALID_ANYTHING, array('configuration', 'ldap', 'defaultCity'));
        $this->declareParameter('defaulCompany', self::VALID_ANYTHING, array('configuration', 'ldap', 'defaultCompany'));
        $this->declareParameter('defaulDepartment', self::VALID_ANYTHING, array('configuration', 'ldap', 'defaultDepartment'));
        $this->declareParameter('defaulPhoneNumber', self::VALID_ANYTHING, array('configuration', 'ldap', 'defaultPhoneNumber'));
        $this->declareParameter('defaulStreet', self::VALID_ANYTHING, array('configuration', 'ldap', 'defaultStreet'));
        
        $this->requireEvent('ldap-update');
    }

    public function bind(Nethgui_Core_RequestInterface $request)
    {
        parent::bind($request);
    }


}

