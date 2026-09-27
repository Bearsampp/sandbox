<?php
/*
 * Copyright (c) 2026 Bearsampp
 * License: GNU General Public License version 3 or later; see LICENSE.txt
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * AJAX handler that returns the on-disk size of the install, broken down by part.
 *
 * This endpoint is opt-in, unlike the other homepage polls. Measuring the install
 * costs about 7s, against roughly 270ms for a full status snapshot, so it is only
 * called when the user asks for it and is then served from a cache for
 * DISK_USAGE::CACHE_TTL seconds. The page loads the drive's free space from the
 * ordinary snapshot instead, which costs nothing.
 *
 * The response carries 'cached' and 'elapsedMs' so the page can be honest about
 * whether the numbers were just measured or reused, and 'generatedAt' so a stale
 * figure can be labelled as stale rather than presented as current.
 *
 * Responds with a JSON object and exits.
 */
header('Content-Type: application/json');
header('Cache-Control: no-store');

// 'proc' itself is read from POST by ajax.php, so the flags are posted alongside
// it rather than in the query string. GET is accepted as well because the
// endpoint is harmless to call either way and this keeps a hand-typed URL from
// behaving differently from the button.
$refresh = isset($_POST['refresh']) ? $_POST['refresh'] : (isset($_GET['refresh']) ? $_GET['refresh'] : '');
$cachedOnly = isset($_POST['cached']) ? $_POST['cached'] : (isset($_GET['cached']) ? $_GET['cached'] : '');

// 'cached' asks for a reading without authorising the walk that produces one.
// The page uses it on load, so that arriving at the status page never costs the
// several seconds a fresh measurement takes on a first visit.
echo json_encode(DiskUsage::captureForWeb($refresh === '1', $cachedOnly !== '1'));
exit;
