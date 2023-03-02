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

namespace BaserCore\Controller\Api;

use BaserCore\Error\BcException;
use BaserCore\Service\SitesServiceInterface;
use BaserCore\Service\ThemesServiceInterface;
use BaserCore\Annotation\UnitTest;
use BaserCore\Annotation\NoTodo;
use BaserCore\Annotation\Checked;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\Exception\PersistenceFailedException;

/**
 * Class ThemesController
 *
 * https://localhost/baser/api/baser-core/themes/action_name.json で呼び出す
 *
 * @package BaserCore\Controller\Api
 */
class ThemesController extends BcApiController
{
    /**
     * [API] 単一テーマ情報を取得する
     * @param ThemesServiceInterface $service
     * @param string $theme
     * @checked
     * @unitTest
     * @noTodo
     */
    public function view(ThemesServiceInterface $service, $theme)
    {
        $this->request->allowMethod(['get']);
        $themeDetail = $message = null;
        try {
            $themeDetail = $service->get($theme);
        } catch (RecordNotFoundException $e) {
            $this->setResponse($this->response->withStatus(404));
            $message = __d('baser', 'データが見つかりません。');
        } catch (\Throwable $e) {
            $message = __d('baser', 'データベース処理中にエラーが発生しました。' . $e->getMessage());
            $this->setResponse($this->response->withStatus(500));
        }
        $this->set([
            'theme' => $themeDetail,
            'message' => $message
        ]);
        $this->viewBuilder()->setOption('serialize', ['theme', 'message']);
    }

    /**
     * [API] テーマ一覧を取得する
     * @param ThemesServiceInterface $service
     * @checked
     * @noTodo
     * @unitTest
     */
    public function index(ThemesServiceInterface $service)
    {
        $this->set([
            'themes' => $service->getIndex()
        ]);
        $this->viewBuilder()->setOption('serialize', ['themes']);
    }

    /**
     * [API] 新しいテーマをアップロードする
     * @param ThemesServiceInterface $service
     * @noTodo
     * @checked
     * @unitTest
     */
    public function add(ThemesServiceInterface $service)
    {
        $this->request->allowMethod(['post']);
        $theme = $errors = null;
        try {
            $theme = $service->add($this->getRequest()->getUploadedFiles());
            $message = __d('baser', 'テーマファイル「' . $theme . '」を追加しました。');
        } catch (PersistenceFailedException $e) {
            $errors = $e->getEntity()->getErrors();
            $message = __d('baser', "入力エラーです。内容を修正してください。");
            $this->setResponse($this->response->withStatus(400));
        } catch (\Throwable $e) {
            $message = __d('baser', 'データベース処理中にエラーが発生しました。' . $e->getMessage());
            $this->setResponse($this->response->withStatus(500));
        }

        $this->set([
            'message' => $message,
            'theme' => $theme,
            'errors' => $errors
        ]);
        $this->viewBuilder()->setOption('serialize', ['message', 'theme', 'errors']);
    }
    /**
     * [API] テーマを削除する
     *
     * @param ThemesServiceInterface $service
     * @param string $theme
     * @checked
     * @noTodo
     * @unitTest
     */
    public function delete(ThemesServiceInterface $service, string $theme)
    {
        $this->request->allowMethod(['post']);

        $themeDetail = null;
        try {
            $themeDetail = $service->get($theme);
            $service->delete($themeDetail->name);
            $message = __d('baser', 'テーマ「{0}」を削除しました。', $themeDetail->name);
        } catch (NotFoundException $e) {
            $this->setResponse($this->response->withStatus(404));
            $message = __d('baser', 'データが見つかりません');
        } catch (\Throwable $e) {
            $message = __d('baser', 'データベース処理中にエラーが発生しました。' . $e->getMessage());
            $this->setResponse($this->response->withStatus(500));
        }

        $this->set([
            'theme' => $themeDetail,
            'message' => $message
        ]);

        $this->viewBuilder()->setOption('serialize', ['theme', 'message']);
    }

    /**
     * [API] テーマをコピーする
     *
     * @param ThemesServiceInterface $service
     * @param string $theme
     * @checked
     * @noTodo
     * @unitTest
     */
    public function copy(ThemesServiceInterface $service, $theme)
    {
        $this->request->allowMethod(['post']);

        $themeDetail = null;
        try {
            $rs = $service->copy($theme);
            if ($rs) {
                $message = __d('baser', 'テーマ「{0}」をコピーしました。', $theme);
            } else {
                $this->setResponse($this->response->withStatus(400));
                $message = __d('baser', 'テーマ「{0}」のコピーに失敗しました。', $theme);
            }
            $themeDetail = $service->get($theme);

        } catch (RecordNotFoundException $e) {
            $this->setResponse($this->response->withStatus(404));
            $message = __d('baser', 'データが見つかりません。');
        } catch (\Throwable $e) {
            $message = __d('baser', 'データベース処理中にエラーが発生しました。' . $e->getMessage());
            $this->setResponse($this->response->withStatus(500));
        }
        $this->set([
            'theme' => $themeDetail,
            'message' => $message
        ]);

        $this->viewBuilder()->setOption('serialize', ['theme', 'message']);
    }

    /**
     * [API] テーマの初期データを読み込むAPIを実装
     * @param ThemesServiceInterface $service
     * @param SitesServiceInterface $sitesService
     * @param int $siteId
     * @noTodo
     */
    public function load_default_data(
        ThemesServiceInterface $service,
        SitesServiceInterface $sitesService,
        int $siteId
    ) {
        $this->request->allowMethod(['post']);

        if (empty($this->getRequest()->getData('default_data_pattern'))) {
            $this->setResponse($this->response->withStatus(400));
            $message = __d('baser', '不正な操作です。');
        } else {
            try {
                $result = $service->loadDefaultDataPattern($sitesService->get($siteId), $this->getRequest()->getData('default_data_pattern'));
                if (!$result) {
                    $this->setResponse($this->response->withStatus(400));
                    $message = __d('baser', '初期データの読み込みが完了しましたが、いくつかの処理に失敗しています。ログを確認してください。');
                } else {
                    $message = __d('baser', '初期データの読み込みが完了しました。');
                }
            } catch (\Throwable $e) {
                $message = __d('baser', 'データベース処理中にエラーが発生しました。' . $e->getMessage());
                $this->setResponse($this->response->withStatus(500));
            }
        }

        $this->set([
            'message' => $message
        ]);

        $this->viewBuilder()->setOption('serialize', ['message']);
    }
    /**
     * [API] テーマを適用するAPI
     * @param ThemesServiceInterface $service
     * @param SitesServiceInterface $sitesService
     * @param int $siteId
     * @param string $theme
     * @checked
     * @noTodo
     * @unitTest
     */
    public function apply(
        ThemesServiceInterface $service,
        SitesServiceInterface $sitesService,
        int $siteId,
        string $theme
    ) {
        $this->request->allowMethod(['post']);

        $themeDetail = null;

        try {
            $info = $service->apply($sitesService->get($siteId), $theme);
            $themeDetail = $service->get($theme);
            $message = [__d('baser', 'テーマ「{0}」を適用しました。', $themeDetail->name)];
            if ($info) $message = array_merge($message, [''], $info);
            $message = implode("\n", $message);
        } catch (RecordNotFoundException $e) {
            $this->setResponse($this->response->withStatus(404));
            $message = __d('baser', 'データが見つかりません。');
        } catch (\Throwable $e) {
            $message = __d('baser', 'データベース処理中にエラーが発生しました。' . $e->getMessage());
            $this->setResponse($this->response->withStatus(500));
        }

        $this->set([
            'message' => $message,
            'theme' => $themeDetail,
            'siteId' => $siteId
        ]);

        $this->viewBuilder()->setOption('serialize', ['message', 'theme', 'siteId']);
    }

    /**
     * [API] baserマーケットよりテーマの一覧を取得する
     * @param ThemesServiceInterface $service
     * @return void
     *
     * @checked
     * @noTodo
     * @unitTest
     */
    public function get_market_themes(ThemesServiceInterface $service)
    {
        $this->set([
            'baserThemes' => $service->getMarketThemes(),
        ]);

        $this->viewBuilder()->setOption('serialize', ['baserThemes']);
    }
}
