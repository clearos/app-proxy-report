<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'proxy_report';
$app['version'] = '1.4.35';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['description'] = lang('proxy_report_app_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('proxy_report_app_name');
$app['category'] = lang('base_category_reports');
$app['subcategory'] = lang('base_category_gateway');

/////////////////////////////////////////////////////////////////////////////
// Controllers
/////////////////////////////////////////////////////////////////////////////

$app['controllers']['proxy_report']['title'] = $app['name'];
$app['controllers']['settings']['title'] = lang('base_settings');

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['core_requires'] = array(
    'app-reports-core >= 1:1.4.2',
    'app-reports-database-core >= 1:1.4.8',
    'app-tasks-core',
    'perl',
    'perl-JSON',
    'perl-Time-modules'
);

$app['core_file_manifest'] = array(
    'app-proxy-report.cron' => array( 'target' => '/etc/cron.d/app-proxy-report'),
    'proxy2db' => array(
        'target' => '/usr/sbin/proxy2db',
        'mode' => '0755',
    ),
    'purge-proxy' => array(
        'target' => '/usr/sbin/purge-proxy',
        'mode' => '0755',
    ),
);

$app['delete_dependency'] = array(
    'app-proxy-report-core'
);
