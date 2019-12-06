<?php
require_once __DIR__ . '/TranslateTable.php';
require_once __DIR__ . '/TranslateTables/TranslateMenu.php';
require_once __DIR__ . '/TranslateTables/TranslateOption.php';
require_once __DIR__ . '/TranslateTables/TranslateCategory.php';
require_once __DIR__ . '/TranslateTables/TranslateContent.php';
require_once __DIR__ . '/TranslateTables/TranslateContentFields.php';
require_once __DIR__ . '/TranslateTables/TranslateTestimonials.php';

class TranslateManager
{

    public $translateProviders = [
        'TranslateMenu',
        'TranslateOption',
        'TranslateCategory',
        'TranslateContent',
        'TranslateContentFields',
        'TranslateTestimonials'
    ];


    public function run()
    {
        //if ($currentLocale != $defaultLocale) {
        event_bind('content.get_by_url', function ($url)  {

            if (!empty($url)) {

                $targetUrl = $url;
                $targetLang = false;
                $segments = explode('/', $url);
                if (count($segments) == 2) {
                    $targetLang = $segments[0];
                    $targetUrl = $segments[1];
                }

                if (!$targetLang) {
                    return;
                }

                change_language_by_locale($targetLang);

                $filter = array();
                $filter['single'] = 1;
                $filter['rel_type'] = 'content';
                $filter['field_name'] = 'url';
                $filter['field_value'] = $targetUrl;
                
                $findTranslate = db_get('translations', $filter);

                if ($findTranslate) {

                    $get = array();
                    $get['id'] = $findTranslate['rel_id'];
                    $get['single'] = true;
                    $content = mw()->content_manager->get($get);

                    if ($content['url'] == $findTranslate['field_value']) {
                        return $content;
                    } else {
                        // Redirect to target lang & finded content url
                        header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
                        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
                        header('HTTP/1.1 301');
                        header('Location: ' . site_url() . $targetLang .'/' . $content['url']);
                        exit;
                    }
                } else {
                    $get = array();
                    $get['url'] = $targetUrl;
                    $get['single'] = true;

                    $content = mw()->content_manager->get($get);
                    if ($content) {
                        return $content;
                    }
                }
            }

            return;
        });

        $currentLocale = mw()->lang_helper->current_lang();
        $defaultLocale = mw()->lang_helper->default_lang();

        if (!empty($this->translateProviders)) {
            foreach ($this->translateProviders as $provider) {

                $providerInstance = new $provider();
                $providerTable = $providerInstance->getRelType();

                // BIND GET TABLES
                event_bind('mw.database.' . $providerTable . '.get', function ($get) use ($currentLocale, $defaultLocale, $providerInstance) {
                    if (is_array($get) && !empty($get)) {
                        foreach ($get as &$item) {
                            if (isset($item['option_key']) && $item['option_key'] == 'language') {
                                continue;
                            }
                            $item = $providerInstance->getTranslate($item);
                        }
                    }
                    return $get;
                });

                // BIND SAVE TABLES
                event_bind('mw.database.' . $providerTable . '.save.params', function ($saveData) use ($currentLocale, $defaultLocale, $providerInstance) {
                    if ($currentLocale != $defaultLocale) {

                        if (isset($saveData['option_key']) && $saveData['option_key'] == 'language') {
                            return false;
                        }

                        if (!empty($providerInstance->getColumns())) {
                            $dataForTranslate = $saveData;
                            foreach ($providerInstance->getColumns() as $column) {

                                if (!isset($saveData['id'])) {
                                    continue;
                                }

                                if (isset($saveData[$column])) {
                                    unset($saveData[$column]);
                                }
                            }

                            if (!empty($dataForTranslate)) {
                                $providerInstance->saveOrUpdate($dataForTranslate);
                            }
                        }
                    }

                    return $saveData;

                });

            }
        }

    }

}