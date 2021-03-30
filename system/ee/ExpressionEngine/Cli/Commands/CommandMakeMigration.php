<?php

namespace ExpressionEngine\Cli\Commands;

use ExpressionEngine\Cli\Cli;
use ExpressionEngine\Library\Filesystem\Filesystem;

/**
 * Generate a new migration using the CLI
 */
class CommandMakeMigration extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Make migration';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'make:migration';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php make:migration --name create_myaddon_table';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'name,n:' => 'Name of migration',
        'table,t:' => 'Table name',
        'status,s:' => 'Status name',
        'location,l:' => 'Migration location. Current options are ExpressionEngine or shortname of an add-on that is currently installed. Defaults to ExpressionEngine.',
        'create,c' => 'Specify command is a create command',
        'update,u' => 'Specify command is an update command',
    ];

    /**
     * Command can run without EE Core
     * @var boolean
     */
    public $standalone = false;

    /**
     * Passed in migration name
     * @var string
     */
    public $migration_name;

    /**
     * Migration action. Options are create, update, and generic
     * @var string
     */
    public $migrationAction = 'generic';

    /**
     * Template name, generated from info about migration
     * @var string
     */
    public $templateName = 'GenericMigration';

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $this->migration_name = $this->option('--name');

        // Ask for the name if they didnt specify one
        if (empty($this->migration_name)) {
            $this->migration_name = $this->ask('command_make_migration_what_is_migration_name');
        }

        // Name is a required field
        if (empty($this->migration_name)) {
            $this->fail('command_make_migration_no_name_specified');
        }

        // Snakecase the passed in migration name
        $this->migration_name = ee('Migration')->snakeCase($this->migration_name);

        $this->info(lang('command_make_migration_using_migration_name') . $this->migration_name);

        // Set location
        $this->migration_location = $this->option('--location', 'ExpressionEngine');

        // Set migration type based on create/update flags
        if ($this->option('--create')) {
            $this->migrationAction = 'create';
        } elseif ($this->option('--update')) {
            $this->migrationAction = 'update';
        } else {
            // No migration flags passed, so first we guess, then we ask,
            // defaulting to the guessed option
            $this->migrationAction = $this->guessMigrationAction();
            $this->askMigrationAction();
        }

        // Set migration type based on create/update flags
        if ($this->option('--table')) {
            $this->migrationCategory = 'table';
        } elseif ($this->option('--status')) {
            $this->migrationCategory = 'status';
        } else {
            // No migration flags passed, so first we guess, then we ask,
            // defaulting to the guessed option
            $this->migrationCategory = $this->guessMigrationCategory();
            $this->askMigrationCategory();
        }

        // If tablename is explicitly passed, use that
        if ($this->option('--table')) {
            $this->tableName = $this->option('--table');
        } else {
            $this->tableName = $this->guessTablename();
            $this->askTablename();
        }

        // Generates an instance of a migration model using a timestamped, processed filename
        $this->migration = ee('Migration')->generateMigration($this->migration_name, $this->migration_location);

        $this->setTemplateName();

        // Print out info about generated migration
        $this->info('<<bold>>' . lang('command_make_migration_table_creating_migration') . $this->migration->migration);
        $this->info(lang('command_make_migration_table_migration_action') . $this->migrationAction);
        $this->info(lang('command_make_migration_table_type_name') . $this->migrationTypeName);
        $this->info(lang('command_make_migration_table_class_name') . $this->migration->getClassname());
        $this->info(lang('command_make_migration_table_file_location') . $this->migration->getFilepath());
        $this->info(lang('command_make_migration_table_template_name') . $this->templateName);

        ee('Migration', $this->migration)->writeMigrationFileFromTemplate($this->templateName, $this->tableName);

        $this->info('<<bold>>' . lang('command_make_migration_successfully_wrote_file'));
    }

    public function askTablename()
    {
        $this->tableName = $this->ask(lang('command_make_migration_what_table_is_migration_for') . ' [' . $this->tableName . ']', $this->tableName);
    }

    public function guessTablename()
    {
        // Generate tablename since it wasnt passed
        $words = explode('_', $this->migration_name);
        $words = array_diff($words, ['create', 'update', 'table', 'status']);

        // Parse key words out of passed string, and what is left we assume is the table name
        $tablename = implode('_', $words);

        if (empty($tablename)) {
            $tablename = 'my_table';
        }

        return $tablename;
    }

    public function guessMigrationAction()
    {
        $words = explode('_', $this->migration_name);

        // Guess create and update flags based on keywords
        if (in_array('create', $words)) {
            return 'create';
        } elseif (in_array('update', $words)) {
            return 'update';
        }

        return 'generic';
    }

    public function askMigrationAction()
    {
        $action = $this->ask(lang('command_make_migration_ask_migration_action') . ' (generic/create/update)? [' . $this->migrationAction . ']', $this->migrationAction);

        $action = trim(strtolower($action));

        if (in_array($action, array('create', 'update', 'generic'))) {
            $this->migrationAction = $action;
        } else {
            $this->migrationAction = 'generic';
        }
    }

    public function guessMigrationCategory()
    {
        $words = explode('_', $this->migration_name);

        // Guess create and update flags based on keywords
        if (in_array('status', $words)) {
            return 'status';
        } elseif (in_array('table', $words)) {
            return 'table';
        }

        return 'generic';
    }

    public function askMigrationCategory()
    {
        $category = $this->ask(lang('command_make_migration_ask_migration_category') . ' (generic/table/status)? [' . $this->migrationCategory . ']', $this->migrationCategory);

        $category = trim(strtolower($category));

        if (in_array($category, array('table', 'status', 'generic'))) {
            $this->migrationCategory = $category;
        } else {
            $this->migrationCategory = 'generic';
        }
    }

    public function setTemplateName()
    {
        if (in_array($this->migrationAction, array('create', 'update'))) {
            $this->templateName = ucfirst($this->migrationAction) . ucfirst($this->migrationCategory);
        }
    }
}
