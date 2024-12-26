<?php

class ImportNavigationCommand implements Command
{
    private $sourceContextId;
    private $currentContextId;
    private $locale;
    private $navigationDao;

    public function __construct($sourceContextId, $currentContextId, $locale, $navigationDao)
    {
        $this->sourceContextId = $sourceContextId;
        $this->currentContextId = $currentContextId;
        $this->locale = $locale;
        $this->navigationDao = $navigationDao;
    }

    public function execute()
    {
        $this->deleteNavigationPreviousSettings();

        $this->insertNavigationMenu();

        $this->applyNavigation();
    }

    /**
     * Deletes previous navigation settings in the specified context.
     *
     * @return void
     */
    private function deleteNavigationPreviousSettings()
    {
        $itemsCurrentContext = $this->navigationDao->getNavigationData('navigation_menu_items', 'context_id', $this->currentContextId);
        $menusCurrentContext = $this->navigationDao->getNavigationData('navigation_menus', 'context_id', $this->currentContextId);

        if (!$itemsCurrentContext || !$menusCurrentContext) {
            error_log('Navigation items or menus not found! Cannot delete the navigation previous settings from the context: ' . $this->currentContextId . '!');
            return;
        }

        foreach ($menusCurrentContext as $menu) {

            foreach ($itemsCurrentContext as $item) {
                $this->navigationDao->deleteAssignments($menu['navigation_menu_id'], $item['navigation_menu_item_id']);

                $this->navigationDao->deleteSettingById($item['navigation_menu_item_id']);
            }
        }

        $this->navigationDao->deleteItems($this->currentContextId);

        $this->navigationDao->deleteMenus($this->currentContextId);
    }

    /**
     * Inserts navigation menus and items into the target context.
     *
     * @param int $sourceContextId The ID of the source context from which navigation menus and items will be imported.
     * @param int $currentContextId The ID of the target context where the navigation menus and items will be inserted.
     * @param NavigationDAO $navigationDao The data access object used to manage navigation-related data.
     * @return void
     */
    private function insertNavigationMenu()
    {
        $menusSource = $this->navigationDao->getNavigationData('navigation_menus', 'context_id', $this->sourceContextId);
        $itemsSource = $this->navigationDao->getNavigationData('navigation_menu_items', 'context_id', $this->sourceContextId);

        if (!$menusSource) {
            error_log('navigation menus not found!');
            return;
        }

        if (!$itemsSource) {
            error_log('navigation items not found!');
            return;
        }

        foreach ($menusSource as $menu) {
            $this->navigationDao->updateNavigationMenu($this->currentContextId, $menu['area_name'], $menu['title']);
        }

        foreach ($itemsSource as $item) {
            $setting = $this->navigationDao->getNavigationSetting($this->locale, $item['navigation_menu_item_id']);

            if ($setting != null) {
                $this->navigationDao->updateNavigationItem($this->currentContextId, $item['path'], $item['type']);
            }
        }

        $this->insertNavigationSettings();
    }

    /**
     * Inserts navigation settings for the newly imported navigation items in the target context.
     *
     * @return void
     */
    private function insertNavigationSettings()
    {
        $itemsFromCurrentContext = $this->navigationDao->getNavigationData('navigation_menu_items', 'context_id', $this->currentContextId);
        $itemsFromSource = $this->navigationDao->getNavigationData('navigation_menu_items', 'context_id', $this->sourceContextId);

        for ($i = 0; $i < count($itemsFromSource); $i++) {
            $itemCurrentContext = $itemsFromCurrentContext[$i];
            $itemSource = $itemsFromSource[$i];

            $setting = $this->navigationDao->getNavigationSetting($this->locale, $itemSource['navigation_menu_item_id']);

            foreach ($setting as $set) {
                if ($set['setting_name'] != 'titleLocaleKey') {
                    $this->navigationDao->updateNavigationSettingWithLocale($itemCurrentContext['navigation_menu_item_id'], $this->locale, $set['setting_name'], $set['setting_value'], $set['setting_type']);
                } else {
                    $this->navigationDao->updateNavigationSetting($itemCurrentContext['navigation_menu_item_id'], $set['setting_name'], $set['setting_value'], $set['setting_type']);
                }
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
    private function applyNavigation()
    {
        $assignments = $this->getNavigationAssignments($this->sourceContextId);

        if (!$assignments) {
            error_log('assignments not found!');
            return;
        }

        $menus = $this->navigationDao->getNavigationData('navigation_menus', 'context_id', $this->currentContextId);
        $items = $this->navigationDao->getNavigationData('navigation_menu_items', 'context_id', $this->currentContextId);
        $itemsFromSource = $this->navigationDao->getNavigationData('navigation_menu_items', 'context_id', $this->sourceContextId);

        $assignsPrevious = array();
        $menuIndex = 0;

        for ($i = 0; $i < count($assignments); $i++) {
            $menu = $menus[$menuIndex];
            $assign = $assignments[$i];

            for ($k = 0; $k < count($itemsFromSource); $k++) {
                $itemSource = $itemsFromSource[$k];

                if ($itemSource['navigation_menu_item_id'] == $assign['navigation_menu_item_id']) {
                    $item = $items[$k];
                    continue;
                }
            }

            if (count($assignsPrevious) > 0) {
                $prev = $i - 1;
                $previous = $assignsPrevious[$prev];
                if ($assign['navigation_menu_id'] != $previous['navigation_menu_id']) {
                    $menuIndex = $menuIndex + 1;
                    $menu = $menus[$menuIndex];
                }
            }

            if ($assign['parent_id'] != 0) {
                for ($j = 0; $j < count($assignsPrevious); $j++) {
                    $previous = $assignsPrevious[$j];
                    if ($previous['navigation_menu_item_id'] == $assign['parent_id']) {
                        $itemPrev = $j - $menuIndex;
                        $itemPrevious = $items[$itemPrev];
                        $this->navigationDao->updateNavigationAssignment($menu['navigation_menu_id'], $item['navigation_menu_item_id'], $itemPrevious['navigation_menu_item_id'], $assign['seq']);
                    }
                }
            } else {
                $this->navigationDao->updateNavigationAssignment($menu['navigation_menu_id'], $item['navigation_menu_item_id'], 0, $assign['seq']);
            }

            $assignsPrevious[] = $assign;
        }
    }

    /**
     * Retrieves the navigation item assignments for a given context.
     *
     * @param int $contextId The ID of the context for which to retrieve navigation assignments.
     * @return array|null Returns an array of assignments if found, or null if no assignments are found.
     */
    private function getNavigationAssignments($contextId)
    {
        $menus = $this->navigationDao->getNavigationData('navigation_menus', 'context_id', $contextId);
        $items = $this->navigationDao->getNavigationData('navigation_menu_items', 'context_id', $contextId);
        $assignments = array();

        foreach ($menus as $menu) {
            foreach ($items as $item) {
                $assign = $this->navigationDao->getAssignment($menu['navigation_menu_id'], $item['navigation_menu_item_id']);

                if (!$assign) {
                    continue;
                }

                $assignments[] = $assign[0];
            }
        }

        if (count($assignments) == 0) {
            return null;
        }

        usort($assignments, function ($a, $b) {
            if ($a['navigation_menu_id'] !== $b['navigation_menu_id']) {
                return $a['navigation_menu_id'] <=> $b['navigation_menu_id'];
            }
            if ($a['parent_id'] !== $b['parent_id']) {
                return $a['parent_id'] <=> $b['parent_id'];
            }
            return $a['seq'] <=> $b['seq'];
        });

        return $assignments;
    }
}
