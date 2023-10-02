<?php

class Shop_Model_Delivery_OtherCity extends Shop_Model_Delivery
{
	
	public function getName()
	{
		return 'Отправить транспортной компанией';
	}
	
	public function getDescr()
	{
		$descr = 'Стоимость доставки до транспортной компании ' . $this->getSettingValue('cost') . ' р. ';
		$descr .= 'Заказы более ' . $this->getSettingValue('free') . ' р. - доставляются до ТК бесплатно';
		return $descr;
	}

    protected function _initSettings()
    {
        parent::_initSettings();
        $this->_createSetting('cost', 'Стоимость доставки до ТК (руб)', Lv7CMS_DataTypes::FLOAT);
        $this->_createSetting('free', 'От какой суммы доставка до ТК бесплатна (руб)', Lv7CMS_DataTypes::FLOAT);
    }

	public function getCost()
	{
		$calculator = Shop_Service_Config::getScheme()->getCalculator();
		return ($calculator->getGoodsCost() >= $this->getSettingValue('free')) ? 0 : $this->getSettingValue('cost');
	}

    protected function _getFullSettingCode($shortCode)
    {
        return 'other_city_' . $shortCode;
    }
		
}