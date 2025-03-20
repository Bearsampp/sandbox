<?php

/*******************************************************************************

 WINBINDER - The native Windows binding for PHP for PHP

 Copyright © Hypervisual - see LICENSE.TXT for details
 Author: Rubem Pechansky (http://winbinder.org/contact.php)

 WinBinder Constants

*******************************************************************************/

// Define all WinBinder constants in a class for better organization and performance
class WB_Constants {
    // Window positioning constants
    const WBC_CENTER = 0x10000000;
    const WBC_DEFAULTPOS = 0x20000000;
    const WBC_MINIMIZED = 0x30000000;
    const WBC_MAXIMIZED = 0x40000000;

    // Window style constants
    const WBC_VISIBLE = 0x00000001;
    const WBC_INVISIBLE = 0x00000002;
    const WBC_ENABLED = 0x00000004;
    const WBC_DISABLED = 0x00000008;
    const WBC_BORDER = 0x00000010;
    const WBC_GROUP = 0x00000020;
    const WBC_READONLY = 0x00000040;
    const WBC_MASKED = 0x00000080;
    const WBC_MULTILINE = 0x00000100;
    const WBC_NUMBER = 0x00000200;
    const WBC_SORT = 0x00000400;
    const WBC_SINGLE = 0x00000800;
    const WBC_LINES = 0x00001000;
    const WBC_CHECKBOXES = 0x00002000;
    const WBC_ELLIPSIS = 0x00004000;
    const WBC_IMAGE = 0x00008000;
    const WBC_LEFT = 0x00010000;
    const WBC_RIGHT = 0x00020000;
    const WBC_NOTIFY = 0x00080000;
    const WBC_AUTOREPEAT = 0x00100000;
    const WBC_TRANSPARENT = 0x00200000;
    const WBC_MULTISELECT = 0x00400000;
    const WBC_NOHEADER = 0x00800000;
    const WBC_DBLCLICK = 0x01000000;
    const WBC_DRAW = 0x02000000;
    const WBC_TASK = 0x04000000;
    const WBC_AUTOCHECK = 0x08000000;

    // Message box constants
    const WBC_INFO = 0;
    const WBC_OK = 0;
    const WBC_OKCANCEL = 1;
    const WBC_QUESTION = 32;
    const WBC_STOP = 16;
    const WBC_WARNING = 48;
    const WBC_YESNO = 4;
    const WBC_YESNOCANCEL = 3;

    // Message box return values
    const WBC_ID_OK = 1;
    const WBC_ID_CANCEL = 2;
    const WBC_ID_ABORT = 3;
    const WBC_ID_RETRY = 4;
    const WBC_ID_IGNORE = 5;
    const WBC_ID_YES = 6;
    const WBC_ID_NO = 7;

    // Other constants
    const NOCOLOR = 0x20000000;
}

// For backward compatibility, define the constants in the global namespace
// This ensures existing code that uses these constants will continue to work

// Window positioning constants
define("WBC_CENTER", WB_Constants::WBC_CENTER);
define("WBC_DEFAULTPOS", WB_Constants::WBC_DEFAULTPOS);
define("WBC_MINIMIZED", WB_Constants::WBC_MINIMIZED);
define("WBC_MAXIMIZED", WB_Constants::WBC_MAXIMIZED);

// Window style constants
define("WBC_VISIBLE", WB_Constants::WBC_VISIBLE);
define("WBC_INVISIBLE", WB_Constants::WBC_INVISIBLE);
define("WBC_ENABLED", WB_Constants::WBC_ENABLED);
define("WBC_DISABLED", WB_Constants::WBC_DISABLED);
define("WBC_BORDER", WB_Constants::WBC_BORDER);
define("WBC_GROUP", WB_Constants::WBC_GROUP);
define("WBC_READONLY", WB_Constants::WBC_READONLY);
define("WBC_MASKED", WB_Constants::WBC_MASKED);
define("WBC_MULTILINE", WB_Constants::WBC_MULTILINE);
define("WBC_NUMBER", WB_Constants::WBC_NUMBER);
define("WBC_SORT", WB_Constants::WBC_SORT);
define("WBC_SINGLE", WB_Constants::WBC_SINGLE);
define("WBC_LINES", WB_Constants::WBC_LINES);
define("WBC_CHECKBOXES", WB_Constants::WBC_CHECKBOXES);
define("WBC_ELLIPSIS", WB_Constants::WBC_ELLIPSIS);
define("WBC_IMAGE", WB_Constants::WBC_IMAGE);
define("WBC_LEFT", WB_Constants::WBC_LEFT);
define("WBC_RIGHT", WB_Constants::WBC_RIGHT);
define("WBC_NOTIFY", WB_Constants::WBC_NOTIFY);
define("WBC_AUTOREPEAT", WB_Constants::WBC_AUTOREPEAT);
define("WBC_TRANSPARENT", WB_Constants::WBC_TRANSPARENT);
define("WBC_MULTISELECT", WB_Constants::WBC_MULTISELECT);
define("WBC_NOHEADER", WB_Constants::WBC_NOHEADER);
define("WBC_DBLCLICK", WB_Constants::WBC_DBLCLICK);
define("WBC_DRAW", WB_Constants::WBC_DRAW);
define("WBC_TASK", WB_Constants::WBC_TASK);
define("WBC_AUTOCHECK", WB_Constants::WBC_AUTOCHECK);

// Message box constants
define("WBC_INFO", WB_Constants::WBC_INFO);
define("WBC_OK", WB_Constants::WBC_OK);
define("WBC_OKCANCEL", WB_Constants::WBC_OKCANCEL);
define("WBC_QUESTION", WB_Constants::WBC_QUESTION);
define("WBC_STOP", WB_Constants::WBC_STOP);
define("WBC_WARNING", WB_Constants::WBC_WARNING);
define("WBC_YESNO", WB_Constants::WBC_YESNO);
define("WBC_YESNOCANCEL", WB_Constants::WBC_YESNOCANCEL);

// Message box return values
define("WBC_ID_OK", WB_Constants::WBC_ID_OK);
define("WBC_ID_CANCEL", WB_Constants::WBC_ID_CANCEL);
define("WBC_ID_ABORT", WB_Constants::WBC_ID_ABORT);
define("WBC_ID_RETRY", WB_Constants::WBC_ID_RETRY);
define("WBC_ID_IGNORE", WB_Constants::WBC_ID_IGNORE);
define("WBC_ID_YES", WB_Constants::WBC_ID_YES);
define("WBC_ID_NO", WB_Constants::WBC_ID_NO);

// Other constants
define("NOCOLOR", WB_Constants::NOCOLOR);

?>
