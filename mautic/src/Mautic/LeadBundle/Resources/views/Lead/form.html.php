<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticLeadBundle:Lead:index.html.php');
?>

<div class="lead-profile-header">
    <h3><?php
    $header = ($lead->getId()) ?
        $view['translator']->trans('mautic.lead.lead.header.edit',
            array('%name%' => $view['translator']->trans($lead->getPrimaryIdentifier()))) :
        $view['translator']->trans('mautic.lead.lead.header.new');
    echo $header;
    ?>
    </h3>
</div>
<?php echo $view['form']->form($form); ?>