<?php

/*******************************************************************************
 *
 * WINBINDER - The native Windows binding for PHP for PHP
 *
 * Copyright Hypervisual - see LICENSE.TXT for details
 * Author: Rubem Pechansky (https://github.com/crispy-computing-machine/Winbinder)
 *
 * Refactor the below functions that begin with _ into your own Winbinder library
 * Temp functions no longer exist.
 *******************************************************************************/
//------------------------------------------------------------- WINDOW FUNCTIONS

/**
 * Creates a window control, menu, toolbar, status bar or accelerator.
 * 
 * This function serves as a wrapper around various WinBinder creation functions,
 * providing a unified interface for creating different types of UI elements.
 * It handles special cases for different control types and applies appropriate
 * initialization based on the control class.
 *
 * @param int $parent The handle of the parent window or control
 * @param int|string $class The class constant or name of the control to create
 * @param string|array $caption The caption text or array of items for the control
 * @param int $xpos The x-coordinate of the control
 * @param int $ypos The y-coordinate of the control
 * @param int $width The width of the control
 * @param int $height The height of the control
 * @param int|null $id The identifier of the control (null for auto-assignment)
 * @param int $style The style flags for the control
 * @param mixed|null $lparam Additional parameter for the control (varies by control type)
 * @param int $ntab The tab number where the control should appear (for TabControl)
 * @return int The handle of the created control or 0 on failure
 */
function _create_control($parent, $class, $caption = "", $xpos = 0, $ypos = 0, $width = 0, $height = 0, $id = null, $style = 0, $lparam = null, $ntab = 0)
{
    switch ($class) {

        case Accel:
            return wb_set_accel_table($parent, $caption);

        case ToolBar:
            return wb_create_toolbar($parent, $caption, $width, $height, $lparam);

        case Menu:
            return wb_create_menu($parent, $caption);

        case HyperLink:
            return wb_create_control($parent, $class, $caption, $xpos, $ypos, $width, $height, $id, $style,
                is_null($lparam) ? NOCOLOR : $lparam, $ntab);

        case ComboBox:
        case ListBox:
        case ListView:
            $ctrl = wb_create_control($parent, $class, $caption, $xpos, $ypos, $width, $height, $id, $style, $lparam, $ntab);
            if (is_array($caption))
                _set_text($ctrl, $caption[0]);
            return $ctrl;

        case TreeView:
            $ctrl = wb_create_control($parent, $class, $caption, $xpos, $ypos, $width, $height, $id, $style, $lparam, $ntab);
            if (is_array($caption))
                _set_text($ctrl, $caption[0]);
            return $ctrl;

        case Gauge:
        case Slider:
        case ScrollBar:
            $ctrl = wb_create_control($parent, $class, $caption, $xpos, $ypos, $width, $height, $id, $style, $lparam, $ntab);
            if ($lparam)
                _set_value($ctrl, $lparam);
            return $ctrl;

        default:
            return wb_create_control($parent, $class, $caption, $xpos, $ypos, $width, $height, $id, $style, $lparam, $ntab);
    }
}

/**
 * Sets the value of a control or control item.
 * 
 * This function handles different control types appropriately when setting values.
 * For ListView controls, it can check multiple items based on their indices.
 * For TreeView controls, it sets the value of a specific item.
 * For other controls, it uses the standard wb_set_value function.
 *
 * @param int $ctrl The handle of the control
 * @param mixed $value The value to set. For ListView, can be an array of indices to check
 * @param mixed|null $item For TreeView, the item identifier; for other controls, passed to wb_set_value
 * @return int|null The result of the operation or null if the control handle is invalid
 */
function _set_value($ctrl, $value, $item = null)
{
    if (!$ctrl)
        return null;

    $class = wb_get_class($ctrl);
    switch ($class) {

        case ListView:        // Array with items to be checked

            if ($value === null)
                break;
            elseif (is_string($value) && strstr($value, ","))
                $values = explode(",", $value);
            elseif (!is_array($value))
                $values = array($value);
            else
                $values = $value;
            foreach ($values as $index)
                wb_set_listview_item_checked($ctrl, $index, 1);
            break;

        case TreeView:        // Array with items to be checked

            if ($item === null)
                $item = wb_get_selected($ctrl);
            return wb_set_treeview_item_value($ctrl, $item, $value);

        default:

            if ($value !== null) {
                return wb_set_value($ctrl, $value, $item);
            }
    }
}


/**
 * Gets the text from a control, a control item, or a control sub-item.
 * 
 * This function provides a unified interface for retrieving text from different
 * control types, handling the specific requirements of each control type.
 * For ListView controls, it can retrieve text from specific items/subitems or the entire table.
 * For TreeView controls, it retrieves text from specific items or the selected item.
 * For ComboBox and ListBox controls, it handles item selection appropriately.
 *
 * @param int $ctrl The handle of the control
 * @param mixed|null $item The item identifier or index (null for selected item or all items)
 * @param int|null $subitem For ListView, the subitem index
 * @return string|array|null The text from the control, item, or subitem; null if control handle is invalid
 */
function _get_text($ctrl, $item = null, $subitem = null)
{
    if (!$ctrl)
        return null;

    if (wb_get_class($ctrl) == ListView) {

        if ($item !== null) {        // Valid item

            $line = wb_get_listview_text($ctrl, $item);
            if ($subitem === null)
                return $line;
            else
                return $line[$subitem];

        } else {                    // NULL item

            $sel = wb_get_selected($ctrl);
            if ($sel === null) {        // Returns the entire table
                $items = array();
                for ($i = 0; ; $i++) {
                    $item = wb_get_listview_text($ctrl, $i);
                    $all = implode('', $item);
                    if ($all == '')
                        break;
                    $items[] = $item;
                }
                return $items ? $items : null;
            } else {
                $items = array();
                foreach ($sel as $row)
                    $items[] = wb_get_listview_text($ctrl, $row);
                return $items ? $items : null;
            }
        }

    } elseif (wb_get_class($ctrl) == TreeView) {

        if ($item) {
            return wb_get_treeview_item_text($ctrl, $item);
        } else {
            $sel = wb_get_selected($ctrl);
            if ($sel === null)
                return null;
            else {
                return wb_get_text($ctrl);
            }
        }

    } elseif (wb_get_class($ctrl) == ComboBox) {

        return wb_get_text($ctrl, $item === null ? -1 : $item);

    } elseif (wb_get_class($ctrl) == ListBox) {

        return wb_get_text($ctrl, $item === null ? -1 : $item);

    } else {

        return wb_get_text($ctrl, $item);

    }
}

/**
 * Sets the text of a control or control items.
 * 
 * This function provides a unified interface for setting text in different control types:
 * - For ListView: Creates columns or sets cell text
 * - For ListBox: Sets items or selects an item
 * - For ComboBox: Sets items or selects/sets an item
 * - For TreeView: Sets item text or creates items
 * - For other controls: Uses the standard wb_set_text function
 *
 * @param int $ctrl The handle of the control
 * @param mixed $text The text to set (string, array of strings, or array of arrays for ListView columns)
 * @param mixed|null $item For ListView/TreeView, the item identifier; for other controls, passed to wb_set_text
 * @param int|null $subitem For ListView, the subitem index
 * @return mixed The result of the operation or null if the control handle is invalid
 */
function _set_text($ctrl, $text, $item = null, $subitem = null)
{
    if (!$ctrl)
        return null;

    switch (wb_get_class($ctrl)) {

        case ListView:

            if ($item !== null) {

                if (!is_array($text) && $subitem !== null) {

                    // Set text of a ListView cell according to $item and $subitem

                    wb_set_listview_item_text($ctrl, $item, $subitem, $text);

                } else {

                    // Set text of several ListView cells, ignoring $subitem

                    for ($sub = 0; $sub < count($text); $sub++) {
                        if ($text) {
                            if (($text[$sub] !== null)) {
                                wb_set_listview_item_text($ctrl, $item, $sub, (string)$text[$sub]);
                            }
                        } else {
                            wb_set_listview_item_text($ctrl, $item, $sub, "");
                        }
                    }
                }

            } else {

                if (!is_array($text))
                    $text = explode(",", $text);

                wb_delete_items($ctrl, null);

                wb_clear_listview_columns($ctrl);

                // Create column headers
                // In the loop below, passing -1 as the 'width' argument of wb_create_listview_column()
                // makes it calculate the column width automatically

                for ($i = 0; $i < count($text); $i++) {
                    if (is_array($text[$i]))
                        wb_create_listview_column($ctrl, $i,
                            (string)$text[$i][0],
                            isset($text[$i][1]) ? (int)$text[$i][1] : -1,
                            isset($text[$i][2]) ? (int)$text[$i][2] : WBC_LEFT
                        );
                    else
                        wb_create_listview_column($ctrl, $i,
                            (string)$text[$i], -1, 0);
                }
            }
            break;

        case ListBox:

            if (!$text) {
                wb_delete_items($ctrl);
            } elseif (is_string($text)) {
                if (strchr($text, "\r") || strchr($text, "\n")) {
                    $text = preg_split("/[\r\n,]/", $text);
                    wb_delete_items($ctrl);
                    foreach ($text as $str)
                        wb_create_item($ctrl, (string)$str);
                } else {
                    $index = wb_send_message($ctrl, LB_FINDSTRINGEXACT, -1, wb_get_address($text));
                    wb_send_message($ctrl, LB_SETCURSEL, $index, 0);
                }
            } elseif (is_array($text)) {
                wb_delete_items($ctrl);
                foreach ($text as $str)
                    wb_create_item($ctrl, (string)$str);
            }
            return;

        case ComboBox:

            if (!$text)
                wb_delete_items($ctrl);
            elseif (is_string($text)) {
                if (strchr($text, "\r") || strchr($text, "\n")) {
                    $text = preg_split("/[\r\n,]/", $text);
                    wb_delete_items($ctrl);
                    foreach ($text as $str)
                        wb_create_item($ctrl, (string)$str);
                } else {
                    $index = wb_send_message($ctrl, CB_FINDSTRINGEXACT, -1, wb_get_address($text));
                    wb_send_message($ctrl, CB_SETCURSEL, $index, 0);
                    if ($index == -1)
                        wb_send_message($ctrl, WM_SETTEXT, 0, wb_get_address($text));
                }
            } elseif (is_array($text)) {
                wb_delete_items($ctrl);
                foreach ($text as $str)
                    wb_create_item($ctrl, (string)$str);
            }
            return;

        case TreeView:

            if ($item)
                return wb_set_treeview_item_text($ctrl, $item, $text);
            else
                return _create_items($ctrl, $text, true);

        default:
            // The (string) cast below works well but is a temporary fix, must be
            // removed when wb_set_text() accepts numeric types correctly
            if (is_array($text))
                return wb_set_text($ctrl, $text, $item);
            else
                return wb_set_text($ctrl, (string)$text, $item);
    }
}

/**
 * Selects one or more items in a control.
 * 
 * This function provides a unified interface for selecting items in different control types.
 * Unlike _set_value() which checks items, this function selects them.
 * For ListView controls, it can select multiple items.
 * For Menu controls, it checks/unchecks menu items.
 *
 * @param int $ctrl The handle of the control
 * @param mixed $selitems The item(s) to select (integer index, array of indices, or null to deselect all)
 * @param bool $selected Whether to select (TRUE) or deselect (FALSE) the items
 * @return bool TRUE if successful, FALSE otherwise
 */
function _set_selected($ctrl, $selitems = 0, $selected = TRUE)
{
    switch (wb_get_class($ctrl)) {

        case ComboBox:
            wb_send_message($ctrl, CB_SETCURSEL, (int)$selitems, 0);
            break;

        case ListBox:
            wb_send_message($ctrl, LB_SETCURSEL, (int)$selitems, 0);
            break;

        case ListView:

            if (is_null($selitems)) {
                return wb_select_all_listview_items($ctrl, false);
            } elseif (is_array($selitems)) {
                foreach ($selitems as $item)
                    wb_select_listview_item($ctrl, $item, $selected);
                return TRUE;
            } else
                return wb_select_listview_item($ctrl, $selitems, $selected);
            break;

        case Menu:
            return wb_set_menu_item_checked($ctrl, $selitems, $selected);

        case TabControl:
            wb_select_tab($ctrl, (int)$selitems);
            break;

        case TreeView:
            wb_set_treeview_item_selected($ctrl, $selitems);
            break;

        default:
            return false;
    }
    return true;
}

/**
 * Creates one or more items in a control.
 * 
 * This function provides a unified interface for creating items in different control types:
 * - For ListView: Creates rows and cells
 * - For TreeView: Creates tree nodes
 * - For StatusBar: Creates status bar sections
 * - For other controls: Creates individual items
 *
 * @param int $ctrl The handle of the control
 * @param mixed $items The item(s) to create (string, array of strings, or array of arrays)
 * @param bool $clear Whether to clear existing items before creating new ones
 * @param callable|null $param For ListView, a callback function to format cell text
 * @return mixed The handle of the created item(s), TRUE on success, or void
 */
function _create_items($ctrl, $items, $clear = false, $param = null)
{
    switch (wb_get_class($ctrl)) {

        case ListView:

            if ($clear)
                wb_send_message($ctrl, LVM_DELETEALLITEMS, 0, 0);

            $last = -1;

            // For each row

            for ($i = 0; $i < count($items); $i++) {
                if (!is_scalar($items[$i]))
                    $last = wb_create_listview_item(
                        $ctrl, -1, -1, (string)$items[$i][0]);
                else
                    $last = wb_create_listview_item(
                        $ctrl, -1, -1, (string)$items[$i]);
                wb_set_listview_item_text($ctrl, -1, 0, (string)$items[$i][0]);

                // For each column except the first

                for ($sub = 0; $sub < count($items[$i]) - 1; $sub++) {
                    if ($param) {
                        $result = call_user_func($param,    // Callback function
                            $items[$i][$sub + 1],            // Item value
                            $i,                                // Row
                            $sub                            // Column
                        );
                        wb_set_listview_item_text($ctrl, $last, $sub + 1, $result);
                    } else
                        wb_set_listview_item_text($ctrl, $last, $sub + 1, (string)$items[$i][$sub + 1]);
                }
            }
            return $last;
            break;

        case TreeView:

            if ($clear)
                $handle = wb_delete_items($ctrl); // Empty the treeview

            if (!$items)
                break;
            $ret = array();
            for ($i = 0; $i < count($items); $i++) {
                $ret[] = wb_create_treeview_item($ctrl,
                    (string)$items[$i][0],                        // Name
                    isset($items[$i][1]) ? $items[$i][1] : 0,        // Value
                    isset($items[$i][2]) ? $items[$i][2] : 0,        // Where
                    isset($items[$i][3]) ? $items[$i][3] : -1,    // ImageIndex
                    isset($items[$i][4]) ? $items[$i][4] : -1,    // SelectedImageIndex
                    isset($items[$i][5]) ? $items[$i][5] : 0        // InsertionType
                );
            }
            return (count($ret) > 1 ? $ret : $ret[0]);
            break;

        case StatusBar:
            wb_create_statusbar_items($ctrl, $items, $clear, $param);
            foreach ($items as $item) {
                _set_text($ctrl, $item[0], key($item));
            }
            return true;

        default:

            if (is_array($items)) {
                foreach ($items as $item)
                    wb_create_item($ctrl, $item);
                return true;
            } else
                return wb_create_item($ctrl, $items);
            break;
    }
}

/**
 * Opens the standard Open dialog box.
 * 
 * This function is a wrapper around wb_sys_dlg_open that provides additional
 * convenience features, such as automatic filter creation.
 *
 * @param int|null $parent The handle of the parent window or null
 * @param string|null $title The dialog title or null for default
 * @param mixed|null $filter The file filter (string or array) or null for default
 * @param string|null $path The initial directory path or null for current directory
 * @param string|null $filename The default filename or null
 * @param int|null $flags Dialog flags or null for default
 * @return mixed The selected filename(s) or false if canceled
 */
function _sys_dlg_open($parent = null, $title = null, $filter = null, $path = null, $filename = null, $flags = null)
{
    $filter = _make_file_filter($filter ? $filter : $filename);
    return wb_sys_dlg_open($parent, $title, $filter, $path, $flags);
}

/**
 * Opens the standard Save As dialog box.
 * 
 * This function is a wrapper around wb_sys_dlg_save that provides additional
 * convenience features, such as automatic filter creation.
 *
 * @param int|null $parent The handle of the parent window or null
 * @param string|null $title The dialog title or null for default
 * @param mixed|null $filter The file filter (string or array) or null for default
 * @param string|null $path The initial directory path or null for current directory
 * @param string|null $filename The default filename or null
 * @param string|null $defext The default file extension or null
 * @return mixed The selected filename or false if canceled
 */
function _sys_dlg_save($parent = null, $title = null, $filter = null, $path = null, $filename = null, $defext = null)
{
    $filter = _make_file_filter($filter ? $filter : $filename);

    return wb_sys_dlg_save($parent, $title, $filter, $path, $filename, $defext);
}

//----------------------------------------- AUXILIARY FUNCTIONS FOR INTERNAL USE

/**
 * Creates a file filter for Open/Save dialog boxes based on an array.
 * 
 * This function converts a filter specification (either a string or an array)
 * into the format required by Windows file dialog functions.
 * 
 * When using an array, each element should be an array with two elements:
 * - The first element is the description (e.g., "Text Files")
 * - The second element is the filter pattern (e.g., "*.txt")
 *
 * @param mixed $filter The filter specification (string or array)
 * @return string The formatted filter string with null terminators
 */
function _make_file_filter($filter)
{
    if (!$filter)
        return "All Files (*.*)\0*.*\0\0";

    if (is_array($filter)) {
        $result = "";
        foreach ($filter as $line)
            $result .= "$line[0] ($line[1])\0$line[1]\0";
        $result .= "\0";
        return $result;
    } else
        return $filter;
}

//-------------------------------------------------------------------------- END
