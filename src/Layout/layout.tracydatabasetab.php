<?php
if ($results['queryCount'] && !$results['errorsFound'])
{
    $color = "#6ba9e6";
}
elseif ($results['queryCount'] && $results['errorsFound'])
{
    $color = "#990000";
}
else
{
    $color = "#aaa";
}
?>

<span title="Database">
<svg viewBox="0 0 2048 2048"><path fill="<?= $color ?>" d="M1024 896q237 0 443-43t325-127v170q0 69-103 128t-280 93.5-385 34.5-385-34.5-280-93.5-103-128v-170q119 84 325 127t443 43zm0 768q237 0 443-43t325-127v170q0 69-103 128t-280 93.5-385 34.5-385-34.5-280-93.5-103-128v-170q119 84 325 127t443 43zm0-384q237 0 443-43t325-127v170q0 69-103 128t-280 93.5-385 34.5-385-34.5-280-93.5-103-128v-170q119 84 325 127t443 43zm0-1152q208 0 385 34.5t280 93.5 103 128v128q0 69-103 128t-280 93.5-385 34.5-385-34.5-280-93.5-103-128v-128q0-69 103-128t280-93.5 385-34.5z"/>
</svg><span class="tracy-label"><?= ($results['queryTimings'] ? sprintf('%0.1f ms / ', $results['queryTimings'] * 1000) : '') . $results['queryCount'] ?></span>
</span>