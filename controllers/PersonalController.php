<?php

class Shop_PersonalController extends Lv7CMS_Controller_Frontend
{
	protected $_user;
	
	public function init()
	{
		parent::init();
		$this->_user = Zend_Registry::get('user');
		if ($this->_user->isGuest()) {
			$this->_helper->redirector->gotoRouteAndExit(array(), 'users::login');
		}		
	}
	

	public function ordersAction()
	{
		$mapper = new Shop_Model_Mapper_Orders();
		$dbSelect = $mapper->getDbSelect();
		$dbSelect->where('user = ?', $this->_user->id);
		$paginator = Zend_Paginator::factory($dbSelect);
		$currentPage = $this->_getParam('page', 1);
		$paginator->setCurrentPageNumber($currentPage);
		$total = $paginator->getTotalItemCount();
		
		$statusMapper = new Shop_Model_Mapper_OrderStatus();
		$statusList = $statusMapper->getList();
		
		$rows = $paginator->getItemsByPage($currentPage);
		if ($rows->count()) {
			$items = array();
			foreach ($rows as $row) {
				$item = $mapper->rowToObject($row);
				if ($item->status) {
					$item->statusName = ($statusList[$item->status] ? $statusList[$item->status]->name : false);
				}
				$items[] = $item;
			}
		}
		$this->view->sites = Lv7CMS::getInstance()->getSites();
		$this->view->items = $items;
		$this->view->currentPage = $currentPage;
		$this->view->paginator = ($total > 10) ? $paginator : '';
	}
	
	public function orderAction()
	{
		$currentPage = $this->_getParam('page', 1);
		$id = $this->_getParam('id');
		$mapper = new Shop_Model_Mapper_Orders();
		$order = $mapper->find($id);
		if ($this->_user->id != $order->user) {
			return;
		}
		
		$statusMapper = new Shop_Model_Mapper_OrderStatus();
		$statusList = $statusMapper->getList();
		$order->statusName = $statusList[$order->status] ? $statusList[$order->status]->name : false;
		
		
		$mapperPos = new Shop_Model_Mapper_OrderPos();
		
		$this->view->posList = $mapperPos->getList($id);
		$this->view->page = $currentPage;
		$this->view->order = $order;
	}	
	
	public function presetsAction()
	{
		$presetMapper = new Shop_Model_Mapper_UserPresets();
		$presets = $presetMapper->getList($this->_user->id);
		if (is_array($presets) && count($presets)) {
			$map = array('contact_face', 'phone', 'email', 'address', 'delivery', 'mileage', 'paymentMethod');
			$presetsArray = array();
			foreach ($presets as $preset) {
				$presetData = array();
				$presetData['id'] = $preset->id;
				foreach ($map as $field) {
					$presetData[$field] = $preset->params->{$field};
				}
				$delivery = Shop_Model_DeliveryFactory::create($preset->params->delivery);
				$presetData['deliveryName'] = $delivery->getName();
				if ($delivery->getId() == 'MkadOut') {
					$presetData['deliveryName'] .= ' (дистанция от МКАД: ' . $preset->params->mileage . ' км)';
				}
				$paymentMethod = Shop_Model_PaymentMethodFactory::create($preset->params->paymentMethod);
				$presetData['paymentMethodName'] = $paymentMethod->getName();
				$presetsArray[] = $presetData;
			}
			$this->view->presets = $presetsArray;
		}		
	}
	
	public function presetdeleteAction()
	{
		$id = $this->_getParam('id');
		$presetMapper = new Shop_Model_Mapper_UserPresets();
		$preset = $presetMapper->find($id);
		if ($preset) {
			$presetMapper->delete($preset);
		}
		$this->_helper->redirector->gotoRouteAndExit(array(), 'shop::personalPresets');
	}
	
}