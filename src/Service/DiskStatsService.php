<?php

namespace App\Service;

use Exception;

class DiskStatsService {
    /**
     * @return array<String, float>
     */
    public function getDiskStats(string $directory): array
    {
        $disk = [];

        $free = disk_free_space($directory);
        if ($free === false) {
            throw new Exception("Couldn't discover free disk space");
        }

        $total = disk_total_space($directory);
        if ($total === false) {
            throw new Exception("Couldn't discover free disk total space");
        }

        $disk['free'] = $free;
        $disk['total'] = $total;
        $disk['used'] = $disk['total'] - $disk['free'];
        $disk['percent'] = $disk['used'] / $disk['total'];
        return $disk;
    }
}
