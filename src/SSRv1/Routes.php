<?php

namespace WEEEOpen\Tarallo\SSRv1;

use FastRoute;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Relay\RelayBuilder;
use WEEEOpen\Tarallo\User;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\UploadedFile;

trait Routes
{
    //add routes of webapplication ( not API )
    private function route(ServerRequestInterface $request): array {
        $dispatcher = FastRoute\cachedDispatcher(
            function(FastRoute\RouteCollector $r) {
                $r->get('/auth', [null, 'Controller::authError',]);
                $r->get('/logout', [null, 'Controller::logout',]);
                $r->get('/', [User::AUTH_LEVEL_RO, 'Controller::getHome',]);
                $r->get('', [User::AUTH_LEVEL_RO, 'Controller::getHome',]);
                $r->get('/features.json', [User::AUTH_LEVEL_RO, 'Controller::getFeaturesJson',]);
                // TODO: make token access public
                $r->get('/item/{id}', [User::AUTH_LEVEL_RO, 'Controller::getItem',]);
                $r->get('/item/{id}/add/{add}', [User::AUTH_LEVEL_RW, 'Controller::getItem',]);
                $r->get('/item/{id}/edit/{edit}', [User::AUTH_LEVEL_RW, 'Controller::getItem',]);
                $r->get('/item/{id}/history', [User::AUTH_LEVEL_RO, 'Controller::getItemHistory',]);
                $r->get('/products', [User::AUTH_LEVEL_RO, 'Controller::getProductsPage',]);
                $r->get('/product', [User::AUTH_LEVEL_RO, 'Controller::getAllProducts',]);
                $r->get('/product/{brand}', [User::AUTH_LEVEL_RO, 'Controller::getAllProducts',]);
                $r->get('/product/{brand}/{model}', [User::AUTH_LEVEL_RO, 'Controller::getAllProducts',]);
                $r->get('/product/{brand}/{model}/{variant}', [User::AUTH_LEVEL_RO, 'Controller::getProduct',]);
                $r->get('/product/{brand}/{model}/{variant}/edit', [User::AUTH_LEVEL_RW, 'Controller::getProduct',]);
                $r->get('/product/{brand}/{model}/{variant}/history', [User::AUTH_LEVEL_RO, 'Controller::getProductHistory',]);
                $r->get('/product/{brand}/{model}/{variant}/items', [User::AUTH_LEVEL_RO, 'Controller::getProductItems',]);
                $r->get('/product/{brand}/{model}/{variant}/items/add/{add}', [User::AUTH_LEVEL_RW, 'Controller::getProductItems',]);
                $r->get('/product/{brand}/{model}/{variant}/items/edit/{edit}', [User::AUTH_LEVEL_RW, 'Controller::getProductItems',]);
                $r->get('/new/item', [User::AUTH_LEVEL_RO, 'Controller::addItem',]);
                $r->get('/new/product', [User::AUTH_LEVEL_RO, 'Controller::addProduct',]);
                $r->post('/search', [User::AUTH_LEVEL_RO, 'Controller::quickSearch',]);
                $r->get('/search/name/{name}', [User::AUTH_LEVEL_RO, 'Controller::quickSearchName',]);
//				$r->get('/search/value/{value}', [User::AUTH_LEVEL_RO, 'Controller::quickSearchValue',]);
                $r->get('/search/feature/{name}/{value}', [User::AUTH_LEVEL_RO, 'Controller::quickSearchFeatureValue',]);
                $r->get('/search/advanced[/{id:[0-9]+}[/page/{page:[0-9]+}]]', [User::AUTH_LEVEL_RO, 'Controller::search',]);
                $r->get('/search/advanced/{id:[0-9]+}/add/{add}', [User::AUTH_LEVEL_RO, 'Controller::search',]);
                $r->get('/search/advanced/{id:[0-9]+}/page/{page:[0-9]+}/add/{add}', [User::AUTH_LEVEL_RO, 'Controller::search',]);
                $r->get('/search/advanced/{id:[0-9]+}/edit/{edit}', [User::AUTH_LEVEL_RO, 'Controller::search',]);
                $r->get('/search/advanced/{id:[0-9]+}/page/{page:[0-9]+}/edit/{edit}', [User::AUTH_LEVEL_RO, 'Controller::search',]);
                $r->get('/options', [User::AUTH_LEVEL_RO, 'Controller::options',]);
                $r->post('/options', [User::AUTH_LEVEL_RO, 'Controller::options',]);
                $r->get('/bulk', [User::AUTH_LEVEL_RO, 'Controller::bulk',]);
                $r->get('/bulk/move', [User::AUTH_LEVEL_RO, 'Controller::bulkMove',]);
                $r->post('/bulk/move', [User::AUTH_LEVEL_RW, 'Controller::bulkMove',]);
                $r->get('/bulk/add', [User::AUTH_LEVEL_RO, 'Controller::bulkAdd',]);
                $r->post('/bulk/add', [User::AUTH_LEVEL_RW, 'Controller::bulkAdd',]);
                $r->get('/bulk/import', [User::AUTH_LEVEL_RO, 'Controller::bulkImport',]);
                $r->post('/bulk/import', [User::AUTH_LEVEL_RW, 'Controller::bulkImport',]);
                $r->get('/bulk/import/review/{id}', [User::AUTH_LEVEL_RO, 'Controller::bulkImportReview',]);
                $r->get('/bulk/import/new/{id}', [User::AUTH_LEVEL_RW, 'Controller::bulkImportAdd',]);
                $r->addGroup(
                    '/stats', function(FastRoute\RouteCollector $r) {
                    $r->get('', [User::AUTH_LEVEL_RO, 'Controller::getStats',]);
                    $r->get('/{which}', [User::AUTH_LEVEL_RO, 'Controller::getStats',]);
                }
                );
                $r->get('/info/locations', [User::AUTH_LEVEL_RO, 'Controller::infoLocations',]);
                $r->get('/info/todo', [User::AUTH_LEVEL_RO, 'Controller::infoTodo',]);
            }, [
                'cacheFile' => self::cachefile,
                'cacheDisabled' => !TARALLO_CACHE_ENABLED,
            ]
        );

        return $dispatcher->dispatch($request->getMethod(), $request->getUri()->getPath());
    }
}