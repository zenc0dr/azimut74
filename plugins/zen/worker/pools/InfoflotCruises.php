<?php namespace Zen\Worker\Pools;

use Mcmraak\Rivercrs\Models\Checkins as Checkin;
use Carbon\Carbon;

class InfoflotCruises extends Infoflot
{
    function addCruise($data)
    {
        $cruise = $data['data'];
        $cruise_id = $cruise['id'];

        $prices = $this->riverQuery("https://restapi.infoflot.com/cruises/$cruise_id/cabins", 'json', [
            'key' => $this->stream->model->data['key'],
        ]);

        if (empty($prices['prices'])) {
            return;
        }

        if (empty($prices['cabins'])) {
            return;
        }

        $infoflot_ship = $cruise['ship'];
        $ship = $this->getMotorship($infoflot_ship['name'], 'infoflot_id', $infoflot_ship['id']);
        if ($ship === false) {
            return;
        }

        $waybill = $this->getInfoflotWaybill($cruise);
//        $dateStart = $this->sqlDate($cruise['dateStart']); # Перестало работать, дата приходит иногда без времени
//        $dateEnd = $this->sqlDate($cruise['dateEnd']);

        $dateStart = Carbon::createFromTimestamp($cruise['dateStartTimestamp'])
            ->setTimeZone('Europe/Moscow')
            ->format('Y-m-d H:i:s');

        $dateEnd = Carbon::createFromTimestamp($cruise['dateEndTimestamp'])
            ->setTimeZone('Europe/Moscow')
            ->format('Y-m-d H:i:s');

        $checkin = Checkin::where('eds_code', 'infoflot')
            ->where('eds_id', $cruise_id)
            ->first();

        if (!$checkin) {
            $checkin = new Checkin;
        }

        if (!is_array($waybill) || count($waybill) < 2) {
            return;
        }

        $checkin->date = $dateStart;
        $checkin->dateb = $dateEnd;
        $checkin->desc_1 = '';
        $checkin->motorship_id = $ship->id;
        $checkin->active = 1;
        $checkin->eds_code = 'infoflot';
        $checkin->eds_id = (int)$cruise_id;
        $checkin->waybill_id = $waybill;
        $checkin->save();

        $this->fixCheckin($checkin->id);

        # Заполнение цен
        $this->fillInfoflotPrices($prices, $checkin, $ship);
    }
}
