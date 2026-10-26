<?php

namespace App\Services;

use RuntimeException;

class PmLocalHostService
{
    private ?array $metrics = null;

    public function appliesTo(?string $host): bool
    {
        $host = strtolower(trim((string) $host));
        if ($host === '') return false;
        foreach (config('pm.local_hosts', []) as $allowed) {
            if ($host === strtolower(trim((string) $allowed))) return true;
        }
        return false;
    }

    public function read(string $method): float
    {
        $this->metrics ??= $this->collect();
        if (! array_key_exists($method,$this->metrics)) {
            throw new RuntimeException('Unsupported local host metric.');
        }
        return $this->metrics[$method];
    }

    private function collect(): array
    {
        $start=$this->cpuSnapshot();
        usleep(350000);
        $end=$this->cpuSnapshot();
        $deltaTotal=$end['total']-$start['total'];
        if ($deltaTotal <= 0) throw new RuntimeException('Cannot measure CPU utilization');
        $deltaIdle=$end['idle']-$start['idle'];

        $mem=@file('/proc/meminfo',FILE_IGNORE_NEW_LINES);
        if (! is_array($mem)) throw new RuntimeException('Cannot read memory statistics');
        $memory=[];
        foreach ($mem as $line) {
            if (preg_match('/^(MemTotal|MemAvailable):\s+(\d+)/',$line,$m)) {
                $memory[$m[1]]=(float)$m[2];
            }
        }
        if (empty($memory['MemTotal']) || ! isset($memory['MemAvailable'])) {
            throw new RuntimeException('Memory statistics incomplete');
        }

        $path=(string) config('pm.local_disk_path','/');
        $total=@disk_total_space($path);
        $free=@disk_free_space($path);
        if (! $total || $free===false) throw new RuntimeException('Cannot read disk space');

        return [
            'cpu_usage'=>round(max(0,min(100,(1-$deltaIdle/$deltaTotal)*100)),2),
            'memory_usage'=>round(max(0,min(100,(1-$memory['MemAvailable']/$memory['MemTotal'])*100)),2),
            'disk_usage'=>round(max(0,min(100,(1-$free/$total)*100)),2),
        ];
    }

    private function cpuSnapshot(): array
    {
        $line=@file('/proc/stat',FILE_IGNORE_NEW_LINES)[0] ?? '';
        $parts=preg_split('/\s+/',trim($line));
        if (! is_array($parts) || ($parts[0]??'')!=='cpu' || count($parts)<5) {
            throw new RuntimeException('Cannot read CPU statistics');
        }
        $values=array_map('floatval',array_slice($parts,1));
        return [
            'total'=>array_sum($values),
            'idle'=>($values[3]??0)+($values[4]??0),
        ];
    }
}
