<?php
/*
 * Copyright (c) 2021-2026 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Class StatusSnapshot
 *
 * Builds a single point-in-time picture of the whole WAMPP stack in the canonical
 * vocabulary defined by ServiceStatus.
 *
 * Design constraints this class exists to respect:
 *
 *  - The homepage polls every couple of seconds, and each poll is a fresh PHP
 *    process, so nothing can be cached between requests. The whole snapshot must
 *    therefore be cheap enough to build on every request. Measured cost of the
 *    queries used here is roughly 20-25ms, which is why the SCM is queried once
 *    for all services (Win32Native::getServicesByNames) rather than once per
 *    service.
 *
 *  - Win32Service::status() and Nssm::status() both retry with sleeps and a 10s
 *    cap, which would stall a poll. This class never calls them; it reads SCM
 *    state through a single non-blocking WMI query instead.
 *
 *  - Status and health are reported as separate dimensions. Nothing here
 *    collapses them into one value, so a service that is running but
 *    unreachable stays visible as exactly that.
 */
class StatusSnapshot
{
    /** Connect timeout for the TCP liveness probe, in seconds. */
    const PROBE_TIMEOUT = 0.25;

    /**
     * Captures the current state of every bin in the stack.
     *
     * @param   Bins  $bearsamppBins  The bins registry.
     *
     * @return array A snapshot array. See build() for the shape.
     */
    public static function capture(Bins $bearsamppBins): array
    {
        $snapshot = self::build($bearsamppBins, self::collectScmState($bearsamppBins));

        return self::attachFootprint($snapshot);
    }

    /**
     * Adds per-service resource usage to an already built snapshot.
     *
     * Kept apart from build() so that the status and health logic stays testable
     * against a hand-written SCM map, with no live process table involved.
     *
     * @param   array  $snapshot  A snapshot from build().
     *
     * @return array The snapshot, with per-entry 'footprint' and a top level
     *               'resources' block.
     */
    private static function attachFootprint(array $snapshot): array
    {
        $roots = [];

        foreach ($snapshot['entries'] as $index => $entry) {
            $serviceName = $entry['serviceName'] ?? null;
            $pid         = (int) ($entry['pid'] ?? 0);

            if ($serviceName !== null && $pid > 0) {
                $names = ServiceHelper::getProcessNamesForService($serviceName);

                if (!empty($names)) {
                    $roots[$serviceName] = ['pid' => $pid, 'names' => $names];
                }
            }

            // Present but empty for everything else, so consumers never have to
            // test for the key's existence.
            $snapshot['entries'][$index]['footprint'] = null;
        }

        $footprint = ProcessFootprint::capture($roots);

        foreach ($snapshot['entries'] as $index => $entry) {
            $serviceName = $entry['serviceName'] ?? null;

            if ($serviceName !== null && isset($footprint['services'][$serviceName])) {
                $snapshot['entries'][$index]['footprint'] = $footprint['services'][$serviceName];
            }
        }

        $snapshot['resources'] = [
            'stack'   => $footprint['total'],
            'host'    => $footprint['host'],
            'cores'   => $footprint['cores'],
            'serviceCount' => count($footprint['services']),
        ];

        return $snapshot;
    }

    /**
     * Queries the SCM once for the state and PID of every enabled service.
     *
     * StartMode is deliberately not requested. It is the one Win32_Service
     * property that is expensive here, costing about 166ms on its own against
     * roughly 12ms for State and ProcessId together, and nothing in this class
     * reads it.
     *
     * @param   Bins  $bearsamppBins  The bins registry.
     *
     * @return array Service name => [State, ProcessId, StartMode]. Empty when the
     *               query fails, in which case every service reports as unknown
     *               rather than as stopped.
     */
    private static function collectScmState(Bins $bearsamppBins): array
    {
        $names = array_keys($bearsamppBins->getServices());

        if (empty($names)) {
            return [];
        }

        return Win32Native::getServicesByNames($names, ['Name', 'State', 'ProcessId']);
    }

    /**
     * Assembles the snapshot from bin configuration and SCM state.
     *
     * Split out from capture() so the assembly logic stays testable without a
     * live SCM.
     *
     * @param   Bins  $bearsamppBins  The bins registry.
     * @param   array $scm            Service name => SCM property map.
     *
     * @return array {
     *     @type string $status     Aggregate STATUS_* constant.
     *     @type int    $expected   Number of expected bins.
     *     @type int    $running    Number of expected bins that are up.
     *     @type array  $entries    Per-bin entries, ordered as Bins::getAll().
     * }
     */
    public static function build(Bins $bearsamppBins, array $scm): array
    {
        $serviceBins = self::mapServiceBins($bearsamppBins);
        $entries     = [];
        $statuses    = [];
        $expected    = 0;
        $running     = 0;

        foreach ($bearsamppBins->getAll() as $bin) {
            $name      = $bin->getName();
            $enabled   = $bin->isEnable();
            $installed = $bin->isInstalled();

            $entry = [
                'name'    => $name,
                'id'      => $bin->getId(),
                'version' => $bin->getVersion(),
                'enabled' => $enabled,
                'installed' => $installed,
                'isService' => isset($serviceBins[$name]),
                'serviceName' => null,
                'status'  => ServiceStatus::STATUS_UNKNOWN,
                'health'  => ServiceStatus::HEALTH_UNKNOWN,
                'pid'     => null,
                'ports'   => [],
                'detail'  => '',
            ];

            if (!$installed) {
                // Absent from disk: the user has to install it. Deliberately
                // distinct from "disabled", which needs no action.
                $entry['status'] = ServiceStatus::STATUS_NOT_INSTALLED;
                $entry['detail'] = 'Not installed';
                $entries[]       = $entry;
                $statuses[]      = $entry['status'];

                continue;
            }

            if (!$enabled) {
                $entry['status'] = ServiceStatus::STATUS_DISABLED;
                $entry['detail'] = 'Installed but disabled';
                $entries[]       = $entry;

                continue;
            }

            $expected++;

            if (!$entry['isService']) {
                // PHP and Node.js are invoked on demand; there is no lifecycle
                // to report, so being installed is all that matters.
                $entry['status'] = ServiceStatus::STATUS_AVAILABLE;
                $entry['health'] = ServiceStatus::HEALTH_NOT_APPLICABLE;
                $entry['detail'] = 'On-demand runtime';
                $entries[]       = $entry;
                $running++;

                continue;
            }

            $serviceName = self::findServiceName($serviceBins, $name);
            $entry['serviceName'] = $serviceName;

            $scmEntry = $serviceName !== null ? ($scm[$serviceName] ?? null) : null;

            if ($scmEntry === null) {
                // The files are on disk but the SCM has no such service, so the
                // Windows service was never registered. Distinct from a failed
                // query: the first needs a service install, the second needs
                // nothing from the user and must not be reported as an outage.
                $entry['status'] = ServiceStatus::STATUS_NOT_INSTALLED;
                $entry['detail'] = 'Service not registered';
                $entries[]       = $entry;
                $statuses[]      = $entry['status'];

                continue;
            }

            $state = ServiceStatus::fromWin32State((string) ($scmEntry['State'] ?? ''));
            $pid   = (int) ($scmEntry['ProcessId'] ?? 0);

            $entry['status'] = $state;
            $entry['detail'] = (string) ($scmEntry['State'] ?? '');
            $entry['pid']    = $pid > 0 ? $pid : null;

            if ($state === ServiceStatus::STATUS_RUNNING) {
                $entry['ports'] = self::collectPorts($bin);
                $entry['health'] = self::probeHealth($entry['ports']);
                $running++;
            }

            $entries[]  = $entry;
            $statuses[] = $entry['status'];
        }

        return [
            'status'   => ServiceStatus::rollup($statuses),
            'expected' => $expected,
            'running'  => $running,
            'entries'  => $entries,
        ];
    }

    /**
     * Builds a map of bin display name => Windows service name.
     *
     * Bins::getServices() only returns enabled bins, so it cannot be used to
     * decide which bins are services; that distinction has to hold for disabled
     * bins too, otherwise toggling a bin off would change its status vocabulary
     * instead of simply reporting it as disabled.
     *
     * @param   Bins  $bearsamppBins  The bins registry.
     *
     * @return array Bin name => service name.
     */
    private static function mapServiceBins(Bins $bearsamppBins): array
    {
        $map = [];
        foreach (ServiceHelper::getAllServiceNames() as $serviceName) {
            $bin = ServiceHelper::getBinFromServiceName($serviceName, $bearsamppBins);
            if ($bin !== null) {
                $map[$bin->getName()] = $serviceName;
            }
        }

        return $map;
    }

    /**
     * Returns the Windows service name backing a bin display name.
     *
     * @param   array   $serviceBins  Bin name => service name map.
     * @param   string  $binName      The bin display name.
     *
     * @return string|null The service name, or null when the bin has none.
     */
    private static function findServiceName(array $serviceBins, string $binName): ?string
    {
        return $serviceBins[$binName] ?? null;
    }

    /**
     * Collects every port a bin is expected to listen on.
     *
     * Bins expose their ports through differently named accessors depending on
     * the service, so the known ones are probed reflectively. Apache is the case
     * that matters most here: it has both an HTTP and an SSL port, and only one
     * of them answering is a partial result rather than a failure.
     *
     * @param   object  $bin  The bin module.
     *
     * @return array List of port numbers.
     */
    private static function collectPorts(object $bin): array
    {
        $ports = [];

        foreach (['getPort', 'getSslPort', 'getSmtpPort'] as $accessor) {
            if (!method_exists($bin, $accessor)) {
                continue;
            }

            $port = (int) $bin->$accessor();
            if ($port > 0) {
                $ports[$port] = $port;
            }
        }

        return array_values($ports);
    }

    /**
     * Probes a set of ports and reduces the results to a single health value.
     *
     * @param   array  $ports  List of port numbers.
     *
     * @return string A HEALTH_* constant.
     */
    private static function probeHealth(array $ports): string
    {
        if (empty($ports)) {
            return ServiceStatus::HEALTH_NOT_APPLICABLE;
        }

        $open = 0;
        foreach ($ports as $port) {
            if (self::probePort($port)) {
                $open++;
            }
        }

        if ($open === 0) {
            return ServiceStatus::HEALTH_UNREACHABLE;
        }

        if ($open < count($ports)) {
            return ServiceStatus::HEALTH_PARTIAL;
        }

        return ServiceStatus::HEALTH_OK;
    }

    /**
     * Attempts a bounded TCP connection to a port on the loopback interface.
     *
     * stream_socket_client() is used rather than fsockopen() because it takes an
     * explicit connect timeout. That matters: a filtered port would otherwise
     * block for the OS default, which on a 2 second poll is a visible stall.
     *
     * @param   int  $port  The port to probe.
     *
     * @return bool True when something accepted the connection.
     */
    private static function probePort(int $port): bool
    {
        $address = sprintf('tcp://%s:%d', APP_LOCALHOST, $port);

        $socket = @stream_socket_client($address, $errno, $errstr, self::PROBE_TIMEOUT);
        if ($socket === false) {
            return false;
        }

        fclose($socket);

        return true;
    }
}
