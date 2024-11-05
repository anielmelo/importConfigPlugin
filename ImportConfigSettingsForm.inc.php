<?php

/**
 * @file plugins/generic/importConfig/ImportConfigSettingsForm.inc.php
 * @package plugins.generic.importConfig
 * @class ImportConfigSettingsForm
 *
 * SettingsForm to manage the plugin's form and execute the import/apply methods
 *
 */

import('lib.pkp.classes.form.Form');
class ImportConfigSettingsForm extends Form {

	public $plugin;

	public function __construct($plugin) {
		parent::__construct($plugin->getTemplateResource('settings.tpl'));
		$this->plugin = $plugin;

		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

  public function initData() {
    $contextDao = DAORegistry::getDAO('SiteJournalDAO');
    
    $context = Application::get()->getRequest()->getContext();
    $currentContextId = $context->getId();

    $journals = $contextDao->getAll($currentContextId);


    $journalOptions = [];
    $journalOptions[0] = 'Portal';
    
    foreach ($journals as $journal_id => $journal_name) {
      $journalOptions[$journal_id] = $journal_name;
    }

    $this->setData('journalOptions', $journalOptions);
		parent::initData();
	}

  public function readInputData() {

    $this->readUserVars(['selectedJournal']);
		parent::readInputData();
	}

	public function fetch($request, $template = null, $display = false) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('pluginName', $this->plugin->getName());

		return parent::fetch($request, $template, $display);
	}

  public function execute(...$functionArgs) {

    $sourceContextId = $this->getData('selectedJournal');
    
    $context = Application::get()->getRequest()->getContext();
    
    if (!$context) {
      return false;
    }

    $currentContextId = $context->getId();

    $this->importPlugins($sourceContextId, $currentContextId);
    $this->applyConfiguration($sourceContextId, $currentContextId);
    $this->importNavigationMenu($sourceContextId, $currentContextId);

		import('classes.notification.NotificationManager');
		$notificationMgr = new NotificationManager();
		$notificationMgr->createTrivialNotification(
			Application::get()->getRequest()->getUser()->getId(),
			NOTIFICATION_TYPE_SUCCESS,
			['contents' => __('common.changesSaved')]
		);

		return parent::execute(...$functionArgs);
	}

	/**
	 * Import plugins from PluginSettingsDAO.
	 *
	 * @param $sourceContextId int site's ID to export data
	 * @param $currentContextId int journal's ID to import data from site
	 */
	private function importPlugins($sourceContextId, $currentContextId) {
		$pluginDao = DAORegistry::getDAO('PluginSettingsDAO');

		if (!$pluginDao) {
			error_log('PluginSettingsDAO not found!');
			return false;
		}

		$this->insertPlugin($sourceContextId, $currentContextId, $pluginDao, 'customblockmanagerplugin');
		$this->insertPlugin($sourceContextId, $currentContextId, $pluginDao, 'customheaderplugin');
		$this->insertPlugin($sourceContextId, $currentContextId, $pluginDao, 'defaultchildthemeplugin');
	}

	/**
	 * Insert plugin settings from site to current journal.
	 *
	 * @param $sourceContextId int site's ID to export data
	 * @param $currentContextId int journal's ID to import data from site
	 * @param $pluginDao class DAO to get plugin settings
	 * @param $pluginName string to access the specific plugin
	 */
	private function insertPlugin($sourceContextId, $currentContextId, $pluginDao, $pluginName) {
		$pluginSettings = $pluginDao->getPluginSettings($sourceContextId, $pluginName);

		if (!$pluginSettings) {
			error_log('plugin_settings not found for ' . $pluginName);
			return false;
		}

		foreach ($pluginSettings as $setting_name => $setting_value) {
			$pluginDao->updateSetting($currentContextId, $pluginName, $setting_name, $setting_value);

			if ($setting_name === 'blocks') {
				$this->insertBlocks($sourceContextId, $currentContextId, $pluginDao, $setting_value);
			}
		}
	}

	/**
	 * Insert blocks from the site's customBlockManager to current journal.
	 *
	 * @param $sourceContextId int site's ID to export data
	 * @param $currentContextId int journal's ID to import data from site
	 * @param $pluginDao class DAO to get plugin settings
	 * @param $blockList array blockList from site
	 */
	private function insertBlocks($sourceContextId, $currentContextId, $pluginDao, $blockList) {
		foreach ($blockList as $index => $block_name) {
			$block_settings = $pluginDao->getPluginSettings($sourceContextId, $block_name);
			foreach ($block_settings as $setting_name => $setting_value) {
				$pluginDao->updateSetting($currentContextId, $block_name, $setting_name, $setting_value);
			}
		}
	}

	/**
	 * Apply specific settings from site to current journal with ImportConfigDAO.
	 *
	 * @param $sourceContextId int site's ID to export data
	 * @param $currentContextId int journal's ID to import data from site
	 */
	private function applyConfiguration($sourceContextId, $currentContextId) {
		$settingDao = DAORegistry::getDAO('SiteJournalDAO');

		if (!$settingDao) {
			error_log('SettingDAO not found!');
			return false;
		}

		$this->insertConfigurationInContext($sourceContextId, $currentContextId, $settingDao, 'sidebar');
		$this->insertConfigurationInContext($sourceContextId, $currentContextId, $settingDao, 'themePluginPath');
		$this->insertConfigurationInContext($sourceContextId, $currentContextId, $settingDao, 'styleSheet');
	}

	/**
	 * Insert site setting into current journal.
	 *
	 * @param $sourceContextId int site's ID to export data
	 * @param $currentContextId int journal's ID to import data from site
	 * @param $settingDao class DAO to get/update a setting
	 * @param $configName string to access the specific setting
	 */
  private function insertConfigurationInContext($sourceContextId, $currentContextId, $settingDao, $configName) {
    if ($sourceContextId == 0) {
      $configuration = $settingDao->getSiteSetting($configName);
    } else {
      $configuration = $settingDao->getJournalSetting($sourceContextId, $configName);
    }
    
		if (!$configuration) {
			error_log('configuration not found: ' . $configName);
			return;
		}

		foreach ($configuration as $setting_name => $setting_value) {

      if ($configName == 'styleSheet') {
        $this->copyStyleSheet($setting_value, $sourceContextId, $currentContextId);
			}

			$settingDao->updateJournalSetting($currentContextId, $setting_name, $setting_value);
		}
	}

  private function copyStyleSheet($styleSheet, $sourceContextId, $currentContextId) {
    $data = json_decode($styleSheet, true);
    $style = $data['uploadName'];
    $sourceDirectory = '';

    if ($sourceContextId == 0) {
      $sourceDirectory = 'site';
    } else {
      $sourceDirectory = 'journals' . '/' . $sourceContextId;
    }

    $publicDir = realpath('public');
    $sourceFile = $publicDir . '/' . $sourceDirectory . '/' . $style;
    $destinationDir = $publicDir . '/journals/' . $currentContextId . '/';
    $destinationFile = $destinationDir . $style;

    if (copy($sourceFile, $destinationFile)) {
      error_log('File successfully copied to: ' . $destinationDir);
    } else {
      error_log('Failed to copy the file.');
    }
  }

	/**
	 * Imports navigation menus and items from one context to another.
	 *
	 * @param int $sourceContextId The ID of the source context from which navigation menus and items will be imported.
	 * @param int $currentContextId The ID of the target context where the navigation menus and items will be inserted.
	 * @return bool Returns false if the NavigationDAO cannot be retrieved, otherwise void.
	 */
	private function importNavigationMenu($sourceContextId, $currentContextId) {
		$navigationDao = DAORegistry::getDAO('NavigationDAO');

		if (!$navigationDao) {
			error_log('navigation dao not found!');
			return false;
		}

		$this->deleteNavigationPreviousSettings($currentContextId, $navigationDao);

		$this->insertNavigationMenu($sourceContextId, $currentContextId, $navigationDao);

		$this->applyNavigation($sourceContextId, $currentContextId, $navigationDao);
	}

	/**
	 * Deletes previous navigation settings in the specified context.
	 *
	 * @param int $currentContextId The ID of the context whose navigation settings are to be deleted.
	 * @param NavigationDAO $navigationDao The data access object used to manage navigation-related data.
	 * @return void
	 */
	private function deleteNavigationPreviousSettings($currentContextId, $navigationDao) {
		$itemsCurrentContext = $navigationDao->getNavigationData('navigation_menu_items', 'context_id', $currentContextId);
		$menusCurrentContext = $navigationDao->getNavigationData('navigation_menus', 'context_id', $currentContextId);

		if (!$itemsCurrentContext || !$menusCurrentContext) {
			error_log('Navigation items or menus not found! Cannot delete the navigation previous settings from the context: ' . $currentContextId . '!');
			return;
		}

		foreach ($menusCurrentContext as $menu) {

			foreach($itemsCurrentContext as $item) {
				$navigationDao->deleteAssignments($menu['navigation_menu_id'], $item['navigation_menu_item_id']);

				$navigationDao->deleteSettingById($item['navigation_menu_item_id']);
			}
		}

		$navigationDao->deleteItems($currentContextId);

		$navigationDao->deleteMenus($currentContextId);
	}

	/**
	 * Inserts navigation menus and items into the target context.
	 *
	 * @param int $sourceContextId The ID of the source context from which navigation menus and items will be imported.
	 * @param int $currentContextId The ID of the target context where the navigation menus and items will be inserted.
	 * @param NavigationDAO $navigationDao The data access object used to manage navigation-related data.
	 * @return void
	 */
	private function insertNavigationMenu($sourceContextId, $currentContextId, $navigationDao) {
		$menusPortal = $navigationDao->getNavigationData('navigation_menus', 'context_id', $sourceContextId);
		$itemsPortal = $navigationDao->getNavigationData('navigation_menu_items', 'context_id', $sourceContextId);

		if (!$menusPortal) {
			error_log('navigation menus not found!');
			return;
		}

		if (!$itemsPortal) {
			error_log('navigation items not found!');
			return;
		}

		foreach($menusPortal as $menu) {
			$navigationDao->updateNavigationMenu($currentContextId, $menu['area_name'], $menu['title']);
		}

		foreach($itemsPortal as $item) {
			$navigationDao->updateNavigationItem($currentContextId, $item['path'], $item['type']);
		}

		$this->insertNavigationSettings($sourceContextId, $currentContextId, $navigationDao);
	}

	/**
	 * Inserts navigation settings for the newly imported navigation items in the target context.
	 *
	 * @param int $sourceContextId The ID of the source context from which navigation item settings will be imported.
	 * @param int $currentContextId The ID of the target context where the navigation item settings will be inserted.
	 * @param NavigationDAO $navigationDao The data access object used to manage navigation-related data.
	 * @return void
	 */
	private function insertNavigationSettings($sourceContextId, $currentContextId, $navigationDao) {
		$itemsFromCurrentContext = $navigationDao->getNavigationData('navigation_menu_items', 'context_id', $currentContextId);
		$itemsFromPortal = $navigationDao->getNavigationData('navigation_menu_items', 'context_id', $sourceContextId);

		for ($i = 0; $i < count($itemsFromPortal); $i++) {
			$itemCurrentContext = $itemsFromCurrentContext[$i];
			$itemPortal = $itemsFromPortal[$i];

			$setting = $navigationDao->getNavigationData('navigation_menu_item_settings', 'navigation_menu_item_id', $itemPortal['navigation_menu_item_id']);

			foreach($setting as $set) {
				$navigationDao->updateNavigationSetting($itemCurrentContext['navigation_menu_item_id'], $set['setting_name'], $set['setting_value'], $set['setting_type']);
			}
		}
	}

	/**
	 * Applies the navigation item assignments in the target context based on the imported data.
	 *
	 * @param int $sourceContextId The ID of the source context from which navigation assignments will be imported.
	 * @param int $currentContextId The ID of the target context where the navigation assignments will be applied.
	 * @param NavigationDAO $navigationDao The data access object used to manage navigation-related data.
	 * @return void
	 */
	private function applyNavigation($sourceContextId, $currentContextId, $navigationDao) {
		$assignments = $this->getNavigationAssignments($sourceContextId, $navigationDao);

		if (!$assignments) {
			error_log('assignments not found!');
			return;
		}

		$menus = $navigationDao->getNavigationData('navigation_menus', 'context_id', $currentContextId);
		$items = $navigationDao->getNavigationData('navigation_menu_items', 'context_id', $currentContextId);
		$assignsPrevious = array();
		$menuIndex = 0;

		for ($i = 0; $i < count($assignments); $i++) {
			$menu = $menus[$menuIndex];
			$item = $items[$i];
			$assign = $assignments[$i];

			if (count($assignsPrevious) > 0) {
				$j = $i - 1;
				$previous = $assignsPrevious[$j];
				if ($assign['navigation_menu_id'] != $previous['navigation_menu_id']) {
					$menuIndex++;
					$menu = $menus[$menuIndex];
				}
			}

			if ($assign['parent_id'] != 0) {
				for ($j = 0; $j < count($assignsPrevious); $j++) {
					$previous = $assignsPrevious[$j];
					if ($previous['navigation_menu_item_id'] == $assign['parent_id']) {
						$itemPrevious = $items[$j];
						$navigationDao->updateNavigationAssignment($menu['navigation_menu_id'], $item['navigation_menu_item_id'], $itemPrevious['navigation_menu_item_id'], $i);
					}
				}
			} else {
				$navigationDao->updateNavigationAssignment($menu['navigation_menu_id'], $item['navigation_menu_item_id'], 0, $i);
			}

			$assignsPrevious[] = $assign;
		}
	}

	/**
	 * Retrieves the navigation item assignments for a given context.
	 *
	 * @param int $contextId The ID of the context for which to retrieve navigation assignments.
	 * @param NavigationDAO $navigationDao The data access object used to manage navigation-related data.
	 * @return array|null Returns an array of assignments if found, or null if no assignments are found.
	 */
	private function getNavigationAssignments($contextId, $navigationDao) {
		$menus = $navigationDao->getNavigationData('navigation_menus', 'context_id', $contextId);
		$items = $navigationDao->getNavigationData('navigation_menu_items', 'context_id', $contextId);
		$assignments = array();

		foreach ($menus as $menu) {
			foreach ($items as $item) {
				$assign = $navigationDao->getAssignment($menu['navigation_menu_id'], $item['navigation_menu_item_id']);

				if (!$assign) {
					continue;
				}

				$assignments[] = $assign[0];
			}
		}

		if (count($assignments) == 0) {
			return null;
		}

		return $assignments;
	}
}
