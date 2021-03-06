<?php

namespace backend\controllers;

use backend\models\ActiveUserForm;
use backend\models\RelationGraphForm;
use common\controllers\BaseController;
use common\models\NormalUser;
use common\models\records\User;
use common\models\ResetPasswordForm;
use common\models\search\NormalUserSearch;
use common\helpers\Constants;
use common\models\search\UserTreeSearch;
use common\models\UserTree;
use Yii;
use yii\helpers\Url;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;

/**
 * 用户控制器
 * @author longfei <phphome@qq.com>
 */
class UserController extends BaseController
{
    /**
     * ---------------------------------------
     * 构造方法
     * ---------------------------------------
     */
    public function init()
    {
        parent::init();
    }

    /**
     * ---------------------------------------
     * 用户列表
     * ---------------------------------------
     */
    public function actionIndex()
    {
        /* 添加当前位置到cookie供后续操作调用 */
        $this->setForward();

        $searchModel = new NormalUserSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * 激活用户
     */
    public function actionActived()
    {
        /* 添加当前位置到cookie供后续操作调用 */
        $this->setForward();

        $searchModel = new NormalUserSearch();
        $searchModel->is_actived = Constants::NUMBER_TRUE;
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * 未激活用户
     */
    public function actionUnActived()
    {
        /* 添加当前位置到cookie供后续操作调用 */
        $this->setForward();

        $searchModel = new NormalUserSearch();
        $searchModel->is_actived = Constants::NUMBER_FALSE;
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * 激活用户
     */
    public function actionActive()
    {
        $model = new ActiveUserForm();
        $model->setAttributes(Yii::$app->request->get());
        if ($model->validate() && $model->active()) {
            $this->success('激活成功', $this->getForward(), true);
        } else {
            $this->error(json_encode($model->errors), '', true);
        }
    }

    /**
     * ---------------------------------------
     * 添加
     * ---------------------------------------
     */
    public function actionAdd()
    {
        $userModel = new NormalUser();
        $userModel->setScenario(NormalUser::SCENARIO_CREATE);
        $userModel->loadDefaultValues();
        if (Yii::$app->request->isPost) {
            /* 表单验证 */
            $data = Yii::$app->request->post($userModel->formName());
            $data['create_time'] = time();
            $data['reg_ip'] = ip2long(Yii::$app->request->getUserIP());
            $data['last_login_time'] = 0;
            $data['last_login_ip'] = ip2long(Yii::$app->request->getUserIP());
            $data['update_time'] = 0;

            $userModel->setAttributes($data);
            $userModel->generateAuthKey();
            if ($userModel->broker_id == null) {
                $userModel->broker_id = 0;
            }
            if ($userModel->referrer_id == null) {
                $userModel->referrer_id = 0;
            }

            /* 保存用户数据到数据库 */
            if ($userModel->save()) {

                $node = UserTree::findOne(['user_id' => $userModel->broker_id]);
                if ($node == null) {
                    $node->makeRoot();
                }
                $c = new UserTree(['user_id' => $userModel->id]);
                $c->appendTo($node);

                $this->redirect($this->getForward());
            } else {
                $userModel->password = null;
            }
        }
        return $this->render('add', [
            'model' => $userModel
        ]);
    }

    /**
     * ---------------------------------------
     * 编辑
     * ---------------------------------------
     */
    public function actionEdit($id)
    {
        $userModel = $this->findModel($id);

        if (Yii::$app->request->isPost) {
            /* 表单验证 */
            $data = Yii::$app->request->post($userModel->formName());
            $data['update_time'] = time();
            /* 如果设置密码则重置密码，否则不修改密码 */
            if (!empty($data['password'])) {
                $userModel->generateAuthKey();
                $userModel->setPassword($data['password']);
            }
            unset($data['password']);

            $userModel->setAttributes($data);
            if ($userModel->broker_id == null) {
                $userModel->broker_id = 0;
            }
            if ($userModel->referrer_id == null) {
                $userModel->referrer_id = 0;
            }
            /* 保存用户数据到数据库 */
            if ($userModel->save()) {
                $this->success('操作成功', $this->getForward('actived'));
            } else {
                $errors = array_merge([], $userModel->errors);
                $this->error(json_encode($errors));
            }
        }

        return $this->render('edit', [
            'model' => $userModel
        ]);
    }

    /**
     * ---------------------------------------
     * 删除
     * ---------------------------------------
     */
    public function actionDelete()
    {
        $uid = Yii::$app->request->get('id');
        $rowCnt = User::updateAll(['status' => User::STATUS_DELETED], ['id' => $uid]);

        // 也要删除用此用户相关的信息
        // 由于与此用户关联的信息过多，目前只更新用户的状态为封禁状态
        if ($rowCnt > 0) {
            $this->success('删除成功', $this->getForward());
        } else {
            $this->error('删除失败！');
        }
    }

    public function actionCheckBan($id)
    {
        $model = $this->findModel($id);
        $model->is_baned = $model->is_baned == Constants::NUMBER_FALSE ?
            Constants::NUMBER_TRUE : Constants::NUMBER_FALSE;
        $rowCnt = $model->update(false, ['is_baned']);
        if ($rowCnt == 1) {
            $this->success('操作成功', $this->getForward(), true);
        } else {
            $this->error(json_encode($model->errors), '', true);
        }
    }

    /**
     * 获取会员系谱图
     * 如果是通过ajax请求，则返回json数据，
     * 否则返回视图
     * @return string
     */
    public function actionRelationGraph()
    {
        $request = Yii::$app->request;

        $searchModel = new RelationGraphForm();
        $searchModel->load($request->get());

        if ($searchModel->load($request->get()) && $request->isAjax) {
            if ($searchModel->validate()) {
                return $this->renderJson(['status' => 1, 'data' => $searchModel->getTreantData()]);
            } else {
                $this->error(json_encode($searchModel->errors));
            }
        }

        return $this->render('relation-graph', [
            'searchModel' => $searchModel,
        ]);
    }

    public function actionUserTree()
    {
        $searchModel = new UserTreeSearch();
        if (Yii::$app->request->isPost) {
            $data = $searchModel->search(Yii::$app->request->post());
            return $this->asJson(['status' => 1, 'data' => $data]);
        }

        return $this->render('user_tree', [
            'searchModel' => $searchModel
        ]);
    }

    /**
     * 根据用户名搜索用户
     */
    public function actionSearch($user_name)
    {
        $rows = User::find()->select('id, username')->where('username like :username', [
            ':username' => "%" . $user_name . "%",
        ])->asArray()->all();
        return $this->renderJson($rows);
    }

    /**
     * 福利统计
     */
    public function actionWelfare()
    {

        return $this->render('welfare');
    }

    public function findModel($id)
    {
        $model = NormalUser::findOne($id);
        if ($model) {
            return $model;
        } else {
            throw new NotFoundHttpException('用户未找到');
        }
    }

    public function actionExists($username)
    {
        return $this->asJson(User::findOne(['username' => $username]) !== null);
    }

    /**
     * 用户级别审核
     */
    public function actionLevelCheck()
    {
        if (Yii::$app->request->isPost) {
            // id, status
            $model = NormalUser::findOne(['id' => Yii::$app->request->post('uid')]);
            $level = Yii::$app->request->post('level');
            if ($model) {
                $levelArr = $model::getLevelArr();
                if (isset($levelArr[$level])) {
                    $model->level = $level;
                    $model->update(false, ['level']);
                } else {
                    throw new BadRequestHttpException('状态错误');
                }
            } else {
                throw new NotFoundHttpException('有效重复报单记录未找到');
            }
            return $this->redirect($this->getForward());
        }
        $this->setForward();
        // 展示一个用户当前共有多少个代理商，一级，二级，三级
        $this->setForward();

        $searchModel = new NormalUserSearch();
        $searchModel->is_actived = Constants::NUMBER_TRUE;
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        return $this->render('user_level_check', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /*
     * 重置当前登录用户密码
     */
    public function actionResetPassword()
    {
        $form = new ResetPasswordForm();

        if (Yii::$app->request->isPost && $form->load(Yii::$app->request->post())) {
            if ($form->resetPassword()) {
                $this->success('重置密码成功', $this->getForward());
            } else {
                $this->error('重置密码失败');
            }
        }
        return $this->render('reset_password', [
            'model' => $form
        ]);
    }
}
