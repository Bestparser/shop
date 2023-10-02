<?php

use Modules\Company\Models\Mappers\Territories;

abstract class Shop_Model_Delivery
{

    protected $_settings = [];

	public function getId()
	{
		return substr(get_class($this), 20);
	}
	
	public function getName()
	{
		return '';
	}
	
	public function getDescr()
	{
		return '';
	}

	public function setDescr()
	{
		return '';
	}

	public function getSettings()
	{
        if (!count($this->_settings)) {
            $this->_initSettings();
        }
		return $this->_settings;
	}

	public function getCost()
	{
		return 0;
	}
	
	public function isPossible()
	{
		return true;
	}

    protected function _initSettings()
    {
        $this->_createSetting('territory', 'Территория', 'territories');
    }

    protected function _createSetting($shortCode, $name, $type)
    {
        $this->_settings[] = new Shop_Model_Setting($this->_getFullSettingCode($shortCode), $name, $type);
    }
	
    public function getSettingValue($shortCode)
    {
        $settings = Shop_Service_Config::getScheme()->getSettings();
        return $settings[$this->_getFullSettingCode($shortCode)] ?? null;
    }

    protected function _getFullSettingCode($shortCode)
    {
        return strtolower($this->getId()) . '_' . $shortCode;
    }

    public function getTerritory()
    {
        return $this->getSettingValue('territory')
            ? Territories::findOrFail($this->getSettingValue('territory'))
            : null;
    }

}