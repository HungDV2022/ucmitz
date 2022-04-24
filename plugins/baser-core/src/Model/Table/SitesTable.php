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

namespace BaserCore\Model\Table;

use ArrayObject;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use BaserCore\Model\AppTable;
use BaserCore\Utility\BcLang;
use BaserCore\Utility\BcUtil;
use BaserCore\Annotation\Note;
use BaserCore\Utility\BcAgent;
use Cake\Event\EventInterface;
use Cake\Validation\Validator;
use BaserCore\Annotation\NoTodo;
use BaserCore\Model\Entity\Site;
use BaserCore\Annotation\Checked;
use BaserCore\Annotation\UnitTest;
use Cake\Datasource\EntityInterface;
use BaserCore\Utility\BcContainerTrait;
use Cake\Datasource\ResultSetInterface;
use BaserCore\Model\Table\ContentsTable;
use BaserCore\Service\SiteConfigService;
use BaserCore\Utility\BcAbstractDetector;
use BaserCore\Event\BcEventDispatcherTrait;
use BaserCore\Service\ContentServiceInterface;
use BaserCore\Service\ContentFolderServiceInterface;

/**
 * Class Site
 *
 * サイトモデル
 * @method Site newEntity($data = null, array $options = [])
 * @package Baser.Model
 */
class SitesTable extends AppTable
{

    /**
     * Trait
     */
    use BcEventDispatcherTrait;
    use BcContainerTrait;

    /**
     * Contents
     *
     * @var ContentsTable $Contents
     */
    public $Contents;

    /**
     * 保存時にエイリアスが変更されたかどうか
     *
     * @var bool
     */
    private $changedAlias = false;

    /**
     * Initialize
     *
     * @param array $config テーブル設定
     * @return void
     * @checked
     * @noTodo
     * @unitTest
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('sites');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->setDisplayField('display_name');
        $this->Contents = TableRegistry::getTableLocator()->get("BaserCore.Contents");
    }

    /**
     * Validation Default
     *
     * @param Validator $validator
     * @return Validator
     * @checked
     * @noTodo
     * @unitTest
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator->setProvider('site', 'BaserCore\Model\Validation\SiteValidation');

        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create');
        $validator
            ->scalar('name')
            ->maxLength('name', 50, __d('baser', '識別名称は50文字以内で入力してください。'))
            ->requirePresence('name', 'create', __d('baser', '識別名称を入力してください。'))
            ->notEmptyString('name', __d('baser', '識別名称を入力してください。'))
            ->add('name', [
                'nameUnique' => [
                    'rule' => 'validateUnique',
                    'provider' => 'table',
                    'message' => __d('baser', '既に利用されている識別名称です。別の名称に変更してください。')
                ]])
            ->add('name', [
                'nameAlphaNumericPlus' => [
                    'rule' => ['alphaNumericPlus'],
                    'provider' => 'bc',
                    'message' => __d('baser', '識別名称は、半角英数・ハイフン（-）・アンダースコア（_）で入力してください。')
                ]]);
        $validator
            ->scalar('display_name')
            ->maxLength('display_name', 50, __d('baser', 'サイト名は50文字以内で入力してください。'))
            ->requirePresence('display_name', 'create', __d('baser', 'サイト名を入力してください。'))
            ->notEmptyString('display_name', __d('baser', 'サイト名を入力してください。'));
        $validator
            ->scalar('alias')
            ->maxLength('alias', 50, __d('baser', 'エイリアスは50文字以内で入力してください。'))
            ->requirePresence('alias', 'create', __d('baser', 'エイリアスを入力してください。'))
            ->notEmptyString('alias', __d('baser', 'エイリアスを入力してください。'))
            ->add('alias', [
                'aliasUnique' => [
                    'rule' => 'validateUnique',
                    'provider' => 'table',
                    'message' => __d('baser', '既に利用されているエイリアス名です。別の名称に変更してください。')
                ]])
            ->add('alias', [
                'aliasSlashChecks' => [
                    'rule' => 'aliasSlashChecks',
                    'provider' => 'site',
                    'message' => __d('baser', 'エイリアスには先頭と末尾にスラッシュ（/）は入力できず、また、連続して入力する事もできません。')
                ]]);
        $validator
            ->scalar('title')
            ->maxLength('title', 255, __d('baser', 'サイトタイトルは255文字以内で入力してください。'))
            ->requirePresence('title', 'create', __d('baser', 'サイトタイトルを入力してください。'))
            ->notEmptyString('title', __d('baser', 'サイトタイトルを入力してください。'));
        return $validator;
    }

    /**
     * 公開されている全てのサイトを取得する
     *
     * @return ResultSetInterface
     * @noTodo
     * @checked
     * @unitTest
     */
    public function getPublishedAll(): ResultSetInterface
    {
        return $this->find()->where(['status' => true])->all();
    }

    /**
     * サイトリストを取得
     *
     * @param bool $mainSiteId メインサイトID
     * @param array $options
     *  - `excludeIds` : 除外するID（初期値：なし）
     *  - `status` : 有効かどうか（初期値：true）
     * @return array
     * @checked
     * @noTodo
     * @unitTest
     */
    public function getList($mainSiteId = null, $options = [])
    {
        $options = array_merge([
            'excludeIds' => [],
            'status' => true
        ], $options);

        // EVENT Site.beforeGetSiteList
        $event = $this->dispatchLayerEvent('beforeGetSiteList', [
            'options' => $options
        ]);
        if ($event !== false) {
            $options = $event->getResult() === true? $event->getData('options') : $event->getResult();
        }

        $conditions = [];
        if (!is_null($options['status'])) {
            $conditions = ['status' => $options['status']];
        }

        if (!is_null($mainSiteId)) {
            $conditions['main_site_id'] = $mainSiteId;
        }

        if (isset($options['excludeIds'])) {
            if (!is_array($options['excludeIds'])) {
                $options['excludeIds'] = [$options['excludeIds']];
            }
            $excludeKey = array_search(0, $options['excludeIds']);
            if ($excludeKey !== false) {
                unset($options['excludeIds'][$excludeKey]);
            }
            if ($options['excludeIds']) {
                $conditions[]['id NOT IN'] = $options['excludeIds'];
            }
        }

        if (isset($options['includeIds'])) {
            if (!is_array($options['includeIds'])) {
                $options['includeIds'] = [$options['includeIds']];
            }
            $includeKey = array_search(0, $options['includeIds']);
            if ($includeKey !== false) {
                unset($options['includeIds'][$includeKey]);
            }
            if ($options['includeIds']) {
                $conditions[]['id IN'] = $options['includeIds'];
            }
        }
        $this->setDisplayField('display_name');
        return $this->find('list')->where($conditions)->toArray();
    }

    /**
     * メインサイトのデータを取得する
     *
     * @param mixed $options
     *  - `fields` : 取得するフィールド
     * @return EntityInterface
     * @checked
     * @noTodo
     * @unitTest
     */
    public function getRootMain($options = [])
    {
        return $this->find()->where(['main_site_id IS' => null])->first();
    }

    /**
     * コンテンツに関連したコンテンツをサイト情報と一緒に全て取得する
     *
     * @param $contentId
     * @return array|null $list
     * @checked
     * @noTodo
     * @unitTest
     */
    public function getRelatedContents($contentId)
    {
        $content = $this->Contents->get($contentId, ['contain' => ['Sites']]);
        $isMainSite = $this->isMain($content->site->id);
        $fields = ['id', 'name', 'alias', 'display_name', 'main_site_id'];
        $conditions = ['Sites.status' => true];
        if (is_null($content->site->main_site_id)) {
            $mainSiteContentId = $content->id;
            $conditions['or'] = [
                ['Sites.id' => $content->site->id],
                ['Sites.main_site_id' => $this->getRootMain()->id]
            ];
        } else {
            $conditions['or'] = [
                    ['Sites.main_site_id' => $content->site->main_site_id],
                    ['Sites.id' => $content->site->main_site_id]
            ];
            if ($isMainSite) {
                $conditions['or'][] = ['Site.main_site_id' => $content->site->id];
            }
            $mainSiteContentId = $content->main_site_content_id ?? $content->id;
        }
        $sites = $this->find()->select($fields)->where($conditions)->order('main_site_id')->toArray();
        $conditions = [
            'or' => [
                ['Contents.id' => $mainSiteContentId],
                ['Contents.main_site_content_id' => $mainSiteContentId]
            ]
        ];
        $list= [];
        $relatedContents = $this->Contents->find()->where($conditions)->toArray();
        foreach($sites as $key => $site) {
            foreach($relatedContents as $relatedContent) {
                $list[$key]['Site'] = $site;
                if ($relatedContent->site_id == $site->id) {
                    $list[$key]['Content'] = $relatedContent;
                    break;
                }
            }
        }
        return $list;
    }

    /**
     * メインサイトかどうか判定する
     *
     * @param int $id
     * @return bool
     * @checked
     * @noTodo
     * @unitTest
     */
    public function isMain(int $id)
    {
        return !$this->find()->where(['main_site_id' => $id])->all()->isEmpty();
    }

    /**
     * サイトを取得する
     *
     * @param $id
     * @param array $options
     * @return ResultSetInterface
     * @checked
     * @noTodo
     * @unitTest
     */
    public function children($id, $options = [])
    {
        $options = array_merge_recursive([
            'conditions' => [
                'main_site_id' => $id
            ]
        ], $options);
        return $this->find()->where($options['conditions'])->all();
    }

    /**
     * After Save
     *
     * @param EventInterface $event
     * @param EntityInterface $entity
     * @param ArrayObject $options
     * @checked
     * @noTodo
     * @unitTest
     */
    public function afterSave(EventInterface $event, EntityInterface $entity, ArrayObject $options)
    {
        $contentFolderService = $this->getService(ContentFolderServiceInterface::class);
        $contentFolderService->saveSiteRoot($entity, $this->changedAlias);
        $this->getEventManager()->off('Model.beforeSave');
        $this->getEventManager()->off('Model.afterSave');
        if (!empty($entity->main)) {
            $site = $this->find()->where(['Site.main' => true, 'Site.id <>' => $this->id])->first();
            if ($site) {
                $site->main = false;
                $this->save($site, ['validate' => false]);
            }
        }
        $this->changedAlias = false;
    }

    /**
     * After Delete
     *
     * @param EventInterface $event
     * @param EntityInterface $entity
     * @param ArrayObject $options
     * @checked
     * @noTodo
     * @unitTest
     */
    public function afterDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options)
    {
        $contentService = $this->getService(ContentServiceInterface::class);
        $content = $this->Contents->find()->where(['Contents.site_id' => $entity->id, 'Contents.site_root' => true])->first();

        $children = $contentService->getChildren($content->id);
        if (isset($children)) {
            foreach($children as $child) {
                $child->site_id = 1;
                // バリデートすると name が変換されてしまう
                $this->Contents->getEventManager()->off('Model.afterSave');
                $this->Contents->save($child, false);
            }
            $children = $contentService->getChildren($content->id);
            foreach($children as $child) {
                $contentService->deleteRecursive($child->id);
            }
        }
        if (!$this->Contents->hardDel($content)) return false;
    }

    /**
     * プレフィックスを取得する
     *
     * @param mixed $id | $data
     * @return false|string
     * @checked
     * @noTodo
     * @unitTest
     */
    public function getPrefix($id)
    {
        if (is_null($id)) return '';

        $site = $this->find()->select(['name', 'alias'])->where(['id' => $id])->first();
        if (!$site) {
            return false;
        }
        $prefix = $site->name;
        if ($site->alias) {
            $prefix = $site->alias;
        }
        return $prefix;
    }

    /**
     * サイトのルートとなるコンテンツIDを取得する
     *
     * @param $id
     * @return int
     * @checked
     * @noTodo
     * @unitTest
     */
    public function getRootContentId($id)
    {
        if ($id == 0) {
            return 1;
        }
        $Contents = TableRegistry::getTableLocator()->get('BaserCore.Contents');
        $contents = $Contents->find()->select(['id'])->where(['Contents.site_root' => true, 'Contents.site_id' => $id]);
        if (!$contents->all()->isEmpty()) return $contents->first()->id;
    }

    /**
     * URLよりサイトを取得する
     * TODO: テストがエラーになる
     *
     * @param string $url
     * @return EntityInterface
     * @checked
     * @noTodo
     * @unitTest
     */
    public function findByUrl($url)
    {
        if (!$url) {
            $url = '/';
        }
        $parseUrl = parse_url($url);
        if (empty($parseUrl['path'])) {
            return $this->getRootMain();
        }
        $url = $parseUrl['path'];
        $url = preg_replace('/(^\/|\/$)/', '', $url);
        $urlAry = explode('/', $url);
        $domain = BcUtil::getCurrentDomain();
        $subDomain = BcUtil::getSubDomain();
        $where = [];
        for($i = count($urlAry); $i > 0; $i--) {
            $where['or'][] = ['alias' => implode('/', $urlAry)];
            if ($subDomain) {
                $where['or'][] = [
                    'domain_type' => 1,
                    'alias' => $subDomain . '/' . implode('/', $urlAry),
                ];
            }
            if ($domain) {
                $where['or'][] = [
                    'domain_type' => 2,
                    'alias' => $domain . '/' . implode('/', $urlAry),
                ];
            }
            unset($urlAry[$i - 1]);
        }
        if ($subDomain) {
            $where['or'][] = [
                'domain_type' => 1,
                'alias' => $subDomain,
            ];
        }
        if ($domain) {
            $where['or'][] = [
                'domain_type' => 2,
                'alias' => $domain,
            ];
        }
        $result = $this->find()->where($where)->order(['alias DESC']);
        if ($result->count()) {
            return $result->first();
        } else {
            return $this->getRootMain();
        }
    }

    /**
     * URLに関連するメインサイトを取得する
     * @param $url
     * @return array|EntityInterface|null
     * @checked
     * @noTodo
     * @unitTest
     */
    public function getMainByUrl($url)
    {
        $site = $this->findByUrl($url);
        if ($site->main_site_id) {
            return $this->find()->where(['id' => $site->main_site_id])->first();
        }
        return null;
    }

    /**
     * URLに関連するサブサイトを取得する
     * @param $url
     * @param false $sameMainUrl
     * @param BcAbstractDetector|null $agent
     * @param BcAbstractDetector|null $lang
     * @return mixed|null
     * @checked
     * @noTodo
     * @unitTest
     */
    public function getSubByUrl(
        $url,
        $sameMainUrl = false,
        BcAbstractDetector $agent = null,
        BcAbstractDetector $lang = null
    )
    {
        $SiteConfigService = new SiteConfigService();
        $currentSite = $this->findByUrl($url);
        $sites = $this->find()->all();

        if (!$lang) {
            $lang = BcLang::findCurrent();
        }
        if (!$agent) {
            $agent = BcAgent::findCurrent();
        }

        // 言語の一致するサイト候補に絞り込む
        $langSubSites = [];
        if ($lang && $SiteConfigService->getValue('use_site_lang_setting')) {
            foreach($sites as $site) {
                if (!$site->status) {
                    continue;
                }
                if (!$sameMainUrl || ($sameMainUrl && $site->same_main_url)) {
                    if ($site->lang == $lang->name && $currentSite->id == $site->main_site_id) {
                        $langSubSites[] = $site;
                        break;
                    }
                }
            }
        }
        if ($langSubSites) {
            $subSites = $langSubSites;
        } else {
            $subSites = $sites;
        }
        if ($agent && $SiteConfigService->getValue('use_site_device_setting')) {
            foreach($subSites as $subSite) {
                if (!$subSite->status) {
                    continue;
                }
                if (!$sameMainUrl || ($sameMainUrl && $subSite->same_main_url)) {
                    if ($subSite->device == $agent->name && $currentSite->id == $subSite->main_site_id) {
                        return $subSite;
                    }
                }
            }
        }
        if ($langSubSites) {
            return $langSubSites[0];
        }
        return null;
    }

    /**
     * メインサイトを取得する
     *
     * @param int $id
     * @return EntityInterface|false
     * @checked
     * @noTodo
     * @unitTest
     */
    public function getMain($id)
    {
        $currentSite = $this->find()->where(['id' => $id])->first();
        if (!$currentSite) {
            return false;
        }
        if (is_null($currentSite->main_site_id)) {
            return $this->getRootMain();
        }
        $mainSite = $this->find()->where([
            'id' => $currentSite->main_site_id
        ])->first();
        if (!$mainSite) {
            return false;
        }
        return $mainSite;
    }

    /**
     * 選択可能なデバイスの一覧を取得する
     *
     * 現在のサイトとすでに利用されいているデバイスは除外する
     *
     * @param int $mainSiteId メインサイトID
     * @param int $currentSiteId 現在のサイトID
     * @return array
     * @checked
     * @noTodo
     * @unitTest
     */
    public function getSelectableDevices($mainSiteId, $currentSiteId)
    {
        $agents = Configure::read('BcAgent');
        $devices = ['' => __d('baser', '指定しない')];
        $this->setDisplayField('device');
        $conditions = [
            'id IS NOT' => $currentSiteId
        ];
        if($mainSiteId) {
            $conditions['main_site_id'] = $mainSiteId;
        } else {
            $conditions['main_site_id IS'] = null;
        }
        $selected = $this->find('list')
            ->where($conditions)->toArray();
        foreach($agents as $key => $agent) {
            if (in_array($key, $selected)) {
                continue;
            }
            $devices[$key] = $agent['name'];
        }
        return $devices;
    }

    /**
     * 選択可能な言語の一覧を取得する
     *
     * @param int $mainSiteId メインサイトID
     * @param int $currentSiteId 現在のサイトID
     * @return array
     * @checked
     * @noTodo
     * @unitTest
     */
    public function getSelectableLangs($mainSiteId, $currentSiteId)
    {
        $langs = Configure::read('BcLang');
        $devices = ['' => __d('baser', '指定しない')];
        $this->setDisplayField('lang');
        $conditions = [
            'id IS NOT' => $currentSiteId
        ];
        if($mainSiteId) {
            $conditions['main_site_id'] = $mainSiteId;
        } else {
            $conditions['main_site_id IS'] = null;
        }
        $selected = $this->find('list')
            ->where($conditions)->toArray();
        foreach($langs as $key => $lang) {
            if (in_array($key, $selected)) {
                continue;
            }
            $devices[$key] = $lang['name'];
        }
        return $devices;
    }

    /**
     * デバイス設定をリセットする
     *
     * @return bool
     * @checked
     * @noTodo
     * @unitTest
     */
    public function resetDevice()
    {
        $sites = $this->find()->all();
        $result = true;
        if ($sites) {
            $this->getConnection()->begin();
            foreach($sites as $site) {
                $site->device = '';
                $site->auto_link = false;
                if (!$site->lang) {
                    $site->same_main_url = false;
                    $site->auto_redirect = false;
                }
                if (!$this->save($site)) {
                    $result = false;
                }
            }
        }
        if (!$result) {
            $this->getConnection()->rollback();
        } else {
            $this->getConnection()->commit();
        }
        return $result;
    }

    /**
     * 言語設定をリセットする
     *
     * @return bool
     * @checked
     * @noTodo
     * @unitTest
     */
    public function resetLang()
    {
        $sites = $this->find()->all();
        $result = true;
        if ($sites) {
            $this->getConnection()->begin();
            foreach($sites as $site) {
                $site->lang = '';
                if (!$site->device) {
                    $site->same_main_url = false;
                    $site->auto_redirect = false;
                }
                if (!$this->save($site)) {
                    $result = false;
                }
            }
        }
        if (!$result) {
            $this->getConnection()->rollback();
        } else {
            $this->getConnection()->commit();
        }
        return $result;
    }

    /**
     * Before Save
     *
     * @param EventInterface $event
     * @param EntityInterface $entity
     * @param ArrayObject $options
     * @return bool
     * @checked
     * @noTodo
     * @unitTest
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options)
    {
        // エイリアスに変更があったかチェックする
        if ($entity->id && $entity->alias) {
            $oldSite = $this->find()->where(['id' => $entity->id])->first();
            if ($oldSite && $oldSite->alias !== $entity->alias) {
                $this->changedAlias = true;
            }
        }
        return true;
    }

}
