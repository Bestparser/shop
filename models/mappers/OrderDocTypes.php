<?php

class Shop_Model_Mapper_OrderDocTypes extends Lv7CMS_Mapper_Abstract
{

    protected $_domainName = 'Shop_Model_OrderDocType';

    protected $_tableName = 'Shop_Model_DbTable_OrderDocTypes';

    protected $_orderField = 'sort';

    const KKM_ORDER_DOC_TYPE = 12;
    const ISHOP_DELIVERY_DOC_TYPE = 3;
    const ISHOP_SELFDELIVERY_DOC_TYPE = 10;
    const KKM_PARTNER_ORDER_DOC_TYPE = 14;

	public function getList($activity = null)
	{
        $result = $this->_table->getList($activity);
		return $this->_rowsetToObjList($result);
	}


}

