<?php
require_once 'Net/DNS.php';

class Net_DNS_ResolverTest extends PHPUnit_Framework_TestCase {

    public function testBug16501() {
        $resolver = new Net_DNS_Resolver(array('nameservers' => array('192.168.0.1')));

        $packet = new Net_DNS_Packet();

        $packet->header = new Net_DNS_Header();
        $packet->header->id = $resolver->nextid();
        $packet->header->qr = 0;
        $packet->header->opcode = "UPDATE";

        $packet->question[0] = new Net_DNS_Question('example.com', 'SOA', 'IN');
        $packet->answer = array();

        $packet->authority[0] = Net_DNS_RR::factory('example.com. 0 ANY A');
        $packet->authority[1] = Net_DNS_RR::factory('example.com. 1800 IN A 192.168.0.2');

        $tsig = Net_DNS_RR::factory('example-key TSIG 6i7jUkH1LXDnMKc7ElBKXQ==');
        $packet->additional = array($tsig);

        $packet->header->qdcount = count($packet->question);
        $packet->header->ancount = count($packet->answer);
        $packet->header->nscount = count($packet->authority);
        $packet->header->arcount = count($packet->additional);

        $response = $resolver->send_tcp($packet, $packet->data());

        $this->assertSame("NOERROR", $response->header->rcode);
    }

    public function testBug16502() {
        $resolver = new Net_DNS_Resolver(array('nameservers' => array('192.168.0.1')));

        $packet = new Net_DNS_Packet();

        $packet->header = new Net_DNS_Header();
        $packet->header->id = $resolver->nextid();
        $packet->header->qr = 0;
        $packet->header->opcode = "UPDATE";

        $packet->question[0] = new Net_DNS_Question('example.com', 'SOA', 'IN');
        $packet->answer = array();
        $packet->authority[0] = Net_DNS_RR::factory('example.com. 0 ANY A');
        $packet->authority[1] = Net_DNS_RR::factory('example.com. 1800 IN A 192.168.0.2');
        $tsig = Net_DNS_RR::factory('example-key TSIG 6i7jUkH1LXDnMKc7ElBKXQ==');
        $packet->additional = array($tsig);
        $packet->header->qdcount = count($packet->question);
        $packet->header->ancount = count($packet->answer);
        $packet->header->nscount = count($packet->authority);
        $packet->header->arcount = count($packet->additional);
        $response = $resolver->send_tcp($packet, $packet->data());

        $this->assertSame("NOERROR", $response->header->rcode);
    }

    public function testBug16515() {
        $r = new Net_DNS_Resolver();

        $data = $r->query('example.com.', 'TXT');
        $this->assertNotSame(false, $data);


        $this->assertTrue(is_array($data->answer), "Expected an array, found " . gettype($data->answer) . "\n" . print_r($data->answer, true));

        $txt_rr = reset($data->answer);

        $this->assertSame('example.com. 3600 IN TXT "x" "y" "z"', $txt_rr->string());
        $this->assertSame('xyz', $txt_rr->rr_rdata(0, 0));
    }
}
