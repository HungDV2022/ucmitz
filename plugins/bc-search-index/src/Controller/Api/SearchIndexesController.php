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

namespace BcSearchIndex\Controller\Api;

use BaserCore\Controller\AppController;
use BaserCore\Error\BcException;
use BcSearchIndex\Service\SearchIndexesServiceInterface;
use Cake\Event\EventInterface;
use BaserCore\Annotation\UnitTest;
use BaserCore\Annotation\NoTodo;
use BaserCore\Annotation\Checked;

/**
 * SearchIndicesController
 */
class SearchIndexesController extends AppController
{

    /**
     * Before filter
     * @param EventInterface $event
     * @return \Cake\Http\Response|void
     * @checked
     * @noTodo
     * @unitTest
     */
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
        $this->Security->setConfig('validatePost', false);
    }

    /**
     * [AJAX] 優先順位を変更する
     * @checked
     * @noTodo
     * @unitTest
     */
    public function change_priority(SearchIndexesServiceInterface $service, $id)
    {
        $this->request->allowMethod(['post', 'put']);
        $searchIndex = $service->get($id);
        try {
            $searchIndex = $service->changePriority(
                $searchIndex,
                $this->getRequest()->getData('priority')
            );
            $message = __d('baser', '検索インデックス「{0}」の優先度を変更しました。', $searchIndex->title);
        } catch (BcException $e) {
            $this->setResponse($this->response->withStatus(400));
            $message = __d('baser', '検索インデックスの優先度の変更に失敗しました。');
        }
        $this->set([
            'message' => $message,
            'searchIndex' => $searchIndex,
        ]);
        $this->viewBuilder()->setOption('serialize', ['searchIndex', 'message']);
    }

    /**
     * バッチ処理
     *
     * @param SearchIndexesServiceInterface $service
     * @checked
     * @noTodo
     */
    public function batch(SearchIndexesServiceInterface $service)
    {
        $this->request->allowMethod(['post', 'put']);
        if($this->{'_batch_' . $this->getRequest()->getData('ListTool.batch')}(
            $service,
            $this->getRequest()->getData('ListTool.batch_targets')
        )) {
            $this->response->withStringBody('true');
        }
        $this->viewBuilder()->setOption('serialize', []);
    }

    /**
     * [ADMIN] 検索インデックス一括削除
     *
     * @param $ids
     * @return bool
     * @checked
     * @noTodo
     */
    protected function _batch_del($service, $ids)
    {
        if (!$ids) {
            return true;
        }
        foreach($ids as $id) {
            if ($service->delete($id)) {
                $this->BcMessage->setSuccess(
                    sprintf(__d('baser', '検索インデックスより NO.%s を削除しました。'), $id),
                    true,
                    false
                );
            }
        }
        return true;
    }
}
