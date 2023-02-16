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

namespace BcMail\Test\TestCase\Controller\Api;

use BaserCore\Test\Factory\ContentFactory;
use BaserCore\Test\Scenario\InitAppScenario;
use BaserCore\TestSuite\BcTestCase;
use BcMail\Service\MailContentsServiceInterface;
use BcMail\Test\Factory\MailContentFactory;
use Cake\TestSuite\IntegrationTestTrait;
use CakephpFixtureFactories\Scenario\ScenarioAwareTrait;

class MailContentsControllerTest extends BcTestCase
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
        'plugin.BaserCore.Factory/Contents',
        'plugin.BcMail.Factory/MailContents',
        'plugin.BcMail.Factory/MailFields',
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
        $token = $this->apiLoginAdmin(1);
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
     * [API] メールコンテンツ API 一覧取得
     */
    public function testIndex()
    {
        $this->markTestIncomplete('このテストは、まだ実装されていません。');
    }

    /**
     * [API] メールコンテンツ API 単一データ取得
     */
    public function testView()
    {
        $this->markTestIncomplete('このテストは、まだ実装されていません。');
    }

    /**
     * [API] メールコンテンツ API リスト取得
     */
    public function testList()
    {
        $this->markTestIncomplete('このテストは、まだ実装されていません。');
    }

    /**
     * [API] メールコンテンツ API 新規追加
     */
    public function testAdd()
    {
        $this->markTestIncomplete('このテストは、まだ実装されていません。');
    }

    /**
     * [API] メールコンテンツ API 編集
     */
    public function testEdit()
    {
        $this->markTestIncomplete('このテストは、まだ実装されていません。');
    }

    /**
     * [API] メールコンテンツ API 削除
     */
    public function testDelete()
    {
        //メールのコンテンツサービスをコル
        $mailContentServices = $this->getService(MailContentsServiceInterface::class);
        //データを生成
        MailContentFactory::make([
            'id' => 1,
            'description' => 'description test',
            'sender_name' => 'baserCMSサンプル',
            'subject_user' => '【baserCMS】お問い合わせ頂きありがとうございます。',
            'subject_admin' => '【baserCMS】お問い合わせを受け付けました',
            'form_template' => 'default',
            'mail_template' => 'mail_default',
            'redirect_url' => '/',
        ])->persist();
        ContentFactory::make([
            'name' => 'name_test',
            'plugin' => 'BcMail',
            'type' => 'MailContent',
            'url' => '/contact/',
            'title' => 'お問い合わせ',
            'entity_id' => 1,
            'rght' => 1,
            'lft' => 2,
            'created_date' => '2023-02-16 16:41:37',
        ])->persist();
        //APIを呼ぶ
        $this->post("/baser/api/bc-mail/mail_contents/delete/1.json?token=" . $this->accessToken);
        // レスポンスコードを確認する
        $this->assertResponseOk();
        // 戻る値を確認
        $result = json_decode((string)$this->_response->getBody());
        $this->assertNotNull($result->mailContent);
        $this->assertNotNull($result->content);
        // 戻るメッセージを確認
        $this->assertEquals($result->message, 'メールフォーム「お問い合わせ」を削除しました。');

        //削除したメールコンテンツが存在しないか確認すること
        $mailContent = $this->getTableLocator()->get('MailContents');
        $query = $mailContent->find()->where(['id' => 1]);
        $this->assertEquals(0, $query->count());
    }

    /**
     * [API] メールコンテンツ API コピー
     */
    public function testCopy()
    {
        $this->markTestIncomplete('このテストは、まだ実装されていません。');
    }
}
