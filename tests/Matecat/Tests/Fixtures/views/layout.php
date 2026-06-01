<h1><?php
    /** @phpstan-ignore variable.undefined */
    echo ucfirst($this->shared_data->get('title')); ?></h1>
<?php
/** @phpstan-ignore variable.undefined */
$this->yieldView(); ?>
<div>footer</div>
