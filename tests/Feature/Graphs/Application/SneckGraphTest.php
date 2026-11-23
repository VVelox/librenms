<?php

namespace LibreNMS\Tests\Feature\Graphs\Application;

use App\Data\Graphing\GraphParameters;
use App\Facades\Rrd;
use App\Graphs\Application\SneckResultsGraph;
use App\Graphs\Application\SneckRunTimeGraph;
use App\Graphs\Application\SneckTimeGraph;
use App\Graphs\GraphFactory;
use App\Models\Application;
use App\Models\Device;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LibreNMS\Tests\TestCase;
use PHPUnit\Framework\Attributes\TestWith;

final class SneckGraphTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['application_sneck_results', SneckResultsGraph::class])]
    #[TestWith(['application_sneck_time', SneckTimeGraph::class])]
    #[TestWith(['application_sneck_run_time', SneckRunTimeGraph::class])]
    public function testGraphTypesResolveToClasses(string $type, string $class): void
    {
        $this->assertInstanceOf($class, app(GraphFactory::class)->graphFor(new GraphParameters(['type' => $type, 'id' => 1])));
    }

    public function testResultsAndTimeReadTheMainFile(): void
    {
        $app = $this->sneck();
        $path = "sneck.example.com/app-sneck-$app->app_id.rrd";

        $results = $this->graph(SneckResultsGraph::class, $app)->getSeries();
        $time = $this->graph(SneckTimeGraph::class, $app)->getSeries();

        $this->assertSame(['ok', 'warning', 'critical', 'unknown', 'errored'], array_keys($results));
        $this->assertSame(['time', 'time_to_polling'], array_keys($time));
        $this->assertSame($path, Rrd::metricName($results['ok']->metric)->relativePath());
        $this->assertSame($path, Rrd::metricName($time['time']->metric)->relativePath());
    }

    public function testRunTimeReadsItsOwnFile(): void
    {
        $app = $this->sneck();

        $series = $this->graph(SneckRunTimeGraph::class, $app)->getSeries();

        $this->assertSame('data', $series['run_time']->field);
        $this->assertSame("sneck.example.com/app-sneck-$app->app_id-run_time.rrd", Rrd::metricName($series['run_time']->metric)->relativePath());
    }

    #[TestWith(['application_sneck_results', 'Results'])]
    #[TestWith(['application_sneck_time', 'Time'])]
    #[TestWith(['application_sneck_run_time', 'Run Time'])]
    public function testTitles(string $type, string $graph): void
    {
        $app = $this->sneck();

        $title = app(GraphFactory::class)->graphFor(new GraphParameters(['type' => $type, 'id' => $app->app_id]))->getTitle();

        $this->assertSame($app->device->shortDisplayName() . "::Sneck::$graph", $title);
    }

    /**
     * @param  class-string<SneckResultsGraph|SneckTimeGraph|SneckRunTimeGraph>  $class
     */
    private function graph(string $class, Application $app): SneckResultsGraph|SneckTimeGraph|SneckRunTimeGraph
    {
        return new $class(new GraphParameters(['id' => $app->app_id]));
    }

    private function sneck(): Application
    {
        $device = Device::factory()->create(['hostname' => 'sneck.example.com']);

        return Application::factory()->for($device)->create(['app_type' => 'sneck']);
    }
}
