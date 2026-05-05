<?php
/**
 * Warcry Admin Panel Gate
 * Stored as a one-way password_hash. It cannot be decrypted, only replaced.
 */
if (!defined('init_engine')) {
    header('HTTP/1.0 404 not found');
    exit;
}

$admin_panel_config = array(
    'enabled' => true,
    'code_hash' => '$2y$10$UHe/R8Bqrra2v/Zi9MGkrudDFQ02cvGcKSIAcV/ZLJFcyKoJj96Mi',
);
