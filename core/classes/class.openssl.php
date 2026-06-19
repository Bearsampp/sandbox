<?php
/*
 *
 *  * Copyright (c) 2022-2025 Bearsampp
 *  * License: GNU General Public License version 3 or later; see LICENSE.txt
 *  * Website: https://bearsampp.com
 *  * Github: https://github.com/Bearsampp
 *
 */

class OpenSsl
{
    private $wbGenSslInputName;
    private $wbGenSslInputDest;
    private $wbGenSslBtnDest;
    private $wbGenSslProgressBar;
    private $wbGenSslBtnSave;
    private $wbGenSslBtnCancel;

    private $wbDelSslListCerts;
    private $wbDelSslInputDest;
    private $wbDelSslBtnDest;
    private $wbDelSslProgressBar;
    private $wbDelSslBtnDelete;
    private $wbDelSslBtnCancel;
    private $rootCaName = 'BearsamppRootCA';


    /**
     * Creates a new Root CA and reinstalls it, then rebuilds all certificates.
     *
     * @return bool True if successful.
     */
    public function makeRootCa()
    {
        $destPath = Path::getSslPath();
        $mkcertExe = Path::getMkcertExe();

        if (!file_exists($mkcertExe)) {
            Log::error('mkcert executable not found at: ' . $mkcertExe);
            return false;
        }

        Log::info('Creating new Root CA and installing it...');
        
        $batch = 'SET CAROOT=' . Path::formatWindowsPath(Path::getSslPath()) . PHP_EOL;
        $batch .= '"' . $mkcertExe . '" -uninstall' . PHP_EOL;
        $batch .= '"' . $mkcertExe . '" -install' . PHP_EOL;
        
        $result = Batch::exec('mkcertMakeRootCa', $batch);
        
        if ($result === false) {
            Log::error('Failed to run mkcert -install');
            return false;
        }

        // Display info about the new Root CA
        $batch = 'SET CAROOT=' . Path::formatWindowsPath(Path::getSslPath()) . PHP_EOL;
        $batch .= '"' . $mkcertExe . '" -CAROOT' . PHP_EOL;
        $caRootInfo = Batch::exec('mkcertCaRootInfo', $batch);
        if ($caRootInfo && isset($caRootInfo[0])) {
            Log::info('mkcert CAROOT is set to: ' . $caRootInfo[0]);
        }

        Log::info('Root CA created. Rebuilding all existing certificates...');
        return $this->rebuildAllCerts();
    }

    /**
     * Rebuilds all existing certificates in the SSL directory.
     *
     * @return bool True if all certificates were rebuilt successfully.
     */
    public function rebuildAllCerts()
    {
        $certs = $this->getCrts();
        $success = true;

        foreach ($certs as $cert) {
            Log::info('Rebuilding certificate: ' . $cert);
            if (!$this->createCrt($cert)) {
                Log::error('Failed to rebuild certificate: ' . $cert);
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Creates a certificate with the specified name and destination path.
     *
     * @param string $name The name of the certificate.
     * @param string|null $destPath The destination path where the certificate files will be saved. If null, the default SSL path is used.
     * @return bool True if the certificate was created successfully, false otherwise.
     */
    public function createCrt($name, $destPath = null)
    {
        $destPath = empty($destPath) ? Path::getSslPath() : $destPath;
        $mkcertExe = Path::getMkcertExe();

        if (!file_exists($mkcertExe)) {
            Log::error('mkcert executable not found at: ' . $mkcertExe);
            return false;
        }

        $this->ensureRootCaExists($destPath);

        $crtPath = '"' . $destPath . '/' . $name . '.crt"';
        $keyPath = '"' . $destPath . '/' . $name . '.ppk"'; // Using .ppk as requested in previous tasks

        $batch = 'SET CAROOT=' . Path::formatWindowsPath(Path::getSslPath()) . PHP_EOL;
        
        $mkcertNames = $name;
        if ($name === 'localhost') {
            $mkcertNames .= ' 127.0.0.1 ::1';
        } else {
            $mkcertNames .= ' "*.' . $name . '" localhost 127.0.0.1 ::1';
        }
        
        $batch .= '"' . $mkcertExe . '" -cert-file ' . $crtPath . ' -key-file ' . $keyPath . ' ' . $mkcertNames . PHP_EOL;

        $batch .= 'IF %ERRORLEVEL% GEQ 1 GOTO EOF' . PHP_EOL . PHP_EOL;
        $batch .= ':EOF' . PHP_EOL;
        $batch .= 'SET RESULT=KO' . PHP_EOL;
        $batch .= 'IF EXIST ' . $crtPath . ' IF EXIST ' . $keyPath . ' SET RESULT=OK' . PHP_EOL;
        $batch .= 'ECHO %RESULT%';

        Log::trace('Creating SSL Certificate for "' . $name . '" using mkcert');
        $result = Batch::exec('createCertificateMkcert', $batch);

        if ($result === false || !is_array($result)) {
            Log::error('Batch execution failed for mkcert generation of "' . $name . '"');
            return false;
        }

        $success = isset($result[0]) && $result[0] == 'OK';
        Log::trace('mkcert generation for "' . $name . '": ' . ($success ? 'SUCCESS' : 'FAILURE'));

        return $success;
    }

    /**
     * Ensures that the Root CA exists, creating it if necessary.
     *
     * @param string $destPath The destination path.
     * @return bool True if the Root CA exists or was created successfully.
     */
    private function ensureRootCaExists($destPath)
    {
        $mkcertExe = Path::getMkcertExe();
        if (!file_exists($mkcertExe)) {
             return false;
        }

        $rootCaPath = $destPath . '/' . Path::getMkcertRootCaName(); // mkcert default root CA name
        if (!file_exists($rootCaPath)) {
            Log::info('Root CA missing. Running mkcert -install');
            $batch = 'SET CAROOT=' . Path::formatWindowsPath($destPath) . PHP_EOL;
            $batch .= '"' . $mkcertExe . '" -install' . PHP_EOL;
            Batch::exec('mkcertInstall', $batch);
        }
        return true;
    }

    /**
     * Displays a WinBinder GUI for deleting an SSL certificate.
     */
    public function delSslCertificate()
    {
        global $bearsamppLang, $bearsamppWinbinder;

        $initServerName = 'test.local';
        $initDocumentRoot = Path::formatWindowsPath(Path::getSslPath());

        $bearsamppWinbinder->reset();
        $wbWindow = $bearsamppWinbinder->createAppWindow($bearsamppLang->getValue(Lang::DELSSL_TITLE), 490, 160, WBC_NOTIFY, WBC_KEYDOWN | WBC_KEYUP);

        $wbLabelName = $bearsamppWinbinder->createLabel($wbWindow, $bearsamppLang->getValue(Lang::NAME) . ' :', 15, 15, 85, null, WBC_RIGHT);
        $this->wbDelSslListCerts = $bearsamppWinbinder->createInputText($wbWindow, $initServerName, 105, 13, 150, null);

        $wbLabelDest = $bearsamppWinbinder->createLabel($wbWindow, $bearsamppLang->getValue(Lang::TARGET) . ' :', 15, 45, 85, null, WBC_RIGHT);
        $this->wbDelSslInputDest = $bearsamppWinbinder->createInputText($wbWindow, $initDocumentRoot, 105, 43, 190, null, null, WBC_READONLY);
        $this->wbDelSslBtnDest = $bearsamppWinbinder->createButton($wbWindow, $bearsamppLang->getValue(Lang::BUTTON_BROWSE), 300, 43, 110);

        $this->wbDelSslProgressBar = $bearsamppWinbinder->createProgressBar($wbWindow, 3, 15, 97, 275);
        $this->wbDelSslBtnDelete = $bearsamppWinbinder->createButton($wbWindow, $bearsamppLang->getValue(Lang::BUTTON_DELETE), 300, 92);
        $this->wbDelSslBtnCancel = $bearsamppWinbinder->createButton($wbWindow, $bearsamppLang->getValue(Lang::BUTTON_CANCEL), 387, 92);

        $bearsamppWinbinder->setHandler($wbWindow, $this, 'delSslCertificateHandler');

        $bearsamppWinbinder->mainLoop();
        $bearsamppWinbinder->reset();
    }

    /**
     * Handler for the SSL certificate deletion WinBinder GUI.
     */
    public function delSslCertificateHandler($window, $id, $ctrl, $param1, $param2)
    {
        global $bearsamppLang, $bearsamppOpenSsl, $bearsamppWinbinder;

        switch ($id) {
            case $this->wbDelSslBtnDest[WinBinder::CTRL_ID]:
                $target = $bearsamppWinbinder->getText($this->wbDelSslInputDest[WinBinder::CTRL_OBJ]);
                $target = $bearsamppWinbinder->sysDlgPath($window, $bearsamppLang->getValue(Lang::GENSSL_PATH), $target);
                if ($target && is_dir($target)) {
                    $bearsamppWinbinder->setText($this->wbDelSslInputDest[WinBinder::CTRL_OBJ], $target . '\\');
                }
                break;
            case $this->wbDelSslBtnDelete[WinBinder::CTRL_ID]:
                $cert = $bearsamppWinbinder->getText($this->wbDelSslListCerts[WinBinder::CTRL_OBJ]);
                $target = $bearsamppWinbinder->getText($this->wbDelSslInputDest[WinBinder::CTRL_OBJ]);

                if ($cert) {
                    $existingCerts = $this->getCrts();
                    if (!in_array($cert, $existingCerts)) {
                        $bearsamppWinbinder->messageBoxError(sprintf($bearsamppLang->getValue(Lang::ERROR_FILE_NOT_FOUND), $cert, $target), $bearsamppLang->getValue(Lang::DELSSL_TITLE));
                        return;
                    }

                    $bearsamppWinbinder->setProgressBarMax($this->wbDelSslProgressBar, 3);
                    $bearsamppWinbinder->incrProgressBar($this->wbDelSslProgressBar);

                    $target = Path::formatUnixPath($target);
                    if ($bearsamppOpenSsl->removeCrt($cert, $target)) {
                        $bearsamppWinbinder->incrProgressBar($this->wbDelSslProgressBar);
                        $bearsamppWinbinder->messageBoxInfo(
                            sprintf($bearsamppLang->getValue(Lang::DELSSL_DELETED), $cert),
                            $bearsamppLang->getValue(Lang::DELSSL_TITLE)
                        );
                        $bearsamppWinbinder->destroyWindow($window);
                    } else {
                        $bearsamppWinbinder->messageBoxError($bearsamppLang->getValue(Lang::DELSSL_DELETED_ERROR), $bearsamppLang->getValue(Lang::DELSSL_TITLE));
                        $bearsamppWinbinder->resetProgressBar($this->wbDelSslProgressBar);
                    }
                }
                break;
            case IDCLOSE:
            case $this->wbDelSslBtnCancel[WinBinder::CTRL_ID]:
                $bearsamppWinbinder->destroyWindow($window);
                break;
        }
    }

    /**
     * Checks if a certificate with the specified name exists.
     *
     * @param string $name The name of the certificate.
     * @return bool True if the certificate exists, false otherwise.
     */
    public function existsCrt($name)
    {
        $ppkPath = Path::getSslPath() . '/' . $name . '.ppk';
        $crtPath = Path::getSslPath() . '/' . $name . '.crt';

        return is_file($ppkPath) && is_file($crtPath);
    }

    /**
     * Displays a WinBinder GUI for generating an SSL certificate.
     */
    public function genSslCertificate()
    {
        global $bearsamppLang, $bearsamppWinbinder;

        $initServerName = 'test.local';
        $initDocumentRoot = Path::formatWindowsPath(Path::getSslPath());

        $bearsamppWinbinder->reset();
        $wbWindow = $bearsamppWinbinder->createAppWindow($bearsamppLang->getValue(Lang::GENSSL_TITLE), 490, 160, WBC_NOTIFY, WBC_KEYDOWN | WBC_KEYUP);

        $wbLabelName = $bearsamppWinbinder->createLabel($wbWindow, $bearsamppLang->getValue(Lang::NAME) . ' :', 15, 15, 85, null, WBC_RIGHT);
        $this->wbGenSslInputName = $bearsamppWinbinder->createInputText($wbWindow, $initServerName, 105, 13, 150, null);

        $wbLabelDest = $bearsamppWinbinder->createLabel($wbWindow, $bearsamppLang->getValue(Lang::TARGET) . ' :', 15, 45, 85, null, WBC_RIGHT);
        $this->wbGenSslInputDest = $bearsamppWinbinder->createInputText($wbWindow, $initDocumentRoot, 105, 43, 190, null, null, WBC_READONLY);
        $this->wbGenSslBtnDest = $bearsamppWinbinder->createButton($wbWindow, $bearsamppLang->getValue(Lang::BUTTON_BROWSE), 300, 43, 110);

        $this->wbGenSslProgressBar = $bearsamppWinbinder->createProgressBar($wbWindow, 3, 15, 97, 275);
        $this->wbGenSslBtnSave = $bearsamppWinbinder->createButton($wbWindow, $bearsamppLang->getValue(Lang::BUTTON_SAVE), 300, 92);
        $this->wbGenSslBtnCancel = $bearsamppWinbinder->createButton($wbWindow, $bearsamppLang->getValue(Lang::BUTTON_CANCEL), 387, 92);

        $bearsamppWinbinder->setHandler($wbWindow, $this, 'genSslCertificateHandler');

        $bearsamppWinbinder->mainLoop();
        $bearsamppWinbinder->reset();
    }

    /**
     * Handler for the SSL certificate generation WinBinder GUI.
     */
    public function genSslCertificateHandler($window, $id, $ctrl, $param1, $param2)
    {
        global $bearsamppLang, $bearsamppOpenSsl, $bearsamppWinbinder;

        switch ($id) {
            case $this->wbGenSslBtnDest[WinBinder::CTRL_ID]:
                $target = $bearsamppWinbinder->getText($this->wbGenSslInputDest[WinBinder::CTRL_OBJ]);
                $target = $bearsamppWinbinder->sysDlgPath($window, $bearsamppLang->getValue(Lang::GENSSL_PATH), $target);
                if ($target && is_dir($target)) {
                    $bearsamppWinbinder->setText($this->wbGenSslInputDest[WinBinder::CTRL_OBJ], $target . '\\');
                }
                break;
            case $this->wbGenSslBtnSave[WinBinder::CTRL_ID]:
                $name = $bearsamppWinbinder->getText($this->wbGenSslInputName[WinBinder::CTRL_OBJ]);
                $target = $bearsamppWinbinder->getText($this->wbGenSslInputDest[WinBinder::CTRL_OBJ]);

                $bearsamppWinbinder->setProgressBarMax($this->wbGenSslProgressBar, 3);
                $bearsamppWinbinder->incrProgressBar($this->wbGenSslProgressBar);

                $target = Path::formatUnixPath($target);
                if ($bearsamppOpenSsl->createCrt($name, $target)) {
                    $bearsamppWinbinder->incrProgressBar($this->wbGenSslProgressBar);
                    $bearsamppWinbinder->messageBoxInfo(
                        sprintf($bearsamppLang->getValue(Lang::GENSSL_CREATED), $name),
                        $bearsamppLang->getValue(Lang::GENSSL_TITLE));
                    $bearsamppWinbinder->destroyWindow($window);
                } else {
                    $bearsamppWinbinder->messageBoxError($bearsamppLang->getValue(Lang::GENSSL_CREATED_ERROR), $bearsamppLang->getValue(Lang::GENSSL_TITLE));
                    $bearsamppWinbinder->resetProgressBar($this->wbGenSslProgressBar);
                }
                break;
            case IDCLOSE:
            case $this->wbGenSslBtnCancel[WinBinder::CTRL_ID]:
                $bearsamppWinbinder->destroyWindow($window);
                break;
        }
    }

    /**
     * Retrieves existing certificates from the SSL directory.
     *
     * @return array List of certificate names.
     */
    public function getCrts()
    {
        $sslPath = Path::getSslPath();
        $certs = [];
        if (is_dir($sslPath)) {
            $files = glob($sslPath . '/*.crt');
            if ($files !== false) {
                foreach ($files as $file) {
                    $certs[] = basename($file, '.crt');
                }
            }
        }
        sort($certs);
        return $certs;
    }

    /**
     * Checks if a certificate with the specified name is expired or about to expire.
     *
     * @param string $name The name of the certificate.
     * @return bool True if the certificate is expired or missing, false otherwise.
     */
    public function isExpired($name)
    {
        $crtPath = Path::getSslPath() . '/' . $name . '.crt';
        if (!is_file($crtPath)) {
            Log::trace('SSL certificate file missing: ' . $crtPath);
            return true;
        }

        $crtContent = file_get_contents($crtPath);
        if ($crtContent === false) {
            Log::error('Could not read certificate file: ' . $crtPath);
            return true;
        }

        $certInfo = openssl_x509_parse($crtContent);
        if ($certInfo === false) {
            Log::error('Could not parse certificate: ' . $crtPath . '. OpenSSL error: ' . openssl_error_string());
            return true;
        }

        if (isset($certInfo['validTo_time_t'])) {
            $isExpired = $certInfo['validTo_time_t'] < time();
            if ($isExpired) {
                Log::trace('SSL certificate expired: ' . $name . ' (Expired on ' . date('Y-m-d H:i:s', $certInfo['validTo_time_t']) . ')');
            }
            return $isExpired;
        }

        Log::error('Could not find expiry date in certificate: ' . $crtPath);
        return true;
    }

    /**
     * Removes a certificate with the specified name.
     *
     * @param string $name The name of the certificate.
     * @param string|null $destPath The destination path where the certificate files are saved. If null, the default SSL path is used.
     * @return bool True if the certificate was removed successfully, false otherwise.
     */
    public function removeCrt($name, $destPath = null)
    {
        if ($name === 'localhost') {
            Log::warning('Attempted to remove protected "localhost" certificate. Operation cancelled.');
            return false;
        }
        $destPath = empty($destPath) ? Path::getSslPath() : $destPath;

        // Basic validation for name to prevent arbitrary file deletion
        if (!preg_match('/^[a-zA-Z0-9._-]+$/', $name)) {
            Log::error('Invalid certificate name for removal: ' . $name);
            return false;
        }

        $ppkPath = $destPath . '/' . $name . '.ppk';
        $crtPath = $destPath . '/' . $name . '.crt';

        Log::info('Removing SSL certificate: ' . $name . ' from ' . $destPath);
        return @unlink($ppkPath) && @unlink($crtPath);
    }
}
