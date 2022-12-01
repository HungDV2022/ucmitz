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
namespace BcBlog\Controller\Api;

use BaserCore\Controller\Api\BcApiController;
use BaserCore\Annotation\NoTodo;
use BaserCore\Annotation\Checked;
use BaserCore\Annotation\UnitTest;
use BaserCore\Error\BcException;
use BcBlog\Service\BlogPostsServiceInterface;

/**
 * BlogPostsController
 */
class BlogPostsController extends BcApiController
{

    /**
     * [API] ブログ記事一覧データ取得
     *
     * @param BlogPostsServiceInterface $service
     * @checked
     * @noTodo
     * @unitTest
     */
    public function index(BlogPostsServiceInterface $service)
    {
        $this->set([
            'blogPosts' => $this->paginate($service->getIndex($this->request->getQueryParams()))
        ]);
        $this->viewBuilder()->setOption('serialize', ['blogPosts']);
    }

    /**
     * [API] ブログ記事単一データ取得のAPI実装
     */
    public function view()
    {
        //todo ブログ記事単一データ取得のAPI実装
    }

    /**
     * [API] ブログ記事新規追加のAPI実装
     */
    public function add()
    {
        //todo ブログ記事新規追加のAPI実装
    }

    /**
     * [API] ブログ記事編集のAPI実装
     */
    public function edit()
    {
        //todo ブログ記事編集のAPI実装
    }

    /**
     * [API] ブログ記事複製のAPI実装
     *
     * @param BlogPostsServiceInterface $service
     * @param $id
     *
     * @checked
     * @noTodo
     * @unitTest
     */
    public function copy(BlogPostsServiceInterface $service, $id)
    {
        $this->request->allowMethod(['patch', 'post', 'put']);

        try {
            $blogPost = $service->get($id);
            $blogPostCopied = $service->copy($id);
            $message = __d('baser', 'ブログ記事「{0}」をコピーしました。', $blogPost->title);
        } catch (BcException $e) {
            $this->setResponse($this->response->withStatus(400));
            $blogPostCopied = $e->getEntity();
            $message = __d('baser', '入力エラーです。内容を修正してください。');
        }

        $this->set([
            'blogPost' => $blogPostCopied,
            'message' => $message,
            'errors' => $blogPostCopied->getErrors(),
        ]);

        $this->viewBuilder()->setOption('serialize', ['blogPost', 'message', 'errors']);
    }

    /**
     * [API] ブログ記事を公開状態に設定のAPI実装
     */
    public function publish()
    {
        //todo ブログ記事を公開状態に設定のAPI実装
    }

    /**
     * [API] ブログ記事を非公開状態に設定のAPI実装
     */
    public function unpublish()
    {
        //todo ブログ記事を非公開状態に設定のAPI実装
    }

    /**
     * ブログ記事のバッチ処理
     *
     * 指定したブログ記事に対して削除、公開、非公開の処理を一括で行う
     *
     * ### エラー
     * 受け取ったPOSTデータのキー名'batch'が'delete','publish','unpublish'以外の値であれば500エラーを発生させる
     *
     * @param BlogPostsServiceInterface $service
     * @checked
     * @noTodo
     */
    public function batch(BlogPostsServiceInterface $service)
    {
        $this->request->allowMethod(['post', 'put']);
        $allowMethod = [
            'delete' => '削除',
            'publish' => '公開',
            'unpublish' => '非公開に'
        ];
        $method = $this->getRequest()->getData('batch');
        if (!isset($allowMethod[$method])) {
            $this->setResponse($this->response->withStatus(500));
            $this->viewBuilder()->setOption('serialize', []);
            return;
        }
        $targets = $this->getRequest()->getData('batch_targets');
        try {
            $names = $service->getTitlesById($targets);
            $service->batch($method, $targets);
            $this->BcMessage->setSuccess(
                sprintf(__d('baser', 'ブログ記事「%s」を %s しました。'), implode('」、「', $names), $allowMethod[$method]),
                true,
                false
            );
            $message = __d('baser', '一括処理が完了しました。');
        } catch (BcException $e) {
            $this->setResponse($this->response->withStatus(400));
            $message = __d('baser', $e->getMessage());
        }
        $this->set(['message' => $message]);
        $this->viewBuilder()->setOption('serialize', ['message']);
    }

}
