<?php

/**
 * Class NavigationDAO
 *
 * This class provides data access methods for managing
 * navigation menus, items, and their assignments and settings.
 */

import('lib.pkp.classes.db.DAO');
class NavigationDAO extends DAO {

	/**
	 * Retrieve navigation data from a specified table based on a column name and ID.
	 *
	 * @param string $tableName The name of the table from which data will be retrieved.
	 * @param string $columnName The name of the column to filter by.
	 * @param int $id The ID value to filter by in the specified column.
	 * @return array|null Returns an array of data rows if found, or null if no data is found.
	 */
	function getNavigationData($tableName, $columnName, $id) {
		$data = array();

		$result = $this->retrieve('SELECT * FROM ' . $tableName . ' WHERE ' . $columnName . ' = ?;',
			[(int) $id]);

		foreach ($result as $row) {
			$data[] = (array) $row;
		}

		if (count($data) == 0) {
			return null;
		}

		return $data;
	}


	/**
	 * Retrieve the assignment of a navigation item to a menu.
	 *
	 * @param int $menuId The ID of the navigation menu.
	 * @param int $itemId The ID of the navigation item.
	 * @return array|null Returns an array of assignment data if found, or null if no assignment is found.
	 */
	function getAssignment($menuId, $itemId) {
		$assign = array();

		$result = $this->retrieve('SELECT * FROM navigation_menu_item_assignments WHERE navigation_menu_id = ? AND navigation_menu_item_id = ?;',
			[(int) $menuId, (int) $itemId]);

		foreach ($result as $row) {
			$assign[] = (array) $row;
		}

		if (count($assign) == 0) {
			return null;
		}

		return $assign;
	}

	/**
	 * Update or insert a navigation menu item.
	 *
	 * @param int $contextId The context ID associated with the navigation item.
	 * @param string $path The path associated with the navigation item.
	 * @param string $type The type of the navigation item.
	 * @return bool Returns true on success or false on failure.
	 */
	function updateNavigationItem($contextId, $path, $type) {
		return $this->replace(
			'navigation_menu_items',
			[
				'context_id' => $contextId,
				'path' 	     => $path,
				'type'       => $type
			],
			[]
		);
	}

	/**
	 * Update or insert a navigation menu.
	 *
	 * @param int $contextId The context ID associated with the navigation menu.
	 * @param string $areaName The area name where the navigation menu is located.
	 * @param string $title The title of the navigation menu.
	 * @return bool Returns true on success or false on failure.
	 */
	function updateNavigationMenu($contextId, $areaName, $title) {
		return $this->replace(
			'navigation_menus',
			[
				'context_id' => $contextId,
				'area_name'  => $areaName,
				'title'      => $title
			],
			['context_id', 'area_name']
		);
	}

	/**
	 * Update or insert a navigation menu item setting.
	 *
	 * @param int $navigationItemId The ID of the navigation item.
	 * @param string $name The name of the setting.
	 * @param string $value The value of the setting.
	 * @param string $type The type of the setting.
	 * @return bool Returns true on success or false on failure.
	 */
	function updateNavigationSetting($navigationItemId, $name, $value, $type) {
		return $this->replace(
			'navigation_menu_item_settings',
			[
				'navigation_menu_item_id' => $navigationItemId,
				'setting_name'            => $name,
				'setting_value'           => $value,
				'setting_type'            => $type,
			],
			[]
		);
	}

	/**
	 * Update or insert an assignment of a navigation item to a menu.
	 *
	 * @param int $menuId The ID of the navigation menu.
	 * @param int $itemId The ID of the navigation item.
	 * @param int $parentId The ID of the parent navigation item, if any.
	 * @param int $seq The sequence number for the navigation item within the menu.
	 * @return bool Returns true on success or false on failure.
	 */
	function updateNavigationAssignment($menuId, $itemId, $parentId, $seq) {
		return $this->replace(
			'navigation_menu_item_assignments',
			[
				'navigation_menu_id' => $menuId,
				'navigation_menu_item_id' => $itemId,
				'parent_id' => $parentId,
				'seq' => $seq,
			],
			['navigation_menu_id', 'navigation_menu_item_id']
		);
	}

	/**
	 * Delete all navigation menus associated with a specific context.
	 *
	 * @param int $contextId The context ID for which the menus will be deleted.
	 * @return bool Returns true on success or false on failure.
	 */
	function deleteMenus($contextId) {
		return $this->update('DELETE FROM navigation_menus WHERE context_id = ?;',
			[(int) $contextId]);
	}

	/**
	 * Delete all navigation items associated with a specific context.
	 *
	 * @param int $contextId The context ID for which the items will be deleted.
	 * @return bool Returns true on success or false on failure.
	 */
	function deleteItems($contextId) {
		return $this->update('DELETE FROM navigation_menu_items WHERE context_id = ?;',
			[(int) $contextId]);
	}

	/**
	 * Delete the assignment of a navigation item to a specific menu.
	 *
	 * @param int $menuId The ID of the navigation menu.
	 * @param int $itemId The ID of the navigation item.
	 * @return bool Returns true on success or false on failure.
	 */
	function deleteAssignments($menuId, $itemId) {
		return $this->update('DELETE FROM navigation_menu_item_assignments WHERE navigation_menu_id = ? AND navigation_menu_item_id = ?;',
			[(int) $menuId, (int) $itemId]);
	}

	/**
	 * Delete all settings associated with a specific navigation item.
	 *
	 * @param int $navigationItemId The ID of the navigation item.
	 * @return bool Returns true on success or false on failure.
	 */
	function deleteSettingById($navigationItemId) {
		return $this->update('DELETE FROM navigation_menu_item_settings WHERE navigation_menu_item_id = ?;',
			[(int) $navigationItemId]);
	}
}
