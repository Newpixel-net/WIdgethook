<?php defined('ALTUMCODE') || die() ?>

<header class="header">
    <div class="container">
        <?php if(settings()->main->breadcrumbs_is_enabled): ?>
            <nav aria-label="breadcrumb">
                <ol class="custom-breadcrumbs small">
                    <li>
                        <a href="<?= url('dashboard') ?>"><?= l('dashboard.breadcrumb') ?></a><i class="fas fa-fw fa-angle-right"></i>
                    </li>
                    <li>
                        <a href="<?= url('campaign/' . $data->campaign->campaign_id) ?>"><?= l('campaign.breadcrumb') ?></a><i class="fas fa-fw fa-angle-right"></i>
                    </li>
                    <li class="active" aria-current="page"><?= l('notification_create.breadcrumb') ?></li>
                </ol>
            </nav>
        <?php endif ?>

        <h1 class="h2 mr-3"><i class="fas fa-fw fa-xs fa-window-maximize mr-1"></i> <?= l('notification_create.header') ?></h1>

        <div class="d-flex align-items-center text-muted mr-3">
            <img referrerpolicy="no-referrer" src="<?= get_favicon_url_from_domain($data->campaign->domain) ?>" class="img-fluid icon-favicon mr-1" loading="lazy" />

            <?= $data->campaign->domain ?>

            <a href="<?= 'https://' . $data->campaign->domain ?>" target="_blank" rel="noreferrer"><i class="fas fa-fw fa-xs fa-external-link-alt text-muted ml-1"></i></a>
        </div>
    </div>
</header>

<div class="container">

    <div class="mt-3">
        <?= \Altum\Alerts::output_alerts() ?>
    </div>

    <div class="my-6 mb-lg-0 d-flex flex-column flex-md-row justify-content-center align-items-center">
        <div id="notification_preview"></div>
    </div>

    <form name="create_notification" method="post" role="form">
        <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" required="required" />
        <input type="hidden" name="campaign_id" value="<?= $data->campaign->campaign_id ?>" />

        <div id="notifications" class="row d-flex align-items-stretch">
            <?php foreach($data->notifications as $notification_type => $notification_config): ?>

                <?php $notification = \Altum\Notification::get($notification_type, null, null, false, $data->campaign->branding) ?>

                    <label
                            class="col-12 col-md-6 col-lg-4 p-3 m-0 notification-radio-box"
                            <?= $this->user->plan_settings->enabled_notifications->{$notification_type} ? null : get_plan_feature_disabled_info() ?>
                    >

                        <input type="radio" name="type" value="<?= $notification_type ?>" class="custom-control-input" required="required" <?= $this->user->plan_settings->enabled_notifications->{$notification_type} ? null : 'disabled="disabled"' ?>>

                        <div class="card zoomer h-100 <?= $this->user->plan_settings->enabled_notifications->{$notification_type} ? null : 'container-disabled' ?>">
                            <div class="card-body d-flex">
                                <div class="mr-4 d-flex justify-content-center h-100">
                                    <div class="notification-avatar rounded-2x" style="background-color: <?= $data->notifications_config[$notification_type]['notification_background_color'] ?>; color: <?= $data->notifications_config[$notification_type]['notification_color'] ?>">
                                        <i class="<?= l('notification.' . mb_strtolower($notification_type) . '.icon') ?>"></i>
                                    </div>
                                </div>

                                <div>
                                    <div class="mb-2 font-weight-bold"><?= l('notification.' . mb_strtolower($notification_type) . '.name') ?></div>

                                    <p class="small text-muted mb-0"><?= l('notification.' . mb_strtolower($notification_type) . '.description') ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="preview" style="display: none">
                            <?= preg_replace(['/<form/', '/<\/form>/', '/required=\"required\"/'], ['<div', '</div>', ''], $notification->html) ?>
                        </div>

                    </label>
            <?php endforeach ?>
        </div>

        <div class="mt-4">
            <button type="submit" name="submit" class="btn btn-block btn-lg btn-primary"><?= l('global.create') ?></button>
        </div>
    </form>
</div>

<?php ob_start() ?>
<script>
    'use strict';

    $('#notifications .altumcode-hidden').removeClass('altumcode-hidden').addClass('altumcode-shown');
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>


<?php ob_start() ?>
<link href="<?= ASSETS_FULL_URL . 'css/pixel.min.css?v=' . PRODUCT_CODE ?>" rel="stylesheet" media="screen,print">
<?php \Altum\Event::add_content(ob_get_clean(), 'head') ?>

<?php ob_start() ?>
<script>
    'use strict';

    /* Preview handler */
    $('input[name="type"]').on('change', event => {

        let preview_html = $(event.currentTarget).closest('label').find('.preview').html();
        let type = $(event.currentTarget).val();

        $('#notification_preview').hide().html(preview_html).fadeIn();

        if(type.includes('_BAR')) {
            $('#notification_preview').removeClass().addClass('notification-create-preview-bar');
        } else {
            $('#notification_preview').removeClass().addClass('notification-create-preview-normal');
        }
    });

    /* Select a default option */
    $('input[name="type"]:first').attr('checked', true).trigger('change');
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
