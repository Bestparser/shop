<?php

class Shop_OrderDocTypesController extends Lv7CMS_Controller_Backend
{

    protected $_mapper = null;

    protected $_filterFields = array('fActivity');

    protected $_filter = null;

	public function preDispatch()
	{
        parent::preDispatch();
		if (!$this->_acl->access('shopOrders')) {
			return;
		}
		$this->_mapper = new Shop_Model_Mapper_OrderDocTypes();
		
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
		$this->view->headScript()->appendFile($this->_dataUrl . '/js/modules/shop/orderDocTypes.js');
	}

	public function indexAction()
	{
	}

	public function formAction()
	{
        $id = $this->_getParam('id');
		$obj = $this->_mapper->find($id);
		
		$form = new Shop_Form_OrderDocType();
		
		if ($this->getRequest()->isPost()) {
			if ($form->isValid($this->getRequest()->getPost())) {
				$obj = $this->_mapper->createObject();
				$obj->id = $id;
				$obj->name = $form->getValue('name');
				$obj->sort = $form->getValue('sort');
				$obj->activity = $form->getValue('activity');
				$this->_mapper->save($obj);
		
				$this->_helper->redirector->gotoRouteAndExit(array(), 'shop::orderDocTypes');
			}
		} else {
			if (!empty($obj)) {
				$form->setDefault('name', $obj->name);
				$form->setDefault('sort', $obj->sort);
				$form->setDefault('activity', $obj->activity);
			} else {
			}
		}
		
		$this->view->form = $form;
	}

	public function deleteAction()
	{
        $id = $this->_getParam('id');
		if ($id) {
			$obj = $this->_mapper->find($id);
			if (!empty($obj)) {
				$this->_mapper->delete($obj);
			}
		}
		$this->_helper->redirector->gotoRouteAndExit(array(), 'shop::orderDocTypes');
	}

	public function dataAction()
	{
        $this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);
		
		$select = $this->_mapper->getDbSelect();
		
		if ($this->_filter->fActivity) {
			$select->where('activity = ?', ($this->_filter->fActivity == 1) ? true : false);
		}
		
		$searchColumns = array('name', 'sort');
		$orderColumns = array('activity', 'name', 'sort');
		
		$dtService = new Lv7CMS_Service_DataTables();
		$data = $dtService
		->setSearchColumns($searchColumns)
		->setOrderColumns($orderColumns)
		->getData($select);
		if (is_array($data)) {
			$dataOutput = array();
			foreach ($data as $item) {
				$params['id'] = $item->id;
				$row = array();
				$row[] = $this->view->showBoolValue($item->activity);
				$row[] = $item->name;
				$row[] = $item->sort;
				$row[] = '<a href="' . $this->view->url($params, 'shop::orderDocTypesForm') .'"
							class="button-edit">' . $this->view->translate('изменить') . '</a>
							<a href="'. $this->view->url($params, 'shop::orderDocTypesDelete') . '" 
							class="button-delete" query="' . $this->view->translate("Удалить?") .'"
							>'. $this->view->translate('удалить') . '</a>';
				if (!$item->activity) {
					$row["DT_RowClass"] = "disabled";
				}
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


}

