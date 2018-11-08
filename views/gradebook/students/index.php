<article>
    <header>
        <h1><?= _("Gesamt") ?></h1>
        TODO<?= $this->render_partial("_progress", ['value' => 60])?>
    </header>

    <? foreach ($categories as $category) { ?>
        <section>
            <header>
                <h1><?= htmlReady($category) ?></h1>
                TODO<?= $this->render_partial("_progress", ['value' => 60])?>
            </header>

            <table class="default">
                <thead>
                    <tr>
                        <th>&nbsp;</th>
                        <th><?= _("Tool") ?></th>
                        <th><?= _("Gewichtung") ?></th>
                        <th><?= _("Feedback") ?></th>
                </thead>

                <tbody>
                    <?
                    foreach ($groupedDefinitions[$category] as $definition) {
                        $instance = $groupedInstances[$definition->id];
                        $grade = $controller->formatAsPercent($instance ? $instance->rawgrade : 0);
                        $feedback = $instance ? $instance->feedback : '';
                    ?>
                        <tr>
                            <td>
                                <?= htmlReady($definition->name) ?>
                                <?= $this->render_partial("_progress", ['value' => (int) $grade])?>
                            </td>
                            <td>
                                <?= htmlReady($definition->tool) ?>
                            </td>
                            <td>
                                <?= $controller->formatAsPercent($controller->getNormalizedWeight($definition)) ?>%
                            </td>
                            <td>
                                <?= htmlReady($feedback) ?>
                            </td>
                        </tr>
                    <? } ?>
                </tbody>
            </table>

        </section>
    <? } ?>
</article>
<style>
progress {
    background-color: var(--light-gray-color-20);
    border: none;
    color: var(--base-color);
    height: 20px;
}

progress::-moz-progress-bar {
    background-color: var(--base-color);
}
progress::-webkit-progress-value {
    background-color: var(--base-color);
}
.progress-wrapper {
    /* position: relative; */
    display: block;
}
.progress-wrapper span {
    font-size: 16px;
    line-height: 20px;
    width: 3em;
    text-align: right;
    display: inline-block;
    /*
       position: absolute;
       left: 0.2em;
       top: 0;
       color: white;
     */
}

/* .progress-wrapper progress[value="0"] + span {
   color: var(--base-color);
   } */
</style>
