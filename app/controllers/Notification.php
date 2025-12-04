<?php
/*
 * Copyright (c) 2025 AltumCode (https://altumcode.com/)
 *
 * This software is licensed exclusively by AltumCode and is sold only via https://altumcode.com/.
 * Unauthorized distribution, modification, or use of this software without a valid license is not permitted and may be subject to applicable legal actions.
 *
 * 🌍 View all other existing AltumCode projects via https://altumcode.com/
 * 📧 Get in touch for support or general queries via https://altumcode.com/contact
 * 📤 Download the latest version via https://altumcode.com/downloads
 *
 * 🐦 X/Twitter: https://x.com/AltumCode
 * 📘 Facebook: https://facebook.com/altumcode
 * 📸 Instagram: https://instagram.com/altumcode
 */

namespace Altum\Controllers;

use Altum\Alerts;
use Altum\Date;
use Altum\Title;

defined('ALTUMCODE') || die();

class Notification extends Controller {
    public $notification_id;
    public $notification;
    public $campaign;

    public function index() {

        \Altum\Authentication::guard();

        $this->notification_id = isset($this->params[0]) ? (int) query_clean($this->params[0]) : null;
        $method = isset($this->params[1]) && in_array($this->params[1], ['settings', 'statistics', 'data']) ? $this->params[1] : 'settings';

        /* Make sure the notification exists and is accessible to the user */
        $this->notification = db()->where('notification_id', $this->notification_id)->where('user_id', $this->user->user_id)->getOne('notifications');

        if(!$this->notification) {
            redirect('dashboard');
        }

        /* Check for the plan limit */
        $notifications_total = database()->query("SELECT COUNT(*) AS `total` FROM `notifications` WHERE `user_id` = {$this->user->user_id} AND `campaign_id` = {$this->notification->campaign_id}")->fetch_object()->total;
        if($this->user->plan_settings->notifications_limit != -1 && $notifications_total >= $this->user->plan_settings->notifications_limit) {
            redirect('campaign/' . $this->notification->campaign_id);
        }

        /* Get the associated campaign */
        $this->campaign = db()->where('campaign_id', $this->notification->campaign_id)->getOne('campaigns');
        $this->campaign->branding = json_decode($this->campaign->branding);

        /* Set the branding, if needed, based on the campaign */
        if($this->campaign->branding && $this->campaign->branding->name && $this->campaign->branding->url) {
            $this->notification->branding = $this->campaign->branding;
        }

        /* Get the settings of the notification */
        $this->notification->settings = json_decode($this->notification->settings ?? '');
        $this->notification->notifications = json_decode($this->notification->notifications ?? '');

        switch($this->notification->type)  {
            case 'INFORMATIONAL':
            case 'INFORMATIONAL_MINI':
            case 'COUPON':
            case 'LIVE_COUNTER':
            case 'VIDEO':
            case 'AUDIO':
            case 'SOCIAL_SHARE':
            case 'EMOJI_FEEDBACK':
            case 'COOKIE_NOTIFICATION':
            case 'SCORE_FEEDBACK':
            case 'INFORMATIONAL_BAR':
            case 'INFORMATIONAL_BAR_MINI':
            case 'IMAGE':
            case 'COUPON_BAR':
            case 'BUTTON_BAR':
            case 'BUTTON_MODAL':
            case 'ENGAGEMENT_LINKS':
            case 'WHATSAPP_CHAT':
            case 'CUSTOM_HTML':
            case 'CONTACT_US':

                $this->notification->settings->enabled_methods = ['statistics', 'settings'];
                $this->notification->settings->enabled_settings_tabs = ['basic', 'display', 'customize', 'triggers'];

                break;

            case 'EMAIL_COLLECTOR':
            case 'CONVERSIONS':
            case 'CONVERSIONS_COUNTER':
            case 'REQUEST_COLLECTOR':
            case 'COUNTDOWN_COLLECTOR':
            case 'COLLECTOR_BAR':
            case 'COLLECTOR_MODAL':
            case 'COLLECTOR_TWO_MODAL':
            case 'TEXT_FEEDBACK':
            case 'REVIEWS':

                $this->notification->settings->enabled_methods = ['statistics', 'settings', 'data'];
                $this->notification->settings->enabled_settings_tabs = ['basic', 'display', 'customize', 'triggers', 'data'];

                break;
        }

        /* Prepare the menu View */
        $data = [
            'notification' => $this->notification,
            'method' => $method
        ];

        $view = new \Altum\View('notification/menu', (array) $this);

        $this->add_view_content('method_menu', $view->run($data));

        /* Handle code for different parts of the page */
        switch($method) {
            case 'settings':

                /* Get available notification handlers */
                $notification_handlers = (new \Altum\Models\NotificationHandlers())->get_notification_handlers_by_user_id($this->user->user_id);

                /* Handle form submission */
                $this->process_settings_post($notification_handlers);

                /* Prepare the method View */
                $data = [
                    'notification'          => $this->notification,
                    'method'                => $method,
                    'notification_handlers' => $notification_handlers
                ];

                $view = new \Altum\View('notification/' . $method . '.method', (array) $this);

                $this->add_view_content('method', $view->run($data));

                break;


            case 'statistics':

                $action = isset($this->params[2]) && in_array($this->params[2], ['reset']) ? $this->params[2] : null;

                if($action) {
                    switch($action) {
                        case 'reset':

                            if(empty($_POST)) {
                                redirect('campaigns');
                            }

                            /* Team checks */
                            if(\Altum\Teams::is_delegated() && !\Altum\Teams::has_access('delete.notifications')) {
                                Alerts::add_error(l('global.info_message.team_no_access'));
                                redirect('notification/' . $this->notification->notification_id . '/statistics');
                            }

                            //ALTUMCODE:DEMO if(DEMO) if($this->user->user_id == 1) Alerts::add_error('Please create an account on the demo to test out this function.');

                            if(!\Altum\Csrf::check()) {
                                Alerts::add_error(l('global.error_message.invalid_csrf_token'));
                                redirect('notification/' . $this->notification->notification_id . '/statistics');
                            }

                            $datetime = \Altum\Date::get_start_end_dates_new($_POST['start_date'], $_POST['end_date']);

                            if(!Alerts::has_field_errors() && !Alerts::has_errors()) {

                                /* Clear statistics data */
                                database()->query("DELETE FROM `track_notifications` WHERE `notification_id` = {$this->notification->notification_id} AND (`datetime` BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}')");

                                /* Set a nice success message */
                                Alerts::add_success(l('global.success_message.update2'));

                                redirect('notification/' . $this->notification->notification_id . '/statistics');

                            }

                            redirect('notification/' . $this->notification->notification_id . '/statistics');

                            break;
                    }
                }

                $datetime = \Altum\Date::get_start_end_dates_new();

                /* Query for the statistics of the notification */
                $logs = [];
                $logs_chart = [];
                $logs_total = [
                    'impression'        => 0,
                    'hover'             => 0,
                    'click'             => 0,
                    'ctr'               => 0,
                    'conversions'       => 0,
                    'form_submission'   => 0,
                ];

                $convert_tz_sql = get_convert_tz_sql('`datetime`', $this->user->timezone);

                /* Logs for the charts */
                $logs_result = database()->query("
                    SELECT
                         `type`,
                         COUNT(`id`) AS `total`,
                         DATE_FORMAT({$convert_tz_sql}, '{$datetime['query_date_format']}') AS `formatted_date`
                    FROM
                         `track_notifications`
                    WHERE
                        `notification_id` = {$this->notification->notification_id}
                        AND ({$convert_tz_sql} BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}')
                    GROUP BY
                        `formatted_date`,
                        `type`
                    ORDER BY
                        `formatted_date`
                ");

                /* Generate the raw chart data and save logs for later usage */
                while($row = $logs_result->fetch_object()) {
                    $logs[] = $row;

                    $row->formatted_date = $datetime['process']($row->formatted_date, true);

                    /* Handle if the date key is not already set */
                    if(!array_key_exists($row->formatted_date, $logs_chart)) {
                        $logs_chart[$row->formatted_date] = [
                            'impression'        => 0,
                            'hover'             => 0,
                            'click'             => 0,
                            'form_submission'   => 0,
                            'feedback_emoji_angry'    => 0,
                            'feedback_emoji_sad'      => 0,
                            'feedback_emoji_neutral'  => 0,
                            'feedback_emoji_happy'    => 0,
                            'feedback_emoji_excited'  => 0,
                            'feedback_score_1'  => 0,
                            'feedback_score_2'  => 0,
                            'feedback_score_3'  => 0,
                            'feedback_score_4'  => 0,
                            'feedback_score_5'  => 0,
                        ];
                    }

                    $logs_chart[$row->formatted_date][$row->type] = $row->total;

                    /* Count totals */
                    if(in_array($row->type, ['impression', 'hover', 'click', 'form_submission'])) {
                        $logs_total[$row->type] += $row->total;
                    }
                }

                /* CTR on mouse clicks */
                $logs_total['ctr'] = $logs_total['impression'] && $logs_total['click'] ? ($logs_total['click'] / $logs_total['impression']) * 100 : 0;

                /* Calculate form submissions conversions */
                $logs_total['conversions'] = $logs_total['impression'] && $logs_total['form_submission'] ? ($logs_total['form_submission'] / $logs_total['impression']) * 100 : 0;

                $logs_chart = get_chart_data($logs_chart);

                /* Get most accessed urls and their type of notification */
                $top_pages_result = database()->query("
                    SELECT 
                        DISTINCT `path`, 
                        `type`, 
                        COUNT(`id`) AS `total`
                    FROM 
                        `track_notifications`
                    WHERE
                        `notification_id` = {$this->notification->notification_id}
                        AND (`datetime` BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}')
                    GROUP BY 
                        `path`, 
                         `type` 
                    ORDER BY 
                        `total` DESC 
                    LIMIT 25
                ");

                /* Prepare the method View */
                $data = [
                    'notification' => $this->notification,
                    'method'=> $method,
                    'logs' => $logs,
                    'logs_chart' => $logs_chart,
                    'logs_total' => $logs_total,
                    'top_pages_result' => $top_pages_result,
                    'datetime' => $datetime,
                    'campaign' => $this->campaign,
                ];

                $view = new \Altum\View('notification/' . $method . '.method', (array) $this);

                $this->add_view_content('method', $view->run($data));

                break;


            case 'data':

                $datetime = \Altum\Date::get_start_end_dates_new();

                /* Prepare the paginator */
                $total_rows = database()->query("SELECT COUNT(*) AS `total` FROM `track_conversions` WHERE `notification_id` = {$this->notification->notification_id} AND (`datetime` BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}')")->fetch_object()->total ?? 0;
                $paginator = (new \Altum\Paginator($total_rows, 50, $_GET['page'] ?? 1, url('notification/' . $this->notification->notification_id . '/data?start_date=' . $datetime['start_date'] . '&end_date=' .$datetime['end_date'] . '&page=%d')));

                /* Get the data from the database */
                $conversions = [];

                $conversions_result = database()->query("SELECT `id`, `notification_id`, `type`, `data`, `location`, `path`, `datetime` FROM `track_conversions` WHERE `notification_id` = {$this->notification->notification_id} AND (`datetime` BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}') ORDER BY `id` DESC {$paginator->get_sql_limit()}");

                while($row = $conversions_result->fetch_object()) $conversions[] = $row;

                /* Prepare the pagination view */
                $pagination = (new \Altum\View('partials/pagination', (array) $this))->run(['paginator' => $paginator]);

                /* Handle JSON Export request */
                if(isset($_GET['json'])) {
                    header('Content-disposition: attachment; filename=data.json');
                    header('Content-type: application/json');

                    /* Prepare the json */
                    $conversions_json = [];

                    foreach($conversions as $row) {
                        $row->data = json_decode($row->data);
                        $row->location = json_decode($row->location);

                        $conversions_json[] = $row;
                    }

                    echo json_encode($conversions_json);

                    die();
                }

                /* Custom Data Import Modal */
                $modal = $this->notification->type == 'REVIEWS' ? 'data.create_review_data_modal.method' : 'data.create_data_modal.method';
                $data = ['notification' => $this->notification];
                $view = new \Altum\View('notification/data/' . $modal, (array) $this);
                \Altum\Event::add_content($view->run($data), 'modals');

                /* Prepare the method View */
                $data = [
                    'notification'      => $this->notification,
                    'method'            => $method,
                    'conversions'       => $conversions,
                    'pagination'        => $pagination,
                    'datetime'          => $datetime
                ];

                $view = new \Altum\View('notification/' . $method . '.method', (array) $this);

                $this->add_view_content('method', $view->run($data));

                break;
        }

        /* Prepare the view */
        $data = [
            'notification' => $this->notification,
            'campaign' => $this->campaign,
            'method' => $method
        ];

        $view = new \Altum\View('notification/index', (array) $this);

        $this->add_view_content('content', $view->run($data));

        /* Set a custom title */
        Title::set(sprintf(l('notification.title'), $this->notification->name));

    }

    private function process_settings_post($notification_handlers = []) {

        /* Handle the update of the notification */
        if(!empty($_POST)) {
            //ALTUMCODE:DEMO if(DEMO) if($this->user->user_id == 1) Alerts::add_error('Please create an account on the demo to test out this function.');

            /* Team checks */
            if(\Altum\Teams::is_delegated() && !\Altum\Teams::has_access('update.notifications')) {
                Alerts::add_error(l('global.info_message.team_no_access'));
                redirect('notification/' . $this->notification_id);
            }

            $_POST['name'] = input_clean($_POST['name'], 256);

            /* Trigger */
            $_POST['trigger_all_pages'] = (int) isset($_POST['trigger_all_pages']);
            $_POST['display_trigger'] = in_array($_POST['display_trigger'], [
                'delay',
                'time_on_site',
                'pageviews',
                'inactivity',
                'exit_intent',
                'scroll',
                'click',
                'hover',
            ]) ? $_POST['display_trigger'] : 'delay';
            $_POST['display_trigger_value'] = in_array($_POST['display_trigger'], ['delay', 'time_on_site', 'pageviews', 'inactivity', 'exit_intent', 'scroll']) ? (int) $_POST['display_trigger_value'] : input_clean($_POST['display_trigger_value']);

            $_POST['display_delay_type_after_close'] = isset($_POST['display_delay_type_after_close']) && in_array($_POST['display_delay_type_after_close'], ['time_on_site', 'pageviews',]) ? $_POST['display_delay_type_after_close'] : 'delay';
            $_POST['display_delay_value_after_close'] = (int) ($_POST['display_delay_value_after_close'] ?? 21600);

            $_POST['display_frequency'] = in_array($_POST['display_frequency'], [
                'all_time',
                'once_per_session',
                'once_per_browser',
            ]) ? $_POST['display_frequency'] : 'all_time';
            $_POST['direction'] = in_array($_POST['direction'], ['rtl', 'ltr']) ? $_POST['direction'] : 'ltr';

            /* Targeting */
            $_POST['display_continents'] = array_filter($_POST['display_continents'] ?? [], function($country) {
                return array_key_exists($country, get_continents_array());
            });

            $_POST['display_countries'] = array_filter($_POST['display_countries'] ?? [], function($country) {
                return array_key_exists($country, get_countries_array());
            });

            $_POST['display_languages'] = array_filter($_POST['display_languages'] ?? [], function($locale) {
                return array_key_exists($locale, get_locale_languages_array());
            });

            $_POST['display_operating_systems'] = array_filter($_POST['display_operating_systems'] ?? [], function($os_name) {
                return in_array($os_name, ['iOS', 'Android', 'Windows', 'OS X', 'Linux', 'Ubuntu', 'Chrome OS']);
            });

            $_POST['display_browsers'] = array_filter($_POST['display_browsers'] ?? [], function($browser_name) {
                return in_array($browser_name, ['Chrome', 'Firefox', 'Safari', 'Edge', 'Opera', 'Samsung Internet']);
            });

            $_POST['display_cities'] = explode(',', $_POST['display_cities']);
            if(count($_POST['display_cities'])) {
                $_POST['display_cities'] = array_map(function($city) {
                    return query_clean($city);
                }, $_POST['display_cities']);

                $_POST['display_cities'] = array_filter($_POST['display_cities'], function($city) {
                    return $city !== '';
                });

                $_POST['display_cities'] = array_unique($_POST['display_cities']);
            }

            $_POST['display_mobile'] = (int) isset($_POST['display_mobile']);
            $_POST['display_desktop'] = (int) isset($_POST['display_desktop']);


            $_POST['schedule'] = (int) isset($_POST['schedule']);
            if($_POST['schedule'] && !empty($_POST['start_date']) && !empty($_POST['end_date']) && Date::validate($_POST['start_date'], 'Y-m-d H:i:s') && Date::validate($_POST['end_date'], 'Y-m-d H:i:s')) {
                $_POST['start_date'] = (new \DateTime($_POST['start_date'], new \DateTimeZone($this->user->timezone)))->setTimezone(new \DateTimeZone(\Altum\Date::$default_timezone))->format('Y-m-d H:i:s');
                $_POST['end_date'] = (new \DateTime($_POST['end_date'], new \DateTimeZone($this->user->timezone)))->setTimezone(new \DateTimeZone(\Altum\Date::$default_timezone))->format('Y-m-d H:i:s');
            } else {
                $_POST['start_date'] = $_POST['end_date'] = null;
            }

            $_POST['display_duration'] = (int) $_POST['display_duration'];
            $_POST['display_position'] = in_array($_POST['display_position'], [
                'top_left',
                'top_center',
                'top_right',
                'middle_left',
                'middle_center',
                'middle_right',
                'bottom_left',
                'bottom_center',
                'bottom_right',
                'top',
                'bottom',
                'top_floating',
                'bottom_floating'
            ]) ? $_POST['display_position'] : 'bottom_left';
            $_POST['display_close_button'] = (int) isset($_POST['display_close_button']);
            $_POST['display_branding'] = (int) isset($_POST['display_branding']);

            $_POST['shadow'] = isset($_POST['shadow']) && in_array($_POST['shadow'], [
                '',
                'subtle',
                'feather',
                '3d',
                'layered'
            ]) ? $_POST['shadow'] : '';
            $_POST['border_width'] = (int) ($_POST['border_width'] >= 0 && $_POST['border_width'] <= 5 ? $_POST['border_width'] : 0);
            $_POST['internal_padding'] = (int) ($_POST['internal_padding'] >= 5 && $_POST['internal_padding'] <= 25 ? $_POST['internal_padding'] : 12);
            $_POST['background_blur'] = isset($_POST['background_blur']) && in_array((int) $_POST['background_blur'], range(0, 30)) ? (int) $_POST['background_blur'] : 0;
            $_POST['hover_animation'] = in_array($_POST['hover_animation'], [
                '',
                'fast_scale_up',
                'slow_scale_up',
                'fast_scale_down',
                'slow_scale_down',
            ]) ? $_POST['hover_animation'] : '';
            $_POST['on_animation'] = in_array($_POST['on_animation'], [
                'fadeIn',
                'slideInUp',
                'slideInDown',
                'zoomIn',
                'bounceIn',
            ]) ? $_POST['on_animation'] : 'fadeIn';
            $_POST['off_animation'] = in_array($_POST['off_animation'], [
                'fadeOut',
                'slideOutUp',
                'slideOutDown',
                'zoomOut',
                'bounceOut',
            ]) ? $_POST['off_animation'] : 'fadeOut';
            $_POST['animation'] = in_array($_POST['animation'], [
                '',
                'heartbeat',
                'bounce',
                'flash',
                'pulse',
            ]) ? $_POST['animation'] : '';
            $_POST['animation_interval'] = (int) $_POST['animation_interval'];
            $_POST['font'] = in_array($_POST['font'], [
                'inherit',
                'Arial',
                'Verdana',
                'Helvetica',
                'Tahoma',
                'Trebuchet MS',
                'Times New Roman',
                'Georgia',
                'Courier New',
                'Monaco',
                'Comic Sans MS',
                'Courier',
                'Impact',
                'Futura',
                'Luminari',
                'Baskerville',
                'Papyrus',
            ]) ? $_POST['font'] : 'inherit';

            $_POST['custom_css'] = mb_substr(trim($_POST['custom_css']), 0, 10000);

            /* Dark mode */
            $_POST['dark_mode_is_enabled'] = (int) isset($_POST['dark_mode_is_enabled']);

            /* Translations */
            $translations = [];

            /* Initiate purifier */
            $purifier_config = \HTMLPurifier_Config::createDefault();
            $purifier_config->set('HTML.Allowed', 'span[style]');
            $purifier_config->set('CSS.AllowedProperties', 'color,font-weight,font-style,text-decoration,font-family,background-color,text-transform,margin,padding,text-align');
            $purifier = new \HTMLPurifier($purifier_config);

            switch($this->notification->type) {

                case 'INFORMATIONAL':

                    /* Clean some posted variables */
                    $_POST['title'] = $purifier->purify(mb_substr($_POST['title'], 0, 256));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['title'])) foreach ($_POST['translations']['title'] as $translation) {
                        $translations['title'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 256));
                    }

                    $_POST['description'] = $purifier->purify(mb_substr($_POST['description'], 0, 512));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['description'])) foreach ($_POST['translations']['description'] as $translation) {
                        $translations['description'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 512));
                    }

                    if(!empty($_POST['translations']) && !empty($_POST['translations']['description'])) foreach ($_POST['translations']['description'] as $translation) {
                        $translations['description'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 512));
                    }

                    $image = \Altum\Uploads::process_upload($this->notification->settings->image, 'notifications', 'image', 'image_remove', settings()->notifications->image_size_limit);
                    $_POST['image_alt'] = mb_substr(query_clean($_POST['image_alt']), 0, 100);
                    $_POST['url'] = get_url($_POST['url']);
                    $_POST['url_new_tab'] = (int) isset($_POST['url_new_tab']);
                    $_POST['border_radius'] = in_array($_POST['border_radius'], [
                        'straight',
                        'rounded',
                        'highly_rounded',
                        'round',
                    ]) ? $_POST['border_radius'] : 'rounded';
                    break;

                case 'INFORMATIONAL_MINI':

                    /* Clean some posted variables */
                    $_POST['title'] = $purifier->purify(mb_substr($_POST['title'], 0, 256));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['title'])) foreach ($_POST['translations']['title'] as $translation) {
                        $translations['title'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 256));
                    }

                    $image = \Altum\Uploads::process_upload($this->notification->settings->image, 'notifications', 'image', 'image_remove', settings()->notifications->image_size_limit);
                    $_POST['image_alt'] = mb_substr(query_clean($_POST['image_alt']), 0, 100);
                    $_POST['url'] = get_url($_POST['url']);
                    $_POST['url_new_tab'] = (int) isset($_POST['url_new_tab']);
                    $_POST['border_radius'] = in_array($_POST['border_radius'], [
                        'straight',
                        'rounded',
                        'highly_rounded',
                        'round',
                    ]) ? $_POST['border_radius'] : 'rounded';
                    break;

                case 'COUPON':

                    /* Clean some posted variables */
                    $_POST['title'] = $purifier->purify(mb_substr($_POST['title'], 0, 256));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['title'])) foreach ($_POST['translations']['title'] as $translation) {
                        $translations['title'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 256));
                    }

                    $_POST['description'] = $purifier->purify(mb_substr($_POST['description'], 0, 512));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['description'])) foreach ($_POST['translations']['description'] as $translation) {
                        $translations['description'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 512));
                    }

                    $_POST['coupon_code'] = input_clean($_POST['coupon_code'], 64);
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['coupon_code'])) foreach ($_POST['translations']['coupon_code'] as $translation) {
                        $translations['coupon_code'][$translation['key']] = input_clean($translation['value'], 64);
                    }

                    $image = \Altum\Uploads::process_upload($this->notification->settings->image, 'notifications', 'image', 'image_remove', settings()->notifications->image_size_limit);
                    $_POST['image_alt'] = mb_substr(query_clean($_POST['image_alt']), 0, 100);
                    $_POST['button_url'] = get_url($_POST['button_url']);
                    $_POST['url_new_tab'] = (int) isset($_POST['url_new_tab']);
                    $_POST['button_text'] = $purifier->purify(mb_substr($_POST['button_text'], 0, 128));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['button_text'])) foreach ($_POST['translations']['button_text'] as $translation) {
                        $translations['button_text'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 128));
                    }
                    $_POST['border_radius'] = in_array($_POST['border_radius'], [
                        'straight',
                        'rounded',
                        'highly_rounded',
                    ]) ? $_POST['border_radius'] : 'rounded';
                    break;

                case 'LIVE_COUNTER':

                    /* Clean some posted variables */
                    $_POST['description'] = $purifier->purify(mb_substr($_POST['description'], 0, 512));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['description'])) foreach ($_POST['translations']['description'] as $translation) {
                        $translations['description'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 512));
                    }

                    $_POST['last_activity'] = (int) $_POST['last_activity'];
                    $_POST['url'] = get_url($_POST['url']);
                    $_POST['url_new_tab'] = (int) isset($_POST['url_new_tab']);
                    $_POST['display_minimum_activity'] = (int) $_POST['display_minimum_activity'];
                    $_POST['border_radius'] = in_array($_POST['border_radius'], [
                        'straight',
                        'round',
                        'rounded',
                        'highly_rounded',
                    ]) ? $_POST['border_radius'] : 'rounded';
                    break;

                case 'EMAIL_COLLECTOR' :

                    /* Clean some posted variables */
                    $_POST['title'] = $purifier->purify(mb_substr($_POST['title'], 0, 256));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['title'])) foreach ($_POST['translations']['title'] as $translation) {
                        $translations['title'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 256));
                    }

                    $_POST['description'] = $purifier->purify(mb_substr($_POST['description'], 0, 512));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['description'])) foreach ($_POST['translations']['description'] as $translation) {
                        $translations['description'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 512));
                    }

                    $_POST['name_placeholder'] = input_clean($_POST['name_placeholder'], 128);
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['name_placeholder'])) foreach ($_POST['translations']['name_placeholder'] as $translation) {
                        $translations['name_placeholder'][$translation['key']] = input_clean($translation['value'], 128);
                    }

                    $_POST['email_placeholder'] = input_clean($_POST['email_placeholder'], 128);
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['email_placeholder'])) foreach ($_POST['translations']['email_placeholder'] as $translation) {
                        $translations['email_placeholder'][$translation['key']] = input_clean($translation['value'], 128);
                    }

                    $_POST['button_text'] = $purifier->purify(mb_substr($_POST['button_text'], 0, 128));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['button_text'])) foreach ($_POST['translations']['button_text'] as $translation) {
                        $translations['button_text'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 128));
                    }
                    $_POST['show_agreement'] = (int) isset($_POST['show_agreement']);
                    $_POST['agreement_text'] = input_clean($_POST['agreement_text'], 256);
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['agreement_text'])) foreach ($_POST['translations']['agreement_text'] as $translation) {
                        $translations['agreement_text'][$translation['key']] = input_clean($translation['value'], 256);
                    }

                    $_POST['agreement_url'] = get_url($_POST['agreement_url']);
                    $_POST['thank_you_url'] = get_url($_POST['thank_you_url']);
                    $_POST['border_radius'] = in_array($_POST['border_radius'], [
                        'straight',
                        'rounded',
                        'highly_rounded',
                    ]) ? $_POST['border_radius'] : 'rounded';


                    break;

                case 'CONVERSIONS':

                    /* Clean some posted variables */
                    $_POST['title'] = $purifier->purify(mb_substr($_POST['title'], 0, 256));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['title'])) foreach ($_POST['translations']['title'] as $translation) {
                        $translations['title'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 256));
                    }

                    $_POST['description'] = $purifier->purify(mb_substr($_POST['description'], 0, 512));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['description'])) foreach ($_POST['translations']['description'] as $translation) {
                        $translations['description'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 512));
                    }

                    $image = \Altum\Uploads::process_upload($this->notification->settings->image, 'notifications', 'image', 'image_remove', settings()->notifications->image_size_limit);
                    $_POST['image_alt'] = mb_substr(query_clean($_POST['image_alt']), 0, 100);
                    $_POST['url'] = get_url($_POST['url']);
                    $_POST['display_time'] = (int) isset($_POST['display_time']);
                    $_POST['url_new_tab'] = (int) isset($_POST['url_new_tab']);
                    $_POST['conversions_count'] = (int) $_POST['conversions_count'] < 1 ? 1 : (int) $_POST['conversions_count'];
                    $_POST['in_between_delay'] = (int) $_POST['in_between_delay'] < 1 ? 0 : (int) $_POST['in_between_delay'];
                    $_POST['order'] = in_array($_POST['order'], ['descending', 'random']) ? $_POST['order'] : 'descending';
                    $_POST['border_radius'] = in_array($_POST['border_radius'], [
                        'straight',
                        'rounded',
                        'highly_rounded',
                        'round',
                    ]) ? $_POST['border_radius'] : 'rounded';
                    $_POST['data_trigger_auto'] = (int) isset($_POST['data_trigger_auto']);

                    break;

                case 'CONVERSIONS_COUNTER':

                    /* Clean some posted variables */
                    $_POST['title'] = $purifier->purify(mb_substr($_POST['title'], 0, 256));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['title'])) foreach ($_POST['translations']['title'] as $translation) {
                        $translations['title'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 256));
                    }

                    $_POST['last_activity'] = (int) $_POST['last_activity'];
                    $_POST['url'] = get_url($_POST['url']);
                    $_POST['url_new_tab'] = (int) isset($_POST['url_new_tab']);
                    $_POST['display_minimum_activity'] = (int) $_POST['display_minimum_activity'];
                    $_POST['border_radius'] = in_array($_POST['border_radius'], [
                        'straight',
                        'rounded',
                        'highly_rounded',
                        'round',
                    ]) ? $_POST['border_radius'] : 'rounded';
                    $_POST['data_trigger_auto'] = (int) isset($_POST['data_trigger_auto']);

                    break;

                case 'VIDEO':

                    /* Clean some posted variables */
                    $_POST['title'] = $purifier->purify(mb_substr($_POST['title'], 0, 256));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['title'])) foreach ($_POST['translations']['title'] as $translation) {
                        $translations['title'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 256));
                    }

                    $_POST['video'] = query_clean($_POST['video']);
                    $_POST['button_url'] = get_url($_POST['button_url']);
                    $_POST['button_text'] = $purifier->purify(mb_substr($_POST['button_text'], 0, 128));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['button_text'])) foreach ($_POST['translations']['button_text'] as $translation) {
                        $translations['button_text'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 128));
                    }
                    $_POST['border_radius'] = in_array($_POST['border_radius'], [
                        'straight',
                        'rounded',
                        'highly_rounded',
                    ]) ? $_POST['border_radius'] : 'rounded';
                    $_POST['url_new_tab'] = (int) isset($_POST['url_new_tab']);
                    $_POST['video_autoplay'] = (int) isset($_POST['video_autoplay']);
                    $_POST['video_controls'] = (int) isset($_POST['video_controls']);
                    $_POST['video_loop'] = (int) isset($_POST['video_loop']);
                    $_POST['video_muted'] = (int) isset($_POST['video_muted']);

                    /* Parse YouTube and Vimeo video links */
                    $video_url = $_POST['video'];
                    $youtube_match = [];
                    $vimeo_match = [];

                    if(preg_match('/(?:https?:\/\/)?(?:www\.)?(?:youtube-nocookie\.com\/youtu\.be\/|youtube\.com\/(?:embed\/|shorts\/|v\/|watch\?v=|watch\?.+&v=))((?:\w|-){11})/', $video_url, $youtube_match)) {
                        $_POST['video'] = 'https://www.youtube.com/embed/' . $youtube_match[1];
                        $_POST['video_is_youtube'] = true;
                        $_POST['youtube_video_id'] = $youtube_match[1];
                        $_POST['video_is_vimeo'] = false;
                        $_POST['vimeo_video_id'] = null;
                    } elseif(preg_match('/(?:https?:\/\/)?(?:www\.)?vimeo\.com\/(?:video\/)?(\d+)/', $video_url, $vimeo_match)) {
                        $_POST['video'] = 'https://player.vimeo.com/video/' . $vimeo_match[1];
                        $_POST['video_is_vimeo'] = true;
                        $_POST['vimeo_video_id'] = $vimeo_match[1];
                        $_POST['video_is_youtube'] = false;
                        $_POST['youtube_video_id'] = null;
                    } else {
                        $_POST['video_is_youtube'] = false;
                        $_POST['youtube_video_id'] = null;
                        $_POST['video_is_vimeo'] = false;
                        $_POST['vimeo_video_id'] = null;
                    }

                    break;

                case 'AUDIO':

                    /* Clean some posted variables */
                    $_POST['title'] = $purifier->purify(mb_substr($_POST['title'], 0, 256));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['title'])) foreach ($_POST['translations']['title'] as $translation) {
                        $translations['title'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 256));
                    }

                    $audio = \Altum\Uploads::process_upload($this->notification->settings->audio, 'notifications_audios', 'audio', 'audio_remove', settings()->notifications->audio_size_limit);
                    $_POST['button_url'] = get_url($_POST['button_url']);
                    $_POST['button_text'] = $purifier->purify(mb_substr($_POST['button_text'], 0, 128));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['button_text'])) foreach ($_POST['translations']['button_text'] as $translation) {
                        $translations['button_text'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 128));
                    }
                    $_POST['border_radius'] = in_array($_POST['border_radius'], [
                        'straight',
                        'rounded',
                        'highly_rounded',
                    ]) ? $_POST['border_radius'] : 'rounded';
                    $_POST['url_new_tab'] = (int) isset($_POST['url_new_tab']);
                    $_POST['audio_autoplay'] = (int) isset($_POST['audio_autoplay']);
                    $_POST['audio_controls'] = (int) isset($_POST['audio_controls']);
                    $_POST['audio_loop'] = (int) isset($_POST['audio_loop']);
                    $_POST['audio_muted'] = (int) isset($_POST['audio_muted']);

                    break;

                case 'SOCIAL_SHARE':

                    /* Clean some posted variables */
                    $_POST['title'] = $purifier->purify(mb_substr($_POST['title'], 0, 256));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['title'])) foreach ($_POST['translations']['title'] as $translation) {
                        $translations['title'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 256));
                    }

                    $_POST['description'] = $purifier->purify(mb_substr($_POST['description'], 0, 512));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['description'])) foreach ($_POST['translations']['description'] as $translation) {
                        $translations['description'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 512));
                    }

                    $_POST['share_url'] = get_url($_POST['share_url']);

                    $_POST['share_x'] = (int) isset($_POST['share_x']);
                    $_POST['share_threads'] = (int) isset($_POST['share_threads']);
                    $_POST['share_facebook'] = (int) isset($_POST['share_facebook']);
                    $_POST['share_reddit'] = (int) isset($_POST['share_reddit']);
                    $_POST['share_tumblr'] = (int) isset($_POST['share_tumblr']);
                    $_POST['share_linkedin'] = (int) isset($_POST['share_linkedin']);
                    $_POST['share_telegram'] = (int) isset($_POST['share_telegram']);
                    $_POST['share_whatsapp'] = (int) isset($_POST['share_whatsapp']);

                    $_POST['border_radius'] = in_array($_POST['border_radius'], [
                        'straight',
                        'rounded',
                        'highly_rounded',
                    ]) ? $_POST['border_radius'] : 'rounded';

                    break;

                case 'REVIEWS':

                    /* Clean some posted variables */
                    $_POST['url'] = get_url($_POST['url']);
                    $_POST['url_new_tab'] = (int) isset($_POST['url_new_tab']);
                    $_POST['reviews_count'] = (int) $_POST['reviews_count'] < 1 ? 1 : (int) $_POST['reviews_count'];
                    $_POST['in_between_delay'] = (int) $_POST['in_between_delay'] < 1 ? 0 : (int) $_POST['in_between_delay'];
                    $_POST['order'] = in_array($_POST['order'], ['descending', 'random']) ? $_POST['order'] : 'descending';
                    $_POST['border_radius'] = in_array($_POST['border_radius'], [
                        'straight',
                        'rounded',
                        'highly_rounded',
                        'round',
                    ]) ? $_POST['border_radius'] : 'rounded';

                    break;

                case 'EMOJI_FEEDBACK':

                    /* Clean some posted variables */
                    $_POST['title'] = $purifier->purify(mb_substr($_POST['title'], 0, 256));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['title'])) foreach ($_POST['translations']['title'] as $translation) {
                        $translations['title'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 256));
                    }

                    $_POST['thank_you_url'] = get_url($_POST['thank_you_url']);
                    $_POST['show_angry'] = (int) isset($_POST['show_angry']);
                    $_POST['show_sad'] = (int) isset($_POST['show_sad']);
                    $_POST['show_neutral'] = (int) isset($_POST['show_neutral']);
                    $_POST['show_happy'] = (int) isset($_POST['show_happy']);
                    $_POST['show_excited'] = (int) isset($_POST['show_excited']);
                    $_POST['border_radius'] = in_array($_POST['border_radius'], [
                        'straight',
                        'rounded',
                        'highly_rounded',
                    ]) ? $_POST['border_radius'] : 'rounded';

                    break;

                case 'COOKIE_NOTIFICATION':

                    /* Clean some posted variables */
                    $_POST['description'] = $purifier->purify(mb_substr($_POST['description'], 0, 512));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['description'])) foreach ($_POST['translations']['description'] as $translation) {
                        $translations['description'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 512));
                    }

                    $image = \Altum\Uploads::process_upload($this->notification->settings->image, 'notifications', 'image', 'image_remove', settings()->notifications->image_size_limit);
                    $_POST['image_alt'] = mb_substr(query_clean($_POST['image_alt']), 0, 100);
                    $_POST['url_text'] = input_clean($_POST['url_text'], 256);
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['url_text'])) foreach ($_POST['translations']['url_text'] as $translation) {
                        $translations['url_text'][$translation['key']] = input_clean($translation['value'], 256);
                    }

                    $_POST['url'] = get_url($_POST['url']);
                    $_POST['button_text'] = $purifier->purify(mb_substr($_POST['button_text'], 0, 128));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['button_text'])) foreach ($_POST['translations']['button_text'] as $translation) {
                        $translations['button_text'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 128));
                    }
                    $_POST['border_radius'] = in_array($_POST['border_radius'], [
                        'straight',
                        'rounded',
                        'highly_rounded',
                    ]) ? $_POST['border_radius'] : 'rounded';

                    break;

                case 'SCORE_FEEDBACK':

                    /* Clean some posted variables */
                    $_POST['title'] = $purifier->purify(mb_substr($_POST['title'], 0, 256));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['title'])) foreach ($_POST['translations']['title'] as $translation) {
                        $translations['title'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 256));
                    }

                    $_POST['description'] = $purifier->purify(mb_substr($_POST['description'], 0, 512));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['description'])) foreach ($_POST['translations']['description'] as $translation) {
                        $translations['description'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 512));
                    }

                    $_POST['thank_you_url'] = get_url($_POST['thank_you_url']);
                    $_POST['border_radius'] = in_array($_POST['border_radius'], [
                        'straight',
                        'rounded',
                        'highly_rounded',
                    ]) ? $_POST['border_radius'] : 'rounded';

                    break;

                case 'REQUEST_COLLECTOR' :

                    /* Clean some posted variables */
                    $_POST['title'] = $purifier->purify(mb_substr($_POST['title'], 0, 256));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['title'])) foreach ($_POST['translations']['title'] as $translation) {
                        $translations['title'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 256));
                    }

                    $_POST['description'] = $purifier->purify(mb_substr($_POST['description'], 0, 512));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['description'])) foreach ($_POST['translations']['description'] as $translation) {
                        $translations['description'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 512));
                    }

                    $image = \Altum\Uploads::process_upload($this->notification->settings->image, 'notifications', 'image', 'image_remove', settings()->notifications->image_size_limit);
                    $_POST['image_alt'] = mb_substr(query_clean($_POST['image_alt']), 0, 100);

                    $_POST['content_title'] = mb_substr(query_clean($_POST['content_title']), 0, 256);
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['content_title'])) foreach ($_POST['translations']['content_title'] as $translation) {
                        $translations['content_title'][$translation['key']] = mb_substr(query_clean($translation['value']), 0, 256);
                    }

                    $_POST['content_description'] = mb_substr(query_clean($_POST['content_description']), 0, 512);
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['content_description'])) foreach ($_POST['translations']['content_description'] as $translation) {
                        $translations['content_description'][$translation['key']] = mb_substr(query_clean($translation['value']), 0, 512);
                    }

                    $_POST['input_placeholder'] = mb_substr(query_clean($_POST['input_placeholder']), 0, 128);
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['input_placeholder'])) foreach ($_POST['translations']['input_placeholder'] as $translation) {
                        $translations['input_placeholder'][$translation['key']] = mb_substr(query_clean($translation['value']), 0, 128);
                    }

                    $_POST['button_text'] = $purifier->purify(mb_substr($_POST['button_text'], 0, 128));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['button_text'])) foreach ($_POST['translations']['button_text'] as $translation) {
                        $translations['button_text'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 128));
                    }
                    $_POST['show_agreement'] = (int) isset($_POST['show_agreement']);
                    $_POST['agreement_text'] = input_clean($_POST['agreement_text'], 256);
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['agreement_text'])) foreach ($_POST['translations']['agreement_text'] as $translation) {
                        $translations['agreement_text'][$translation['key']] = input_clean($translation['value'], 256);
                    }

                    $_POST['agreement_url'] = get_url($_POST['agreement_url']);
                    $_POST['thank_you_url'] = get_url($_POST['thank_you_url']);
                    $_POST['border_radius'] = in_array($_POST['border_radius'], [
                        'straight',
                        'rounded',
                        'highly_rounded',
                    ]) ? $_POST['border_radius'] : 'rounded';

                    break;

                case 'COUNTDOWN_COLLECTOR' :

                    /* Clean some posted variables */
                    $_POST['title'] = $purifier->purify(mb_substr($_POST['title'], 0, 256));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['title'])) foreach ($_POST['translations']['title'] as $translation) {
                        $translations['title'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 256));
                    }

                    $_POST['description'] = $purifier->purify(mb_substr($_POST['description'], 0, 512));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['description'])) foreach ($_POST['translations']['description'] as $translation) {
                        $translations['description'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 512));
                    }

                    $_POST['content_title'] = mb_substr(query_clean($_POST['content_title']), 0, 256);
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['content_title'])) foreach ($_POST['translations']['content_title'] as $translation) {
                        $translations['content_title'][$translation['key']] = mb_substr(query_clean($translation['value']), 0, 256);
                    }

                    $_POST['input_placeholder'] = mb_substr(query_clean($_POST['input_placeholder']), 0, 128);
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['input_placeholder'])) foreach ($_POST['translations']['input_placeholder'] as $translation) {
                        $translations['input_placeholder'][$translation['key']] = mb_substr(query_clean($translation['value']), 0, 128);
                    }

                    $_POST['button_text'] = $purifier->purify(mb_substr($_POST['button_text'], 0, 128));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['button_text'])) foreach ($_POST['translations']['button_text'] as $translation) {
                        $translations['button_text'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 128));
                    }
                    $_POST['countdown_end_date'] = (new \DateTime($_POST['countdown_end_date'], new \DateTimeZone($this->user->timezone)))->setTimezone(new \DateTimeZone(\Altum\Date::$default_timezone))->format('Y-m-d H:i:s');
                    $_POST['show_agreement'] = (int) isset($_POST['show_agreement']);
                    $_POST['agreement_text'] = input_clean($_POST['agreement_text'], 256);
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['agreement_text'])) foreach ($_POST['translations']['agreement_text'] as $translation) {
                        $translations['agreement_text'][$translation['key']] = input_clean($translation['value'], 256);
                    }

                    $_POST['agreement_url'] = get_url($_POST['agreement_url']);
                    $_POST['thank_you_url'] = get_url($_POST['thank_you_url']);
                    $_POST['border_radius'] = in_array($_POST['border_radius'], [
                        'straight',
                        'rounded',
                        'highly_rounded',
                    ]) ? $_POST['border_radius'] : 'rounded';

                    break;

                case 'INFORMATIONAL_BAR':

                    /* Clean some posted variables */
                    $_POST['title'] = $purifier->purify(mb_substr($_POST['title'], 0, 256));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['title'])) foreach ($_POST['translations']['title'] as $translation) {
                        $translations['title'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 256));
                    }

                    $_POST['description'] = $purifier->purify(mb_substr($_POST['description'], 0, 512));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['description'])) foreach ($_POST['translations']['description'] as $translation) {
                        $translations['description'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 512));
                    }

                    $image = \Altum\Uploads::process_upload($this->notification->settings->image, 'notifications', 'image', 'image_remove', settings()->notifications->image_size_limit);
                    $_POST['image_alt'] = mb_substr(query_clean($_POST['image_alt']), 0, 100);
                    $_POST['url'] = get_url($_POST['url']);
                    $_POST['url_new_tab'] = (int) isset($_POST['url_new_tab']);
                    $_POST['border_radius'] = 'straight';

                    break;

                case 'INFORMATIONAL_BAR_MINI':

                    /* Clean some posted variables */
                    $_POST['title'] = $purifier->purify(mb_substr($_POST['title'], 0, 256));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['title'])) foreach ($_POST['translations']['title'] as $translation) {
                        $translations['title'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 256));
                    }

                    $image = \Altum\Uploads::process_upload($this->notification->settings->image, 'notifications', 'image', 'image_remove', settings()->notifications->image_size_limit);
                    $_POST['image_alt'] = mb_substr(query_clean($_POST['image_alt']), 0, 100);
                    $_POST['url'] = get_url($_POST['url']);
                    $_POST['url_new_tab'] = (int) isset($_POST['url_new_tab']);
                    $_POST['border_radius'] = 'straight';

                    break;

                case 'IMAGE':

                    /* Clean some posted variables */
                    $_POST['title'] = $purifier->purify(mb_substr($_POST['title'], 0, 256));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['title'])) foreach ($_POST['translations']['title'] as $translation) {
                        $translations['title'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 256));
                    }

                    $image = \Altum\Uploads::process_upload($this->notification->settings->image, 'notifications', 'image', 'image_remove', settings()->notifications->image_size_limit);
                    $_POST['image_alt'] = mb_substr(query_clean($_POST['image_alt']), 0, 100);
                    $_POST['button_url'] = get_url($_POST['button_url']);
                    $_POST['button_text'] = $purifier->purify(mb_substr($_POST['button_text'], 0, 128));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['button_text'])) foreach ($_POST['translations']['button_text'] as $translation) {
                        $translations['button_text'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 128));
                    }
                    $_POST['border_radius'] = in_array($_POST['border_radius'], [
                        'straight',
                        'rounded',
                        'highly_rounded',
                    ]) ? $_POST['border_radius'] : 'rounded';

                    break;

                case 'COLLECTOR_BAR' :

                    /* Clean some posted variables */
                    $_POST['title'] = $purifier->purify(mb_substr($_POST['title'], 0, 256));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['title'])) foreach ($_POST['translations']['title'] as $translation) {
                        $translations['title'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 256));
                    }

                    $_POST['input_placeholder'] = mb_substr(query_clean($_POST['input_placeholder']), 0, 128);
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['input_placeholder'])) foreach ($_POST['translations']['input_placeholder'] as $translation) {
                        $translations['input_placeholder'][$translation['key']] = mb_substr(query_clean($translation['value']), 0, 128);
                    }

                    $_POST['button_text'] = $purifier->purify(mb_substr($_POST['button_text'], 0, 128));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['button_text'])) foreach ($_POST['translations']['button_text'] as $translation) {
                        $translations['button_text'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 128));
                    }
                    $_POST['show_agreement'] = (int) isset($_POST['show_agreement']);
                    $_POST['agreement_text'] = input_clean($_POST['agreement_text'], 256);
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['agreement_text'])) foreach ($_POST['translations']['agreement_text'] as $translation) {
                        $translations['agreement_text'][$translation['key']] = input_clean($translation['value'], 256);
                    }

                    $_POST['agreement_url'] = get_url($_POST['agreement_url']);
                    $_POST['thank_you_url'] = get_url($_POST['thank_you_url']);
                    $_POST['border_radius'] = 'straight';


                    break;

                case 'COUPON_BAR':

                    /* Clean some posted variables */
                    $_POST['title'] = $purifier->purify(mb_substr($_POST['title'], 0, 256));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['title'])) foreach ($_POST['translations']['title'] as $translation) {
                        $translations['title'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 256));
                    }

                    $_POST['coupon_code'] = input_clean($_POST['coupon_code'], 64);
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['coupon_code'])) foreach ($_POST['translations']['coupon_code'] as $translation) {
                        $translations['coupon_code'][$translation['key']] = input_clean($translation['value'], 64);
                    }

                    $_POST['url'] = get_url($_POST['url']);
                    $_POST['url_new_tab'] = (int) isset($_POST['url_new_tab']);
                    $_POST['border_radius'] = 'straight';

                    break;

                case 'BUTTON_BAR':

                    /* Clean some posted variables */
                    $_POST['title'] = $purifier->purify(mb_substr($_POST['title'], 0, 256));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['title'])) foreach ($_POST['translations']['title'] as $translation) {
                        $translations['title'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 256));
                    }

                    $_POST['button_text'] = $purifier->purify(mb_substr($_POST['button_text'], 0, 128));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['button_text'])) foreach ($_POST['translations']['button_text'] as $translation) {
                        $translations['button_text'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 128));
                    }
                    $_POST['url'] = get_url($_POST['url']);
                    $_POST['url_new_tab'] = (int) isset($_POST['url_new_tab']);
                    $_POST['border_radius'] = 'straight';

                    break;

                case 'COLLECTOR_MODAL' :
                case 'COLLECTOR_TWO_MODAL' :

                    /* Clean some posted variables */
                    $_POST['title'] = $purifier->purify(mb_substr($_POST['title'], 0, 256));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['title'])) foreach ($_POST['translations']['title'] as $translation) {
                        $translations['title'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 256));
                    }

                    $_POST['description'] = $purifier->purify(mb_substr($_POST['description'], 0, 512));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['description'])) foreach ($_POST['translations']['description'] as $translation) {
                        $translations['description'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 512));
                    }

                    $image = \Altum\Uploads::process_upload($this->notification->settings->image, 'notifications', 'image', 'image_remove', settings()->notifications->image_size_limit);
                    $_POST['image_alt'] = mb_substr(query_clean($_POST['image_alt']), 0, 100);
                    $_POST['input_placeholder'] = mb_substr(query_clean($_POST['input_placeholder']), 0, 128);
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['input_placeholder'])) foreach ($_POST['translations']['input_placeholder'] as $translation) {
                        $translations['input_placeholder'][$translation['key']] = mb_substr(query_clean($translation['value']), 0, 128);
                    }

                    $_POST['button_text'] = $purifier->purify(mb_substr($_POST['button_text'], 0, 128));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['button_text'])) foreach ($_POST['translations']['button_text'] as $translation) {
                        $translations['button_text'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 128));
                    }
                    $_POST['show_agreement'] = (int) isset($_POST['show_agreement']);
                    $_POST['agreement_text'] = input_clean($_POST['agreement_text'], 256);
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['agreement_text'])) foreach ($_POST['translations']['agreement_text'] as $translation) {
                        $translations['agreement_text'][$translation['key']] = input_clean($translation['value'], 256);
                    }

                    $_POST['agreement_url'] = get_url($_POST['agreement_url']);
                    $_POST['thank_you_url'] = get_url($_POST['thank_you_url']);
                    $_POST['border_radius'] = in_array($_POST['border_radius'], [
                        'straight',
                        'rounded',
                        'highly_rounded',
                    ]) ? $_POST['border_radius'] : 'rounded';


                    break;

                case 'BUTTON_MODAL' :

                    /* Clean some posted variables */
                    $_POST['title'] = $purifier->purify(mb_substr($_POST['title'], 0, 256));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['title'])) foreach ($_POST['translations']['title'] as $translation) {
                        $translations['title'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 256));
                    }

                    $_POST['description'] = $purifier->purify(mb_substr($_POST['description'], 0, 512));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['description'])) foreach ($_POST['translations']['description'] as $translation) {
                        $translations['description'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 512));
                    }

                    $image = \Altum\Uploads::process_upload($this->notification->settings->image, 'notifications', 'image', 'image_remove', settings()->notifications->image_size_limit);
                    $_POST['image_alt'] = mb_substr(query_clean($_POST['image_alt']), 0, 100);
                    $_POST['button_text'] = $purifier->purify(mb_substr($_POST['button_text'], 0, 128));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['button_text'])) foreach ($_POST['translations']['button_text'] as $translation) {
                        $translations['button_text'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 128));
                    }
                    $_POST['button_url'] = get_url($_POST['button_url']);
                    $_POST['border_radius'] = in_array($_POST['border_radius'], [
                        'straight',
                        'rounded',
                        'highly_rounded',
                    ]) ? $_POST['border_radius'] : 'rounded';
                    $_POST['url_new_tab'] = (int) isset($_POST['url_new_tab']);

                    break;

                case 'TEXT_FEEDBACK' :

                    /* Clean some posted variables */
                    $_POST['title'] = $purifier->purify(mb_substr($_POST['title'], 0, 256));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['title'])) foreach ($_POST['translations']['title'] as $translation) {
                        $translations['title'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 256));
                    }

                    $_POST['description'] = $purifier->purify(mb_substr($_POST['description'], 0, 512));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['description'])) foreach ($_POST['translations']['description'] as $translation) {
                        $translations['description'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 512));
                    }

                    $_POST['input_placeholder'] = mb_substr(query_clean($_POST['input_placeholder']), 0, 128);
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['input_placeholder'])) foreach ($_POST['translations']['input_placeholder'] as $translation) {
                        $translations['input_placeholder'][$translation['key']] = mb_substr(query_clean($translation['value']), 0, 128);
                    }

                    $_POST['button_text'] = $purifier->purify(mb_substr($_POST['button_text'], 0, 128));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['button_text'])) foreach ($_POST['translations']['button_text'] as $translation) {
                        $translations['button_text'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 128));
                    }
                    $_POST['thank_you_url'] = get_url($_POST['thank_you_url']);
                    $_POST['border_radius'] = in_array($_POST['border_radius'], [
                        'straight',
                        'rounded',
                        'highly_rounded',
                    ]) ? $_POST['border_radius'] : 'rounded';


                    break;

                case 'ENGAGEMENT_LINKS' :

                    /* Clean some posted variables */
                    $_POST['title'] = $purifier->purify(mb_substr($_POST['title'], 0, 256));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['title'])) foreach ($_POST['translations']['title'] as $translation) {
                        $translations['title'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 256));
                    }

                    $_POST['categories'] = $_POST['categories'] ? array_map(function($category) use ($purifier) {
                        $category['title'] = $purifier->purify(mb_substr($category['title'], 0, 256));
                        $category['description'] = $purifier->purify(mb_substr($category['description'], 0, 512));

                        $category['links'] = array_map(function($category_link) use ($purifier) {
                            $category_link['title'] = $purifier->purify(mb_substr($category_link['title'], 0, 256));
                            $category_link['description'] = $purifier->purify(mb_substr($category_link['description'], 0, 512));
                            $category_link['image'] = mb_substr(query_clean($category_link['image']), 0, 2048);
                            $category_link['url'] = get_url($category_link['url']);

                            return $category_link;
                        }, $category['links']);

                        return $category;
                    }, $_POST['categories']) : null;
                    $_POST['categories'] = array_values($_POST['categories'] ?? []);

                    $_POST['border_radius'] = in_array($_POST['border_radius'], [
                        'straight',
                        'rounded',
                        'highly_rounded',
                    ]) ? $_POST['border_radius'] : 'rounded';

                    break;

                case 'WHATSAPP_CHAT' :

                    /* Clean some posted variables */
                    $_POST['title'] = $purifier->purify(mb_substr($_POST['title'], 0, 256));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['title'])) foreach ($_POST['translations']['title'] as $translation) {
                        $translations['title'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 256));
                    }

                    $agent_image = \Altum\Uploads::process_upload($this->notification->settings->agent_image, 'notifications', 'agent_image', 'agent_image_remove', settings()->notifications->image_size_limit);
                    $_POST['agent_image_alt'] = mb_substr(query_clean($_POST['agent_image_alt']), 0, 100);

                    $_POST['agent_name'] = mb_substr(query_clean($_POST['agent_name']), 0, 64);
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['agent_name'])) foreach ($_POST['translations']['agent_name'] as $translation) {
                        $translations['agent_name'][$translation['key']] = mb_substr(query_clean($translation['value']), 0, 64);
                    }

                    $_POST['agent_description'] = mb_substr(query_clean($_POST['agent_description']), 0, 512);
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['agent_description'])) foreach ($_POST['translations']['agent_description'] as $translation) {
                        $translations['agent_description'][$translation['key']] = mb_substr(query_clean($translation['value']), 0, 512);
                    }

                    $_POST['agent_message'] = mb_substr(query_clean($_POST['agent_message']), 0, 1024);
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['agent_message'])) foreach ($_POST['translations']['agent_message'] as $translation) {
                        $translations['agent_message'][$translation['key']] = mb_substr(query_clean($translation['value']), 0, 1024);
                    }

                    $_POST['agent_phone_number'] = (int) mb_substr(query_clean($_POST['agent_phone_number']), 0, 32);
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['agent_phone_number'])) foreach ($_POST['translations']['agent_phone_number'] as $translation) {
                        $translations['agent_phone_number'][$translation['key']] = (int) mb_substr(query_clean($translation['value']), 0, 32);
                    }

                    $_POST['button_text'] = $purifier->purify(mb_substr($_POST['button_text'], 0, 128));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['button_text'])) foreach ($_POST['translations']['button_text'] as $translation) {
                        $translations['button_text'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 128));
                    }

                    $_POST['border_radius'] = in_array($_POST['border_radius'], [
                        'straight',
                        'rounded',
                        'round',
                        'highly_rounded',
                    ]) ? $_POST['border_radius'] : 'rounded';

                    break;

                case 'CUSTOM_HTML':

                    /* Clean some posted variables */
                    $_POST['html'] = mb_substr($_POST['html'], 0, 16000);
                    $_POST['border_radius'] = in_array($_POST['border_radius'], [
                        'straight',
                        'rounded',
                        'highly_rounded',
                        'round',
                    ]) ? $_POST['border_radius'] : 'rounded';
                    break;

                case 'CONTACT_US':

                    /* Clean some posted variables */
                    $_POST['title'] = $purifier->purify(mb_substr($_POST['title'], 0, 256));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['title'])) foreach ($_POST['translations']['title'] as $translation) {
                        $translations['title'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 256));
                    }

                    $_POST['description'] = $purifier->purify(mb_substr($_POST['description'], 0, 512));
                    if(!empty($_POST['translations']) && !empty($_POST['translations']['description'])) foreach ($_POST['translations']['description'] as $translation) {
                        $translations['description'][$translation['key']] = $purifier->purify(mb_substr($translation['value'], 0, 512));
                    }

                    $_POST['contact_email'] = query_clean($_POST['contact_email'], 320);
                    $_POST['contact_phone_number'] = query_clean($_POST['contact_phone_number'], 64);
                    $_POST['contact_whatsapp'] = query_clean($_POST['contact_whatsapp'], 64);
                    $_POST['contact_telegram'] = query_clean($_POST['contact_telegram'], 32);
                    $_POST['contact_facebook_messenger'] = query_clean($_POST['contact_facebook_messenger'], 64);

                    $_POST['border_radius'] = in_array($_POST['border_radius'], [
                        'straight',
                        'rounded',
                        'highly_rounded',
                    ]) ? $_POST['border_radius'] : 'rounded';

                    break;

            }

            /* Go over all the possible color inputs and make sure they comply */
            foreach($_POST as $key => $value) {
                if(string_ends_with('_color', $key) && !verify_hex_color($value)) {

                    /* Replace it with a plain black color */
                    $_POST[$key] = '#000000';

                }
            }

            /* Go over the triggers and clean them */
            foreach($_POST['trigger_type'] as $key => $value) {
                $_POST['trigger_type'][$key] = in_array($value, ['exact', 'not_exact', 'contains', 'not_contains', 'starts_with', 'not_starts_with', 'ends_with', 'not_ends_with', 'page_contains']) ? query_clean($value) : 'exact';
            }

            foreach($_POST['trigger_value'] as $key => $value) {
                $_POST['trigger_value'][$key] = input_clean($value, 512);
            }

            /* Generate the trigger rules var */
            $triggers = [];

            foreach($_POST['trigger_type'] as $key => $value) {
                $triggers[] = [
                    'type' => $value,
                    'value' => $_POST['trigger_value'][$key]
                ];
            }

            /* Notification handlers */
            $_POST['notifications'] = array_map(
                function($notification_handler_id) {
                    return (int) $notification_handler_id;
                },
                array_filter($_POST['notifications'] ?? [], function($notification_handler_id) use($notification_handlers) {
                    return array_key_exists($notification_handler_id, $notification_handlers);
                })
            );

            /* Check for any errors */
            if(!\Altum\Csrf::check()) {
                Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            }

            if(!Alerts::has_field_errors() && !Alerts::has_errors()) {

                /* Default notification settings */
                $new_notification_settings = [
                    'trigger_all_pages' => $_POST['trigger_all_pages'],
                    'triggers' => $triggers,
                    'display_trigger' => $_POST['display_trigger'],
                    'display_trigger_value' => $_POST['display_trigger_value'],
                    'display_frequency' => $_POST['display_frequency'],
                    'display_delay_type_after_close' => $_POST['display_delay_type_after_close'],
                    'display_delay_value_after_close' => $_POST['display_delay_value_after_close'],

                    /* Targeting */
                    'display_continents' => $_POST['display_continents'],
                    'display_countries' => $_POST['display_countries'],
                    'display_languages' => $_POST['display_languages'],
                    'display_operating_systems' => $_POST['display_operating_systems'],
                    'display_browsers' => $_POST['display_browsers'],
                    'display_cities' => $_POST['display_cities'],
                    'display_mobile' => $_POST['display_mobile'],
                    'display_desktop' => $_POST['display_desktop'],

                    'close_button_color' => $_POST['close_button_color'],
                    'dark_mode_close_button_color' => $_POST['dark_mode_close_button_color'],

                    'shadow' => $_POST['shadow'],
                    'hover_animation' => $_POST['hover_animation'],
                    'internal_padding' => $_POST['internal_padding'],
                    'background_blur' => $_POST['background_blur'],
                    'border_radius' => $_POST['border_radius'],
                    'border_width' => $_POST['border_width'],
                    'border_color' => $_POST['border_color'],
                    'dark_mode_border_color' => $_POST['dark_mode_border_color'],
                    'shadow_color' => $_POST['shadow_color'] ?? null,
                    'dark_mode_shadow_color' => $_POST['dark_mode_shadow_color'] ?? null,
                    'on_animation' => $_POST['on_animation'],
                    'off_animation' => $_POST['off_animation'],
                    'animation' => $_POST['animation'],
                    'animation_interval' => $_POST['animation_interval'],
                    'font' => $_POST['font'],

                    'direction' => $_POST['direction'],
                    'display_duration' => $_POST['display_duration'],
                    'display_position' => $_POST['display_position'],
                    'display_close_button' => $_POST['display_close_button'],
                    'display_branding' => $_POST['display_branding'],

                    'schedule' => $_POST['schedule'],
                    'start_date' => $_POST['start_date'],
                    'end_date' => $_POST['end_date'],

                    'custom_css' => $_POST['custom_css'],

                    /* Dark mode */
                    'dark_mode_is_enabled' => $_POST['dark_mode_is_enabled'],

                    /* Translations */
                    'translations' => $translations,
                ];


                /* Prepare the settings json based on the notification type */
                switch($this->notification->type) {

                    case 'INFORMATIONAL' :

                        $new_notification_settings = array_merge(
                            $new_notification_settings,
                            [
                                'title' => $_POST['title'],
                                'description' => $_POST['description'],
                                'image' => $image,
                                'image_alt' => $_POST['image_alt'],
                                'url' => $_POST['url'],
                                'url_new_tab' => $_POST['url_new_tab'],

                                'title_color' => $_POST['title_color'],
                                'description_color' => $_POST['description_color'],
                                'background_color' => $_POST['background_color'],

                                /* Dark mode */
                                'dark_mode_title_color' => $_POST['dark_mode_title_color'],
                                'dark_mode_description_color' => $_POST['dark_mode_description_color'],
                                'dark_mode_background_color' => $_POST['dark_mode_background_color'],
                            ]
                        );

                        break;

                    case 'INFORMATIONAL_MINI' :

                        $new_notification_settings = array_merge(
                            $new_notification_settings,
                            [
                                'title' => $_POST['title'],
                                'image' => $image,
                                'image_alt' => $_POST['image_alt'],
                                'url' => $_POST['url'],
                                'url_new_tab' => $_POST['url_new_tab'],

                                'title_color' => $_POST['title_color'],
                                'background_color' => $_POST['background_color'],

                                /* Dark mode */
                                'dark_mode_title_color' => $_POST['dark_mode_title_color'],
                                'dark_mode_background_color' => $_POST['dark_mode_background_color'],
                            ]
                        );

                        break;

                    case 'COUPON' :

                        $new_notification_settings = array_merge(
                            $new_notification_settings,
                            [
                                'title' => $_POST['title'],
                                'description' => $_POST['description'],
                                'image' => $image,
                                'image_alt' => $_POST['image_alt'],
                                'coupon_code' => $_POST['coupon_code'],
                                'button_url' => $_POST['button_url'],
                                'url_new_tab' => $_POST['url_new_tab'],
                                'button_text' => $_POST['button_text'],

                                'title_color' => $_POST['title_color'],
                                'description_color' => $_POST['description_color'],
                                'background_color' => $_POST['background_color'],
                                'button_background_color' => $_POST['button_background_color'],
                                'button_color' => $_POST['button_color'],

                                /* Dark mode */
                                'dark_mode_title_color' => $_POST['dark_mode_title_color'],
                                'dark_mode_description_color' => $_POST['dark_mode_description_color'],
                                'dark_mode_background_color' => $_POST['dark_mode_background_color'],
                                'dark_mode_button_background_color' => $_POST['dark_mode_button_background_color'],
                                'dark_mode_button_color' => $_POST['dark_mode_button_color'],
                            ]
                        );

                        break;

                    case 'LIVE_COUNTER' :

                        $new_notification_settings = array_merge(
                            $new_notification_settings,
                            [
                                'description' => $_POST['description'],
                                'last_activity' => $_POST['last_activity'],
                                'url' => $_POST['url'],
                                'url_new_tab' => $_POST['url_new_tab'],

                                'display_minimum_activity' => $_POST['display_minimum_activity'],

                                'description_color' => $_POST['description_color'],
                                'background_color' => $_POST['background_color'],
                                'pulse_background_color' => $_POST['pulse_background_color'],

                                /* Dark mode */
                                'dark_mode_description_color' => $_POST['dark_mode_description_color'],
                                'dark_mode_background_color' => $_POST['dark_mode_background_color'],
                                'dark_mode_pulse_background_color' => $_POST['dark_mode_pulse_background_color'],
                            ]
                        );

                        break;

                    case 'EMAIL_COLLECTOR' :

                        $new_notification_settings = array_merge(
                            $new_notification_settings,
                            [
                                'title' => $_POST['title'],
                                'description' => $_POST['description'],
                                'name_placeholder' => $_POST['name_placeholder'],
                                'email_placeholder' => $_POST['email_placeholder'],
                                'button_text' => $_POST['button_text'],
                                'show_agreement' => $_POST['show_agreement'],
                                'agreement_text' => $_POST['agreement_text'],
                                'agreement_url' => $_POST['agreement_url'],
                                'thank_you_url' => $_POST['thank_you_url'],

                                'title_color' => $_POST['title_color'],
                                'description_color' => $_POST['description_color'],
                                'background_color' => $_POST['background_color'],
                                'button_background_color' => $_POST['button_background_color'],
                                'button_color' => $_POST['button_color'],

                                /* Dark mode */
                                'dark_mode_title_color' => $_POST['dark_mode_title_color'],
                                'dark_mode_description_color' => $_POST['dark_mode_description_color'],
                                'dark_mode_background_color' => $_POST['dark_mode_background_color'],
                                'dark_mode_button_background_color' => $_POST['dark_mode_button_background_color'],
                                'dark_mode_button_color' => $_POST['dark_mode_button_color'],
                            ]
                        );

                        break;

                    case 'CONVERSIONS' :

                        /* Go over the data triggers auto and clean them */
                        foreach($_POST['data_trigger_auto_type'] as $key => $value) {
                            $_POST['data_trigger_auto_type'][$key] = in_array($value, ['exact', 'contains', 'starts_with', 'ends_with']) ? query_clean($value) : 'exact';
                        }

                        foreach($_POST['data_trigger_auto_value'] as $key => $value) {
                            $_POST['data_trigger_auto_value'][$key] = query_clean($value);
                        }

                        /* Generate the trigger rules var */
                        $data_triggers_auto = [];

                        foreach($_POST['data_trigger_auto_type'] as $key => $value) {
                            $data_triggers_auto[] = [
                                'type' => $value,
                                'value' => $_POST['data_trigger_auto_value'][$key]
                            ];
                        }

                        $new_notification_settings = array_merge(
                            $new_notification_settings,
                            [
                                'title' => $_POST['title'],
                                'description' => $_POST['description'],
                                'image' => $image,
                                'image_alt' => $_POST['image_alt'],
                                'url' => $_POST['url'],
                                'display_time' => $_POST['display_time'],
                                'url_new_tab' => $_POST['url_new_tab'],
                                'conversions_count' => $_POST['conversions_count'],
                                'in_between_delay' => $_POST['in_between_delay'],
                                'order' => $_POST['order'],

                                'title_color' => $_POST['title_color'],
                                'description_color' => $_POST['description_color'],
                                'date_color' => $_POST['date_color'],
                                'background_color' => $_POST['background_color'],

                                /* Dark mode */
                                'dark_mode_title_color' => $_POST['dark_mode_title_color'],
                                'dark_mode_description_color' => $_POST['dark_mode_description_color'],
                                'dark_mode_date_color' => $_POST['dark_mode_date_color'],
                                'dark_mode_background_color' => $_POST['dark_mode_background_color'],

                                'data_trigger_auto' => $_POST['data_trigger_auto'],
                                'data_triggers_auto' => $data_triggers_auto
                            ]);

                        break;

                    case 'CONVERSIONS_COUNTER' :

                        /* Go over the data triggers auto and clean them */
                        foreach($_POST['data_trigger_auto_type'] as $key => $value) {
                            $_POST['data_trigger_auto_type'][$key] = in_array($value, ['exact', 'contains', 'starts_with', 'ends_with']) ? query_clean($value) : 'exact';
                        }

                        foreach($_POST['data_trigger_auto_value'] as $key => $value) {
                            $_POST['data_trigger_auto_value'][$key] = query_clean($value);
                        }

                        /* Generate the trigger rules var */
                        $data_triggers_auto = [];

                        foreach($_POST['data_trigger_auto_type'] as $key => $value) {
                            $data_triggers_auto[] = [
                                'type' => $value,
                                'value' => $_POST['data_trigger_auto_value'][$key]
                            ];
                        }

                        $new_notification_settings = array_merge(
                            $new_notification_settings,
                            [
                                'title' => $_POST['title'],
                                'last_activity' => $_POST['last_activity'],
                                'url' => $_POST['url'],
                                'url_new_tab' => $_POST['url_new_tab'],

                                'display_minimum_activity' => $_POST['display_minimum_activity'],

                                'title_color' => $_POST['title_color'],
                                'description_color' => $_POST['description_color'],
                                'background_color' => $_POST['background_color'],
                                'number_background_color' => $_POST['number_background_color'],
                                'number_color' => $_POST['number_color'],

                                /* Dark mode */
                                'dark_mode_title_color' => $_POST['dark_mode_title_color'],
                                'dark_mode_description_color' => $_POST['dark_mode_description_color'],
                                'dark_mode_background_color' => $_POST['dark_mode_background_color'],
                                'dark_mode_number_background_color' => $_POST['dark_mode_number_background_color'],
                                'dark_mode_number_color' => $_POST['dark_mode_number_color'],

                                'data_trigger_auto' => $_POST['data_trigger_auto'],
                                'data_triggers_auto' => $data_triggers_auto
                            ]
                        );

                        break;

                    case 'VIDEO' :

                        $new_notification_settings = array_merge(
                            $new_notification_settings,
                            [
                                'title' => $_POST['title'],
                                'video' => $_POST['video'],
                                'video_is_youtube' => $_POST['video_is_youtube'],
                                'video_is_vimeo' => $_POST['video_is_vimeo'],
                                'youtube_video_id' => $_POST['youtube_video_id'],
                                'vimeo_video_id' => $_POST['vimeo_video_id'],
                                'video_autoplay' => $_POST['video_autoplay'],
                                'video_controls' => $_POST['video_controls'],
                                'video_loop' => $_POST['video_loop'],
                                'video_muted' => $_POST['video_muted'],
                                'button_url' => $_POST['button_url'],
                                'button_text' => $_POST['button_text'],
                                'url_new_tab' => $_POST['url_new_tab'],

                                'title_color' => $_POST['title_color'],
                                'background_color' => $_POST['background_color'],
                                'button_background_color' => $_POST['button_background_color'],
                                'button_color' => $_POST['button_color'],

                                /* Dark mode */
                                'dark_mode_title_color' => $_POST['dark_mode_title_color'],
                                'dark_mode_background_color' => $_POST['dark_mode_background_color'],
                                'dark_mode_button_background_color' => $_POST['dark_mode_button_background_color'],
                                'dark_mode_button_color' => $_POST['dark_mode_button_color'],
                            ]
                        );

                        break;

                    case 'AUDIO' :

                        $new_notification_settings = array_merge(
                            $new_notification_settings,
                            [
                                'title' => $_POST['title'],
                                'audio' => $audio,
                                'audio_autoplay' => $_POST['audio_autoplay'],
                                'audio_controls' => $_POST['audio_controls'],
                                'audio_loop' => $_POST['audio_loop'],
                                'audio_muted' => $_POST['audio_muted'],
                                'button_url' => $_POST['button_url'],
                                'button_text' => $_POST['button_text'],
                                'url_new_tab' => $_POST['url_new_tab'],

                                'title_color' => $_POST['title_color'],
                                'background_color' => $_POST['background_color'],
                                'button_background_color' => $_POST['button_background_color'],
                                'button_color' => $_POST['button_color'],

                                /* Dark mode */
                                'dark_mode_title_color' => $_POST['dark_mode_title_color'],
                                'dark_mode_background_color' => $_POST['dark_mode_background_color'],
                                'dark_mode_button_background_color' => $_POST['dark_mode_button_background_color'],
                                'dark_mode_button_color' => $_POST['dark_mode_button_color'],
                            ]
                        );

                        break;

                    case 'SOCIAL_SHARE' :

                        $new_notification_settings = array_merge(
                            $new_notification_settings,
                            [
                                'title' => $_POST['title'],
                                'description' => $_POST['description'],
                                'share_url' => $_POST['share_url'],
                                'share_facebook' => $_POST['share_facebook'],
                                'share_x' => $_POST['share_x'],
                                'share_threads' => $_POST['share_threads'],
                                'share_linkedin' => $_POST['share_linkedin'],
                                'share_reddit' => $_POST['share_reddit'],
                                'share_pinterest' => $_POST['share_pinterest'],
                                'share_tumblr' => $_POST['share_tumblr'],
                                'share_telegram' => $_POST['share_telegram'],
                                'share_whatsapp' => $_POST['share_whatsapp'],

                                'title_color' => $_POST['title_color'],
                                'description_color' => $_POST['description_color'],
                                'background_color' => $_POST['background_color'],

                                /* Dark mode */
                                'dark_mode_title_color' => $_POST['dark_mode_title_color'],
                                'dark_mode_description_color' => $_POST['dark_mode_description_color'],
                                'dark_mode_background_color' => $_POST['dark_mode_background_color'],
                            ]
                        );

                        break;

                    case 'REVIEWS' :

                        $new_notification_settings = array_merge(
                            $new_notification_settings,
                            [
                                'url' => $_POST['url'],
                                'url_new_tab' => $_POST['url_new_tab'],
                                'reviews_count' => $_POST['reviews_count'],
                                'in_between_delay' => $_POST['in_between_delay'],
                                'order' => $_POST['order'],

                                /* Keep the following keys to default */
                                'title' => l('notification.reviews.title_default'),
                                'description' => l('notification.reviews.description_default'),
                                'image' => l('notification.reviews.image_default'),
                                'stars' => 5,

                                'title_color' => $_POST['title_color'],
                                'description_color' => $_POST['description_color'],
                                'background_color' => $_POST['background_color'],

                                /* Dark mode */
                                'dark_mode_title_color' => $_POST['dark_mode_title_color'],
                                'dark_mode_description_color' => $_POST['dark_mode_description_color'],
                                'dark_mode_background_color' => $_POST['dark_mode_background_color'],
                            ]
                        );

                        break;

                    case 'EMOJI_FEEDBACK' :

                        $new_notification_settings = array_merge(
                            $new_notification_settings,
                            [
                                'title' => $_POST['title'],
                                'thank_you_url' => $_POST['thank_you_url'],

                                'show_angry' => $_POST['show_angry'],
                                'show_sad' => $_POST['show_sad'],
                                'show_neutral' => $_POST['show_neutral'],
                                'show_happy' => $_POST['show_happy'],
                                'show_excited' => $_POST['show_excited'],

                                'title_color' => $_POST['title_color'],
                                'background_color' => $_POST['background_color'],

                                /* Dark mode */
                                'dark_mode_title_color' => $_POST['dark_mode_title_color'],
                                'dark_mode_background_color' => $_POST['dark_mode_background_color'],
                            ]
                        );

                        break;

                    case 'COOKIE_NOTIFICATION' :

                        $new_notification_settings = array_merge(
                            $new_notification_settings,
                            [
                                'description' => $_POST['description'],
                                'image' => $image,
                                'image_alt' => $_POST['image_alt'],
                                'url_text' => $_POST['url_text'],
                                'url' => $_POST['url'],
                                'button_text' => $_POST['button_text'],

                                'description_color' => $_POST['description_color'],
                                'background_color' => $_POST['background_color'],
                                'button_background_color' => $_POST['button_background_color'],
                                'button_color' => $_POST['button_color'],

                                /* Dark mode */
                                'dark_mode_description_color' => $_POST['dark_mode_description_color'],
                                'dark_mode_background_color' => $_POST['dark_mode_background_color'],
                                'dark_mode_button_background_color' => $_POST['dark_mode_button_background_color'],
                                'dark_mode_button_color' => $_POST['dark_mode_button_color'],
                            ]
                        );

                        break;

                    case 'SCORE_FEEDBACK' :

                        $new_notification_settings = array_merge(
                            $new_notification_settings,
                            [
                                'title' => $_POST['title'],
                                'description' => $_POST['description'],
                                'thank_you_url' => $_POST['thank_you_url'],

                                'title_color' => $_POST['title_color'],
                                'description_color' => $_POST['description_color'],
                                'background_color' => $_POST['background_color'],
                                'button_background_color' => $_POST['button_background_color'],
                                'button_color' => $_POST['button_color'],

                                /* Dark mode */
                                'dark_mode_title_color' => $_POST['dark_mode_title_color'],
                                'dark_mode_description_color' => $_POST['dark_mode_description_color'],
                                'dark_mode_background_color' => $_POST['dark_mode_background_color'],
                                'dark_mode_button_background_color' => $_POST['dark_mode_button_background_color'],
                                'dark_mode_button_color' => $_POST['dark_mode_button_color'],
                            ]
                        );

                        break;

                    case 'REQUEST_COLLECTOR' :

                        $new_notification_settings = array_merge(
                            $new_notification_settings,
                            [
                                'title' => $_POST['title'],
                                'description' => $_POST['description'],
                                'image' => $image,
                                'image_alt' => $_POST['image_alt'],
                                'content_title' => $_POST['content_title'],
                                'content_description' => $_POST['content_description'],
                                'input_placeholder' => $_POST['input_placeholder'],
                                'button_text' => $_POST['button_text'],
                                'show_agreement' => $_POST['show_agreement'],
                                'agreement_text' => $_POST['agreement_text'],
                                'agreement_url' => $_POST['agreement_url'],
                                'thank_you_url' => $_POST['thank_you_url'],

                                'title_color' => $_POST['title_color'],
                                'description_color' => $_POST['description_color'],
                                'content_title_color' => $_POST['content_title_color'],
                                'content_description_color' => $_POST['content_description_color'],
                                'background_color' => $_POST['background_color'],
                                'button_background_color' => $_POST['button_background_color'],
                                'button_color' => $_POST['button_color'],

                                /* Dark mode */
                                'dark_mode_title_color' => $_POST['dark_mode_title_color'],
                                'dark_mode_description_color' => $_POST['dark_mode_description_color'],
                                'dark_mode_content_title_color' => $_POST['dark_mode_content_title_color'],
                                'dark_mode_content_description_color' => $_POST['dark_mode_content_description_color'],
                                'dark_mode_background_color' => $_POST['dark_mode_background_color'],
                                'dark_mode_button_background_color' => $_POST['dark_mode_button_background_color'],
                                'dark_mode_button_color' => $_POST['dark_mode_button_color'],
                            ]
                        );

                        break;

                    case 'COUNTDOWN_COLLECTOR' :

                        $new_notification_settings = array_merge(
                            $new_notification_settings,
                            [
                                'title' => $_POST['title'],
                                'description' => $_POST['description'],
                                'content_title' => $_POST['content_title'],
                                'input_placeholder' => $_POST['input_placeholder'],
                                'button_text' => $_POST['button_text'],
                                'countdown_end_date' => $_POST['countdown_end_date'],
                                'show_agreement' => $_POST['show_agreement'],
                                'agreement_text' => $_POST['agreement_text'],
                                'agreement_url' => $_POST['agreement_url'],
                                'thank_you_url' => $_POST['thank_you_url'],

                                'title_color' => $_POST['title_color'],
                                'description_color' => $_POST['description_color'],
                                'content_title_color' => $_POST['content_title_color'],
                                'time_color' => $_POST['time_color'],
                                'time_background_color' => $_POST['time_background_color'],
                                'background_color' => $_POST['background_color'],
                                'button_background_color' => $_POST['button_background_color'],
                                'button_color' => $_POST['button_color'],

                                /* Dark mode */
                                'dark_mode_title_color' => $_POST['dark_mode_title_color'],
                                'dark_mode_description_color' => $_POST['dark_mode_description_color'],
                                'dark_mode_content_title_color' => $_POST['dark_mode_content_title_color'],
                                'dark_mode_time_color' => $_POST['dark_mode_time_color'],
                                'dark_mode_time_background_color' => $_POST['dark_mode_time_background_color'],
                                'dark_mode_background_color' => $_POST['dark_mode_background_color'],
                                'dark_mode_button_background_color' => $_POST['dark_mode_button_background_color'],
                                'dark_mode_button_color' => $_POST['dark_mode_button_color'],
                            ]
                        );

                        break;

                    case 'CUSTOM_HTML' :

                        $new_notification_settings = array_merge(
                            $new_notification_settings,
                            [
                                'html' => $_POST['html'],
                                'background_color' => $_POST['background_color'],

                                /* Dark mode */
                                'dark_mode_background_color' => $_POST['dark_mode_background_color'],
                            ]
                        );

                        break;

                    case 'INFORMATIONAL_BAR' :

                        $new_notification_settings = array_merge(
                            $new_notification_settings,
                            [
                                'title' => $_POST['title'],
                                'description' => $_POST['description'],
                                'image' => $image,
                                'image_alt' => $_POST['image_alt'],
                                'url' => $_POST['url'],
                                'url_new_tab' => $_POST['url_new_tab'],

                                'title_color' => $_POST['title_color'],
                                'description_color' => $_POST['description_color'],
                                'background_color' => $_POST['background_color'],

                                /* Dark mode */
                                'dark_mode_title_color' => $_POST['dark_mode_title_color'],
                                'dark_mode_description_color' => $_POST['dark_mode_description_color'],
                                'dark_mode_background_color' => $_POST['dark_mode_background_color'],
                            ]
                        );

                        break;

                    case 'INFORMATIONAL_BAR_MINI' :

                        $new_notification_settings = array_merge(
                            $new_notification_settings,
                            [
                                'title' => $_POST['title'],
                                'image' => $image,
                                'image_alt' => $_POST['image_alt'],
                                'url' => $_POST['url'],
                                'url_new_tab' => $_POST['url_new_tab'],

                                'title_color' => $_POST['title_color'],
                                'background_color' => $_POST['background_color'],

                                /* Dark mode */
                                'dark_mode_title_color' => $_POST['dark_mode_title_color'],
                                'dark_mode_background_color' => $_POST['dark_mode_background_color'],
                            ]
                        );

                        break;

                    case 'IMAGE' :

                        $new_notification_settings = array_merge(
                            $new_notification_settings,
                            [
                                'title' => $_POST['title'],
                                'image' => $image,
                                'image_alt' => $_POST['image_alt'],
                                'button_url' => $_POST['button_url'],
                                'button_text' => $_POST['button_text'],

                                'title_color' => $_POST['title_color'],
                                'background_color' => $_POST['background_color'],
                                'button_background_color' => $_POST['button_background_color'],
                                'button_color' => $_POST['button_color'],

                                /* Dark mode */
                                'dark_mode_title_color' => $_POST['dark_mode_title_color'],
                                'dark_mode_background_color' => $_POST['dark_mode_background_color'],
                                'dark_mode_button_background_color' => $_POST['dark_mode_button_background_color'],
                                'dark_mode_button_color' => $_POST['dark_mode_button_color'],
                            ]
                        );

                        break;

                    case 'COLLECTOR_BAR' :

                        $new_notification_settings = array_merge(
                            $new_notification_settings,
                            [
                                'title' => $_POST['title'],
                                'input_placeholder' => $_POST['input_placeholder'],
                                'button_text' => $_POST['button_text'],
                                'show_agreement' => $_POST['show_agreement'],
                                'agreement_text' => $_POST['agreement_text'],
                                'agreement_url' => $_POST['agreement_url'],
                                'thank_you_url' => $_POST['thank_you_url'],

                                'title_color' => $_POST['title_color'],
                                'background_color' => $_POST['background_color'],
                                'button_background_color' => $_POST['button_background_color'],
                                'button_color' => $_POST['button_color'],

                                /* Dark mode */
                                'dark_mode_title_color' => $_POST['dark_mode_title_color'],
                                'dark_mode_background_color' => $_POST['dark_mode_background_color'],
                                'dark_mode_button_background_color' => $_POST['dark_mode_button_background_color'],
                                'dark_mode_button_color' => $_POST['dark_mode_button_color'],
                            ]
                        );

                        break;

                    case 'COUPON_BAR' :

                        $new_notification_settings = array_merge(
                            $new_notification_settings,
                            [
                                'title' => $_POST['title'],
                                'coupon_code' => $_POST['coupon_code'],
                                'url' => $_POST['url'],
                                'url_new_tab' => $_POST['url_new_tab'],

                                'title_color' => $_POST['title_color'],
                                'background_color' => $_POST['background_color'],
                                'coupon_code_color' => $_POST['coupon_code_color'],
                                'coupon_code_background_color' => $_POST['coupon_code_background_color'],
                                'coupon_code_border_color' => $_POST['coupon_code_border_color'],

                                /* Dark mode */
                                'dark_mode_title_color' => $_POST['dark_mode_title_color'],
                                'dark_mode_background_color' => $_POST['dark_mode_background_color'],
                                'dark_mode_coupon_code_color' => $_POST['dark_mode_coupon_code_color'],
                                'dark_mode_coupon_code_background_color' => $_POST['dark_mode_coupon_code_background_color'],
                                'dark_mode_coupon_code_border_color' => $_POST['dark_mode_coupon_code_border_color'],
                            ]
                        );

                        break;

                    case 'BUTTON_BAR' :

                        $new_notification_settings = array_merge(
                            $new_notification_settings,
                            [
                                'title' => $_POST['title'],
                                'button_text' => $_POST['button_text'],
                                'url' => $_POST['url'],
                                'url_new_tab' => $_POST['url_new_tab'],

                                'title_color' => $_POST['title_color'],
                                'background_color' => $_POST['background_color'],
                                'button_color' => $_POST['button_color'],
                                'button_background_color' => $_POST['button_background_color'],

                                /* Dark mode */
                                'dark_mode_title_color' => $_POST['dark_mode_title_color'],
                                'dark_mode_background_color' => $_POST['dark_mode_background_color'],
                                'dark_mode_button_color' => $_POST['dark_mode_button_color'],
                                'dark_mode_button_background_color' => $_POST['dark_mode_button_background_color'],
                            ]
                        );

                        break;

                    case 'COLLECTOR_MODAL' :
                    case 'COLLECTOR_TWO_MODAL' :

                        $new_notification_settings = array_merge(
                            $new_notification_settings,
                            [
                                'title' => $_POST['title'],
                                'description' => $_POST['description'],
                                'image' => $image,
                                'image_alt' => $_POST['image_alt'],
                                'input_placeholder' => $_POST['input_placeholder'],
                                'button_text' => $_POST['button_text'],
                                'show_agreement' => $_POST['show_agreement'],
                                'agreement_text' => $_POST['agreement_text'],
                                'agreement_url' => $_POST['agreement_url'],
                                'thank_you_url' => $_POST['thank_you_url'],

                                'title_color' => $_POST['title_color'],
                                'description_color' => $_POST['description_color'],
                                'background_color' => $_POST['background_color'],
                                'button_background_color' => $_POST['button_background_color'],
                                'button_color' => $_POST['button_color'],

                                /* Dark mode */
                                'dark_mode_title_color' => $_POST['dark_mode_title_color'],
                                'dark_mode_description_color' => $_POST['dark_mode_description_color'],
                                'dark_mode_background_color' => $_POST['dark_mode_background_color'],
                                'dark_mode_button_background_color' => $_POST['dark_mode_button_background_color'],
                                'dark_mode_button_color' => $_POST['dark_mode_button_color'],
                            ]
                        );

                        break;

                    case 'BUTTON_MODAL' :

                        $new_notification_settings = array_merge(
                            $new_notification_settings,
                            [
                                'title' => $_POST['title'],
                                'description' => $_POST['description'],
                                'image' => $image,
                                'image_alt' => $_POST['image_alt'],
                                'button_text' => $_POST['button_text'],
                                'button_url' => $_POST['button_url'],
                                'url_new_tab' => $_POST['url_new_tab'],

                                'title_color' => $_POST['title_color'],
                                'description_color' => $_POST['description_color'],
                                'background_color' => $_POST['background_color'],
                                'button_background_color' => $_POST['button_background_color'],
                                'button_color' => $_POST['button_color'],

                                /* Dark mode */
                                'dark_mode_title_color' => $_POST['dark_mode_title_color'],
                                'dark_mode_description_color' => $_POST['dark_mode_description_color'],
                                'dark_mode_background_color' => $_POST['dark_mode_background_color'],
                                'dark_mode_button_background_color' => $_POST['dark_mode_button_background_color'],
                                'dark_mode_button_color' => $_POST['dark_mode_button_color'],
                            ]
                        );

                        break;

                    case 'TEXT_FEEDBACK' :

                        $new_notification_settings = array_merge(
                            $new_notification_settings,
                            [
                                'title' => $_POST['title'],
                                'description' => $_POST['description'],
                                'input_placeholder' => $_POST['input_placeholder'],
                                'button_text' => $_POST['button_text'],
                                'thank_you_url' => $_POST['thank_you_url'],

                                'title_color' => $_POST['title_color'],
                                'description_color' => $_POST['description_color'],
                                'background_color' => $_POST['background_color'],
                                'button_background_color' => $_POST['button_background_color'],
                                'button_color' => $_POST['button_color'],

                                /* Dark mode */
                                'dark_mode_title_color' => $_POST['dark_mode_title_color'],
                                'dark_mode_description_color' => $_POST['dark_mode_description_color'],
                                'dark_mode_background_color' => $_POST['dark_mode_background_color'],
                                'dark_mode_button_background_color' => $_POST['dark_mode_button_background_color'],
                                'dark_mode_button_color' => $_POST['dark_mode_button_color'],
                            ]
                        );

                        break;

                    case 'ENGAGEMENT_LINKS' :

                        $new_notification_settings = array_merge(
                            $new_notification_settings,
                            [
                                'title' => $_POST['title'],
                                'categories' => $_POST['categories'],

                                'title_color' => $_POST['title_color'],
                                'categories_title_color' => $_POST['categories_title_color'],
                                'categories_description_color' => $_POST['categories_description_color'],
                                'categories_links_title_color' => $_POST['categories_links_title_color'],
                                'categories_links_description_color' => $_POST['categories_links_description_color'],
                                'categories_links_background_color' => $_POST['categories_links_background_color'],
                                'categories_links_border_color' => $_POST['categories_links_border_color'],
                                'background_color' => $_POST['background_color'],

                                /* Dark mode */
                                'dark_mode_title_color' => $_POST['dark_mode_title_color'],
                                'dark_mode_categories_title_color' => $_POST['dark_mode_categories_title_color'],
                                'dark_mode_categories_description_color' => $_POST['dark_mode_categories_description_color'],
                                'dark_mode_categories_links_title_color' => $_POST['dark_mode_categories_links_title_color'],
                                'dark_mode_categories_links_description_color' => $_POST['dark_mode_categories_links_description_color'],
                                'dark_mode_categories_links_background_color' => $_POST['dark_mode_categories_links_background_color'],
                                'dark_mode_categories_links_border_color' => $_POST['dark_mode_categories_links_border_color'],
                                'dark_mode_background_color' => $_POST['dark_mode_background_color'],
                            ]
                        );

                        break;

                    case 'WHATSAPP_CHAT' :

                        $new_notification_settings = array_merge(
                            $new_notification_settings,
                            [
                                'title' => $_POST['title'],
                                'agent_image' => $agent_image,
                                'agent_image_alt' => $_POST['agent_image_alt'],
                                'agent_name' => $_POST['agent_name'],
                                'agent_description' => $_POST['agent_description'],
                                'agent_message' => $_POST['agent_message'],
                                'agent_phone_number' => $_POST['agent_phone_number'],
                                'button_text' => $_POST['button_text'],

                                'header_agent_name_color' => $_POST['header_agent_name_color'],
                                'header_agent_description_color' => $_POST['header_agent_description_color'],
                                'header_background_color' => $_POST['header_background_color'],
                                'content_background_color' => $_POST['content_background_color'],
                                'content_agent_name_color' => $_POST['content_agent_name_color'],
                                'content_agent_message_color' => $_POST['content_agent_message_color'],
                                'content_agent_message_background_color' => $_POST['content_agent_message_background_color'],
                                'footer_background_color' => $_POST['footer_background_color'],
                                'footer_button_background_color' => $_POST['footer_button_background_color'],
                                'footer_button_color' => $_POST['footer_button_color'],
                                'title_color' => $_POST['title_color'],
                                'background_color' => $_POST['background_color'],

                                /* Dark mode */
                                'dark_mode_header_agent_name_color' => $_POST['dark_mode_header_agent_name_color'],
                                'dark_mode_header_agent_description_color' => $_POST['dark_mode_header_agent_description_color'],
                                'dark_mode_header_background_color' => $_POST['dark_mode_header_background_color'],
                                'dark_mode_content_background_color' => $_POST['dark_mode_content_background_color'],
                                'dark_mode_content_agent_name_color' => $_POST['dark_mode_content_agent_name_color'],
                                'dark_mode_content_agent_message_color' => $_POST['dark_mode_content_agent_message_color'],
                                'dark_mode_content_agent_message_background_color' => $_POST['dark_mode_content_agent_message_background_color'],
                                'dark_mode_footer_background_color' => $_POST['dark_mode_footer_background_color'],
                                'dark_mode_footer_button_background_color' => $_POST['dark_mode_footer_button_background_color'],
                                'dark_mode_footer_button_color' => $_POST['dark_mode_footer_button_color'],
                                'dark_mode_title_color' => $_POST['dark_mode_title_color'],
                                'dark_mode_background_color' => $_POST['dark_mode_background_color'],
                            ]
                        );

                        break;

                    case 'CONTACT_US' :

                        $new_notification_settings = array_merge(
                            $new_notification_settings,
                            [
                                'title' => $_POST['title'],
                                'description' => $_POST['description'],
                                'contact_email' => $_POST['contact_email'],
                                'contact_phone_number' => $_POST['contact_phone_number'],
                                'contact_whatsapp' => $_POST['contact_whatsapp'],
                                'contact_telegram' => $_POST['contact_telegram'],
                                'contact_facebook_messenger' => $_POST['contact_facebook_messenger'],

                                'title_color' => $_POST['title_color'],
                                'description_color' => $_POST['description_color'],
                                'background_color' => $_POST['background_color'],

                                /* Dark mode */
                                'dark_mode_title_color' => $_POST['dark_mode_title_color'],
                                'dark_mode_description_color' => $_POST['dark_mode_description_color'],
                                'dark_mode_background_color' => $_POST['dark_mode_background_color'],
                            ]
                        );

                        break;
                }

                /* Prepare as json for the database update */
                $new_notification_settings = json_encode($new_notification_settings);

                /* Notifications */
                $notifications = json_encode($_POST['notifications']);

                /* Database query */
                db()->where('notification_id', $this->notification_id)->where('user_id', $this->user->user_id)->update('notifications', [
                    'name' => $_POST['name'],
                    'settings' => $new_notification_settings,
                    'notifications' => $notifications,
                    'last_datetime' => get_date(),
                ]);

                /* Set a nice success message */
                Alerts::add_success(sprintf(l('global.success_message.update1'), '<strong>' . $_POST['name'] . '</strong>'));

                redirect('notification/' . $this->notification_id);
            }
        }

    }

    public function duplicate() {

        \Altum\Authentication::guard();

        $notification_id = (int) query_clean($_POST['notification_id']);

        /* Make sure the notification is created by the logged in user */
        if(!$notification = db()->where('notification_id', $notification_id)->where('user_id', $this->user->user_id)->getOne('notifications')) {
            redirect('dashboard');
        }

        /* Team checks */
        if(\Altum\Teams::is_delegated() && !\Altum\Teams::has_access('create.notifications')) {
            Alerts::add_error(l('global.info_message.team_no_access'));
            redirect('campaign/' . $notification->campaign_id);
        }

        if(empty($_POST)) {
            redirect('campaign/' . $notification->campaign_id);
        }

        /* Check for the plan limit */
        $notifications_total = database()->query("SELECT COUNT(*) AS `total` FROM `notifications` WHERE `user_id` = {$this->user->user_id} AND `campaign_id` = {$notification->campaign_id}")->fetch_object()->total;
        if($this->user->plan_settings->notifications_limit != -1 && $notifications_total >= $this->user->plan_settings->notifications_limit) {
            Alerts::add_error(l('global.info_message.plan_feature_limit') . (settings()->payment->is_enabled ? ' <a href="' . url('plan') . '" class="font-weight-bold text-reset">' . l('global.info_message.plan_upgrade') . '.</a>' : null));
            redirect('campaign/' . $notification->campaign_id);
        }

        //ALTUMCODE:DEMO if(DEMO) if($this->user->user_id == 1) Alerts::add_error('Please create an account on the demo to test out this function.');

        if(!\Altum\Csrf::check()) {
            Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            redirect('campaign/' . $notification->campaign_id);
        }


        if(!Alerts::has_field_errors() && !Alerts::has_errors()) {

            /* Determine the default settings */
            $notification_key = md5($this->user->user_id . $notification->notification_id . $notification->campaign_id . time());

            /* Insert to database */
            $notification_id = db()->insert('notifications', [
                'user_id' => $this->user->user_id,
                'campaign_id' => $notification->campaign_id,
                'name' => string_truncate($notification->name . ' - ' . l('global.duplicated'), 64, null),
                'type' => $notification->type,
                'settings' => $notification->settings,
                'notification_key' => $notification_key,
                'is_enabled' => 0,
                'datetime' => get_date(),
            ]);

            /* Set a nice success message */
            Alerts::add_success(sprintf(l('global.success_message.create1'), '<strong>' . input_clean($name) . '</strong>'));

            /* Redirect */
            redirect('notification/' . $notification_id);

        }

        die();
    }

    public function bulk() {

        \Altum\Authentication::guard();

        $campaign_id = (int) $_POST['campaign_id'];

        /* Check for any errors */
        if(empty($_POST)) {
            redirect('campaign/' . $campaign_id);
        }

        if(empty($_POST['selected'])) {
            redirect('campaign/' . $campaign_id);
        }

        if(!isset($_POST['type'])) {
            redirect('campaign/' . $campaign_id);
        }

        //ALTUMCODE:DEMO if(DEMO) Alerts::add_error('This command is blocked on the demo.');

        if(!\Altum\Csrf::check()) {
            Alerts::add_error(l('global.error_message.invalid_csrf_token'));
        }

        if(!Alerts::has_field_errors() && !Alerts::has_errors()) {

            set_time_limit(0);

            session_write_close();

            switch($_POST['type']) {
                case 'delete':

                    /* Team checks */
                    if(\Altum\Teams::is_delegated() && !\Altum\Teams::has_access('delete.notifications')) {
                        Alerts::add_error(l('global.info_message.team_no_access'));
                        redirect('notifications');
                    }

                    foreach($_POST['selected'] as $notification_id) {
                        if($notification = db()->where('notification_id', $notification_id)->where('user_id', $this->user->user_id)->getOne('notifications', ['notification_id'])) {
                            (new \Altum\Models\Notification())->delete($notification_id);
                        }
                    }

                    break;
            }

            /* Clear the cache */
            cache()->deleteItem('notifications_total?user_id=' . $this->user->user_id);

            session_start();

            /* Set a nice success message */
            Alerts::add_success(l('bulk_delete_modal.success_message'));

        }

        redirect('campaign/' . $campaign_id);
    }

    public function reset() {
        \Altum\Authentication::guard();

        /* Team checks */
        if(\Altum\Teams::is_delegated() && !\Altum\Teams::has_access('update.dashboard')) {
            Alerts::add_error(l('global.info_message.team_no_access'));
            redirect('dashboard');
        }

        if(empty($_POST)) {
            redirect('dashboard');
        }

        $notification_id = (int) query_clean($_POST['notification_id']);

        //ALTUMCODE:DEMO if(DEMO) if($this->user->user_id == 1) Alerts::add_error('Please create an account on the demo to test out this function.');

        if(!\Altum\Csrf::check()) {
            Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            redirect('dashboard');
        }

        /* Make sure the link id is created by the logged in user */
        if(!$notification = db()->where('notification_id', $notification_id)->where('user_id', $this->user->user_id)->getOne('notifications', ['notification_id', 'campaign_id'])) {
            redirect('dashboard');
        }

        if(!Alerts::has_field_errors() && !Alerts::has_errors()) {

            /* Reset data */
            db()->where('notification_id', $notification_id)->update('notifications', [
                'impressions' => 0,
                'hovers' => 0,
                'form_submissions' => 0,
                'clicks' => 0,
            ]);

            /* Remove data */
            db()->where('notification_id', $notification_id)->delete('track_notifications');

            /* Set a nice success message */
            Alerts::add_success(l('global.success_message.update2'));

            redirect('campaign/' . $notification->campaign_id);

        }

        redirect('dashboard');
    }

    public function delete() {

        \Altum\Authentication::guard();

        /* Team checks */
        if(\Altum\Teams::is_delegated() && !\Altum\Teams::has_access('delete.notifications')) {
            Alerts::add_error(l('global.info_message.team_no_access'));
            redirect('dashboard');
        }

        if(empty($_POST)) {
            redirect('dashboard');
        }

        $notification_id = (int) query_clean($_POST['notification_id']);

        //ALTUMCODE:DEMO if(DEMO) if($this->user->user_id == 1) Alerts::add_error('Please create an account on the demo to test out this function.');

        if(!\Altum\Csrf::check()) {
            Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            redirect('dashboard');
        }

        /* Make sure the notification is created by the logged in user */
        if(!$notification = db()->where('notification_id', $notification_id)->where('user_id', $this->user->user_id)->getOne('notifications', ['notification_id', 'campaign_id', 'name'])) {
            redirect('dashboard');
        }

        if(!Alerts::has_field_errors() && !Alerts::has_errors()) {

            (new \Altum\Models\Notification())->delete($notification_id);

            /* Set a nice success message */
            Alerts::add_success(sprintf(l('global.success_message.delete1'), '<strong>' . $notification->name . '</strong>'));

            redirect('campaign/' . $notification->campaign_id);
        }

        redirect('dashboard');
    }
}
