<?php

namespace EllisLab\ExpressionEngine\Controllers\Design;

use EllisLab\ExpressionEngine\Controllers\Design\Design;
use EllisLab\ExpressionEngine\Library\CP\Pagination;
use EllisLab\ExpressionEngine\Library\CP\Table;
use EllisLab\ExpressionEngine\Library\CP\URL;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP Design\Template Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Template extends Design {

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		if ( ! ee()->cp->allowed_group('can_access_design'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->stdHeader();
	}

	public function create($group_name)
	{
		$group = ee('Model')->get('TemplateGroup')
			->filter('group_name', $group_name)
			->first();

		if ( ! $group)
		{
			show_error(sprintf(lang('error_no_template_group'), $group_name));
		}

		if ($this->hasEditTemplatePrivileges($group->group_id) === FALSE)
		{
			show_error(lang('unauthorized_access'));
		}

		$existing_templates = array(
			'0' => '-- ' . strtolower(lang('none')) . ' --'
		);

		foreach (ee('Model')->get('TemplateGroup')->all() as $group)
		{
			$templates = array();
			foreach ($group->getTemplates() as $template)
			{
				$templates[$template->template_id] = $template->template_name;
			}
			$existing_templates[$group->group_name] = $templates;
		}

		$vars = array(
			'ajax_validate' => TRUE,
			'base_url' => cp_url('design/template/create/' . $group_name),
			'save_btn_text' => 'btn_create_template',
			'save_btn_text_working' => 'btn_create_template_working',
			'sections' => array(
				array(
					array(
						'title' => 'name',
						'desc' => 'template_name_desc',
						'fields' => array(
							'template_name' => array(
								'type' => 'text',
								'required' => TRUE
							)
						)
					),
					array(
						'title' => 'template_type',
						'desc' => 'template_type_desc',
						'fields' => array(
							'template_type' => array(
								'type' => 'dropdown',
								'choices' => $this->getTemplateTypes()
							)
						)
					),
					array(
						'title' => 'duplicate_existing_template',
						'desc' => 'duplicate_existing_template_desc',
						'fields' => array(
							'template_id' => array(
								'type' => 'dropdown',
								'choices' => $existing_templates
							)
						)
					),
				)
			)
		);

		ee()->load->library('form_validation');
		ee()->form_validation->set_rules(array(
			array(
				'field' => 'template_name',
				'label' => 'lang:template_name',
				'rules' => 'required|callback__template_name_checks[' . $group->group_id . ']'
			),
			array(
				'field' => 'template_type',
				'label' => 'lang:template_type',
				'rules' => 'required'
			)
		));

		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			if (ee()->input->post('template_id'))
			{
				$template = ee('Model')->get('Template', ee()->input->post('template_id'));
				$template->template_id = NULL;
			}
			else
			{
				$template = ee('Model')->make('Template');
			}
			$template->site_id = ee()->config->item('site_id');
			$template->group_id = $group->group_id;
			$template->template_name = ee()->input->post('template_name');
			$template->template_type = ee()->input->post('template_type');
			$template->save();

			ee('Alert')->makeInline('settings-form')
				->asSuccess()
				->withTitle(lang('create_template_success'))
				->addToBody(sprintf(lang('create_template_success_desc'), $group_name, $template->template_name))
				->defer();

			ee()->functions->redirect(cp_url('design/manager/' . $group->group_name));
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee('Alert')->makeInline('settings-form')
				->asIssue()
				->withTitle(lang('create_template_error'))
				->addToBody(lang('create_template_error_desc'));
		}

		$this->sidebarMenu($group->group_id);
		ee()->view->cp_page_title = lang('create_template');

		ee()->cp->render('settings/form', $vars);
	}

	public function edit()
	{

	}

	public function remove()
	{

	}

	public function export()
	{

	}

	public function sync()
	{

	}

	public function settings($template_id)
	{

	}

	private function loadCodeMirrorAssets()
	{
		$this->cp->add_to_head($this->view->head_link('css/codemirror.css'));
		$this->cp->add_to_head($this->view->head_link('css/codemirror-additions.css'));
		ee()->cp->add_js_script(array(
				'plugin'	=> 'ee_codemirror',
				'file'		=> array(
					'codemirror/codemirror',
					'codemirror/closebrackets',
					'codemirror/overlay',
					'codemirror/xml',
					'codemirror/css',
					'codemirror/javascript',
					'codemirror/htmlmixed',
					'codemirror/ee-mode',
					'codemirror/dialog',
					'codemirror/searchcursor',
					'codemirror/search',

					'cp/template_editor',
					'cp/manager'
				)
			)
		);
	}

	/**
	 * Get template types
	 *
	 * Returns a list of the standard EE template types to be used in
	 * template type selection dropdowns, optionally merged with
	 * user-defined template types via the template_types hook.
	 *
	 * @access private
	 * @return array Array of available template types
	 */
	private function getTemplateTypes()
	{
		$template_types = array(
			'webpage'	=> lang('webpage'),
			'feed'		=> lang('rss'),
			'css'		=> lang('css_stylesheet'),
			'js'		=> lang('js'),
			'static'	=> lang('static'),
			'xml'		=> lang('xml')
		);

		// -------------------------------------------
		// 'template_types' hook.
		//  - Provide information for custom template types.
		//
		$custom_templates = ee()->extensions->call('template_types', array());
		//
		// -------------------------------------------

		if ($custom_templates != NULL)
		{
			// Instead of just merging the arrays, we need to get the
			// template_name value out of the associative array for
			// easy use of the form_dropdown helper
			foreach ($custom_templates as $key => $value)
			{
				$template_types[$key] = $value['template_name'];
			}
		}

		return $template_types;
	}

	/**
	  *	 Check Template Name
	  */
	public function _template_name_checks($str, $group_id)
	{
		if ( ! preg_match("#^[a-zA-Z0-9_\-/]+$#i", $str))
		{
			ee()->lang->loadfile('admin');
			ee()->form_validation->set_message('_template_name_checks', lang('illegal_characters'));
			return FALSE;
		}

		$reserved_names = array('act', 'css');

		if (in_array($str, $reserved_names))
		{
			ee()->form_validation->set_message('_template_name_checks', lang('reserved_name'));
			return FALSE;
		}

		$count = ee('Model')->get('Template')
			->filter('group_id', $group_id)
			->filter('template_name', $str)
			->count();

		if ((strtolower($this->input->post('old_name')) != strtolower($str)) AND $count > 0)
		{
			$this->form_validation->set_message('_template_name_checks', lang('template_name_taken'));
			return FALSE;
		}
		elseif ($count > 1)
		{
			$this->form_validation->set_message('_template_name_checks', lang('template_name_taken'));
			return FALSE;
		}

		return TRUE;
	}
}
// EOF