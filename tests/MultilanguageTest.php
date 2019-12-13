<?php

class MultilanguageTest extends \Microweber\tests\TestCase
{
    public function testSupportedLanguages()
    {
        // Set default lang
        $lang = 'en';
        $option = array();
        $option['option_value'] = $lang;
        $option['option_key'] = 'language';
        $option['option_group'] = 'website';
        save_option($option);

        mw()->lang_helper->set_current_lang($lang);
        $this->assertEquals($lang, mw()->lang_helper->current_lang());

        $languages = get_supported_languages();

        // Check default lang is exists on supported languages
        $locales = array();
        foreach ($languages as $language) {
            $locales[] = strtolower($language['locale']);
        }
        $default_lang = mw()->lang_helper->default_lang();
        $default_lang = strtolower($default_lang);
        $check = false;
        if (in_array($default_lang, $locales)) {
            $check = true;
        }

        $this->assertEquals(true, $check);

    }

    public function testAddNewLanguage()
    {

        $locale = 'bg';
        $language = 'Bulgarian';

        // Add language
        add_supported_language($locale, $language);

        $languages = get_supported_languages();

        $locales = array();
        foreach ($languages as $language) {
            $locales[] = strtolower($language['locale']);
        }

        $check = false;
        if (in_array($locale, $locales)) {
            $check = true;
        }

        $this->assertEquals(true, $check);

    }

    public function testSwitchLanguage()
    {
        mw()->lang_helper->set_current_lang('bg');
        $this->assertEquals('bg', mw()->lang_helper->current_lang());
    }

    public function testTranslateNewOption()
    {
        $this->_addNewMultilanguageOption('bozhidar', 'Bozhidar', 'Божидар');
        $this->_addNewMultilanguageOption('slaveykov', 'Slaveykov', 'Славейков');
        $this->_addNewMultilanguageOption('health', 'Health', 'Здраве');
        $this->_addNewMultilanguageOption('love', 'Love', 'Любов');
        $this->_addNewMultilanguageOption('apple', 'Apple', 'Ябълка');
        $this->_addNewMultilanguageOption('car', 'Car', 'Кола');
        $this->_addNewMultilanguageOption('rich', 'Rich', 'Богат');
    }

    private function _addNewMultilanguageOption($option_key, $en_option_value, $bg_option_value) {

        // Switch to english language
        mw()->lang_helper->set_current_lang('en');
        $this->assertEquals('en', mw()->lang_helper->current_lang());

        $option_group = 'new_option_test';

        // Add new english option
        $option = array();
        $option['option_value'] = $en_option_value;
        $option['option_key'] = $option_key;
        $option['option_group'] = $option_group;
        save_option($option);
        // Get option
        $this->assertEquals($en_option_value, get_option($option_key, $option_group));

        /**
         * TEST BULGARIAN LANGUAGE
         * Switch to bulgarian language
         */
        mw()->lang_helper->set_current_lang('bg');
        $this->assertEquals('bg', mw()->lang_helper->current_lang());

        // Update english option
        $option = array();
        $option['option_value'] = $bg_option_value;
        $option['option_key'] = $option_key;
        $option['option_group'] = $option_group;
        save_option($option);
        // Get bg option
        $this->assertEquals($bg_option_value, get_option($option_key, $option_group));


        /**
         * TEST ENGLISH LANGUAGE
         * Switch to english language
         */
        mw()->lang_helper->set_current_lang('en');
        $this->assertEquals('en', mw()->lang_helper->current_lang());
        // Get en option
        $this->assertEquals($en_option_value, get_option($option_key, $option_group));


        /**
         * TEST BULGARIAN LANGUAGE
         * Switch to bulgarian language
         */
        mw()->lang_helper->set_current_lang('bg');
        $this->assertEquals('bg', mw()->lang_helper->current_lang());
        // Get bg option
        $this->assertEquals($bg_option_value, get_option($option_key, $option_group));

    }

    public function testTranslateNewMenu() {

        // Switch to english language
        mw()->lang_helper->set_current_lang('en');
        $this->assertEquals('en', mw()->lang_helper->current_lang());

        $menu = array();
        $menu['title'] = 'Richest people in the world';
        $menu['url'] = 'richest-people-in-the-world';
        mw()->menu_manager->menu_create($menu);

        $get_menu = mw()->menu_manager->get_menu('url=richest-people-in-the-world&single=1');

        $this->assertEquals($get_menu['title'], $menu['title']);
        $this->assertEquals($get_menu['url'], $menu['url']);


        /**
         * TEST BULGARIAN LANGUAGE
         * Switch to bulgarian language
         */
        mw()->lang_helper->set_current_lang('bg');
        $this->assertEquals('bg', mw()->lang_helper->current_lang());

        $update = array();
        $update['menu_id'] = $get_menu['id'];
        $update['title'] = 'Най-богатите хора в света';
        $update['url'] = 'nai-bogatite-xora-v-sveta';

        mw()->menu_manager->menu_create($update);

        $get_menu = mw()->menu_manager->get_menu('id='.$get_menu['id'].'&single=1');

        $this->assertEquals($get_menu['title'], $update['title']);
        $this->assertEquals($get_menu['url'], $update['url']);

    }
}