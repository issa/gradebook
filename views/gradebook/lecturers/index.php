<?
$categories = array_keys($groupedDefinitions);
sort($categories);
?>

<table class="default sortable-table gradebook-lecturer-overview" data-sortlist="[[0, 0]]">

    <thead>
        <tr class="tablesorter-ignoreRow">
            <th colspan="2">&nbsp;</th>
            <? foreach ($categories as $category) { ?>
                <th colspan="<?= count($groupedDefinitions[$category]) ?>"><?= htmlReady($category) ?></th>
            <? } ?>
        </tr>

        <tr class="sortable">
            <th data-sort="htmldata"><?= _("Name") ?></th>
            <th data-sort="text"><?= _("Gesamtsumme") ?></th>

            <? foreach ($categories as $category) { ?>
                <? foreach ($groupedDefinitions[$category] as $definition) { ?>
                    <th data-sort="text" class="gradebook-lecturer-overview-definition">
                        <?= htmlReady($definition->name) ?>
                        <span class="gradebook-definition-weight">(<?= $controller->formatAsPercent($controller->getNormalizedWeight($definition)) ?>)</span>
                    </th>
                <? } ?>
            <? } ?>
        </tr>

    </thead>

    <tbody>

        <? foreach ($students as $student) { ?>
            <tr>
                <td class="gradebook-student-name" data-sort-value="<?= $studentName = htmlReady($student->getFullName('no_title_rev')) ?>">
                    <a href="<?= URLHelper::getURL('dispatch.php/profile', ['username' => $student->username]) ?>">
                        <?= $studentName ?>
                    </a>
                </td>
                <td data-sort-value="0">
                    <? if (isset($totalSums[$student->id])) { ?>
                        <?= $controller->formatAsPercent($totalSums[$student->id]) ?>
                    <? } else { ?>
                        ??%
                    <? } ?>

                </td>

                <? foreach ($categories as $category) { ?>
                    <? foreach ($groupedDefinitions[$category] as $definition) { ?>
                        <? $instance = $controller->getInstanceForUser($definition, $student) ?>
                        <td>
                            <? if ($instance) { ?>
                                <?= $controller->formatAsPercent($instance->rawgrade) ?>
                            <? } else { ?>
                                0%
                            <? } ?>
                        </td>
                    <? } ?>
                <? } ?>
            </tr>
        <? } ?>

    </tbody>

</table>

<style>
.gradebook-lecturer-overview-definition {
    white-space: nowrap;
}
.gradebook-student-name {
    white-space: nowrap;
}
</style>
