<?php

namespace App\Actions;

use Illuminate\Support\Facades;

trait UseLogViaStorage
{
    protected string $logDirectories;

    protected string $logFilename;

    /**
     * Set the root directory of the activity logs
     *
     * @param  string  ...$directories
     *
     * @return $this
     */
    protected function setLogDirectories(string ...$directories) : self
    {
        $logDirectories = implode('/', array_merge($directories, [
            $this->now->format('Y'),
            $this->now->format('Y-m'),
            $this->now->format('Y-m-d'),
        ]));

        $this->logDirectories = "activity-logs/{$logDirectories}";

        return $this;
    }

    /**
     * Set the log filename.
     *
     * @param  string  ...$filename
     *
     * @return $this
     */
    protected function setLogFilename(string ...$filename) : self
    {
        $this->logFilename = implode('/', $filename);

        return $this;
    }

    /**
     * Do the logging.
     *
     * @param  string  $content
     *
     * @return void
     */
    protected function log(string $content)
    {
        Facades\Storage::append("{$this->logDirectories}/{$this->logFilename}", "{$content}\n");
    }
}
