<?php namespace Zen\GroupTours\Classes;

class Frontend extends Core
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
        if ($this->isLandingPage($cms_page)) {
            return true;
        }
        if ($this->isTourPage($cms_page)) {
            return true;
        }
        return false;
    }

    # Посадочная страница
    private function isLandingPage(&$cms_page): bool
    {
        if (!isset($this->segments[1])) {
            return false;
        }

        if ($this->segments[1] !== 'tour') {
            $data = $this->store('LandingPage', ['slug' => $this->segments[1]]);

            if (!$data) {
                return false;
            }

            if (isset($_GET['dump'])) {
                dd($data);
            }

            $cms_page['GroupTours'] = $data;
            return true;
        }
        return false;
    }

    private function isTourPage(&$cms_page): bool
    {

        if (preg_match('/^tour-(\d+)$/', $this->segments[1], $match)) {
            $data = $this->store('TourPage', ['tour_id' => $match[1]]);

            if (!$data) {
                return false;
            }

            if (isset($_GET['dump'])) {
                dd($data);
            }

            $cms_page['GroupTours'] = $data;
            return true;
        }
        return false;
    }
}
