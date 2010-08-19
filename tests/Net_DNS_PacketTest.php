<?php
require_once 'Net/DNS.php';

class Net_DNS_PacketTest extends PHPUnit_Framework_TestCase {
    
    protected $packet;

    public function setUp() {
        $this->packet = new Net_DNS_Packet();
    }

    public function testDnExpand1() {
        $this->assertSame(array(null, null), $this->packet->dn_expand(null, null));
    }

    public function testDnExpand2() {
        $this->assertSame(array(null, null), $this->packet->dn_expand("onetwothree", -1234));
    }

    public function testDnExpand3() {
        $this->markTestIncomplete("Cover http://pear.php.net/bugs/bug.php?id=17743");   
    }


}
