<?php
//namespace ...;
use Path\To\Class\GoogleApiSettings;

//todo: create constant STORAGE_PATH

class GooglePlacesApi
{
    const ENDPOINT = 'https://maps.googleapis.com/maps/api/place/';
    private static
    $debugMode = false,
    $waitBeforeRequest = false,
    $requestsIntervalSeconds = 1,
    $nextPageDelay = 1,
    $skipClosed = true,
    $nextPageToken = '',
    $lastRequestMethod = '',
    $lastRequestLanguage = '',
    $mockResponse = false,
    $apikey = '';

    const
        TYPE__TOURIST_ATTRACTION = 'tourist_attraction',
        TYPE__LODGING = 'lodging',
        TYPE__POINT_OF_INTEREST = 'point_of_interest',
        TYPE__ESTABLISHMENT = 'establishment',
        TYPE__GYM = 'gym',
        TYPE__HEALTH = 'health',
        TYPE__CLOTHING_STORE = 'clothing_store',
        TYPE__STORE = 'store',
        TYPE__BAR = "bar",
        TYPE__CAFE = "cafe",
        TYPE__RESTAURANT = "restaurant",
        TYPE__FOOD = "food",
        STATUS__OK = 'OK',
        STATUS__ZERO_RESULTS = 'ZERO_RESULTS',
        STATUS__INVALID_REQUEST = 'INVALID_REQUEST',
        STATUS__OVER_QUERY_LIMIT = 'OVER_QUERY_LIMIT',
        STATUS__REQUEST_DENIED = 'REQUEST_DENIED';
    const
        BASIC__PLACE_ID = 'place_id',
        BASIC__NAME = 'name',
        BASIC__BUSINESS_STATUS = 'business_status',
        DETAILS__WEBSITE = 'website',
        DETAILS__INTER_PHONE_NUMBER = 'international_phone_number',
        DETAILS__OPENING_HOURS = 'opening_hours',
        DETAILS__FORMATTED_ADDRESS = 'formatted_address',
        DETAILS__URL = 'url';

    const//place types
        catering__bar = "bar",
        catering__cafe = "cafe",
        catering__restaurant = "restaurant",
        catering__food = "food",
        shopping__liquor_store = 'liquor_store',
        shopping__shopping_mall = 'shopping_mall',
        shopping__store = 'store',
        shopping__clothing_store = 'clothing_store',
        shopping__bakery = 'bakery',
        shopping__bicycle_store = 'bicycle_store',
        shopping__book_store = 'book_store',
        shopping__convenience_store = 'convenience_store',
        shopping__department_store = 'department_store',
        shopping__electronics_store = 'electronics_store',
        shopping__furniture_store = 'furniture_store',
        shopping__meal_delivery = 'meal_delivery',
        shopping__meal_takeaway = 'meal_takeaway',
        shopping__jewelry_store = 'jewelry_store',
        shopping__florist = 'florist',
        shopping__laundry = 'laundry',
        shopping__hardware_store = 'hardware_store',
        shopping__home_goods_store = 'home_goods_store',
        shopping__supermarket = 'supermarket',
        shopping__shoe_store = 'shoe_store',
        shopping__painter = 'painter',
        shopping__plumber = 'plumber',
        shopping__library = 'library',
        shopping__storage = 'storage',
        shopping__locksmith = 'locksmith',
        shopping__electrician = 'electrician',
        shopping__movie_rental = 'movie_rental',
        finances__bank = 'bank',
        finances__finance = 'finance',
        finances__atm = 'atm',
        finances__accounting = 'accounting',
        sport__gym = 'gym',
        sport__stadium = 'stadium',
        body__health = 'health',
        body__beauty_salon = 'beauty_salon',
        body__dentist = 'dentist',
        body__doctor = 'doctor',
        body__drugstore = 'drugstore',
        body__hair_care = 'hair_care',
        body__hospital = 'hospital',
        body__insurance_agency = 'insurance_agency',
        body__pharmacy = 'pharmacy',
        body__physiotherapist = 'physiotherapist',
        body__spa = 'spa',
        government__courthouse = 'courthouse',
        government__embassy = 'embassy',
        government__police = 'police',
        government__city_hall = 'city_hall',
        government__post_office = 'post_office',
        government__post_box = 'post_box',
        government__lawyer = 'lawyer',
        government__fire_station = 'fire_station',
        government__local_government_office = 'local_government_office',
        transport__travel_agency = 'travel_agency',
        transport__car_repair = 'car_repair',
        transport__bus_station = 'bus_station',
        transport__car_dealer = 'car_dealer',
        transport__car_rental = 'car_rental',
        transport__car_wash = 'car_wash',
        transport__airport = 'airport',
        transport__gas_station = 'gas_station',
        transport__parking = 'parking',
        transport__rv_park = 'rv_park',
        transport__subway_station = 'subway_station',
        transport__taxi_stand = 'taxi_stand',
        transport__train_station = 'train_station',
        transport__light_rail_station = 'light_rail_station',
        transport__transit_station = 'transit_station',
        attraction__tourist_attraction = 'tourist_attraction',
        attraction__campground = 'campground',
        attraction__amusement_park = 'amusement_park',
        attraction__art_gallery = 'art_gallery',
        attraction__bowling_alley = 'bowling_alley',
        attraction__casino = 'casino',
        attraction__movie_theater = 'movie_theater',
        attraction__museum = 'museum',
        attraction__night_club = 'night_club',
        animals__pet_store = 'pet_store',
        animals__veterinary_care = 'veterinary_care',
        animals__zoo = 'zoo',
        nature__aquarium = 'aquarium',
        nature__natural_feature = 'natural_feature',
        nature__park = 'park',
        nature__town_square = 'town_square',
        religion__place_of_worship = 'place_of_worship',
        religion__cemetery = 'cemetery',
        religion__church = 'church',
        religion__mosque = 'mosque',
        religion__hindu_temple = 'hindu_temple',
        religion__funeral_home = 'funeral_home',
        religion__synagogue = 'synagogue',
        education__school = 'school',
        education__secondary_school = 'secondary_school',
        education__university = 'university',
        education__primary_school = 'primary_school',
        real_estate__moving_company = 'moving_company',
        real_estate__real_estate_agency = 'real_estate_agency',
        real_estate__lodging = 'lodging',
        other__point_of_interest = 'point_of_interest',
        other__establishment = 'establishment';

    //Before using API you have to created billing and attach card
    //pricing info: https://mapsplatform.google.com/pricing/

    /* response when service must be paid: {
        "error_message" : "You must enable Billing on the Google Cloud Project at
            https://console.cloud.google.com/project/_/billing/enable
            Learn more at https://developers.google.com/maps/gmp-get-started",
        "html_attributions" : [],
        "results" : [],
        "status" : "REQUEST_DENIED"
     }
     ....
     when API is not enabled: array (
        'error_message' => 'This API project is not authorized to use this API.',
        'html_attributions' =>...,
        'results' => [],
        'status' => 'REQUEST_DENIED',
      ),
     *      */

    //setters
    public static function setDebugMode($enabled)
    {
        self::$debugMode = !empty($enabled);
    }
    public static function setSkipClosed($enabled)
    {
        self::$skipClosed = !empty($enabled);
    }

    public static function getAPIKey()
    {
        static $apikey = '';
        if (empty($apikey)) {
            $apikey = GoogleApiSettings::get_places_api_key();
        }
        return $apikey;
    }

    private static function log($variable, $value = NULL)
    {
        if (self::$debugMode) {
            $output = __CLASS__ . "\n $variable";
            if (!is_null($value)) {
                if (!is_string($value)) {
                    $value = var_export($value, 1);
                }
                $output .= " = " . (mb_strlen($value) > 1500
                    ? mb_substr($value, 0, 1350) . "\n...\n" . mb_substr($value, -150)
                    : $value);
            }
            echo "$output\n";
        }
    }


    private static function fileDump($label, $data = false)
    {
        $path = STORAGE_PATH . DIRECTORY_SEPARATOR . "gplaces_" . $label . ".dump";
        if ($data === false) {
            if (!file_exists($path) || !filesize($path)) {
                return false;
            }
            return @unserialize(file_get_contents($path));
        }
        return (bool) file_put_contents($path, serialize($data));
    }
    public static function mockResponseFromDump($dumpFileLabel)
    {
        self::$mockResponse = self::fileDump($dumpFileLabel);
        return !empty(self::$mockResponse);
    }


    private static function getResult($method, $query, $resultKey = 'result')
    {
        $suffix = '&' . http_build_query(["key" => self::getAPIKey()]);
        self::$lastRequestMethod = $method;
        $request = self::ENDPOINT . "$method/json?" . http_build_query($query);

        if (self::$waitBeforeRequest) {
            sleep(self::$requestsIntervalSeconds);
        } else {
            self::$waitBeforeRequest = true;
        }
        if (self::$mockResponse) {
            $result = self::$mockResponse;
        } else {
            $response = file_get_contents($request . $suffix);
            self::log($request, $response);//log without key
            $result = json_decode($response, true);
            //self::fileDump($method.'_'.time(), $result);
        }
        self::$nextPageToken = empty($result['next_page_token']) ? '' : $result['next_page_token'];
        $correctStatuses = [
            self::STATUS__OK,
            self::STATUS__ZERO_RESULTS
        ];
        if (empty($result['status']) || !in_array($result['status'], $correctStatuses)) {
            return [
                'error' => empty($result['error_message']) ? 'unknown' : $result['error_message'],
                'status' => $result['status']
            ];
        }
        /*
        INVALID_REQUEST - missing required query parameter (location or radius)
        OVER_QUERY_LIMIT - exceeded the QPS limits
            | Billing has not been enabled
            | The monthly $200 credit, or a self-imposed usage cap, has been exceeded.
            | The provided method of payment is no longer valid (for ex., a credit card has expired).
        REQUEST_DENIED - missing an API key | The key parameter is invalid.
        UNKNOWN_ERROR ...
         */
        return isset($result[$resultKey]) ? $result[$resultKey] : [];
    }


    public static function nextPage()
    {
        if (empty(self::$nextPageToken) || empty(self::$lastRequestMethod)) {
            return [];
        }
        /*
        There is a short delay before token becomes valid.
        Requesting the next page before it is available will return an INVALID_REQUEST response.
        Retrying the request with the same next_page_token will return the next page of results.
        Setting pagetoken will cause any other parameters to be ignored.
        You can request a new page up to two times following the original query.
        Each search counts as a single request against your usage limits.
         */
        if (self::$nextPageDelay) {
            sleep(self::$nextPageDelay);
        }
        $results = self::getNextPage(self::$lastRequestMethod, self::$nextPageToken);
        if (empty($results['error'])) {
            self::filterResults($results);
        }
        return $results;
    }

    private static function filterResults(&$results, $language = '')
    {
        $language = $language ? $language : (self::$lastRequestLanguage ? self::$lastRequestLanguage : 'en');
        self::$lastRequestLanguage = $language;
        $skipTypes = ['political']; //'locality'
        $skipFields = [
            'icon',
            'icon_background_color',
            'icon_mask_base_uri',
            'scope',
            'geometry',
            'business_status',
            'permanently_closed',
            'price_level',
            'reference',
            'reviews',
            'photos', //todo: remove it when downloading is implemented
        ];
        if (!empty($results)) {
            foreach ($results as $index => &$record) {
                $operating = !empty($record['business_status'])
                    && $record['business_status'] === 'OPERATIONAL';
                if (self::$skipClosed && !$operating) {
                    unset($results[$index]);
                    continue;
                }
                ///
                $types = empty($record['types']) ? [] : $record['types'];
                foreach ($skipTypes as $type) {
                    if (in_array($type, $types)) {
                        unset($results[$index]);
                        break;
                    }
                }
                ///
                //moving 'location' 1 level up
                $record['location'] = $record['geometry']['location'];
                $record['language'] = $language;
                $record['operating'] = $operating;
                foreach ($skipFields as $field) {
                    if (isset($record[$field])) {
                        unset($record[$field]);
                    }
                }
            }//foreach
        }//if
    }

    public static function convertableTypeDomain()
    {
        return [
            self::catering__bar => "bar",
            self::catering__cafe => "cafe",
            self::catering__restaurant => "restaurant",
            self::catering__food => "food",
            self::shopping__liquor_store => 'liquor store',
            self::shopping__shopping_mall => 'shopping mall',
            self::shopping__store => 'store',
            self::shopping__clothing_store => 'clothing store',
            self::shopping__bakery => 'bakery',
            self::shopping__bicycle_store => 'bicycle store',
            self::shopping__book_store => 'book store',
            self::shopping__convenience_store => 'convenience store',
            self::shopping__department_store => 'department store',
            self::shopping__electronics_store => 'electronics store',
            self::shopping__furniture_store => 'furniture store',
            self::shopping__meal_delivery => 'meal delivery',
            self::shopping__meal_takeaway => 'meal takeaway',
            self::shopping__jewelry_store => 'jewelry store',
            self::shopping__florist => 'florist',
            self::shopping__laundry => 'laundry',
            self::shopping__hardware_store => 'hardware store',
            self::shopping__home_goods_store => 'home goods store',
            self::shopping__supermarket => 'supermarket',
            self::shopping__shoe_store => 'shoe store',
            self::shopping__painter => 'painter',
            self::shopping__plumber => 'plumber',
            self::shopping__library => 'library',
            self::shopping__storage => 'storage',
            self::shopping__locksmith => 'locksmith',
            self::shopping__electrician => 'electrician',
            self::shopping__movie_rental => 'movie rental',
            self::finances__bank => 'bank',
            self::finances__finance => 'finance',
            self::finances__atm => 'atm',
            self::finances__accounting => 'accounting',
            self::sport__gym => 'gym',
            self::sport__stadium => 'stadium',
            self::body__health => 'health',
            self::body__beauty_salon => 'beauty salon',
            self::body__dentist => 'dentist',
            self::body__doctor => 'doctor',
            self::body__drugstore => 'drugstore',
            self::body__hair_care => 'hair care',
            self::body__hospital => 'hospital',
            self::body__insurance_agency => 'insurance agency',
            self::body__pharmacy => 'pharmacy',
            self::body__physiotherapist => 'physiotherapist',
            self::body__spa => 'spa',
            self::government__courthouse => 'courthouse',
            self::government__embassy => 'embassy',
            self::government__police => 'police',
            self::government__city_hall => 'city hall',
            self::government__post_office => 'post office',
            self::government__post_box => 'post box',
            self::government__lawyer => 'lawyer',
            self::government__fire_station => 'fire station',
            self::government__local_government_office => 'local government office',
            self::transport__travel_agency => 'travel agency',
            self::transport__car_repair => 'car repair',
            self::transport__bus_station => 'bus station',
            self::transport__car_dealer => 'car dealer',
            self::transport__car_rental => 'car rental',
            self::transport__car_wash => 'car wash',
            self::transport__airport => 'airport',
            self::transport__gas_station => 'gas station',
            self::transport__parking => 'parking',
            self::transport__rv_park => 'rv park',
            self::transport__subway_station => 'subway station',
            self::transport__taxi_stand => 'taxi stand',
            self::transport__train_station => 'train station',
            self::transport__light_rail_station => 'light rail station',
            self::transport__transit_station => 'transit station',
            self::attraction__tourist_attraction => 'tourist attraction',
            self::attraction__campground => 'campground',
            self::attraction__amusement_park => 'amusement park',
            self::attraction__art_gallery => 'art gallery',
            self::attraction__bowling_alley => 'bowling alley',
            self::attraction__casino => 'casino',
            self::attraction__movie_theater => 'movie theater',
            self::attraction__museum => 'museum',
            self::attraction__night_club => 'night club',
            self::animals__pet_store => 'pet store',
            self::animals__veterinary_care => 'veterinary care',
            self::animals__zoo => 'zoo',
            self::nature__aquarium => 'aquarium',
            self::nature__natural_feature => 'natural feature',
            self::nature__park => 'park',
            self::nature__town_square => 'town square',
            self::religion__place_of_worship => 'place of worship',
            self::religion__cemetery => 'cemetery',
            self::religion__church => 'church',
            self::religion__mosque => 'mosque',
            self::religion__hindu_temple => 'hindu temple',
            self::religion__funeral_home => 'funeral home',
            self::religion__synagogue => 'synagogue',
            self::education__school => 'school',
            self::education__secondary_school => 'secondary school',
            self::education__university => 'university',
            self::education__primary_school => 'primary school',
            self::real_estate__moving_company => 'moving company',
            self::real_estate__real_estate_agency => 'real estate agency',
            self::real_estate__lodging => 'lodging',
            //self::other__point_of_interest => 'point of interest',
            //self::other__establishment => 'establishment',
        ];
    }

    //
    //Nearby Search and Text Search return all of the available data fields for the selected place
    //(a subset of the supported fields) and you will be billed accordingly.
    //To keep from requesting and paying for data that you don't need, use a Find Place request instead
    //

    public static function nearBySearch(
        $locationLatitude,
        $locationLongitude, //required
        $radius = 0, //in meters, max 50000 (50km)
        $placeType = '', //Only one type may be specified. Examples: hospital, pharmacy, doctor
        $keyword = '', //must be a place name, address, or category of establishments.
        //If this parameter is omitted, places with a business_status of CLOSED_TEMPORARILY or CLOSED_PERMANENTLY will not be returned.
        $language = 'en' //supported values: https://developers.google.com/maps/faq#languagesupport
        //$maxPrice, $minPrice,
    ) {
        //Adding both `keyword` and `type` with the same value can yield `ZERO_RESULTS`.
        //opennow: Returns only places open for business at the time the query is sent.
        //pagetoken: Returns up to 20 results from a previously run search
        /*example: https://maps.googleapis.com/maps/api/place/nearbysearch/json
            ?keyword=cruise
            &location=-33.8670522%2C151.1957362
            &radius=1500
            &type=restaurant
            &key=YOUR_API_KEY*/
        $query = [
            'location' => $locationLatitude === false ? '' : $locationLatitude . ',' . $locationLongitude,
            'radius' => empty($radius) ? '' : (int) $radius,
            'type' => empty($placeType) ? '' : trim($placeType),
            'keyword' => empty($keyword) ? '' : trim($keyword),
            'language' => empty($language) ? 'en' : $language,
            'rank_by' => empty($radius) ? 'distance' : 'prominence',
        ];
        $results = self::getResult('nearbysearch', $query, 'results');
        if (empty($results['error'])) {
            self::filterResults($results, $query['language']);
        }
        return $results;
    }

    public static function textSearch($text, $locationLatitude = false, $locationLongitude = false, $language = '')
    {
        $params = [
            "query" => $text,
            'language' => $language ? $language : 'en',
            'location' => $locationLatitude === false ? '' : $locationLatitude . ',' . $locationLongitude,
        ];
        $results = self::getResult('textsearch', $params, 'results');
        if (empty($results['error'])) {
            self::filterResults($results, $params['language']);
        }
        return $results;
    }
    /* response example: {
    html_attributions:[],
    next_page_token:'Aap_uEBG....tEFCjS3qVPErs',
    results:[
      {
        business_status:'OPERATIONAL', //'CLOSED_TEMPORARILY',
        permanently_closed:true/false,
        formatted_address:'Carretera La Romana - Higuey Hwy, La Romana 22000, Dominican Republic',
        geometry:{location:{lat:18.4141499, lng:-68.935294}, viewport:...},
        name:'Casa de Campo Resort and Villas',
        opening_hours:{open_now:true},
        photos:...,
        place_id:'ChIJS1...b3KH8',
        rating:4.7,
        types:['tourist_attraction', 'lodging', 'point_of_interest', 'establishment',
          //'gym', 'health', 'clothing_store', 'store', "bar", "cafe", "restaurant", "food",
        ],
        user_ratings_total:10396,
      },
      ....*/

    /* Since Google limits you to 20 businesses per request, you’ll need to get access
     * to more results. Check the response for “next_page_token” and if it’s set, you
     * can request the next page of results (3 times in total, 60 total businesses).*/
    private static function getNextPage($method, $token)
    {
        $supportedMethods = ['textsearch', 'nearbysearch'];
        if (!in_array($method, $supportedMethods)) {
            return ['error' => 'wrong method name'];
        }
        if (empty($token) || !is_string($token)) {
            return ['error' => 'wrong page token'];
        }
        $results = self::getResult($method, ["pagetoken" => $token], 'results');
        if (empty($results['error'])) {
            self::filterResults($results);
        }
        return $results;
    }

    public static function getPlaceDetails($placeId, $fields = [])
    {
        if (empty($placeId) || !is_string($placeId)) {
            return ['error' => 'wrong place Id'];
        }
        if (empty($fields) || !is_array($fields)) {
            return ['error' => 'wrong fields list'];
        }
        // usage limits https://developers.google.com/places/web-service/usage-and-billing#other-usage-limits
        return self::getResult('details', [
            self::BASIC__PLACE_ID => $placeId,
            "fields" => implode(',', $fields), //',' --> %2C
        ]);
    }
    /* response example:
  array (
    html_attributions:[],
    result:{
      business_status:'OPERATIONAL',
      formatted_address:'C3CJ+662, La Romana 22000, Dominican Republic',
      formatted_phone_number:'(866) 818-4966',
      international_phone_number:'+1 866-818-4966',
      geometry:{
        location:{lat:18.4205246, lng:-68.9194801},
        viewport:{
            northeast:{lat:18.4218999802915, lng:-68.9181265197085},
            southwest:{lat:18.4192020197085, lng:-68.9208244802915}
        },
      },
      icon:'https://maps.gstatic.com/mapfiles/place_api/icons/v1/png_71/generic_business-71.png',
      icon_background_color:'#7B9EB0',
      icon_mask_base_uri:'https://maps.gstatic.com/mapfiles/place_api/icons/v2/generic_pinlet',
      name:'Casa de Campo Tennis Center',
      photos:[
        {
          height:3096, width:4128,
          html_attributions:['<a href="https://maps.google.com/maps/contrib/105557800608881034647">Dylan play divertido</a>'],
          photo_reference:'Aap_uEBM9yw....mSFcJdvWJCCMX',

        ],
        ....
      ],
      place_id:'ChIJi...rNw',
      rating:4.6,
      reference:'ChIJi...KIrNw',
      reviews:...,
      types:['point_of_interest', 'establishment'],
      url:'https://maps.google.com/?cid=159...349',
      user_ratings_total:59,
      utc_offset:-240,
      vicinity:'C3CJ+662, La Romana',
      address_components:[...
        {
          long_name:'La Romana',
          short_name:'La Romana',
          types:['administrative_area_level_2', 'political'],
        },
        ...
      ],
      adr_address:'C3CJ+662, <span class="locality">La Romana</span>...',

      opening_hours:{
        open_now:true,
        periods:[
          {open:{day:0, time:'0000'}}
        ],
        weekday_text:[
          'Monday: Open 24 hours',
          'Tuesday: Open 24 hours',
          'Wednesday: Open 24 hours',
          'Thursday: Open 24 hours',
          'Friday: Open 24 hours',
          'Saturday: Open 24 hours',
          'Sunday: Open 24 hours',
        ],
      },
    ),
    status:'OK',
  ),*/
}