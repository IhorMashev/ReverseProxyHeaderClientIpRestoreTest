<?php

namespace Drupal\Tests\reverse_proxy_header\Unit;

use Composer\Autoload\ClassLoader;
use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher as EventDispatcher;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Site\Settings;
use Drupal\reverse_proxy_header\EventSubscriber\ReverseProxyHeaderClientIpRestore;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Test class for ReverseProxyHeaderClientIpRestore service.
 */
class ReverseProxyHeaderClientIpRestoreTest extends UnitTestCase {

  /**
   * Test ReverseProxyHeaderClientIpRestore service.
   *
   * @param string $expected_client_ip
   *   The expected clientIp address.
   *
   * @dataProvider providerReverseProxyHeader
   */
  public function testReverseProxyHeader($expected_client_ip) {
    $client = new Client();
    $auto_loader = new ClassLoader();
    $request = new Request();
    $site_path = DrupalKernel::findSitePath($request);
    $app_root = dirname(substr(__DIR__, 0, -strlen(__NAMESPACE__)), 2);
    Settings::initialize($app_root, $site_path, $auto_loader);
    $header = Settings::get('reverse_proxy_header');

    // Set server parameter reverse_proxy_header as expected client ip.
    $request->server->set($header, $expected_client_ip);
    $reverse_proxy_header_client_ip_restore_service = new ReverseProxyHeaderClientIpRestore($client, new Settings(Settings::getAll()));

    $kernel = $this->createMock('Symfony\Component\HttpKernel\HttpKernelInterface');
    $dispatcher = new EventDispatcher(new Container());
    $dispatcher->addListener(KernelEvents::REQUEST, [$reverse_proxy_header_client_ip_restore_service, 'onRequest']);
    $event = new RequestEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);
    $dispatcher->dispatch($event, KernelEvents::REQUEST);

    $this->assertEquals($request->getClientIp(), $expected_client_ip);
  }

  /**
   * Provider for testing ReverseProxyHeaderClientIpRestore.
   *
   * @return array
   *   Test Data to simulate incoming ip address.
   */

  public function providerReverseProxyHeader() {
    return [
      [
        '182.188.2.213',
      ],
      [
        '182.188.2.253',
      ],
      [
        '172.188.3.213',
      ],
      [
        '182.118.2.213',
      ],
    ];
  }

}
