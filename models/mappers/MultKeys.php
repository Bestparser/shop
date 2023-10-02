<?php

class Shop_Model_Mapper_MultKeys extends Lv7CMS_Mapper_Abstract
{

    protected $_domainName = 'Shop_Model_MultKey';

    protected $_tableName = 'Shop_Model_DbTable_MultKeys';

	public function getList($activity = null)
	{
        $result = $this->_table->getList($activity);
		return $this->_rowsetToObjList($result);
	}


}

