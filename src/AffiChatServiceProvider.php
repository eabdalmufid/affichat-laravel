<?php

namespace AffiChat\Laravel;

use AffiChat\Laravel\Channels\AffiChatChannel;
use AffiChat\Laravel\Clients\AffiChatClient;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\ServiceProvider;

class AffiChatServiceProvider extends ServiceProvider {
    public function register(): void {
        $this->mergeConfigFrom(__DIR__ . '/../config/affichat.php', 'affichat');

        $this->app->singleton(AffiChatClient::class, function ($app) {
            $config = $app['config']['affichat'] ?? [];
            return new AffiChatClient(
                apiKey: (string)($config['api_key'] ?? ''),
                defaultSessionId: (string)($config['default_session_id'] ?? 'default'),
                timeout: (int)($config['timeout'] ?? 15)
            );
        });

        $this->app->alias(AffiChatClient::class, 'affichat');
    }

    public function boot(): void {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/affichat.php' => $this->app->configPath('affichat.php'),
            ], 'affichat-config');
        }

        $this->app->make(ChannelManager::class)->extend('affichat', function ($app) {
            return $app->make(AffiChatChannel::class);
        });
    }
}
