<?php

class Shop_Service_Scheme_Factory
{
	/**
	 * @return Shop_Service_Scheme
	 */
	static public function getSchemeInstance($schemeId)
	{
		switch ($schemeId) {
			case 'Default': return Shop_Service_Scheme_Default::getInstance();
			case 'Avtomrk': return Shop_Service_Scheme_Avtomrk::getInstance();
			case 'Backend': return Shop_Service_Scheme_Backend::getInstance();
			case 'Planetspares': return Shop_Service_Scheme_Planetspares::getInstance();
		}
		return null;
	}
	
}