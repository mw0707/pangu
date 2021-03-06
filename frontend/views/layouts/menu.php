<?php

use common\helpers\Html;
use common\widgets\Alert;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;

?>

<style>
    @media (min-width: 991px) {
        .page-header-top {
            display: none;
        }
    }
    @media (max-width: 991px) {
        .page-header .page-header-top .menu-toggler {
            float: left;
            background-image: none;
            margin: 5px;
            width: 45px;
            height: 19px;
        }
    }
</style>
<div class="page-header">
    <div class="page-header-top" style="background-color: #e2e7ea;margin-bottom: 15px">
        <div class="container">
            <a href="javascript:;" class="menu-toggler">菜单栏</a>
        </div>
    </div>
    <div class="page-header-menu">
        <div class="container">
            <div class="hor-menu">
                <?php
                if (!Yii::$app->user->isGuest) {
                    $menuItems = [
                        ['label' => '首页', 'options' => ['class' => 'menu-dropdown'], 'url' => ['/site/index']],
                        ['label' => '会员资料', 'options' => ['class' => 'menu-dropdown mega-menu-dropdown'], 'items' => [
                            ['label' => '资料收集', 'url' => '/user/edit'],
                            ['label' => '修改密码', 'url' => '/user/reset-password'],
                            ['label' => '收货地址', 'url' => '/address/index'],
                        ]],
                        ['label' => '部门情况', 'options' => ['class' => 'menu-dropdown mega-menu-dropdown'], 'items' => [
                            ['label' => '会员网络', 'url' => '/user/user-tree'],
                            ['label' => '审核报单', 'url' => '/baodan/check'],
                            ['label' => '注册会员', 'url' => '/user/add'],
                            ['label' => '部门会员列表', 'url' => '/user/index'],
                        ]]
                    ];

                    $menuItems[] = ['label' => '财务管理', 'options' => ['class' => 'menu-dropdown mega-menu-dropdown'], 'items' => [
                        ['label' => '奖金明细', 'url' => '/finance/index'],
                        ['label' => '帐户提现', 'url' => '/finance/exchange'],
                        ['label' => '帐户转账', 'url' => '/finance/transfer'],
                        ['label' => '奖金币转换', 'url' => '/finance/bonus-to-dianzibi'],
                        ['label' => '重复报单', 'url' => '/finance/recharge']
                    ]];

                    $pathInfo = Yii::$app->request->pathInfo;
                    if ($pathInfo == '') {
                        $pathInfo = 'site/index';
                    }

                    foreach ($menuItems as &$menuItem) {
                        if (!empty($menuItem['items'])) {
                            foreach ($menuItem['items'] as $item) {
                                if (strpos($item['url'], $pathInfo) !== false) {
                                    $menuItem['options']['class'] .= ' active opened';
                                    break;
                                }
                            }
                        } elseif (!empty($menuItem['url'])) {
                            if (strpos($menuItem['url'][0], $pathInfo) !== false) {
                                $menuItem['options']['class'] .= ' active';
                                break;
                            }
                        }
                    }
                    echo Nav::widget([
                        'options' => ['class' => 'navbar-nav'],
                        'items' => $menuItems,
                    ]);
                }
                ?>
            </div>
            <div class="hor-menu" style="float: right">
                <?php
                if (Yii::$app->user->isGuest) {
                    $menuItems = [['label' => '登录', 'options' => ['style' => 'color: #fff'], 'url' => ['/site/login']]];
                } else {
                    $menuItems = [['label' => Yii::$app->user->identity->username . '(退出)', 'options' => ['style' => 'color: #fff'], 'url' => ['/site/logout']]];
                }
                echo Nav::widget([
                    'options' => ['class' => 'navbar-nav navbar-right'],
                    'items' => $menuItems,
                ]);
                ?>
            </div>
        </div>
    </div>
</div>


