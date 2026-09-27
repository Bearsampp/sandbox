<?php
/*
 * Copyright (c) 2026 Bearsampp
 * License: GNU General Public License version 3 or later; see LICENSE.txt
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Reports how much disk space the Bearsampp install occupies, broken down by part.
 *
 * This is deliberately separate from StatusSnapshot, which is polled every few
 * seconds. Measuring the install means stat-ing roughly 230,000 files, which
 * costs about 7s here, against roughly 270ms for an entire status snapshot. The
 * two figures differ by more than an order of magnitude, so a poll that included
 * this would spend nearly all of its time in the disk walk. A user who installs
 * or removes a bin changes the answer, but only rarely, so it is offered as an
 * explicit request and then cached.
 *
 * The measurement itself, including why it runs through PowerShell and why the
 * version symlinks are not followed, is documented in status-disk-usage.php.
 *
 * @see ajax.stackdisk.php for the endpoint.
 */
class DiskUsage
{
    /**
     * On-disk cache for a computed breakdown.
     */
    const CACHE_PATH = '/tmp/stack-disk-usage.json';

    /**
     * How long a computed breakdown may be reused, in seconds.
     *
     * Five minutes. A walk costs several seconds, so this is what keeps a reload
     * of the page from re-running one, while still letting an install or removal
     * show up on its own soon afterwards rather than after a long wait. The
     * endpoint also accepts a forced refresh, so a user who has just changed
     * something never has to wait out a stale entry.
     */
    const CACHE_TTL = 300;

    /**
     * Path to the CLI collector that performs the walk.
     */
    const COLLECTOR_SCRIPT = '/status-disk-usage.php';

    /**
     * Part name used for files sitting directly in the install root.
     *
     * Grouping them keeps the reported total equal to the sum of the listed
     * parts. A total that cannot be checked against its own rows is a number
     * worth trusting less, and these files are otherwise silently missing.
     */
    const ROOT_FILES_PART = '(root files)';

    /**
     * Returns the on-disk breakdown, computing it only when needed.
     *
     * The two booleans are separate on purpose, because "no cache yet" has to be
     * answerable without paying for a walk:
     *
     *   - $force asks for a fresh measurement even when the cache is usable;
     *   - $compute decides whether a missing cache entry may be filled in.
     *
     * A page load passes $compute as false so that arriving at the status page
     * never costs several seconds; the figure appears only once something has
     * already measured it, and the button is what starts that.
     *
     * @param   bool  $force    Recompute even when a usable cache entry exists.
     * @param   bool  $compute  Fill in a missing or stale cache entry.
     *
     * @return array {
     *     @type array  $parts       One entry per top-level part, largest first.
     *     @type array  $total       byte and file count for the whole install.
     *     @type int    $generatedAt When the measurement was taken, 0 if none.
     *     @type bool   $available   Whether a real measurement is being reported.
     *     @type bool   $cached      Whether this response came from the cache.
     *     @type int    $elapsedMs   How long the computation took, 0 when cached.
     *     @type string $error       Present only when the measurement failed.
     * }
     */
    public static function captureForWeb(bool $force = false, bool $compute = true): array
    {
        $cachePath = Path::getCorePath() . self::CACHE_PATH;

        if (!$force) {
            $cached = self::readCache($cachePath);

            if ($cached !== null) {
                $cached['cached']    = true;
                $cached['elapsedMs'] = 0;

                return $cached;
            }
        }

        if (!$compute) {
            // Asked for a reading without authorising the walk that produces one.
            return [
                'parts'       => [],
                'total'       => ['bytes' => 0, 'files' => 0],
                'generatedAt' => 0,
                'available'   => false,
                'cached'      => false,
                'elapsedMs'   => 0,
            ];
        }

        $started  = microtime(true);
        $measured = self::runCollector();
        $elapsed  = (int) round((microtime(true) - $started) * 1000);

        if (isset($measured['error'])) {
            // A failure is never cached: the next request should be free to
            // succeed, for instance once whatever locked the files has exited.
            return [
                'parts'       => [],
                'total'       => ['bytes' => 0, 'files' => 0],
                'generatedAt' => time(),
                'available'   => false,
                'cached'      => false,
                'elapsedMs'   => $elapsed,
                'error'       => $measured['error'],
            ];
        }

        $result = [
            'parts'       => self::normaliseParts($measured['parts'] ?? []),
            'total'       => self::normaliseTotal($measured['total'] ?? []),
            'generatedAt' => (int) ($measured['generatedAt'] ?? time()),
            'available'   => true,
            'cached'      => false,
            'elapsedMs'   => $elapsed,
        ];

        self::writeCache($cachePath, $result);

        return $result;
    }

    /**
     * Runs the collector with the internal engine and decodes its output.
     *
     * The internal engine is used even though the walk needs no COM, because it
     * is the fixed, known-good interpreter for this codebase and keeps the
     * measurement independent of whatever the user has selected in the UI.
     *
     * @return array The measurement, carrying 'error' when it failed.
     */
    private static function runCollector(): array
    {
        $engine = Path::getPhpPath() . '/php.exe';
        $script = Path::getCorePath() . self::COLLECTOR_SCRIPT;

        if (!is_file($engine) || !is_file($script)) {
            Log::error('Disk usage collector is missing: ' . $engine . ' or ' . $script);

            return ['error' => 'The disk usage collector is missing.'];
        }

        $command = escapeshellarg($engine) . ' ' . escapeshellarg($script) . ' 2>NUL';

        try {
            $output = shell_exec($command);
        } catch (Throwable $e) {
            Log::error('Disk usage collector failed to start: ' . $e->getMessage());

            return ['error' => 'The disk usage collector could not be started.'];
        }

        $output = is_string($output) ? trim($output) : '';

        if ($output === '') {
            Log::error('Disk usage collector returned no output');

            return ['error' => 'The disk usage collector returned no response.'];
        }

        $decoded = json_decode($output, true);

        if (!is_array($decoded) || isset($decoded['error'])) {
            Log::error('Disk usage collector returned unusable output');

            return ['error' => 'The disk usage collector returned an unusable response.'];
        }

        return $decoded;
    }

    /**
     * Reads a cached breakdown, provided it is recent enough to be useful.
     *
     * @param   string  $path  Absolute cache file path.
     *
     * @return array|null The cached measurement, or null when absent,
     *                    unreadable or stale.
     */
    private static function readCache(string $path): ?array
    {
        if (!is_file($path)) {
            return null;
        }

        $age = time() - (int) @filemtime($path);

        if ($age < 0 || $age > self::CACHE_TTL) {
            return null;
        }

        $raw  = @file_get_contents($path);
        $data = $raw === false ? null : json_decode($raw, true);

        if (!is_array($data) || !isset($data['parts'], $data['total']) || isset($data['error'])) {
            return null;
        }

        // Older cache entries predate the 'available' flag and are still a real
        // measurement, so they are upgraded rather than discarded.
        if (!isset($data['available'])) {
            $data['available'] = true;
        }

        return $data;
    }

    /**
     * Stores a computed breakdown for reuse.
     *
     * A failed write is not worth reporting: the only consequence is that the
     * next request measures again.
     *
     * @param   string  $path       Absolute cache file path.
     * @param   array   $measurement  The measurement to store.
     *
     * @return void
     */
    private static function writeCache(string $path, array $measurement): void
    {
        if (!is_dir(dirname($path))) {
            return;
        }

        @file_put_contents($path, json_encode($measurement), LOCK_EX);
    }

    /**
     * Cleans and orders the parts reported by the collector.
     *
     * Sorting is repeated here rather than trusted from the script, so the page
     * still shows the largest part first if the collector ever changes.
     *
     * @param   array  $parts  Raw parts from the collector.
     *
     * @return array
     */
    private static function normaliseParts(array $parts): array
    {
        $clean = [];

        foreach ($parts as $part) {
            if (!is_array($part) || !isset($part['name'])) {
                continue;
            }

            $children = [];

            foreach (($part['childrenList'] ?? []) as $child) {
                if (!is_array($child) || !isset($child['name'])) {
                    continue;
                }

                $children[] = [
                    'name'  => (string) $child['name'],
                    'bytes' => max(0, (int) ($child['bytes'] ?? 0)),
                    'files' => max(0, (int) ($child['files'] ?? 0)),
                ];
            }

            usort($children, function (array $a, array $b) {
                return $b['bytes'] <=> $a['bytes'];
            });

            $clean[] = [
                'name'        => (string) $part['name'],
                // Set by the collector for the synthetic group of files sitting
                // directly in the install root, so the page can relabel it without
                // matching on an English name this class knows nothing about.
                'isRootFiles' => !empty($part['isRootFiles']),
                'bytes'       => max(0, (int) ($part['bytes'] ?? 0)),
                'files'       => max(0, (int) ($part['files'] ?? 0)),
                'children'    => $children,
            ];
        }

        usort($clean, function (array $a, array $b) {
            return $b['bytes'] <=> $a['bytes'];
        });

        return $clean;
    }

    /**
     * Coerces the collector's totals into integers.
     *
     * The total is recomputed from the parts rather than taken from the script,
     * because that identity is the useful property: if the rows ever disagree
     * with the headline figure, the rows are the ones that can be checked.
     *
     * @param   array  $total  Raw total from the collector.
     *
     * @return array
     */
    private static function normaliseTotal(array $total): array
    {
        return [
            'bytes' => max(0, (int) ($total['bytes'] ?? 0)),
            'files' => max(0, (int) ($total['files'] ?? 0)),
        ];
    }
}
