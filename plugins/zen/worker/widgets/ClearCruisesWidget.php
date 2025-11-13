<?php namespace Zen\Worker\Widgets;

use Backend\Classes\ReportWidgetBase;
use Zen\Worker\Controllers\Admin;
use Flash;
use October\Rain\Exception\ApplicationException;

class ClearCruisesWidget extends ReportWidgetBase
{
    protected $defaultAlias = 'clear_cruises';

    public function render()
    {
        // Проверка окружения - виджет только для dev
        if (env('APP_ENV') !== 'dev') {
            return '';
        }

        return $this->makePartial('widget');
    }

    public function defineProperties()
    {
        return [
            'title' => [
                'title'             => 'backend::lang.dashboard.widget_title_label',
                'default'           => 'Очистка базы круизов',
                'type'              => 'string',
                'validationPattern' => '^.+$',
                'validationMessage' => 'backend::lang.dashboard.widget_title_error',
            ]
        ];
    }

    public function onClearCruises()
    {
        // Проверка окружения для безопасности
        if (env('APP_ENV') !== 'dev') {
            Flash::error('Доступно только в dev окружении');
            return [
                'error' => 'Доступно только в dev окружении',
                'partial' => $this->makePartial('widget')
            ];
        }

        try {
            Admin::clearCruises();
            
            Flash::success('База круизов успешно очищена');
            
            return [
                'partial' => $this->makePartial('widget')
            ];
        } catch (ApplicationException $ex) {
            Flash::error('Ошибка при очистке: ' . $ex->getMessage());
            
            return [
                'error' => $ex->getMessage(),
                'partial' => $this->makePartial('widget')
            ];
        } catch (\Exception $ex) {
            Flash::error('Ошибка при очистке: ' . $ex->getMessage());
            
            return [
                'error' => $ex->getMessage(),
                'partial' => $this->makePartial('widget')
            ];
        }
    }
}

