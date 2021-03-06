<?php

use kartik\widgets\DatePicker;
use kartik\widgets\Select2;
use yii\helpers\Html;
use yii\web\JsExpression;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $form common\core\ActiveForm */
?>

<?php $form = ActiveForm::begin([
    'action' => [Yii::$app->request->pathInfo],
    'method' => 'get',
    'options' => [
        //'class'=>"form-inline",
        'data-pjax' => true, //开启pjax搜索
    ]
]); ?>
<div class="row">
    <div style="display:none;">
        <?php
        if (!empty($hiddenFields)) {
            foreach ($hiddenFields as $hiddenField) {
                echo $form->field($model, $hiddenField)->hiddenInput();
            }
        }
        ?>
    </div>
    <div class="col-md-4">
        <?php echo '<label class="control-label">时间范围</label>';
        echo DatePicker::widget([
            'model' => $model,
            'attribute' => 'start_time',
            'attribute2' => 'end_time',
            'options' => ['placeholder' => '开始日期'],
            'options2' => ['placeholder' => '结束日期'],
            'separator' => '至',
            'type' => DatePicker::TYPE_RANGE,
            'form' => $form,
            'pluginOptions' => [
                'format' => 'yyyy-mm-dd',
                'autoclose' => true,
            ]
        ]); ?>
    </div>

    <div class="col-md-2">
        <div class="form-group" style="margin-top: 24px;">
            <?= Html::submitButton('搜索', ['class' => 'btn blue']) ?>
            <?= Html::a('重置', [Yii::$app->request->pathInfo],['class' => 'btn btn-default']) ?>
        </div>
    </div>
</div>
<?php ActiveForm::end(); ?>
