<?php
/**
 * CountryController.php
 *
 * @copyright  2023 beikeshop.com - All Rights Reserved
 * @link       https://beikeshop.com
 * @author     Edward Yang <yangjin@guangda.work>
 * @created    2023-08-15 14:27:07
 * @modified   2023-08-15 14:27:07
 */

namespace Beike\ShopAPI\Controllers;

use App\Http\Controllers\Controller;
use Beike\Models\City;
use Beike\Models\Country;
use Beike\Models\Zone;
use Beike\Repositories\CountryRepo;
use Beike\Repositories\ZoneRepo;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    public function index(Request $request)
    {
        return CountryRepo::listEnabled();
    }

    public function zones(Request $request, Country $country)
    {
        return ZoneRepo::listByCountry($country->id);
    }

    /**
     * @param Request $request
     * @param Zone $zone
     * @return mixed
     */
    public function cities(Request $request, Zone $zone): mixed
    {
        return City::query()
            ->where('zone_id', $zone->id)
            ->where('parent_id', 0)
            ->where('active', 1)
            ->get();
    }

    /**
     * @param Request $request
     * @param City $city
     * @return mixed
     */
    public function counties(Request $request, City $city): mixed
    {
        return City::query()
            ->where('parent_id', $city->id)
            ->where('active', 1)
            ->get();
    }
}
