<?php

class Shop_SettingsController extends Lv7CMS_Controller_Backend
{


	public function postDispatch()
	{
		parent::postDispatch();
		$this->view->headScript()->appendFile($this->_dataUrl . '/js/jquery.cookie.js');
		$this->view->headScript()->appendFile($this->_dataUrl . '/js/modules/shop/settings.js');
	}

	public function indexAction()
	{
		$schemes = $this->_getAccessibleSchemes();
		$this->view->schemes = $schemes;
	}
	
	public function formAction()
	{
		$accessibleSchemes = $this->_getAccessibleSchemes();
		$schemeId = $this->_getParam('scheme');
		if (!isset($accessibleSchemes[$schemeId])) {
			return false;
		}
		$scheme = Shop_Service_Scheme_Factory::getSchemeInstance($schemeId);
		
		$settingCollection = $scheme->getSettingCollection();
		$settingValues = $scheme->getSettings();
		$form = $this->_getForm($settingCollection);
		
		if ($this->getRequest()->isPost()) {
			if ($form->isValid($this->getRequest()->getPost())) {

				$settingMapper = new Shop_Model_Mapper_SettingValues();
				//$schemeId = Shop_Service_Config::getScheme()->getId();
				foreach ($settingCollection as $blockName => $settings) {
					foreach ($settings as $setting) {
						$code = $setting->getCode();
						$value = $form->getValue($code);
						$settingMapper->update($schemeId, $code, $value);
					}
				}

				$this->_helper->redirector->gotoRouteAndExit(array(), 'shop::settings');
			}
				
		} else {
			foreach ($settingCollection as $blockName => $settings) {
				foreach ($settings as $setting) {
					$code = $setting->getCode();
					$form->setDefault($code, $settingValues[$code]);
				}
			}
		}
		
		$this->view->form = $form;
	}


	protected function _getForm($settingCollection)
	{
		$form = new Shop_Form_Settings();
		
		$tabs = array();
		foreach ($settingCollection as $blockName => $settings) {
			$flds = array();
			foreach ($settings as $setting) {
				$flds[] = $setting->getCode();
                switch($setting->getType()) {
                    case 'territories':
                        $element = Lv7CMS_Form_ElementFactory::create(
                            Lv7CMS_DataTypes::SELECT,
                            $setting->getCode(),
                            $setting->getName(),
                            false, false, false,
                            \Modules\Company\Models\Mappers\Territories::asOptions());
                        break;
                    default:
                        $element = Lv7CMS_Form_ElementFactory::create(
                            $setting->getType(),
                            $setting->getCode(),
                            $setting->getName());
                }
				$form->addElement($element);
			}
			$tabs[strip_tags($blockName)] = array(
				'elements' => $flds,
				'decorators' => array(Lv7CMS_Form::D_TWO_COL)
			);
		}
		$form->addTabs($tabs, 'formTabs');
		$form->addStdButtons();
		return $form;
	}

	
	protected function _getAccessibleSchemes()
	{
		$scheme = Shop_Service_Scheme_Default::getInstance();
		
		$sites = $this->_getAccessibleSite();
		$schemes = array();
		if (is_array($sites) && count($sites)) {
			foreach ($sites as $siteId => $site) {
				$scheme = Shop_Service_Config::getScheme($siteId);
				if (!isset($schemes[$scheme->getId()])) {
					$schemes[$scheme->getId()] = $scheme;
				}
			}
		}
		return $schemes;
	}
	
	protected function _getAccessibleSite()
	{
		return Lv7CMS_Acl_Service::getAccessibleSites('shopSettings', null, 'Shop');
	}
	
}

