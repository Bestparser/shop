<?php

class Shop_OrdersLogController extends Lv7CMS_Controller_Backend
{
	protected $_user;

	protected $_filterFields = array(
		'fManager');

	protected $_filter = null;
	
	protected $_managerRoles = array('foreignCarsDirector', 'foreignCarsManager', 'orderManager', 'foreignCarsManagerReq');

	public function init()
	{
		parent::init();
		$this->_user = Zend_Registry::get('user');
		if ($this->_user->isGuest()) {
			$this->_helper->redirector->gotoRouteAndExit(array(), 'users::login');
		}
		$accessibleRoles = array('developer', 'administrator');
		if ($this->_user && in_array($this->_user->getRole(), $accessibleRoles)) {
			return true;
		} else {
			$this->_acl->accessDenied();
		}
	}	

	public function preDispatch()
	{
        parent::preDispatch();
		$this->_mapper = new Shop_Model_Mapper_OrdersLog();

		// фильтр
		$this->_filter = new Zend_Session_Namespace(get_class($this));
		foreach ($this->_filterFields as $fFiled) {
			if ($this->_hasParam($fFiled)) {
				$this->_filter->{$fFiled} = $this->_getParam($fFiled);
			}
			if (!isset($this->_filter->{$fFiled})) {
				$this->_filter->{$fFiled} = 0;
			}
			$this->view->{$fFiled} = $this->_filter->{$fFiled};
		}
	}

	public function postDispatch()
	{
        parent::postDispatch();

		$this->view->headScript()->appendFile($this->_dataUrl . '/js/jquery.form.js');
		$this->view->headScript()->appendFile($this->_dataUrl . '/js/jquery.cookie.js');
		$this->view->headScript()->appendFile($this->_dataUrl . '/js/modules/shop/logs.js?v1');
	}

	
	public function indexAction()
	{

		$managers = Users_Service_Facade::getInstance()->getList($this->_managerRoles);
		$managerOptions = array('0' => $this->view->translate('Все'));
		foreach ($managers as $manager) {
			$managerOptions[$manager->id] = $manager->getRealname();
		}
		$this->view->managerOptions = $managerOptions;

	}
	
	public function dataAction()
	{
        $this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);

		$conditions = $this->_getConditions();

		$select = $this->_mapper->getDbSelect($conditions);
		$select->order('created desc');
		$select->joinLeft(array('op' => 'shop_orders'), '`o`.`order_id` = `op`.`id`', array('number'));

		$searchColumns = array('o.order_id', 'op.number');
		$orderColumns = array('', '', 'manager', 'site', '', 'created');

		$dtService = new Lv7CMS_Service_DataTables();
		$data = $dtService
		->setSearchColumns($searchColumns)
		->setOrderColumns($orderColumns)
		->getData($select);
		if (is_array($data)) {
			$dataOutput = array();
				$userMapper = new Users_Model_Mapper_User();
				$ordersMapper = new Shop_Model_Mapper_Orders();
			foreach ($data as $item) {
				$user = $userMapper->find($item->manager);
				if (!empty($user->email)) {
					$managerEmail = 'Email: ' . $user->email;
				}
				if (!empty($user->internal_phone)) {
					$managerPhone[$item->manager] = '<br><span class="text-muted">' . $user->internal_phone . '</span>';
				}
				$userRealname = $user ? '<a href="' . $this->view->url(array('id' => $user->id), 'users::userForm') .'"  data-toggle="popover" data-placement="top" data-trigger="hover" data-html="true" data-content="'.$managerEmail.'">' .$user->getRealname() . '</a>' . $managerPhone[$item->manager] : '---';
				$order = $ordersMapper->find($item->order_id);

				$params['id'] = $item->id;
				$row = array();
				$row[] = $item->id;
				$orderLink = '<a href="' . $this->view->url(array('id' => $item->order_id), 'shop::adminOrder') .'">'.$order->number.'</a>';
				$row[] = $orderLink;
				$row[] = $userRealname;
				$row[] = $item->site;
				$typesMapper = new Shop_Model_Mapper_OrdersLogTypes();
				$row[] = $typesMapper->find($item->action_type)->name;
				$row[] = $item->created;
				$dataOutput[] = $row;
			}
		}

		echo $dtService->getOutput($dataOutput);
	}

	public function filterAction()
	{
        $this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);
	}

	protected function _getConditions()
	{

		$conditions = array();

		if ($this->_filter->fManager) {
			$conditions[] = array(
				'key' => 'manager', 
				'value' => $this->_filter->fManager
			);
		}

		return $conditions;		

	}	
	
}