<?php

class Shop_Service_Config
{
	
	static protected $_siteId = null;
	
	/**
	 * @return Shop_Service_Scheme
	 */
	static public function getScheme($siteId = null)
	{
		$siteId = is_null($siteId) ? self::getSiteId() : $siteId;
		if ($siteId == 'avtomrk') {
			return Shop_Service_Scheme_Avtomrk::getInstance();
		} else if ($siteId == 'planetspares') {
			return Shop_Service_Scheme_Planetspares::getInstance();
		}
		return Shop_Service_Scheme_Default::getInstance();
	}
	
	/**
	 * @return Shop_Service_Scheme
	 */
	static public function getBackendScheme($siteId = null)
	{
		$siteId = is_null($siteId) ? self::getSiteId() : $siteId;
		if ($siteId == 'planetspares') {
			return Shop_Service_Scheme_Backend::getInstance();
		}
		return Shop_Service_Scheme_Default::getInstance();
	}
	
	static public function getSettings()
	{
		
	}
	
	
	static public function getSiteId()
	{
		if (is_null(self::$_siteId) || !strlen(self::$_siteId)) {
			self::setSiteId(null);
		}
		return self::$_siteId;
	}
	
	static public function setSiteId($siteId = null)
	{
		self::$_siteId = is_null($siteId) ? Lv7CMS::getInstance()->getSiteId() : $siteId;
	}
	
	public static function getDefaultCurrencyId()
	{
		$modulesOptions = Lv7CMS::getInstance()->getCurrentSiteOption('modules');
		return $modulesOptions['Shop']['currency'];
	}
	
}