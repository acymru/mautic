<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');

$view['slots']->set('mauticContent', 'leadImport');
$view['slots']->set('headerTitle', $view['translator']->trans('mautic.lead.import.leads'));

?>

<?php if (isset($form['file'])): ?>
<div class="row">
    <div class="col-sm-offset-3 col-sm-6">
        <div class="ml-lg mr-lg mt-md pa-lg">
            <div class="panel panel-info">
                <div class="panel-heading">
                    <div class="panel-title"><?php echo $view['translator']->trans('mautic.lead.import.start.instructions'); ?></div>
                </div>
                <div class="panel-body">
                    <?php echo $view['form']->start($form); ?>
                    <div class="input-group well mt-lg">
                        <?php echo $view['form']->widget($form['file']); ?>
                        <span class="input-group-btn">
                            <?php echo $view['form']->widget($form['start']); ?>
                        </span>
                    </div>
                    <?php echo $view['form']->end($form); ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
    <div class="ml-lg mr-lg mt-md pa-lg">
        <?php echo $view['form']->start($form); ?>
        <div class="panel panel-info">
            <div class="panel-heading">
                <div class="panel-title"><?php echo $view['translator']->trans('mautic.lead.import.default.owner'); ?></div>
            </div>
            <div class="panel-body">
                <div class="col-xs-4">
                    <?php echo $view['form']->widget($form['owner']); ?>
                </div>
            </div>
        </div>
        <div class="panel panel-info">
            <div class="panel-heading">
                <div class="panel-title"><?php echo $view['translator']->trans('mautic.lead.import.fields'); ?></div>
            </div>
            <div class="panel-body">
                <?php echo $view['form']->errors($form); ?>
                <?php $rowCount = 2; ?>
                <?php foreach ($form->children as $child): ?>
                    <?php if ($rowCount++ % 3 == 1 ): ?>
                        <div class="row">
                    <?php endif; ?>
                    <div class="col-sm-4">
                        <?php echo $view['form']->row($child); ?>
                    </div>
                    <?php if ($rowCount++ % 3 == 1 ): ?>
                        </div>
                    <?php endif; ?>
                    <?php $rowCount++; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php echo $view['form']->end($form); ?>
    </div>
<?php endif; ?>

