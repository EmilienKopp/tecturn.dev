<?php

namespace App\Models\Views;

use Splitstack\Rome\Models\ReadOnlyModel;

/**
 * Per-presentation delivery aggregates across rehearsals and live sessions.
 * Durations that need timestamp arithmetic (session lengths, per-slide
 * timings) are finished in the DeliveryStatsReadModel, keeping this view's
 * SQL portable across the sqlite/postgres pair.
 *
 * @property int $presentation_id
 * @property int $team_id
 * @property int $rehearsal_count
 * @property int $total_rehearsal_seconds
 * @property int $session_count
 */
class PresentationDeliveryStatsView extends ReadOnlyModel
{
    protected $table = 'presentation_delivery_stats';

    protected $primaryKey = 'presentation_id';

    public $timestamps = false;
}
