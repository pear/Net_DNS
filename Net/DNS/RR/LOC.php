<?php
/**
*  License Information:
*
*  Net_DNS:  A resolver library for PHP
*  Copyright (c) 2002-2003 Eric Kilfoil eric@ypass.net
*  Maintainers:
*  Marco Kaiser <bate@php.net>
*  Florian Anderiasch <fa@php.net>
*  Ian Pye <ianbara@imap.cc>
*
* PHP versions 4 and 5
*
* LICENSE: This source file is subject to version 3.01 of the PHP license
* that is available through the world-wide-web at the following URI:
* http://www.php.net/license/3_01.txt.  If you did not receive a copy of
* the PHP License and are unable to obtain it through the web, please
* send a note to license@php.net so we can mail you a copy immediately.
*
* A representation of a resource record of type <b>LOC</b>
*
* @category Networking
* @package  Net_DNS 
* @author   Ian Pye <ianbara@imap.cc>
* @license  3.01 PHP license
* @link     http://pear.php.net/package/Net_DNS
*/

class Net_DNS_RR_LOC extends Net_DNS_RR
{
    /* class variable definitions {{{ */

    // Static constants
    // Reference altitude in centimeters (see RFC 1876).
    public static $reference_alt     = 10000000;
    // Reference lat/lon (see RFC 1876).
    public static $reference_latlon  = 2147483648; // pow(2, 31);
    
    // Conversions to/from thousandths of a degree.
    public static $conv_sec          = 1000; // 1000 milisecs.
    public static $conv_min          = 60000; // sec * 60 
    public static $conv_deg          = 3600000; // min * 60

    // Defaults (from RFC 1876, Section 3).
    public static $default_min       = 0;
    public static $default_sec       = 0;
    public static $default_size      = 1;
    public static $default_horiz_pre = 10000;
    public static $default_vert_pre  = 10;

    var $data; // Contains packed binary data in the LOC format. 
    var $offset; // Offset to start reading the data.

    // Variables read directs from the raw data.
    var $raw_latitude;
    var $raw_longitude;
    var $raw_alt;
    var $size;
    var $hp;
    var $vp;

    // Variables set by parsing the raw data.
    var $altitude;
    var $degree_latitude;
    var $degree_longitude;
    var $min_latitude;
    var $min_longitude;
    var $sec_latitude;
    var $sec_longitude;
    var $ns_hem;
    var $ew_hem;
        
    // Complete string representation of the data.
    var $pretty_print_string;
        
    // Has the raw data been parsed yet?
    var $parsed;

    // What version of the protocol are we using?
    var $version;

    /* }}} */
    /**
     * class constructor - RR(&$rro, $data, $offset = '')
     *
     * Usage:
     * $rr = new Net_DNS_RR_LOC($rro, $data, $offset);
     * $rr->parse();
     * {{{ 
     */
    function Net_DNS_RR_LOC($rro, $data, $offset = 0)
    {      
        // Keep all of the common fields.
        $this->name = $rro->name;
        $this->type = $rro->type;
        $this->class = $rro->class;
        $this->ttl = $rro->ttl;
        $this->rdlength = $rro->rdlength;
        $this->rdata = $rro->rdata;
        
        // And keep the actual data.
        $this->data = $data;
        $this->offset = $offset;
    }

    /** 
     *  Net_DNS_RR_LOC::parse()
     *  Parses the $data field. 
     */
    function parse() {
        if (isset($this->offset) && isset($this->data) && !($this->parsed)) {
            if ($this->rdlength > 0) {
                $off = $this->offset;
                
                $a = unpack(
                    "@$off/Cversion/Csize/Choriz_pre/Cvert_pre/Nlat/Nlong/Nalt", 
                    $this->data
                );

                $this->version = $a['version'];
                $this->size = $this->precsize_ntoval($a['size']);
                $this->hp = $this->precsize_ntoval($a['horiz_pre']);
                $this->vp = $this->precsize_ntoval($a['vert_pre']);

                // If these are all 0, use the defaults.
                if (!$this->size) {
                    $this->size = static::$default_size;
                }
                    
                if (!$this->hp) {
                    $this->hp = static::$default_horiz_pre;
                }
                 
                if (!$this->vp) {
                    $this->vp = static::$default_vert_pre;
                }

                $this->raw_latitude = $a['lat'];
                $this->raw_longitude = $a['long'];
                $this->raw_alt = $a['alt'];
                $this->altitude = ($this->raw_alt - static::$reference_alt) / 100;
                
                $this->pretty_print_string = 
                    $this->latlon2dms($this->raw_latitude, "NS", true) . ' ' .
                    $this->latlon2dms($this->raw_longitude, "EW", false) . ' ' .
                    $this->altitude . 'm ' .
                    $this->size . 'm ' .
                    $this->hp . 'm ' .
                    $this->vp . 'm';
                
                $this->parsed = true;
            }
        }
    }
    
    /* }}} */
    /* Net_DNS_RR_LOC::rdatastr() {{{ */
    function rdatastr()
    {
        if (!$this->parsed) {
            $this->parse_data();
        }
        
        if ($this->pretty_print_string) {
            return $this->pretty_print_string;
        }
        
        return '; no data';
    }

    /* }}} */
    /* Net_DNS_RR_LOC::rr_rdata($packet, $offset) {{{ */
    function rr_rdata($packet, $offset)
    {    
        if (!$this->parsed) {
            $this->parse_data();
        }
 
        $rdata = "";
        if (isset($this->version)) {
            $rdata .= pack("C", $this->version);
            if ($this->version == 0) {
                $rdata .= pack(
                    "C3", $this->precsize_valton($this->size),
                    $this->precsize_valton($this->hp),
                    $this->precsize_valton($this->vp)
                );
                $rdata .= pack(
                    "N3", 
                    $this->raw_latitude,
                    $this->raw_longitude,
                    $this->raw_alt
                );
            } else {
                // We don't know how to handle other versions.
            }
        }
        return $rdata;
    }

    /* }}} */
    /* Net_DNS_RR_LOC::precsize_ntoval($prec) {{{ */
    function precsize_ntoval($prec) 
    {
        $mantissa = (($prec >> 4) & 0x0f) % 10;
        $exponent = ($prec & 0x0f) % 10;
        return $mantissa * $poweroften[$exponent];
    }

    /* }}} */
    /* Net_DNS_RR_LOC::precsize_valton($val) {{{ */
    function precsize_valton($val) 
    {
        $exponent = 0;
        while ($val >= 10) {
            $val /= 10;
            ++$exponent;
        }
        return (intval($val) << 4) | ($exponent & 0x0f);
    }
    
    /* }}}
     * Now with added side effects, setting values for the class,
     * while returning a formatted string.
     * Net_DNS_RR_LOC::latlon2dms($rawmsec, $hems, $is_lat) {{{ 
     */
    function latlon2dms($rawmsec, $hems, $is_lat = false) 
    {   
        // Adjust for hemisphere problems (E and N can have negative values, 
        // which need to be corrected for).
        $flipped = false;
        if ($rawmsec < 0) {
            $rawmsec = -1 * $rawmsec;
            $flipped = true;
        }
     
        $abs = abs($rawmsec - static::$reference_latlon);
        $deg = intval($abs / static::$conv_deg);
        $abs  -= $deg * static::$conv_deg;
        $min  = intval($abs / static::$conv_min); 
        $abs -= $min * static::$conv_min;
        $sec  = intval($abs / static::$conv_sec);
        $abs -= $sec * static::$conv_sec;
        $msec = $abs;
        $hem = substr($hems, (($rawmsec >= static::$reference_latlon) ? 0 : 1), 1);
        if ($flipped) {
            $hem = substr($hems, (($rawmsec >= static::$reference_latlon) ? 1 : 0), 1);
        }

        // Save the results.
        if ($is_lat) {
            $this->degree_latitude = $deg;
            $this->min_latitude = $min;
            $this->sec_latitude = $sec;
            $this->ns_hem = $hem;
        } else {
            $this->degree_longitude = $deg;
            $this->min_longitude = $min;
            $this->sec_longitude = $sec;
            $this->ew_hem = $hem;
        }
        
        return sprintf("%d %02d %02d.%03d %s", $deg, $min, $sec, $msec, $hem);
    }

    /* }}} */
}
/* }}} */
/* VIM settings {{{
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * soft-stop-width: 4
 * c indent on
 * End:
 * vim600: sw=4 ts=4 sts=4 cindent fdm=marker et
 * vim<600: sw=4 ts=4
 * }}} */
?>
