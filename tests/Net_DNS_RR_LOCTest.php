<?php
require_once 'Net/DNS.php';

class Net_DNS_RR_LOCTest extends PHPUnit_Framework_TestCase {
 

    public function testShouldSetUpInitialState() {
        $this->markTestIncomplete('function Net_DNS_RR_LOC($rro, $data, $offset = 0)');
    }

    public function testShouldParse() {
        $this->markTestIncomplete('parse');
    }

    public function testShouldFormatDataCorrectly() {
        $this->markTestIncomplete('rdatastr');
    }

    public function testShouldDoSomethingWithRRData() {
        $this->markTestIncomplete('rr_rdata');
    }

    public function testShouldReturnCorrectNtovalFigures() {
        $this->markTestIncomplete('precsize_ntoval');
    }

    public function testShouldReturnCorrectValtonFigures() {
        $this->markTestIncomplete('precsize_valton');
    }

    public function testShouldReturnACorrectlyFormattedLatDonDmsString() {
        $this->markTestIncomplete('latlon2dms');
    }

}
