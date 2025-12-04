<?php defined('ALTUMCODE') || die() ?>

<div>
    <div class="form-group">
        <label for="branding"><?= l('admin_settings.notifications.branding') ?></label>
        <textarea id="branding" name="branding" class="form-control"><?= settings()->notifications->branding ?></textarea>
        <small class="form-text text-muted"><?= l('admin_settings.notifications.branding_help') ?></small>
    </div>

    <div class="form-group custom-control custom-switch">
        <input id="analytics_is_enabled" name="analytics_is_enabled" type="checkbox" class="custom-control-input" <?= settings()->notifications->analytics_is_enabled ? 'checked="checked"' : null?>>
        <label class="custom-control-label" for="analytics_is_enabled"><?= l('admin_settings.notifications.analytics_is_enabled') ?></label>
        <small class="form-text text-muted"><?= l('admin_settings.notifications.analytics_is_enabled_help') ?></small>
    </div>

    <div class="form-group">
        <label for="pixel_cache"><?= l('admin_settings.notifications.pixel_cache') ?></label>
        <div class="input-group">
            <input id="pixel_cache" type="number" min="0" name="pixel_cache" class="form-control" value="<?= settings()->notifications->pixel_cache ?>" />
            <div class="input-group-append">
                <span class="input-group-text"><?= l('global.date.seconds') ?></span>
            </div>
        </div>
        <small class="form-text text-muted"><?= l('admin_settings.notifications.pixel_cache_help') ?></small>
    </div>

    <div class="form-group">
        <label for="email_reports_is_enabled"><i class="fas fa-fw fa-sm fa-fire text-muted mr-1"></i> <?= l('admin_settings.notifications.email_reports_is_enabled') ?></label>
        <select id="email_reports_is_enabled" name="email_reports_is_enabled" class="custom-select">
            <option value="0" <?= !settings()->notifications->email_reports_is_enabled ? 'selected="selected"' : null ?>><?= l('global.disabled') ?></option>
            <option value="weekly" <?= settings()->notifications->email_reports_is_enabled == 'weekly' ? 'selected="selected"' : null ?>><?= l('admin_settings.notifications.email_reports_is_enabled_weekly') ?></option>
            <option value="monthly" <?= settings()->notifications->email_reports_is_enabled == 'monthly' ? 'selected="selected"' : null ?>><?= l('admin_settings.notifications.email_reports_is_enabled_monthly') ?></option>
        </select>
        <small class="form-text text-muted"><?= l('admin_settings.notifications.email_reports_is_enabled_help') ?></small>
    </div>

    <div class="form-group custom-control custom-switch">
        <input id="email_notices_is_enabled" name="email_notices_is_enabled" type="checkbox" class="custom-control-input" <?= settings()->notifications->email_notices_is_enabled ? 'checked="checked"' : null?>>
        <label class="custom-control-label" for="email_notices_is_enabled"><?= l('admin_settings.notifications.email_notices_is_enabled') ?></label>
        <small class="form-text text-muted"><?= l('admin_settings.notifications.email_notices_is_enabled_help') ?></small>
    </div>

    <div class="form-group custom-control custom-switch">
        <input id="domains_is_enabled" name="domains_is_enabled" type="checkbox" class="custom-control-input" <?= settings()->notifications->domains_is_enabled ? 'checked="checked"' : null?>>
        <label class="custom-control-label" for="domains_is_enabled"><?= l('admin_settings.notifications.domains_is_enabled') ?></label>
        <small class="form-text text-muted"><?= l('admin_settings.notifications.domains_is_enabled_help') ?></small>
    </div>

    <div class="form-group">
        <label for="domains_custom_main_ip"><?= l('admin_settings.notifications.domains_custom_main_ip') ?></label>
        <input id="domains_custom_main_ip" name="domains_custom_main_ip" type="text" class="form-control" value="<?= settings()->notifications->domains_custom_main_ip ?>" placeholder="<?= $_SERVER['SERVER_ADDR'] ?>">
        <small class="form-text text-muted"><?= l('admin_settings.notifications.domains_custom_main_ip_help') ?></small>
    </div>

    <div class="form-group">
        <label for="blacklisted_domains"><?= l('admin_settings.notifications.blacklisted_domains') ?></label>
        <textarea id="blacklisted_domains" class="form-control" name="blacklisted_domains"><?= implode(',', settings()->notifications->blacklisted_domains) ?></textarea>
        <small class="form-text text-muted"><?= l('admin_settings.notifications.blacklisted_domains_help') ?></small>
    </div>

    <?php foreach(['image', 'audio'] as $key): ?>
        <div class="form-group">
            <label for="<?= $key . '_size_limit' ?>"><?= l('admin_settings.notifications.' . $key . '_size_limit') ?></label>
            <div class="input-group">
                <input id="<?= $key . '_size_limit' ?>" type="number" min="0" max="<?= get_max_upload() ?>" step="any" name="<?= $key . '_size_limit' ?>" class="form-control" value="<?= settings()->notifications->{$key . '_size_limit'} ?>" />
                <div class="input-group-append">
                    <span class="input-group-text"><?= l('global.mb') ?></span>
                </div>
            </div>
            <small class="form-text text-muted"><?= l('global.accessibility.admin_file_size_limit_help') ?></small>
        </div>
    <?php endforeach ?>
</div>

<button type="submit" name="submit" class="btn btn-lg btn-block btn-primary mt-4"><?= l('global.update') ?></button>
