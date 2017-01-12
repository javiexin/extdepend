<?php
/**
 *
 * Extension Dependency Management. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2016, javiexin
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace javiexin\extdependencies\event;

/**
 * @ignore
 */
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Extension Dependency Management Event listener.
 */
class listener implements EventSubscriberInterface
{
	static public function getSubscribedEvents()
	{
		return array(
			'core.acp_extensions_run_action'		=> 'acp_extensions_run_action',
			'core.acp_extensions_run_action_after'	=> 'acp_extensions_run_action_after',
		);
	}

	/** @var \phpbb\extension\manager */
	protected $ext_manager;

	/** @var \javiexin\extdependencies\extension\dependency_manager */
	protected $dependency_manager;

	/* @var \phpbb\template\template */
	protected $template;

	/* @var \phpbb\request\request */
	protected $request;

	/* @var \phpbb\user */
	protected $user;

	/**
	 * Constructor
	 *
	 * @param \phpbb\extension\manager	$ext_manager	Extension manager object
	 * @param \javiexin\extdependencies\extension\dependency_manager	$dependency_manager		Extension dependency manager object
	 * @param \phpbb\template\template	$template		Template object
	 * @param \phpbb\request\request	$request		Request object
	 * @param \phpbb\user				$user			User object
	 */
	public function __construct(
						\phpbb\extension\manager $ext_manager,
						\javiexin\extdependencies\extension\dependency_manager $dependency_manager,
						\phpbb\template\template $template,
						\phpbb\request\request $request,
						\phpbb\user $user
					)
	{
		$this->ext_manager = $ext_manager;
		$this->dependency_manager = $dependency_manager;
		$this->template = $template;
		$this->request = $request;
		$this->user = $user;
	}

	/**
	 * Event to run before actions are performed in acp_extensions
	 * Handles dependencies via the Dependency Manager service
	 *
	 * @param \phpbb\event\data	$event	Event object
	 */
	public function acp_extensions_run_action($event)
	{
		$action = $event['action'];
		$ext_name = $event['ext_name'];

		$this->user->add_lang_ext('javiexin/extdependencies', 'acp/extdependencies');

		// If cleanup is set and we are not enabling, there has been an error, so we have to clean up
		$cleanup = $this->request->variable('cleanup', '');
		if ($cleanup && ($action !== 'enable'))
		{
			$this->dependency_manager->revalidate_cleanup($cleanup);
		}

		if (in_array($action, array('enable', 'disable', 'enable_pre', 'disable_pre')) && $ext_name)
		{
			$u_action = $event['u_action'];

			// What are we doing?
			switch ($action)
			{
				case 'enable':
					$ext_dep = $this->request->variable('ext_dep', '');
					if ($ext_dep)
					{
						$u_action = $u_action . '&amp;ext_dep=' . urlencode($ext_dep);
					}
					$revalidate = $this->request->variable('revalidate', '');
					if ($revalidate)
					{
						$ext_name = $cleanup;
						$u_action = $u_action . (($cleanup) ? '&amp;cleanup=' . urlencode($cleanup) : '') . '&amp;revalidate=' . urlencode($revalidate);
					}
					else if ($cleanup)
					{
						$this->dependency_manager->revalidate_cleanup($cleanup);
					}
					if (!$this->dependency_manager->check_dependencies($ext_name))
					{
						$dependencies_not_available = $this->dependency_manager->unavailable_dependencies($ext_name);
						if (!empty($dependencies_not_available))
						{
							trigger_error($this->user->lang('EXTENSION_DEPENDENCIES_NOT_AVAILABLE') . $this->to_html_string($dependencies_not_available) . adm_back_link($u_action), E_USER_WARNING);
						}
						$dependencies_available = $this->dependency_manager->available_dependencies($ext_name);
						if (!empty($dependencies_available))
						{
							$dependencies_agreed_to_enable = array_flip(explode(',', $ext_dep));
							$dependencies_to_enable = array_intersect_key($dependencies_available, $dependencies_agreed_to_enable);
							$dependencies_not_confirmed = array_diff_key($dependencies_available, $dependencies_to_enable);
							if (!empty($dependencies_not_confirmed))
							{
								trigger_error($this->user->lang('EXTENSION_DEPENDENCIES_NOT_CONFIRMED') . $this->to_html_string($dependencies_not_confirmed) . adm_back_link($u_action), E_USER_WARNING);
							}
							$dependencies_to_enable = array_keys($dependencies_to_enable);
							$ext_name = array_pop($dependencies_to_enable);
						}
					} 
				break;

				case 'disable':
					$ext_dep = $this->request->variable('ext_dep', '');
					if ($ext_dep)
					{
						$u_action = $u_action . '&amp;ext_dep=' . urlencode($ext_dep);
					}
					$revalidate = $this->request->variable('revalidate', '');
					if ($revalidate)
					{
						$u_action = $u_action . '&amp;revalidate=' . urlencode($revalidate);
					}
					if (!$this->dependency_manager->check_dependants($ext_name))
					{
						$enabled_dependants = $this->dependency_manager->enabled_dependants($ext_name);
						if (!empty($enabled_dependants))
						{
							$dependencies_agreed_to_disable = array_flip(explode(',', $ext_dep));
							$dependencies_to_disable = array_intersect_key($enabled_dependants, $dependencies_agreed_to_disable);
							$dependencies_not_confirmed = array_diff_key($enabled_dependants, $dependencies_to_disable);
							if (!empty($dependencies_not_confirmed))
							{
								trigger_error($this->user->lang('EXTENSION_DEPENDANTS_NOT_CONFIRMED') . $this->to_html_string($dependencies_not_confirmed) . adm_back_link($u_action), E_USER_WARNING);
							}
							$dependencies_to_disable = array_keys($dependencies_to_disable);
							$ext_name = array_pop($dependencies_to_disable);
						}
					}
				break;
			}

			$event['u_action'] = $u_action;
			$event['ext_name'] = $ext_name;
		}
	}

	/**
	 * Event to run after actions have been performed in acp_extensions, preparing next step
	 * Handles dependencies via the Dependency Manager service
	 *
	 * @param \phpbb\event\data	$event	Event object
	 */
	public function acp_extensions_run_action_after($event)
	{
		$action = $event['action'];
		$ext_name = $event['ext_name'];
		$tpl_name = $event['tpl_name'];
		$u_action = $event['u_action'];

		// If the action is list, it might come as 'list' or as "default", so the only way to make sure is check for the template
		if ($tpl_name == 'acp_ext_list') // $action == 'list'
		{
			foreach ($this->ext_manager->all_available() as $name => $location)
			{
				$invalid = !$this->dependency_manager->register($name);
				if ($this->ext_manager->is_enabled($name))
				{
					$revalidate = !$this->dependency_manager->check_dependencies($name, true);
					$index = method_exists($this->template, 'find_key_index') ? $this->template->find_key_index('enabled', array('NAME' => $name)) : false;
					if ($invalid)
					{
						$this->template->alter_block_array('enabled', array(
								'META_DISPLAY_NAME'		=> '<strong style="color: #BC2A4D;">' . $this->dependency_manager->display_name($name) . '</strong>: ' .
														$this->user->lang('EXTENSION_CIRCULAR_DEPENDENCY_DISABLE')  .
														(($index === false)
															? ('<br/><strong style="color: #BC2A4D;">' . $this->adm_link($u_action . '&amp;action=disable_pre&amp;ext_name=' . urlencode($name), 'EXTENSION_DISABLE', 'EXTENSION_DISABLE_EXPLAIN') . '</strong>')
															: ''),
							), array('NAME' => $name), 'change');
						if ($index !== false)
						{
							$this->template->alter_block_array("enabled[$index].actions", array(
								'L_ACTION'				=> '<strong style="color: #BC2A4D;">' . $this->user->lang('EXTENSION_DISABLE') . '</strong>',
							), array('L_ACTION' => $this->user->lang('EXTENSION_DISABLE')), 'change');
						}
					}
					else if ($revalidate)
					{
						$this->template->alter_block_array('enabled', array(
								'META_DISPLAY_NAME'		=> '<strong style="color: #BC2A4D;">' . $this->dependency_manager->display_name($name) . '</strong>: ' .
														$this->user->lang('EXTENSION_REVALIDATE_NEEDED')  .
														(($index === false)
															? ('<br/><strong style="color: #BC2A4D;">' . $this->adm_link($u_action . '&amp;action=disable_pre&amp;revalidate=' . urlencode($name) . '&amp;ext_name=' . urlencode($name), 'EXTENSION_REVALIDATE', 'EXTENSION_REVALIDATE_ACTION_EXPLAIN') . '</strong>')
															: ''),
							), array('NAME' => $name), 'change');
						if ($index !== false)
						{
							$this->template->alter_block_array("enabled[$index].actions", array(
									'L_ACTION'			=> '<strong style="color: #BC2A4D;">' . $this->user->lang('EXTENSION_REVALIDATE') . '</strong>',
									'L_ACTION_EXPLAIN'	=> $this->user->lang('EXTENSION_REVALIDATE_ACTION_EXPLAIN'),
									'U_ACTION'			=> $u_action . '&amp;action=disable_pre&amp;revalidate=' . urlencode($name) . '&amp;ext_name=' . urlencode($name),
								), true);
						}
					}
					if ($index !== false)
					{
						$template_data = $this->ext_details_template_data($name, $u_action);
						foreach($template_data as $block => $dep_array)
						{
							foreach($dep_array as $dep)
							{
								$this->template->alter_block_array("enabled[$index].$block", $dep);
							}
						}
					}
				}
				else
				{
					$index = method_exists($this->template, 'find_key_index') ? $this->template->find_key_index('disabled', array('NAME' => $name)) : false;
					if ($invalid)
					{
						$this->template->alter_block_array('disabled', array(
								'META_DISPLAY_NAME'		=> '<strong style="color: #BC2A4D;">' . $this->dependency_manager->display_name($name) . '</strong>: ' . $this->user->lang('EXTENSION_CIRCULAR_DEPENDENCY_ENABLE'),
							), array('NAME' => $name), 'change');
						if ($index !== false)
						{
							$this->template->alter_block_array("disabled[$index].actions", array(), array('L_ACTION' => $this->user->lang('EXTENSION_ENABLE')), 'delete');
						}
					}
					if ($index !== false)
					{
						$template_data = $this->ext_details_template_data($name, $u_action);
						foreach($template_data as $block => $dep_array)
						{
							foreach($dep_array as $dep)
							{
								$this->template->alter_block_array("disabled[$index].$block", $dep);
							}
						}
					}
				}
			}
		}

		if (in_array($action, array('enable', 'disable', 'enable_pre', 'disable_pre', 'details')) && $ext_name)
		{
			// What are we doing?
			switch ($action)
			{
				case 'enable_pre':
					$revalidate = $this->request->variable('revalidate', '');
					if ($revalidate)
					{
						$ext_name_save = $ext_name;
						$ext_name = $this->dependency_manager->revalidate_new(explode(',',$revalidate));
						if (!$ext_name)
						{
							trigger_error($this->dependency_manager->display_name($ext_name) . ': ' . $this->user->lang('EXTENSION_CIRCULAR_DEPENDENCY_ENABLE') . adm_back_link($u_action), E_USER_WARNING);
						}
						$revalidate = $ext_name_save;
						$this->template->assign_var('S_REVALIDATE', true);
						$u_action .= '&amp;cleanup=' . urlencode($ext_name);
						$tpl_name = '@javiexin_extdependencies/acp_ext_dep_enable';
					}
					else
					{
						if (!$this->dependency_manager->register($ext_name))
						{
							trigger_error($this->dependency_manager->display_name($ext_name) . ': ' . $this->user->lang('EXTENSION_CIRCULAR_DEPENDENCY_ENABLE') . adm_back_link($u_action), E_USER_WARNING);
						}
					}
					if (!$this->dependency_manager->check_dependencies($ext_name))
					{
						$dependencies_not_available = $this->dependency_manager->unavailable_dependencies($ext_name);
						if (!empty($dependencies_not_available))
						{
							trigger_error($this->user->lang('EXTENSION_DEPENDENCIES_NOT_AVAILABLE') . $this->to_html_string($dependencies_not_available) . adm_back_link($u_action), E_USER_WARNING);
						}
						$dependencies_available = $this->dependency_manager->available_dependencies($ext_name);
						if (!empty($dependencies_available))
						{
							$ext_dep = $ext_name . ',' . implode(',', array_keys($dependencies_available));
							$this->template->assign_block_vars_array('ext', $this->ext_template_data($ext_dep, $u_action));
							$tpl_name = '@javiexin_extdependencies/acp_ext_dep_enable';
						}
					}
					$this->template->assign_vars(array(
						'L_CONFIRM_MESSAGE'	=> $this->user->lang('EXTENSION_' . (isset($ext_dep) ? 'DEP_' : '') . ($revalidate ? 'REVALIDATE_' : '') . 'ENABLE_CONFIRM', $this->dependency_manager->display_name(($revalidate) ? $revalidate : $ext_name)),
						'U_ENABLE'			=> $u_action . '&amp;action=enable&amp;ext_name=' . urlencode((($revalidate) ? $revalidate : $ext_name)) . (isset($ext_dep) ? '&amp;ext_dep=' . urlencode($ext_dep) : '') . (isset($revalidate) ? '&amp;revalidate=' . urlencode($revalidate) : '') . '&amp;hash=' . generate_link_hash('enable.' . (($revalidate) ? $revalidate : $ext_name)),
					));
				break;

				case 'enable':
					$this->dependency_manager->enable($ext_name);
					$ext_dep = $this->request->variable('ext_dep', '');
					if ($ext_dep)
					{
						if (strpos($ext_dep, $ext_name) !== 0)
						{
							$refresh = true;
							$ext_name = substr($ext_dep, 0, strpos($ext_dep, ','));
							$this->template->assign_var('S_NEXT_STEP', true);							
						}
						$this->template->assign_block_vars_array('ext', $this->ext_template_data($ext_dep, $u_action));
						$tpl_name = '@javiexin_extdependencies/acp_ext_dep_enable';
					}
					$revalidate = $this->request->variable('revalidate', '');
					if ($revalidate)
					{
						$ext_name = $revalidate;
						$cleanup = $this->request->variable('cleanup', '');
						if ($this->dependency_manager->check_dependencies($cleanup))
						{
							$this->template->assign_var('S_NEXT_STEP', false);							
							$this->dependency_manager->revalidate_cleanup($cleanup);
							unset($refresh);
						}
						$this->template->assign_var('S_REVALIDATE', true);							
						$tpl_name = '@javiexin_extdependencies/acp_ext_dep_enable';
					}
					$this->template->assign_vars(array(
						'L_EXTENSION_SUCCESS'		=> $this->user->lang('EXTENSION_' . ($ext_dep ? 'DEP_' : '') . (($revalidate) ? 'REVALIDATE_' : '') . 'ENABLE_SUCCESS', $this->dependency_manager->display_name($ext_name)),
						'L_EXTENSION_IN_PROGRESS'	=> $this->user->lang('EXTENSION_' . ($ext_dep ? 'DEP_' : '') . (($revalidate) ? 'REVALIDATE_' : '') . 'ENABLE_IN_PROGRESS', $this->dependency_manager->display_name($ext_name)),
					));
					if (isset($refresh))
					{
						meta_refresh(0, $u_action . '&amp;action=enable&amp;ext_name=' . urlencode($ext_name) . '&amp;hash=' . generate_link_hash('enable.' . ($ext_name)));
					}
					$this->template->assign_var('U_RETURN', preg_replace('/&(amp;)?(ext_dep|revalidate|cleanup)=[^&]*/', '', $u_action) . '&amp;action=list');
				break;

				case 'disable_pre':
					if (!$this->dependency_manager->check_dependants($ext_name))
					{
						$enabled_dependants = $this->dependency_manager->enabled_dependants($ext_name);
						if (!empty($enabled_dependants))
						{
							$ext_dep = $ext_name . ',' . implode(',', array_keys($enabled_dependants));
							$this->template->assign_block_vars_array('ext', $this->ext_template_data($ext_dep, $u_action));
							$tpl_name = '@javiexin_extdependencies/acp_ext_dep_disable';
						}
					}
					$revalidate = $this->request->variable('revalidate', '');
					if ($revalidate)
					{
						$revalidate = isset($ext_dep) ? $ext_dep : $ext_name;
						$this->template->assign_var('S_REVALIDATE', true);
						$tpl_name = '@javiexin_extdependencies/acp_ext_dep_disable';
					}
					$this->template->assign_vars(array(
						'L_CONFIRM_MESSAGE'	=> $this->user->lang('EXTENSION_' . (isset($ext_dep) ? 'DEP_' : '') . ($revalidate ? 'REVALIDATE_' : '') . 'DISABLE_CONFIRM', $this->dependency_manager->display_name($ext_name)),
						'U_DISABLE'			=> $u_action . '&amp;action=disable&amp;ext_name=' . urlencode($ext_name) . (isset($ext_dep) ? '&amp;ext_dep=' . urlencode($ext_dep) : '') . (isset($revalidate) ? '&amp;revalidate=' . urlencode($revalidate) : '') . '&amp;hash=' . generate_link_hash('disable.' . $ext_name),
					));
				break;

				case 'disable':
					$this->dependency_manager->disable($ext_name);
					$ext_dep = $this->request->variable('ext_dep', '');
					if ($ext_dep)
					{
						if (strpos($ext_dep, $ext_name) !== 0)
						{
							$refresh = true;
							$ext_name = substr($ext_dep, 0, strpos($ext_dep, ','));
							$this->template->assign_var('S_NEXT_STEP', true);							
						}
						$this->template->assign_block_vars_array('ext', $this->ext_template_data($ext_dep, $u_action));
						$tpl_name = '@javiexin_extdependencies/acp_ext_dep_disable';
					}
					$revalidate = $this->request->variable('revalidate', '');
					if ($revalidate)
					{
						$this->template->assign_vars(array(
							'S_REVALIDATE'	=> true,
							'U_CONTINUE'	=> preg_replace('/&(amp;)?ext_dep=[^&]*/', '', $u_action) . '&amp;action=enable_pre' . '&amp;ext_name=' . urlencode($ext_name),
						));
						$tpl_name = '@javiexin_extdependencies/acp_ext_dep_disable';
					}
					$this->template->assign_vars(array(
						'L_EXTENSION_SUCCESS'		=> $this->user->lang('EXTENSION_' . ($ext_dep ? 'DEP_' : '') . (($revalidate) ? 'REVALIDATE_' : '') . 'DISABLE_SUCCESS', $this->dependency_manager->display_name($ext_name)),
						'L_EXTENSION_IN_PROGRESS'	=> $this->user->lang('EXTENSION_' . ($ext_dep ? 'DEP_' : '') . (($revalidate) ? 'REVALIDATE_' : '') . 'DISABLE_IN_PROGRESS', $this->dependency_manager->display_name($ext_name)),
					));
					if (isset($refresh))
					{
						meta_refresh(0, $u_action . '&amp;action=disable&amp;ext_name=' . urlencode($ext_name) . '&amp;hash=' . generate_link_hash('disable.' . $ext_name));
					}
					$this->template->assign_var('U_RETURN', preg_replace('/&(amp;)?ext_dep=[^&]*/', '', $u_action) . '&amp;action=list');
				break;

				case 'details':
					$template_data = $this->ext_details_template_data($ext_name, $u_action);
					foreach($template_data as $block => $dep_array)
					{
						$this->template->assign_block_vars_array($block, $dep_array);
					}
				break;
			}

			$event['u_action'] = $u_action;
			$event['tpl_name'] = $tpl_name;
		}
	}

	/**
	 * Converts an array of dependencies into a single HTML string for displaying
	 *
	 * @param array	$dependencies	Dependencies array
	 * @return string				The HTML string with the list of dependencies
	 */
	protected function to_html_string($dependencies)
	{
		$html_string = '';
		foreach($dependencies as $ext_name => $ext_data)
		{
			$html_string .= '<br/>';
			$html_string .= isset($ext_data['display_name']) ? $ext_data['display_name'] : $ext_name;
			$html_string .= isset($ext_data['version']) ? ('&nbsp;(' . $ext_data['version'] . ')') : '';
		}
		return $html_string;
	}

	/**
	 * Generate extension dependencies template array
	 * Given a comma separated list of extension dependencies, returns an array of template data for each extension
	 * The returned array is suitable to be used by assign_block_vars_array
	 *
	 * @param string	$ext_dep	Comma separated list of dependencies
	 * @param string	$u_action	Base action to perform
	 * @return array				Array of arrays of template data, one entry per extension in the dependencies list
	 */
	protected function ext_template_data($ext_dep, $u_action)
	{
		$ext_dep_ary = explode(',', $ext_dep);
		array_shift($ext_dep_ary);
		$ext_list = array_flip($ext_dep_ary);
		$ext_list = $this->dependency_manager->ext_data($ext_list);
		$ext_template_data = array();
		foreach($ext_list as $ext_name => $ext_data)
		{
			$ext_template_data[] = array_merge(array(
										'NAME' => $ext_name,
										'U_DETAILS' => $u_action . '&amp;action=details&amp;ext_name=' . urlencode($ext_name),
									), array_change_key_case($ext_data, CASE_UPPER));
		}
		return $ext_template_data;
	}

	/**
	 * Generate extension dependency details
	 * Given an extension name, generates an array of arrays with extension dependencies and details for each of them
	 * The returned array is suitable to be used by assign_block_vars_array
	 *
	 * @param string	$ext_name	Extension name
	 * @param string	$u_action	Base action to perform
	 * @return array				Array of (up to two) arrays with extension details for dependency related extensions
	 */
	protected function ext_details_template_data($ext_name, $u_action)
	{
		$ext_data['depends'] = $this->dependency_manager->ext_depends_on($ext_name);
		$ext_data['dependants'] = $this->dependency_manager->ext_dependants($ext_name);
		$ext_template_data = array();
		foreach($ext_data as $dep_block => $ext_list)
		{
			foreach($ext_list as $ext_depend => $ext_details)
			{
				$ext_template_data[$dep_block][] = array_merge(array(
											'NAME' => $ext_depend,
											'U_DETAILS' => $u_action . '&amp;action=details&amp;ext_name=' . urlencode($ext_depend),
										), array_change_key_case($ext_details, CASE_UPPER));
			}
		}
		return $ext_template_data;
	}

	/**
	 * Generate a clickable HTML string to access a link with a message
	 *
	 * @param string	$u_action	The URL to link to
	 * @param string	$msg		A string (or language constant) with the message to show to user
	 * @param string	$explain	A string (or language constant) with the explanation of the action, shown as a tooltip (optional)
	 * @return array				Array of (up to two) arrays with extension details for dependency related extensions
	 */
	protected function adm_link($u_action, $msg, $explain = '')
	{
		return '&raquo; <a ' . (($explain) ? ('title="' . $this->user->lang($explain) . '" ') : '') . 'href="' . $u_action . '">' . $this->user->lang($msg) . '</a>';
	}
}
