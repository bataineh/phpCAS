<?php
require_once dirname(__FILE__).'/../harness/DummyRequest.php';
require_once dirname(__FILE__).'/../harness/BasicResponse.php';

/**
 * Test class for verifying the operation of service tickets.
 *
 *
 * Generated by PHPUnit on 2010-09-07 at 13:33:53.
 */
class ProxyTicketValidationTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var CAS_Client
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
		$_SERVER['SERVER_NAME'] = 'www.service.com';
		$_SERVER['SERVER_PORT'] = '80';
		$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
		$_SERVER['SERVER_ADMIN'] = 'root@localhost';
		$_SERVER['REQUEST_URI'] = '/';
		$_SERVER['SCRIPT_NAME'] = '/index.php';
		$_SERVER['PHP_SELF'] = '/index.php';
		$_SESSION = array();

// 		$_GET['ticket'] = 'ST-123456-asdfasdfasgww2323radf3';

		$this->object = new CAS_Client(
			CAS_VERSION_2_0, 	// Server Version
			false, 				// Proxy
			'cas.example.edu',	// Server Hostname
			443,				// Server port
			'/cas/',			// Server URI
			false				// Start Session
		);
		
		$this->object->setRequestImplementation('CAS_TestHarness_DummyRequest');
		$this->object->setCasServerCACert('/path/to/ca_cert.crt');

		/*********************************************************
		 * Enumerate our responses
		 *********************************************************/
		// Valid ticket response
		$response = new CAS_TestHarness_BasicResponse('https', 'cas.example.edu', '/cas/proxyValidate');
		$response->matchQueryParameters(array(
			'service' => 'http://www.service.com/',
			'ticket' => 'ST-123456-asdfasdfasgww2323radf3',
		));
		$response->setResponseHeaders(array(
			'HTTP/1.1 200 OK',
			'Date: Wed, 29 Sep 2010 19:20:57 GMT',
			'Server: Apache-Coyote/1.1',
			'Pragma: no-cache',
			'Expires: Thu, 01 Jan 1970 00:00:00 GMT',
			'Cache-Control: no-cache, no-store',
			'Content-Type: text/html;charset=UTF-8',
			'Content-Language: en-US',
			'Via: 1.1 cas.example.edu',
			'Connection: close',
			'Transfer-Encoding: chunked',
		));
		$response->setResponseBody(
"<cas:serviceResponse xmlns:cas='http://www.yale.edu/tp/cas'>
    <cas:authenticationSuccess>
        <cas:user>jsmith</cas:user>
        <cas:proxies>
            <cas:proxy>http://firstproxy.com/mysite/test</cas:proxy>
            <cas:proxy>https://anotherdomain.org/mysite/test2</cas:proxy>    
        </cas:proxies>
    </cas:authenticationSuccess>
</cas:serviceResponse>");
		$response->ensureCaCertPathEquals('/path/to/ca_cert.crt');
		CAS_TestHarness_DummyRequest::addResponse($response);

		// Invalid ticket response
		$response = new CAS_TestHarness_BasicResponse('https', 'cas.example.edu', '/cas/proxyValidate');
		$response->matchQueryParameters(array(
			'service' => 'http://www.service.com/',
		));
		$response->setResponseHeaders(array(
			'HTTP/1.1 200 OK',
			'Date: Wed, 29 Sep 2010 19:20:57 GMT',
			'Server: Apache-Coyote/1.1',
			'Pragma: no-cache',
			'Expires: Thu, 01 Jan 1970 00:00:00 GMT',
			'Cache-Control: no-cache, no-store',
			'Content-Type: text/html;charset=UTF-8',
			'Content-Language: en-US',
			'Via: 1.1 cas.example.edu',
			'Connection: close',
			'Transfer-Encoding: chunked',
		));
		$response->setResponseBody(
"<cas:serviceResponse xmlns:cas='http://www.yale.edu/tp/cas'>
    <cas:authenticationFailure code='INVALID_TICKET'>
        Ticket ST-1856339-aA5Yuvrxzpv8Tau1cYQ7 not recognized
    </cas:authenticationFailure>
</cas:serviceResponse>

");
		$response->ensureCaCertPathEquals('/path/to/ca_cert.crt');
		CAS_TestHarness_DummyRequest::addResponse($response);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
		CAS_TestHarness_DummyRequest::clearResponses();
    }

    /**
     * Test that a service ticket can be successfully validated.
     */
    public function test_validation_success() {
    	$this->object->setTicket('ST-123456-asdfasdfasgww2323radf3');
    	$this->object->getAllowedProxyChains()->allowProxyChain(new CAS_ProxyChain_Any());
    	$result = $this->object->validateCAS20($url, $text_response, $tree_response);
    	$this->assertTrue($result);
    	$this->assertEquals(
"<cas:serviceResponse xmlns:cas='http://www.yale.edu/tp/cas'>
    <cas:authenticationSuccess>
        <cas:user>jsmith</cas:user>
        <cas:proxies>
            <cas:proxy>http://firstproxy.com/mysite/test</cas:proxy>
            <cas:proxy>https://anotherdomain.org/mysite/test2</cas:proxy>    
        </cas:proxies>
    </cas:authenticationSuccess>
</cas:serviceResponse>"
    	, $text_response);
    	$this->assertInstanceOf('DOMElement', $tree_response);
    }

	/**
     * Test that a service ticket can be successfully fails.
     * @expectedException CAS_AuthenticationException
     * @outputBuffering enabled
     */
    public function test_invalid_ticket_failure() {
		$this->object->setTicket('ST-1856339-aA5Yuvrxzpv8Tau1cYQ7');
		ob_start();
		$result = $this->object->validateCAS20($url, $text_response, $tree_response);
		ob_end_clean();
		$this->assertTrue($result);
		$this->assertEquals(
"<cas:serviceResponse xmlns:cas='http://www.yale.edu/tp/cas'>
    <cas:authenticationFailure code='INVALID_TICKET'>
        Ticket ST-1856339-aA5Yuvrxzpv8Tau1cYQ7 not recognized
    </cas:authenticationFailure>
</cas:serviceResponse>

", $text_response);
		$this->assertInstanceOf('DOMElement', $tree_response);
    }
    
    
    public function test_allowed_proxies_string_success(){
    	$this->object->setTicket('ST-123456-asdfasdfasgww2323radf3');
    	$this->object->getAllowedProxyChains()->allowProxyChain(new CAS_ProxyChain(array(
    			'http://firstproxy.com','https://anotherdomain.org/mysite/test2'
    		)));
    	$this->object->getAllowedProxyChains()->allowProxyChain(new CAS_ProxyChain(array(
    			'https://anotherdomain.php'
    		)));
    	$result = $this->object->validateCAS20($url, $text_response, $tree_response);
  		$this->assertTrue($result);
    	$this->assertEquals(
"<cas:serviceResponse xmlns:cas='http://www.yale.edu/tp/cas'>
    <cas:authenticationSuccess>
        <cas:user>jsmith</cas:user>
        <cas:proxies>
            <cas:proxy>http://firstproxy.com/mysite/test</cas:proxy>
            <cas:proxy>https://anotherdomain.org/mysite/test2</cas:proxy>    
        </cas:proxies>
    </cas:authenticationSuccess>
</cas:serviceResponse>"
    	, $text_response);
    	$this->assertInstanceOf('DOMElement', $tree_response);
    }
    /**
     * Test that the trusted proxy allows any proxies beyond the one we trust.
     */
    public function test_allowed_proxies_trusted_success(){
    	$this->object->setTicket('ST-123456-asdfasdfasgww2323radf3');
    	$this->object->getAllowedProxyChains()->allowProxyChain(new CAS_ProxyChain_Trusted(array(
    			'http://firstproxy.com'
    		)));
    	$this->object->getAllowedProxyChains()->allowProxyChain(new CAS_ProxyChain(array(
    			'https://anotherdomain.php'
    		)));
    	$result = $this->object->validateCAS20($url, $text_response, $tree_response);
  		$this->assertTrue($result);
    	$this->assertEquals(
"<cas:serviceResponse xmlns:cas='http://www.yale.edu/tp/cas'>
    <cas:authenticationSuccess>
        <cas:user>jsmith</cas:user>
        <cas:proxies>
            <cas:proxy>http://firstproxy.com/mysite/test</cas:proxy>
            <cas:proxy>https://anotherdomain.org/mysite/test2</cas:proxy>    
        </cas:proxies>
    </cas:authenticationSuccess>
</cas:serviceResponse>"
    	, $text_response);
    	$this->assertInstanceOf('DOMElement', $tree_response);
    }

    /**
     * Test that proxies fail if one is missing from the chain
     * @expectedException CAS_AuthenticationException
     * @outputBuffering enabled
     */
    public function test_allowed_proxies_string_failure_missing_proxy(){
    	$this->object->setTicket('ST-123456-asdfasdfasgww2323radf3');
    	$this->object->getAllowedProxyChains()->allowProxyChain(new CAS_ProxyChain(array(
    			'https://anotherdomain.php'
    		)));
    	$result = $this->object->validateCAS20($url, $text_response, $tree_response);
    	$this->assertFalse($result);
    }
    
    /**
     * Test that proxies fail if in wrong order and definded as string
     * @expectedException CAS_AuthenticationException
     * @outputBuffering enabled
     */
    public function test_allowed_proxies_string_failure_wrong_order(){
    	$this->object->setTicket('ST-123456-asdfasdfasgww2323radf3');
    	$this->object->getAllowedProxyChains()->allowProxyChain(new CAS_ProxyChain(array(
    			'https://anotherdomain.org/mysite/test2','http://firstproxy.com'
    		)));
    	$this->object->getAllowedProxyChains()->allowProxyChain(new CAS_ProxyChain(array(
    			'https://anotherdomain.php'
    		)));
    	$result = $this->object->validateCAS20($url, $text_response, $tree_response);
    	$this->assertFalse($result);
    }
    
    /**
     * Test that if proxies exist a response with proxies will fail unless allowed
     * @expectedException CAS_AuthenticationException
     * @outputBuffering enabled
     */
    public function test_allowed_proxies_failure(){
    	$this->object->setTicket('ST-123456-asdfasdfasgww2323radf3');
    	// By default no proxies are allowed.
    	$result = $this->object->validateCAS20($url, $text_response, $tree_response);
    	$this->assertFalse($result);
    }
    
    /**
     * 
     * Test that regexp filtering of allowed proxies works
     */
    public function test_allowed_proxies_regexp_success(){
    	$this->object->setTicket('ST-123456-asdfasdfasgww2323radf3');
    	$this->object->getAllowedProxyChains()->allowProxyChain(new CAS_ProxyChain(array(
    			'/badregexp/'
    		)));
    	$this->object->getAllowedProxyChains()->allowProxyChain(new CAS_ProxyChain(array(
    			'/http\:\/\/firstproxy\.com.*$/','/^https\:\/\/anotherdomain.org\/mysite\/test2$/'
    		)));
    	$result = $this->object->validateCAS20($url, $text_response, $tree_response);
  		$this->assertTrue($result);
	}
	

     /**
     * Wrong regexp to mach proxies
     * @expectedException CAS_AuthenticationException
     * @outputBuffering enabled
     */
    public function test_allowed_proxies_regexp_failure_wrong(){
    	$this->object->setTicket('ST-123456-asdfasdfasgww2323radf3');
    	$this->object->getAllowedProxyChains()->allowProxyChain(new CAS_ProxyChain(array(
    			'/^http:\/\/secondproxy\.com/','/^https.*$/'
    		)));
    	$result = $this->object->validateCAS20($url, $text_response, $tree_response);
  		$this->assertFalse($result);
	}
	
     /**
     * Wrong order of valid regexp
     * @expectedException CAS_AuthenticationException
     * @outputBuffering enabled
     */
    public function test_allowed_proxies_regexp_failure_wrong_order(){
    	$this->object->setTicket('ST-123456-asdfasdfasgww2323radf3');
    	$this->object->getAllowedProxyChains()->allowProxyChain(new CAS_ProxyChain(array(
    			'/^https\:\/\/anotherdomain.org\/mysite\/test2$/','/http\:\/\/firstproxy\.com.*$/'
    		)));
    	$result = $this->object->validateCAS20($url, $text_response, $tree_response);
  		$this->assertFalse($result);
	}
}
?>
