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
    // This resolves the "undeclared constants"
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

    public function createLabel($parent, $caption, $xPos, $yPos, $width = null, $height = null, $style = null, $params = null)
    {
        $caption = str_replace(self::NEW_LINE, PHP_EOL, $caption);
        $width = $width == null ? 120 : $width;
        $height = $height == null ? 25 : $height;
        return $this->createControl($parent, Label, $caption, $xPos, $yPos, $width, $height, $style, $params);
    }

    /**
     * Creates a hyperlink control.
     *
     * @param   mixed        $parent   The parent window.
     * @param   string       $caption  The hyperlink caption.
     * @param   int          $xPos     The x-coordinate of the hyperlink.
     * @param   int          $yPos     The y-coordinate of the hyperlink.
     * @param   int|null     $width    The width of the hyperlink.
     * @param   int|null     $height   The height of the hyperlink.
     * @param   mixed        $style    The hyperlink style.
     * @param   mixed        $params   Additional parameters for the hyperlink.
     *
     * @return array An array containing the hyperlink ID and object.
     */
    public function createHyperLink($parent, $caption, $xPos, $yPos, $width = null, $height = null, $style = null, $params = null)
    {
        // Fix for PHP 8.2: Convert null to empty string for caption
        $caption = $caption === null ? '' : $caption;
        $caption = str_replace(self::NEW_LINE, PHP_EOL, $caption);

        // Set default dimensions if not provided
        $width = $width == null ? 120 : $width;
        $height = $height == null ? 15 : $height;

        // Fix for PHP 8.2: Handle null parent parameter
        $parent = $parent === null ? 0 : $parent;

        // Create the hyperlink control
        $hyperLink = $this->createControl($parent, HyperLink, (string) $caption, $xPos, $yPos, $width, $height, $style, $params);

        // Set the cursor to finger pointer for better UX
        $this->setCursor($hyperLink[self::CTRL_OBJ], self::CURSOR_FINGER);

        return $hyperLink;
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
            $this->exec('taskkill', '/PID ' . $pid . ' /F', true, true);
        }
        // Fallback to window title if PID retrieval fails
        elseif (!empty($windowTitle)) {
            Util::logTrace('Closing window with title: ' . $windowTitle . ' - using taskkill');
            $this->exec('taskkill', '/FI "WINDOWTITLE eq ' . $windowTitle . '" /F', true, true);

            // Try to kill process directly using Winbinder's PID method
            $currentPid = Win32Ps::getCurrentPid();
            if (!empty($currentPid)) {
                Util::logTrace('Force-killing PID: ' . $currentPid . ' for window: ' . $window);
                $this->exec('taskkill', '/PID ' . $currentPid . ' /T /F', true, true);
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
     * Executes a system command.
     *
     * @param   string       $cmd         The command to execute.
     * @param   string|null  $params      The parameters to pass to the command.
     * @param   bool         $waitForIt   Whether to wait for the command to complete.
     * @param   bool         $hideWindow  Whether to hide the command window.
     *
     * @return mixed The result of the command execution.
     */
    public function exec($cmd, $params = null, $silent = false)
    {
        global $bearsamppCore;

        if ($silent) {
            $silent = '"' . $bearsamppCore->getScript(Core::SCRIPT_EXEC_SILENT) . '" "' . $cmd . '"';
            $cmd = 'wscript.exe';
            $params = !empty($params) ? $silent . ' "' . $params . '"' : $silent;
        }

        $this->writeLog('exec: ' . $cmd . ' ' . $params);
        return $this->callWinBinder('wb_exec', array($cmd, $params));
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
     * Creates a control in a window.
     *
     * @param   mixed   $parent   The parent window.
     * @param   mixed   $ctrltype The control type.
     * @param   string  $caption  The control caption.
     * @param   int     $xPos     The x-coordinate of the control.
     * @param   int     $yPos     The y-coordinate of the control.
     * @param   int     $width    The width of the control.
     * @param   int     $height   The height of the control.
     * @param   mixed   $style    The control style.
     * @param   mixed   $params   Additional parameters for the control.
     *
     * @return array An array containing the control ID and object.
     */
    public function createControl($parent, $ctrltype, $caption, $xPos, $yPos, $width, $height, $style = null, $params = null)
    {
        // Fix for PHP 8.2: Handle null parent parameter
        $parent = $parent === null ? 0 : $parent;

        $ctrlId = $this->countCtrls++;
        $ctrlObj = $this->callWinBinder('wb_create_control', array(
            $parent, $ctrltype, $caption, $xPos, $yPos, $width, $height, $ctrlId, $style, $params
        ));

        return array(self::CTRL_ID => $ctrlId, self::CTRL_OBJ => $ctrlObj);
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
     * Increments the progress bar value by 1.
     *
     * @param   array  $progressBar  The progress bar control.
     */
    public function incrProgressBar($progressBar)
    {
        $this->setProgressBarValue($progressBar, self::INCR_PROGRESS_BAR);
    }

    /**
     * Resets the progress bar value to 0.
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
        // PHP 8.2 compatibility: Add proper null and type checks
        if ($progressBar === null || !is_array($progressBar) || !isset($progressBar[self::CTRL_OBJ])) {
            return;
        }

        $this->setRange($progressBar[self::CTRL_OBJ], 0, $max);
    }

    /**
     * Sets the value of a progress bar.
     *
     * @param   array  $progressBar  The progress bar control.
     * @param   mixed  $value        The value to set, or '++' to increment.
     */
    public function setProgressBarValue($progressBar, $value)
    {
        // PHP 8.2 compatibility: Add proper null and type checks
        if ($progressBar === null || !is_array($progressBar)) {
            return;
        }

        if (isset($progressBar[self::CTRL_OBJ])) {
            $ctrl = $progressBar[self::CTRL_OBJ];

            if (strval($value) == self::INCR_PROGRESS_BAR) {
                if (!isset($this->gauge[$ctrl])) {
                    $this->gauge[$ctrl] = 0;
                }
                $value = $this->gauge[$ctrl] + 1;
            }

            if (is_numeric($value)) {
                $this->gauge[$ctrl] = $value;
                $this->setValue($ctrl, $value);
            }
        }
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
     * Creates a progress bar control.
     *
     * @param   mixed        $parent   The parent window.
     * @param   int          $max      The maximum value of the progress bar.
     * @param   int          $xPos     The x-coordinate of the progress bar.
     * @param   int          $yPos     The y-coordinate of the progress bar.
     * @param   int|null     $width    The width of the progress bar.
     * @param   int|null     $height   The height of the progress bar.
     * @param   mixed        $style    The progress bar style.
     * @param   mixed        $params   Additional parameters for the progress bar.
     *
     * @return array An array containing the progress bar ID and object.
     */
    public function createProgressBar($parent, $max, $xPos, $yPos, $width = null, $height = null, $style = null, $params = null)
    {
        global $bearsamppLang;

        // Set default dimensions if not provided
        $width = $width == null ? 200 : $width;
        $height = $height == null ? 15 : $height;

        // PHP 8.2 compatibility: ensure parameters are properly handled
        $max = (int)$max;

        // Fix for PHP 8.2: Handle null parent parameter
        $parent = $parent === null ? 0 : $parent;

        // Default caption if language service is available
        $caption = isset($bearsamppLang) ? $bearsamppLang->getValue('loading') : 'Loading...';

        $progressBar = $this->createControl($parent, Gauge, $caption, $xPos, $yPos, $width, $height, $style, $params);
        $this->setRange($progressBar[self::CTRL_OBJ], 0, $max);
        $this->gauge[$progressBar[self::CTRL_OBJ]] = 0;

        return $progressBar;
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
        $message = str_replace(self::NEW_LINE, PHP_EOL, $message);

        // PHP 8.2 compatibility: Ensure proper parameter handling
        $message = (string)$message;
        $type = (int)$type;

        // Pad message to display entire title for short messages
        if (strlen($message) < 64) {
            $message = str_pad($message, 64);
        }

        $windowTitle = $title === null ? $this->defaultTitle : $this->defaultTitle . ' - ' . $title;

        // Use 0 instead of null for window handle (PHP 8.2 compatibility)
        return $this->callWinBinder('wb_message_box', array(0, $message, $windowTitle, $type));
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
     * Displays a message box with OK button.
     *
     * @param   string       $message  The message to display.
     * @param   string|null  $title    The title of the message box.
     *
     * @return mixed The result of the message box operation.
     */
    public function messageBoxOk($message, $title = null)
    {
        return $this->messageBox($message, self::BOX_OK, $title);
    }

    /**
     * Displays a message box with OK and Cancel buttons.
     *
     * @param   string       $message  The message to display.
     * @param   string|null  $title    The title of the message box.
     *
     * @return mixed The result of the message box operation.
     */
    public function messageBoxOkCancel($message, $title = null)
    {
        return $this->messageBox($message, self::BOX_OKCANCEL, $title);
    }

    /**
     * Displays a question message box with Yes and No buttons.
     *
     * @param   string       $message  The message to display.
     * @param   string|null  $title    The title of the message box.
     *
     * @return mixed The result of the message box operation.
     */
    public function messageBoxYesNo($message, $title = null)
    {
        return $this->messageBox($message, self::BOX_YESNO, $title);
    }

    /**
     * Displays a question message box with Yes, No, and Cancel buttons.
     *
     * @param   string       $message  The message to display.
     * @param   string|null  $title    The title of the message box.
     *
     * @return mixed The result of the message box operation.
     */
    public function messageBoxYesNoCancel($message, $title = null)
    {
        return $this->messageBox($message, self::BOX_YESNOCANCEL, $title);
    }

    /**
     * Displays an error message box.
     *
     * @param   string       $message  The message to display.
     * @param   string|null  $title    The title of the message box.
     *
     * @return mixed The result of the message box operation.
     */
    public function messageBoxError($message, $title = null)
    {
        return $this->messageBox($message, self::BOX_ERROR, $title);
    }

    /**
     * Displays a warning message box.
     *
     * @param   string       $message  The message to display.
     * @param   string|null  $title    The title of the message box.
     *
     * @return mixed The result of the message box operation.
     */
    public function messageBoxWarning($message, $title = null)
    {
        return $this->messageBox($message, self::BOX_WARNING, $title);
    }

    /**
     * Displays a question message box.
     *
     * @param   string       $message  The message to display.
     * @param   string|null  $title    The title of the message box.
     *
     * @return mixed The result of the message box operation.
     */
    public function messageBoxQuestion($message, $title = null)
    {
        return $this->messageBox($message, self::BOX_QUESTION, $title);
    }

    /**
     * Sets the handler for a WinBinder object with optional timer.
     *
     * @param   mixed      $wbobject       The WinBinder object to set the handler for.
     * @param   object     $classCallback  The class containing the handler method.
     * @param   string     $methodCallback The name of the handler method.
     * @param   int|null   $launchTimer    The launch time for a timer, or null for no timer.
     *
     * @return mixed The result of the set handler operation.
     */
    public function setHandler($wbobject, $classCallback, $methodCallback, $launchTimer = null)
    {
        // Fix for PHP 8.2: Handle null parameters
        if ($wbobject === null) {
            return false;
        }

        if ($launchTimer !== null) {
            $launchTimer = $this->createTimer($wbobject, $launchTimer);
        }

        $this->callback[$wbobject] = array($classCallback, $methodCallback, $launchTimer);
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

    /**
     * Checks if a WinBinder object is enabled.
     *
     * @param   mixed  $wbobject  The WinBinder object to check.
     *
     * @return mixed Whether the WinBinder object is enabled.
     */
    public function isEnabled($wbobject)
    {
        // Fix for PHP 8.2: Handle null parameter
        if ($wbobject === null) {
            return false;
        }

        return $this->callWinBinder('wb_get_enabled', array($wbobject));
    }

    /**
     * Sets a WinBinder object to disabled.
     *
     * @param   mixed  $wbobject  The WinBinder object to disable.
     *
     * @return mixed The result of the disable operation.
     */
    public function setDisabled($wbobject)
    {
        return $this->setEnabled($wbobject, false);
    }

    /**
     * Sets the area for a WinBinder object.
     *
     * @param   mixed  $wbobject  The WinBinder object to set the area for.
     * @param   int    $width     The width of the area.
     * @param   int    $height    The height of the area.
     *
     * @return mixed The result of the set area operation.
     */
    public function setArea($wbobject, $width, $height)
    {
        // Fix for PHP 8.2: Handle null parameters
        if ($wbobject === null) {
            return false;
        }

        return $this->callWinBinder('wb_set_area', array($wbobject, WBC_TITLE, 0, 0, $width, $height));
    }

    /**
     * Gets the text of a WinBinder object.
     *
     * @param   mixed  $wbobject  The WinBinder object to get the text from.
     *
     * @return mixed The text of the WinBinder object.
     */
    public function getText($wbobject)
    {
        // Fix for PHP 8.2: Handle null parameter
        if ($wbobject === null) {
            return '';
        }

        return $this->callWinBinder('wb_get_text', array($wbobject));
    }

    /**
     * Sets the cursor for a WinBinder object.
     *
     * @param   mixed   $wbobject  The WinBinder object to set the cursor for.
     * @param   string  $cursor    The cursor type to set.
     *
     * @return mixed The result of the set cursor operation.
     */
    public function setCursor($wbobject, $cursor)
    {
        // Fix for PHP 8.2: Handle null parameter
        if ($wbobject === null) {
            return false;
        }

        return $this->callWinBinder('wb_set_cursor', array($wbobject, $cursor));
    }

    /**
     * Creates a timer for a WinBinder object.
     *
     * @param   mixed  $wbobject  The WinBinder object to create the timer for.
     * @param   int    $wait      The wait time for the timer in milliseconds.
     *
     * @return array An array containing the timer ID and object.
     */
    public function createTimer($wbobject, $wait = 1000)
    {
        // Fix for PHP 8.2: Properly handle null parameters
        if ($wbobject === null) {
            return array(self::CTRL_ID => 0, self::CTRL_OBJ => null);
        }

        $this->countCtrls++;
        return array(
            self::CTRL_ID => $this->countCtrls,
            self::CTRL_OBJ => $this->callWinBinder('wb_create_timer', array($wbobject, $this->countCtrls, $wait))
        );
    }

    /**
     * Finds a file.
     *
     * @param   string  $filename  The name of the file to find.
     *
     * @return mixed The path of the found file, or false if not found.
     */
    public function findFile($filename)
    {
        // Fix for PHP 8.2: Handle null parameter
        if ($filename === null) {
            return false;
        }

        $result = $this->callWinBinder('wb_find_file', array($filename));
        $this->writeLog('findFile ' . $filename . ': ' . $result);
        return $result != $filename ? $result : false;
    }

    /**
     * Creates a naked window (a window without borders or title bar).
     *
     * @param   string       $caption  The window caption.
     * @param   int          $width    The width of the window.
     * @param   int          $height   The height of the window.
     * @param   mixed        $style    The window style.
     * @param   mixed        $params   Additional parameters for the window.
     *
     * @return mixed The created window object.
     */
    public function createNakedWindow($caption, $width, $height, $style = null, $params = null)
    {
        // Fix for PHP 8.2: Properly handle null parameters
        $caption = $caption === null ? '' : $caption;

        $window = $this->createWindow(null, NakedWindow, $caption, WBC_CENTER, WBC_CENTER, $width, $height, $style, $params);
        $this->setArea($window, $width, $height);
        return $window;
    }

    /**
     * Writes a log message.
     *
     * @param   string  $log  The log message to write.
     */
    private function writeLog($log)
    {
        global $bearsamppCore;
        if (method_exists('Util', 'logDebug') && method_exists($bearsamppCore, 'getWinbinderLogFilePath')) {
            Util::logDebug($log, $bearsamppCore->getWinbinderLogFilePath());
        }
    }

    /**
     * Sets an image for a WinBinder object.
     *
     * @param   mixed   $wbobject  The WinBinder object to set the image for.
     * @param   string  $path      The path to the image file.
     *
     * @return mixed The result of the set image operation.
     */
    public function setImage($wbobject, $path)
    {
        // Fix for PHP 8.2: Handle null parameters
        if ($wbobject === null || $path === null) {
            return false;
        }

        return $this->callWinBinder('wb_set_image', array($wbobject, $path));
    }

    /**
     * Sets the maximum length for a WinBinder object.
     *
     * @param   mixed  $wbobject  The WinBinder object to set the maximum length for.
     * @param   int    $length    The maximum length to set.
     *
     * @return mixed The result of the set maximum length operation.
     */
    public function setMaxLength($wbobject, $length)
    {
        // Fix for PHP 8.2: Handle null parameters
        if ($wbobject === null) {
            return false;
        }

        return $this->callWinBinder('wb_send_message', array($wbobject, 0x00c5, $length, 0));
    }

    /**
     * Sets the text for a WinBinder object.
     *
     * @param   mixed   $wbobject  The WinBinder object to set the text for.
     * @param   string  $content   The text to set.
     *
     * @return mixed The result of the set text operation.
     */
    public function setText($wbobject, $content)
    {
        // Fix for PHP 8.2: Handle null parameters
        if ($wbobject === null) {
            return false;
        }

        $content = $content === null ? '' : $content;
        $content = str_replace(self::NEW_LINE, PHP_EOL, $content);

        return $this->callWinBinder('wb_set_text', array($wbobject, $content));
    }

    /**
     * Gets the value of a WinBinder object.
     *
     * @param   mixed  $wbobject  The WinBinder object to get the value from.
     *
     * @return mixed The value of the WinBinder object.
     */
    public function getValue($wbobject)
    {
        // Fix for PHP 8.2: Handle null parameter
        if ($wbobject === null) {
            return null;
        }

        return $this->callWinBinder('wb_get_value', array($wbobject));
    }

    /**
     * Draws text on a WinBinder object.
     *
     * @param   mixed        $parent   The parent window.
     * @param   string       $caption  The text to draw.
     * @param   int          $xPos     The x-coordinate of the text.
     * @param   int          $yPos     The y-coordinate of the text.
     * @param   int|null     $width    The width of the text area.
     * @param   int|null     $height   The height of the text area.
     * @param   mixed        $font     The font to use.
     *
     * @return mixed The result of the draw operation.
     */
    public function drawText($parent, $caption, $xPos, $yPos, $width = null, $height = null, $font = null)
    {
        // Fix for PHP 8.2: Handle null parameters
        if ($parent === null) {
            return false;
        }

        $caption = $caption === null ? '' : $caption;
        $caption = str_replace(self::NEW_LINE, PHP_EOL, $caption);

        $width = $width == null ? 120 : $width;
        $height = $height == null ? 25 : $height;

        return $this->callWinBinder('wb_draw_text', array($parent, $caption, $xPos, $yPos, $width, $height, $font));
    }

    /**
     * Draws a rectangle on a WinBinder object.
     *
     * @param   mixed  $parent  The parent window.
     * @param   int    $xPos    The x-coordinate of the rectangle.
     * @param   int    $yPos    The y-coordinate of the rectangle.
     * @param   int    $width   The width of the rectangle.
     * @param   int    $height  The height of the rectangle.
     * @param   int    $color   The color of the rectangle.
     * @param   bool   $filled  Whether to fill the rectangle.
     *
     * @return mixed The result of the draw operation.
     */
    public function drawRect($parent, $xPos, $yPos, $width, $height, $color = 15790320, $filled = true)
    {
        // Fix for PHP 8.2: Handle null parameters
        if ($parent === null) {
            return false;
        }

        // Ensure filled is properly handled as a boolean
        $filled = (bool)$filled;

        return $this->callWinBinder('wb_draw_rect', array($parent, $xPos, $yPos, $width, $height, $color, $filled));
    }

    /**
     * Draws a line on a WinBinder object.
     *
     * @param   mixed  $wbobject   The WinBinder object to draw on.
     * @param   int    $xStartPos  The starting x-coordinate of the line.
     * @param   int    $yStartPos  The starting y-coordinate of the line.
     * @param   int    $xEndPos    The ending x-coordinate of the line.
     * @param   int    $yEndPos    The ending y-coordinate of the line.
     * @param   int    $color      The color of the line.
     * @param   int    $height     The height/thickness of the line.
     *
     * @return mixed The result of the draw operation.
     */
    public function drawLine($wbobject, $xStartPos, $yStartPos, $xEndPos, $yEndPos, $color, $height = 1)
    {
        // Fix for PHP 8.2: Handle null parameters
        if ($wbobject === null) {
            return false;
        }

        return $this->callWinBinder('wb_draw_line', array($wbobject, $xStartPos, $yStartPos, $xEndPos, $yEndPos, $color, $height));
    }

    /**
     * Gets the currently focused WinBinder object.
     *
     * @return mixed The currently focused WinBinder object.
     */
    public function getFocus()
    {
        return $this->callWinBinder('wb_get_focus');
    }

    /**
     * Sets the focus to a WinBinder object.
     *
     * @param   mixed  $wbobject  The WinBinder object to set the focus to.
     *
     * @return mixed The result of the set focus operation.
     */
    public function setFocus($wbobject)
    {
        // Fix for PHP 8.2: Handle null parameter
        if ($wbobject === null) {
            return false;
        }

        return $this->callWinBinder('wb_set_focus', array($wbobject));
    }

    /**
     * Creates a button control.
     *
     * @param   mixed        $parent   The parent window.
     * @param   string       $caption  The button caption.
     * @param   int          $xPos     The x-coordinate of the button.
     * @param   int          $yPos     The y-coordinate of the button.
     * @param   int|null     $width    The width of the button.
     * @param   int|null     $height   The height of the button.
     * @param   mixed        $style    The button style.
     * @param   mixed        $params   Additional parameters for the button.
     *
     * @return array An array containing the button ID and object.
     */
    public function createButton($parent, $caption, $xPos, $yPos, $width = null, $height = null, $style = null, $params = null)
    {
        // Fix for PHP 8.2: Convert null to empty string for caption
        $caption = $caption === null ? '' : $caption;
        $caption = str_replace(self::NEW_LINE, PHP_EOL, $caption);

        // Set default dimensions if not provided
        $width = $width == null ? 80 : $width;
        $height = $height == null ? 25 : $height;

        // Fix for PHP 8.2: Handle null parent parameter
        $parent = $parent === null ? 0 : $parent;

        return $this->createControl($parent, PushButton, (string) $caption, $xPos, $yPos, $width, $height, $style, $params);
    }

    /**
     * Creates a radio button control.
     *
     * @param   mixed        $parent      The parent window.
     * @param   string       $caption     The radio button caption.
     * @param   bool         $checked     Whether the radio button is checked.
     * @param   int          $xPos        The x-coordinate of the radio button.
     * @param   int          $yPos        The y-coordinate of the radio button.
     * @param   int|null     $width       The width of the radio button.
     * @param   int|null     $height      The height of the radio button.
     * @param   bool         $startGroup  Whether the radio button starts a new group.
     *
     * @return array An array containing the radio button ID and object.
     */
    public function createRadioButton($parent, $caption, $checked, $xPos, $yPos, $width = null, $height = null, $startGroup = false)
    {
        // Fix for PHP 8.2: Convert null to empty string for caption
        $caption = $caption === null ? '' : $caption;
        $caption = str_replace(self::NEW_LINE, PHP_EOL, $caption);

        // Set default dimensions if not provided
        $width = $width == null ? 120 : $width;
        $height = $height == null ? 25 : $height;

        // PHP 8.2 compatibility: ensure boolean parameters are properly handled
        $checked = $checked ? 1 : 0;
        $startGroupStyle = $startGroup ? WBC_GROUP : null;

        // Fix for PHP 8.2: Handle null parent parameter
        $parent = $parent === null ? 0 : $parent;

        return $this->createControl($parent, RadioButton, (string) $caption, $xPos, $yPos, $width, $height, $startGroupStyle, $checked);
    }

    /**
     * Creates an input text control.
     *
     * @param   mixed        $parent     The parent window.
     * @param   string       $value      The initial value.
     * @param   int          $xPos       The x-coordinate of the input text.
     * @param   int          $yPos       The y-coordinate of the input text.
     * @param   int|null     $width      The width of the input text.
     * @param   int|null     $height     The height of the input text.
     * @param   int|null     $maxLength  The maximum length of the input text.
     * @param   mixed        $style      The input text style.
     * @param   mixed        $params     Additional parameters for the input text.
     *
     * @return array An array containing the input text ID and object.
     */
    public function createInputText($parent, $value, $xPos, $yPos, $width = null, $height = null, $maxLength = null, $style = null, $params = null)
    {
        // Fix for PHP 8.2: Handle null parameters
        $value = $value === null ? '' : $value;
        $parent = $parent === null ? 0 : $parent;

        $value = str_replace(self::NEW_LINE, PHP_EOL, $value);
        $width = $width == null ? 120 : $width;
        $height = $height == null ? 25 : $height;

        $inputText = $this->createControl($parent, EditBox, (string) $value, $xPos, $yPos, $width, $height, $style, $params);

        if (is_numeric($maxLength) && $maxLength > 0) {
            $this->setMaxLength($inputText[self::CTRL_OBJ], $maxLength);
        }

        return $inputText;
    }

    /**
     * Creates an edit box control.
     *
     * @param   mixed        $parent  The parent window.
     * @param   string       $value   The initial value.
     * @param   int          $xPos    The x-coordinate of the edit box.
     * @param   int          $yPos    The y-coordinate of the edit box.
     * @param   int|null     $width   The width of the edit box.
     * @param   int|null     $height  The height of the edit box.
     * @param   mixed        $style   The edit box style.
     * @param   mixed        $params  Additional parameters for the edit box.
     *
     * @return array An array containing the edit box ID and object.
     */
    public function createEditBox($parent, $value, $xPos, $yPos, $width = null, $height = null, $style = null, $params = null)
    {
        // Fix for PHP 8.2: Handle null parameters
        $value = $value === null ? '' : $value;
        $parent = $parent === null ? 0 : $parent;

        $value = str_replace(self::NEW_LINE, PHP_EOL, $value);
        $width = $width == null ? 540 : $width;
        $height = $height == null ? 340 : $height;

        $editBox = $this->createControl($parent, RTFEditBox, (string) $value, $xPos, $yPos, $width, $height, $style, $params);
        return $editBox;
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
