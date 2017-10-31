<?php
/**
 * Created by PhpStorm.
 * User: mjz
 * Date: 17/10/16
 * Time: 下午6:46
 */

namespace backend\models\search;


use common\models\NormalUser;
use common\models\records\TransactionLog;
use common\models\records\User;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Expression;
use yii\db\Query;

// 财务结算
class FinanceSearch extends Model
{

    const DETAIL_TYPE_ALL = 1;
    const DETAIL_TYPE_DAILY = 2;
    const DETAIL_TYPE_DAILY_USER = 3;

    public $start_time;
    // 查询结束时间
    public $end_time;

    public $detail_time;
    public $user_id;

    public $detail_type;

    public function rules()
    {
        return [
            [['start_time', 'end_time', 'detail_time'], 'date', 'format' => 'php:Y-m-d'],
            [['user_id', 'detail_type'], 'integer']
        ];
    }

    public function attributeLabels()
    {
        return [
            'start_time' => '起始时间',
            'end_time' => '结束时间',
        ];
    }

    public function formName()
    {
        return "";
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $this->load($params);


        switch ($this->detail_type) {
            case static::DETAIL_TYPE_DAILY:
                $query = $this->getMultiUserQuery();
                break;
            case static::DETAIL_TYPE_DAILY_USER:
                $query = $this->getSingleUserQuery();
                break;
            default:
                $this->detail_type = static::DETAIL_TYPE_ALL;
                $query = $this->getAllDateQuery();
                break;
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 10,
            ],
        ]);

        return $dataProvider;
    }

    private function getAllDateQuery()
    {
        $query = new Query();

        $query->select(new Expression('date, sum(if (transaction_type=2, `amount`, 0)) as referrer_revenue, 
                    sum(if (transaction_type=3, amount, 0)) as bd_revenue,
                    sum(if (transaction_type=4, amount, 0)) as bd_1_revenue,
                    sum(if (transaction_type=5, amount, 0)) as manage_tax,
                    sum(if (transaction_type=6, amount, 0)) as chongxiao_tax'))
            ->from(TransactionLog::tableName())
            ->andFilterWhere(['between', 'date', $this->start_time, $this->end_time])
            ->groupBy('date')
            ->orderBy('date desc');

        return $query;
    }

    private function getMultiUserQuery()
    {
        $query = new Query();

        $query->select(new Expression('date, user_id, user.username as username, user.real_name as real_name,
                    sum(if (transaction_type=2, `amount`, 0)) as referrer_revenue, 
                    sum(if (transaction_type=3, amount, 0)) as bd_revenue,
                    sum(if (transaction_type=4, amount, 0)) as bd_1_revenue,
                    sum(if (transaction_type=5, amount, 0)) as manage_tax,
                    sum(if (transaction_type=6, amount, 0)) as chongxiao_tax'))
            ->from(TransactionLog::tableName())
            ->innerJoin(User::tableName() . ' user', 'user.id=user_id')
            ->andFilterWhere(['between', 'date', $this->start_time, $this->end_time])
            ->groupBy('user_id, date')
            ->orderBy('date desc');

        $query->andFilterWhere(['user_id' => $this->user_id]);

        return $query;
    }

    private function getSingleUserQuery()
    {
        $query = TransactionLog::find();
        $query->andFilterWhere(['between', 'date', $this->start_time, $this->end_time])
            ->andFilterWhere(['user_id' => $this->user_id])
            ->orderBy('create_time desc');
        return $query;
    }
}