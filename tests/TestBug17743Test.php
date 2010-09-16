<?php
require_once "Net/DNS.php";
require_once "PHPUnit/Framework/TestCase.php";

/** */
class TestBug17743 extends PHPUnit_Framework_TestCase {
	public function testNonStaticContext() {
		$resolver = new Net_DNS_Resolver();
		$host = '127.0.0.1';
		if ($response = $resolver->query($host, 'PTR')) {
			foreach ($response->answer as $val) {
				if (isset($val->ptrdname)) {
					$ptrdname = $val->ptrdname; break;
				} 
			} 
		} 
	} 
}