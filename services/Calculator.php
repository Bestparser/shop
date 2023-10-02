<?php

abstract class Shop_Service_Calculator
{
	abstract public function getGoodsCost();

	abstract public function getDeliveryCost();

	abstract public function getTotalCost();
	
}