<?php
namespace EllisLab\ExpressionEngine\Model\Status\Gateway;

use EllisLab\ExpressionEngine\Service\Model\Gateway\RowDataGateway;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Status Group Table
 *
 * @package		ExpressionEngine
 * @subpackage	Status\Gateway
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class StatusGroupGateway extends RowDataGateway {
	protected static $_table_name = 'status_groups';
	protected static $_primary_key = 'group_id';
	protected static $_related_gateways = array(
		'site_id' => array(
			'gateway' => 'SiteGateway',
			'key' => 'site_id'
		),
		'group_id' => array(
			'gateway' => 'StatusGateway',
			'key' => 'group_id'
		)
	);


	// Properties
	protected $group_id;
	protected $site_id;
	protected $group_name;

}