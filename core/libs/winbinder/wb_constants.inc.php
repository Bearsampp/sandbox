
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
    const WBC_TITLE = 0x00000001;
    const NOCOLOR = 0x20000000;

    // Screen constants from wb_resources.inc.php
    const WB_KX_SCREEN = 1.498; // Determined through trial and error
    const WB_KY_SCREEN = 1.625; // Determined through trial and error

    // Windows constants from WIN.H
    const WS_VISIBLE = 0x10000000;
    const WS_DISABLED = 0x08000000;
    const WS_GROUP = 0x00020000;
    const WS_EX_STATICEDGE = 0x00020000;

    // Button styles
    const BS_PUSHBUTTON = 0x00;
    const BS_CHECKBOX = 0x02;
    const BS_AUTOCHECKBOX = 0x03;
    const BS_RADIOBUTTON = 0x04;
    const BS_GROUPBOX = 0x07;
    const BS_AUTORADIOBUTTON = 0x09;
    const BS_ICON = 0x40;
    const BS_BITMAP = 0x80;

    // Edit control styles
    const ES_NUMBER = 0x2000;
    const ES_PASSWORD = 0x20;
    const ES_READONLY = 0x0800;
    const ES_UPPERCASE = 0x08;
    const ES_LEFT = 0x0;
    const ES_CENTER = 0x01;
    const ES_RIGHT = 0x02;
    const ES_MULTILINE = 0x04;

    // Static styles
    const SS_LEFT = 0x00;
    const SS_CENTER = 0x01;
    const SS_RIGHT = 0x02;
    const SS_ETCHEDHORZ = 0x10;
    const SS_ETCHEDVERT = 0x11;
    const SS_ETCHEDFRAME = 0x12;
    const SS_ICON = 0x03;
    const SS_BITMAP = 0x0E;
    const SS_LEFTNOWORDWRAP = 0x0C;
    const SS_WORDELLIPSIS = 0xC000;

    // Other styles
    const CBS_SORT = 0x100;
    const CBS_DROPDOWNLIST = 3;
    const LBS_SORT = 2;
    const LVS_NOSORTHEADER = 0x00008000;
    const LVS_GRIDLINES = 0x00800000;    // Actually WS_BORDER
    const LVS_CHECKBOXES = 0x00000800;   // Actually LVS_ALIGNLEFT
    const LVS_SINGLESEL = 0x00000004;
    const TBS_AUTOTICKS = 1;

    // Constants from wb_windows.inc.php
    const BM_SETCHECK = 241;
    const LVM_FIRST = 0x1000;
    const LVM_DELETEALLITEMS = self::LVM_FIRST + 9;
    const LVM_GETITEMCOUNT = self::LVM_FIRST + 4;
    const LVM_GETITEMSTATE = self::LVM_FIRST + 44;
    const LVM_GETSELECTEDCOUNT = self::LVM_FIRST + 50;
    const LVIS_SELECTED = 2;
    const TCM_GETCURSEL = 4875;
    const CB_FINDSTRINGEXACT = 344;
    const CB_SETCURSEL = 334;
    const LB_FINDSTRINGEXACT = 418;
    const LB_SETCURSEL = 390;
    const TCM_SETCURSEL = 4876;
    const WM_SETTEXT = 12;

    // Window class constants
    const AppWindow = 'AppWindow';
    const ResizableWindow = 'ResizableWindow';
    const NakedWindow = 'NakedWindow';
    const ModalDialog = 'ModalDialog';
    const ModelessDialog = 'ModelessDialog';
    const ToolDialog = 'ToolDialog';
    const Accel = 'Accel';
    const ToolBar = 'ToolBar';
    const Menu = 'Menu';
    const HyperLink = 'HyperLink';
    const ComboBox = 'ComboBox';
    const ListBox = 'ListBox';
    const ListView = 'ListView';
    const TreeView = 'TreeView';
    const Gauge = 'Gauge';
    const Slider = 'Slider';
    const ScrollBar = 'ScrollBar';
    const PushButton = 'PushButton';
    const RadioButton = 'RadioButton';
    const CheckBox = 'CheckBox';
    const Frame = 'Frame';
    const Label = 'Label';
    const EditBox = 'EditBox';
    const RTFEditBox = 'RTFEditBox';
    const TabControl = 'TabControl';
    const StatusBar = 'StatusBar';
    const Calendar = 'Calendar';
    const Spinner = 'Spinner';

    // Path constants
    const PATH_RES = __DIR__ . '/../../resources/';

    // Color constants
    const BLACK = 0x000000;
    const BLUE = 0x0000FF;
    const GREEN = 0x00FF00;
    const CYAN = 0x00FFFF;
    const RED = 0xFF0000;
    const MAGENTA = 0xFF00FF;
    const YELLOW = 0xFFFF00;
    const WHITE = 0xFFFFFF;
    const DARKBLUE = 0x000080;
    const DARKGREEN = 0x008000;
    const DARKCYAN = 0x008080;
    const DARKRED = 0x800000;
    const DARKMAGENTA = 0x800080;
    const DARKYELLOW = 0x808000;
    const DARKGRAY = 0x808080;
    const LIGHTGRAY = 0xC0C0C0;
}
