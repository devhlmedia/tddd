<?php

namespace PragmaRX\TestsWatcher\Services;

use PragmaRX\TestsWatcher\Data\Repositories\Data;
use PragmaRX\TestsWatcher\Services\Watcher as ResourceWatcher;
use PragmaRX\TestsWatcher\Vendor\Laravel\Console\Commands\BaseCommand as Command;

class Config extends Base
{
    /**
     * Is the watcher initialized?
     *
     * @var
     */
    protected $is_initialized;

    /**
     * The file watcher.
     *
     * @var
     */
    protected $watcher;

    /**
     * Folder listeners.
     *
     * @var
     */
    protected $listeners;

    /**
     * Console command object.
     *
     * @var \PragmaRX\TestsWatcher\Vendor\Laravel\Console\Commands\BaseCommand
     */
    protected $command;

    /**
     * Watcher Repository.
     *
     * @var \PragmaRX\TestsWatcher\Data\Repositories\Data
     */
    private $dataRepository;

    /**
     * @var Loader
     */
    private $loader;

    /**
     * Instantiate a Watcher.
     *
     * @param \PragmaRX\TestsWatcher\Data\Repositories\Data $dataRepository
     * @param \PragmaRX\TestsWatcher\Services\Watcher       $watcher
     * @param Loader                                        $loader
     */
    public function __construct(Data $dataRepository, ResourceWatcher $watcher, Loader $loader)
    {
        $this->dataRepository = $dataRepository;

        $this->watcher = $watcher;

        $this->loader = $loader;
    }

    /**
     * Watch for file changes.
     *
     * @param \PragmaRX\TestsWatcher\Vendor\Laravel\Console\Commands\BaseCommand $command
     *
     * @return bool
     */
    public function run(Command $command)
    {
        $this->setCommand($command);

        $this->initialize();

        $this->watch();

        return true;
    }

    /**
     * Initialize the Watcher.
     */
    private function initialize()
    {
        $this->command->comment($this->getConfig('names.watcher'));

        if (!$this->is_initialized) {
            $this->loader->loadEverything();

            $this->is_initialized = true;
        }
    }

    /**
     * Set the command.
     *
     * @param $command
     */
    private function setCommand($command)
    {
        $this->command = $command;

        $this->loader->setCommand($this->command);
    }

    /**
     * Watch folders for changes.
     */
    private function watch()
    {
        $this->command->line('Booting watchers...');

        $me = $this;

        foreach ($this->loader->watchFolders as $folder) {
            if (!file_exists($folder)) {
                $this->command->line("Folder {$folder} does not exists");

                continue;
            }

            $this->command->line('Watching '.$folder);

            $this->listeners[$folder] = $this->watcher->watch($folder);

            $this->listeners[$folder]->anything(function ($event, $resource, $path) use ($me) {
                if (!$me->isExcluded($path)) {
                    $me->fireEvent($event, $resource, $path);
                }
            });
        }

        $this->watcher->start();
    }

    /**
     * Fire file modified event.
     *
     * @param $event
     * @param $resource
     * @param $path
     */
    public function fireEvent($event, $resource, $path)
    {
        $this->loader->loadEverything();

        $message = "File {$path} was ".$this->getEventName($event->getCode());

        $this->command->drawLine($message);

        $this->command->line($message);

        if ($test = $this->dataRepository->isTestFile($path)) {
            $this->dataRepository->addTestToQueue($test);

            return;
        }

        if ($this->queueTestSuites($path)) {
            return;
        }

        $this->command->line('All tests added to queue');

        $this->dataRepository->queueAllTests();
    }

    /**
     * Check if folder is excluded.
     *
     * @param $folder
     *
     * @return bool
     */
    public function isExcluded($folder)
    {
        return $this->dataRepository->isExcluded($this->loader->exclusions, $folder);
    }

    /**
     * Queue tests for suites.
     *
     * @param $path
     *
     * @return bool tests were queued
     */
    private function queueTestSuites($path)
    {
        $queued = false;

        $suites = $this->dataRepository->getSuitesForPath($path);

        foreach ($suites as $suite) {
            $queued = true;

            $this->command->line('Adding all tests for the '.$suite->name.' suite');

            $this->dataRepository->queueTestsForSuite($suite->id);
        }

        return $queued;
    }
}
