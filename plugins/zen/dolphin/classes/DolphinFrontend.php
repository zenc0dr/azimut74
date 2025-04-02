<?php namespace Zen\Dolphin\Classes;

class DolphinFrontend extends Core
{
    private ?array $segments;

    public static function init(&$cms_page): bool
    {
        $frontend = new self();
        if (!$frontend->getRoutes($cms_page)) {
            return false;
        }
        return true;
    }

    # Базовый роутинг
    private function getRoutes(&$cms_page): bool
    {
        $this->segments = request()->segments();

        if (!isset($this->segments[1])) {
            return false;
        }

        if ($this->isSchedulePage($cms_page)) {
            return true;
        }

        if ($this->isExtOffersPage($cms_page)) {
            return true;
        }

        if ($this->isExtStartPage($cms_page)) {
            return true;
        }
        
        if ($this->isExtPrintPage($cms_page)) {
            return true;
        }

        if ($this->isExtLandingPage($cms_page)) {
            return true;
        }

        return false;
    }

    private function isSchedulePage(&$cms_page): bool
    {
        if ($this->segments[1] === 'schedule') {
            return true;
        }
        return false;
    }

    # Стартовая страница EXT
    private function isExtStartPage(&$cms_page): bool
    {
        if ($this->segments[1] === 'ext' && !isset($this->segments[2])) {
            $data = $this->store('ExtStartPage')->getData();
            if (isset($_GET['dump'])) {
                dd($data);
            }
            $cms_page['Dolphin'] = $data;
            return true;
        }
        return false;
    }

    # Посадочная страница EXT
    private function isExtLandingPage(&$cms_page): bool
    {
        if ($this->segments[1] === 'ext' && isset($this->segments[2]) && $this->segments[2] !== 'booking') {
            $data = $this->store('LandingPage')->getData('ext', $this->segments[2]);
            if (isset($_GET['dump'])) {
                dd($data);
            }
            $cms_page['Dolphin'] = $data;
            return true;
        }
        return false;
    }

    # Страница подбора цен и бронирования EXT
    private function isExtOffersPage(&$cms_page): bool
    {
        if ($this->segments[1] === 'ext' && isset($this->segments[2]) && $this->segments[2] === 'booking') {
            $data = $this->store('OffersPage')->getData('ext');
            if (!$data) {
                return false;
            }
            if (isset($_GET['dump'])) {
                dd($data);
            }
            $cms_page['Dolphin'] = $data;
            return true;
        }
        return false;
    }

    # Страница для печати EXT
    private function isExtPrintPage(&$cms_page): bool
    {
        if ($this->segments[1] === 'ext' && isset($this->segments[2]) && $this->segments[2] === 'print') {
            $data = $this->store('DolphinPrintPage')->getData('ext');
            
            if (!$data) {
                return false;
            }
            if (isset($_GET['dump'])) {
                dd($data);
            }
            $cms_page['Dolphin'] = $data;
            return true;
        }
        return false;
    }
}
