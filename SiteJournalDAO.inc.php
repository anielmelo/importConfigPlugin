<?php

/**
 * @file plugins/generic/importConfig/SiteJournalDAO.inc.php
 *
 *
 * @package plugins.generic.importConfig
 * @class SiteJournalDAO
 *
 * Class to access/update settings from site and journals values
 *
 */


import('lib.pkp.classes.db.DAO');
class SiteJournalDAO extends DAO {

	/**
	 * Retrieve all journals except the specified one.
	 * @param $currentContextId int Context ID to exclude
	 * @return null||array
	 */
	function getAll($currentContextId) {
		$result = $this->retrieve(
			'SELECT journal_id, setting_value FROM journal_settings WHERE setting_name = "name" AND setting_value != "NULL" AND journal_id != ?',
			array($currentContextId)
		);

		$journals = array();

		foreach ($result as $row) {
			$journals[$row->journal_id] = $row->setting_value;
		}

		if (count($journals) == 0) {
			return null;
		}

		return $journals;
	}

	/**
	 * Retrieve a site setting.
	 * @param $settingName string Setting name
	 * @return null||array
	 */
	function getSiteSetting($settingName) {
		$result = $this->retrieve(
			'SELECT setting_name, setting_value FROM site_settings WHERE setting_name = ?',
			array($settingName)
		);

		$site_setting = array();

		foreach ($result as $row) {
			$site_setting[$row->setting_name] = $row->setting_value;
		}

		if (count($site_setting) == 0) {
			return null;
		}

		return $site_setting;
	}

	/**
	 * Retrieve a journal setting.
	 * @param $journalId int Journal ID
	 * @param $settingName string Setting name
	 * @return null||array
	 */
	function getJournalSetting($journalId, $settingName) {
		$result = $this->retrieve(
			'SELECT setting_name, setting_value FROM journal_settings WHERE journal_id = ? and setting_name = ?',
			array((int) $journalId, $settingName)
		);

		$journal_setting = array();

		foreach ($result as $row) {
			$journal_setting[$row->setting_name] = $row->setting_value;
		}

		if (count($journal_setting) == 0) {
			return null;
		}

		return $journal_setting;
	}

	/**
	 * Add/update a journal setting.
	 * @param $journalId int journal ID
	 * @param $settingName string Setting name
	 * @param $settingValue mixed Setting value
	 * @param $settingType string data type of the setting. If omitted, type will be NULL
	 */
	function updateJournalSetting($journalId, $settingName, $settingValue, $settingType = NULL) {
		$this->replace(
			'journal_settings',
			array(
				'journal_id' => (int) $journalId,
				'setting_name' => $settingName,
				'setting_value' => $settingValue,
				'setting_type' => $settingType,
			),
			array('journal_id', 'setting_name')
		);
	}

}
