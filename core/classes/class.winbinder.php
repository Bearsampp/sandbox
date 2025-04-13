<?php
/*
 *
 *  * Copyright (c) 2022-2025 Bearsampp
 *  * License: GNU General Public License version 3 or later; see LICENSE.txt
 *  * Website: https://bearsampp.com
 *  * Github: https://github.com/Bearsampp
 *
 */

/**
 * Class WinBinder
 *
 * This class provides an interface to the WinBinder library, allowing for the creation and management
 * of Windows GUI elements in PHP. It includes methods for creating windows, controls, handling events,
 * and executing system commands.
 */
class WinBinder
{
    // Constants for control IDs and objects
    const CTRL_ID = 0;
    const CTRL_OBJ = 1;

    // Constants for progress bar increment and new line
    const INCR_PROGRESS_BAR = '++';
    const NEW_LINE = '@nl@';

    // Define WinBinder constants if not already defined
    // This resolves the "undeclared constants" TODO
    const BOX_INFO = 0;       // WBC_INFO
    const BOX_OK = 1;         // WBC_OK
    const BOX_OKCANCEL = 2;   // WBC_OKCANCEL
    const BOX_QUESTION = 3;   // WBC_QUESTION
    const BOX_ERROR = 4;      // WBC_STOP
    const BOX_WARNING = 5;    // WBC_WARNING
    const BOX_YESNO = 6;      // WBC_YESNO
    const BOX_YESNOCANCEL = 7; // WBC_YESNOCANCEL

    // Constants for cursor types
    const CURSOR_ARROW = 'arrow';
    const CURSOR_CROSS = 'cross';
    const CURSOR_FINGER = 'finger';
    const CURSOR_FORBIDDEN = 'forbidden';
    const CURSOR_HELP = 'help';
    const CURSOR_IBEAM = 'ibeam';
    const CURSOR_NONE = null;
    const CURSOR_SIZEALL = 'sizeall';
    const CURSOR_SIZENESW = 'sizenesw';
    const CURSOR_SIZENS = 'sizens';
    const CURSOR_SIZENWSE = 'sizenwse';
    const CURSOR_SIZEWE = 'sizewe';
    const CURSOR_UPARROW = 'uparrow';
    const CURSOR_WAIT = 'wait';
    const CURSOR_WAITARROW = 'waitarrow';

    // Constants for system information types
    const SYSINFO_SCREENAREA = 'screenarea';
    const SYSINFO_WORKAREA = 'workarea';

    // Property declarations to avoid dynamic property deprecation warnings in PHP 8.2
    public $callback = [];
    public $gauge = [];
    private $defaultTitle = '';
    private $countCtrls = 0;

    /**
     * WinBinder constructor.
     *
     * Initializes the WinBinder class, sets the default window title, and resets control counters.
     */
    public function __construct()
    {
        global $bearsamppCore;
        Util::logInitClass($this);

        $this->defaultTitle = APP_TITLE . ' ' . $bearsamppCore->getAppVersion();
        $this->reset();
    }

    /**
     * Resets the control counter and callback array.
     */
    public function reset()
    {
        $this->countCtrls = 1000;
        $this->callback   = array();
    }

    /**
     * Creates a new application window.
     *
     * @param   string  $caption  The window caption.
     * @param   int     $width    The width of the window.
     * @param   int     $height   The height of the window.
     * @param   mixed   $style    The window style.
     * @param   mixed   $params   Additional parameters for the window.
     *
     * @return mixed The created window object.
     */
    public function createAppWindow($caption, $width, $height, $style = null, $params = null)
    {
        return $this->createWindow(null, AppWindow, $caption, WBC_CENTER, WBC_CENTER, $width, $height, $style, $params);
    }

    /**
     * Creates a new window.
     *
     * @param   mixed   $parent   The parent window.
     * @param   mixed   $wintype  The window type.
     * @param   string  $caption  The window caption.
     * @param   int     $xpos     The x-coordinate of the window.
     * @param   int     $ypos     The y-coordinate of the window.
     * @param   int     $width    The width of the window.
     * @param   int     $height   The height of the window.
     * @param   mixed   $style    The window style.
     * @param   mixed   $params   Additional parameters for the window.
     *
     * @return mixed The created window object.
     */
    public function createWindow($parent, $wintype, $caption, $xpos, $ypos, $width, $height, $style = null, $params = null)
    {
        // Fix for PHP 8.2: Convert null to 0 for parent parameter
        $parent = $parent === null ? 0 : $parent;

        if (empty($caption)) {
            $caption = $this->defaultTitle;
        }

        return $this->callWinBinder('wb_create_window', array($parent, $wintype, $caption, $xpos, $ypos, $width, $height, $style, $params));
    }

    /**
     * Calls a WinBinder function with the given parameters.
     *
     * @param   string  $function     The WinBinder function to call.
     * @param   array   $params       The parameters for the function.
     * @param   bool    $ignoreError  Whether to ignore errors.
     *
     * @return mixed The result of the function call.
     */
    private function callWinBinder($function, $params = array(), $ignoreError = false)
    {
        if (!function_exists($function)) {
            Util::logError('WinBinder function does not exist: ' . $function);
            return false;
        }

        Util::logTrace('Using @Call_user_func_array: ' . $function . ' with params: ' . print_r($params, true));

        try {
            return call_user_func_array($function, $params);
        } catch (Exception $e) {
            if (!$ignoreError) {
                Util::logError('Error calling WinBinder function: ' . $function . ' - ' . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Destroys a window.
     *
     * @param   mixed  $window  The window to destroy.
     *
     * @return mixed The result of the destroy operation.
     */
    public function destroyWindow($window)
    {
        // Fix for PHP 8.2: Handle null window parameter
        if ($window === null) {
            return false;
        }

        // Get window title for logging
        $windowTitle = $this->getWindowTitle($window);

        // Get process ID if possible
        $pid = null;
        if (function_exists('wb_get_process_id')) {
            $pid = wb_get_process_id($window);
        }

        if ($pid) {
            Util::logTrace('Closing process with PID: ' . $pid . ' - using taskkill');
            $this->exec('taskkill', '/PID ' . $pid . ' /F', true);
        }
        // Fallback to window title if PID retrieval fails
        elseif (!empty($windowTitle)) {
            Util::logTrace('Closing window with title: ' . $windowTitle . ' - using taskkill');
            $this->exec('taskkill', '/FI "WINDOWTITLE eq ' . $windowTitle . '" /F', true);

            // Try to kill process directly using Winbinder's PID method
            $currentPid = Win32Ps::getCurrentPid();
            if (!empty($currentPid)) {
                Util::logTrace('Force-killing PID: ' . $currentPid . ' for window: ' . $window);
                $this->exec('taskkill', '/PID ' . $currentPid . ' /T /F', true);
            }

            // Final sanity check
            if ($this->windowIsValid($window)) {
                Util::logTrace('Closing window with wb_destroy_window: ' . $window);
                $this->callWinBinder('wb_destroy_window', array($window), true);
            }

            // Reset internal state to prevent memory leaks
            $this->reset();
        }

        return $this->callWinBinder('wb_destroy_window', array($window));
    }

    /**
     * Checks if a window is valid.
     *
     * @param   mixed  $window  The window to check.
     *
     * @return bool Whether the window is valid.
     */
    public function windowIsValid($window)
    {
        // Fix for PHP 8.2: Handle null window parameter
        if ($window === null) {
            return false;
        }

        return $this->callWinBinder('wb_get_visible', array($window)) !== false;
    }

    /**
     * Gets the title of a window.
     *
     * @param   mixed  $window  The window to get the title of.
     *
     * @return mixed The title of the window.
     */
    public function getWindowTitle($window)
    {
        // Fix for PHP 8.2: Handle null window parameter
        if ($window === null) {
            return '';
        }

        return $this->callWinBinder('wb_get_text', array($window));
    }

    /**
     * Executes a command.
     *
     * @param   string  $command    The command to execute.
     * @param   string  $params     The parameters for the command.
     * @param   bool    $waitForIt  Whether to wait for the command to complete.
     *
     * @return mixed The result of the execution.
     */
    public function exec($command, $params = '', $waitForIt = false)
    {
        return $this->callWinBinder('wb_exec', array($command, $params, $waitForIt));
    }

    /**
     * Creates a font for use in WinBinder controls.
     *
     * @param   string    $fontName  The name of the font.
     * @param   int|null  $size      The size of the font.
     * @param   int|null  $color     The color of the font.
     * @param   mixed     $style     The style of the font.
     *
     * @return mixed The created font object.
     */
    public function createFont($fontName, $size = null, $color = null, $style = null)
    {
        return $this->callWinBinder('wb_create_font', array($fontName, $size, $color, $style));
    }

    /**
     * Waits for an event on a WinBinder object.
     *
     * @param   mixed  $wbobject  The WinBinder object to wait on.
     *
     * @return mixed The result of the wait operation.
     */
    public function wait($wbobject = null)
    {
        // Fix for PHP 8.2: Handle null parameter
        $wbobject = $wbobject === null ? 0 : $wbobject;
        return $this->callWinBinder('wb_wait', array($wbobject), true);
    }

    /**
     * Destroys a timer for a WinBinder object.
     *
     * @param   mixed  $wbobject     The WinBinder object to destroy the timer for.
     * @param   mixed  $timerobject  The timer object to destroy.
     *
     * @return mixed The result of the destroy operation.
     */
    public function destroyTimer($wbobject, $timerobject)
    {
        // Fix for PHP 8.2: Handle null parameters
        if ($wbobject === null || $timerobject === null) {
            return false;
        }

        return $this->callWinBinder('wb_destroy_timer', array($wbobject, $timerobject));
    }

    /**
     * Refreshes a WinBinder object.
     *
     * @param   mixed  $wbobject  The WinBinder object to refresh.
     *
     * @return mixed The result of the refresh operation.
     */
    public function refresh($wbobject)
    {
        // Fix for PHP 8.2: Handle null parameter
        if ($wbobject === null) {
            return false;
        }

        return $this->callWinBinder('wb_refresh', array($wbobject, true));
    }

    /**
     * Retrieves system information.
     *
     * @param   string  $info  The type of system information to retrieve.
     *
     * @return mixed The retrieved system information.
     */
    public function getSystemInfo($info)
    {
        return $this->callWinBinder('wb_get_system_info', array($info));
    }

    /**
     * Draws an image on a WinBinder object.
     *
     * @param   mixed   $wbobject  The WinBinder object to draw on.
     * @param   string  $path      The path to the image file.
     * @param   int     $xPos      The x-coordinate of the image.
     * @param   int     $yPos      The y-coordinate of the image.
     * @param   int     $width     The width of the image.
     * @param   int     $height    The height of the image.
     *
     * @return mixed The result of the draw operation.
     */
    public function drawImage($wbobject, $path, $xPos = 0, $yPos = 0, $width = 0, $height = 0)
    {
        // Fix for PHP 8.2: Handle null parameter
        if ($wbobject === null) {
            return false;
        }

        $image = $this->callWinBinder('wb_load_image', array($path));

        return $this->callWinBinder('wb_draw_image', array($wbobject, $image, $xPos, $yPos, $width, $height));
    }

    /**
     * Sets the style for a WinBinder object.
     *
     * @param   mixed  $wbobject  The WinBinder object to set the style for.
     * @param   mixed  $style     The style to set.
     *
     * @return mixed The result of the set style operation.
     */
    public function setStyle($wbobject, $style)
    {
        // Fix for PHP 8.2: Handle null parameter
        if ($wbobject === null) {
            return false;
        }

        return $this->callWinBinder('wb_set_style', array($wbobject, $style));
    }

    /**
     * Opens a system dialog to select a path.
     *
     * @param   mixed        $parent  The parent window for the dialog.
     * @param   string       $title   The title of the dialog.
     * @param   string|null  $path    The initial path for the dialog.
     *
     * @return mixed The selected path.
     */
    public function sysDlgPath($parent, $title, $path = null)
    {
        // Fix for PHP 8.2: Handle null parent parameter
        $parent = $parent === null ? 0 : $parent;

        return $this->callWinBinder('wb_sys_dlg_path', array($parent, $title, $path));
    }

    /**
     * Opens a system dialog to open a file.
     *
     * @param   mixed        $parent  The parent window for the dialog.
     * @param   string       $title   The title of the dialog.
     * @param   string|null  $filter  The file filter for the dialog.
     * @param   string|null  $path    The initial path for the dialog.
     *
     * @return mixed The selected file path.
     */
    public function sysDlgOpen($parent, $title, $filter = null, $path = null)
    {
        // Fix for PHP 8.2: Handle null parent parameter
        $parent = $parent === null ? 0 : $parent;

        return $this->callWinBinder('wb_sys_dlg_open', array($parent, $title, $filter, $path));
    }

    /**
     * Sets the value for a WinBinder object.
     *
     * @param   mixed  $wbobject  The WinBinder object to set the value for.
     * @param   mixed  $content   The value to set.
     *
     * @return mixed The result of the set value operation.
     */
    public function setValue($wbobject, $content)
    {
        // Fix for PHP 8.2: Handle null parameter
        if ($wbobject === null) {
            return false;
        }

        return $this->callWinBinder('wb_set_value', array($wbobject, $content));
    }

    /**
     * Resets the value of a progress bar to zero.
     *
     * @param   array  $progressBar  The progress bar control.
     */
    public function resetProgressBar($progressBar)
    {
        $this->setProgressBarValue($progressBar, 0);
    }

    /**
     * Sets the maximum value of a progress bar.
     *
     * @param   array  $progressBar  The progress bar control.
     * @param   int    $max          The maximum value to set.
     */
    public function setProgressBarMax($progressBar, $max)
    {
        // Fix for PHP 8.2: Handle null or invalid progressBar
        if (!is_array($progressBar) || !isset($progressBar[self::CTRL_OBJ])) {
            return;
        }

        $this->setRange($progressBar[self::CTRL_OBJ], 0, $max);
    }

    /**
     * Sets the range for a WinBinder object.
     *
     * @param   mixed  $wbobject  The WinBinder object to set the range for.
     * @param   int    $min       The minimum value of the range.
     * @param   int    $max       The maximum value of the range.
     *
     * @return mixed The result of the set range operation.
     */
    public function setRange($wbobject, $min, $max)
    {
        // Fix for PHP 8.2: Handle null parameter
        if ($wbobject === null) {
            return false;
        }

        return $this->callWinBinder('wb_set_range', array($wbobject, $min, $max));
    }

    /**
     * Displays an informational message box.
     *
     * @param   string       $message  The message to display.
     * @param   string|null  $title    The title of the message box.
     *
     * @return mixed The result of the message box operation.
     */
    public function messageBoxInfo($message, $title = null)
    {
        return $this->messageBox($message, self::BOX_INFO, $title);
    }

    /**
     * Displays a message box.
     *
     * @param   string       $message  The message to display.
     * @param   int          $type     The type of message box.
     * @param   string|null  $title    The title of the message box.
     *
     * @return mixed The result of the message box operation.
     */
    public function messageBox($message, $type = 0, $title = null)
    {
        if (empty($title)) {
            $title = $this->defaultTitle;
        }

        // Use 0 instead of null for window handle (PHP 8.2 compatibility)
        return $this->callWinBinder('wb_message_box', array(0, $message, $title, $type));
    }

    /**
     * Sets the handler for a WinBinder object.
     *
     * @param   mixed   $wbobject  The WinBinder object to set the handler for.
     * @param   object  $object    The object containing the handler method.
     * @param   string  $method    The name of the handler method.
     *
     * @return mixed The result of the set handler operation.
     */
    public function setHandler($wbobject, $object, $method)
    {
        // Fix for PHP 8.2: Handle null parameter
        if ($wbobject === null) {
            return false;
        }

        $this->callback[$wbobject] = array($object, $method);

        return $this->callWinBinder('wb_set_handler', array($wbobject, '__winbinderEventHandler'));
    }

    /**
     * Starts the main event loop for WinBinder.
     *
     * @return mixed The result of the main loop.
     */
    public function mainLoop()
    {
        return $this->callWinBinder('wb_main_loop');
    }

    /**
     * Sets the enabled state of a WinBinder object.
     *
     * @param   mixed  $wbobject  The WinBinder object to set the enabled state for.
     * @param   bool   $enabled   Whether the object should be enabled.
     *
     * @return mixed The result of the set enabled operation.
     */
    public function setEnabled($wbobject, $enabled)
    {
        // Fix for PHP 8.2: Handle null parameter
        if ($wbobject === null) {
            return false;
        }

        return $this->callWinBinder('wb_set_enabled', array($wbobject, $enabled));
    }
}

/**
 * Event handler for WinBinder events.
 *
 * This function is called by WinBinder when an event occurs. It retrieves the callback
 * associated with the window and executes it. If a timer is associated with the callback,
 * the timer is destroyed before executing the callback.
 *
 * @param   mixed  $window  The window object where the event occurred.
 * @param   int    $id      The ID of the event.
 * @param   mixed  $ctrl    The control that triggered the event.
 * @param   mixed  $param1  The first parameter of the event.
 * @param   mixed  $param2  The second parameter of the event.
 */
function __winbinderEventHandler($window, $id, $ctrl, $param1, $param2)
{
    global $bearsamppWinbinder;

    // Fix for PHP 8.2: Handle null or invalid callback
    if (!isset($bearsamppWinbinder->callback[$window]) ||
        !is_array($bearsamppWinbinder->callback[$window])) {
        return;
    }

    if (isset($bearsamppWinbinder->callback[$window][2]) &&
        $bearsamppWinbinder->callback[$window][2] !== null) {
        $bearsamppWinbinder->destroyTimer($window, $bearsamppWinbinder->callback[$window][2][0]);
    }

    call_user_func_array(
        array($bearsamppWinbinder->callback[$window][0], $bearsamppWinbinder->callback[$window][1]),
        array($window, $id, $ctrl, $param1, $param2)
    );
}
