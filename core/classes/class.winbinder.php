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

    // Constants for message box types
    const BOX_INFO = WBC_INFO;
    const BOX_OK = WBC_OK;
    const BOX_OKCANCEL = WBC_OKCANCEL;
    const BOX_QUESTION = WBC_QUESTION;
    const BOX_ERROR = WBC_STOP;
    const BOX_WARNING = WBC_WARNING;
    const BOX_YESNO = WBC_YESNO;
    const BOX_YESNOCANCEL = WBC_YESNOCANCEL;

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
    public $callback;
    public $gauge;
    /**
     * Array to store progress bar objects
     * @var array
     */
    private $progressBars = array();
    private $defaultTitle;
    private $countCtrls;

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
        
        // Initialize the progressBars array
        $this->progressBars = array();
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
     * @param   mixed   $parent   The parent window or null for a top-level window.
     * @param   string  $wclass   The window class.
     * @param   string  $caption  The window caption.
     * @param   int     $xPos     The x-coordinate of the window.
     * @param   int     $yPos     The y-coordinate of the window.
     * @param   int     $width    The width of the window.
     * @param   int     $height   The height of the window.
     * @param   mixed   $style    The window style.
     * @param   mixed   $params   Additional parameters for the window.
     *
     * @return mixed The created window object.
     */
    public function createWindow($parent, $wclass, $caption, $xPos, $yPos, $width, $height, $style = null, $params = null)
    {
        global $bearsamppCore;

        // Fix for PHP 8.2: Convert null to 0 for parent parameter
        $parent = $parent === null ? 0 : $parent;

        $caption = empty($caption) ? $this->defaultTitle : $this->defaultTitle . ' - ' . $caption;
        $window  = $this->callWinBinder('wb_create_window', array($parent, $wclass, $caption, $xPos, $yPos, $width, $height, $style, $params));

        // Set tiny window icon
        $this->setImage($window, $bearsamppCore->getIconsPath() . 'app.ico');

        return $window;
    }

    /**
     * Calls a WinBinder function with the specified parameters.
     *
     * @param   string  $function            The name of the WinBinder function to call.
     * @param   array   $params              The parameters to pass to the function.
     * @param   bool    $removeErrorHandler  Whether to remove the error handler during the call.
     *
     * @return mixed The result of the function call.
     */
    private function callWinBinder($function, $params = array(), $removeErrorHandler = false)
    {
        $result = false;
        if (function_exists($function)) {
            // Log function call and parameters for debugging
            Util::logTrace("Calling WinBinder function: $function with params: " . print_r($params, true));
            
            if ($removeErrorHandler) {
                $result = @call_user_func_array($function, $params);
            } else {
                $result = call_user_func_array($function, $params);
            }
            
            // Log the result of the function call
            Util::logTrace("WinBinder function $function result: " . ($result === false ? 'false' : print_r($result, true)));
        } else {
            Util::logTrace("WinBinder function not found: $function");
        }

        return $result;
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
        if ($wbobject === null) {
            error_log('Error: $wbobject is null.');

            return false;
        }

        if (!file_exists($path)) {
            error_log('Error: Image file does not exist at path: ' . $path);

            return false;
        }

        return $this->callWinBinder('wb_set_image', array($wbobject, $path));
    }

    /**
     * Creates a new naked window.
     *
     * @param   string  $caption  The window caption.
     * @param   int     $width    The width of the window.
     * @param   int     $height   The height of the window.
     * @param   mixed   $style    The window style.
     * @param   mixed   $params   Additional parameters for the window.
     *
     * @return mixed The created window object.
     */
    public function createNakedWindow($caption, $width, $height, $style = null, $params = null)
    {
        $window = $this->createWindow(null, NakedWindow, $caption, WBC_CENTER, WBC_CENTER, $width, $height, $style, $params);
        $this->setArea($window, $width, $height);

        return $window;
    }

    /**
     * Sets the area of a WinBinder object.
     *
     * @param   mixed  $wbobject  The WinBinder object to set the area for.
     * @param   int    $width     The width of the area.
     * @param   int    $height    The height of the area.
     *
     * @return mixed The result of the set area operation.
     */
    public function setArea($wbobject, $width, $height)
    {
        return $this->callWinBinder('wb_set_area', array($wbobject, WBC_TITLE, 0, 0, $width, $height));
    }

    /**
     * Destroys a window and its children, ensuring UI elements are properly updated.
     *
     * @param   mixed  $window  The window object to destroy.
     * @return  boolean True if window was successfully destroyed
     */
    public function destroyWindow($window)
    {
        Util::logTrace("Destroying window: " . $window);
        
        // First, find and update any progress bars to their final state
        $progressBars = $this->findControlsByType($window, "gauge");
        if (!empty($progressBars)) {
            foreach ($progressBars as $progressBar) {
                // Get the range to set to maximum value
                $range = $this->callWinBinder('wb_get_range', array($progressBar));
                if (is_array($range) && isset($range[1])) {
                    $this->setValue($progressBar, $range[1]);
                    
                    // Also update our tracking array
                    if (isset($this->progressBars[$progressBar])) {
                        $this->progressBars[$progressBar]['value'] = $range[1];
                    }
                }
                $this->refresh($progressBar);
            }
        }
        
        // Before destroying the window, refresh the UI to show final state
        $this->refresh($window);
        
        // Apply a small delay to ensure UI updates are rendered
        usleep(50000); // 50ms delay
        
        try {
            // First try standard window destruction
            if ($this->windowIsValid($window)) {
                Util::logTrace("Window exists, attempting standard window destruction");
                $result = $this->callWinBinder('wb_destroy_window', array($window));
                
                if ($result === false || $result === null) {
                    // If standard destruction fails, try with error suppression
                    Util::logTrace("Standard window destruction failed, using error suppression");
                    $result = $this->callWinBinder('wb_destroy_window', array($window), true);
                }
                
                // Process any pending messages to finalize destruction
                $this->processMessages();
                
                // If window still exists, try more aggressive methods
                if ($this->windowIsValid($window)) {
                    // Get window title for fallback
                    $windowTitle = $this->getText($window);
                    
                    // Try to close by PID
                    $pid = getmypid();
                    if ($pid) {
                        Util::logTrace('Window still exists. Closing process with PID: ' . $pid . ' - using taskkill');
                        $this->exec('taskkill.exe', '/PID ' . $pid . ' /F', true);
                    }
                    // Fallback to window title if PID retrieval fails
                    elseif (!empty($windowTitle)) {
                        Util::logTrace('Closing window with title: ' . $windowTitle . ' - using taskkill');
                        $this->exec('taskkill.exe', '/FI "WINDOWTITLE eq ' . $windowTitle . '" /F', true);
                    }
                    
                    // Final processing of messages
                    $this->processMessages();
                }
            } else {
                Util::logTrace("Window does not exist or is already destroyed");
                return true;
            }
            
            // Force redraw to ensure UI elements update properly
            $this->callWinBinder('wb_refresh', array(null, true));
            
            return true;
        } catch (Exception $e) {
            Util::logTrace("Exception in destroyWindow: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Find all controls of a specific type in a window.
     *
     * @param   mixed   $window  The window object
     * @param   string  $type    The control type to find (e.g., "gauge" for progress bars)
     * @return  array   Array of control objects that match the requested type
     */
    /**
     * Find all controls of a specific type in a window.
     *
     * @param   mixed   $window  The window object
     * @param   string  $type    The control type to find (e.g., "gauge" for progress bars)
     * @return  array   Array of control objects that match the requested type
     */
    private function findControlsByType($window, $type)
    {
        $result = array();
        
        // First check our progressBars array if looking for gauges
        if (strtolower($type) == 'gauge' && !empty($this->progressBars)) {
            foreach ($this->progressBars as $ctrlObj => $data) {
                // Check if this control belongs to the specified window
                $parent = $this->callWinBinder('wb_get_parent', array($ctrlObj), true);
                if ($parent == $window) {
                    $result[] = $ctrlObj;
                }
            }
            
            // If we found progress bars in our tracking, return them
            if (!empty($result)) {
                return $result;
            }
        }
        
        // Fallback to searching all controls
        $controls = $this->callWinBinder('wb_get_item_list', array($window));
        if (!is_array($controls) || empty($controls)) {
            return $result;
        }
        
        // Filter controls by type
        foreach ($controls as $controlId) {
            $className = $this->callWinBinder('wb_get_class', array($controlId));
            if ($className && strtolower($className) == strtolower($type)) {
                $result[] = $controlId;
            }
        }
        
        return $result;
    }

    /**
     * Retrieves the text from a WinBinder object.
     *
     * @param   mixed  $wbobject  The WinBinder object to get the text from.
     *
     * @return mixed The retrieved text.
     */
    public function getText($wbobject)
    {
        return $this->callWinBinder('wb_get_text', array($wbobject));
    }
    
    /**
     * Checks if a window handle is still valid.
     *
     * @param   mixed  $window  The window object to check.
     * @return  boolean True if window is valid
     */
    private function windowIsValid($window)
    {
        if (!$window) {
            return false;
        }
        
        // Try multiple methods to verify window validity
        
        // Method 1: Try to get window text
        $text = $this->callWinBinder('wb_get_text', array($window), true);
        if ($text !== false) {
            return true;
        }
        
        // Method 2: Check if window is visible
        $visible = $this->callWinBinder('wb_get_visible', array($window), true);
        if ($visible !== false) {
            return true;
        }
        
        // Method 3: Try to get window value
        $value = $this->callWinBinder('wb_get_value', array($window), true);
        if ($value !== false) {
            return true;
        }
        
        // Method 4: Check if window exists in the system
        $exists = $this->callWinBinder('wb_sys_dlg_path', array($window, '', null), true);
        if ($exists !== false) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Process any pending window messages.
     *
     * @param   int  $timeout  Optional timeout in milliseconds (default: 50ms)
     * @return void
     */
    private function processMessages($timeout = 50)
    {
        // Process messages with a reasonable timeout
        $this->callWinBinder('wb_wait', array(null, $timeout), true);
        
        // Allow a short sleep to ensure OS has time to process
        usleep(10000); // 10ms sleep
        
        // Force a refresh to ensure UI updates
        $this->callWinBinder('wb_refresh', array(null, true), true);
    }

    /**
     * Executes a system command.
     *
     * @param   string       $cmd     The command to execute.
     * @param   string|null  $params  The parameters to pass to the command.
     * @param   bool         $silent  Whether to execute the command silently.
     * @param   string       $dir     The working directory for the command.
     *
     * @return mixed The result of the command execution.
     */
    public function exec($cmd, $params = null, $silent = false, $dir = '')
    {
        global $bearsamppCore;
        
        Util::logTrace("Starting exec method with command: $cmd, params: " . print_r($params, true) . ", silent: " . ($silent ? 'true' : 'false') . ", dir: $dir");

        // Handle silent execution
        if ($silent) {
            $silentScript = '"' . $bearsamppCore->getScript(Core::SCRIPT_EXEC_SILENT) . '" "' . $cmd . '"';
            $originalCmd = $cmd; // Store original command for logging
            $cmd = 'wscript.exe';
            $params = !empty($params) ? $silentScript . ' "' . $params . '"' : $silentScript;
            Util::logTrace("Silent execution configured. New command: $cmd, new params: $params, original command: $originalCmd");
        }
        
        // Ensure proper quoting of paths with spaces for non-system commands
        if (!$this->isSystemCommand($cmd) && strpos($cmd, ' ') !== false && substr($cmd, 0, 1) != '"') {
            $cmd = '"' . $cmd . '"';
            Util::logTrace("Added quotes to command path: $cmd");
        }

        $this->writeLog('exec: ' . $cmd . ' ' . $params);
        Util::logTrace("Preparing to execute: $cmd $params in directory: " . ($dir ? $dir : 'current directory'));

        try {
            // Call the WinBinder function with proper error handling
            Util::logTrace("Calling wb_exec through callWinBinder");
            $result = $this->callWinBinder('wb_exec', array($cmd, $params, $dir));
            
            if ($result === false || $result === null) {
                $this->writeLog('Failed to execute command: ' . $cmd);
                Util::logTrace("Execution failed for command: $cmd");
                return false;
            }
            
            Util::logTrace("Command executed successfully. Result: " . print_r($result, true));
            return $result;
        } catch (Exception $e) {
            $this->writeLog('Exception in exec method: ' . $e->getMessage());
            Util::logTrace("Exception caught in exec method: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Checks if a command is a system command that doesn't need a full path.
     *
     * @param string $command The command to check
     * @return bool True if it's a system command, false otherwise
     */
    private function isSystemCommand($command)
    {
        // Extract just the filename if a path is provided
        $commandName = basename($command);
        
        $systemCommands = array(
            'cmd.exe', 'wscript.exe', 'cscript.exe', 'explorer.exe', 
            'notepad.exe', 'regedit.exe', 'taskmgr.exe', 'taskkill.exe'
        );
        
        // Case-insensitive check for system commands
        foreach ($systemCommands as $sysCmd) {
            if (strtolower($commandName) == strtolower($sysCmd)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Writes a log message to the WinBinder log file.
     *
     * @param   string  $log  The log message to write.
     */
    private static function writeLog($log)
    {
        global $bearsamppRoot;
        Util::logDebug($log, $bearsamppRoot->getWinbinderLogFilePath());
    }

    /**
     * Starts the main event loop.
     *
     * @return mixed The result of the main loop.
     */
    public function mainLoop()
    {
        return $this->callWinBinder('wb_main_loop');
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
        $image = $this->callWinBinder('wb_load_image', array($path));

        return $this->callWinBinder('wb_draw_image', array($wbobject, $image, $xPos, $yPos, $width, $height));
    }

    /**
     * Draws text on a WinBinder object.
     *
     * @param   mixed     $parent   The parent WinBinder object.
     * @param   string    $caption  The text to draw.
     * @param   int       $xPos     The x-coordinate of the text.
     * @param   int       $yPos     The y-coordinate of the text.
     * @param   int|null  $width    The width of the text area.
     * @param   int|null  $height   The height of the text area.
     * @param   mixed     $font     The font to use for the text.
     *
     * @return mixed The result of the draw operation.
     */
    public function drawText($parent, $caption, $xPos, $yPos, $width = null, $height = null, $font = null)
    {
        $caption = str_replace(self::NEW_LINE, PHP_EOL, $caption);
        $width   = $width == null ? 120 : $width;
        $height  = $height == null ? 25 : $height;

        return $this->callWinBinder('wb_draw_text', array($parent, $caption, $xPos, $yPos, $width, $height, $font));
    }

    /**
     * Draws a rectangle on a WinBinder object.
     *
     * @param   mixed  $parent  The parent WinBinder object.
     * @param   int    $xPos    The x-coordinate of the rectangle.
     * @param   int    $yPos    The y-coordinate of the rectangle.
     * @param   int    $width   The width of the rectangle.
     * @param   int    $height  The height of the rectangle.
     * @param   int    $color   The color of the rectangle.
     * @param   bool   $filled  Whether the rectangle should be filled.
     *
     * @return mixed The result of the draw operation.
     */
    public function drawRect($parent, $xPos, $yPos, $width, $height, $color = 15790320, $filled = true)
    {
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
     * @param   int    $height     The height of the line.
     *
     * @return mixed The result of the draw operation.
     */
    public function drawLine($wbobject, $xStartPos, $yStartPos, $xEndPos, $yEndPos, $color, $height = 1)
    {
        return $this->callWinBinder('wb_draw_line', array($wbobject, $xStartPos, $yStartPos, $xEndPos, $yEndPos, $color, $height));
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
        return $this->callWinBinder('wb_destroy_timer', array($wbobject, $timerobject));
    }

    /**
     * Finds a file using WinBinder.
     *
     * @param   string  $filename  The name of the file to find.
     *
     * @return mixed The result of the find operation.
     */
    public function findFile($filename)
    {
        $result = $this->callWinBinder('wb_find_file', array($filename));
        $this->writeLog('findFile ' . $filename . ': ' . $result);

        return $result != $filename ? $result : false;
    }

    /**
     * Sets an event handler for a WinBinder object.
     *
     * @param   mixed  $wbobject        The WinBinder object to set the handler for.
     * @param   mixed  $classCallback   The class callback for the handler.
     * @param   mixed  $methodCallback  The method callback for the handler.
     * @param   mixed  $launchTimer     The timer to launch for the handler.
     *
     * @return mixed The result of the set handler operation.
     */
    public function setHandler($wbobject, $classCallback, $methodCallback, $launchTimer = null)
    {
        if ($launchTimer != null) {
            $launchTimer = $this->createTimer($wbobject, $launchTimer);
        }

        $this->callback[$wbobject] = array($classCallback, $methodCallback, $launchTimer);

        return $this->callWinBinder('wb_set_handler', array($wbobject, '__winbinderEventHandler'));
    }
    
    /**
     * Callback function for WinBinder events.
     *
     * @param   mixed  $window   The window object.
     * @param   mixed  $id       The control ID.
     * @param   mixed  $ctrl     The control object.
     * @param   mixed  $param1   The first parameter.
     * @param   mixed  $param2   The second parameter.
     *
     * @return mixed The result of the callback.
     */
    public function callback($window, $id, $ctrl, $param1 = 0, $param2 = 0)
    {
        if (isset($this->callback[$window])) {
            $class = $this->callback[$window][0];
            $method = $this->callback[$window][1];
            return $class->$method($window, $id, $ctrl, $param1, $param2);
        }
        return false;
    }

    /**
     * Creates a timer for a WinBinder object.
     *
     * @param   mixed  $wbobject  The WinBinder object to create the timer for.
     * @param   int    $wait      The wait time in milliseconds.
     *
     * @return array An array containing the timer ID and object.
     */
    public function createTimer($wbobject, $wait = 1000)
    {
        $this->countCtrls++;

        return array(
            self::CTRL_ID  => $this->countCtrls,
            self::CTRL_OBJ => $this->callWinBinder('wb_create_timer', array($wbobject, $this->countCtrls, $wait))
        );
    }

    /**
     * Sets the text for a WinBinder object.
     *
     * @param   mixed   $wbobject  The WinBinder object to set the text for.
     * @param   string  $content   The text content to set.
     *
     * @return mixed The result of the set text operation.
     */
    public function setText($wbobject, $content)
    {
        $content = str_replace(self::NEW_LINE, PHP_EOL, $content);

        return $this->callWinBinder('wb_set_text', array($wbobject, $content));
    }

    /**
     * Retrieves the focus from a WinBinder object.
     *
     * @return mixed The WinBinder object that has the focus.
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
        return $this->callWinBinder('wb_set_focus', array($wbobject));
    }

    /**
     * Checks if a WinBinder object is enabled.
     *
     * @param   mixed  $wbobject  The WinBinder object to check.
     *
     * @return mixed True if the object is enabled, false otherwise.
     */
    public function isEnabled($wbobject)
    {
        return $this->callWinBinder('wb_get_enabled', array($wbobject));
    }

    /**
     * Disables a WinBinder object.
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
     * Sets the enabled state for a WinBinder object.
     *
     * @param   mixed  $wbobject  The WinBinder object to set the enabled state for.
     * @param   bool   $enabled   True to enable the object, false to disable it.
     *
     * @return mixed The result of the set enabled state operation.
     */
    public function setEnabled($wbobject, $enabled = true)
    {
        return $this->callWinBinder('wb_set_enabled', array($wbobject, $enabled));
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
        return $this->callWinBinder('wb_sys_dlg_open', array($parent, $title, $filter, $path));
    }

    /**
     * Creates a label control.
     *
     * @param   mixed     $parent   The parent window or control.
     * @param   string    $caption  The caption for the label.
     * @param   int       $xPos     The x-coordinate of the label.
     * @param   int       $yPos     The y-coordinate of the label.
     * @param   int|null  $width    The width of the label.
     * @param   int|null  $height   The height of the label.
     * @param   mixed     $style    The style for the label.
     * @param   mixed     $params   Additional parameters for the label.
     *
     * @return array An array containing the control ID and object.
     */
    public function createLabel($parent, $caption, $xPos, $yPos, $width = null, $height = null, $style = null, $params = null)
    {
        $caption = str_replace(self::NEW_LINE, PHP_EOL, $caption);
        $width   = $width == null ? 120 : $width;
        $height  = $height == null ? 25 : $height;

        return $this->createControl($parent, Label, $caption, $xPos, $yPos, $width, $height, $style, $params);
    }

    /**
     * Creates a new control.
     *
     * @param   mixed   $parent    The parent window or control.
     * @param   string  $ctlClass  The control class.
     * @param   string  $caption   The control caption.
     * @param   int     $xPos      The x-coordinate of the control.
     * @param   int     $yPos      The y-coordinate of the control.
     * @param   int     $width     The width of the control.
     * @param   int     $height    The height of the control.
     * @param   mixed   $style     The control style.
     * @param   mixed   $params    Additional parameters for the control.
     *
     * @return array An array containing the control ID and object.
     */
    public function createControl($parent, $ctlClass, $caption, $xPos, $yPos, $width, $height, $style = null, $params = null)
    {
        $this->countCtrls++;

        return array(
            self::CTRL_ID  => $this->countCtrls,
            self::CTRL_OBJ => $this->callWinBinder('wb_create_control', array(
                $parent,
                $ctlClass,
                $caption,
                $xPos,
                $yPos,
                $width,
                $height,
                $this->countCtrls,
                $style,
                $params
            )),
        );
    }

    /**
     * Creates an input text control.
     *
     * @param   mixed     $parent     The parent window or control.
     * @param   string    $value      The initial value for the input text.
     * @param   int       $xPos       The x-coordinate of the input text.
     * @param   int       $yPos       The y-coordinate of the input text.
     * @param   int|null  $width      The width of the input text.
     * @param   int|null  $height     The height of the input text.
     * @param   int|null  $maxLength  The maximum length of the input text.
     * @param   mixed     $style      The style for the input text.
     * @param   mixed     $params     Additional parameters for the input text.
     *
     * @return array An array containing the control ID and object.
     */
    public function createInputText($parent, $value, $xPos, $yPos, $width = null, $height = null, $maxLength = null, $style = null, $params = null)
    {
        $value     = str_replace(self::NEW_LINE, PHP_EOL, $value);
        $width     = $width == null ? 120 : $width;
        $height    = $height == null ? 25 : $height;
        $inputText = $this->createControl($parent, EditBox, (string)$value, $xPos, $yPos, $width, $height, $style, $params);
        if (is_numeric($maxLength) && $maxLength > 0) {
            $this->setMaxLength($inputText[self::CTRL_OBJ], $maxLength);
        }

        return $inputText;
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
        return $this->callWinBinder('wb_send_message', array($wbobject, 0x00c5, $length, 0));
    }

    /**
     * Creates an edit box control.
     *
     * @param   mixed     $parent  The parent window or control.
     * @param   string    $value   The initial value for the edit box.
     * @param   int       $xPos    The x-coordinate of the edit box.
     * @param   int       $yPos    The y-coordinate of the edit box.
     * @param   int|null  $width   The width of the edit box.
     * @param   int|null  $height  The height of the edit box.
     * @param   mixed     $style   The style for the edit box.
     * @param   mixed     $params  Additional parameters for the edit box.
     *
     * @return array An array containing the control ID and object.
     */
    public function createEditBox($parent, $value, $xPos, $yPos, $width = null, $height = null, $style = null, $params = null)
    {
        $value   = str_replace(self::NEW_LINE, PHP_EOL, $value);
        $width   = $width == null ? 540 : $width;
        $height  = $height == null ? 340 : $height;
        $editBox = $this->createControl($parent, RTFEditBox, (string)$value, $xPos, $yPos, $width, $height, $style, $params);

        return $editBox;
    }

    /**
     * Creates a hyperlink control.
     *
     * @param   mixed     $parent   The parent window or control.
     * @param   string    $caption  The caption for the hyperlink.
     * @param   int       $xPos     The x-coordinate of the hyperlink.
     * @param   int       $yPos     The y-coordinate of the hyperlink.
     * @param   int|null  $width    The width of the hyperlink.
     * @param   int|null  $height   The height of the hyperlink.
     * @param   mixed     $style    The style for the hyperlink.
     * @param   mixed     $params   Additional parameters for the hyperlink.
     *
     * @return array An array containing the control ID and object.
     */
    public function createHyperLink($parent, $caption, $xPos, $yPos, $width = null, $height = null, $style = null, $params = null)
    {
        $caption   = str_replace(self::NEW_LINE, PHP_EOL, $caption);
        $width     = $width == null ? 120 : $width;
        $height    = $height == null ? 15 : $height;
        $hyperLink = $this->createControl($parent, HyperLink, (string)$caption, $xPos, $yPos, $width, $height, $style, $params);
        $this->setCursor($hyperLink[self::CTRL_OBJ], self::CURSOR_FINGER);

        return $hyperLink;
    }

    /**
     * Sets the cursor type for a WinBinder object.
     *
     * @param   mixed   $wbobject  The WinBinder object to set the cursor for.
     * @param   string  $type      The cursor type to set.
     *
     * @return mixed The result of the set cursor operation.
     */
    public function setCursor($wbobject, $type = self::CURSOR_ARROW)
    {
        return $this->callWinBinder('wb_set_cursor', array($wbobject, $type));
    }

    /**
     * Creates a radio button control.
     *
     * @param   mixed     $parent      The parent window or control.
     * @param   string    $caption     The caption for the radio button.
     * @param   bool      $checked     Whether the radio button is checked.
     * @param   int       $xPos        The x-coordinate of the radio button.
     * @param   int       $yPos        The y-coordinate of the radio button.
     * @param   int|null  $width       The width of the radio button.
     * @param   int|null  $height      The height of the radio button.
     * @param   bool      $startGroup  Whether this radio button starts a new group.
     *
     * @return array An array containing the control ID and object.
     */
    public function createRadioButton($parent, $caption, $checked, $xPos, $yPos, $width = null, $height = null, $startGroup = false)
    {
        $caption = str_replace(self::NEW_LINE, PHP_EOL, $caption);
        $width   = $width == null ? 120 : $width;
        $height  = $height == null ? 25 : $height;

        return $this->createControl($parent, RadioButton, (string)$caption, $xPos, $yPos, $width, $height, $startGroup ? WBC_GROUP : null, $checked ? 1 : 0);
    }

    /**
     * Creates a button control.
     *
     * @param   mixed     $parent   The parent window or control.
     * @param   string    $caption  The caption for the button.
     * @param   int       $xPos     The x-coordinate of the button.
     * @param   int       $yPos     The y-coordinate of the button.
     * @param   int|null  $width    The width of the button.
     * @param   int|null  $height   The height of the button.
     * @param   mixed     $style    The style for the button.
     * @param   mixed     $params   Additional parameters for the button.
     *
     * @return array An array containing the control ID and object.
     */
    public function createButton($parent, $caption, $xPos, $yPos, $width = null, $height = null, $style = null, $params = null)
    {
        $width  = $width == null ? 80 : $width;
        $height = $height == null ? 25 : $height;

        return $this->createControl($parent, PushButton, $caption, $xPos, $yPos, $width, $height, $style, $params);
    }

    /**
     * Creates a progress bar control.
     *
     * @param   mixed     $parent  The parent window or control.
     * @param   int       $max     The maximum value for the progress bar.
     * @param   int       $xPos    The x-coordinate of the progress bar.
     * @param   int       $yPos    The y-coordinate of the progress bar.
     * @param   int|null  $width   The width of the progress bar.
     * @param   int|null  $height  The height of the progress bar.
     * @param   mixed     $style   The style for the progress bar.
     * @param   mixed     $params  Additional parameters for the progress bar.
     *
     * @return array An array containing the control ID and object.
     */
    public function createProgressBar($parent, $max, $xPos, $yPos, $width = null, $height = null, $style = null, $params = null)
    {
        global $bearsamppLang;

        $width       = $width == null ? 200 : $width;
        $height      = $height == null ? 15 : $height;
        $progressBar = $this->createControl($parent, Gauge, $bearsamppLang->getValue(Lang::LOADING), $xPos, $yPos, $width, $height, $style, $params);

        $this->setRange($progressBar[self::CTRL_OBJ], 0, $max);
        $this->gauge[$progressBar[self::CTRL_OBJ]] = 0;
        
        // Store the progress bar in our tracking array
        $this->progressBars[$progressBar[self::CTRL_OBJ]] = array(
            'value' => 0,
            'max' => $max,
            'control' => $progressBar
        );

        return $progressBar;
    }

    /**
     * Retrieves the value from a WinBinder object.
     *
     * @param   mixed  $wbobject  The WinBinder object to get the value from.
     *
     * @return mixed The retrieved value.
     */
    public function getValue($wbobject)
    {
        return $this->callWinBinder('wb_get_value', array($wbobject));
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
        return $this->callWinBinder('wb_set_range', array($wbobject, $min, $max));
    }

    /**
     * Increments the value of a progress bar.
     *
     * @param   array  $progressBar  The progress bar control.
     */
    public function incrProgressBar($progressBar)
    {
        $this->setProgressBarValue($progressBar, self::INCR_PROGRESS_BAR);
    }

    /**
     * Sets the value of a progress bar.
     *
     * @param   array  $progressBar  The progress bar control.
     * @param   mixed  $value        The value to set.
     */
    public function setProgressBarValue($progressBar, $value)
    {
        if ($progressBar != null && isset($progressBar[self::CTRL_OBJ])) {
            $ctrlObj = $progressBar[self::CTRL_OBJ];
            
            // Check if progress bar exists in our tracking array
            if (isset($this->progressBars[$ctrlObj])) {
                if (strval($value) == self::INCR_PROGRESS_BAR) {
                    $value = $this->progressBars[$ctrlObj]['value'] + 1;
                }
                
                if (is_numeric($value)) {
                    // Update both tracking systems for backward compatibility
                    $this->gauge[$ctrlObj] = $value;
                    $this->progressBars[$ctrlObj]['value'] = $value;
                    
                    // Update the actual control
                    $this->setValue($ctrlObj, $value);
                }
            } 
            // Fallback to old gauge system if not in progressBars
            else if (isset($this->gauge[$ctrlObj])) {
                if (strval($value) == self::INCR_PROGRESS_BAR) {
                    $value = $this->gauge[$ctrlObj] + 1;
                }
                if (is_numeric($value)) {
                    $this->gauge[$ctrlObj] = $value;
                    $this->setValue($ctrlObj, $value);
                }
            }
        }
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
        return $this->callWinBinder('wb_set_value', array($wbobject, $content));
    }

    /**
     * Resets the value of a progress bar to zero.
     *
     * @param   array  $progressBar  The progress bar control.
     */
    public function resetProgressBar($progressBar)
    {
        if ($progressBar != null && isset($progressBar[self::CTRL_OBJ])) {
            $ctrlObj = $progressBar[self::CTRL_OBJ];
            
            // Reset to zero
            $this->setProgressBarValue($progressBar, 0);
            
            // Make sure our tracking is also reset
            if (isset($this->progressBars[$ctrlObj])) {
                $this->progressBars[$ctrlObj]['value'] = 0;
            }
        }
    }

    /**
     * Sets the maximum value of a progress bar.
     *
     * @param   array  $progressBar  The progress bar control.
     * @param   int    $max          The maximum value to set.
     */
    public function setProgressBarMax($progressBar, $max)
    {
        if ($progressBar != null && isset($progressBar[self::CTRL_OBJ])) {
            $ctrlObj = $progressBar[self::CTRL_OBJ];
            
            // Update the range in the control
            $this->setRange($ctrlObj, 0, $max);
            
            // Update our tracking array if it exists
            if (isset($this->progressBars[$ctrlObj])) {
                $this->progressBars[$ctrlObj]['max'] = $max;
            }
        }
    }

    /**
     * Creates an item in a WinBinder control.
     *
     * @param   mixed   $wbobject  The WinBinder object to create the item in.
     * @param   string  $text      The text of the item.
     * @param   mixed   $value     The value of the item.
     *
     * @return mixed The result of the create item operation.
     */
    public function createItem($wbobject, $text, $value = null)
    {
        return $this->callWinBinder('wb_create_item', array($wbobject, $text, $value));
    }

    /**
     * Displays an informational message box.
     *
     * @param   string       $message  The message to display.
     * @param   string|null  $title    The title of the message box.
     * @param   mixed        $parent   The parent window handle or null for a top-level message box.
     *
     * @return mixed The result of the message box operation.
     */
    public function messageBoxInfo($message, $title = null, $parent = null)
    {
        return $this->messageBox($message, self::BOX_INFO, $title, $parent);
    }

    /**
     * Displays a message box.
     *
     * @param   string       $message  The message to display.
     * @param   int          $type     The type of message box.
     * @param   string|null  $title    The title of the message box.
     * @param   mixed        $parent   The parent window handle or null for a top-level message box.
     *
     * @return mixed The result of the message box operation.
     */
    public function messageBox($message, $type = self::BOX_OK, $title = null, $parent = null)
    {
        // Fix for PHP 8.2: Convert null to 0 for parent parameter
        $parent = $parent === null ? 0 : $parent;
        
        // Format the message and title
        $message = str_replace(self::NEW_LINE, PHP_EOL, $message);
        $title = $title === null ? $this->defaultTitle : $this->defaultTitle . ' - ' . $title;
        
        // Ensure message is long enough to display the full title
        if (strlen($message) < 64) {
            $message = str_pad($message, 64);
        }
        
        // Call the WinBinder function
        $messageBox = $this->callWinBinder('wb_message_box', array(
            $parent,
            $message,
            $title,
            $type
        ));
        
        return $messageBox;
    }

    /**
     * Displays an OK message box.
     *
     * @param   string       $message  The message to display.
     * @param   string|null  $title    The title of the message box.
     * @param   mixed        $parent   The parent window handle or null for a top-level message box.
     *
     * @return mixed The result of the message box operation.
     */
    public function messageBoxOk($message, $title = null, $parent = null)
    {
        return $this->messageBox($message, self::BOX_OK, $title, $parent);
    }

    /**
     * Displays an OK/Cancel message box.
     *
     * @param   string       $message  The message to display.
     * @param   string|null  $title    The title of the message box.
     * @param   mixed        $parent   The parent window handle or null for a top-level message box.
     *
     * @return mixed The result of the message box operation.
     */
    public function messageBoxOkCancel($message, $title = null, $parent = null)
    {
        return $this->messageBox($message, self::BOX_OKCANCEL, $title, $parent);
    }

    /**
     * Displays a question message box.
     *
     * @param   string       $message  The message to display.
     * @param   string|null  $title    The title of the message box. If null, the default title will be used.
     * @param   mixed        $parent   The parent window handle or null for a top-level message box.
     *
     * @return mixed The result of the message box operation.
     */
    public function messageBoxQuestion($message, $title = null, $parent = null)
    {
        return $this->messageBox($message, self::BOX_QUESTION, $title, $parent);
    }

    /**
     * Displays an error message box.
     *
     * @param   string       $message  The message to display.
     * @param   string|null  $title    The title of the message box. If null, the default title will be used.
     * @param   mixed        $parent   The parent window handle or null for a top-level message box.
     *
     * @return mixed The result of the message box operation.
     */
    public function messageBoxError($message, $title = null, $parent = null)
    {
        return $this->messageBox($message, self::BOX_ERROR, $title, $parent);
    }

    /**
     * Displays a warning message box.
     *
     * @param   string       $message  The message to display.
     * @param   string|null  $title    The title of the message box. If null, the default title will be used.
     * @param   mixed        $parent   The parent window handle or null for a top-level message box.
     *
     * @return mixed The result of the message box operation.
     */
    public function messageBoxWarning($message, $title = null, $parent = null)
    {
        return $this->messageBox($message, self::BOX_WARNING, $title, $parent);
    }

    /**
     * Displays a Yes/No message box.
     *
     * @param   string       $message  The message to display.
     * @param   string|null  $title    The title of the message box. If null, the default title will be used.
     * @param   mixed        $parent   The parent window handle or null for a top-level message box.
     *
     * @return mixed The result of the message box operation.
     */
    public function messageBoxYesNo($message, $title = null, $parent = null)
    {
        return $this->messageBox($message, self::BOX_YESNO, $title, $parent);
    }

    /**
     * Displays a Yes/No/Cancel message box.
     *
     * @param   string       $message  The message to display.
     * @param   string|null  $title    The title of the message box. If null, the default title will be used.
     * @param   mixed        $parent   The parent window handle or null for a top-level message box.
     *
     * @return mixed The result of the message box operation.
     */
    public function messageBoxYesNoCancel($message, $title = null, $parent = null)
    {
        return $this->messageBox($message, self::BOX_YESNOCANCEL, $title, $parent);
    }

    /**
     * Sets the selected item in a WinBinder control.
     *
     * @param   mixed  $wbobject  The WinBinder object to set the selected item in.
     * @param   mixed  $index     The index of the item to select.
     *
     * @return mixed The result of the set selected operation.
     */
    public function setSelected($wbobject, $index)
    {
        return $this->callWinBinder('wb_set_selected', array($wbobject, $index));
    }

    /**
     * Sets the state of a WinBinder object.
     *
     * @param   mixed  $wbobject  The WinBinder object to set the state of.
     * @param   mixed  $state     The state to set.
     *
     * @return mixed The result of the set state operation.
     */
    public function setState($wbobject, $state)
    {
        return $this->callWinBinder('wb_set_state', array($wbobject, $state));
    }

    /**
     * Shows or hides a WinBinder object.
     *
     * @param   mixed   $wbobject  The WinBinder object to show or hide.
     * @param   bool    $visible   Whether to show or hide the object.
     *
     * @return mixed The result of the set visible operation.
     */
    public function setVisible($wbobject, $visible = true)
    {
        return $this->callWinBinder('wb_set_visible', array($wbobject, $visible));
    }

    /**
     * Deletes all items from a WinBinder control.
     *
     * @param   mixed  $wbobject  The WinBinder object to delete items from.
     *
     * @return mixed The result of the delete items operation.
     */
    public function deleteItems($wbobject)
    {
        return $this->callWinBinder('wb_delete_items', array($wbobject));
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

    if ($bearsamppWinbinder->callback[$window][2] != null) {
        $bearsamppWinbinder->destroyTimer($window, $bearsamppWinbinder->callback[$window][2][0]);
    }

    return $bearsamppWinbinder->callback($window, $id, $ctrl, $param1, $param2);
}
