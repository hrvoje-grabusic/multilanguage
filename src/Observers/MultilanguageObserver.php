<?php
/**
 * Created by PhpStorm.
 * Page: Bojidar
 * Date: 8/19/2020
 * Time: 2:53 PM
 */

namespace MicroweberPackages\Multilanguage\Observers;


use Illuminate\Database\Eloquent\Model;
use MicroweberPackages\Multilanguage\Models\MultilanguageTranslations;

class MultilanguageObserver
{
    protected static $fieldsToSave = [];

    public function retrieved(Model $model)
    {
        if (isset($model->translatable) && is_array($model->translatable)) {
            $multilanguage = [];
            foreach ($model->translatable as $fieldName) {

                if (empty($model->$fieldName)) {
                    continue;
                }

                $findTranslations = MultilanguageTranslations::where('field_name', $fieldName)
                    ->where('rel_type', $model->getTable())
                    ->where('rel_id', $model->id)
                    ->get();

                if ($findTranslations) {
                    foreach ($findTranslations as $findTranslate) {
                        $multilanguage[$findTranslate->locale][$fieldName] = $findTranslate->field_value;
                    }
                }
            }

            $model->multilanguage = $multilanguage;
        }

        if ($this->getLocale() == $this->getDefaultLocale()) {
            return;
        }

        // Module option translate
        if ($model->getTable() == 'options') {
            if (!empty($model->module)) {

                $translatableModuleOptions = [];
                foreach (get_modules_from_db() as $module) {
                    if (isset($module['settings']['translatable_options'])) {
                        $translatableModuleOptions[$module['module']] = $module['settings']['translatable_options'];
                    }
                }

                $translateModuleOption = false;
                if (isset($translatableModuleOptions[$model->module]) && in_array($model->option_key, $translatableModuleOptions[$model->module])) {
                    $translateModuleOption = true;
                }

                if ($translateModuleOption) {
                    $findTranslate = MultilanguageTranslations::where('field_name', 'option_value')
                        ->where('rel_type', $model->getTable())
                        ->where('rel_id', $model->id)
                        ->where('locale', $this->getLocale())
                        ->first();

                    if ($findTranslate) {
                        $model->option_value = $findTranslate->field_value;
                    }
                }
            }
        }

        // Replace fields
        if (isset($model->translatable) && is_array($model->translatable)) {
            foreach ($model->translatable as $fieldName) {

                if (empty($model->$fieldName)) {
                    continue;
                }

                $findTranslate = MultilanguageTranslations::where('field_name', $fieldName)
                    ->where('rel_type', $model->getTable())
                    ->where('rel_id', $model->id)
                    ->where('locale', $this->getLocale())
                    ->first();

                if ($findTranslate) {
                    $model->$fieldName = $findTranslate->field_value;
                }
            }
        }
    }

    public function saving(Model $model)
    {
        if (isset($model->multilanguage)) {
            unset($model->multilanguage);
        }

        if ($this->getLocale() == $this->getDefaultLocale()) {
            return;
        }

        if (isset($model->translatable) && is_array($model->translatable)) {
            foreach ($model->translatable as $fieldName) {
                self::$fieldsToSave[$fieldName] = $model->$fieldName;
                $fieldValue = $model->getOriginal($fieldName);
                if (!empty($fieldValue)) {
                    $model->$fieldName = $fieldValue;
                }
            }
        }
    }

    /**
     * Handle the Page "saving" event.
     *
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @return void
     */
    public function saved(Model $model)
    {
        if ($this->getLocale() == $this->getDefaultLocale()) {
            return;
        }

        if (isset($model->translatable) && is_array($model->translatable)) {
            foreach ($model->translatable as $fieldName) {

                $findTranslate = MultilanguageTranslations::where('field_name', $fieldName)
                    ->where('rel_type', $model->getTable())
                    ->where('rel_id', $model->id)
                    ->where('locale', $this->getLocale())
                    ->first();

                if ($findTranslate) {
                    $fieldValue = self::$fieldsToSave[$fieldName];
                    if (!is_null($fieldValue)) {
                        $findTranslate->field_value = $fieldValue;
                        $findTranslate->save();
                    }
                } else {
                    $fieldValue = self::$fieldsToSave[$fieldName];
                    if (!is_null($fieldValue)) {
                        MultilanguageTranslations::create([
                            'field_name' => $fieldName,
                            'field_value' => $fieldValue,
                            'rel_type' => $model->getTable(),
                            'rel_id' => $model->id,
                            'locale' => $this->getLocale()
                        ]);
                    }
                }
            }
            self::$fieldsToSave = [];
        }
    }

    protected function getDefaultLocale()
    {
        return strtolower(mw()->lang_helper->default_lang());
    }

    protected function getLocale()
    {
        return strtolower(mw()->lang_helper->current_lang());
    }
}