<style class="tracy-debug">
    #tracy-debug td.nette-DbConnectionPanel-sql { background: white !important }
    #tracy-debug .nette-DbConnectionPanel-source { color: #BBB !important }
    #tracy-debug .nette-DbConnectionPanel-explain td { white-space: pre }
    #tracy-debug .fuzeworks-DbDescriptor th { background: #FDF5CE !important }
</style>

<h1 title="Database">Queries: <?php
    echo $results['queryCount'], ($results['queryTimings'] ? sprintf(', time: %0.3f ms', $results['queryTimings'] * 1000) : ''); ?></h1>

<div class="tracy-inner">
    <table>
        <?php
        if (isset($results['queries'])):
        foreach ($results['queries'] as $database => $queries): ?>
            <tr class='fuzeworks-DbDescriptor'>
                <th>Database:</th>
                <th><?= htmlSpecialChars($database, ENT_QUOTES, 'UTF-8') ?></th>
                <th>#</th>
            </tr>

            <tr><th>Time&nbsp;ms</th><th>SQL Query</th><th>Rows</th></tr>
            <?php foreach ($queries as $query): ?>
                <tr>
                    <td>
                        <?php if (!empty($query['errors'])): ?>
                            <span title="<?= htmlSpecialChars((isset($query['errors']['message']) ? $query['errors']['message'] : ''), ENT_IGNORE | ENT_QUOTES, 'UTF-8') ?>">ERROR</span>
                            <br /><a class="tracy-toggle tracy-collapsed" data-tracy-ref="^tr .nette-DbConnectionPanel-explain">explain</a>
                        <?php elseif ($query['timings'] !== 0): echo sprintf('%0.3f', $query['timings'] * 1000); endif ?>
                    </td>
                    <td class="nette-DbConnectionPanel-sql"><?= htmlSpecialChars($query['query'], ENT_QUOTES, 'UTF-8') ?>
                        <?php if (!empty($query['errors'])): ?>
                            <table class="tracy-collapsed nette-DbConnectionPanel-explain">
                                <tr>
                                    <th>Code</th>
                                    <th>Message</th>
                                </tr>
                                <tr>
                                    <td><?= htmlSpecialChars($query['errors']['code'], ENT_NOQUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlSpecialChars((isset($query['errors']['message']) ? $query['errors']['message'] : ''), ENT_NOQUOTES, 'UTF-8') ?></td>
                                </tr>
                            </table>
                        <?php endif ?>
                    </td>
                    <td> <?= htmlSpecialChars(var_export($query['data'], true), ENT_QUOTES, 'UTF-8') ?> </td>
                </tr>
            <?php endforeach;
        endforeach; endif; ?>
    </table>
    <?php if ($results['queryCountProvided'] < $results['queryCount']): ?><p>...and more</p><?php endif ?>

</div>