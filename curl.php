<?php

namespace SoulOfMachines\PHPUtils;

class CURL {
    protected $opt = array();
    protected $info = null;
    protected $header = null;
    protected $header_mb = false;
    protected $constants = array();

    public function __construct($opt = array()) {
        $constants = get_defined_constants(true);
        foreach($constants['curl'] as $i => $e) {
            if(strpos($i, 'CURLOPT_') === 0 || $i === 'CURLINFO_HEADER_OUT') {
                $this->constants[$i] = $e;
            }
        }
        $this->setOpt(array(
            'returntransfer'    => true,
            'followlocation'    => true,
            'connecttimeout'    => 10,
            'maxredirs'         => 5,
            'timeout'           => 60,
        ));
        $this->header_mb = (ini_get('mbstring.func_overload')>0);
    }

    protected function headerFunction($curl, $header) {
        $result = trim($header);
        if($result) $this->header[] = $result;
        return ($this->header_mb?mb_strlen($header, '8bit'):strlen($header));
    }

    protected function getConstantName($name) {
        $result = strtoupper($name);
        if(!(strpos($result, 'CURLOPT_') === 0 || $result === 'CURLINFO_HEADER_OUT')) {
            if($result === 'HEADER_OUT') $result = 'CURLINFO_HEADER_OUT';
            else $result = 'CURLOPT_'.$result;
        }
        return $result;
    }

    public function setOpt($opt, $val = null) {
        $result = true;
        if(!is_array($opt)) $opt = array($opt => $val);
        foreach($opt as $i => $e) {
            $i = $this->getConstantName($i);
            if(isset($this->constants[$i])) $this->opt[$i] = $e;
            else $result = false;
        }
        return $result;
    }

    public function __get($name) {
        switch($name) {
            case 'opt':
            case 'info':
            case 'header':
            case 'constants':
                return $this->{$name};
            break;
            default:
            break;
        }
        return null;
    }

    public function exec($url, $opt = array()) {
        $this->header = array();
        $curl = curl_init();
        $setopt = array();
        foreach($this->opt as $i => $e) {
            $setopt[constant($i)] = $e;
        }
        foreach($opt as $i => $e) {
            $i = $this->getConstantName($i);
            if(isset($this->constants[$i])) $setopt[constant($i)] = $e;
        }
        $setopt[CURLOPT_URL] = $url;
        $setopt[CURLOPT_HEADERFUNCTION] = array($this, 'headerFunction');
        if(!curl_setopt_array($curl, $setopt)) {
            foreach($setopt as $i => $e) {
                if(!curl_setopt($curl, $i, $e)) {
                    $exception = array_search($i, $this->constants, true);
                    if($exception === false) $exception = 'Unknown constant value '.$i;
                    throw new CURL\Exception($exception);
                }
            }
        }
        $result = curl_exec($curl);
        $this->info = curl_getinfo($curl);
        curl_close($curl);
        return $result;
    }
}

namespace SoulOfMachines\PHPUtils\CURL;

class Exception extends \Exception {
}
