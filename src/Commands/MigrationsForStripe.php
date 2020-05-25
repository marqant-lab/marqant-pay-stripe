<?php

namespace Marqant\MarqantPayStripe\Commands;

use Str;
use Illuminate\Support\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Marqant\MarqantPay\Traits\Billable;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Finder\SplFileInfo;
use Marqant\MarqantPaySubscriptions\Traits\RepresentsPlan;

class MigrationsForStripe extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'marqant-pay:migrations:stripe
                                {-- subscriptions : Should the subscription migrations also be run.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create migrations for a given billable model.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->makeMigrationForBillable();

        $this->makeMigrationForPlan();

        $this->makeMigrationForSubscriptions();

        $this->info('Done! 👍');
    }

    /**
     * Get billable argument from input and resolve it to a model with the Billable trait attached.
     *
     * @param string $model_name
     *
     * @return Model|null
     */
    private function getBillableModel(string $model_name)
    {
        $Billable = app($model_name);

        $can_continue = $this->checkIfModelIsBillable($Billable);
        if ($can_continue === false) {
            return null;
        }

        return $Billable;
    }

    /**
     * Get billable argument from input and resolve it to a model with the Billable trait attached.
     *
     * @return Model
     */
    private function getPlanModel()
    {
        $Plan = app(config('marqant-pay-subscriptions.plan_model'));

        $can_continue = $this->checkIfModelIsPlan($Plan);
        if ($can_continue === false) {
            return null;
        }

        return $Plan;
    }

    /**
     * Ensure, that the given model actually uses the Billable trait.
     * If it doesn't, print out an error message and exit the command.
     *
     * @param \Illuminate\Database\Eloquent\Model $Billable
     *
     * @return bool
     */
    private function checkIfModelIsBillable(Model $Billable): bool
    {
        $traits = class_uses($Billable);

        if (!collect($traits)->contains(Billable::class)) {
            $this->alert('The given model is not a Billable.');

            return false;
        }

        return true;
    }

    /**
     * Ensure, that the given model actually uses the Billable trait.
     * If it doesn't, print out an error message and exit the command.
     *
     * @param \Illuminate\Database\Eloquent\Model $Plan
     *
     * @return bool
     */
    private function checkIfModelIsPlan(Model $Plan): bool
    {
        $traits = class_uses($Plan);

        if (!collect($traits)->contains(RepresentsPlan::class)) {
            $this->alert('The given model does not represent a Plan.');

            return false;
        }

        return true;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $Model
     *
     * @return string
     */
    private function getTableFromModel(Model $Model): string
    {
        return $Model->getTable();
    }

    /**
     * @return void
     */
    private function makeMigrationForBillable()
    {
        // get all billable models from config
        $billables = collect(config('marqant-pay.billables'));

        $billables->each(function ($model_name) {
            $this->line("create migration for '$model_name' model");

            $Billable = $this->getBillableModel($model_name);
            if (is_null($Billable)) {
                return;
            }

            $table = $this->getTableFromModel($Billable);

            $path = database_path('migrations');

            // no need to create migration if it is already exists
            $can_continue = $this->preventDuplicates($path, $table);
            if ($can_continue === false) {
                return;
            }

            $stub_path = $this->getBillableStubPath();

            $stub = $this->getStub($stub_path);

            $this->replaceClassName($stub, $table);

            $this->replaceTableName($stub, $table);

            $this->saveMigration($stub, $table);

            $this->line("completed processing '$model_name' model");
        });
    }

    /**
     * Create migrations for Plan model.
     *
     * @return void
     */
    private function makeMigrationForPlan(): void
    {
        if (!$this->option('subscriptions')) {
            return;
        }

        $Plan = $this->getPlanModel();
        if (is_null($Plan)) {
            return;
        }

        $table = $this->getTableFromModel($Plan);

        $path = database_path('migrations');

        // no need to create migration if it is already exists
        $can_continue = $this->preventDuplicates($path, $table);
        if ($can_continue === false) {
            return;
        }

        $stub_path = $this->getPlanStubPath();

        $stub = $this->getStub($stub_path);

        $this->replaceClassName($stub, $table);

        $this->replaceTableName($stub, $table);

        $this->saveMigration($stub, $table);
    }

    /**
     * Create migrations for Subscription model.
     *
     * @return void
     */
    private function makeMigrationForSubscriptions(): void
    {
        if (!$this->option('subscriptions')) {
            return;
        }

        $Plan = $this->getPlanModel();
        if (is_null($Plan)) {
            return;
        }

        $plans_table = $this->getTableFromModel($Plan);

        $plan_singular = $this->getSingular($plans_table);

        $table = "billable_{$plan_singular}";

        $path = database_path('migrations');

        // no need to create migration if it is already exists
        $can_continue = $this->preventDuplicates($path, $table);
        if ($can_continue === false) {
            return;
        }

        $class_name = "Billable" . ucfirst($plan_singular);

        $stub_path = $this->getSubscriptionStubPath();

        $stub = $this->getStub($stub_path);

        $this->replaceClassName($stub, $class_name);

        $this->replaceTableName($stub, $table);

        $this->saveMigration($stub, $table, Carbon::now()
            ->addMinutes(2));
    }

    /**
     * Return singular of a plural.
     *
     * @param string $plural
     *
     * @return string
     */
    private function getSingular(string $plural): string
    {
        return Str::singular($plural);
    }

    /**
     * @return string
     */
    private function getBillableStubPath(): string
    {
        return base_path('vendor/marqant-lab/marqant-pay-stripe/stubs/billable_fields.stub');
    }

    /**
     * @return string
     */
    private function getPlanStubPath(): string
    {
        return base_path('vendor/marqant-lab/marqant-pay-stripe/stubs/plan_fields.stub');
    }

    /**
     * @return string
     */
    private function getSubscriptionStubPath(): string
    {
        return base_path('vendor/marqant-lab/marqant-pay-stripe/stubs/subscription_fields.stub');
    }

    /**
     * Returns the blueprint for the migration about to be created.
     *
     * @param string $stub_path
     *
     * @return string
     */
    private function getStub(string $stub_path): string
    {
        return file_get_contents($stub_path);
    }

    /**
     * @param string $stub
     *
     * @param string $table
     *
     * @return string
     */
    private function replaceClassName(string &$stub, string $table): string
    {
        // table => Table
        $table = ucfirst($table);

        $class_name = "AddMarqantPayStripeFieldsTo{$table}Table";

        $stub = str_replace('{{CLASS_NAME}}', $class_name, $stub);

        return $stub;
    }

    /**
     * @param string $stub
     *
     * @param string $table
     *
     * @return string
     */
    private function replaceTableName(string &$stub, string $table): string
    {
        $stub = str_replace('{{TABLE_NAME}}', $table, $stub);

        return $stub;
    }

    /**
     * @param string                          $stub
     *
     * @param string                          $table
     *
     * @param null|\Illuminate\Support\Carbon $timestamp
     *
     * @return void
     */
    private function saveMigration(string $stub, string $table, ?Carbon $timestamp = null): void
    {
        $file_name = $this->getMigrationFileName($table, $timestamp);

        $path = database_path('migrations');

        $can_continue = $this->preventDuplicates($path, $table);
        if ($can_continue === false) {
            return;
        }

        File::put($path . '/' . $file_name, $stub);
    }

    /**
     * @param null|\Illuminate\Support\Carbon $timestamp
     *
     * @return string
     */
    private function getMigrationPrefix(?Carbon $timestamp = null): string
    {
        $format = 'Y_m_d_His';

        if ($timestamp) {
            return $timestamp->format($format);
        }

        return Carbon::now()
            ->format($format);
    }

    /**
     * @param string                          $table
     * @param null|\Illuminate\Support\Carbon $timestamp
     *
     * @return string
     */
    private function getMigrationFileName(string $table, ?Carbon $timestamp = null): string
    {
        $prefix = $this->getMigrationPrefix($timestamp);

        return $prefix . "_add_marqant_pay_stripe_fields_to_{$table}_table.php";
    }

    /**
     * Check if migration already exists
     *
     * @param string $path
     * @param string $table
     *
     * @return bool - true if can continue, false if find migration
     */
    private function preventDuplicates(string $path, string $table): bool
    {
        $file = "add_marqant_pay_stripe_fields_to_{$table}_table.php";

        $files = collect(File::files($path))
            ->map(function (SplFileInfo $file) {
                return $file->getFilename();
            })
            ->map(function (string $file_name) {
                return preg_replace('/[0-9]{4}_[0-9]{2}_[0-9]{2}_[0-9]{6}_/', '', $file_name);
            });

        if ($files->contains($file)) {
            $this->alert("Migration for marqant pay stripe fields on '{$table}' already exists.");

            return false;
        }

        return true;
    }
}
