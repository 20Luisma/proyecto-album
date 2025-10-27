<?php

declare(strict_types=1);

use App\Albums\Application\UseCase\CreateAlbumUseCase;
use App\Albums\Application\UseCase\UpdateAlbumUseCase;
use App\Albums\Application\UseCase\DeleteAlbumUseCase;
use App\Albums\Application\UseCase\ListAlbumsUseCase;
use App\Heroes\Application\UseCase\SeedAlbumHeroesUseCase;
use App\Albums\Infrastructure\Persistence\FileAlbumRepository;
use App\Dev\Seed\SeedHeroesService;
use App\Heroes\Application\UseCase\CreateHeroUseCase;
use App\Heroes\Application\UseCase\DeleteHeroUseCase;
use App\Heroes\Application\UseCase\FindHeroUseCase;
use App\Heroes\Application\UseCase\ListHeroesUseCase;
use App\Heroes\Application\UseCase\UpdateHeroUseCase;
use App\Heroes\Infrastructure\Persistence\FileHeroRepository;
use App\Notifications\Application\AlbumUpdatedNotificationHandler;
use App\Notifications\Application\ClearNotificationsUseCase;
use App\Notifications\Application\HeroCreatedNotificationHandler;
use App\Notifications\Application\ListNotificationsUseCase;
use App\Notifications\Infrastructure\FileNotificationSender;
use App\Notifications\Infrastructure\NotificationRepository;
use App\Shared\Infrastructure\Bus\InMemoryEventBus;

return (static function (): array {
    $rootPath = dirname(__DIR__);
    $envPath = $rootPath . DIRECTORY_SEPARATOR . '.env';

    if (is_file($envPath)) {
        foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            if (str_starts_with($line, '#')) {
                continue;
            }

            [$key, $value] = array_map('trim', explode('=', $line, 2) + [1 => '']);
            if ($key !== '') {
                $_ENV[$key] = $value;
                putenv($key . '=' . $value);
            }
        }
    }

    $albumRepository = new FileAlbumRepository($rootPath . '/storage/albums.json');
    $heroRepository = new FileHeroRepository($rootPath . '/storage/heroes.json');

    $eventBus = new InMemoryEventBus();

    $notificationSender = new FileNotificationSender($rootPath . '/storage/notifications.log');
    $notificationRepository = new NotificationRepository($rootPath . '/storage/notifications.log');

    $notificationHandler = new HeroCreatedNotificationHandler($notificationSender);
    $albumUpdatedHandler = new AlbumUpdatedNotificationHandler($notificationSender);

    $eventBus->subscribe($notificationHandler);
    $eventBus->subscribe($albumUpdatedHandler);

    $createHeroUseCase = new CreateHeroUseCase($heroRepository, $albumRepository, $eventBus);

    $container = [
        'albumRepository' => $albumRepository,
        'heroRepository' => $heroRepository,
        'eventBus' => $eventBus,
        'notificationRepository' => $notificationRepository,
        'useCases' => [
            'createAlbum' => new CreateAlbumUseCase($albumRepository),
            'seedAlbumHeroes' => new SeedAlbumHeroesUseCase($albumRepository, $heroRepository, $eventBus),
            'updateAlbum' => new UpdateAlbumUseCase($albumRepository, $eventBus),
            'listAlbums' => new ListAlbumsUseCase($albumRepository),
            'deleteAlbum' => new DeleteAlbumUseCase($albumRepository, $heroRepository),
            'createHero' => $createHeroUseCase,
            'listHeroes' => new ListHeroesUseCase($heroRepository),
            'findHero' => new FindHeroUseCase($heroRepository),
            'deleteHero' => new DeleteHeroUseCase($heroRepository),
            'updateHero' => new UpdateHeroUseCase($heroRepository),
            'clearNotifications' => new ClearNotificationsUseCase($notificationRepository),
            'listNotifications' => new ListNotificationsUseCase($notificationRepository),
        ],
    ];

    $container['seedHeroesService'] = new SeedHeroesService(
        $albumRepository,
        $heroRepository,
        $createHeroUseCase
    );

    try {
        $container['seedHeroesService']->seedIfEmpty();
    } catch (Throwable $e) {
        // Do not break the app on boot if seeding fails
        error_log('Hero seeding failed: ' . $e->getMessage());
    }

    return $container;
})();
