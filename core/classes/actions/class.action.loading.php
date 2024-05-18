<?php

/**
 * Manages the loading process with a graphical user interface.
 *
 * This class is responsible for creating and managing a window that displays the loading progress of the application.
 * It includes a progress bar and an image that updates as the application loads. The class handles the incrementation
 * of the progress bar and the drawing of an image within the window. It also handles user interactions and system events
 * during the loading process.
 *
 * @global object $bearsamppCore      Access to core functionalities of the application.
 * @global object $bearsamppLang      Access to language settings and values.
 * @global object $bearsamppWinbinder Access to WinBinder functions for window and control management.
 */
class ActionLoading
{
    const WINDOW_WIDTH = 340;
    const WINDOW_HEIGHT = 65;
    const GAUGE = 20;

    private $wbWindow;
    private $wbProgressBar;

    /**
     * Increments the progress bar by a specified number of steps and updates the window with an image.
     *
     * This method increments the progress bar within the window by the number of steps specified in the `$nb` parameter.
     * After each increment, it draws an image at a specified location within the window. Once all increments are completed,
     * the method waits for any pending operations to complete before proceeding.
     *
     * @param   int   $nb                 The number of increments to apply to the progress bar. Defaults to 1.
     *
     * @global object $bearsamppCore      Used to access the application's core functionalities.
     * @global object $bearsamppWinbinder Used to interact with the WinBinder library for window management.
     */
    public function __construct($args)
    {
        global $bearsamppCore, $bearsamppLang, $bearsamppWinbinder;

        $bearsamppWinbinder->reset();
        $bearsamppCore->addLoadingPid( Win32Ps::getCurrentPid() );

        // Screen infos
        $screenArea   = explode( ' ', $bearsamppWinbinder->getSystemInfo( WinBinder::SYSINFO_WORKAREA ) );
        $screenWidth  = intval( $screenArea[2] );
        $screenHeight = intval( $screenArea[3] );
        $xPos         = $screenWidth - self::WINDOW_WIDTH;
        $yPos         = $screenHeight - self::WINDOW_HEIGHT - 5;

        $this->wbWindow = $bearsamppWinbinder->createWindow( null, ToolDialog, null, $xPos, $yPos, self::WINDOW_WIDTH, self::WINDOW_HEIGHT, WBC_TOP, null );

        $bearsamppWinbinder->createLabel( $this->wbWindow, $bearsamppLang->getValue( Lang::LOADING ), 42, 2, 295, null, WBC_LEFT );
        $this->wbProgressBar = $bearsamppWinbinder->createProgressBar( $this->wbWindow, self::GAUGE, 42, 20, 290, 15 );

        $bearsamppWinbinder->setHandler( $this->wbWindow, $this, 'processLoading', 10 );
        $bearsamppWinbinder->mainLoop();
    }

    /**
     * Increments the progress bar and updates the window with an image.
     *
     * This method increments the progress bar by the specified number of steps. For each step, it also draws an image
     * in the window. After completing all increments, the method waits for any pending operations to complete.
     *
     * @param int $nb The number of increments to apply to the progress bar. Defaults to 1.
     *
     * @global object $bearsamppCore Used to access the application's core functionalities.
     * @global object $bearsamppWinbinder Used to interact with the WinBinder library for window management.
     */
    public function incrProgressBar($nb = 1)
    {
        global $bearsamppCore, $bearsamppWinbinder;

        for ( $i = 0; $i < $nb; $i++ ) {
            $bearsamppWinbinder->incrProgressBar( $this->wbProgressBar );
            $bearsamppWinbinder->drawImage( $this->wbWindow, $bearsamppCore->getResourcesPath() . '/bearsampp.bmp', 4, 2, 32, 32 );
        }

        $bearsamppWinbinder->wait();
        $bearsamppWinbinder->wait( $this->wbWindow );
    }

    /**
     * Handles the loading process for the application window.
     * This method is responsible for managing the progress bar and handling the close action.
     *
     * @param   resource  $window  The window resource that this method is associated with.
     * @param   int       $id      The identifier of the control that triggered the event.
     * @param   int       $ctrl    The control identifier.
     * @param   mixed     $param1  Additional parameter 1 (not used in this method).
     * @param   mixed     $param2  Additional parameter 2 (not used in this method).
     */
    public function processLoading($window, $id, $ctrl, $param1, $param2)
    {
        global $bearsamppRoot, $bearsamppWinbinder;

        switch ( $id ) {
            case IDCLOSE:
                Win32Ps::kill( Win32Ps::getCurrentPid() );
                break;
        }

        while ( true ) {
            $bearsamppRoot->removeErrorHandling();
            $bearsamppWinbinder->resetProgressBar( $this->wbProgressBar );
            usleep( 100000 );
            for ( $i = 0; $i < self::GAUGE; $i++ ) {
                $this->incrProgressBar();
                usleep( 100000 );
            }
        }
    }
}
