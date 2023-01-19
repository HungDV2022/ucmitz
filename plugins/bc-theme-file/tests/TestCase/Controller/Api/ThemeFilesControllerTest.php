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

namespace BcThemeFile\Test\TestCase\Controller\Api;

use BaserCore\Test\Scenario\InitAppScenario;
use BaserCore\TestSuite\BcTestCase;
use Cake\Filesystem\File;
use CakephpFixtureFactories\Scenario\ScenarioAwareTrait;

class ThemeFilesControllerTest extends BcTestCase
{

    /**
     * ScenarioAwareTrait
     */
    use ScenarioAwareTrait;

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
        parent::tearDown();
    }

    /**
     * [API] テーマファイル ファイル新規追加
     */
    public function test_add()
    {
        $this->markTestIncomplete('このテストは未実装です。');
    }

    /**
     * [API] テーマファイル ファイル編集
     */
    public function test_edit()
    {
        $this->markTestIncomplete('このテストは未実装です。');
    }

    /**
     * [API] テーマファイル ファイル削除
     */
    public function test_delete()
    {
        $this->markTestIncomplete('このテストは未実装です。');
    }

    /**
     * [API] テーマファイル ファイルコピー
     */
    public function test_copy()
    {
        //テストファイルを作成
        $fullpath = BASER_PLUGINS . 'BcThemeSample' . '/templates/layout/';
        new File($fullpath . 'base_name_1.php', true);
        //POSTデータを生成
        $data = [
            'fullpath' => $fullpath . 'base_name_1.php',
            'path' => 'base_name_1.php',
        ];
        //APIをコール
        $this->post('/baser/api/bc-theme-file/theme_files/copy.json?token=' . $this->accessToken, $data);
        //レスポンスコードを確認
        $this->assertResponseSuccess();
        //戻る値を確認
        $result = json_decode((string)$this->_response->getBody());
        $this->assertEquals('ファイル「base_name_1.php」をコピーしました。', $result->message);
        //実際にファイルが削除されいてるか確認すること
        $this->assertTrue(file_exists($fullpath . 'base_name_1_copy.php'));
        //生成されたテストファイルを削除
        unlink($fullpath . 'base_name_1.php');
        unlink($fullpath . 'base_name_1_copy.php');
    }

    /**
     * [API] テーマファイル 現在のテーマにファイルをコピー
     */
    public function test_copy_to_theme()
    {
        $this->markTestIncomplete('このテストは未実装です。');
    }

    /**
     * [API] テーマファイル ファイルを表示
     */
    public function test_view()
    {
        $this->markTestIncomplete('このテストは未実装です。');
    }

    /**
     * [API] テーマファイル 画像を表示
     */
    public function test_img()
    {
        $this->markTestIncomplete('このテストは未実装です。');
    }

    /**
     * [API] テーマファイル 画像のサムネイルを表示
     */
    public function test_img_thumb()
    {
        $this->markTestIncomplete('このテストは未実装です。');
    }
}
