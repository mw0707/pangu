<?php

namespace backend\controllers;

use backend\models\AdminUser;
use backend\models\search\AdminUserSearch;
use common\models\records\User;
use Yii;

/**
 * 后台用户控制器
 * @author longfei <phphome@qq.com>
 */
class AdminController extends BaseController
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

        $searchModel = new AdminUserSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * ---------------------------------------
     * 添加
     * ---------------------------------------
     */
    public function actionAdd()
    {

        $model = new AdminUser();

        if (Yii::$app->request->isPost) {
            /* 表单验证 */
            $data = Yii::$app->request->post('AdminUser');
            $data['reg_time'] = time();
            $data['reg_ip'] = ip2long(Yii::$app->request->getUserIP());
            $data['last_login_time'] = 0;
            $data['last_login_ip'] = ip2long('127.0.0.1');
            $data['update_time'] = 0;
            /* 表单数据加载和验证，具体验证规则在模型rule中配置 */
            /* 密码单独验证，否则setPassword后密码肯定符合rule */
            if (empty($data['password']) || strlen($data['password']) < 6) {
                $this->error('密码为空或小于6字符');
            }
            $model->setAttributes($data);
            $model->generateAuthKey();
            $model->setPassword($data['password']);
            /* 保存用户数据到数据库 */
            if ($model->save()) {
                $this->success('操作成功', $this->getForward());
            } else {
                $this->error('操作错误');
            }
        }

        return $this->render('add', [
            'model' => $model,
        ]);
    }

    /**
     * ---------------------------------------
     * 编辑
     * ---------------------------------------
     */
    public function actionEdit($id)
    {
        $model = AdminUser::findOne($id);

        if (Yii::$app->request->isPost) {
            /* 表单验证 */
            $data = Yii::$app->request->post('AdminUser');
            $data['update_time'] = time();
            /* 如果设置密码则重置密码，否则不修改密码 */
            if (!empty($data['password'])) {
                $model->generateAuthKey();
                $model->setPassword($data['password']);
            }
            unset($data['password']);

            $model->setAttributes($data);
            /* 保存用户数据到数据库 */
            if ($model->save()) {
                $this->success('操作成功', $this->getForward());
            } else {
                $this->error('操作错误');
            }
        }

        $model->password = '';
        return $this->render('edit', [
            'model' => $model,
        ]);
    }

    /**
     * ---------------------------------------
     * 删除
     * ---------------------------------------
     */
    public function actionDelete()
    {
        $ids = Yii::$app->request->param('id', 0);
        $ids = implode(',', array_unique((array)$ids));

        if (empty($ids)) {
            $this->error('请选择要操作的数据!');
        }

        /* 不能删除超级管理员 */
        if (in_array(Yii::$app->params['admin'], explode(',', $ids))) {
            $this->error('不能删除超级管理员！');
        }

        $_where = 'id in(' . $ids . ')';
        if (AdminUser::deleteAll($_where)) {
            $this->success('删除成功', $this->getForward());
        } else {
            $this->error('删除失败！');
        }
    }

    /**
     * ---------------------------------------
     * 用户授权
     * ---------------------------------------
     */
    public function actionAuth($id)
    {
        $auth = Yii::$app->authManager;
        /* 获取用户信息 */
        $model = AdminUser::findOne($id);

        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post();
            /* 用户权限组 */
            $item_name = $data['param'];

            /* 先删除 用户组-用户 记录 */
            $auth->revokeAll($id);
            /* 再添加记录 */
            $role = $auth->getRole($item_name);
            $auth->assign($role, $id);

            $this->success('授权成功！', $this->getForward());

            var_dump($data['param']);
            exit;
        }

        /* 获取所有权限组 */
        $roles = $auth->getRoles();
        /* 获取该用户的权限 */
        $group = array_keys($auth->getAssignments($id));
        //var_dump($group);exit;

        return $this->render('auth', [
            'model' => $model,
            'roles' => $roles,
            'group' => $group
        ]);
    }

    public function actionBan()
    {
        $ids = Yii::$app->request->param('id', 0);
        $ids = array_unique((array)$ids);

        if (empty($ids)) {
            $this->error('请选择要操作的用户!');
        }

        // 也要删除用此用户相关的信息
        // 由于与此用户关联的信息过多，目前只更新用户的状态为封禁状态
        if (User::banUsers($ids) > 0) {
            $this->success('封禁成功', $this->getForward());
        } else {
            $this->error('封禁失败！');
        }
    }

    public function actionUnban() {
        $ids = Yii::$app->request->param('id', 0);
        $ids = array_unique((array)$ids);

        if (empty($ids)) {
            $this->error('请选择要操作的用户!');
        }

        // 也要删除用此用户相关的信息
        // 由于与此用户关联的信息过多，目前只更新用户的状态为封禁状态
        if (User::UnbanUsers($ids) > 0) {
            $this->success('解封成功', $this->getForward());
        } else {
            $this->error('解封失败！');
        }
    }

    public function actions()
    {
        return parent::actions(); // TODO: Change the autogenerated stub
    }
}
