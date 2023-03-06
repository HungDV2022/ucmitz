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

namespace BcUploader\Test\TestCase\Controller\Api;

use BaserCore\Test\Scenario\InitAppScenario;
use BaserCore\TestSuite\BcTestCase;
use BcUploader\Test\Factory\UploaderConfigFactory;
use CakephpFixtureFactories\Scenario\ScenarioAwareTrait;
use Cake\TestSuite\IntegrationTestTrait;

/**
 * Class UploaderConfigsControllerTest
 */
class UploaderConfigsControllerTest extends BcTestCase
{

    /**
     * ScenarioAwareTrait
     */
    use ScenarioAwareTrait;
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.BaserCore.Factory/Sites',
        'plugin.BaserCore.Factory/SiteConfigs',
        'plugin.BaserCore.Factory/Users',
        'plugin.BaserCore.Factory/UsersUserGroups',
        'plugin.BaserCore.Factory/UserGroups',
        'plugin.BcUploader.Factory/UploaderFiles',
        'plugin.BcUploader.Factory/UploaderCategories',
        'plugin.BcUploader.Factory/UploaderConfigs',
    ];

    /**
     * Access Token
     * @var string
     */
    public $accessToken = null;

    /**
     * Refresh Token
     * @var null
     */
    public $refreshToken = null;

    /**
     * set up
     */
    public function setUp(): void
    {
        $this->setFixtureTruncate();
        parent::setUp();
        $this->loadFixtureScenario(InitAppScenario::class);
        $token = $this->apiLoginAdmin(1);
        $this->accessToken = $token['access_token'];
        $this->refreshToken = $token['refresh_token'];
    }

    /**
     * Tear Down
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * test view
     * @return void
     */
    public function test_view()
    {
        //データを生成
        UploaderConfigFactory::make(['name' => 'name_1', 'value' => 'value_1'])->persist();
        UploaderConfigFactory::make(['name' => 'name_2', 'value' => 'value_2'])->persist();
        //APIを呼ぶ
        $this->get("/baser/api/bc-uploader/uploader_configs/view.json?token=" . $this->accessToken);
        //ステータスを確認
        $this->assertResponseSuccess();
        //戻る値を確認
        $result = json_decode((string)$this->_response->getBody());
        $this->assertEquals('value_1', $result->uploaderConfigs->name_1);
        $this->assertEquals('value_2', $result->uploaderConfigs->name_2);
    }

    /**
     * test edit
     * @return void
     */
    public function test_edit()
    {
        $this->markTestIncomplete('このテストは、まだ実装されていません。');
    }
}
