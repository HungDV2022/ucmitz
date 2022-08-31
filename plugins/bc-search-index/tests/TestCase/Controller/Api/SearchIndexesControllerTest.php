<?php
/**
 * baserCMS :  Based Website Development Project <https://basercms.net>
 * Copyright (c) NPO baser foundation <https://baserfoundation.org/>
 *
 * @copyright     Copyright (c) NPO baser foundation
 * @link          https://basercms.net baserCMS Project
 * @since         5.0.0
 * @license       https://basercms.net/license/index.html MIT License
 */

namespace BcSearchIndex\Test\TestCase\Controller\Api;

use BaserCore\Test\Scenario\InitAppScenario;
use BaserCore\TestSuite\BcTestCase;
use BcSearchIndex\Controller\Api\SearchIndexesController;
use BcSearchIndex\Test\Factory\SearchIndexFactory;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\TestSuite\IntegrationTestTrait;
use CakephpFixtureFactories\Scenario\ScenarioAwareTrait;

/**
 * Class SearchIndexesControllerTest
 * @package BcSearchIndex\Test\TestCase\Controller\Api
 * @property SearchIndexesController $SearchIndexesController
 */
class SearchIndexesControllerTest extends BcTestCase
{

    /**
     * Trait
     */
    use ScenarioAwareTrait;
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.BaserCore.Factory/Users',
        'plugin.BaserCore.Factory/Sites',
        'plugin.BaserCore.Factory/UserGroups',
        'plugin.BaserCore.Factory/UsersUserGroups',
        'plugin.BaserCore.Factory/SearchIndexes',
        'plugin.BaserCore.Factory/Dblogs'
    ];

    /**
     * set up
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->loadFixtureScenario(InitAppScenario::class);
        $token = $this->apiLoginAdmin();
        $this->accessToken = $token['access_token'];
        $this->refreshToken = $token['refresh_token'];
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown(): void
    {
        Configure::clear();
        parent::tearDown();
    }

    /**
     * testBeforeRender
     */
    public function testBeforeRender()
    {
        $this->markTestIncomplete('このテストは、まだ実装されていません。');
    }

    /**
     * test beforeFilter
     * @return void
     */
    public function testBeforeFilter()
    {
        $request = $this->getRequest('/baser/admin/bc-search-index/search_indexes/');
        $request = $this->loginAdmin($request);
        $searchIndexes = new SearchIndexesController($request);

        $event = new Event('filter');
        $searchIndexes->beforeFilter($event);
        $this->assertFalse($searchIndexes->Security->getConfig('validatePost'));
    }

    /**
     * test change_priority
     * @return void
     */
    public function testChangePriority()
    {
        SearchIndexFactory::make(['id' => 1, 'title' => 'test data', 'type' => 'admin', 'site_id' => 1], 1)->persist();
        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->get('/baser/api/bc-search-index/search_indexes/change_priority/1.json?token=' . $this->accessToken);
        $this->assertResponseCode(405);

        $data = [
            'priority' => 10
        ];
        $this->post('/baser/api/bc-search-index/search_indexes/change_priority/1.json?token=' . $this->accessToken, $data);
        $this->assertResponseSuccess();
        $result = json_decode((string)$this->_response->getBody());

        $this->assertEquals('検索インデックス「test data」の優先度を変更しました。', $result->message);
        $this->assertEquals(10, $result->searchIndex->priority);

    }



    /**
     * test index
     * @return void
     */
    public function testIndex(){
        SearchIndexFactory::make(['id' => 2, 'title' => 'test data index', 'type' => 'admin', 'site_id' => 0], 1)->persist();

        $this->get('/baser/api/bc-search-index/search_indexes/index.json?token=' . $this->accessToken);
        $this->assertResponseOk();
        $result = json_decode((string)$this->_response->getBody());
        $this->assertEquals('test data index', $result->searchIndexes[0]->title);

        SearchIndexFactory::make(['id' => 3, 'title' => 'test with param', 'type' => 'admin', 'site_id' => 1], 1)->persist();
        $this->get('/baser/api/bc-search-index/search_indexes/index.json?site_id=1&token=' . $this->accessToken);
        $this->assertResponseOk();
        $result = json_decode((string)$this->_response->getBody());
        $this->assertEquals('test with param', $result->searchIndexes[0]->title);
    }
}
