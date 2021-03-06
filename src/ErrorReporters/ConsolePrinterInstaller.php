<?php

namespace Imanghafoori\LaravelMicroscope\ErrorReporters;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Event;
use Imanghafoori\LaravelMicroscope\ErrorTypes\ddFound;
use Imanghafoori\LaravelMicroscope\ErrorTypes\BladeFile;

class ConsolePrinterInstaller
{
    protected static function finishCommand($command)
    {
        $errorPrinter = app(ErrorPrinter::class);
        $errorPrinter->printer = $command->getOutput();

        $commandName = class_basename($command);
        $commandType = Str::after($commandName, 'Check');
        $commandType = strtolower($commandType);

        if (! $errorPrinter->logErrors) {
            return;
        }

        if ($errorCount = $errorPrinter->hasErrors()) {
            $command->getOutput()->writeln(PHP_EOL.$errorCount.' errors found for '.$commandType);
            $errorPrinter->logErrors();
        } else {
            $command->info(PHP_EOL.'All '.$commandType.' are correct!');
        }
    }

    public static function boot()
    {
        Event::listen(BladeFile::class, function (BladeFile $event) {
            $data = $event->data;
            $msg = 'Blade does not exist';

            app(ErrorPrinter::class)->view(
                $data['absPath'],
                $msg,
                $data['lineNumber'],
                $data['name']
            );
        });

        Event::listen(ddFound::class, function (ddFound $event) {
            $data = $event->data;
            app(ErrorPrinter::class)->simplePendError(
                $data['absPath'],
                $data['lineNumber'],
                $data['name'],
                'ddFound',
                'Debug function found: '
            );
        });

        Event::listen('microscope.finished.checks', function ($command) {
            self::finishCommand($command);
        });
    }
}
