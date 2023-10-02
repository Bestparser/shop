<?php

class Shop_Model_Delivery_MkadIn extends Shop_Model_Delivery
{
	
	public function getName()
	{
		return 'Доставка - Москва <small>(в пределах МКАД)</small>';
	}
	
	public function getDescr()
	{
		$descr = 'Стоимость доставки ' . $this->getSettingValue('base_cost') . ' р. ';
		$descr .= 'Заказы более ' . $this->getSettingValue('cost_free') . ' р. доставляются бесплатно';
		return $descr;
	}


	protected function _initSettings()
	{
        parent::_initSettings();
        $this->_createSetting('base_cost', 'Стоимость доставки (руб)', Lv7CMS_DataTypes::FLOAT);
        $this->_createSetting('cost_free', 'От какой суммы доставка бесплатна (руб)', Lv7CMS_DataTypes::FLOAT);
	}

	public function getCost()
	{
		$calculator = Shop_Service_Config::getScheme()->getCalculator();
		return ($calculator->getGoodsCost() >= $this->getSettingValue('cost_free')) ? 0 : $this->getSettingValue('base_cost');
	}



}