<?php

/*******************************************************************************
 WINBINDER - The native Windows binding for PHP

 Copyright © Hypervisual - see LICENSE.TXT for details
 Author: Rubem Pechansky (http://winbinder.org/contact.php)

 Windows functions
*******************************************************************************/

// so this file will not be necessary in the future

//-------------------------------------------------------------------- CONSTANTS

// Windows constants with existence checks
if (!defined('BM_SETCHECK')) {
    /**
     * Button control message: Set check state
     * @const int
     * @link https://docs.microsoft.com/en-us/windows/win32/controls/bm-setcheck
     */
    define('BM_SETCHECK', 241);
}

if (!defined('LVM_FIRST')) {
    /** @const int Base value for ListView messages */
    define('LVM_FIRST', 0x1000);
}

if (!defined('LVM_DELETEALLITEMS')) {
    /**
     * ListView message: Delete all items
     * @const int
     * @link https://docs.microsoft.com/en-us/windows/win32/controls/lvm-deleteallitems
     */
    define('LVM_DELETEALLITEMS', (LVM_FIRST + 9));
}

// ... (other constants with similar documentation)

if (!defined('CB_FINDSTRINGEXACT')) {
    /**
     * ComboBox message: Find exact string match
     * @const int
     * @link https://docs.microsoft.com/en-us/windows/win32/controls/cb-findstringexact
     */
    define('CB_FINDSTRINGEXACT', 344);
}

//------------------------------------------------------------- WINDOW FUNCTIONS

if (!function_exists('wb_create_control')) {
    /**
     * Create a WinBinder GUI control
     *
     * @param mixed $parent Parent window/control handle
     * @param string $class Control class (Button, ListBox, etc.)
     * @param string $caption Initial control text
     * @param int $xpos X position in pixels
     * @param int $ypos Y position in pixels
     * @param int $width Control width
     * @param int $height Control height
     * @param mixed $id Control identifier
     * @param int $style Combination of style flags
     * @param mixed $lparam Additional creation data
     * @param int $ntab Tab order index
     * @return mixed Handle to created control
     * @throws RuntimeException If control creation fails
     */
    function wb_create_control($parent, $class, $caption = "", $xpos = 0, $ypos = 0, $width = 0, $height = 0, $id = null, $style = 0, $lparam = null, $ntab = 0)
    {
        switch ($class) {
            case 'Accel':
                return wbtemp_set_accel_table($parent, $caption);
            case 'ToolBar':
                return wbtemp_create_toolbar($parent, $caption, $width, $height, $lparam);
            case 'Menu':
                return wbtemp_create_menu($parent, $caption);
            case 'HyperLink':
                return wbtemp_create_control($parent, $class, $caption, $xpos, $ypos, $width, $height, $id, $style, is_null($lparam) ? NOCOLOR : $lparam, $ntab);
            case 'ComboBox':
            case 'ListBox':
            case 'ListView':
                $ctrl = wbtemp_create_control($parent, $class, $caption, $xpos, $ypos, $width, $height, $id, $style, $lparam, $ntab);
                if (is_array($caption)) {
                    wb_set_text($ctrl, $caption[0]);
                }
                return $ctrl;
            case 'TreeView':
                $ctrl = wbtemp_create_control($parent, $class, $caption, $xpos, $ypos, $width, $height, $id, $style, $lparam, $ntab);
                if (is_array($caption)) {
                    wb_set_text($ctrl, $caption[0]);
                }
                return $ctrl;
            case 'Gauge':
            case 'Slider':
            case 'ScrollBar':
                $ctrl = wbtemp_create_control($parent, $class, $caption, $xpos, $ypos, $width, $height, $id, $style, $lparam, $ntab);
                if ($lparam) {
                    wb_set_value($ctrl, $lparam);
                }
                return $ctrl;
            default:
                return wbtemp_create_control($parent, $class, $caption, $xpos, $ypos, $width, $height, $id, $style, $lparam, $ntab);
        }
    }
}

if (!function_exists('wb_set_value')) {
    /**
     * Set the value of a control or control item
     *
     * @param mixed $ctrl Control handle
     * @param mixed $value Value to set
     * @param mixed $item Item index (optional)
     * @return void
     */
    function wb_set_value($ctrl, $value, $item = null)
    {
        if (!$ctrl) {
            return null;
        }

        $class = wb_get_class($ctrl);
        switch ($class) {
            case 'ListView':
                if ($value === null) {
                    break;
                } elseif (is_string($value) && strstr($value, ",")) {
                    $values = explode(",", $value);
                } elseif (!is_array($value)) {
                    $values = array($value);
                } else {
                    $values = $value;
                }
                foreach ($values as $index) {
                    wbtemp_set_listview_item_checked($ctrl, $index, 1);
                }
                break;
            case 'TreeView':
                if ($item === null) {
                    $item = wb_get_selected($ctrl);
                }
                return wbtemp_set_treeview_item_value($ctrl, $item, $value);
            default:
                if ($value !== null) {
                    return wbtemp_set_value($ctrl, $value, $item);
                }
        }
    }
}

if (!function_exists('wb_get_text')) {
    /**
     * Get the text from a control, a control item, or a control sub-item
     *
     * @param mixed $ctrl Control handle
     * @param mixed $item Item index (optional)
     * @param mixed $subitem Sub-item index (optional)
     * @return string|array|null Retrieved text data
     */
    function wb_get_text($ctrl, $item = null, $subitem = null)
    {
        if (!$ctrl) return null;

        $class = wb_get_class($ctrl);

        switch ($class) {
            case 'ListView':
                if ($item !== null) {
                    $line = wbtemp_get_listview_text($ctrl, $item);
                    return $subitem === null ? $line : $line[$subitem];
                }

                $sel = wb_get_selected($ctrl);
                if ($sel === null) {
                    $items = array();
                    for ($i = 0;; $i++) {
                        $item = wbtemp_get_listview_text($ctrl, $i);
                        if (implode('', $item) === '') break;
                        $items[] = $item;
                    }
                    return $items ?: null;
                }
                return array_map(function ($row) use ($ctrl) {
                    return wbtemp_get_listview_text($ctrl, $row);
                }, $sel);

            case 'TreeView':
                return $item ? wbtemp_get_treeview_item_text($ctrl, $item)
                    : (($sel = wb_get_selected($ctrl)) ? wbtemp_get_text($ctrl) : null);

            case 'ComboBox':
            case 'ListBox':
                return wbtemp_get_text($ctrl, $item === null ? -1 : $item);

            default:
                return wbtemp_get_text($ctrl, $item);
        }
    }
}

if (!function_exists('wb_set_text')) {
    /**
     * Set the text of a control
     *
     * In a ListView, it creates columns: each element of the array text is a column.
     * In a tab control, it renames the tabs.
     * Sets the text of a control item.
     *
     * @param mixed $ctrl Control handle
     * @param mixed $text Text to set
     * @param mixed $item Item index (optional)
     * @param mixed $subitem Sub-item index (optional)
     * @return void
     */
    function wb_set_text($ctrl, $text, $item = null, $subitem = null)
    {
        if (!$ctrl) return null;

        switch (wb_get_class($ctrl)) {

            case 'ListView':

                if ($item !== null) {

                    if (!is_array($text) && $subitem !== null) {

                        wbtemp_set_listview_item_text($ctrl, $item, $subitem, $text);

                    } else {

                        for ($sub = 0; $sub < count($text); $sub++) {
                            if ($text) {
                                if (($text[$sub] !== null)) {
                                    wbtemp_set_listview_item_text($ctrl, $item, $sub, (string)$text[$sub]);
                                }
                            } else {
                                wbtemp_set_listview_item_text($ctrl, $item, $sub, "");
                            }
                        }
                    }

                } else {

                    if (!is_array($text))
                        $text = explode(",", $text);

                    wb_delete_items($ctrl, null);

                    if (!$item) {
                        wbtemp_clear_listview_columns($ctrl);

                        for ($i = 0; $i < count($text); $i++) {
                            if (is_array($text[$i]))
                                wbtemp_create_listview_column($ctrl, $i,
                                    (string)$text[$i][0],
                                    isset($text[$i][1]) ? (int)$text[$i][1] : -1,
                                    isset($text[$i][2]) ? (int)$text[$i][2] : WBC_LEFT
                                );
                            else
                                wbtemp_create_listview_column($ctrl, $i,
                                    (string)$text[$i], -1, 0);
                        }
                    }
                }
                break;

            case 'ListBox':

                if (!$text) {
                    wb_delete_items($ctrl);
                } elseif (is_string($text)) {
                    if (strchr($text, "\r") || strchr($text, "\n")) {
                        $text = preg_split("/[\r\n,]/", $text);
                        wb_delete_items($ctrl);
                        foreach ($text as $str)
                            wbtemp_create_item($ctrl, (string)$str);
                    } else {
                        $index = wb_send_message($ctrl, LB_FINDSTRINGEXACT, -1, wb_get_address($text));
                        wb_send_message($ctrl, LB_SETCURSEL, $index, 0);
                    }
                } elseif (is_array($text)) {
                    wb_delete_items($ctrl);
                    foreach ($text as $str)
                        wbtemp_create_item($ctrl, (string)$str);
                }
                return;

            case 'ComboBox':

                if (!$text)
                    wb_delete_items($ctrl);
                elseif (is_string($text)) {
                    if (strchr($text, "\r") || strchr($text, "\n")) {
                        $text = preg_split("/[\r\n,]/", $text);
                        wb_delete_items($ctrl);
                        foreach ($text as $str)
                            wbtemp_create_item($ctrl, (string)$str);
                    } else {
                        $index = wb_send_message($ctrl, CB_FINDSTRINGEXACT, -1, wb_get_address($text));
                        wb_send_message($ctrl, CB_SETCURSEL, $index, 0);
                        if ($index == -1)
                            wb_send_message($ctrl, WM_SETTEXT, 0, wb_get_address($text));
                    }
                } elseif (is_array($text)) {
                    wb_delete_items($ctrl);
                    foreach ($text as $str)
                        wbtemp_create_item($ctrl, (string)$str);
                }
                return;

            case 'TreeView':

                if ($item)
                    return wbtemp_set_treeview_item_text($ctrl, $item, $text);
                else
                    return wb_create_items($ctrl, $text, true);

            default:
                if (is_array($text))
                    return wbtemp_set_text($ctrl, $text, $item);
                else
                    return wbtemp_set_text($ctrl, (string)$text, $item);
        }
    }
}

if (!function_exists('wb_set_selected')) {
    /**
     * Selects one or more items
     *
     * Compare with wb_set_value() which checks items instead.
     *
     * @param mixed $ctrl Control handle
     * @param mixed $selitems Items to select
     * @param bool $selected Selection state
     * @return bool Success status
     */
    function wb_set_selected($ctrl, $selitems, $selected = true)
    {
        switch (wb_get_class($ctrl)) {

            case 'ComboBox':
                wb_send_message($ctrl, CB_SETCURSEL, (int)$selitems, 0);
                break;

            case 'ListBox':
                wb_send_message($ctrl, LB_SETCURSEL, (int)$selitems, 0);
                break;

            case 'ListView':

                if (is_null($selitems)) {
                    return wbtemp_select_all_listview_items($ctrl, false);
                } elseif (is_array($selitems)) {
                    foreach ($selitems as $item)
                        wbtemp_select_listview_item($ctrl, $item, $selected);
                    return true;
                } else
                    return wbtemp_select_listview_item($ctrl, $selitems, $selected);
                break;

            case 'Menu':
                return wbtemp_set_menu_item_checked($ctrl, $selitems, $selected);

            case 'TabControl':
                wbtemp_select_tab($ctrl, (int)$selitems);
                break;

            case 'TreeView':
                wbtemp_set_treeview_item_selected($ctrl, $selitems);
                break;

            default:
                return false;
        }
        return true;
    }
}

if (!function_exists('wb_create_items')) {
    /**
     * Creates one or more items in a control
     *
     * @param mixed $ctrl Control handle
     * @param array $items Items to create
     * @param bool $clear Clear existing items
     * @param callable|null $param Callback function for item processing
     * @return mixed Last created item handle
     */
    function wb_create_items($ctrl, $items, $clear = false, $param = null)
    {
        switch (wb_get_class($ctrl)) {

            case 'ListView':

                if ($clear)
                    wb_send_message($ctrl, LVM_DELETEALLITEMS, 0, 0);

                $last = -1;

                for ($i = 0; $i < count($items); $i++) {
                    if (!is_scalar($items[$i]))
                        $last = wbtemp_create_listview_item(
                            $ctrl, -1, -1, (string)$items[$i][0]);
                    else
                        $last = wbtemp_create_listview_item(
                            $ctrl, -1, -1, (string)$items[$i]);
                    wbtemp_set_listview_item_text($ctrl, -1, 0, (string)$items[$i][0]);

                    for ($sub = 0; $sub < count($items[$i]) - 1; $sub++) {
                        if ($param) {
                            $result = call_user_func($param, $items[$i][$sub + 1], $i, $sub);
                            wbtemp_set_listview_item_text($ctrl, $last, $sub + 1, $result);
                        } else
                            wbtemp_set_listview_item_text($ctrl, $last, $sub + 1, (string)$items[$i][$sub + 1]);
                    }
                }
                return $last;
                break;

            case 'TreeView':

                if ($clear)
                    $handle = wb_delete_items($ctrl);

                if (!$items)
                    break;
                $ret = array();
                for ($i = 0; $i < count($items); $i++) {
                    $ret[] = wbtemp_create_treeview_item($ctrl,
                        (string)$items[$i][0],
                        isset($items[$i][1]) ? $items[$i][1] : 0,
                        isset($items[$i][2]) ? $items[$i][2] : 0,
                        isset($items[$i][3]) ? $items[$i][3] : -1,
                        isset($items[$i][4]) ? $items[$i][4] : -1,
                        isset($items[$i][5]) ? $items[$i][5] : 0
                    );
                }
                return (count($ret) > 1 ? $ret : $ret[0]);
                break;

            case 'StatusBar':
                wbtemp_create_statusbar_items($ctrl, $items, $clear, $param);
                return true;

            default:

                if (is_array($items)) {
                    foreach ($items as $item)
                        wbtemp_create_item($ctrl, $item);
                    return true;
                } else
                    return wbtemp_create_item($ctrl, $items);
                break;
        }
    }
}

if (!function_exists('wb_sys_dlg_open')) {
    /**
     * Opens the standard Open dialog box
     *
     * @param mixed $parent Parent window handle
     * @param string|null $title Dialog title
     * @param string|null $filter File filter
     * @param string|null $path Initial directory path
     * @param string|null $filename Default filename
     * @return string|bool Selected file path or false
     */
    function wb_sys_dlg_open($parent = null, $title = null, $filter = null, $path = null, $filename = null)
    {
        $filter = _make_file_filter($filter ? $filter : $filename);
        return wbtemp_sys_dlg_open($parent, $title, $filter, $path);
    }
}

if (!function_exists('wb_sys_dlg_save')) {
    /**
     * Opens the standard Save dialog box
     *
     * @param mixed $parent Parent window handle
     * @param string|null $title Dialog title
     * @param string|null $filter File filter
     * @param string|null $path Initial directory path
     * @param string|null $filename Default filename
     * @param string|null $defext Default file extension
     * @return string|bool Selected file path or false
     */
    function wb_sys_dlg_save($parent = null, $title = null, $filter = null, $path = null, $filename = null, $defext = null)
    {
        $filter = _make_file_filter($filter ? $filter : $filename);
        return wbtemp_sys_dlg_save($parent, $title, $filter, $path, $filename, $defext);
    }
}

//----------------------------------------- AUXILIARY FUNCTIONS FOR INTERNAL USE

/**
 * Creates a file filter for Open/Save dialog boxes based on an array
 *
 * @param string|array $filter Filter specification
 * @return string Formatted filter string
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
