<?php
/**
 * Created by PhpStorm.
 * User: mjz
 * Date: 17/11/2
 * Time: 上午12:01
 */

namespace frontend\models\search;

use common\helpers\Constants;
use common\models\NormalUser;
use common\models\records\User;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\web\NotFoundHttpException;

/**
 * 只显示与此用户相关的用户
 */
class UserSearch extends \yii\base\Model
{
    // 推荐的会员
    const STATUS_REFERRER = 1;
    // 未激活会员
    const STATUS_CHECKING = 2;
    // 已激活会员
    const STATUS_ACTIVED = 3;

    // 查询的用户状态
    public $status;
    // 用户账号
    public $user_id;
    // 查询开始时间
    public $start_time;
    // 查询结束时间
    public $end_time;
    // 推荐人账号
    public $referrer_id;
    public $username;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'status', 'referrer_id', 'username'], 'integer'],
            [['start_time', 'end_time'], 'date', 'format' => 'php:Y-m-d']
        ];
    }

    public function attributeLabels()
    {
        return [
            'status' => '状态',
            'user_id' => '用户账号',
            'username' => '用户账号',
            'referrer_id' => '推荐人账号',
            'start_time' => '起始时间',
            'end_time' => '结束时间',
        ];
    }

    public function formName()
    {
        return '';
    }

    public function search($params)
    {
        $query = NormalUser::find();
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 10,
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            $query->where('0=1');
            return $dataProvider;
        }

        $query->from(NormalUser::tableName());

        $query->andFilterWhere(['broker_id' => \Yii::$app->user->getId()])
            ->andFilterWhere(['username' => $this->username]);
        $query->andFilterWhere(['between', 'create_time', strtotime($this->start_time), strtotime($this->end_time . ' +1 day')]);

        return $dataProvider;
    }

    /**
     * 获取推荐的所有会员
     * @param $query ActiveQuery
     */
    private function getReferrerQuery($query)
    {
        $query->andWhere(['referrer_id' => \Yii::$app->user->identity->getId()]);
    }

    /**
     * 获取报单中心的所有会员，仅在当前用户已开通报单中心才可以
     * @param $query ActiveQuery
     */
    private function getBaodanMembers($query)
    {
        $baodanModel = \Yii::$app->user->identity->getBaodan();

        if ($baodanModel == null) {
            return;
        }

        $query->andWhere([
            'baodan_id' => $baodanModel->id,
            'is_actived' => $this->status == static::STATUS_ACTIVED ?
                Constants::NUMBER_TRUE : Constants::NUMBER_FALSE
        ]);
    }
}
