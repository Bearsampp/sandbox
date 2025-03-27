<?php

/*******************************************************************************
 WINBINDER - The native Windows binding for PHP for PHP
*******************************************************************************/

class WBConstants {
    // Screen constants
    const KX_SCREEN = 1.498;
    const KY_SCREEN = 1.625;

    // Window styles
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
    const LVS_GRIDLINES = 0x00800000;
    const LVS_CHECKBOXES = 0x00000800;
    const LVS_SINGLESEL = 0x00000004;
    const TBS_AUTOTICKS = 1;

    // Windows constants
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
}
