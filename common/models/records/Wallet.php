<?php

namespace common\models\records;

use Yii;

/**
 * This is the model class for table "{{%wallet}}".
 *
 * @property integer $id
 * @property integer $user_id
 * @property double $total_jiangjin
 * @property double $jiangjin
 * @property double $dianzi
 * @property double $total_dianzi
 * @property integer $update_time
 */
class Wallet extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%wallet}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id'], 'required'],
            [['user_id', 'update_time'], 'integer'],
            [['total_jiangjin', 'jiangjin', 'dianzi', 'total_dianzi', 'jifen'], 'number'],
            [['user_id'], 'unique'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'total_jiangjin' => '奖金累积',
            'jiangjin' => '奖金余额',
            'dianzi' => '电子币余额',
            'total_dianzi' => '重消余额',
            'jifen' => '积分',
            'update_time' => '更新时间',
        ];
    }

    /**
     * @param $userId
     * @return Wallet
     */
    public static function getValidWallet($userId) {
        $wallet = static::findOne(['user_id' => $userId]);
        if (empty($wallet)) {
            $wallet = new Wallet();
            $wallet->user_id = $userId;
            $wallet->loadDefaultValues();
            $wallet->save();
        }
        return $wallet;
    }

    public function addJiangjin($amount) {
        $this->jiangjin += $amount;
        if ($amount > 0) {
            $this->total_jiangjin += $amount;
        }
    }

    public function addDianzi($amount) {
        $this->dianzi += $amount;
        if ($amount > 0) {
            $this->total_dianzi += $amount;
        }
    }
}
