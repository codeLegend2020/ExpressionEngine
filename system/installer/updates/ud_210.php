<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package     ExpressionEngine
 * @author      ExpressionEngine Dev Team
 * @copyright   Copyright (c) 2003 - 2010, EllisLab, Inc.
 * @license     http://expressionengine.com/docs/license.html
 * @link        http://expressionengine.com
 * @since       Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine Update Class
 *
 * @package     ExpressionEngine
 * @subpackage  Core
 * @category    Core
 * @author      ExpressionEngine Dev Team
 * @link        http://expressionengine.com
 */
class Updater {

	var $version_suffix = '';

    function Updater()
    {
        $this->EE =& get_instance();
    }

    function do_update()
    {
		$Q[] = "ALTER TABLE `exp_member_groups` ADD `can_access_fieldtypes` char(1) NOT NULL DEFAULT 'n' AFTER `can_access_files`";
		$Q[] = 'UPDATE exp_member_groups SET can_access_fieldtypes = "y" WHERE group_id = 1';

		foreach ($Q as $num => $sql)
		{
			$this->EE->progress->update_state("Running Query $num of $count");
	        $this->EE->db->query($sql);
		}
		
		return TRUE;
	}
}   
/* END CLASS */

/* End of file ud_210.php */
/* Location: ./system/expressionengine/installer/updates/ud_210.php */