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

namespace BcBlog\Test\TestCase\Service\Admin;

use BaserCore\TestSuite\BcTestCase;
use BaserCore\Utility\BcContainerTrait;
use BcBlog\Service\Admin\BlogCategoriesAdminService;
use BcBlog\Test\Factory\BlogCategoryFactory;
use BcBlog\Test\Factory\BlogContentsFactory;
use Cake\TestSuite\IntegrationTestTrait;

/**
 * BlogCategoriesAdminServiceTest
 * @property BlogCategoriesAdminService $BlogCategoriesAdminService
 */
class BlogCategoriesAdminServiceTest extends BcTestCase
{

    /**
     * Trait
     */
    use BcContainerTrait;
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.BaserCore.Factory/Sites',
        'plugin.BaserCore.Factory/Users',
        'plugin.BaserCore.Factory/Contents',
        'plugin.BaserCore.Factory/ContentFolders',
        'plugin.BaserCore.Factory/Pages',
        'plugin.BaserCore.Factory/SiteConfigs',
        'plugin.BaserCore.Factory/SearchIndexes',
        'plugin.BcBlog.Factory/BlogContents',
    ];

    /**
     * Set Up
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->setFixtureTruncate();
        parent::setUp();
        $this->BlogCategoriesAdminService = new BlogCategoriesAdminService();
    }

    /**
     * Tear Down
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->BlogCategoriesAdminService);
        parent::tearDown();
    }

    /**
     * test getViewVarsForIndex
     */
    public function test_getViewVarsForIndex()
    {
        BlogContentsFactory::make(['id' => 52, 'description' => 'test index'])->persist();
        BlogCategoryFactory::make(['id' => 52, 'title' => 'title index 1', 'blog_content_id' => 52, 'rght' => 7, 'lft' => 8])->persist();
        BlogCategoryFactory::make(['id' => 53, 'title' => 'title index 2', 'blog_content_id' => 52, 'rght' => 9, 'lft' => 10])->persist();
        BlogCategoryFactory::make(['id' => 54, 'title' => 'title index 2', 'blog_content_id' => 51, 'rght' => 10, 'lft' => 11])->persist();

        $rs = $this->BlogCategoriesAdminService->getViewVarsForIndex(52);
        $this->assertEquals($rs['blogContent']['description'], 'test index');
        $this->assertEquals(2, count($rs['blogCategories']));
    }

    /**
     * test getViewVarsForAdd
     */
    public function test_getViewVarsForAdd()
    {
        $this->markTestIncomplete('このテストは、まだ実装されていません。');
    }

    /**
     * test getViewVarsForEdit
     */
    public function test_getViewVarsForEdit()
    {
        $this->markTestIncomplete('このテストは、まだ実装されていません。');
    }
}
