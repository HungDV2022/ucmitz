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

namespace BaserCore\Controller\Admin;

use BaserCore\Error\BcException;
use BaserCore\Service\UtilitiesAdminServiceInterface;
use BaserCore\Service\UtilitiesServiceInterface;
use BaserCore\Utility\BcUtil;
use BaserCore\Annotation\UnitTest;
use BaserCore\Annotation\NoTodo;
use BaserCore\Annotation\Checked;
use Cake\Core\Configure;

/**
 * Class UtilitiesController
 * @package BaserCore\Controller\Admin
 */
class UtilitiesController extends BcAdminAppController
{

    /**
     * initialize
     * @return void
     * @checked
     * @noTodo
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->Authentication->allowUnauthenticated(['credit']);
    }

    /**
     * サーバーキャッシュを削除する
     * @checked
     * @unitTest
     * @noTodo
     */
    public function clear_cache()
    {
        $this->_checkReferer();
        BcUtil::clearAllCache();
        $this->BcMessage->setInfo(__d('baser', 'サーバーキャッシュを削除しました。'));
        $this->redirect($this->referer());
    }

    /**
     * 検索ボックスの表示状態を保存する
     *
     * @param string $key キー
     * @param mixed $open 1 Or ''
     * @return void
     */
    public function ajax_save_search_box($key, $open = '')
    {
        $this->autoRender = false;
        $this->request->getSession()->write('BcApp.adminSearchOpened.' . $key, $open);
        echo true;
    }

    /**
     * ユーティリティトップ
     * @checked
     * @noTodo
     */
    public function index()
    {
    }

    /**
     * 環境情報を表示する
     */
    public function info(UtilitiesAdminServiceInterface $service)
    {
        $this->set($service->getViewVarsForInfo());
    }

    /**
     * phpinfo を表示する
     * 環境情報の表示から iframe で読み込まれる
     * @checked
     * @unitTest
     * @uses phpinfo
     */
    public function phpinfo()
    {
        $this->_checkReferer();
        $this->autoRender = false;
        phpinfo();
        exit();
    }

    /**
     * データメンテナンス
     *
     * @param string $mode
     */
    public function maintenance($mode = '')
    {
        $this->_checkReferer();
        switch($mode) {
            case 'backup':
                set_time_limit(0);
                $this->_backupDb($this->request->getQuery('backup_encoding'));
                break;
            case 'restore':
                set_time_limit(0);
                $messages = [];
                if (!$this->request->getData()) {
                    if ($this->Tool->isOverPostSize()) {
                        $messages[] = __d('baser', '送信できるデータ量を超えています。合計で %s 以内のデータを送信してください。', ini_get('post_max_size'));
                    } else {
                        $this->notFound();
                    }
                }
                if ($this->_restoreDb($this->request->getData())) {
                    $messages[] = __d('baser', 'データの復元が完了しました。');
                    $error = false;
                } else {
                    $messages[] = __d('baser', 'データの復元に失敗しました。ログの確認を行なって下さい。');
                    $error = true;
                }
                // Pageモデルがレストア処理でAppModelで初期化されClassRegistryにセットされている為
                ClassRegistry::flush();
                if (!$error && !$this->Page->createAllPageTemplate()) {
                    $messages[] = __d('baser', "ページテンプレートの生成に失敗しました。\n表示できないページはページ管理より更新処理を行ってください。");
                }
                if ($messages) {
                    if ($error) {
                        $this->BcMessage->setError(implode("\n", $messages));
                    } else {
                        $this->BcMessage->setInfo(implode("\n", $messages));
                    }
                }
                BcUtil::clearAllCache();
                $this->redirect(['action' => 'maintenance']);
                break;
        }
        $this->setTitle(__d('baser', 'データメンテナンス'));
        $this->setHelp('tools_maintenance');
    }

    /**
     * バックアップファイルを復元する
     *
     * @param array $data
     * @return boolean
     */
    protected function _restoreDb($data)
    {

        if (empty($data['Tool']['backup']['tmp_name'])) {
            return false;
        }

        $tmpPath = TMP . 'schemas' . DS;
        $targetPath = $tmpPath . $data['Tool']['backup']['name'];

        if (!move_uploaded_file($data['Tool']['backup']['tmp_name'], $targetPath)) {
            return false;
        }

        /* ZIPファイルを解凍する */
        $Simplezip = new Simplezip();
        if (!$Simplezip->unzip($targetPath, $tmpPath)) {
            return false;
        }
        @unlink($targetPath);

        $result = true;
        $db = ConnectionManager::getDataSource('default');
        $db->begin();
        if (!$this->_loadBackup($tmpPath . 'core' . DS, $data['Tool']['encoding'])) {
            $result = false;
        }
        if (!$this->_loadBackup($tmpPath . 'plugin' . DS, $data['Tool']['encoding'])) {
            $result = false;
        }
        if ($result) {
            $db->commit();
        } else {
            $db->rollback();
        }
        $this->_resetTmpSchemaFolder();
        BcUtil::clearAllCache();

        return $result;
    }

    /**
     * データベースをレストア
     *
     * @param string $path スキーマファイルのパス
     * @param $encoding
     * @return boolean
     */
    protected function _loadBackup($path, $encoding)
    {
        $Folder = new Folder($path);
        $files = $Folder->read(true, true);
        if (!is_array($files[1])) {
            return false;
        }

        $db = ConnectionManager::getDataSource('default');
        $result = true;
        /* テーブルを削除する */
        foreach($files[1] as $file) {
            if (preg_match("/\.php$/", $file)) {
                try {
                    if (!$db->loadSchema(['type' => 'drop', 'path' => $path, 'file' => $file])) {
                        $result = false;
                        continue;
                    }
                } catch (Exception $e) {
                    $result = false;
                    $this->log($e->getMessage());
                }
            }
        }

        /* テーブルを読み込む */
        foreach($files[1] as $file) {
            if (preg_match("/\.php$/", $file)) {
                try {
                    if (!$db->loadSchema(['type' => 'create', 'path' => $path, 'file' => $file])) {
                        $result = false;
                        continue;
                    }
                } catch (Exception $e) {
                    $result = false;
                    $this->log($e->getMessage());
                }
            }
        }

        /* CSVファイルを読み込む */
        foreach($files[1] as $file) {
            if (preg_match("/\.csv$/", $file)) {
                try {
                    if (!$db->loadCsv(['path' => $path . $file, 'encoding' => $encoding])) {
                        $result = false;
                        continue;
                    }
                } catch (Exception $e) {
                    $result = false;
                    $this->log($e->getMessage());
                }
            }
        }

        return $result;
    }

    /**
     * バックアップデータを作成する
     *
     * @return void
     */
    protected function _backupDb($encoding)
    {
        $tmpDir = TMP . 'schemas' . DS;
        $version = str_replace(' ', '_', BcUtil::getVersion());
        $this->_resetTmpSchemaFolder();
        BcUtil::clearAllCache();
        $this->_writeBackup($tmpDir . 'core' . DS, '', $encoding);
        $Plugin = ClassRegistry::init('Plugin');
        $plugins = $Plugin->find('all');
        if ($plugins) {
            foreach($plugins as $plugin) {
                $this->_writeBackup($tmpDir . 'plugin' . DS, $plugin['Plugin']['name'], $encoding);
            }
        }
        // ZIP圧縮して出力
        $fileName = 'baserbackup_' . $version . '_' . date('Ymd_His');
        $Simplezip = new Simplezip();
        $Simplezip->addFolder($tmpDir);
        $Simplezip->download($fileName);
        $this->_resetTmpSchemaFolder();
        exit();
    }

    /**
     * バックアップファイルを書きだす
     *
     * @param string $path
     * @param string $plugin
     * @param $encoding
     * @return boolean
     */
    protected function _writeBackup($path, $plugin, $encoding)
    {
        $db = ConnectionManager::getDataSource('default');
        $db->cacheSources = false;
        $tables = $db->listSources();
        $tableList = getTableList();
        foreach($tables as $table) {
            if ((!$plugin && in_array($table, $tableList['core']) || ($plugin && in_array($table, $tableList['plugin'])))) {
                $table = str_replace($db->config['prefix'], '', $table);
                if (!$db->writeSchema(['path' => $path, 'table' => $table, 'plugin' => $plugin])) {
                    return false;
                }
                if (!$db->writeCsv(['path' => $path . $table . '.csv', 'encoding' => $encoding])) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * モデル名からスキーマファイルを生成する
     *
     * @return void
     */
    public function write_schema()
    {
        $path = TMP . 'schemas' . DS;

        /* 表示設定 */
        $this->setTitle(__d('baser', 'スキーマファイル生成'));
        $this->setHelp('tools_write_schema');

        if (!$this->request->getData()) {
            $this->request = $this->request->withData('Tool.connection', 'core');
            return;
        }

        if (empty($this->request->getData('Tool'))) {
            $this->BcMessage->setError(__d('baser', 'テーブルを選択してください。'));
            return;
        }

        if (!$this->_resetTmpSchemaFolder()) {
            $this->BcMessage->setError('フォルダ：' . $path . ' が存在するか確認し、存在する場合は、削除するか書込権限を与えてください。');
            $this->redirect(['action' => 'write_schema']);
        }
        if (!$this->Tool->writeSchema($this->request->getData(), $path)) {
            $this->BcMessage->setError(__d('baser', 'スキーマファイルの生成に失敗しました。'));
            return;
        }

        $Simplezip = new Simplezip();
        $Simplezip->addFolder($path);
        $Simplezip->download('schemas');
        exit();
    }

    /**
     * スキーマファイルを読み込みテーブルを生成する
     *
     * @return void
     */
    public function load_schema()
    {
        /* 表示設定 */
        $this->setTitle(__d('baser', 'スキーマファイル読込'));
        $this->setHelp('tools_load_schema');
        if (!$this->request->is(['post', 'put'])) {
            $this->request = $this->request->withData('Tool.schema_type', 'create');
            return;
        }

        if ($this->Tool->isOverPostSize()) {
            $this->BcMessage->setError(
                __d('baser', '送信できるデータ量を超えています。合計で %s 以内のデータを送信してください。', ini_get('post_max_size'))
            );
            $this->redirect(['action' => 'load_schema']);
        }
        if (!is_uploaded_file($this->request->getData('Tool.schema_file.tmp_name'))) {
            $this->BcMessage->setError(__d('baser', 'ファイルアップロードに失敗しました。'));
            return;
        }

        $path = TMP . 'schemas' . DS;
        if (!$this->_resetTmpSchemaFolder()) {
            $this->BcMessage->setError('フォルダ：' . $path . ' が存在するか確認し、存在する場合は、削除するか書込権限を与えてください。');
            $this->redirect(['action' => 'load_schema']);
        }
        if (!$this->Tool->loadSchemaFile($this->request->getData(), $path)) {
            $this->BcMessage->setError(__d('baser', 'スキーマファイルの読み込みに失敗しました。'));
            return;
        }

        $this->BcMessage->setInfo(__d('baser', 'スキーマファイルの読み込みに成功しました。'));
        $this->redirect(['action' => 'load_schema']);
    }

    /**
     * スキーマ用の一時フォルダをリセットする
     *
     * @return boolean
     */
    protected function _resetTmpSchemaFolder()
    {
        $path = TMP . 'schemas' . DS;
        return emptyFolder($path);
    }

    /**
     * ログメンテナンス
     *
     * @param string $mode
     * @return void
     * @uses log_maintenance
     */
    public function log_maintenance(
        UtilitiesServiceInterface $service,
        UtilitiesAdminServiceInterface $adminService,
        $mode = '')
    {
        switch($mode) {
            case 'download':
                $this->autoRender = false;
                $result = $service->createLogZip();
                if ($result) {
                    $result->download('basercms_logs_' . date('Ymd_His'));
                    return;
                }
                $this->BcMessage->setInfo('エラーログが存在しません。');
                $this->redirect(['action' => 'log_maintenance']);
                break;
            case 'delete':
                $this->request->allowMethod(['post']);
                try {
                    $service->deleteLog();
                    $this->BcMessage->setInfo(__d('baser', 'エラーログを削除しました。'));
                } catch (BcException $e) {
                    $this->BcMessage->setError($e->getMessage());
                }
                $this->redirect(['action' => 'log_maintenance']);
                break;
        }
        $this->set($adminService->getViewVarsForLogMaintenance());
    }

    /**
     * コンテンツ管理のツリー構造をリセットする
     * @param UtilitiesServiceInterface $service
     * @checked
     * @noTodo
     * @uses reset_contents_tree
     */
    public function reset_contents_tree(UtilitiesServiceInterface $service)
    {
        $this->request->allowMethod(['post']);
        if ($service->resetContentsTree()) {
            $this->BcMessage->setSuccess(__d('baser', 'コンテンツのツリー構造をリセットしました。'));
        } else {
            $this->BcMessage->setError(__d('baser', 'コンテンツのツリー構造のリセットに失敗しました。'));
        }
        $this->redirect(['action' => 'index']);
    }

    /**
     * コンテンツ管理のツリー構造のチェックを行う
     *
     * 問題がある場合にはログを出力する
     * @param UtilitiesServiceInterface $service
     * @uses verity_contents_tree
     * @checked
     * @noTodo
     */
    public function verity_contents_tree(UtilitiesServiceInterface $service)
    {
        $this->request->allowMethod(['post']);
        if (!$service->verityContentsTree()) {
            $this->BcMessage->setError(__d('baser', 'コンテンツのツリー構造に問題があります。ログを確認してください。'));
        } else {
            $this->BcMessage->setSuccess(__d('baser', 'コンテンツのツリー構造に問題はありません。'), false);
        }
        $this->redirect(['action' => 'index']);
    }

    /**
     * クレジット表示用データをレンダリング
     * @param UtilitiesServiceInterface $service
     * @checked
     * @noTodo
     */
    public function credit(UtilitiesServiceInterface $service)
    {
        $this->viewBuilder()->disableAutoLayout();
        try {
            $this->set('credits', $service->getCredit());
        } catch (BcException $e) {
            $this->setResponse($this->response->withStatus(400)->withStringBody($e->getMessage()));
        }
    }

}
