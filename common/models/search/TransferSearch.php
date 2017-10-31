<?php
/**
 * Created by PhpStorm.
 * User: mjz
 * Date: 17/10/30
 * Time: 上午9:19
 */

namespace common\models\search;


use common\helpers\TransactionHelper;
use common\models\records\TransactionLog;
use yii\base\Model;
use yii\data\ActiveDataProvider;

class TransferSearch extends Model
{
    public $user_id;
    public $start_time;
    // 查询结束时间
    public $end_time;

    public function rules()
    {
        return [
            [['user_id'], 'integer'],
            [['start_time', 'end_time'], 'date', 'format' => 'php:Y-m-d']
        ];
    }

    public function attributeLabels()
    {
        return [
            'user_id' => '用户账号',
            'start_time' => '起始时间',
            'end_time' => '结束时间',
        ];
    }

    /**
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = TransactionLog::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 10,
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            $query->where('0=1');
            return $dataProvider;
        }

        $query->andWhere(['transaction_type' => TransactionHelper::TRANSACTION_TRANSFER_IN])
            ->andFilterWhere(['between', 'date', $this->start_time, $this->end_time])
            ->andFilterWhere(['or', ['user_id' => $this->user_id], ['from_user_id' => $this->user_id]]);

        return $dataProvider;
    }
}