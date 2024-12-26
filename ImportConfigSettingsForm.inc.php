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

class ImportConfigSettingsForm extends Form
{

  public $plugin;
  public $executor;

  public function __construct($plugin)
  {
    parent::__construct($plugin->getTemplateResource('settings.tpl'));
    $this->plugin = $plugin;

    $this->addCheck(new FormValidatorPost($this));
    $this->addCheck(new FormValidatorCSRF($this));
  }

  public function initData()
  {
    $contextDao = DAORegistry::getDAO('SiteJournalDAO');

    $context = Application::get()->getRequest()->getContext();
    $currentContextId = $context->getId();

    $journals = $contextDao->getAllContexts($currentContextId);

    $journalOptions = [];
    $journalOptions[0] = 'Portal';

    foreach ($journals as $journal_id => $journal_name) {
      $journalOptions[$journal_id] = $journal_name;
    }

    $this->setData('journalOptions', $journalOptions);
    parent::initData();
  }

  public function readInputData()
  {
    $this->readUserVars(['selectedJournal']);
    parent::readInputData();
  }

  public function fetch($request, $template = null, $display = false)
  {
    $templateMgr = TemplateManager::getManager($request);
    $templateMgr->assign('pluginName', $this->plugin->getName());

    return parent::fetch($request, $template, $display);
  }

  public function execute(...$functionArgs)
  {
    $pluginDao = DAORegistry::getDAO('PluginSettingsDAO');
    $contextDao = DAORegistry::getDAO('SiteJournalDAO');
    $navigationDao = DAORegistry::getDAO('NavigationDAO');

    $sourceContextId = $this->getData('selectedJournal');
    $this->executor = new CommandExecutor();

    $context = Application::get()->getRequest()->getContext();
    if (!$context) {
      return false;
    }
    $currentContextId = $context->getId();
    $locale = $context->getPrimaryLocale();

    $daoOK = $this->verifyDaos(array($pluginDao, $contextDao, $navigationDao));

    if ($daoOK == false) {
      return $daoOK;
    }

    $this->executor->executeCommand(new ImportPluginCommand($sourceContextId, $currentContextId, $locale, $pluginDao));
    $this->executor->executeCommand(new ImportConfigurationCommand($sourceContextId, $currentContextId, $contextDao));
    $this->executor->executeCommand(new ImportNavigationCommand($sourceContextId, $currentContextId, $locale, $navigationDao));

    import('classes.notification.NotificationManager');
    $notificationMgr = new NotificationManager();
    $notificationMgr->createTrivialNotification(
      Application::get()->getRequest()->getUser()->getId(),
      NOTIFICATION_TYPE_SUCCESS,
      ['contents' => __('common.changesSaved')]
    );

    return parent::execute(...$functionArgs);
  }

  private function verifyDaos($daos)
  {
    foreach ($daos as $index => $dao) {
      if (!$dao) {
        error_log('dao não encontrado!' . $dao);
        return false;
      }
      return true;
    }
  }
}
