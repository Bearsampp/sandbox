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
    const PATH_RES = './resources/';

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

// For backward compatibility, define the constants in the global namespace
// This ensures existing code that uses these constants will continue to work

// Window positioning constants
if (!defined("WBC_CENTER")) define("WBC_CENTER", WB_Constants::WBC_CENTER);
if (!defined("WBC_DEFAULTPOS")) define("WBC_DEFAULTPOS", WB_Constants::WBC_DEFAULTPOS);
if (!defined("WBC_MINIMIZED")) define("WBC_MINIMIZED", WB_Constants::WBC_MINIMIZED);
if (!defined("WBC_MAXIMIZED")) define("WBC_MAXIMIZED", WB_Constants::WBC_MAXIMIZED);

// Window style constants
if (!defined("WBC_VISIBLE")) define("WBC_VISIBLE", WB_Constants::WBC_VISIBLE);
if (!defined("WBC_INVISIBLE")) define("WBC_INVISIBLE", WB_Constants::WBC_INVISIBLE);
if (!defined("WBC_ENABLED")) define("WBC_ENABLED", WB_Constants::WBC_ENABLED);
if (!defined("WBC_DISABLED")) define("WBC_DISABLED", WB_Constants::WBC_DISABLED);
if (!defined("WBC_BORDER")) define("WBC_BORDER", WB_Constants::WBC_BORDER);
if (!defined("WBC_GROUP")) define("WBC_GROUP", WB_Constants::WBC_GROUP);
if (!defined("WBC_READONLY")) define("WBC_READONLY", WB_Constants::WBC_READONLY);
if (!defined("WBC_MASKED")) define("WBC_MASKED", WB_Constants::WBC_MASKED);
if (!defined("WBC_MULTILINE")) define("WBC_MULTILINE", WB_Constants::WBC_MULTILINE);
if (!defined("WBC_NUMBER")) define("WBC_NUMBER", WB_Constants::WBC_NUMBER);
if (!defined("WBC_SORT")) define("WBC_SORT", WB_Constants::WBC_SORT);
if (!defined("WBC_SINGLE")) define("WBC_SINGLE", WB_Constants::WBC_SINGLE);
if (!defined("WBC_LINES")) define("WBC_LINES", WB_Constants::WBC_LINES);
if (!defined("WBC_CHECKBOXES")) define("WBC_CHECKBOXES", WB_Constants::WBC_CHECKBOXES);
if (!defined("WBC_ELLIPSIS")) define("WBC_ELLIPSIS", WB_Constants::WBC_ELLIPSIS);
if (!defined("WBC_IMAGE")) define("WBC_IMAGE", WB_Constants::WBC_IMAGE);
if (!defined("WBC_LEFT")) define("WBC_LEFT", WB_Constants::WBC_LEFT);
if (!defined("WBC_RIGHT")) define("WBC_RIGHT", WB_Constants::WBC_RIGHT);
if (!defined("WBC_NOTIFY")) define("WBC_NOTIFY", WB_Constants::WBC_NOTIFY);
if (!defined("WBC_AUTOREPEAT")) define("WBC_AUTOREPEAT", WB_Constants::WBC_AUTOREPEAT);
if (!defined("WBC_TRANSPARENT")) define("WBC_TRANSPARENT", WB_Constants::WBC_TRANSPARENT);
if (!defined("WBC_MULTISELECT")) define("WBC_MULTISELECT", WB_Constants::WBC_MULTISELECT);
if (!defined("WBC_NOHEADER")) define("WBC_NOHEADER", WB_Constants::WBC_NOHEADER);
if (!defined("WBC_DBLCLICK")) define("WBC_DBLCLICK", WB_Constants::WBC_DBLCLICK);
if (!defined("WBC_DRAW")) define("WBC_DRAW", WB_Constants::WBC_DRAW);
if (!defined("WBC_TASK")) define("WBC_TASK", WB_Constants::WBC_TASK);
if (!defined("WBC_AUTOCHECK")) define("WBC_AUTOCHECK", WB_Constants::WBC_AUTOCHECK);

// Message box constants
if (!defined("WBC_INFO")) define("WBC_INFO", WB_Constants::WBC_INFO);
if (!defined("WBC_OK")) define("WBC_OK", WB_Constants::WBC_OK);
if (!defined("WBC_OKCANCEL")) define("WBC_OKCANCEL", WB_Constants::WBC_OKCANCEL);
if (!defined("WBC_QUESTION")) define("WBC_QUESTION", WB_Constants::WBC_QUESTION);
if (!defined("WBC_STOP")) define("WBC_STOP", WB_Constants::WBC_STOP);
if (!defined("WBC_WARNING")) define("WBC_WARNING", WB_Constants::WBC_WARNING);
if (!defined("WBC_YESNO")) define("WBC_YESNO", WB_Constants::WBC_YESNO);
if (!defined("WBC_YESNOCANCEL")) define("WBC_YESNOCANCEL", WB_Constants::WBC_YESNOCANCEL);

// Message box return values
if (!defined("WBC_ID_OK")) define("WBC_ID_OK", WB_Constants::WBC_ID_OK);
if (!defined("WBC_ID_CANCEL")) define("WBC_ID_CANCEL", WB_Constants::WBC_ID_CANCEL);
if (!defined("WBC_ID_ABORT")) define("WBC_ID_ABORT", WB_Constants::WBC_ID_ABORT);
if (!defined("WBC_ID_RETRY")) define("WBC_ID_RETRY", WB_Constants::WBC_ID_RETRY);
if (!defined("WBC_ID_IGNORE")) define("WBC_ID_IGNORE", WB_Constants::WBC_ID_IGNORE);
if (!defined("WBC_ID_YES")) define("WBC_ID_YES", WB_Constants::WBC_ID_YES);
if (!defined("WBC_ID_NO")) define("WBC_ID_NO", WB_Constants::WBC_ID_NO);

// Other constants
if (!defined("NOCOLOR")) define("NOCOLOR", WB_Constants::NOCOLOR);

// Screen constants from wb_resources.inc.php
if (!defined("WB_KX_SCREEN")) define("WB_KX_SCREEN", WB_Constants::WB_KX_SCREEN);
if (!defined("WB_KY_SCREEN")) define("WB_KY_SCREEN", WB_Constants::WB_KY_SCREEN);

// Windows constants from WIN.H
if (!defined("WS_VISIBLE")) define("WS_VISIBLE", WB_Constants::WS_VISIBLE);
if (!defined("WS_DISABLED")) define("WS_DISABLED", WB_Constants::WS_DISABLED);
if (!defined("WS_GROUP")) define("WS_GROUP", WB_Constants::WS_GROUP);
if (!defined("WS_EX_STATICEDGE")) define("WS_EX_STATICEDGE", WB_Constants::WS_EX_STATICEDGE);

// Button styles
if (!defined("BS_PUSHBUTTON")) define("BS_PUSHBUTTON", WB_Constants::BS_PUSHBUTTON);
if (!defined("BS_CHECKBOX")) define("BS_CHECKBOX", WB_Constants::BS_CHECKBOX);
if (!defined("BS_AUTOCHECKBOX")) define("BS_AUTOCHECKBOX", WB_Constants::BS_AUTOCHECKBOX);
if (!defined("BS_RADIOBUTTON")) define("BS_RADIOBUTTON", WB_Constants::BS_RADIOBUTTON);
if (!defined("BS_GROUPBOX")) define("BS_GROUPBOX", WB_Constants::BS_GROUPBOX);
if (!defined("BS_AUTORADIOBUTTON")) define("BS_AUTORADIOBUTTON", WB_Constants::BS_AUTORADIOBUTTON);
if (!defined("BS_ICON")) define("BS_ICON", WB_Constants::BS_ICON);
if (!defined("BS_BITMAP")) define("BS_BITMAP", WB_Constants::BS_BITMAP);

// Edit control styles
if (!defined("ES_NUMBER")) define("ES_NUMBER", WB_Constants::ES_NUMBER);
if (!defined("ES_PASSWORD")) define("ES_PASSWORD", WB_Constants::ES_PASSWORD);
if (!defined("ES_READONLY")) define("ES_READONLY", WB_Constants::ES_READONLY);
if (!defined("ES_UPPERCASE")) define("ES_UPPERCASE", WB_Constants::ES_UPPERCASE);
if (!defined("ES_LEFT")) define("ES_LEFT", WB_Constants::ES_LEFT);
if (!defined("ES_CENTER")) define("ES_CENTER", WB_Constants::ES_CENTER);
if (!defined("ES_RIGHT")) define("ES_RIGHT", WB_Constants::ES_RIGHT);
if (!defined("ES_MULTILINE")) define("ES_MULTILINE", WB_Constants::ES_MULTILINE);

// Static styles
if (!defined("SS_LEFT")) define("SS_LEFT", WB_Constants::SS_LEFT);
if (!defined("SS_CENTER")) define("SS_CENTER", WB_Constants::SS_CENTER);
if (!defined("SS_RIGHT")) define("SS_RIGHT", WB_Constants::SS_RIGHT);
if (!defined("SS_ETCHEDHORZ")) define("SS_ETCHEDHORZ", WB_Constants::SS_ETCHEDHORZ);
if (!defined("SS_ETCHEDVERT")) define("SS_ETCHEDVERT", WB_Constants::SS_ETCHEDVERT);
if (!defined("SS_ETCHEDFRAME")) define("SS_ETCHEDFRAME", WB_Constants::SS_ETCHEDFRAME);
if (!defined("SS_ICON")) define("SS_ICON", WB_Constants::SS_ICON);
if (!defined("SS_BITMAP")) define("SS_BITMAP", WB_Constants::SS_BITMAP);
if (!defined("SS_LEFTNOWORDWRAP")) define("SS_LEFTNOWORDWRAP", WB_Constants::SS_LEFTNOWORDWRAP);
if (!defined("SS_WORDELLIPSIS")) define("SS_WORDELLIPSIS", WB_Constants::SS_WORDELLIPSIS);

// Other styles
if (!defined("CBS_SORT")) define("CBS_SORT", WB_Constants::CBS_SORT);
if (!defined("CBS_DROPDOWNLIST")) define("CBS_DROPDOWNLIST", WB_Constants::CBS_DROPDOWNLIST);
if (!defined("LBS_SORT")) define("LBS_SORT", WB_Constants::LBS_SORT);
if (!defined("LVS_NOSORTHEADER")) define("LVS_NOSORTHEADER", WB_Constants::LVS_NOSORTHEADER);
if (!defined("LVS_GRIDLINES")) define("LVS_GRIDLINES", WB_Constants::LVS_GRIDLINES);
if (!defined("LVS_CHECKBOXES")) define("LVS_CHECKBOXES", WB_Constants::LVS_CHECKBOXES);
if (!defined("LVS_SINGLESEL")) define("LVS_SINGLESEL", WB_Constants::LVS_SINGLESEL);
if (!defined("TBS_AUTOTICKS")) define("TBS_AUTOTICKS", WB_Constants::TBS_AUTOTICKS);

// Constants from wb_windows.inc.php
if (!defined("BM_SETCHECK")) define("BM_SETCHECK", WB_Constants::BM_SETCHECK);
if (!defined("LVM_FIRST")) define("LVM_FIRST", WB_Constants::LVM_FIRST);
if (!defined("LVM_DELETEALLITEMS")) define("LVM_DELETEALLITEMS", WB_Constants::LVM_DELETEALLITEMS);
if (!defined("LVM_GETITEMCOUNT")) define("LVM_GETITEMCOUNT", WB_Constants::LVM_GETITEMCOUNT);
if (!defined("LVM_GETITEMSTATE")) define("LVM_GETITEMSTATE", WB_Constants::LVM_GETITEMSTATE);
if (!defined("LVM_GETSELECTEDCOUNT")) define("LVM_GETSELECTEDCOUNT", WB_Constants::LVM_GETSELECTEDCOUNT);
if (!defined("LVIS_SELECTED")) define("LVIS_SELECTED", WB_Constants::LVIS_SELECTED);
if (!defined("TCM_GETCURSEL")) define("TCM_GETCURSEL", WB_Constants::TCM_GETCURSEL);
if (!defined("CB_FINDSTRINGEXACT")) define("CB_FINDSTRINGEXACT", WB_Constants::CB_FINDSTRINGEXACT);
if (!defined("CB_SETCURSEL")) define("CB_SETCURSEL", WB_Constants::CB_SETCURSEL);
if (!defined("LB_FINDSTRINGEXACT")) define("LB_FINDSTRINGEXACT", WB_Constants::LB_FINDSTRINGEXACT);
if (!defined("LB_SETCURSEL")) define("LB_SETCURSEL", WB_Constants::LB_SETCURSEL);
if (!defined("TCM_SETCURSEL")) define("TCM_SETCURSEL", WB_Constants::TCM_SETCURSEL);
if (!defined("WM_SETTEXT")) define("WM_SETTEXT", WB_Constants::WM_SETTEXT);

// Window class constants
if (!defined("AppWindow")) define("AppWindow", WB_Constants::AppWindow);
if (!defined("ResizableWindow")) define("ResizableWindow", WB_Constants::ResizableWindow);
if (!defined("ModalDialog")) define("ModalDialog", WB_Constants::ModalDialog);
if (!defined("ModelessDialog")) define("ModelessDialog", WB_Constants::ModelessDialog);
if (!defined("ToolDialog")) define("ToolDialog", WB_Constants::ToolDialog);
if (!defined("Accel")) define("Accel", WB_Constants::Accel);
if (!defined("ToolBar")) define("ToolBar", WB_Constants::ToolBar);
if (!defined("Menu")) define("Menu", WB_Constants::Menu);
if (!defined("HyperLink")) define("HyperLink", WB_Constants::HyperLink);
if (!defined("ComboBox")) define("ComboBox", WB_Constants::ComboBox);
if (!defined("ListBox")) define("ListBox", WB_Constants::ListBox);
if (!defined("ListView")) define("ListView", WB_Constants::ListView);
if (!defined("TreeView")) define("TreeView", WB_Constants::TreeView);
if (!defined("Gauge")) define("Gauge", WB_Constants::Gauge);
if (!defined("Slider")) define("Slider", WB_Constants::Slider);
if (!defined("ScrollBar")) define("ScrollBar", WB_Constants::ScrollBar);
if (!defined("PushButton")) define("PushButton", WB_Constants::PushButton);
if (!defined("RadioButton")) define("RadioButton", WB_Constants::RadioButton);
if (!defined("CheckBox")) define("CheckBox", WB_Constants::CheckBox);
if (!defined("Frame")) define("Frame", WB_Constants::Frame);
if (!defined("Label")) define("Label", WB_Constants::Label);
if (!defined("EditBox")) define("EditBox", WB_Constants::EditBox);
if (!defined("RTFEditBox")) define("RTFEditBox", WB_Constants::RTFEditBox);
if (!defined("TabControl")) define("TabControl", WB_Constants::TabControl);
if (!defined("StatusBar")) define("StatusBar", WB_Constants::StatusBar);
if (!defined("Calendar")) define("Calendar", WB_Constants::Calendar);
if (!defined("Spinner")) define("Spinner", WB_Constants::Spinner);

// Path constants
if (!defined("PATH_RES")) define("PATH_RES", WB_Constants::PATH_RES);

// Color constants
if (!defined("BLACK")) define("BLACK", WB_Constants::BLACK);
if (!defined("BLUE")) define("BLUE", WB_Constants::BLUE);
if (!defined("GREEN")) define("GREEN", WB_Constants::GREEN);
if (!defined("CYAN")) define("CYAN", WB_Constants::CYAN);
if (!defined("RED")) define("RED", WB_Constants::RED);
if (!defined("MAGENTA")) define("MAGENTA", WB_Constants::MAGENTA);
if (!defined("YELLOW")) define("YELLOW", WB_Constants::YELLOW);
if (!defined("WHITE")) define("WHITE", WB_Constants::WHITE);
if (!defined("DARKBLUE")) define("DARKBLUE", WB_Constants::DARKBLUE);
if (!defined("DARKGREEN")) define("DARKGREEN", WB_Constants::DARKGREEN);
if (!defined("DARKCYAN")) define("DARKCYAN", WB_Constants::DARKCYAN);
if (!defined("DARKRED")) define("DARKRED", WB_Constants::DARKRED);
if (!defined("DARKMAGENTA")) define("DARKMAGENTA", WB_Constants::DARKMAGENTA);
if (!defined("DARKYELLOW")) define("DARKYELLOW", WB_Constants::DARKYELLOW);
if (!defined("DARKGRAY")) define("DARKGRAY", WB_Constants::DARKGRAY);
if (!defined("LIGHTGRAY")) define("LIGHTGRAY", WB_Constants::LIGHTGRAY);

?>
