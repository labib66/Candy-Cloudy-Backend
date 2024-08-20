<?php namespace Common\Settings;

use Common\Core\AppUrl;
use Common\Core\BaseController;
use Common\Settings\Events\SettingsSaved;
use Common\Settings\Mail\ConnectGmailAccountController;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;


class SettingsController extends BaseController
{
    public function __construct(
        protected Request $request,
        protected Settings $settings,
        protected DotEnvEditor $dotEnv,
    ) { 
    }


    public function indexApi()
    {
        // $this->authorize('index', Setting::class);
        $envSettings = $this->dotEnv->load('.env');
        $envSettings['newAppUrl'] = app(AppUrl::class)->newAppUrl;
        $envSettings[
            'connectedGmailAccount'
        ] = ConnectGmailAccountController::getConnectedEmail();
        // inputs on frontend can't be bound to null
        foreach ($envSettings as $key => $value) {
            if ($value === null) {
                $envSettings[$key] = '';
            }
        }
        $client = $this->settings->getUnflattened(true);
        $data =  [
            'General' =>[
                'app_url'=> $envSettings['app_url'],
                'homepage->type'=> $client['homepage']['type'],
                'themes->default_id'=> $client['themes']['default_id'],
                'themes->user_change'=> $client['themes']['user_change'],
            ],
            'Drive' =>[
                'drive->default_view'=> $client['drive']['default_view'],
                'drive->send_share_notification'=> $client['drive']['send_share_notification'],
                'share->suggest_emails'=> $client['share']['suggest_emails'],
            ],
            'Subscriptions' =>[
                'billing->enable'=> $client['billing']['enable'],
                'billing->paypal->enable'=> $client['billing']['paypal']['enable'],
                'billing->paypal_test_mode'=> $client['billing']['paypal_test_mode'],
                'billing->stripe->enable'=> $client['billing']['stripe']['enable'],
                // 'billing->stripe_test_mode'=> $client['billing']['stripe_test_mode'],
                'billing->accepted_cards'=>$client['billing']['accepted_cards'],
                'billing->invoice->address'=> $client['billing']['invoice']['address'],
                'billing->invoice->notes'=> $client['billing']['invoice']['notes'],
            ],
        ];
        // if ($client['billing']['paypal']['enable'] == true) {
        //     $data['Subscriptions']['paypal']['Client_ID'] = $client['billing']['paypal']['Webhook _ID'];
        //     $data['Subscriptions']['paypal']['PayPal_Secret'] = $client['billing']['paypal']['Webhook _ID'];
        //     $data['Subscriptions']['paypal']['Webhook _ID'] = $client['billing']['paypal']['Webhook _ID'];
        // }

        // if ($client['billing']['stripe']['enable'] == true) {
        //     $data['Subscriptions']['stripe']['publishable_key'] = $client['billing']['stripe']['Webhook _ID'];
        //     $data['Subscriptions']['stripe']['stripe_Secret'] = $client['billing']['stripe']['Webhook _ID'];
        //     $data['Subscriptions']['stripe']['Webhook _ID'] = $client['billing']['stripe']['Webhook _ID'];      
        // }

        $data['Localization'] =  [
                'dates->default_timezone'=> $client['dates']['default_timezone'],
                'locale->default'=> $client['locale']['default'],
                'dates->format'=> $client['dates']['format'],
                'i18n->enable'=> $client['i18n']['enable'],
        ];
        $data['Authentication'] =  [
                'mail_from_address'=> $envSettings['mail_from_address'],
                'mail->contact_page_address'=> $client['mail']['contact_page_address'],
                'mail_from_name'=> $envSettings['mail_from_name'],
                'mail_driver'=> $envSettings['mail_driver'],
        ];

        if ($client['social']['google']['enable'] == true) {
            $data['Authentication']['google']['GOOGLE_ID'] = $envSettings['google_id'] ?? '';
            $data['Authentication']['google']['GOOGLE_SECRET'] = $envSettings['google_secret'] ?? '';
        }
        if ($client['social']['twitter']['enable'] == true) {
            $data['Authentication']['twitter']['GOOGLE_ID'] = $envSettings['twitter_id'] ?? '';
            $data['Authentication']['twitter']['GOOGLE_SECRET'] = $envSettings['twitter_secret'] ?? '';
        }
        if ($client['social']['facebook']['enable'] == true) {
            $data['Authentication']['facebook']['GOOGLE_ID'] = $envSettings['facebook_id'] ?? '';
            $data['Authentication']['facebook']['GOOGLE_SECRET'] = $envSettings['facebook_secret'] ?? '';
        }
        
        $data['uploads'] =  [
                'uploads->public_driver'=> $client['uploads']['public_driver'],
                'uploads->uploads_driver'=> $client['uploads']['uploads_driver'],
                'static_file_delivery'=> $envSettings['static_file_delivery'],
                'uploads->chunk_size'=> $client['uploads']['chunk_size'],
                'uploads->max_size'=> $client['uploads']['max_size'],
                'uploads->available_space'=> $client['uploads']['available_space'],
                'uploads->allowed_extensions'=> $client['uploads']['allowed_extensions'] ?? '',
                'uploads->blocked_extensions'=> $client['uploads']['blocked_extensions']  ?? '',
        ];

        if($client['uploads']['public_driver'] == "s3") {
            $data['uploads']['s3']['storage_s3_key'] = $envSettings['storage_s3_key'] ?? '';
            $data['uploads']['s3']['storage_s3_secret'] = $envSettings['storage_s3_secret'] ?? '';
            $data['uploads']['s3']['storage_s3_region'] = $envSettings['storage_s3_region'] ?? '';
            $data['uploads']['s3']['storage_s3_bucket'] = $envSettings['storage_s3_bucket'] ?? '';
            $data['uploads']['s3']['storage_s3_endpoint'] = $envSettings['storage_s3_endpoint'] ?? '';
        }
        $data['Outgoing_email_settings'] =  [
                'require_email_confirmation'=> $client['require_email_confirmation']  ?? '',
                'registration->disable'=> $client['registration']['disable']  ?? '',
                'single_device_login'=> $client['single_device_login']  ?? '',
                'social->compact_buttons'=> $client['social']['compact_buttons']  ?? '',
                'auth->domain_blacklist'=> $client['auth']['domain_blacklist']  ?? '',
        ];

        // $data['cache'] =  [
        //         'cache_driver'=> $envSettings['cache_driver'],
        // ];
        $data['analytics'] =  [
            'analytics->gchart_api_key'=> $client['analytics']['gchart_api_key'] ?? '' ,
        ];

        return $data;
    }
    public function persistApi()
    {
        // $this->authorize('update', Setting::class);
    
        $settings = $this->request->all();

        if (empty($settings)) {
            return response()->json(['error' => 'No settings provided'], 400);
        }
    
        $serverSettings = [];
        $clientSettings = [];
    
        // تفكيك الإعدادات الواردة
        foreach ($settings as $key => $value) {
        if (strpos($key, '->') !== false) {
            // تحديث القيم المتداخلة باستخدام data_set
            data_set($clientSettings, str_replace('->', '.', $key), $value);
        } else {
            if ($this->isServerSetting($key)) {
                $serverSettings[$key] = $value;
            } else {
                $clientSettings[$key] = $value;
            }
        }
        }
        // Need to handle files before validating
        $this->handleFiles();
    
        // Validate settings
        if ($errResponse = $this->validateSettings($serverSettings, $clientSettings)) {
            return $errResponse;
        }
    
        // Write server settings to .env
        if ($serverSettings) {
            $this->dotEnv->write($serverSettings);
        }
    
        // Save client settings to the database
        if ($clientSettings) {
            $this->settings->save($clientSettings);
        }
    
        Cache::flush();
    
        event(new SettingsSaved($clientSettings, $serverSettings));
    
        return response()->json(['success' => true]);
    }    
    private function isServerSetting($key)
    {
        // Define which keys are server settings
        $serverSettingKeys = [
            'app_url',
            'mail_from_address',
            'mail_from_name',
            'mail_driver',
            'static_file_delivery',
            'cache_driver',
            'storage_s3_key',
            'storage_s3_secret',
            'storage_s3_region',
            'storage_s3_bucket',
            'storage_s3_endpoint',
            'google_id',
            'google_secret',
            'twitter_id',
            'twitter_secret',
            'facebook_id',
            'facebook_secret',


        ];
        return in_array($key, $serverSettingKeys);
    }



    public function index()
    {
        // $this->authorize('index', Setting::class);
        $envSettings = $this->dotEnv->load('.env');
        $envSettings['newAppUrl'] = app(AppUrl::class)->newAppUrl;
        $envSettings[
            'connectedGmailAccount'
        ] = ConnectGmailAccountController::getConnectedEmail();
        // inputs on frontend can't be bound to null
        foreach ($envSettings as $key => $value) {
            if ($value === null) {
                $envSettings[$key] = '';
            }
        }

        return [
            'server' => $envSettings,
            'client' => $this->settings->getUnflattened(true),
        ];
    }

    public function persist()
    {
        // $this->authorize('update', Setting::class);

        $clientSettings = $this->cleanValues($this->request->get('client'));
        $serverSettings = $this->cleanValues($this->request->get('server'));

        // need to handle files before validating
        $this->handleFiles();

        if (
            $errResponse = $this->validateSettings(
                $serverSettings,
                $clientSettings,
            )
        ) {
            return $errResponse;
        }

        if ($serverSettings) {
            $this->dotEnv->write($serverSettings);
        }

        if ($clientSettings) {
            $this->settings->save($clientSettings);
        }

        Cache::flush();

        event(new SettingsSaved($clientSettings, $serverSettings));

        return $this->success();
    }

    private function cleanValues(string|null $config): array
    {
        if (!$config) {
            return [];
        }
        $config = json_decode($config, true);
        foreach ($config as $key => $value) {
            $config[$key] = is_string($value) ? trim($value) : $value;
        }
        return $config;
    }

    private function handleFiles()
    {
        $files = $this->request->allFiles();

        // store google analytics certificate file
        if ($certificateFile = Arr::get($files, 'certificate')) {
            File::put(
                storage_path('laravel-analytics/certificate.json'),
                file_get_contents($certificateFile),
            );
        }
    }

    private function validateSettings(
        array $serverSettings,
        array $clientSettings, )
    {
        // flatten "client" and "server" arrays into single array
        $values = array_merge(
            $serverSettings ?: [],
            $clientSettings ?: [],
            $this->request->allFiles(),
        );
        $keys = array_keys($values);
        $validators = config('common.setting-validators');

        foreach ($validators as $validator) {
            if (empty(array_intersect($validator::KEYS, $keys))) {
                continue;
            }

            try {
                if ($messages = app($validator)->fails($values)) {
                    return $this->error(
                        __('Could not persist settings.'),
                        $messages,
                    );
                }
                // catch and display any generic error that might occur
            } catch (Exception $e) {
                // Common\Settings\Validators\GoogleLoginValidator => GoogleLoginValidator
                $class = (new ReflectionClass($validator))->getShortName();
                // GoogleLoginValidator => google-login-validator => google => google_group
                $groupName = explode('-', Str::kebab($class))[0] . '_group';
                return $this->error(__('Could not persist settings.'), [
                    $groupName => Str::limit($e->getMessage(), 200),
                ]);
            }
        }
    }
}
