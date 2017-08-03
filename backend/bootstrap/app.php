<?php

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Laravel application instance
| which serves as the "glue" for all the components of Laravel, and is
| the IoC container for the system binding all of the various parts.
|
*/

$app = new Illuminate\Foundation\Application(
    realpath(__DIR__.'/../')
);

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
|--------------------------------------------------------------------------
|
| Next, we need to bind some important interfaces into the container so
| we will be able to resolve them when needed. The kernels serve the
| incoming requests to this application from both the web and CLI.
|
*/

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);
use Monolog\Handler\StreamHandler;

$app->configureMonologUsing(function ($monolog) {
    $monolog->pushProcessor(function ($r) {
        //add in some 'extra' info
        $r['extra'] = [
            'app'                 => 'antelope_api',
            'env'                 => env('APP_ENV'),
            'origin_request_path' => Request::path(),
        ];

        return $r;
    });

    //re-setup default laravel log style since we're overriding Monoog initially
    $infoStreamHandler = new StreamHandler(storage_path('/logs/laravel.log'));
    if (env('JSON_LOG')) {
        //logstash pipeline needs JSON logs
        $infoStreamHandler->setFormatter(new \Monolog\Formatter\JsonFormatter());
    } else {
        $infoStreamHandler->setFormatter(new \Monolog\Formatter\LineFormatter(null, null, true, true));
    }
    $monolog->pushHandler($infoStreamHandler);
});
/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
|
| This script returns the application instance. The instance is given to
| the calling script so we can separate the building of the instances
| from the actual running of the application and sending responses.
|
*/

return $app;
