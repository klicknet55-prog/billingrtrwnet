<?php

register_menu("Clear System Cache", true, "clear_cache", 'SETTINGS', '');

function clear_cache()
{
    global $ui;
    _admin();
    $ui->assign('_title', 'Clear Cache');
    $ui->assign('_system_menu', 'settings');
    $admin = Admin::_info();
    $ui->assign('_admin', $admin);

    // Check user type for access
    if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
        _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        exit;
    }

    $compiledCacheDir = 'ui/compiled';
    $templateCacheDir = 'system/cache';

    try {
        // Clear the compiled cache
        $files = scandir($compiledCacheDir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..' && is_file($compiledCacheDir . '/' . $file)) {
                unlink($compiledCacheDir . '/' . $file);
            }
        }

        // Clear the template cache
        $templateCacheFiles = glob($templateCacheDir . '/*.{json,temp}', GLOB_BRACE);
        foreach ($templateCacheFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        // Cache cleared successfully
        _log('[' . ($admin['fullname'] ?? 'Unknown Admin') . ']: ' . Lang::T(' Cleared the system cache '), $admin['user_type']);
        r2(U . 'dashboard', 's', Lang::T("Cache cleared successfully!"));
    } catch (Exception $e) {
        // Error occurred while clearing the cache
        _log('[' . ($admin['fullname'] ?? 'Unknown Admin') . ']: ' . Lang::T(' Error occurred while clearing the cache: ' . $e->getMessage()), $admin['user_type']);
        r2(U . 'dashboard', 'e', Lang::T("Error occurred while clearing the cache: ") . $e->getMessage());
    }
}
