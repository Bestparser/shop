<?php

class Shop_UserController extends Lv7CMS_Controller_Frontend
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
		
		$rows = $paginator->getItemsByPage($currentPage);
		if ($rows->count()) {
			$items = array();
			foreach ($rows as $row) {
				$item = $mapper->rowToObject($row);
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
		
		$mapperPos = new Shop_Model_Mapper_OrderPos();
		
		$this->view->posList = $mapperPos->getList($id);
		$this->view->page = $currentPage;
		$this->view->order = $order;
	}	
	
	
}