<?php
declare(strict_types=1);

namespace VelocityDev\DeveloperTools\Test\Unit\Model\Config;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;
use Magento\Framework\HTTP\PhpEnvironment\Request;
use Magento\Framework\Math\Random;
use VelocityDev\DeveloperTools\Model\Config\ProfilerConfig;
use VelocityDev\DeveloperTools\Api\ProfilerConfigInterface;
use VelocityDev\DeveloperTools\Service\ApiKeyCookieManagerService;

/**
 * Test class for ProfilerConfig
 * @package VelocityDev\DeveloperTools\Test\Unit\Model\Config
 */
class ProfilerConfigTest extends TestCase
{
    /**
     * @var ProfilerConfig
     */
    private ProfilerConfig $profilerConfig;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private ScopeConfigInterface|MockObject $scopeConfig;

    /**
     * @var State|MockObject
     */
    private State|MockObject $appState;

    /**
     * @var Random|MockObject
     */
    private Random|MockObject $mathRandom;

    /**
     * @var ApiKeyCookieManagerService|MockObject
     */
    private ApiKeyCookieManagerService|MockObject $cookieManagerService;

    /**
     * @var Request|MockObject
     */
    private Request|MockObject $request;

    /**
     * Set up test dependencies
     */
    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->appState = $this->createMock(State::class);
        $this->mathRandom = $this->createMock(Random::class);
        $this->cookieManagerService = $this->createMock(ApiKeyCookieManagerService::class);
        $this->request = $this->createMock(Request::class);

        $this->profilerConfig = new ProfilerConfig(
            $this->scopeConfig,
            $this->appState,
            $this->mathRandom,
            $this->cookieManagerService
        );
    }

    /**
     * Test isEnabled method
     */
    public function testIsEnabled(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with(ProfilerConfigInterface::XML_PATH_ENABLED)
            ->willReturn(true);

        $result = $this->profilerConfig->isEnabled();
        $this->assertTrue($result);
    }

    /**
     * Test isEnabled method when disabled
     */
    public function testIsEnabledWhenDisabled(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with(ProfilerConfigInterface::XML_PATH_ENABLED)
            ->willReturn(false);

        $result = $this->profilerConfig->isEnabled();
        $this->assertFalse($result);
    }

    /**
     * Test getProfilerHeaderKey method
     */
    public function testGetProfilerHeaderKey(): void
    {
        $expectedKey = 'X-Custom-Debug-Profile';
        
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(ProfilerConfigInterface::XML_PATH_HEADER_KEY)
            ->willReturn($expectedKey);

        $result = $this->profilerConfig->getProfilerHeaderKey();
        $this->assertEquals($expectedKey, $result);
    }

    /**
     * Test getProfilerHeaderKey method with default value
     */
    public function testGetProfilerHeaderKeyWithDefault(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(ProfilerConfigInterface::XML_PATH_HEADER_KEY)
            ->willReturn(null);

        $result = $this->profilerConfig->getProfilerHeaderKey();
        $this->assertEquals(ProfilerConfigInterface::DEFAULT_HEADER_KEY, $result);
    }

    /**
     * Test isHtmlOutputEnabled method
     */
    public function testIsHtmlOutputEnabled(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with(ProfilerConfigInterface::XML_PATH_HTML_OUTPUT)
            ->willReturn(true);

        $result = $this->profilerConfig->isHtmlOutputEnabled();
        $this->assertTrue($result);
    }

    /**
     * Test isJsonInjectionEnabled method
     */
    public function testIsJsonInjectionEnabled(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with(ProfilerConfigInterface::XML_PATH_JSON_INJECTION)
            ->willReturn(false);

        $result = $this->profilerConfig->isJsonInjectionEnabled();
        $this->assertFalse($result);
    }

    /**
     * Test isLogToFileEnabled method
     */
    public function testIsLogToFileEnabled(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with(ProfilerConfigInterface::XML_PATH_LOG_TO_FILE)
            ->willReturn(true);

        $result = $this->profilerConfig->isLogToFileEnabled();
        $this->assertTrue($result);
    }

    /**
     * Test isDeveloperModeOnly method
     */
    public function testIsDeveloperModeOnly(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with(ProfilerConfigInterface::XML_PATH_DEVELOPER_MODE_ONLY)
            ->willReturn(true);

        $result = $this->profilerConfig->isDeveloperModeOnly();
        $this->assertTrue($result);
    }

    /**
     * Test getSlowQueryThreshold method
     */
    public function testGetSlowQueryThreshold(): void
    {
        $expectedThreshold = 200;
        
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(ProfilerConfigInterface::XML_PATH_SLOW_QUERY_THRESHOLD)
            ->willReturn($expectedThreshold);

        $result = $this->profilerConfig->getSlowQueryThreshold();
        $this->assertEquals($expectedThreshold, $result);
    }

    /**
     * Test getSlowQueryThreshold method with default value
     */
    public function testGetSlowQueryThresholdWithDefault(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(ProfilerConfigInterface::XML_PATH_SLOW_QUERY_THRESHOLD)
            ->willReturn(null);

        $result = $this->profilerConfig->getSlowQueryThreshold();
        $this->assertEquals(ProfilerConfigInterface::DEFAULT_SLOW_QUERY_THRESHOLD, $result);
    }

    /**
     * Test getMemoryLimitMb method
     */
    public function testGetMemoryLimitMb(): void
    {
        $expectedLimit = 256;
        
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(ProfilerConfigInterface::XML_PATH_MEMORY_LIMIT)
            ->willReturn($expectedLimit);

        $result = $this->profilerConfig->getMemoryLimitMb();
        $this->assertEquals($expectedLimit, $result);
    }

    /**
     * Test getMemoryLimitMb method with default value
     */
    public function testGetMemoryLimitMbWithDefault(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(ProfilerConfigInterface::XML_PATH_MEMORY_LIMIT)
            ->willReturn(null);

        $result = $this->profilerConfig->getMemoryLimitMb();
        $this->assertEquals(ProfilerConfigInterface::DEFAULT_MEMORY_LIMIT, $result);
    }

    /**
     * Test isApiKeyEnabled method
     */
    public function testIsApiKeyEnabled(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with(ProfilerConfigInterface::XML_PATH_API_KEY_ENABLED)
            ->willReturn(true);

        $result = $this->profilerConfig->isApiKeyEnabled();
        $this->assertTrue($result);
    }

    /**
     * Test getApiKey method
     */
    public function testGetApiKey(): void
    {
        $expectedApiKey = 'test-api-key-123';
        
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(ProfilerConfigInterface::XML_PATH_API_KEY)
            ->willReturn($expectedApiKey);

        $result = $this->profilerConfig->getApiKey();
        $this->assertEquals($expectedApiKey, $result);
    }

    /**
     * Test getApiKey method when null
     */
    public function testGetApiKeyWhenNull(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(ProfilerConfigInterface::XML_PATH_API_KEY)
            ->willReturn(null);

        $result = $this->profilerConfig->getApiKey();
        $this->assertNull($result);
    }

    /**
     * Test generateApiKey method
     */
    public function testGenerateApiKey(): void
    {
        $expectedApiKey = 'generated-api-key-456';
        
        $this->mathRandom->expects($this->once())
            ->method('getRandomString')
            ->with(32)
            ->willReturn($expectedApiKey);

        $result = $this->profilerConfig->generateApiKey();
        $this->assertEquals($expectedApiKey, $result);
    }

    /**
     * Test validateApiKey method with valid key
     */
    public function testValidateApiKeyWithValidKey(): void
    {
        $apiKey = 'valid-api-key-123';
        
        // Mock API key enabled
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with(ProfilerConfigInterface::XML_PATH_API_KEY_ENABLED)
            ->willReturn(true);

        // Mock getting API key from config
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(ProfilerConfigInterface::XML_PATH_API_KEY)
            ->willReturn($apiKey);

        // Mock request header
        $this->request->expects($this->once())
            ->method('getHeader')
            ->with(ProfilerConfigInterface::API_KEY_HEADER)
            ->willReturn($apiKey);

        $result = $this->profilerConfig->validateApiKey($this->request);
        $this->assertTrue($result);
    }

    /**
     * Test validateApiKey method with invalid key
     */
    public function testValidateApiKeyWithInvalidKey(): void
    {
        $configApiKey = 'valid-api-key-123';
        $requestApiKey = 'invalid-api-key-456';
        
        // Mock API key enabled
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with(ProfilerConfigInterface::XML_PATH_API_KEY_ENABLED)
            ->willReturn(true);

        // Mock getting API key from config
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(ProfilerConfigInterface::XML_PATH_API_KEY)
            ->willReturn($configApiKey);

        // Mock request header
        $this->request->expects($this->once())
            ->method('getHeader')
            ->with(ProfilerConfigInterface::API_KEY_HEADER)
            ->willReturn($requestApiKey);

        $result = $this->profilerConfig->validateApiKey($this->request);
        $this->assertFalse($result);
    }

    /**
     * Test validateApiKey method when API key validation is disabled
     */
    public function testValidateApiKeyWhenDisabled(): void
    {
        // Mock API key disabled
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with(ProfilerConfigInterface::XML_PATH_API_KEY_ENABLED)
            ->willReturn(false);

        $result = $this->profilerConfig->validateApiKey($this->request);
        $this->assertTrue($result);
    }
} 