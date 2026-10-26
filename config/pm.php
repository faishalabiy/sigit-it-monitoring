<?php

return [
    // Network probes are limited to trusted company HTTPS application domains.
    // Comma-separated exact domain suffixes; e.g. 'ptrekaindo.co.id'.
    'allowed_domains'=>array_filter(array_map('trim',explode(',',(string) env('PM_ALLOWED_DOMAINS','ptrekaindo.co.id')))),
    'connect_timeout'=>3,
    'timeout'=>6,
    // Known Laravel health endpoints. Avoid 302 auth redirects at app root.
    'http_health_paths'=>[
        'paduka.ptrekaindo.co.id'=>'/up',
        'sipo.ptrekaindo.co.id'=>'/up',
    ],

    // Shared server-local system metrics, never per-application metrics.
    'local_hosts'=>array_filter(array_map('trim',explode(',',(string) env('PM_LOCAL_HOST_IPS','')))),
    'local_disk_path'=>'/',
    // Explicit, non-user-supplied unit identifiers. No shell commands from UI.
    'infra_units'=>[
        'HOST_APACHE_STATUS'=>'apache2',
        'HOST_MYSQL_STATUS'=>'mysql',
        'HOST_PHP83_STATUS'=>'php8.3-fpm',
        'HOST_PHP84_STATUS'=>'php8.4-fpm',
        'HOST_CRON_STATUS'=>'cron',
    ],
    'queue_worker_paths'=>[
        'HOST_SIPO_QUEUE'=>'/home/server/Reka/sipo',
    ],
    // Only count a backup artifact in a configured, app-specific directory.
    'backup_directories'=>[
        'sipo.ptrekaindo.co.id'=>env('PM_SIPO_BACKUP_DIR'),
        'paduka.ptrekaindo.co.id'=>env('PM_PADUKA_BACKUP_DIR'),
        'sigit.ptrekaindo.co.id'=>env('PM_SIGIT_BACKUP_DIR'),
    ],


    // Synology NFS export for Proxmox: /volume1/Proxmox_Backup/dump on 192.168.10.10.
    // Mount the export read-only on the SIGIT server before enabling this source.
    // Shared VM ID must be verified against the Proxmox VM inventory; do not guess.
    'proxmox_backup'=>[
        'dump_directory'=>env('PM_PROXMOX_BACKUP_DIR', '/mnt/proxmox-backup/dump'),
        'shared_vm_id'=>env('PM_PROXMOX_SHARED_VM_ID'),
    ],

    // Fixed, private credentials for trusted application health endpoints.
    'health_clients'=>[
        'sipo.ptrekaindo.co.id'=>env('PM_SIPO_HEALTH_SECRET'),
        'paduka.ptrekaindo.co.id'=>env('PM_PADUKA_HEALTH_SECRET'),
    ],

];