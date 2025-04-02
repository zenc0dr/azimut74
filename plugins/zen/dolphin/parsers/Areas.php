<?php namespace Zen\Dolphin\Parsers;

use Zen\Dolphin\Classes\Parser;

use Zen\Dolphin\Models\Country;
use Zen\Dolphin\Models\Region;
use Zen\Dolphin\Models\City;

class Areas extends Parser
{
    private
        $areas;

    function go()
    {
        $this->parser_class_name = 'Areas';
        $this->parser_name = 'Географические объекты';
        $this->saveProgress('Запрос к источнику...');
        $this->areas = $this->store('Dolphin')->attemps(5, 5, 15)->query('Areas');
        $this->initCounts(count($this->areas));

        $this->addCountries();
        $this->addRegions();
        $this->addCities();

        $this->parserSuccess();
    }


    private function addCountries()
    {
        foreach ($this->areas as $geo) {
            if($geo['type'] == 'Country') {

                $country = Country::where('eid', $geo['id'])->first();

                if(!$country) {
                    $country = new Country;
                    $country->name = $geo['title'];
                    $country->eid = $geo['id'];
                    $country->created_by = 'dolphin';
                    $country->save();
                }

                $this->countries[$geo['id']] = $country->id;
                $this->saveProgress();
            }
        }
    }

    private function addRegions()
    {
        foreach ($this->areas as $geo) {
            if($geo['type'] == 'Region') {
                $region = Region::where('eid', $geo['id'])->first();
                if(!$region) {
                    $region = new Region;
                    $region->name = $geo['title'];
                    $region->country_id = $this->countries[$geo['countryId']];
                    $region->eid = $geo['id'];
                    $region->created_by = 'dolphin';
                    $region->save();
                }
                $this->regions[$geo['id']] = $region->id;
                $this->saveProgress();
            }
        }
    }

    private function addCities()
    {
        foreach ($this->areas as $geo) {
            if($geo['type'] == 'City') {
                $city = City::where('eid', $geo['id'])->first();
                if(!$city) {
                    $city = new City;
                    $city->name = $geo['title'];
                    $city->country_id = $this->countries[$geo['countryId']];
                    $city->region_id = $this->regions[$geo['regionId']];
                    $city->eid = $geo['id'];
                    $city->created_by = 'dolphin';
                    $city->save();
                }
                $this->cities[$geo['id']] = $city->id;
                $this->saveProgress();
            }
        }

        foreach ($this->areas as $geo) {
            if($geo['type'] == 'Distinct') {
                $city = City::where('eid', $geo['id'])->first();
                if(!$city) {
                    $city = new City;
                    $city->name = $geo['title'];
                    $city->country_id = $this->countries[$geo['countryId']];
                    $city->region_id = $this->regions[$geo['regionId']];

                    $pertain_id = @$this->cities[$geo['cityId']];

                    if(!$pertain_id) {
                        $this->saveProgress();
                        continue;
                    }

                    $city->pertain_id = $pertain_id;
                    $city->eid = $geo['id'];
                    $city->created_by = 'dolphin';
                    $city->save();
                }
                $this->saveProgress();
            }
        }
    }
}
