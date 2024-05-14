<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

class ActionClearFolders
{
    public function __construct($args)
    {
        global $bearsamppRoot, $bearsamppCore;

        Util::clearFolder($bearsamppRoot->getTmpPath(), array('cachegrind', 'composer', 'openssl', 'mailhog', 'npm-cache', 'pip', 'yarn'));
        Util::clearFolder($bearsamppCore->getTmpPath());
    }
}
