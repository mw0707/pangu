<?php
/**
 * Created by PhpStorm.
 * User: mjz
 * Date: 17/11/9
 * Time: 下午4:20
 */

namespace common\models\search;


use Codeception\PHPUnit\Constraint\Page;
use common\models\NormalUser;
use common\models\records\Baodan;
use common\models\records\RechargeLog;
use yii\base\Model;
use yii\data\ActiveDataProvider;

class RechargeLogSearch extends Model
{

    // 上级报单中心审核状态
    public $baodan_status;
    // 公司财务审核状态
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
            [['user_id', 'status', 'baodan_status'], 'integer'],
            [['start_time', 'end_time'], 'date', 'format' => 'php:Y-m-d']
        ];
    }

    public function attributeLabels()
    {
        return [
            'baodan_status' => '领路老师审核状态',
            'status' => '公司财务审核状态',
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
    private function basicSearch()
    {
        $query = RechargeLog::find();

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
            'baodan_status' => $this->baodan_status,
            'user_id' => $this->user_id,
        ])->andFilterWhere(['between', 'date', $this->start_time, $this->end_time]);

        $query->orderBy('create_time desc');
        return $dataProvider;
    }


    public function frontendCheckSearch($params)
    {
        $this->load($params);

        // 只查找本报单中心的充值记录
        $query = RechargeLog::find();

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

        // 查找报单ID

        if (!$this->validate()) {
            $query->andWhere('0=1');
            return $dataProvider;
        }

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            RechargeLog::tableName() . '.status' => $this->status,
            RechargeLog::tableName() . '.baodan_status' => $this->baodan_status,
            RechargeLog::tableName() . '.user_id' => $this->user_id,
        ])->andFilterWhere(['between', 'date', $this->start_time, $this->end_time]);

        $query->innerJoin(NormalUser::tableName() . ' u', [
            'u.broker_id' => \Yii::$app->user->getId(),
            'u.id' => 't_recharge_log.user_id']);

        $query->orderBy('create_time desc');
        return $dataProvider;
    }
}