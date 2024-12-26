<?php

class ImportPluginCommand implements Command
{
    private $pluginDao;
    private $sourceContextId;
    private $currentContextId;
    private $locale;
    private $pluginsToImport = array(
        'customblockmanagerplugin',
        'customheaderplugin',
        'defaultchildthemeplugin',
        'viewcountermt',
        'most-read',
        'mostreadblockplugin',
        'keywordcloudblockplugin',
        'citationstylelanguageplugin',
        'citationsplugin',
        'reviewcertificateplugin',
        'pdfjsviewerplugin',
        'shariffplugin',
        'subscriptionblockplugin',
    );

    public function __construct($sourceContextId, $currentContextId, $locale, $pluginDao)
    {
        $this->sourceContextId = $sourceContextId;
        $this->currentContextId = $currentContextId;
        $this->locale = $locale;
        $this->pluginDao = $pluginDao;
    }

    public function execute()
    {
        foreach ($this->pluginsToImport as $index => $pluginName) {
            $this->insertPlugin($pluginName);
        }
    }

    /**
     * Insert plugin settings from source to current journal.
     *
     * @param $pluginName string to access the specific plugin
     */
    private function insertPlugin($pluginName)
    {
        $pluginSettings = $this->pluginDao->getPluginSettings($this->sourceContextId, $pluginName);

        if (!$pluginSettings) {
            return false;
        }

        foreach ($pluginSettings as $setting_name => $setting_value) {
            $this->pluginDao->updateSetting($this->currentContextId, $pluginName, $setting_name, $setting_value);

            if ($setting_name === 'blocks') {
                $this->insertBlocks($setting_value);
            }
        }
    }

    /**
     * Insert blocks from the site's customBlockManager to current journal.
     *
     * @param $blockList array blockList from site
     */
    private function insertBlocks($blockList)
    {
        foreach ($blockList as $index => $block_name) {
            $block_settings = $this->pluginDao->getPluginSettings($this->sourceContextId, $block_name);

            foreach ($block_settings as $setting_name => $setting_value) {

                if ($setting_name == 'blockContent' || $setting_name == 'blockTitle') {
                    if (!array_key_exists($this->locale, $setting_value)) {
                        return;
                    }

                    $setting_value = [$this->locale => $setting_value[$this->locale]];
                }

                $this->pluginDao->updateSetting($this->currentContextId, $block_name, $setting_name, $setting_value);
            }
        }
    }
}
