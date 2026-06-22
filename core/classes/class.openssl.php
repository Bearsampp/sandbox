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
     * Ensures that the mkcert executable exists, attempting to download it if missing.
     *
     * @return bool True if mkcert exists or was successfully downloaded.
     */
    private function ensureMkcertExeExists()
    {
        $mkcertExe = Path::getMkcertExe();
        if (file_exists($mkcertExe)) {
            return true;
        }

        Log::info('mkcert.exe missing at: ' . $mkcertExe . '. Attempting to download...');

        $mkcertDir = Path::getMkcertPath();
        if (!is_dir($mkcertDir)) {
            if (!mkdir($mkcertDir, 0777, true)) {
                Log::error('Failed to create mkcert directory: ' . $mkcertDir);
                return false;
            }
        }

        // Use GitHub API to find the latest release
        $apiUrl = 'https://api.github.com/repos/FiloSottile/mkcert/releases/latest';
        $latest = HttpClient::getApiJson($apiUrl);
        $url = '';

        if (!empty($latest)) {
            $releaseData = json_decode($latest, true);
            if (isset($releaseData['assets'])) {
                foreach ($releaseData['assets'] as $asset) {
                    if (isset($asset['name']) && UtilString::endWith($asset['name'], 'amd64.exe')) {
                        $url = $asset['browser_download_url'];
                        Log::info('Found latest mkcert download URL: ' . $url);
                        break;
                    }
                }
            }
        }

        // Fallback to v1.4.4 if API failed or asset not found
        if (empty($url)) {
            Log::warning('Failed to find latest mkcert via GitHub API. Falling back to v1.4.4.');
            $url = 'https://github.com/FiloSottile/mkcert/releases/download/v1.4.4/mkcert-v1.4.4-windows-amd64.exe';
        }
        
        global $bearsamppCore;
        if (!isset($bearsamppCore)) {
            // If global not available, try to instantiate it (though it should be available)
            $bearsamppCore = new Core();
        }

        $result = $bearsamppCore->getFileFromUrl($url, $mkcertExe);

        if (isset($result['success']) && $result['success'] === true) {
            // The getFileFromUrl uses the provided $filePath ($mkcertExe), 
            // so it is already saved as mkcert.exe.
            if (file_exists($mkcertExe)) {
                Log::info('Successfully downloaded and saved mkcert.exe');
                return true;
            }
            Log::error('mkcert.exe not found at expected path after download: ' . $mkcertExe);
        }

        Log::error('Failed to download mkcert.exe: ' . (isset($result['error']) ? $result['error'] : 'Unknown error'));
        return false;
    }

    /**
     * Ensures that the SSL directory exists, creating it if necessary.
     *
     * @return string The SSL path.
     */
    private function ensureSslDirExists()
    {
        $sslPath = Path::getSslPath();
        if (!is_dir($sslPath)) {
            Log::info('SSL directory missing, creating: ' . $sslPath);
            if (mkdir($sslPath, 0777, true)) {
                // Create .gitignore if the directory was just created
                $gitignorePath = $sslPath . '/.gitignore';
                if (!file_exists($gitignorePath)) {
                    file_put_contents($gitignorePath, '# git holder' . PHP_EOL);
                }
            } else {
                Log::error('Failed to create SSL directory: ' . $sslPath);
            }
        } else {
            // Even if directory exists, ensure .gitignore is present
            $gitignorePath = $sslPath . '/.gitignore';
            if (!file_exists($gitignorePath)) {
                file_put_contents($gitignorePath, '# git holder' . PHP_EOL);
            }
        }
        return $sslPath;
    }

    /**
     * Creates a new Root CA and reinstalls it, then rebuilds all certificates.
     *
     * @return bool True if successful.
     */
    public function makeRootCa()
    {
        if (!$this->ensureMkcertExeExists()) {
            return false;
        }
        $destPath = Path::getSslPath();
        $mkcertExe = Path::getMkcertExe();

        Log::info('Creating new Root CA and installing it...');
        
        $batch = 'SET CAROOT=' . Path::formatWindowsPath(Path::getSslPath()) . PHP_EOL;
        $batch .= '"' . $mkcertExe . '" -uninstall' . PHP_EOL;
        $batch .= '"' . $mkcertExe . '" -install' . PHP_EOL;
        
        // Wait for the Root CA file to appear or timeout
        $result = Batch::exec('mkcertMakeRootCa', $batch);
        
        $rootCaPath = Path::getSslPath() . '/' . Path::getMkcertRootCaName();
        if (!file_exists($rootCaPath)) {
            Log::error('Failed to create Root CA file at: ' . $rootCaPath);
            return false;
        }

        // Display info about the new Root CA
        $batch = 'SET CAROOT=' . Path::formatWindowsPath(Path::getSslPath()) . PHP_EOL;
        $batch .= '"' . $mkcertExe . '" -CAROOT' . PHP_EOL;
        $caRootInfo = Batch::exec('mkcertCaRootInfo', $batch);
        if ($caRootInfo && isset($caRootInfo[0])) {
            Log::info('mkcert CAROOT is set to: ' . $caRootInfo[0]);
        }

        Log::info('Root CA created. Rebuilding all existing certificates and ensuring localhost exists...');
        
        // Ensure localhost is created/rebuilt
        $this->createCrt('localhost');
        
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
        Log::trace('createCrt called for: ' . $name . ($destPath ? ' (dest: ' . $destPath . ')' : ''));
        if (!$this->ensureMkcertExeExists()) {
            return false;
        }
        if (empty($destPath)) {
            $destPath = $this->ensureSslDirExists();
        }
        $mkcertExe = Path::getMkcertExe();

        if (!$this->ensureRootCaExists($destPath)) {
            Log::error('Failed to ensure Root CA exists for: ' . $name);
            return false;
        }

        $crtPath = '"' . $destPath . '/' . $name . '.crt"';
        $pubPath = '"' . $destPath . '/' . $name . '.pub"';
        $keyPath = '"' . $destPath . '/' . $name . '.ppk"'; // Using .ppk as requested in previous tasks

        $batch = 'SET CAROOT=' . Path::formatWindowsPath(Path::getSslPath()) . PHP_EOL;
        
        $mkcertNames = $name;
        if ($name === 'localhost') {
            $mkcertNames .= ' 127.0.0.1 ::1';
        } else {
            $mkcertNames .= ' "*.' . $name . '" localhost 127.0.0.1 ::1';
        }
        
        Log::trace('Executing mkcert for "' . $name . '"');
        $batch .= '("' . $mkcertExe . '" -cert-file ' . $crtPath . ' -key-file ' . $keyPath . ' ' . $mkcertNames . ') || (ECHO mkcert failed && EXIT /B 1)' . PHP_EOL;
        $batch .= 'COPY /Y ' . $crtPath . ' ' . $pubPath . ' >NUL 2>&1' . PHP_EOL;
        $batch .= 'IF NOT EXIST ' . $pubPath . ' (ECHO pub file missing && EXIT /B 1)' . PHP_EOL;

        $batch .= 'SET RESULT=KO' . PHP_EOL;
        $batch .= 'IF EXIST ' . $crtPath . ' IF EXIST ' . $keyPath . ' IF EXIST ' . $pubPath . ' SET RESULT=OK' . PHP_EOL;
        $batch .= 'ECHO %RESULT%';

        Log::trace('Creating SSL Certificate for "' . $name . '" using mkcert. Batch content: ' . $batch);
        $result = Batch::exec('createCertificateMkcert', $batch);

        if ($result === false || !is_array($result)) {
            Log::error('Batch execution failed for mkcert generation of "' . $name . '". Check logs for createCertificateMkcert.');
            return false;
        }

        $success = false;
        foreach ($result as $line) {
            if (trim($line) === 'OK') {
                $success = true;
                break;
            }
        }
        
        if (!$success) {
            Log::error('mkcert generation for "' . $name . '" did not return OK. Output: ' . implode(' | ', $result));
        }
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
        if (!$this->ensureMkcertExeExists()) {
            return false;
        }
        $mkcertExe = Path::getMkcertExe();

        $rootCaPath = $destPath . '/' . Path::getMkcertRootCaName(); // mkcert default root CA name
        if (!file_exists($rootCaPath)) {
            Log::info('Root CA missing at ' . $rootCaPath . '. Running mkcert -install');
            $batch = 'SET CAROOT=' . Path::formatWindowsPath($destPath) . PHP_EOL;
            $batch .= '"' . $mkcertExe . '" -install' . PHP_EOL;
            $result = Batch::exec('mkcertInstall', $batch);
            
            if ($result === false) {
                Log::error('Batch execution failed for mkcert -install');
                return false;
            }

            // Re-check after installation
            if (!file_exists($rootCaPath)) {
                Log::error('Root CA still missing after mkcert -install at: ' . $rootCaPath);
                return false;
            }
            Log::info('Root CA successfully created and verified.');
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
        $pubPath = Path::getSslPath() . '/' . $name . '.pub';

        if (!is_file($crtPath)) {
            Log::trace('SSL certificate file missing: ' . $crtPath);
            return true;
        }

        if (!is_file($pubPath)) {
            Log::trace('SSL public certificate file missing: ' . $pubPath);
            return true;
        }

        if (!extension_loaded('openssl')) {
            Log::warning('OpenSSL extension not loaded. Cannot parse certificate for expiry check. Assuming NOT expired if file exists.');
            return false;
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
        $pubPath = $destPath . '/' . $name . '.pub';

        Log::info('Removing SSL certificate: ' . $name . ' from ' . $destPath);
        return @unlink($ppkPath) && @unlink($crtPath) && @unlink($pubPath);
    }
}
