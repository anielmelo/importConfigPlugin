<?php

/**
 * @file plugins/generic/importConfig/ImportConfigPlugin.inc.php
 * @package plugins.generic.importConfig
 * @class ImportConfigPlugin
 *
 * Plugin to let managers import settings from site to journals
 *
 */

import('lib.pkp.classes.plugins.GenericPlugin');
class ImportConfigPlugin extends GenericPlugin {

	public function register($category, $path, $mainContextId = NULL) {
		$success = parent::register($category, $path);
		if ($success && $this->getEnabled()) {
			$this->import('SiteJournalDAO');
		        DAORegistry::registerDAO('SiteJournalDAO', new SiteJournalDAO());

			$this->import('NavigationDAO');
		        DAORegistry::registerDAO('NavigationDAO', new NavigationDAO());
		}
		return $success;
	}

	public function getDisplayName() {
		return __('plugins.generic.importConfig.displayName');
	}

	public function getDescription() {
		return __('plugins.generic.importConfig.description');
	}

	public function getActions($request, $actionArgs) {

		$actions = parent::getActions($request, $actionArgs);

		if (!$this->getEnabled()) {
			return $actions;
		}

		$router = $request->getRouter();
		import('lib.pkp.classes.linkAction.request.AjaxModal');
		$linkAction = new LinkAction(
			'settings',
			new AjaxModal(
				$router->url(
					$request,
					null,
					null,
					'manage',
					null,
					[
						'verb' => 'settings',
						'plugin' => $this->getName(),
						'category' => 'generic'
					]
				),
				$this->getDisplayName()
			),
			__('manager.plugins.settings'),
			null
		);

		array_unshift($actions, $linkAction);

		return $actions;
	}

	/**
	 * Manages actions of the plugin.
	 *
	 * @param array $args Action arguments
	 * @param PKPRequest $request Request object
	 * @return JSONMessage Response in JSON format
	 */
	public function manage($args, $request) {
		switch ($request->getUserVar('verb')) {
			case 'settings':
				$this->import('ImportConfigSettingsForm');
				$form = new ImportConfigSettingsForm($this);

				if (!$request->getUserVar('save')) {
					$form->initData();
					return new JSONMessage(true, $form->fetch($request));
				}

				$form->readInputData();

				if ($form->validate()) {
					$form->execute();
					return new JSONMessage(true);
				}
		}
		return parent::manage($args, $request);
	}
}
