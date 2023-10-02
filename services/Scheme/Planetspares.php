<?php

class Shop_Service_Scheme_Planetspares extends Shop_Service_Scheme_Default
{

	/**
	 * Singleton instance
	 *
	 * @var Shop_Service_Scheme_Default
	 */
	protected static $_instance = null;
	
	
	/**
	 * Singleton instance
	 *
	 * @return Shop_Service_Scheme_Default
	 */
	public static function getInstance()
	{
		if (null === self::$_instance) {
			self::$_instance = new self();
		}
		
		return self::$_instance;
	}
	
	
	public function getName()
	{
		return 'Настройка магазина "Планета запчастей"';
	}
	
	
	protected function _getBasketFormContent($disabled)
	{
		if (!isset($this->_basketFormContent[$disabled])) {
			$this->_basketFormContent[$disabled] = $this->_view->action('grid-form', 'basket', 'shop', array('disabled' => $disabled));
		}
		return $this->_basketFormContent[$disabled];
	}
	
	
}