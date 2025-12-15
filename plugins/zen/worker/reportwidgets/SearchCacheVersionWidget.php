<?php namespace Zen\Worker\ReportWidgets;

use Backend\Classes\ReportWidgetBase;
use Zen\Worker\Classes\SearchCacheVersion;
use Flash;

class SearchCacheVersionWidget extends ReportWidgetBase
{
    protected $defaultAlias = 'search_cache_version';

    public function render()
    {
        $this->vars['searchCacheVersion'] = SearchCacheVersion::get();

        return $this->makePartial('widget');
    }

    public function defineProperties()
    {
        return [
            'title' => [
                'title'             => 'backend::lang.dashboard.widget_title_label',
                'default'           => 'Версия поискового кеша',
                'type'              => 'string',
                'validationPattern' => '^.+$',
                'validationMessage' => 'backend::lang.dashboard.widget_title_error',
            ]
        ];
    }

    /**
     * Обработчик кнопки увеличения версии кеша поиска.
     */
    public function onIncrementVersion()
    {
        try {
            $newVersion = SearchCacheVersion::increment();

            $this->vars['searchCacheVersion'] = $newVersion;

            if ($newVersion > 0) {
                Flash::success('Версия поискового кеша обновлена');
            } else {
                Flash::warning('Не удалось обновить версию поискового кеша');
            }
        } catch (\Exception $ex) {
            Flash::error('Ошибка при обновлении версии кеша: ' . $ex->getMessage());
            $this->vars['searchCacheVersion'] = SearchCacheVersion::get();
        }

        return [
            '#' . $this->getId() => $this->makePartial('widget')
        ];
    }
}

