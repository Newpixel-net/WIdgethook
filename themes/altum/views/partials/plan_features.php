<?php defined('ALTUMCODE') || die() ?>


<?php $features = ((array) (settings()->payment->plan_features ?? [])) + array_fill_keys(require APP_PATH . 'includes/available_plan_features.php', true) ?>

<ul class="list-style-none m-0">
    <?php foreach($features as $feature => $is_enabled): ?>

        <?php if($is_enabled && $feature == 'campaigns_limit'): ?>
            <li class="d-flex align-items-baseline mb-2">
                <i class="fas fa-fw fa-sm mr-3 <?= $data->plan_settings->campaigns_limit ? 'fa-check-circle text-success' : 'fa-times-circle text-muted' ?>"></i>
                <div class="<?= $data->plan_settings->campaigns_limit ? null : 'text-muted' ?>">
                    <?= sprintf(l('global.plan_settings.campaigns_limit'), '<strong>' . ($data->plan_settings->campaigns_limit == -1 ? l('global.unlimited') : nr($data->plan_settings->campaigns_limit)) . '</strong>') ?>
                </div>
            </li>
        <?php endif ?>

        <?php if($is_enabled && $feature == 'notifications_limit'): ?>
            <li class="d-flex align-items-baseline mb-2">
                <i class="fas fa-fw fa-sm mr-3 <?= $data->plan_settings->notifications_limit ? 'fa-check-circle text-success' : 'fa-times-circle text-muted' ?>"></i>
                <div class="<?= $data->plan_settings->notifications_limit ? null : 'text-muted' ?>">
                    <?= sprintf(l('global.plan_settings.notifications_limit'), '<strong>' . ($data->plan_settings->notifications_limit == -1 ? l('global.unlimited') : nr($data->plan_settings->notifications_limit)) . '</strong>') ?>
                </div>
            </li>
        <?php endif ?>

        <?php if($is_enabled && $feature == 'notifications_impressions_limit'): ?>
            <li class="d-flex align-items-baseline mb-2">
                <i class="fas fa-fw fa-sm mr-3 <?= $data->plan_settings->notifications_impressions_limit ? 'fa-check-circle text-success' : 'fa-times-circle text-muted' ?>"></i>
                <div class="<?= $data->plan_settings->notifications_impressions_limit ? null : 'text-muted' ?>">
                    <?= sprintf(l('global.plan_settings.notifications_impressions_limit'), '<strong>' . ($data->plan_settings->notifications_impressions_limit == -1 ? l('global.unlimited') : nr($data->plan_settings->notifications_impressions_limit)) . '</strong>') ?>
                </div>
            </li>
        <?php endif ?>

        <?php if($is_enabled && $feature == 'domains_limit' && settings()->notifications->domains_is_enabled): ?>
            <li class="d-flex align-items-baseline mb-2">
                <i class="fas fa-fw fa-sm mr-3 <?= $data->plan_settings->domains_limit ? 'fa-check-circle text-success' : 'fa-times-circle text-muted' ?>"></i>
                <div class="<?= $data->plan_settings->domains_limit ? null : 'text-muted' ?>">
                    <?= sprintf(l('global.plan_settings.domains_limit'), '<strong>' . ($data->plan_settings->domains_limit == -1 ? l('global.unlimited') : nr($data->plan_settings->domains_limit)) . '</strong>') ?>
                </div>
            </li>
        <?php endif ?>

        <?php if($is_enabled && $feature == 'teams_limit' && \Altum\Plugin::is_active('teams')): ?>
            <li class="d-flex align-items-baseline mb-2">
                <i class="fas fa-fw fa-sm mr-3 <?= $data->plan_settings->teams_limit ? 'fa-check-circle text-success' : 'fa-times-circle text-muted' ?>"></i>
                <div class="<?= $data->plan_settings->teams_limit ? null : 'text-muted' ?>">
                    <?= sprintf(l('global.plan_settings.teams_limit'), '<strong>' . ($data->plan_settings->teams_limit == -1 ? l('global.unlimited') : nr($data->plan_settings->teams_limit)) . '</strong>') ?>
                    <span class="ml-1" data-toggle="tooltip" data-html="true" title="<?= sprintf(l('global.plan_settings.team_members_limit'), '<strong>' . ($data->plan_settings->team_members_limit == -1 ? l('global.unlimited') : nr($data->plan_settings->team_members_limit)) . '</strong>') ?>"><i class="fas fa-fw fa-xs fa-circle-question text-gray-500"></i></span>
                </div>
            </li>
        <?php endif ?>

        <?php if($is_enabled && $feature == 'affiliate_commission_percentage' && \Altum\Plugin::is_active('affiliate') && settings()->affiliate->is_enabled): ?>
            <li class="d-flex align-items-baseline mb-2">
                <i class="fas fa-fw fa-sm mr-3 <?= $data->plan_settings->affiliate_commission_percentage ? 'fa-check-circle text-success' : 'fa-times-circle text-muted' ?>"></i>
                <div class="<?= $data->plan_settings->affiliate_commission_percentage ? null : 'text-muted' ?>">
                    <?= sprintf(l('global.plan_settings.affiliate_commission_percentage'), '<strong>' . nr($data->plan_settings->affiliate_commission_percentage) . '%</strong>') ?>
                </div>
            </li>
        <?php endif ?>

        <?php if($is_enabled && $feature == 'notification_handlers_limit'): ?>
            <?php ob_start() ?>
            <?php $notification_handlers_icon = 'fa-times-circle text-muted'; ?>
            <div class='d-flex flex-column'>
                <?php foreach(array_keys(require APP_PATH . 'includes/notification_handlers.php') as $notification_handler): ?>
                    <?php if($data->plan_settings->{'notification_handlers_' . $notification_handler . '_limit'} != 0) $notification_handlers_icon = 'fa-check-circle text-success' ?>
                    <span class='my-1'><?= sprintf(l('global.plan_settings.notification_handlers_' . $notification_handler . '_limit'), '<strong>' . ($data->plan_settings->{'notification_handlers_' . $notification_handler . '_limit'} == -1 ? l('global.unlimited') : nr($data->plan_settings->{'notification_handlers_' . $notification_handler . '_limit'})) . '</strong>') ?></span>
                <?php endforeach ?>
            </div>
            <?php $html = ob_get_clean() ?>
            <li class="d-flex align-items-baseline mb-2">
                <i class="fas fa-fw fa-sm mr-3 <?= $notification_handlers_icon ?>"></i>
                <div>
                    <?= l('global.plan_settings.notification_handlers_limit') ?>
                    <span class="ml-1" data-toggle="tooltip" data-html="true" title="<?= $html ?>"><i class="fas fa-fw fa-xs fa-circle-question text-gray-500"></i></span>
                </div>
            </li>
        <?php endif ?>

        <?php if($is_enabled && $feature == 'track_notifications_retention'): ?>
            <li class="d-flex align-items-baseline mb-2">
                <i class="fas fa-fw fa-sm mr-3 <?= $data->plan_settings->track_notifications_retention ? 'fa-check-circle text-success' : 'fa-times-circle text-muted' ?>"></i>
                <div class="<?= $data->plan_settings->track_notifications_retention ? null : 'text-muted' ?>" data-toggle="tooltip" title="<?= ($data->plan_settings->track_notifications_retention == -1 ? '' : $data->plan_settings->track_notifications_retention . ' ' . l('global.date.days')) ?>">
                    <?= sprintf(l('global.plan_settings.track_notifications_retention'), '<strong>' . ($data->plan_settings->track_notifications_retention == -1 ? l('global.unlimited') : \Altum\Date::days_format($data->plan_settings->track_notifications_retention)) . '</strong>') ?>
                </div>
            </li>
        <?php endif ?>

        <?php if($is_enabled && $feature == 'track_conversions_retention'): ?>
            <li class="d-flex align-items-baseline mb-2">
                <i class="fas fa-fw fa-sm mr-3 <?= $data->plan_settings->track_conversions_retention ? 'fa-check-circle text-success' : 'fa-times-circle text-muted' ?>"></i>
                <div class="<?= $data->plan_settings->track_conversions_retention ? null : 'text-muted' ?>" data-toggle="tooltip" title="<?= ($data->plan_settings->track_conversions_retention == -1 ? '' : $data->plan_settings->track_conversions_retention . ' ' . l('global.date.days')) ?>">
                    <?= sprintf(l('global.plan_settings.track_conversions_retention'), '<strong>' . ($data->plan_settings->track_conversions_retention == -1 ? l('global.unlimited') : \Altum\Date::days_format($data->plan_settings->track_conversions_retention)) . '</strong>') ?>
                </div>
            </li>
        <?php endif ?>

        <?php if($is_enabled && $feature == 'enabled_notifications'): ?>
            <?php $enabled_notifications = array_filter((array) $data->plan_settings->enabled_notifications) ?>
            <?php $enabled_notifications_count = count($enabled_notifications) ?>
            <?php $enabled_notifications_string = implode(', ', array_map(function($key) {
                return l('notification.' . mb_strtolower($key) . '.name');
            }, array_keys($enabled_notifications))); ?>
            <li class="d-flex align-items-baseline mb-2">
                <i class="fas fa-fw fa-sm mr-3 <?= $enabled_notifications_count ? 'fa-check-circle text-success' : 'fa-times-circle text-muted' ?>"></i>
                <div class="<?= $enabled_notifications_count ? null : 'text-muted' ?>">
                    <?php if($enabled_notifications_count == count(\Altum\Notification::get_config())): ?>
                        <?= l('global.plan_settings.enabled_notifications_all') ?>
                    <?php else: ?>
                        <span data-toggle="tooltip" title="<?= $enabled_notifications_string ?>">
                            <?= sprintf(l('global.plan_settings.enabled_notifications_x'), '<strong>' . nr($enabled_notifications_count) . '</strong>') ?>
                        </span>
                    <?php endif ?>
                </div>
            </li>
        <?php endif ?>

        <?php if($is_enabled && $feature == 'email_reports_is_enabled' && settings()->notifications->email_reports_is_enabled): ?>
            <li class="d-flex align-items-baseline mb-2">
                <i class="fas fa-fw fa-sm mr-3 <?= $data->plan_settings->email_reports_is_enabled ? 'fa-check-circle text-success' : 'fa-times-circle text-muted' ?>"></i>
                <div class="<?= $data->plan_settings->email_reports_is_enabled ? null : 'text-muted' ?>">
                    <?= settings()->notifications->email_reports_is_enabled ? l('global.plan_settings.email_reports_is_enabled_' . settings()->notifications->email_reports_is_enabled) : l('global.plan_settings.email_reports_is_enabled') ?>
                </div>
            </li>
        <?php endif ?>

        <?php if($is_enabled && in_array($feature, ['no_ads', 'removable_branding', 'custom_branding', 'custom_css_is_enabled', 'api_is_enabled', 'white_labeling_is_enabled'])): ?>
            <li class="d-flex align-items-baseline mb-2">
                <i class="fas fa-fw fa-sm mr-3 <?= $data->plan_settings->{$feature} ? 'fa-check-circle text-success' : 'fa-times-circle text-muted' ?>"></i>
                <div class="<?= $data->plan_settings->{$feature} ? null : 'text-muted' ?>">
                    <?= l('global.plan_settings.' . $feature) ?>
                </div>
            </li>
        <?php endif ?>

        <?php if($is_enabled && $feature == 'global.plan_settings.export'): ?>
            <?php $enabled_exports_count = count(array_filter((array) $data->plan_settings->export)); ?>
            <?php ob_start() ?>
            <div class='d-flex flex-column'>
                <?php foreach(['csv', 'json', 'pdf'] as $key): ?>
                    <?php if($data->plan_settings->export->{$key}): ?>
                        <span class='my-1'><?= sprintf(l('global.export_to'), mb_strtoupper($key)) ?></span>
                    <?php else: ?>
                        <s class='my-1'><?= sprintf(l('global.export_to'), mb_strtoupper($key)) ?></s>
                    <?php endif ?>
                <?php endforeach ?>
            </div>
            <?php $html = ob_get_clean() ?>
            <li class="d-flex align-items-baseline mb-2">
                <i class="fas fa-fw fa-sm mr-3 <?= $enabled_exports_count ? 'fa-check-circle text-success' : 'fa-times-circle text-muted' ?>"></i>
                <div class="<?= $enabled_exports_count ? null : 'text-muted' ?>">
                    <?= sprintf(l('global.plan_settings.export'), $enabled_exports_count) ?>
                    <span class="mr-1" data-html="true" data-toggle="tooltip" title="<?= $html ?>"><i class="fas fa-fw fa-xs fa-circle-question text-gray-500"></i></span>
                </div>
            </li>
        <?php endif ?>

    <?php endforeach ?>
</ul>
