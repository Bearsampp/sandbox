<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Class WinBinder
 *
 * This class provides an interface to the WinBinder library, which is used for creating
 * and managing Windows GUI applications. It includes methods for creating windows, controls,
 * handling events, and interacting with the system.
 *
 * @package Bearsampp
 * @license GNU General Public License version 3 or later; see LICENSE.txt
 * @link    https://bearsampp.com
 * @link    https://github.com/Bearsampp
 */
class WinBinder
{
    // Control identifiers
    const CTRL_ID = 0;
    const CTRL_OBJ = 1;

    // Progress bar constants
    const INCR_PROGRESS_BAR = '++';
    const NEW_LINE = '@nl@';

    // Message box types
    const BOX_INFO = WBC_INFO;
    const BOX_OK = WBC_OK;
    const BOX_OKCANCEL = WBC_OKCANCEL;
    const BOX_QUESTION = WBC_QUESTION;
    const BOX_ERROR = WBC_STOP;
    const BOX_WARNING = WBC_WARNING;
    const BOX_YESNO = WBC_YESNO;
    const BOX_YESNOCANCEL = WBC_YESNOCANCEL;

    // Cursor types
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

    // System information types
    const SYSINFO_SCREENAREA = 'screenarea';
    const SYSINFO_WORKAREA = 'workarea';

    private $defaultTitle;
    private $countCtrls;

    public $callback;
    public $gauge;

    /**
     * WinBinder constructor.
     *
     * Initializes the WinBinder class, sets the default window title, and resets control counters.
     */
    public function __construct()
    {
        global $bearsamppCore;
        Util::logInitClass( $this );

        $this->defaultTitle = APP_TITLE . ' ' . $bearsamppCore->getAppVersion();
        $this->reset();
    }

    /**
     * Writes a log message to the WinBinder log file.
     *
     * @param   string  $log  The log message to write.
     */
    private static function writeLog($log)
    {
        global $bearsamppRoot;
        Util::logDebug( $log, $bearsamppRoot->getWinbinderLogFilePath() );
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
     * Calls a WinBinder function with the specified parameters.
     *
     * @param   string  $function            The name of the WinBinder function to call.
     * @param   array   $params              The parameters to pass to the function.
     * @param   bool    $removeErrorHandler  Whether to suppress error handling.
     *
     * @return mixed The result of the function call.
     */
    private function callWinBinder($function, $params = array(), $removeErrorHandler = false)
    {
        $result = false;
        if ( function_exists( $function ) ) {
            if ( $removeErrorHandler ) {
                $result = @call_user_func_array( $function, $params );
            }
            else {
                $result = call_user_func_array( $function, $params );
            }
        }

        return $result;
    }

    /**
     * Creates a new window.
     *
     * @param   mixed   $parent   The parent window.
     * @param   string  $wclass   The window class.
     * @param   string  $caption  The window caption.
     * @param   int     $xPos     The x-coordinate of the window.
     * @param   int     $yPos     The y-coordinate of the window.
     * @param   int     $width    The width of the window.
     * @param   int     $height   The height of the window.
     * @param   mixed   $style    The window style.
     * @param   mixed   $params   Additional parameters.
     *
     * @return mixed The created window.
     */
    public function createWindow($parent, $wclass, $caption, $xPos, $yPos, $width, $height, $style = null, $params = null)
    {
        global $bearsamppCore;

        $caption = empty( $caption ) ? $this->defaultTitle : $this->defaultTitle . ' - ' . $caption;
        $window  = $this->callWinBinder( 'wb_create_window', array($parent, $wclass, $caption, $xPos, $yPos, $width, $height, $style, $params) );

        // Set tiny window icon
        $this->setImage( $window, $bearsamppCore->getResourcesPath() . '/homepage/img/icons/app.ico' );

        return $window;
    }

    /**
     * Creates a new control.
     *
     * @param   mixed   $parent    The parent window.
     * @param   string  $ctlClass  The control class.
     * @param   string  $caption   The control caption.
     * @param   int     $xPos      The x-coordinate of the control.
     * @param   int     $yPos      The y-coordinate of the control.
     * @param   int     $width     The width of the control.
     * @param   int     $height    The height of the control.
     * @param   mixed   $style     The control style.
     * @param   mixed   $params    Additional parameters.
     *
     * @return array The created control.
     */
    public function createControl($parent, $ctlClass, $caption, $xPos, $yPos, $width, $height, $style = null, $params = null)
    {
        $this->countCtrls++;

        return array(
            self::CTRL_ID  => $this->countCtrls,
            self::CTRL_OBJ => $this->callWinBinder( 'wb_create_control', array(
                $parent, $ctlClass, $caption, $xPos, $yPos, $width, $height, $this->countCtrls, $style, $params
            ) ),
        );
    }

    /**
     * Creates an application window with the specified caption, width, and height.
     *
     * @param   string      $caption  The caption/title of the window.
     * @param   int         $width    The width of the window.
     * @param   int         $height   The height of the window.
     * @param   mixed|null  $style    Optional. The style of the window.
     * @param   mixed|null  $params   Optional. Additional parameters for the window.
     *
     * @return mixed The created window.
     */
    public function createAppWindow($caption, $width, $height, $style = null, $params = null)
    {
        return $this->createWindow( null, AppWindow, $caption, WBC_CENTER, WBC_CENTER, $width, $height, $style, $params );
    }

    /**
     * Creates a naked window with the specified caption, width, and height.
     * A naked window is a window without any decorations.
     *
     * @param   string      $caption  The caption/title of the window.
     * @param   int         $width    The width of the window.
     * @param   int         $height   The height of the window.
     * @param   mixed|null  $style    Optional. The style of the window.
     * @param   mixed|null  $params   Optional. Additional parameters for the window.
     *
     * @return mixed The created window.
     */
    public function createNakedWindow($caption, $width, $height, $style = null, $params = null)
    {
        $window = $this->createWindow( null, NakedWindow, $caption, WBC_CENTER, WBC_CENTER, $width, $height, $style, $params );
        $this->setArea( $window, $width, $height );

        return $window;
    }

    /**
     * Destroys the specified window and exits the application.
     *
     * @param   mixed  $window  The window to be destroyed.
     */
    public function destroyWindow($window)
    {
        $this->callWinBinder( 'wb_destroy_window', array($window), true );
        exit();
    }

    /**
     * Starts the main event loop of the application.
     *
     * @return mixed The result of the main loop.
     */
    public function mainLoop()
    {
        return $this->callWinBinder( 'wb_main_loop' );
    }

    /**
     * Refreshes the specified WinBinder object.
     *
     * @param   mixed  $wbobject  The WinBinder object to refresh.
     *
     * @return mixed The result of the refresh operation.
     */
    public function refresh($wbobject)
    {
        return $this->callWinBinder( 'wb_refresh', array($wbobject, true) );
    }

    /**
     * Retrieves system information based on the specified info type.
     *
     * @param   string  $info  The type of system information to retrieve.
     *
     * @return mixed The retrieved system information.
     */
    public function getSystemInfo($info)
    {
        return $this->callWinBinder( 'wb_get_system_info', array($info) );
    }

    /**
     * Draws an image on the specified WinBinder object.
     *
     * @param   mixed   $wbobject  The WinBinder object to draw the image on.
     * @param   string  $path      The path to the image file.
     * @param   int     $xPos      Optional. The x-coordinate to draw the image. Default is 0.
     * @param   int     $yPos      Optional. The y-coordinate to draw the image. Default is 0.
     * @param   int     $width     Optional. The width of the image. Default is 0.
     * @param   int     $height    Optional. The height of the image. Default is 0.
     *
     * @return mixed The result of the draw image operation.
     */
    public function drawImage($wbobject, $path, $xPos = 0, $yPos = 0, $width = 0, $height = 0)
    {
        $image = $this->callWinBinder( 'wb_load_image', array($path) );

        return $this->callWinBinder( 'wb_draw_image', array($wbobject, $image, $xPos, $yPos, $width, $height) );
    }

    /**
     * Draws text on the specified parent object.
     *
     * @param   mixed       $parent   The parent object to draw the text on.
     * @param   string      $caption  The text to draw.
     * @param   int         $xPos     The x-coordinate to draw the text.
     * @param   int         $yPos     The y-coordinate to draw the text.
     * @param   int|null    $width    Optional. The width of the text area. Default is 120.
     * @param   int|null    $height   Optional. The height of the text area. Default is 25.
     * @param   mixed|null  $font     Optional. The font to use for the text.
     *
     * @return mixed The result of the draw text operation.
     */
    public function drawText($parent, $caption, $xPos, $yPos, $width = null, $height = null, $font = null)
    {
        $caption = str_replace( self::NEW_LINE, PHP_EOL, $caption );
        $width   = $width == null ? 120 : $width;
        $height  = $height == null ? 25 : $height;

        return $this->callWinBinder( 'wb_draw_text', array($parent, $caption, $xPos, $yPos, $width, $height, $font) );
    }

    /**
     * Draws a rectangle on the specified parent object.
     *
     * @param   mixed  $parent  The parent object to draw the rectangle on.
     * @param   int    $xPos    The x-coordinate to draw the rectangle.
     * @param   int    $yPos    The y-coordinate to draw the rectangle.
     * @param   int    $width   The width of the rectangle.
     * @param   int    $height  The height of the rectangle.
     * @param   int    $color   Optional. The color of the rectangle. Default is 15790320.
     * @param   bool   $filled  Optional. Whether the rectangle should be filled. Default is true.
     *
     * @return mixed The result of the draw rectangle operation.
     */
    public function drawRect($parent, $xPos, $yPos, $width, $height, $color = 15790320, $filled = true)
    {
        return $this->callWinBinder( 'wb_draw_rect', array($parent, $xPos, $yPos, $width, $height, $color, $filled) );
    }

    /**
     * Draws a line on the specified WinBinder object.
     *
     * @param   mixed  $wbobject   The WinBinder object to draw the line on.
     * @param   int    $xStartPos  The starting x-coordinate of the line.
     * @param   int    $yStartPos  The starting y-coordinate of the line.
     * @param   int    $xEndPos    The ending x-coordinate of the line.
     * @param   int    $yEndPos    The ending y-coordinate of the line.
     * @param   int    $color      The color of the line.
     * @param   int    $height     Optional. The height of the line. Default is 1.
     *
     * @return mixed The result of the draw line operation.
     */
    public function drawLine($wbobject, $xStartPos, $yStartPos, $xEndPos, $yEndPos, $color, $height = 1)
    {
        return $this->callWinBinder( 'wb_draw_line', array($wbobject, $xStartPos, $yStartPos, $xEndPos, $yEndPos, $color, $height) );
    }

    /**
     * Creates a font with the specified properties.
     *
     * @param   string      $fontName  The name of the font.
     * @param   int|null    $size      Optional. The size of the font.
     * @param   int|null    $color     Optional. The color of the font.
     * @param   mixed|null  $style     Optional. The style of the font.
     *
     * @return mixed The created font.
     */
    public function createFont($fontName, $size = null, $color = null, $style = null)
    {
        return $this->callWinBinder( 'wb_create_font', array($fontName, $size, $color, $style) );
    }

    /**
     * Waits for an event on the specified WinBinder object.
     *
     * @param   mixed|null  $wbobject  Optional. The WinBinder object to wait for an event on.
     *
     * @return mixed The result of the wait operation.
     */
    public function wait($wbobject = null)
    {
        return $this->callWinBinder( 'wb_wait', array($wbobject), true );
    }

    /**
     * Creates a timer with the specified wait time.
     *
     * @param   mixed  $wbobject  The WinBinder object to associate the timer with.
     * @param   int    $wait      Optional. The wait time in milliseconds. Default is 1000.
     *
     * @return array The created timer.
     */
    public function createTimer($wbobject, $wait = 1000)
    {
        $this->countCtrls++;

        return array(
            self::CTRL_ID  => $this->countCtrls,
            self::CTRL_OBJ => $this->callWinBinder( 'wb_create_timer', array($wbobject, $this->countCtrls, $wait) )
        );
    }

    /**
     * Destroys the specified timer.
     *
     * @param   mixed  $wbobject     The WinBinder object associated with the timer.
     * @param   mixed  $timerobject  The timer to be destroyed.
     *
     * @return mixed The result of the destroy timer operation.
     */
    public function destroyTimer($wbobject, $timerobject)
    {
        return $this->callWinBinder( 'wb_destroy_timer', array($wbobject, $timerobject) );
    }

    /**
     * Executes a command with optional parameters.
     *
     * @param   string       $cmd     The command to execute.
     * @param   string|null  $params  Optional. The parameters for the command.
     * @param   bool         $silent  Optional. Whether to execute the command silently. Default is false.
     *
     * @return mixed The result of the exec operation.
     */
    public function exec($cmd, $params = null, $silent = false)
    {
        global $bearsamppCore;

        if ( $silent ) {
            $silent = '"' . $bearsamppCore->getScript( Core::SCRIPT_EXEC_SILENT ) . '" "' . $cmd . '"';
            $cmd    = 'wscript.exe';
            $params = !empty( $params ) ? $silent . ' "' . $params . '"' : $silent;
        }

        $this->writeLog( 'exec: ' . $cmd . ' ' . $params );

        return $this->callWinBinder( 'wb_exec', array($cmd, $params) );
    }

    /**
     * Finds a file with the specified filename.
     *
     * @param   string  $filename  The name of the file to find.
     *
     * @return mixed The result of the find file operation.
     */
    public function findFile($filename)
    {
        $result = $this->callWinBinder( 'wb_find_file', array($filename) );
        $this->writeLog( 'findFile ' . $filename . ': ' . $result );

        return $result != $filename ? $result : false;
    }

    /**
     * Sets an event handler for the specified WinBinder object.
     *
     * @param   mixed     $wbobject        The WinBinder object to set the handler for.
     * @param   string    $classCallback   The class callback for the handler.
     * @param   string    $methodCallback  The method callback for the handler.
     * @param   int|null  $launchTimer     Optional. The timer to launch the handler. Default is null.
     *
     * @return mixed The result of the set handler operation.
     */
    public function setHandler($wbobject, $classCallback, $methodCallback, $launchTimer = null)
    {
        if ( $launchTimer != null ) {
            $launchTimer = $this->createTimer( $wbobject, $launchTimer );
        }

        $this->callback[$wbobject] = array($classCallback, $methodCallback, $launchTimer);

        return $this->callWinBinder( 'wb_set_handler', array($wbobject, '__winbinderEventHandler') );
    }

    /**
     * Sets an image for the specified WinBinder object.
     *
     * @param   mixed   $wbobject  The WinBinder object to set the image for.
     * @param   string  $path      The path to the image file.
     *
     * @return mixed The result of the set image operation.
     */
    public function setImage($wbobject, $path)
    {
        return $this->callWinBinder( 'wb_set_image', array($wbobject, $path) );
    }

    /**
     * Sets the maximum length for the specified WinBinder object.
     *
     * @param   mixed  $wbobject  The WinBinder object to set the maximum length for.
     * @param   int    $length    The maximum length to set.
     *
     * @return mixed The result of the set maximum length operation.
     */
    public function setMaxLength($wbobject, $length)
    {
        return $this->callWinBinder( 'wb_send_message', array($wbobject, 0x00c5, $length, 0) );
    }

    /**
     * Sets the area for the specified WinBinder object.
     *
     * @param   mixed  $wbobject  The WinBinder object to set the area for.
     * @param   int    $width     The width of the area.
     * @param   int    $height    The height of the area.
     *
     * @return mixed The result of the set area operation.
     */
    public function setArea($wbobject, $width, $height)
    {
        return $this->callWinBinder( 'wb_set_area', array($wbobject, WBC_TITLE, 0, 0, $width, $height) );
    }

    /**
     * Retrieves the text from the specified WinBinder object.
     *
     * @param   mixed  $wbobject  The WinBinder object to retrieve the text from.
     *
     * @return mixed The retrieved text.
     */
    public function getText($wbobject)
    {
        return $this->callWinBinder( 'wb_get_text', array($wbobject) );
    }

    /**
     * Sets the text for the specified WinBinder object.
     *
     * @param   mixed   $wbobject  The WinBinder object to set the text for.
     * @param   string  $content   The text content to set.
     *
     * @return mixed The result of the set text operation.
     */
    public function setText($wbobject, $content)
    {
        $content = str_replace( self::NEW_LINE, PHP_EOL, $content );

        return $this->callWinBinder( 'wb_set_text', array($wbobject, $content) );
    }

    /**
     * Retrieves the value from the specified WinBinder object.
     *
     * @param   mixed  $wbobject  The WinBinder object to retrieve the value from.
     *
     * @return mixed The retrieved value.
     */
    public function getValue($wbobject)
    {
        return $this->callWinBinder( 'wb_get_value', array($wbobject) );
    }

    /**
     * Sets the value for the specified WinBinder object.
     *
     * @param   mixed  $wbobject  The WinBinder object to set the value for.
     * @param   mixed  $content   The value to set.
     *
     * @return mixed The result of the set value operation.
     */
    public function setValue($wbobject, $content)
    {
        return $this->callWinBinder( 'wb_set_value', array($wbobject, $content) );
    }

    /**
     * Retrieves the focus from the current WinBinder object.
     *
     * @return mixed The WinBinder object that currently has focus.
     */
    public function getFocus()
    {
        return $this->callWinBinder( 'wb_get_focus' );
    }

    /**
     * Sets the focus to the specified WinBinder object.
     *
     * @param   mixed  $wbobject  The WinBinder object to set the focus to.
     *
     * @return mixed The result of the set focus operation.
     */
    public function setFocus($wbobject)
    {
        return $this->callWinBinder( 'wb_set_focus', array($wbobject) );
    }

    /**
     * Sets the cursor type for the specified WinBinder object.
     *
     * @param   mixed   $wbobject  The WinBinder object to set the cursor for.
     * @param   string  $type      Optional. The type of cursor to set. Default is self::CURSOR_ARROW.
     *
     * @return mixed The result of the set cursor operation.
     */
    public function setCursor($wbobject, $type = self::CURSOR_ARROW)
    {
        return $this->callWinBinder( 'wb_set_cursor', array($wbobject, $type) );
    }

    /**
     * Checks if the specified WinBinder object is enabled.
     *
     * @param   mixed  $wbobject  The WinBinder object to check.
     *
     * @return mixed The result of the is enabled check.
     */
    public function isEnabled($wbobject)
    {
        return $this->callWinBinder( 'wb_get_enabled', array($wbobject) );
    }

    /**
     * Sets the enabled state for the specified WinBinder object.
     *
     * @param   mixed  $wbobject  The WinBinder object to set the enabled state for.
     * @param   bool   $enabled   Optional. Whether to enable the object. Default is true.
     *
     * @return mixed The result of the set enabled operation.
     */
    public function setEnabled($wbobject, $enabled = true)
    {
        return $this->callWinBinder( 'wb_set_enabled', array($wbobject, $enabled) );
    }

    /**
     * Sets the disabled state for the specified WinBinder object.
     *
     * @param   mixed  $wbobject  The WinBinder object to set the disabled state for.
     *
     * @return mixed The result of the set disabled operation.
     */
    public function setDisabled($wbobject)
    {
        return $this->setEnabled( $wbobject, false );
    }

    /**
     * Sets the style for the specified WinBinder object.
     *
     * @param   mixed  $wbobject  The WinBinder object to set the style for.
     * @param   mixed  $style     The style to set.
     *
     * @return mixed The result of the set style operation.
     */
    public function setStyle($wbobject, $style)
    {
        return $this->callWinBinder( 'wb_set_style', array($wbobject, $style) );
    }

    /**
     * Sets the range for the specified WinBinder object.
     *
     * @param   mixed  $wbobject  The WinBinder object to set the range for.
     * @param   int    $min       The minimum value of the range.
     * @param   int    $max       The maximum value of the range.
     *
     * @return mixed The result of the set range operation.
     */
    public function setRange($wbobject, $min, $max)
    {
        return $this->callWinBinder( 'wb_set_range', array($wbobject, $min, $max) );
    }

    /**
     * Opens a system dialog to select a path.
     *
     * @param   mixed        $parent  The parent object for the dialog.
     * @param   string       $title   The title of the dialog.
     * @param   string|null  $path    Optional. The initial path for the dialog.
     *
     * @return mixed The selected path.
     */
    public function sysDlgPath($parent, $title, $path = null)
    {
        return $this->callWinBinder( 'wb_sys_dlg_path', array($parent, $title, $path) );
    }

    /**
     * Opens a system dialog to select a file.
     *
     * @param   mixed        $parent  The parent object for the dialog.
     * @param   string       $title   The title of the dialog.
     * @param   string|null  $filter  Optional. The file filter for the dialog.
     * @param   string|null  $path    Optional. The initial path for the dialog.
     *
     * @return mixed The selected file.
     */
    public function sysDlgOpen($parent, $title, $filter = null, $path = null)
    {
        return $this->callWinBinder( 'wb_sys_dlg_open', array($parent, $title, $filter, $path) );
    }

    /**
     * Creates a label control.
     *
     * @param   mixed       $parent   The parent control.
     * @param   string      $caption  The text to display on the label.
     * @param   int         $xPos     The x-coordinate of the label.
     * @param   int         $yPos     The y-coordinate of the label.
     * @param   int|null    $width    The width of the label. Defaults to 120.
     * @param   int|null    $height   The height of the label. Defaults to 25.
     * @param   mixed|null  $style    The style of the label.
     * @param   mixed|null  $params   Additional parameters for the label.
     *
     * @return mixed The created label control.
     */
    public function createLabel($parent, $caption, $xPos, $yPos, $width = null, $height = null, $style = null, $params = null)
    {
        $caption = str_replace( self::NEW_LINE, PHP_EOL, $caption );
        $width   = $width == null ? 120 : $width;
        $height  = $height == null ? 25 : $height;

        return $this->createControl( $parent, Label, $caption, $xPos, $yPos, $width, $height, $style, $params );
    }

    /**
     * Creates an input text control.
     *
     * @param   mixed       $parent     The parent control.
     * @param   string      $value      The initial value of the input text.
     * @param   int         $xPos       The x-coordinate of the input text.
     * @param   int         $yPos       The y-coordinate of the input text.
     * @param   int|null    $width      The width of the input text. Defaults to 120.
     * @param   int|null    $height     The height of the input text. Defaults to 25.
     * @param   int|null    $maxLength  The maximum length of the input text.
     * @param   mixed|null  $style      The style of the input text.
     * @param   mixed|null  $params     Additional parameters for the input text.
     *
     * @return mixed The created input text control.
     */
    public function createInputText($parent, $value, $xPos, $yPos, $width = null, $height = null, $maxLength = null, $style = null, $params = null)
    {
        $value     = str_replace( self::NEW_LINE, PHP_EOL, $value );
        $width     = $width == null ? 120 : $width;
        $height    = $height == null ? 25 : $height;
        $inputText = $this->createControl( $parent, EditBox, (string) $value, $xPos, $yPos, $width, $height, $style, $params );
        if ( is_numeric( $maxLength ) && $maxLength > 0 ) {
            $this->setMaxLength( $inputText[self::CTRL_OBJ], $maxLength );
        }

        return $inputText;
    }

    /**
     * Creates an edit box control.
     *
     * @param   mixed       $parent  The parent control.
     * @param   string      $value   The initial value of the edit box.
     * @param   int         $xPos    The x-coordinate of the edit box.
     * @param   int         $yPos    The y-coordinate of the edit box.
     * @param   int|null    $width   The width of the edit box. Defaults to 540.
     * @param   int|null    $height  The height of the edit box. Defaults to 340.
     * @param   mixed|null  $style   The style of the edit box.
     * @param   mixed|null  $params  Additional parameters for the edit box.
     *
     * @return mixed The created edit box control.
     */
    public function createEditBox($parent, $value, $xPos, $yPos, $width = null, $height = null, $style = null, $params = null)
    {
        $value   = str_replace( self::NEW_LINE, PHP_EOL, $value );
        $width   = $width == null ? 540 : $width;
        $height  = $height == null ? 340 : $height;
        $editBox = $this->createControl( $parent, RTFEditBox, (string) $value, $xPos, $yPos, $width, $height, $style, $params );

        return $editBox;
    }

    /**
     * Creates a hyperlink control.
     *
     * @param   mixed       $parent   The parent control.
     * @param   string      $caption  The text to display on the hyperlink.
     * @param   int         $xPos     The x-coordinate of the hyperlink.
     * @param   int         $yPos     The y-coordinate of the hyperlink.
     * @param   int|null    $width    The width of the hyperlink. Defaults to 120.
     * @param   int|null    $height   The height of the hyperlink. Defaults to 15.
     * @param   mixed|null  $style    The style of the hyperlink.
     * @param   mixed|null  $params   Additional parameters for the hyperlink.
     *
     * @return mixed The created hyperlink control.
     */
    public function createHyperLink($parent, $caption, $xPos, $yPos, $width = null, $height = null, $style = null, $params = null)
    {
        $caption   = str_replace( self::NEW_LINE, PHP_EOL, $caption );
        $width     = $width == null ? 120 : $width;
        $height    = $height == null ? 15 : $height;
        $hyperLink = $this->createControl( $parent, HyperLink, (string) $caption, $xPos, $yPos, $width, $height, $style, $params );
        $this->setCursor( $hyperLink[self::CTRL_OBJ], self::CURSOR_FINGER );

        return $hyperLink;
    }

    /**
     * Creates a radio button control.
     *
     * @param   mixed     $parent      The parent control.
     * @param   string    $caption     The text to display on the radio button.
     * @param   bool      $checked     Whether the radio button is checked.
     * @param   int       $xPos        The x-coordinate of the radio button.
     * @param   int       $yPos        The y-coordinate of the radio button.
     * @param   int|null  $width       The width of the radio button. Defaults to 120.
     * @param   int|null  $height      The height of the radio button. Defaults to 25.
     * @param   bool      $startGroup  Whether this radio button starts a new group.
     *
     * @return mixed The created radio button control.
     */
    public function createRadioButton($parent, $caption, $checked, $xPos, $yPos, $width = null, $height = null, $startGroup = false)
    {
        $caption = str_replace( self::NEW_LINE, PHP_EOL, $caption );
        $width   = $width == null ? 120 : $width;
        $height  = $height == null ? 25 : $height;

        return $this->createControl( $parent, RadioButton, (string) $caption, $xPos, $yPos, $width, $height, $startGroup ? WBC_GROUP : null, $checked ? 1 : 0 );
    }

    /**
     * Creates a button control.
     *
     * @param   mixed       $parent   The parent control.
     * @param   string      $caption  The text to display on the button.
     * @param   int         $xPos     The x-coordinate of the button.
     * @param   int         $yPos     The y-coordinate of the button.
     * @param   int|null    $width    The width of the button. Defaults to 80.
     * @param   int|null    $height   The height of the button. Defaults to 25.
     * @param   mixed|null  $style    The style of the button.
     * @param   mixed|null  $params   Additional parameters for the button.
     *
     * @return mixed The created button control.
     */
    public function createButton($parent, $caption, $xPos, $yPos, $width = null, $height = null, $style = null, $params = null)
    {
        $width  = $width == null ? 80 : $width;
        $height = $height == null ? 25 : $height;

        return $this->createControl( $parent, PushButton, $caption, $xPos, $yPos, $width, $height, $style, $params );
    }

    /**
     * Creates a progress bar control.
     *
     * @param   mixed       $parent  The parent control.
     * @param   int         $max     The maximum value of the progress bar.
     * @param   int         $xPos    The x-coordinate of the progress bar.
     * @param   int         $yPos    The y-coordinate of the progress bar.
     * @param   int|null    $width   The width of the progress bar. Defaults to 200.
     * @param   int|null    $height  The height of the progress bar. Defaults to 15.
     * @param   mixed|null  $style   The style of the progress bar.
     * @param   mixed|null  $params  Additional parameters for the progress bar.
     *
     * @return mixed The created progress bar control.
     */
    public function createProgressBar($parent, $max, $xPos, $yPos, $width = null, $height = null, $style = null, $params = null)
    {
        global $bearsamppLang;

        $width       = $width == null ? 200 : $width;
        $height      = $height == null ? 15 : $height;
        $progressBar = $this->createControl( $parent, Gauge, $bearsamppLang->getValue( Lang::LOADING ), $xPos, $yPos, $width, $height, $style, $params );

        $this->setRange( $progressBar[self::CTRL_OBJ], 0, $max );
        $this->gauge[$progressBar[self::CTRL_OBJ]] = 0;

        return $progressBar;
    }

    /**
     * Increments the value of the progress bar by one.
     *
     * @param   mixed  $progressBar  The progress bar control.
     *
     * @return void
     */
    public function incrProgressBar($progressBar)
    {
        $this->setProgressBarValue( $progressBar, self::INCR_PROGRESS_BAR );
    }

    /**
     * Resets the value of the progress bar to zero.
     *
     * @param   mixed  $progressBar  The progress bar control.
     *
     * @return void
     */
    public function resetProgressBar($progressBar)
    {
        $this->setProgressBarValue( $progressBar, 0 );
    }

    /**
     * Sets the value of the progress bar.
     *
     * @param   mixed       $progressBar  The progress bar control.
     * @param   int|string  $value        The value to set. If the value is self::INCR_PROGRESS_BAR, the progress bar is incremented by one.
     *
     * @return void
     */
    public function setProgressBarValue($progressBar, $value)
    {
        if ( $progressBar != null && isset( $progressBar[self::CTRL_OBJ] ) && isset( $this->gauge[$progressBar[self::CTRL_OBJ]] ) ) {
            if ( strval( $value ) == self::INCR_PROGRESS_BAR ) {
                $value = $this->gauge[$progressBar[self::CTRL_OBJ]] + 1;
            }
            if ( is_numeric( $value ) ) {
                $this->gauge[$progressBar[self::CTRL_OBJ]] = $value;
                $this->setValue( $progressBar[self::CTRL_OBJ], $value );
            }
        }
    }

    /**
     * Sets the maximum value of the progress bar.
     *
     * @param   mixed  $progressBar  The progress bar control.
     * @param   int    $max          The maximum value to set.
     *
     * @return void
     */
    public function setProgressBarMax($progressBar, $max)
    {
        $this->setRange( $progressBar[self::CTRL_OBJ], 0, $max );
    }

    /**
     * Displays a message box.
     *
     * @param   string       $message  The message to display.
     * @param   int          $type     The type of the message box.
     * @param   string|null  $title    The title of the message box. Defaults to the default title.
     *
     * @return mixed The created message box control.
     */
    public function messageBox($message, $type, $title = null)
    {
        global $bearsamppCore;

        $message    = str_replace( self::NEW_LINE, PHP_EOL, $message );
        $messageBox = $this->callWinBinder( 'wb_message_box', array(
            null, strlen( $message ) < 64 ? str_pad( $message, 64 ) : $message, // Pad message to display entire title
            $title == null ? $this->defaultTitle : $this->defaultTitle . ' - ' . $title, $type
        ) );

        // TODO why does this create an error?
        // Set tiny window icon
        $this->setImage( $messageBox, $bearsamppCore->getResourcesPath() . '/homepage/img/icons/app.ico' );

        return $messageBox;
    }

    /**
     * Displays an informational message box.
     *
     * @param   string       $message  The message to display.
     * @param   string|null  $title    The title of the message box. Defaults to the default title.
     *
     * @return mixed The created message box control.
     */
    public function messageBoxInfo($message, $title = null)
    {
        return $this->messageBox( $message, self::BOX_INFO, $title );
    }

    /**
     * Displays an OK message box.
     *
     * @param   string       $message  The message to display.
     * @param   string|null  $title    The title of the message box. Defaults to the default title.
     *
     * @return mixed The created message box control.
     */
    public function messageBoxOk($message, $title = null)
    {
        return $this->messageBox( $message, self::BOX_OK, $title );
    }

    /**
     * Displays an OK/Cancel message box.
     *
     * @param   string       $message  The message to display.
     * @param   string|null  $title    The title of the message box. Defaults to the default title.
     *
     * @return mixed The created message box control.
     */
    public function messageBoxOkCancel($message, $title = null)
    {
        return $this->messageBox( $message, self::BOX_OKCANCEL, $title );
    }

    /**
     * Displays a question message box.
     *
     * @param   string       $message  The message to display.
     * @param   string|null  $title    The title of the message box. Defaults to the default title.
     *
     * @return mixed The created message box control.
     */
    public function messageBoxQuestion($message, $title = null)
    {
        return $this->messageBox( $message, self::BOX_QUESTION, $title );
    }

    /**
     * Displays an error message box.
     *
     * @param   string       $message  The message to display.
     * @param   string|null  $title    The title of the message box. Defaults to the default title.
     *
     * @return mixed The created message box control.
     */
    public function messageBoxError($message, $title = null)
    {
        return $this->messageBox( $message, self::BOX_ERROR, $title );
    }

    /**
     * Displays a warning message box.
     *
     * @param   string       $message  The message to display.
     * @param   string|null  $title    The title of the message box. Defaults to the default title.
     *
     * @return mixed The created message box control.
     */
    public function messageBoxWarning($message, $title = null)
    {
        return $this->messageBox( $message, self::BOX_WARNING, $title );
    }

    /**
     * Displays a Yes/No message box.
     *
     * @param   string       $message  The message to display.
     * @param   string|null  $title    The title of the message box. Defaults to the default title.
     *
     * @return mixed The created message box control.
     */
    public function messageBoxYesNo($message, $title = null)
    {
        return $this->messageBox( $message, self::BOX_YESNO, $title );
    }

    /**
     * Displays a Yes/No/Cancel message box.
     *
     * @param   string       $message  The message to display.
     * @param   string|null  $title    The title of the message box. Defaults to the default title.
     *
     * @return mixed The created message box control.
     */
    public function messageBoxYesNoCancel($message, $title = null)
    {
        return $this->messageBox( $message, self::BOX_YESNOCANCEL, $title );
    }
}

/**
 * Event handler for WinBinder events.
 *
 * @param   mixed  $window  The window control.
 * @param   int    $id      The event ID.
 * @param   mixed  $ctrl    The control that triggered the event.
 * @param   mixed  $param1  The first parameter of the event.
 * @param   mixed  $param2  The second parameter of the event.
 *
 * @return void
 */
function __winbinderEventHandler($window, $id, $ctrl, $param1, $param2)
{
    global $bearsamppWinbinder;

    if ( $bearsamppWinbinder->callback[$window][2] != null ) {
        $bearsamppWinbinder->destroyTimer( $window, $bearsamppWinbinder->callback[$window][2][0] );
    }

    call_user_func_array(
        array($bearsamppWinbinder->callback[$window][0], $bearsamppWinbinder->callback[$window][1]),
        array($window, $id, $ctrl, $param1, $param2)
    );
}
