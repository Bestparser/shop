<?php

class Shop_InWayController extends Lv7CMS_Controller_Backend
{ 
	protected $_mapper = null;

    protected $_filterFields = array('fShopId');

    protected $_filter = null;

	public function preDispatch()
	{
        $this->_mapper = new Products_Model_Mapper_InWay();

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
		$this->view->headScript()->appendFile($this->_dataUrl . '/js/modules/shop/inWay.js?v5');
	}


	public function indexAction()
	{
		$shopMapper = new CatCommon_Model_Mapper_Shops();
		$shopList = $shopMapper->getList();

		$shopListOptions[''] = $this->view->translate('Все');
		if (is_array($shopList)) {
			foreach ($shopList as $dataItem) {
				$shopListOptions[$dataItem->id] = $dataItem->name;
			}
			$this->view->shops = $shopListOptions;
		}
	}

	public function dataAction()
	{

        $this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);
		
		$productsMapper = new Products_Model_Mapper_Products();
		$shopMapper = new CatCommon_Model_Mapper_Shops();
		$select = $this->_mapper->getDbSelect();

		if ($this->_filter->fShopId) {
			$select->where('shop_id = ?', $this->_filter->fShopId);
		}

		$searchColumns = array('code', 'brand', 'articul1');
		$orderColumns = array('code', '', '', 'articul1', 'brand', 'qty');

		$dtService = new Lv7CMS_Service_DataTables();
		$data = $dtService
		->setSearchColumns($searchColumns)
		->setOrderColumns($orderColumns)
		->getData($select);
		if (is_array($data)) {
			$dataOutput = array();
			foreach ($data as $item) {
				$row = array();
				$shopFind = $shopMapper->find($item->shop_id);
				$productFind = $productsMapper->find($item->code);
				$row[] = $item->code;
				$row[] = $productFind ? $productFind->name : '---';
				$row[] = $item->shop_id ? $shopFind->name : '---';
				$row[] = $item->articul1;
				$row[] = $item->brand ? $item->brand : '---';
				$row[] = $item->qty;
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