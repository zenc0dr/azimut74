<?php namespace Mcmraak\Rivercrs\Classes;

use Mcmraak\Rivercrs\Models\Checkins;
use Mcmraak\Rivercrs\Models\Cruises;
use Mcmraak\Rivercrs\Models\Transit;
use Mcmraak\Rivercrs\Models\Motorships;
use Mcmraak\Rivercrs\Models\Reference;
use Carbon\Carbon;

class RivercrsCore
{
    private ?array $segments;
    private $cruise;


    public static function init(&$cms_page): bool
    {
        $river_crs = new self();
        if (!$river_crs->getRoutes($cms_page)) {
            return false;
        }
        return true;
    }

    private function getRoutes(&$cms_page): bool
    {
        $this->segments = request()->segments();

        if ($this->isReference($cms_page)) {
            return true;
        }

        if ($this->isShipsPage()) {
            return true;
        }

        if ($this->isShipPage($cms_page)) {
            return true;
        }

        if ($this->isCruisesPage($cms_page)) {
            return true;
        }

        if ($this->isCruisePage($cms_page)) {
            return true;
        }

        return false;
    }

    private function isReference(&$cms_page): bool
    {
        $slug = $this->segments[1] ?? null;
        if ($slug !== 'references') {
            return false;
        }

        if (!$this->segments[2] ?? null) {
            return false;
        }

        $reference = Reference::where('slug', $this->segments[2])->first();

        if (!$reference) {
            return false;
        }

        $cruise = Cruises::where('slug', 'river-cruises')->first();

        $data =  [
            'meta_title' => $reference->metatitle,
            'meta_description' => $reference->metadesc,
            'meta_keywords' => $reference->metakey,
            'html' => $reference->text,
            'leftMenu' => RivercrsLeftmenu::build($cruise, null),
        ];

        if (isset($_GET['dump'])) {
            dd($data);
        }

        $cms_page['RiverCRS'] = $data;
        return true;
    }

    private function isShipsPage(): bool
    {
        $slug = $this->segments[1] ?? null;
        if ($slug !== 'ships') {
            return false;
        }
        return true;
    }

    private function isShipPage(&$cms_page): bool
    {
        $slug = $this->segments[1] ?? null;
        if ($slug !== 'motorship') {
            return false;
        }

        if (!isset($this->segments[2])) {
            return false;
        }

        $ship_id = $this->segments[2];
        $ship = Motorships::find($ship_id);

        if (!$ship) {
            return false;
        }

        $data = RivercrsShip::getShip($ship);

        if (isset($_GET['dump'])) {
            dd($data);
        }

        $cms_page['RiverCRS'] = $data;
        return true;
    }

    private function isCruisesPage(&$cms_page)
    {
        $slug = $this->segments[1] ?? 'river-cruises';

        $is_transit_page = false;


        $this->cruise = $cruise = Cruises::where('slug', $slug)->where('active', 1)->first();

        if (!$cruise) {
            $is_transit_page = true;
            $this->cruise = $cruise =  Transit::where('slug', $slug)->where('active', 1)->first();
        }

        if (!$cruise) {
            return false;
        }

        # Ассеты необходимые для поиска
        #$cms_page->addCss('/plugins/mcmraak/rivercrs/assets/css/prok/multi_dropdown.css');
        #$cms_page->addCss('/themes/prokruiz/assets/css/blocks/search-widget.css');
        #$cms_page->addCss('/plugins/mcmraak/rivercrs/assets/css/datepicker.min.css');
        $cms_page->addJs('/plugins/mcmraak/rivercrs/assets/js/jquery-2.2.4.min.js');
        #$cms_page->addJs('/plugins/mcmraak/rivercrs/assets/js/datepicker/datepicker.js');
        #$cms_page->addJs('/plugins/mcmraak/rivercrs/assets/js/vue.min.js');
        #$cms_page->addJs('/plugins/mcmraak/rivercrs/assets/js/vue.components.js');
        #$cms_page->addJs('/plugins/mcmraak/rivercrs/assets/js/rivercrs.widget.js');

        $data = [
            'meta_title' => $cruise->metatitle,
            'meta_description' => $cruise->metadesc,
            'meta_keywords' => $cruise->metakey,
            'searchPreset' => $this->searchPreset(),
            'cruise' => $cruise,
            'leftMenu' => RivercrsLeftmenu::build($cruise, $is_transit_page),
            'articles' => RivercrsArticles::articles($this->cruise),
            'seoLinks' => RivercrsSeoLinks::getSeoLinks()
        ];

        if (isset($_GET['dump'])) {
            dd($data);
        }

        $cms_page['RiverCRS'] = $data;

        return true;
    }

    private function isCruisePage(&$cms_page)
    {
        if ($this->segments[1] === 'cruise') {
            $checkin = Checkins::where('active', 1)->find($this->segments[2]);
            if (!$checkin) {
                return false;
            }
        } else {
            return false;
        }

        # Ассеты необходимые для внешнего модуля бронирования
        $cms_page->addJs('/plugins/mcmraak/rivercrs/assets/js/jquery-2.2.4.min.js');
        $cms_page->addJs('/plugins/mcmraak/rivercrs/assets/js/vue.min.js');
        $cms_page->addCss('/plugins/mcmraak/rivercrs/assets/css/rivercrs.cabin_modal.css');
        $cms_page->addJs('/themes/prokruiz/assets/js/libs/jquery.inputmask.bundle.js');
        $cms_page->addJs('/plugins/mcmraak/rivercrs/assets/js/rivercrs.booking.js?v2');

        $data = RivercrsCheckin::checkin($checkin);

        if (isset($_GET['dump'])) {
            dd($data);
        }

        $cms_page['RiverCRS'] = $data;

        return true;
    }

    public function searchPreset()
    {
        $cruise = $this->cruise;

        return json_encode([
            't1' => $cruise->town1 ?? null,
            't2' => $cruise->town2 ?? null,
            'd1' => $cruise->date1 ? Carbon::parse($cruise->date1)->format('d.m.Y') : null,
            'd2' => $cruise->date2 ? Carbon::parse($cruise->date2)->format('d.m.Y') : null,
            'd' => $cruise->days ?? null,
            's' => $cruise->ship_id ?? null
        ], 256);
    }


}
