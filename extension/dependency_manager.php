<?php
/**
 *
 * Extension Dependency Management. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2016, javiexin
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace javiexin\extdependencies\extension;

/**
 * Extension Dependency Management Service info.
 */
class dependency_manager
{
	/** @var \phpbb\extension\manager */
	protected $ext_manager;

	/** @var \phpbb\config\db_text */
	protected $config_text;

	/* @var \phpbb\template\template */
	protected $template;

	/** @var array */
	protected $dependencies;

	/**
	 * Constructor
	 *
	 * @param \phpbb\extension\manager	$ext_mgr		Extension manager object
	 * @param \phpbb\config\db_text		$config_text	Config text object
	 * @param \phpbb\template\template	$template		Template object
	 */
	public function __construct(
						\phpbb\extension\manager $ext_mgr, 
						\phpbb\config\db_text $config_text,
						\phpbb\template\template $template 
					)
	{
		$this->ext_manager = $ext_mgr;
		$this->config_text = $config_text;
		$this->template = $template;

		$this->load_dependencies();
	}

	/**
	 * Load dependencies information from a config_text field
	 */
	protected function load_dependencies()
	{
		$dependencies = $this->config_text->get('javiexin.extdependencies.saved');
		$this->dependencies = ($dependencies == '') ? array() : json_decode($dependencies, true);
		if (!$this->dependencies)
		{
			$this->initialize();
		}
	}

	/**
	 * Save dependencies information into a config_text field
	 */
	protected function save_dependencies()
	{
		$this->config_text->set('javiexin.extdependencies.saved', json_encode($this->dependencies));
	}

	/**
	 * Initialize the Dependency Manager itself
	 */
	private function initialize()
	{
		foreach ($this->ext_manager->all_available() as $ext_name => $location)
		{
			$this->update_extension($ext_name);
		}
		$this->save_dependencies();
	}

	/**
	 * Register dependencies recursively
	 *
	 * @param string	$ext_name 	The name of the extension being registered in the dependencies tree
	 * @param array		$context	Context for the call, ie, recursive calls before this one, used to check for circular dependencies
	 * @return boolean 	Success
	 */
	public function register($ext_name, $context = array())
	{
		$this->update_extension($ext_name);
		$context[] = $ext_name;
		foreach ($this->dependencies[$ext_name]['depends_on'] as $ext_depend => $ext_version)
		{
			if (array_search($ext_depend, $context) !== false)
			{
				return false; // Circular dependency, should throw an exception instead
			}
			if ($this->ext_manager->is_available($ext_depend))
			{
				if (!$this->register($ext_depend, $context))
				{
					return false;
				}
			}
		}
		if ($context == array($ext_name))
		{
			$this->save_dependencies();
		}
		return true;
	}

	/**
	 * Update extension information in the dependencies tree, for all available extensions
	 *
	 * @param string	$ext_name 	The name of the extension being updated
	 */
	private function update_extension($ext_name)
	{
		if ($this->ext_manager->is_enabled($ext_name) && isset($this->dependencies[$ext_name]['display-name']))
		{
			return;
		}
		if (!isset($this->dependencies[$ext_name]))
		{
			$this->dependencies[$ext_name] = array();
		}
		$md_manager = $this->ext_manager->create_extension_metadata_manager($ext_name, $this->template);
		$this->dependencies[$ext_name]['display-name'] = $md_manager->get_metadata('display-name');
		$this->dependencies[$ext_name]['version'] = $md_manager->get_metadata('version');
		$extension = $this->ext_manager->get_extension($ext_name);
		if (method_exists($extension, 'depends_on'))
		{
			$this->dependencies[$ext_name]['depends_on'] =  $extension->depends_on();
			$this->dependencies[$ext_name]['depends_on']['javiexin/extdependencies'] = '';
		}
		else
		{
			$this->dependencies[$ext_name]['depends_on'] = array();
		}
		if ($this->ext_manager->is_enabled($ext_name))
		{
			$this->enable($ext_name);
		}
	}

	/**
	 * Register dependants on enable
	 *
	 * @param string	$ext_name 		The name of the extension just enabled
	 */
	public function enable($ext_name)
	{
		if (isset($this->dependencies[$ext_name]) && ($depends_on = $this->dependencies[$ext_name]['depends_on']))
		{
			foreach ($depends_on as $ext_depend => $ext_version)
			{
				$this->dependencies[$ext_depend]['dependants'][$ext_name] = $this->dependencies[$ext_name]['version'];
			}
			$this->save_dependencies();
		}
	}

	/**
	 * Unregister dependants on disable
	 *
	 * @param string	$ext_name		The name of the extension just disabled
	 */
	public function disable($ext_name)
	{
		if (isset($this->dependencies[$ext_name]) && $depends_on = $this->dependencies[$ext_name]['depends_on'])
		{
			foreach ($depends_on as $ext_depend => $ext_version)
			{
				unset($this->dependencies[$ext_depend]['dependants'][$ext_name]);
			}
			$this->save_dependencies();
		}
	}

	/**
	 * Unregister extension
	 *
	 * @param string	$ext_name 	The name of the extension being unregistered from the dependencies tree
	 * @return boolean 	Success
	 */
	public function unregister($ext_name)
	{
		if ($this->ext_manager->is_enabled($ext_name))
		{
			return false;
		}
		if (isset($this->dependencies[$ext_name]))
		{
			unset($this->dependencies[$ext_name]);
			$this->save_dependencies();
		}
		return true;
	}

	/**
	 * Create a temporary revalidation entity
	 *
	 * @param array	$ext_list 	List of extensions to be treated as dependencies of the new entity
	 * @return string 	The name of the newly created entity
	 */
	public function revalidate_new($ext_list)
	{
		$ext_name = 'javiexin/extdependencies_' . time();
		$this->dependencies[$ext_name]['display-name'] = '';
		$this->dependencies[$ext_name]['version'] = '';
		$this->dependencies[$ext_name]['depends_on'] =  array();
		foreach ($ext_list as $ext_depend)
		{
			$this->dependencies[$ext_name]['depends_on'][$ext_depend] = '';
			if ($this->ext_manager->is_available($ext_depend))
			{
				if (!$this->register($ext_depend))
				{
					unset($this->dependencies[$ext_name]);
					$this->save_dependencies();
					return '';
				}
			}
		}
		return $ext_name;
	}

	/**
	 * Cleanup a temporary revalidation entity
	 *
	 * @param string	$ext_name 	The name of the revalidation entity to be cleaned up from the dependencies tree
	 * @return boolean 	Success
	 */
	public function revalidate_cleanup($ext_name)
	{
		if (isset($this->dependencies[$ext_name]))
		{
			unset($this->dependencies[$ext_name]);
			$this->save_dependencies();
		}
		return true;
	}

	/**
	 * Check dependencies
	 *
	 * @param string	$ext_name 		The name of the extension being checked
	 * @param bool		$check_enabled	Force checking of enabled extension, to resynchronize (default false)
	 * @return boolean 	true if dependencies are fulfilled, false otherwise
	 */
	public function check_dependencies($ext_name, $check_enabled=false)
	{
		if ($this->ext_manager->is_enabled($ext_name) && !$check_enabled)
		{
			return true;
		}
		foreach ($this->dependencies[$ext_name]['depends_on'] as $ext_depend => $ext_version)
		{
			if (!$this->ext_manager->is_enabled($ext_depend))
			{
				return false;
			}
		}
		return true;
	}

	/**
	 * Available dependencies
	 *
	 * @param string	$ext_name 		The name of the extension
	 * @param bool		$check_enabled	Force checking of enabled extension, to resynchronize (default false)
	 * @return array 	Array with all dependencies that are not met, but available to be enabled
	 */
	public function available_dependencies($ext_name, $check_enabled=false)
	{
		if ($this->ext_manager->is_enabled($ext_name) && !$check_enabled)
		{
			return array();
		}
		$available = array();
		foreach ($this->dependencies[$ext_name]['depends_on'] as $ext_depend => $ext_version)
		{
			if ($this->ext_manager->is_available($ext_depend) && !$this->ext_manager->is_enabled($ext_depend)) // Missing check for version
			{
				$available[$ext_depend] = array(
											'version'		=> $this->dependencies[$ext_depend]['version'], 
											'display_name'	=> $this->dependencies[$ext_depend]['display-name'],
										);
				$available_depend = $this->available_dependencies($ext_depend, $check_enabled);
				$available = array_merge(array_diff_key($available, $available_depend), $available_depend);
			}
		}
		return $available;
	}

	/**
	 * Unavailable dependencies
	 *
	 * @param string	$ext_name 		The name of the extension
	 * @param bool		$check_enabled	Force checking of enabled extension, to resynchronize (default false)
	 * @return array 	Array with all dependencies that are not available
	 */
	public function unavailable_dependencies($ext_name, $check_enabled=false)
	{
		if ($this->ext_manager->is_enabled($ext_name) && !$check_enabled)
		{
			return array();
		}
		$unavailable = array();
		foreach ($this->dependencies[$ext_name]['depends_on'] as $ext_depend => $ext_version)
		{
			if (!$this->ext_manager->is_available($ext_depend)) // Missing check for version
			{
				$unavailable[$ext_depend] = array(
											'version'		=> $ext_version,
										);
			}
			else
			{
				$unavailable = array_merge($unavailable, $this->unavailable_dependencies($ext_depend, $check_enabled));
			}
		}
		return $unavailable;
	}

	/**
	 * Check dependants
	 *
	 * @param string	$ext_name 	The name of the extension being checked
	 * @return boolean 	true if no dependants are enabled, false otherwise
	 */
	public function check_dependants($ext_name)
	{
		if ($this->ext_manager->is_disabled($ext_name))
		{
			return true;
		}
		if (isset($this->dependencies[$ext_name]['dependants']))
		{
			foreach ($this->dependencies[$ext_name]['dependants'] as $ext_depend => $ext_version)
			{
				if ($this->ext_manager->is_enabled($ext_depend))
				{
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Enabled dependants
	 *
	 * @param string	$ext_name 	The name of the extension
	 * @return array 	Array with all dependants that are enabled
	 */
	public function enabled_dependants($ext_name, $context = array())
	{
		if (!$this->ext_manager->is_enabled($ext_name) || !isset($this->dependencies[$ext_name]['dependants']))
		{
			return array();
		}
		$context[] = $ext_name;
		$enabled = array();
		foreach ($this->dependencies[$ext_name]['dependants'] as $ext_depend => $ext_version)
		{
			if ($this->ext_manager->is_enabled($ext_depend) && !array_search($ext_depend, $context))
			{
				$enabled[$ext_depend] = array(
											'version'		=> $this->dependencies[$ext_depend]['version'], 
											'display_name'	=> $this->dependencies[$ext_depend]['display-name'],
										);
				$enabled_depend = $this->enabled_dependants($ext_depend, $context);
				$enabled = array_merge(array_diff_key($enabled, $enabled_depend), $enabled_depend);
			}
		}
		unset($enabled[$ext_name]);
		return $enabled;
	}

	/**
	 * Extension display name
	 *
	 * @param string	$ext_name 	The name of the extension
	 * @return string 	The extension display name if set
	 */
	public function display_name($ext_name)
	{
		if (isset($this->dependencies[$ext_name]['display-name']))
		{
			return $this->dependencies[$ext_name]['display-name'];
		}
		else
		{
			return $ext_name;
		}
	}

	/**
	 * Extension data
	 *
	 * @param array		$list	Array with extension names as keys
	 * @return array 			Adds extension information as an array of key/value pairs to the received array
	 */
	public function ext_data($ext_list)
	{
		$return_list = array();
		foreach($ext_list as $ext_name => $ext_data)
		{
			$return_list[$ext_name] = array(
										'display_name'	=> (isset($this->dependencies[$ext_name]['display-name'])) ? $this->dependencies[$ext_name]['display-name'] : $ext_name,
										'version'		=> (isset($this->dependencies[$ext_name]['version'])) ? $this->dependencies[$ext_name]['version'] : '',
										's_enabled'		=> ($this->ext_manager->is_enabled($ext_name)) ? 1 : 0,
									);
		}
		return $return_list;
	}

	/**
	 * Extension dependencies
	 *
	 * @param string	$ext_name	The extension requested
	 * @return array 				Array with relevant data for dependencies of the referred extension, or an empty array if none
	 */
	public function ext_depends_on($ext_name)
	{
		$return_list = array();
		if (isset($this->dependencies[$ext_name]['depends_on']))
		{
			foreach($this->dependencies[$ext_name]['depends_on'] as $ext_depend => $ext_version)
			{
				$return_list[$ext_depend] = array(
											'display_name'	=> (isset($this->dependencies[$ext_depend]['display-name'])) ? $this->dependencies[$ext_depend]['display-name'] : $ext_depend,
											'version'		=> (isset($this->dependencies[$ext_depend]['version'])) ? $this->dependencies[$ext_depend]['version'] : '',
											'req_version'	=> $ext_version,
											's_enabled'		=> ($this->ext_manager->is_enabled($ext_depend)) ? 1 : 0,
											's_disabled'	=> ($this->ext_manager->is_disabled($ext_depend)) ? 1 : 0,
											's_available'	=> ($this->ext_manager->is_available($ext_depend)) ? 1 : 0,
										);
			}
		}
		return $return_list;
	}

	/**
	 * Extension dependants
	 *
	 * @param string	$ext_name	The extension requested
	 * @return array 				Array with relevant data for dependants of the referred extension, or an empty array if none
	 */
	public function ext_dependants($ext_name)
	{
		$return_list = array();
		if (isset($this->dependencies[$ext_name]['dependants']))
		{
			foreach($this->dependencies[$ext_name]['dependants'] as $ext_depend => $ext_version)
			{
				$return_list[$ext_depend] = array(
											'display_name'	=> (isset($this->dependencies[$ext_depend]['display-name'])) ? $this->dependencies[$ext_depend]['display-name'] : $ext_depend,
											'version'		=> (isset($this->dependencies[$ext_depend]['version'])) ? $this->dependencies[$ext_depend]['version'] : '',
											'req_version'	=> (isset($this->dependencies[$ext_depend]['depends_on'][$ext_name])) ? $this->dependencies[$ext_depend]['depends_on'][$ext_name] : '',
											's_enabled'		=> ($this->ext_manager->is_enabled($ext_depend)) ? 1 : 0,
											's_disabled'	=> ($this->ext_manager->is_disabled($ext_depend)) ? 1 : 0,
											's_available'	=> ($this->ext_manager->is_available($ext_depend)) ? 1 : 0,
										);
			}
		}
		return $return_list;
	}
}
