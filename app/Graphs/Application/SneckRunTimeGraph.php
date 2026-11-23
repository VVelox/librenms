<?php

namespace App\Graphs\Application;

use App\Data\Graphing\DataSeries;
use App\Data\Graphing\GraphOptions;

class SneckRunTimeGraph extends ApplicationGraph
{
    public function getSeries(): array
    {
        // app-sneck-<app_id>-run_time.rrd, only written by sneck 1.1.0 and up
        return [
            'run_time' => new DataSeries($this->metric(['metric' => 'run_time']), 'data', __('graph.application.sneck.run_time'), 's'),
        ];
    }

    protected function options(): GraphOptions
    {
        return new GraphOptions(legendTitle: __('graph.application.sneck.seconds'), palette: 'psychedelic');
    }
}
