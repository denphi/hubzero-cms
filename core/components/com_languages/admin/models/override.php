<?php
/**
 * @package		Joomla.Administrator
 * @subpackage	com_languages
 * @copyright	Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_HZEXEC_') or die();

jimport('joomla.application.component.modeladmin');

/**
 * Languages Override Model
 *
 * @package			Joomla.Administrator
 * @subpackage	com_languages
 * @since				2.5
 */
class LanguagesModelOverride extends JModelAdmin
{
	/**
	 * Method to get the record form.
	 *
	 * @param		array		$data			Data for the form.
	 * @param		boolean	$loadData	True if the form is to load its own data (default case), false if not.
	 *
	 * @return	mixed		A JForm object on success, false on failure
	 *
	 * @since		2.5
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form
		$form = $this->loadForm('com_languages.override', 'override', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form))
		{
			return false;
		}

		$client   = $this->getState('filter.client', 'site');
		$language = $this->getState('filter.language', 'en-GB');
		$langName = JLanguage::getInstance($language)->getName();
		if (!$langName)
		{
			// If a language only exists in frontend, it's meta data cannot be
			// loaded in backend at the moment, so fall back to the language tag
			$langName = $language;
		}
		$form->setValue('client', null, Lang::txt('COM_LANGUAGES_VIEW_OVERRIDE_CLIENT_'.strtoupper($client)));
		$form->setValue('language', null, Lang::txt('COM_LANGUAGES_VIEW_OVERRIDE_LANGUAGE', $langName, $language));
		$form->setValue('file', null, JPath::clean(PATH_APP . '/bootstrap/' . $client . '/language/overrides/' . $language . '.override.ini'));

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return	mixed The data for the form
	 *
	 * @since		2.5
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = User::getState('com_languages.edit.override.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param		string	$pk	The key name.
	 *
	 * @return	mixed		Object on success, false otherwise.
	 *
	 * @since		2.5
	 */
	public function getItem($pk = null)
	{
		require_once JPATH_COMPONENT.'/helpers/languages.php';

		$pk	= (!empty($pk)) ? $pk : Request::getCmd('id');
		$filename = PATH_APP . '/bootstrap/' . $this->getState('filter.client') . '/language/overrides/' . $this->getState('filter.language', 'en-GB').'.override.ini';
		$strings = LanguagesHelper::parseFile($filename);

		$result = new stdClass();
		$result->key = '';
		$result->override = '';
		if (isset($strings[$pk]))
		{
			$result->key = $pk;
			$result->override = $strings[$pk];
		}

		return $result;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param		array		$data							The form data.
	 * @param		boolean	$opposite_client	Indicates whether the override should not be created for the current client
	 *
	 * @return	boolean	True on success, false otherwise.
	 *
	 * @since		2.5
	 */
	public function save($data, $opposite_client = false)
	{
		require_once JPATH_COMPONENT.'/helpers/languages.php';

		$client   = User::getState('com_languages.overrides.filter.client', 0);
		$language = User::getState('com_languages.overrides.filter.language', 'en-GB');

		// If the override should be created for both
		if ($opposite_client)
		{
			$client = 1 - $client;
		}

		$client = $client ? 'administrator' : 'site';

		// Parse the override.ini file in oder to get the keys and strings
		$filename = PATH_APP . '/bootstrap/' . $client . '/language/overrides/' . $language . '.override.ini';
		$strings  = LanguagesHelper::parseFile($filename);

		if (isset($strings[$data['id']]))
		{
			// If an existent string was edited check whether
			// the name of the constant is still the same
			if ($data['key'] == $data['id'])
			{
				// If yes, simply override it
				$strings[$data['key']] = $data['override'];
			}
			else
			{
				// If no, delete the old string and prepend the new one
				unset($strings[$data['id']]);
				$strings = array($data['key'] => $data['override']) + $strings;
			}
		}
		else
		{
			// If it is a new override simply prepend it
			$strings = array($data['key'] => $data['override']) + $strings;
		}

		foreach ($strings as $key => $string) {
			$strings[$key] = str_replace('"', '"_QQ_"', $string);
		}

		// Write override.ini file with the strings
		$registry = new \Hubzero\Config\Registry($strings);

		if (!Filesystem::write($filename, $registry->toString('INI')))
		{
			return false;
		}

		// If the override should be stored for both clients save
		// it also for the other one and prevent endless recursion
		if (isset($data['both']) && $data['both'] && !$opposite_client)
		{
			return $this->save($data, true);
		}

		return true;
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @return	void
	 *
	 * @since		2.5
	 */
	protected function populateState()
	{
		$client = Request::getstate('com_languages.overrides.filter.client', 'filter_client', 0, 'int') ? 'administrator' : 'site';
		$this->setState('filter.client', $client);

		$language = Request::getState('com_languages.overrides.filter.language', 'filter_language', 'en-GB', 'cmd');
		$this->setState('filter.language', $language);
	}
}
