<?php

namespace WhichBrowser\Analyser\Header\Useragent\Device;

use WhichBrowser\Constants;
use WhichBrowser\Data;
use WhichBrowser\Model\Version;

trait Mobile
{
    private function detectMobile($ua)
    {
        /* Detect the type based on some common markers */
        $this->detectGenericMobile($ua);

        /* Look for specific manufacturers and models */
        $this->detectKin($ua);
        $this->detectNokia($ua);
        $this->detectSamsung($ua);

        /* Try to parse some generic methods to store device information */
        $this->detectGenericMobileModels($ua);

        /* Try to find the model names based on id */
        $this->detectGenericMobileLocations($ua);
    }






    /* Generic markers */

    private function detectGenericMobile($ua)
    {
        if (preg_match('/MIDP/u', $ua)) {
            $this->data->device->type = Constants\DeviceType::MOBILE;
        }
    }


    /* Microsoft KIN */

    private function detectKin($ua)
    {
        if (preg_match('/KIN\.(One|Two) ([0-9.]*)/ui', $ua, $match)) {
            $this->data->os->name = 'Kin OS';
            $this->data->os->version = new Version([ 'value' => $match[2], 'details' => 2 ]);

            switch ($match[1]) {
                case 'One':
                    $this->data->device->manufacturer = 'Microsoft';
                    $this->data->device->model = 'Kin ONE';
                    $this->data->device->identified |= Constants\Id::MATCH_UA;
                    $this->data->device->generic = false;
                    break;

                case 'Two':
                    $this->data->device->manufacturer = 'Microsoft';
                    $this->data->device->model = 'Kin TWO';
                    $this->data->device->identified |= Constants\Id::MATCH_UA;
                    $this->data->device->generic = false;
                    break;
            }
        }
    }


    /* Nokia */

    private function detectNokia($ua)
    {
        if (isset($this->data->device->manufacturer)) {
            return;
        }

        if (preg_match('/Nokia-?([^\/\)]+)/ui', $ua, $match)) {

            if ($match[1] == 'Browser') {
                return;
            }

            $this->data->device->manufacturer = 'Nokia';
            $this->data->device->model = Data\DeviceModels::cleanup($match[1]);
            $this->data->device->identifier = $match[0];
            $this->data->device->identified |= Constants\Id::PATTERN;
            $this->data->device->generic = false;
            $this->data->device->type = Constants\DeviceType::MOBILE;

                $device = Data\DeviceModels::identify('s60', $this->data->device->model);
            if (!($this->data->device->identified & Constants\Id::MATCH_UA)) {
                if ($device->identified) {
                    $device->identified |= $this->data->device->identified;
                    $this->data->device = $device;

                    if (!isset($this->data->os->name) || $this->data->os->name != 'Series60') {
                        $this->data->os->name = 'Series60';
                        $this->data->os->version = null;
                    }
                }
            }

            if (!($this->data->device->identified & Constants\Id::MATCH_UA)) {
                $device = Data\DeviceModels::identify('s40', $this->data->device->model);
                if ($device->identified) {
                    $device->identified |= $this->data->device->identified;
                    $this->data->device = $device;

                    if (!isset($this->data->os->name) || $this->data->os->name != 'Series40') {
                        $this->data->os->name = 'Series40';
                        $this->data->os->version = null;
                    }
                }
            }

                $device = Data\DeviceModels::identify('asha', $this->data->device->model);
            if (!($this->data->device->identified & Constants\Id::MATCH_UA)) {
                if ($device->identified) {
                    $device->identified |= $this->data->device->identified;
                    $this->data->device = $device;

                    if (!isset($this->data->os->name) || $this->data->os->name != 'Nokia Asha Platform') {
                        $this->data->os->name = 'Nokia Asha Platform';
                        $this->data->os->version = new Version([ 'value' => '1.0' ]);

                        if (preg_match('/java_runtime_version=Nokia_Asha_([0-9_]+)[;\)]/u', $ua, $match)) {
                            $this->data->os->version = new Version([ 'value' => str_replace('_', '.', $match[1]) ]);
                        }
                    }
                }
            }

            $this->identifyBasedOnIdentifier();
        }
    }


    /* Samsung */

    private function detectSamsung($ua)
    {
        if (isset($this->data->device->manufacturer)) {
            return;
        }

        if (preg_match('/SAMSUNG[-\/ ]?([^\/\)_]+)/ui', $ua, $match)) {
            $this->data->device->manufacturer = 'Samsung';
            $this->data->device->model = Data\DeviceModels::cleanup($match[1]);
            $this->data->device->identifier = $match[0];
            $this->data->device->identified |= Constants\Id::PATTERN;
            $this->data->device->generic = false;
            $this->data->device->type = Constants\DeviceType::MOBILE;

            if ($this->data->isOS('Bada')) {
                $device = Data\DeviceModels::identify('bada', $this->data->device->model);
                if ($device->identified) {
                    $device->identified |= $this->data->device->identified;
                    $this->data->device = $device;
                }
            }

            if ($this->data->isOS('Series60')) {
                $device = Data\DeviceModels::identify('s60', $this->data->device->model);
                if ($device->identified) {
                    $device->identified |= $this->data->device->identified;
                    $this->data->device = $device;
                }
            }

            if (!$this->data->os->isDetected()) {
                if (preg_match('/Jasmine\/([0-9.]*)/u', $ua, $match)) {
                    $version = $match[1];

                    $device = Data\DeviceModels::identify('touchwiz', $this->data->device->model);
                    if ($device->identified) {
                        $device->identified |= $this->data->device->identified;
                        $this->data->device = $device;
                        $this->data->os->name = 'Touchwiz';

                        switch ($version) {
                            case '0.8':
                                $this->data->os->version = new Version([ 'value' => '1.0' ]);
                                break;
                            case '1.0':
                                $this->data->os->version = new Version([ 'value' => '2.0', 'alias' => '2.0 or earlier' ]);
                                break;
                        }
                    }
                }

                if (preg_match('/(?:Dolfin\/([0-9.]*)|Browser\/Dolfin([0-9.]*))/u', $ua, $match)) {
                    $version = $match[1] || $match[2];

                    $device = Data\DeviceModels::identify('bada', $this->data->device->model);
                    if ($device->identified) {
                        $device->identified |= $this->data->device->identified;
                        $this->data->device = $device;
                        $this->data->os->name = 'Bada';

                        switch ($version) {
                            case '2.0':
                                $this->data->os->version = new Version([ 'value' => '1.0' ]);
                                break;
                            case '2.2':
                                $this->data->os->version = new Version([ 'value' => '1.2' ]);
                                break;
                            case '3.0':
                                $this->data->os->version = new Version([ 'value' => '2.0' ]);
                                break;
                        }
                    } else {
                        $device = Data\DeviceModels::identify('touchwiz', $this->data->device->model);
                        if ($device->identified) {
                            $device->identified |= $this->data->device->identified;
                            $this->data->device = $device;
                            $this->data->os->name = 'Touchwiz';

                            switch ($version) {
                                case '1.5':
                                    $this->data->os->version = new Version([ 'value' => '2.0' ]);
                                    break;
                                case '2.0':
                                    $this->data->os->version = new Version([ 'value' => '3.0' ]);
                                    break;
                            }
                        }
                    }
                }
            }

            $this->identifyBasedOnIdentifier();
        }
    }


    /* Generic models */

    private function detectGenericMobileModels($ua)
    {
        if (isset($this->data->device->manufacturer)) {
            return;
        }

        $this->data->device->identifyModel('/\(([A-Z]+[0-9]+[A-Z])[^;]*; ?FOMA/ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'DoCoMo'
        ]);

        $this->data->device->identifyModel('/DoCoMo\/[0-9].0[\/\s]([0-9A-Z]+)/ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'DoCoMo'
        ]);

        $this->data->device->identifyModel('/Vodafone\/[0-9.]+\/V([0-9]+[A-Z]+)[^\/]*\//ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'Vodafone'
        ]);

        $this->data->device->identifyModel('/J-PHONE\/[^\/]+\/([^\/_]+)/u', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'Softbank'
        ]);

        $this->data->device->identifyModel('/SoftBank\/[^\/]+\/([^\/]+)\//u', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'Softbank'
        ]);

        $this->data->device->identifyModel('/T-Mobile[_ ]([^\/;]+)/u', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'T-Mobile'
        ]);

        $this->data->device->identifyModel('/Danger hiptop ([0-9.]+)/u', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'Danger',
            'model'         => 'Hiptop'
        ]);

        $this->data->device->identifyModel('/HP(iPAQ[0-9]+)\//u', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'HP'
        ]);

        $this->data->device->identifyModel('/Acer_?([^\/_]*)/ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'Acer'
        ]);

        $this->data->device->identifyModel('/AIRNESS-([^\/]*)/ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'Airness'
        ]);

        $this->data->device->identifyModel('/BenQ-([^\/]*)/ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'BenQ'
        ]);

        $this->data->device->identifyModel('/ALCATEL[_-]([^\/]*)/ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'Alcatel',
            'model'         => function ($model) {
                if (preg_match('/^TRIBE ([^\s]+)/ui', $model, $match)) {
                    $model = 'One Touch Tribe ' . $match[1];
                } elseif (preg_match('/^ONE TOUCH ([^\s]*)/ui', $model, $match)) {
                    $model = 'One Touch ' . $match[1];
                } elseif (preg_match('/^OT[-\s]*([^\s]*)/ui', $model, $match)) {
                    $model = 'One Touch ' . $match[1];
                }

                return $model;
            }
        ]);

        $this->data->device->identifyModel('/Bird[ _]([^\/]*)/ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'Bird'
        ]);

        $this->data->device->identifyModel('/(?:YL-|YuLong-)?COOLPAD([^\s]+)/ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'Coolpad'
        ]);

        $this->data->device->identifyModel('/CELKON\.([^\s]+)/ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'Celkon'
        ]);

        $this->data->device->identifyModel('/Coship ([^\s]+)/ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'Coship'
        ]);

        $this->data->device->identifyModel('/Cricket-([^\s]+)/ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'Cricket'
        ]);

        $this->data->device->identifyModel('/DESAY[ _]([^\s]+)/ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'DESAY'
        ]);

        $this->data->device->identifyModel('/Diamond_([^\s]+)/ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'Diamond'
        ]);

        $this->data->device->identifyModel('/dopod[-_]?([^\s]+)/ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'Dopod'
        ]);

        $this->data->device->identifyModel('/FLY_]?([^\s\/]+)/ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'Fly'
        ]);

        $this->data->device->identifyModel('/GIONEE[-_ ]([^\s\/;]+)/ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'Gionee'
        ]);

        $this->data->device->identifyModel('/GIONEE([A-Z0-9]+)/ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'Gionee'
        ]);

        $this->data->device->identifyModel('/HIKe_([^\s]+)/ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'HIKe'
        ]);

        $this->data->device->identifyModel('/Hisense[ -](?:HS-)?([^\s]+)/ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'Hisense'
        ]);

        $this->data->device->identifyModel('/HS-([^\s]+)/ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'Hisense'
        ]);

        $this->data->device->identifyModel('/HTC[\s_-]?([^\/\);]+)/ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'HTC'
        ]);

        $this->data->device->identifyModel('/(?:HTC_)?([A-Z0-9_]+_T[0-9]{4,4})/ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'HTC'
        ]);

        $this->data->device->identifyModel('/HUAWEI[\s_-]?([^\/\)]*)/ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'Huawei'
        ]);

        $this->data->device->identifyModel('/Huawei\/1.0\/0?(?:Huawei)?([^\/]+)\//ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'Huawei'
        ]);

        $this->data->device->identifyModel('/Karbonn ([^\s]+)/ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'Karbonn'
        ]);

        $this->data->device->identifyModel('/KDDI-([^\s\);]+)/ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'KDDI'
        ]);

        $this->data->device->identifyModel('/KYOCERA\/([^\s\/]+)/ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'Kyocera'
        ]);

        $this->data->device->identifyModel('/KONKA[-_]?([^\s]+)/ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'Konka'
        ]);

        $this->data->device->identifyModel('/TIANYU-KTOUCH\/([^\/]+)/ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'K-Touch'
        ]);

        $this->data->device->identifyModel('/K-Touch_?([^\/]*)/ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'K-Touch'
        ]);

        $this->data->device->identifyModel('/Lenovo[_-]?([^\/]*)/ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'Lenovo'
        ]);

        $this->data->device->identifyModel('/Lephone_([^\/]*)/ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'Lephone'
        ]);

        $this->data->device->identifyModel('/LGE?(?:\/|-|_)([^\s\)\-]+)/ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'LG'
        ]);

        $this->data->device->identifyModel('/LGE? ([A-Z]+[0-9]+)/u', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'LG'
        ]);

        $this->data->device->identifyModel('/Micromax([^\)]+)/ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'Micromax'
        ]);

        $this->data->device->identifyModel('/MOTO([^\/_]+)/u', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'Motorola'
        ]);

        $this->data->device->identifyModel('/MOT-([^\/_]+)/u', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'Motorola'
        ]);

        $this->data->device->identifyModel('/Motorola_([^\/_]+)/ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'Motorola'
        ]);

        $this->data->device->identifyModel('/Nexian([^\/_]+)/ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'Nexian'
        ]);

        $this->data->device->identifyModel('/NGM_([^\/_]+)/ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'NGM'
        ]);

        $this->data->device->identifyModel('/OPPO_([^\/_]+)/ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'Oppo'
        ]);

        $this->data->device->identifyModel('/Pantech-?([^\/_]+)/ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'Pantech'
        ]);

        $this->data->device->identifyModel('/Philips([A-Z][0-9]+)/u', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'Philips'
        ]);

        $this->data->device->identifyModel('/sam-([A-Z][0-9]+)/ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'Samsung'
        ]);

        $this->data->device->identifyModel('/SANYO\/([^\/]+)/ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'Sanyo'
        ]);

        $this->data->device->identifyModel('/(SH[0-9]+[A-Z])/u', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'Sharp'
        ]);

        $this->data->device->identifyModel('/SE([A-Z][0-9]+[a-z])/u', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'Sony Ericsson'
        ]);

        $this->data->device->identifyModel('/SonyEricsson([^\/\)]+)/iu', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'Sony Ericsson',
            'model'         => function ($model) {
                if (preg_match('/^([A-Z]) ([0-9]+)$/u', $model, $match)) {
                    $model = $match[1] . $match[2];
                }

                if (preg_match('/^[a-z][0-9]+/u', $model)) {
                    $model[0] = strtoupper($model[0]);
                }

                return $model;
            }
        ]);

        $this->data->device->identifyModel('/SHARP[-_\/]([^\/\;]*)/u', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'Sharp'
        ]);

        $this->data->device->identifyModel('/Spice\s([^\s]+)/ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'Spice'
        ]);

        $this->data->device->identifyModel('/Spice\s?([A-Z][0-9]+)/ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'Spice'
        ]);

        $this->data->device->identifyModel('/Tecno([^\/]*)/ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'Tecno'
        ]);

        $this->data->device->identifyModel('/T-smart_([^\/]*)/ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'T-smart'
        ]);

        $this->data->device->identifyModel('/TCL[-_ ]([^\/\;\)]*)/ui', $ua, [
            'manufacturer'  => 'TCL'
        ]);

        $this->data->device->identifyModel('/Tiphone ([^\/]*)/ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'TiPhone'
        ]);

        $this->data->device->identifyModel('/Toshiba[\/-]([^\/-]*)/ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'Toshiba'
        ]);

        $this->data->device->identifyModel('/UTStar-([^\s\.]+)/u', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'UTStarcom'
        ]);


        $this->data->device->identifyModel('/vk-(vk[0-9]+)/u', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'VK Mobile',
            'model'         => function ($model) {
                return strtoupper($model);
            }
        ]);

        $this->data->device->identifyModel('/Xiaomi[_]?([^\s]+)/ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'Xiaomi'
        ]);


        $this->data->device->identifyModel('/ZTE[-_\s]?([^\s\/]+)/ui', $ua, [
            'type'          => Constants\DeviceType::MOBILE,
            'manufacturer'  => 'ZTE'
        ]);

        $this->identifyBasedOnIdentifier();
    }


    /* Device models not identified by a prefix */

    private function detectGenericMobileLocations($ua)
    {
        if ($this->data->device->identified & Constants\Id::PATTERN) {
            return;
        }

        $candidates = [];

        if (preg_match('/Windows NT 5.1; ([^;]+); Windows Phone/u', $ua, $match)) {
            array_push($candidates, $match[1]);
        }

        if (preg_match('/Windows Mobile; ([^;]+); PPC;/u', $ua, $match)) {
            array_push($candidates, $match[1]);
        }

        if (preg_match('/\(([^;]+); U; Windows Mobile/u', $ua, $match)) {
            array_push($candidates, $match[1]);
        }

        if (preg_match('/MSIEMobile [0-9.]+\) ([^\s]+)/u', $ua, $match)) {
            array_push($candidates, $match[1]);
        }

        if (preg_match('/^([a-z0-9\.\_\+\/ ]+)_TD\//iu', $ua, $match)) {
            array_push($candidates, $match[1]);
        }

        if (preg_match('/\ ([^\s\)\/]+)[^\s]*$/u', $ua, $match)) {
            array_push($candidates, $match[1]);
        }

        if (preg_match('/^([^\/\)]+)/u', $ua, $match)) {
            array_push($candidates, $match[1]);
        }

        $candidates = array_diff($candidates, [
            'Mobile', 'Safari', 'Version', 'GoogleTV', 'WebKit', 'NetFront', 
            'Microsoft', 'ZuneWP7', 'Firefox', 'UCBrowser', 'IEMobile', 'Touch',
            'Fennec', 'Minimo', 'Gecko', 'TizenBrowser', 'Browser', 'sdk', 
            'Mini', 'Fennec', 'Darwin', 'Puffin', 'Tanggula', 'Edge',
            'QHBrowser', 'BonEcho', 'Iceweasel', 'Midori', 'BeOS', 'UBrowser', 
            'SeaMonkey', 'Model', 'Silk-Accelerated=true', 'Configuration',
            'UNTRUSTED', 'OSRE', 'Dolfin', 'Surf', 'Epiphany', 'Konqueror',
            'Presto', 'OWB', 'PmWFx', 'Netscape', 'Netscape6', 'Navigator'
        ]);

        foreach ($candidates as $i => $id) {
            $this->identifyBasedOnIdUsingOs($id);

            if ($this->data->device->identified & Constants\Id::MATCH_UA) {
                return;
            }
        }

        foreach ($candidates as $i => $id) {
            $this->identifyBasedOnId($id);

            if ($this->data->device->identified & Constants\Id::MATCH_UA) {
                return;
            }
        }
    }

    function identifyBasedOnIdentifier()
    {
        if ($this->data->device->identified & Constants\Id::MATCH_UA) {
            return;
        }

        $ids = [];

        if (!empty($this->data->device->identifier)) {
            $ids[] = $this->data->device->identifier;
        }

        if (!empty($this->data->device->model)) {
            $ids[] = $this->data->device->model;
        }

        foreach ($ids as $i => $id) {
            $this->identifyBasedOnIdUsingOs($id);

            if ($this->data->device->identified & Constants\Id::MATCH_UA) {
                return;
            }
        }

        foreach ($ids as $i => $id) {
            $this->identifyBasedOnId($id);

            if ($this->data->device->identified & Constants\Id::MATCH_UA) {
                return;
            }
        }
    }

    function identifyBasedOnIdUsingOs($id)
    {
        switch ($this->data->os->getFamily()) {

            case 'Android':
                $device = Data\DeviceModels::identify('android', $id);
                if ($device->identified) {
                    $device->identified |= $this->data->device->identified;
                    $this->data->device = $device;
                }
                break;

            case 'Brew':
                $device = Data\DeviceModels::identify('brew', $id);
                if ($device->identified) {
                    $device->identified |= $this->data->device->identified;
                    $this->data->device = $device;
                }
                break;

            case 'Symbian':
                $device = Data\DeviceModels::identify('s60', $id);
                if ($device->identified) {
                    $device->identified |= $this->data->device->identified;
                    $this->data->device = $device;
                }
                break;

            case 'Windows':
            case 'Windows CE':
            case 'Windows Mobile':
                $device = Data\DeviceModels::identify('wm', $id);
                if ($device->identified) {
                    $device->identified |= $this->data->device->identified;
                    $this->data->device = $device;

                    if (!$this->data->isOs('Windows Mobile')) {
                        $this->data->os->reset([
                            'name' => 'Windows Mobile'
                        ]);
                    }
                }
                break;

            default:
                $device = Data\DeviceModels::identify('feature', $id);
                if ($device->identified) {
                    $device->identified |= $this->data->device->identified;
                    $this->data->device = $device;
                }
                break;
        }
    }

    function identifyBasedOnId($id)
    {
        if (!($this->data->device->identified & Constants\Id::MATCH_UA)) {
            $device = Data\DeviceModels::identify('brew', $id);
            if ($device->identified) {
                $device->identified |= $this->data->device->identified;
                $this->data->device = $device;
                $this->data->os->name = 'Brew';
            }
        }

        if (!($this->data->device->identified & Constants\Id::MATCH_UA)) {
            $device = Data\DeviceModels::identify('bada', $id);
            if ($device->identified) {
                $device->identified |= $this->data->device->identified;
                $this->data->device = $device;
                $this->data->os->name = 'Bada';
            }
        }

        if (!($this->data->device->identified & Constants\Id::MATCH_UA)) {
            $device = Data\DeviceModels::identify('touchwiz', $id);
            if ($device->identified) {
                $device->identified |= $this->data->device->identified;
                $this->data->device = $device;
                $this->data->os->name = 'Touchwiz';
            }
        }

        if (!($this->data->device->identified & Constants\Id::MATCH_UA)) {
            $device = Data\DeviceModels::identify('wm', $id);
            if ($device->identified) {
                $device->identified |= $this->data->device->identified;
                $this->data->device = $device;
                $this->data->os->name = 'Windows Mobile';
            }
        }

        if (!($this->data->device->identified & Constants\Id::MATCH_UA)) {
            $device = Data\DeviceModels::identify('feature', $id);
            if ($device->identified) {
                $device->identified |= $this->data->device->identified;
                $this->data->device = $device;
            }
        }
    }
}
