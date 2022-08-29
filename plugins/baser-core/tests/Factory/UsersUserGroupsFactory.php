<?php
declare(strict_types=1);

/**
 * baserCMS :  Based Website Development Project <https://basercms.net>
 * Copyright (c) NPO baser foundation <https://baserfoundation.org/>
 *
 * @copyright     Copyright (c) NPO baser foundation
 * @link          https://basercms.net baserCMS Project
 * @since         5.0.0
 * @license       https://basercms.net/license/index.html MIT License
 */

namespace BaserCore\Test\Factory;

use CakephpFixtureFactories\Factory\BaseFactory as CakephpBaseFactory;
use Faker\Generator;

/**
 * UsersUserGroupsFactory
 *
 * @method \BaserCore\Model\Entity\User getEntity()
 * @method \BaserCore\Model\Entity\User[] getEntities()
 * @method \BaserCore\Model\Entity\User|\BaserCore\Model\Entity\User[] persist()
 * @method static \BaserCore\Model\Entity\User get(mixed $primaryKey, array $options = [])
 */
class UsersUserGroupsFactory extends CakephpBaseFactory
{
    /**
     * Defines the Table Registry used to generate entities with
     *
     * @return string
     */
    protected function getRootTableRegistryName(): string
    {
        return 'BaserCore.UsersUserGroups';
    }

    /**
     * Defines the factory's default values. This is useful for
     * not nullable fields. You may use methods of the present factory here too.
     *
     * @return void
     */
    protected function setDefaultTemplate(): void
    {
        $this->setDefaultData(function (Generator $faker) {
            return [
            ];
        });
    }

    /**
     * 管理者ユーザーを作成する
     * @return UserFactory
     */
    public function admin()
    {
        return $this->setField('user_id', 1)
            ->setField('user_group_id', 1);
    }
}
