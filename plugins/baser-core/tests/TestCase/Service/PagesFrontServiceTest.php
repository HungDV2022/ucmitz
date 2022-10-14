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

namespace BaserCore\Test\TestCase\Service;

use BaserCore\Controller\PagesController;
use BaserCore\Service\Front\PagesFrontService;
use BaserCore\Service\Front\PagesFrontServiceInterface;
use BaserCore\Service\PagesService;
use BaserCore\Test\Factory\PageFactory;
use BaserCore\TestSuite\BcTestCase;
use BaserCore\Utility\BcContainerTrait;

/**
 * PagesFrontServiceTest
 */
class PagesFrontServiceTest extends BcTestCase
{

    public $fixtures = [
        'plugin.BaserCore.Pages',
        'plugin.BaserCore.Users',
        'plugin.BaserCore.UsersUserGroups',
        'plugin.BaserCore.UserGroups',
        'plugin.BaserCore.Contents',
        'plugin.BaserCore.ContentFolders',
        'plugin.BaserCore.Sites',
    ];

    /**
     * Trait
     */
    use BcContainerTrait;

    /**
     * PagesFront
     * @var PagesFrontService
     */
    public $PagesFront;

    /**
     * Set up
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->PagesFront = $this->getService(PagesFrontServiceInterface::class);
    }

    /**
     * Tear down
     */
    public function tearDown(): void
    {
        parent::tearDown();
        unset($this->PagesFront);
    }

    /**
     * test getViewVarsForDisplay
     */
    public function test_getViewVarsForView()
    {
        $vars = $this->PagesFront->getViewVarsForView(
            $this->PagesFront->get(2),
            $this->getRequest('/')
        );
        $this->assertArrayHasKey('page', $vars);
        $this->assertArrayHasKey('editLink', $vars);
    }

    /**
     * test setupPreviewForView
     */
    public function test_setupPreviewForView()
    {
        PageFactory::make(['id' => 1])->persist();
        $pageService = new PagesService();
        $page = $pageService->get(1);
        $request = $this->getRequest()->withParam('currentContent', $page);
        $controller = new PagesController($request);

        $this->PagesFront->setupPreviewForView($controller);
        $this->assertArrayHasKey('page', $controller->viewBuilder()->getVars());
        $this->assertArrayHasKey('editLink', $controller->viewBuilder()->getVars());
    }
}
