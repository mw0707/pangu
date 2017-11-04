<?php
/**
 * Created by PhpStorm.
 * User: mjz
 * Date: 17/10/23
 * Time: 上午11:22
 */

namespace backend\models\search;


use common\models\records\ExchangeLog;
use yii\base\Model;
use yii\data\ActiveDataProvider;

class ExchangeSearch extends Model
{

    // 提现状态
    public $status;
    // 用户账号
    public $user_id;
    // 查询开始时间
    public $start_time;
    // 查询结束时间
    public $end_time;

    public function rules()
    {
        return [
            ['status', 'required'],
            ['status', 'in', 'range' => [ExchangeLog::STATUS_CHECKING, ExchangeLog::STATUS_APPROVE]],
            [['user_id', 'status'], 'integer'],
            [['start_time', 'end_time'], 'date', 'format' => 'php:Y-m-d']
        ];
    }

    public function attributeLabels()
    {
        return [
            'status' => '提现状态',
            'user_id' => '用户账号',
            'start_time' => '起始时间',
            'end_time' => '结束时间',
        ];
    }

    public function formName()
    {
        return ''; // TODO: Change the autogenerated stub
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
        return $this->basicSearch();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function frontendSearch($params)
    {
        $this->load($params);
        $this->user_id = \Yii::$app->user->identity->getId();
        return $this->basicSearch();
    }

    /**
     * @return ActiveDataProvider
     */
    private function basicSearch() {
        $query = ExchangeLog::find();

        /**
         * 在PHP5中 对象的复制是通过引用来实现的，
         * 运行到return处的$query对象和这里的$query在内存中的地址是一样的，
         * 所以不需要将这个语句写在return前
         */
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 10,
            ],
        ]);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'status' => $this->status,
            'user_id' => $this->user_id,
        ])->andFilterWhere(['between', 'date', $this->start_time, $this->end_time]);

        return $dataProvider;
    }
}