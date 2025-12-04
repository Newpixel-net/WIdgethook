<?php defined('ALTUMCODE') || die() ?>

<p><?= sprintf(l('cron.email_reports.p1', $data->row->language), $data->row->domain) ?></p>

<div>
    <table>
        <tbody>

        <!-- Impressions -->
        <tr>
            <td></td>
            <td><strong>👁<?= l('notification.statistics.impressions_chart', $data->row->language) ?></strong></td>
            <td></td>
        </tr>

        <tr>
            <td style="vertical-align: middle;">
                <?= \Altum\Date::get($data->previous_start_date, 5) . ' - ' . \Altum\Date::get($data->start_date, 5) ?>
            </td>

            <td>
                <span style="color: #808080 !important;">
                    <?= nr($data->previous_statistics['impression'] ?? 0) ?>
                </span>
            </td>
            <td></td>
        </tr>

        <tr>
            <td style="vertical-align: middle;">
                <?= \Altum\Date::get($data->start_date, 5) . ' - ' . \Altum\Date::get($data->date, 5) ?>
            </td>

            <td>
                <h3 style="margin-bottom: 0">
                    <?= nr($data->statistics['impression'] ?? 0) ?>
                </h3>
            </td>
            <td style="vertical-align: middle;">
                <?php $percentage = get_percentage_change($data->previous_statistics['impression'] ?? 0, $data->statistics['impression'] ?? 0) ?>

                <?php if(round($percentage) != 0): ?>
                    <?= round($percentage) > 0 ? '<span style="color: #28a745 !important;">+' . round($percentage, 0) . '%</span>' : '<span style="color: #dc3545 !important;">' . round($percentage, 0) . '%</span>'; ?>
                <?php endif ?>
            </td>
        </tr>

        <!-- Hovers -->
        <tr>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
        </tr>

        <tr>
            <td></td>
            <td><strong>🖐<?= l('notification.statistics.hovers_chart', $data->row->language) ?></strong></td>
            <td></td>
        </tr>

        <tr>
            <td style="vertical-align: middle;">
                <?= \Altum\Date::get($data->previous_start_date, 5) . ' - ' . \Altum\Date::get($data->start_date, 5) ?>
            </td>

            <td>
                <span style="color: #808080 !important;">
                    <?= nr($data->previous_statistics['hover'] ?? 0) ?>
                </span>
            </td>
            <td></td>
        </tr>

        <tr>
            <td style="vertical-align: middle;">
                <?= \Altum\Date::get($data->start_date, 5) . ' - ' . \Altum\Date::get($data->date, 5) ?>
            </td>

            <td>
                <h3 style="margin-bottom: 0">
                    <?= nr($data->statistics['hover'] ?? 0) ?>
                </h3>
            </td>
            <td style="vertical-align: middle;">
                <?php $percentage = get_percentage_change($data->previous_statistics['hover'] ?? 0, $data->statistics['hover'] ?? 0) ?>

                <?php if(round($percentage) != 0): ?>
                    <?= round($percentage) > 0 ? '<span style="color: #28a745 !important;">+' . round($percentage, 0) . '%</span>' : '<span style="color: #dc3545 !important;">' . round($percentage, 0) . '%</span>'; ?>
                <?php endif ?>
            </td>
        </tr>

        <!-- Clicks -->
        <tr>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
        </tr>

        <tr>
            <td></td>
            <td><strong>🖱<?= l('notification.statistics.clicks_chart', $data->row->language) ?></strong></td>
            <td></td>
        </tr>

        <tr>
            <td style="vertical-align: middle;">
                <?= \Altum\Date::get($data->previous_start_date, 5) . ' - ' . \Altum\Date::get($data->start_date, 5) ?>
            </td>

            <td>
                <span style="color: #808080 !important;">
                    <?= nr($data->previous_statistics['click'] ?? 0) ?>
                </span>
            </td>
            <td></td>
        </tr>

        <tr>
            <td style="vertical-align: middle;">
                <?= \Altum\Date::get($data->start_date, 5) . ' - ' . \Altum\Date::get($data->date, 5) ?>
            </td>

            <td>
                <h3 style="margin-bottom: 0">
                    <?= nr($data->statistics['click'] ?? 0) ?>
                </h3>
            </td>
            <td style="vertical-align: middle;">
                <?php $percentage = get_percentage_change($data->previous_statistics['click'] ?? 0, $data->statistics['click'] ?? 0) ?>

                <?php if(round($percentage) != 0): ?>
                    <?= round($percentage) > 0 ? '<span style="color: #28a745 !important;">+' . round($percentage, 0) . '%</span>' : '<span style="color: #dc3545 !important;">' . round($percentage, 0) . '%</span>'; ?>
                <?php endif ?>
            </td>
        </tr>

        <!-- Form submissions -->
        <tr>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
        </tr>

        <tr>
            <td></td>
            <td><strong><?= l('notification.statistics.form_submissions_chart', $data->row->language) ?></strong></td>
            <td></td>
        </tr>

        <tr>
            <td style="vertical-align: middle;">
                <?= \Altum\Date::get($data->previous_start_date, 5) . ' - ' . \Altum\Date::get($data->start_date, 5) ?>
            </td>

            <td>
                <span style="color: #808080 !important;">
                    <?= nr($data->previous_statistics['form_submission'] ?? 0) ?>
                </span>
            </td>
            <td></td>
        </tr>

        <tr>
            <td style="vertical-align: middle;">
                <?= \Altum\Date::get($data->start_date, 5) . ' - ' . \Altum\Date::get($data->date, 5) ?>
            </td>

            <td>
                <h3 style="margin-bottom: 0">
                    <?= nr($data->statistics['form_submission'] ?? 0) ?>
                </h3>
            </td>
            <td style="vertical-align: middle;">
                <?php $percentage = get_percentage_change($data->previous_statistics['form_submission'] ?? 0, $data->statistics['form_submission'] ?? 0) ?>

                <?php if(round($percentage) != 0): ?>
                    <?= round($percentage) > 0 ? '<span style="color: #28a745 !important;">+' . round($percentage, 0) . '%</span>' : '<span style="color: #dc3545 !important;">' . round($percentage, 0) . '%</span>'; ?>
                <?php endif ?>
            </td>
        </tr>

        </tbody>
    </table>
</div>

<div style="margin-top: 30px">
    <table border="0" cellpadding="0" cellspacing="0" class="btn btn-primary">
        <tbody>
        <tr>
            <td align="center">
                <table border="0" cellpadding="0" cellspacing="0">
                    <tbody>
                    <tr>
                        <td>
                            <a href="<?= url('campaign/' . $data->row->campaign_id . '/statistics') ?>">
                                <?= l('cron.email_reports.button', $data->row->language) ?>
                            </a>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </td>
        </tr>
        </tbody>
    </table>
</div>

<p style="text-align: center;">
    <small style="color: #808080 !important;"><?= sprintf(l('cron.email_reports.notice', $data->row->language), '<a href="' . url('campaign/' . $data->row->campaign_id) . '">', '</a>') ?></small>
</p>
