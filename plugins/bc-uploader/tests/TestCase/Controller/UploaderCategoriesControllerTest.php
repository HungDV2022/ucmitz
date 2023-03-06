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
use CakephpFixtureFactories\Scenario\ScenarioAwareTrait;
use Cake\TestSuite\IntegrationTestTrait;

/**
 * Class UploaderCategoriesControllerTest
 */
class UploaderCategoriesControllerTest extends BcTestCase
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
     * test index
     * @return void
     */
    public function test_index()
    {
        $this->markTestIncomplete('このテストは、まだ実装されていません。');
    }

    /**
     * test add
     * @return void
     */
    public function test_add()
    {
        //アップロードカテゴリを追加
        $data = [
            'name' => 'japan'
        ];
        //APIを呼ぶ
        $this->post("/baser/api/bc-uploader/uploader_categories/add.json?token=" . $this->accessToken, $data);
        //ステータスを確認
        $this->assertResponseSuccess();
        //戻る値を確認
        $result = json_decode((string)$this->_response->getBody());
        $this->assertEquals('新規アップロードカテゴリ「japan」を追加しました。', $result->message);
        $this->assertEquals('japan', $result->uploaderCategory->name);

        //400エラーを確認
        $data = [
            'name' => null
        ];
        //APIを呼ぶ
        $this->post("/baser/api/bc-uploader/uploader_categories/add.json?token=" . $this->accessToken, $data);
        //ステータスを確認
        $this->assertResponseCode(400);
        //戻る値を確認
        $result = json_decode((string)$this->_response->getBody());
        $this->assertEquals('入力エラーです。内容を修正してください。', $result->message);
        $this->assertEquals('カテゴリ名を入力してください。', $result->errors->name->_empty);

        //500エラーを確認
        $data = [
            'name' => 'name...................................................'
        ];
        //APIを呼ぶ
        $this->post("/baser/api/bc-uploader/uploader_categories/add.json?token=" . $this->accessToken, $data);
        //ステータスを確認
        $this->assertResponseCode(500);
        //戻る値を確認
        $result = json_decode((string)$this->_response->getBody());
        $this->assertEquals("データベース処理中にエラーが発生しました。SQLSTATE[22001]: String data, right truncated: 1406 Data too long for column 'name' at row 1", $result->message);
    }

    /**
     * test edit
     * @return void
     */
    public function test_edit()
    {
        $this->markTestIncomplete('このテストは、まだ実装されていません。');
    }

    /**
     * test delete
     * @return void
     */
    public function test_delete()
    {
        $this->markTestIncomplete('このテストは、まだ実装されていません。');
    }

    /**
     * test batch
     * @return void
     */
    public function test_batch()
    {
        $this->markTestIncomplete('このテストは、まだ実装されていません。');
    }
}
