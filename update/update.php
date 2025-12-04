<?php
/**
 * WidgetHook Update Backend
 * Updates are disabled for standalone installations
 */

die(json_encode([
    'status' => 'error',
    'message' => 'Updates are disabled for standalone installations. Please manage updates manually through your development workflow.'
]));
