<?php

class ImportConfigurationCommand implements Command
{
    private $sourceContextId;
    private $currentContextId;
    private $contextDao;
    private $configToImport = array(
        'sidebar',
        'themePluginPath',
        'styleSheet',
        'shariffServicesSelected',
        'shariffEnableWCAG',
        'shariffOrientationSelected',
        'shariffPositionSelected',
        'shariffThemeSelected',
    );

    public function __construct($sourceContextId, $currentContextId, $contextDao)
    {
        $this->sourceContextId = $sourceContextId;
        $this->currentContextId = $currentContextId;
        $this->contextDao = $contextDao;
    }

    public function execute()
    {
        foreach ($this->configToImport as $index => $configName) {
            $this->insertConfigurationInContext($configName);
        }
    }

    /**
     * Insert site setting into current journal.
     *
     * @param $configName string to access the specific setting
     */
    private function insertConfigurationInContext($configName)
    {
        if ($this->sourceContextId == 0) {
            $configuration = $this->contextDao->getSiteSetting($configName);
        } else {
            $configuration = $this->contextDao->getJournalSetting($this->sourceContextId, $configName);
        }

        if (!$configuration) {
            error_log('configuration not found: ' . $configName);
            return;
        }

        foreach ($configuration as $setting_name => $setting_value) {

            if ($configName == 'styleSheet') {
                $this->copyStyleSheet($setting_value);
            }

            $this->contextDao->updateJournalSetting($this->currentContextId, $setting_name, $setting_value);
        }
    }

    private function copyStyleSheet($styleSheet)
    {
        $data = json_decode($styleSheet, true);
        $style = $data['uploadName'];
        $sourceDirectory = '';

        if ($this->sourceContextId == 0) {
            $sourceDirectory = 'site';
        } else {
            $sourceDirectory = 'journals' . '/' . $this->sourceContextId;
        }

        $publicDir = realpath('public');
        $sourceFile = $publicDir . '/' . $sourceDirectory . '/' . $style;
        $destinationDir = $publicDir . '/journals/' . $this->currentContextId . '/';
        $destinationFile = $destinationDir . $style;

        if (copy($sourceFile, $destinationFile)) {
            error_log('File successfully copied to: ' . $destinationDir);
        } else {
            error_log('Failed to copy the file.');
        }
    }
}
