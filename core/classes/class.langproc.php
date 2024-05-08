<?php

/**
 * Language Processing Class
 *
 * Handles the loading and retrieval of language-specific settings and values from language files.
 */
class LangProc
{
    /**
     * @var string Current language setting
     */
    private $current;

    /**
     * @var array|null Raw language data loaded from the language file
     */
    private $raw;

    /**
     * Constructor that initializes the language processing by loading language data.
     */
    public function __construct()
    {
        $this->load();
    }

    /**
     * Loads the language file based on the default language or a specified language.
     *
     * Utilizes global variables from the $bearsamppCore and $bearsamppConfig to determine
     * the path and default language settings.
     */
    public function load()
    {
        global $bearsamppCore, $bearsamppConfig;
        $this->raw = null;

        $this->current = $bearsamppConfig->getDefaultLang();
        if ( !empty( $this->current ) && in_array( $this->current, $this->getList() ) )
        {
            $this->current = $bearsamppConfig->getLang();
        }

        $this->raw = parse_ini_file( $bearsamppCore->getLangsPath() . '/' . $this->current . '.lang' );
    }

    /**
     * Gets the current language setting.
     *
     * @return string The current language
     */
    public function getCurrent()
    {
        return $this->current;
    }

    /**
     * Retrieves a list of available languages by scanning the language directory.
     *
     * @return array List of available languages without file extension
     */
    public function getList()
    {
        global $bearsamppCore;
        $result = array();

        $handle = @opendir( $bearsamppCore->getLangsPath() );
        if ( !$handle )
        {
            return $result;
        }

        while ( false !== ($file = readdir( $handle )) )
        {
            if ( $file != '.' && $file != '..' && Util::endWith( $file, '.lang' ) )
            {
                $result[] = str_replace( '.lang', '', $file );
            }
        }

        closedir( $handle );

        return $result;
    }

    /**
     * Retrieves the value for a specified key from the loaded language data.
     *
     * If the key does not exist, logs an error and returns the key itself.
     *
     * @param   string  $key  The key for the language-specific value
     *
     * @return string The value associated with the key, or the key itself if not found
     */
    public function getValue($key)
    {
        global $bearsamppRoot;

        if ( !isset( $this->raw[$key] ) )
        {
            $content = '[' . date( 'Y-m-d H:i:s', time() ) . '] ';
            $content .= 'ERROR: Lang var missing ' . $key;
            $content .= ' for ' . $this->current . ' language.' . PHP_EOL;
            file_put_contents( $bearsamppRoot->getErrorLogFilePath(), $content, FILE_APPEND );

            return $key;
        }

        // Special chars not handled by Aestan Tray Menu
        $replace = array('ő', 'Ő', 'ű', 'Ű');
        $with    = array('o', 'O', 'u', 'U');

        return str_replace( $replace, $with, $this->raw[$key] );
    }
}
