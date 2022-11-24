<?php

namespace WEEEOpen\Tarallo\APIv2;

use FastRoute;
use Psr\Http\Message\ServerRequestInterface;
use WEEEOpen\Tarallo\User;

trait Routes
{
	// Add routes of API (not server side rendered pages)

	private function route(ServerRequestInterface $request): array
	{
		$dispatcher = FastRoute\cachedDispatcher(
			function (FastRoute\RouteCollector $r) {

				$r->addGroup(
					'/v2',
					function (FastRoute\RouteCollector $r) {
						$r->addGroup(
							'/items',
							function (FastRoute\RouteCollector $r) {
								$r->get('', [User::AUTH_LEVEL_RO, [Controller::class, 'getItem']]);
								$r->post('', [User::AUTH_LEVEL_RW, [Controller::class, 'createItem']]);

								$r->addGroup(
									'/{id}',
									function (FastRoute\RouteCollector $r) {
										// TODO: make token access public
										$r->get('[/token/{token}]', [User::AUTH_LEVEL_RO, [Controller::class, 'getItem']]);
										$r->get('/history', [User::AUTH_LEVEL_RO, [Controller::class, 'getItemHistory']]);
										$r->get('/summary', [User::AUTH_LEVEL_RO, [Controller::class, 'getItemSummary']]);
										$r->put('', [User::AUTH_LEVEL_RW, [Controller::class, 'createItem']]);
										$r->delete('', [User::AUTH_LEVEL_RW, [Controller::class, 'removeItem']]);

										$r->put('/rename', [User::AUTH_LEVEL_RW, [Controller::class, 'renameItem']]);

										// Useless
										//$r->get('/parent',  [User::AUTH_LEVEL_RW, [Controller::class, 'getItemParent']]);
										$r->put('/parent', [User::AUTH_LEVEL_RW, [Controller::class, 'setItemParent']]);
										$r->delete('/parent', [User::AUTH_LEVEL_RW, [Controller::class, 'deleteItemParent']]);

										//$r->get('/product', [User::AUTH_LEVEL_RW, [Controller::class, 'getItemProduct']]);
										//$r->put('/product',  [User::AUTH_LEVEL_RW, [Controller::class, 'setItemProduct']]);
										//$r->delete('/product',  [User::AUTH_LEVEL_RW, [Controller::class, 'deleteItemProduct']]);

										// Also useless, just get the item
										// $r->get('/features',  [User::AUTH_LEVEL_RW, [Controller::class, 'getItemFeatures']]);
										$r->put('/features', [User::AUTH_LEVEL_RW, [Controller::class, 'setFeatures']]);
										$r->patch('/features', [User::AUTH_LEVEL_RW, [Controller::class, 'updateFeatures']]);

										// TODO: implement this one
										//$r->get('/path',  [User::AUTH_LEVEL_RW, [Controller::class, 'getItemPath']]);

										// $r->get('/contents',  [User::AUTH_LEVEL_RW, [Controller::class, 'getItemContents']]);
									}
								);
							}
						);
						$r->addGroup(
							'/deleted',
							function (FastRoute\RouteCollector $r) {
								$r->addGroup(
									'/{id}',
									function (FastRoute\RouteCollector $r) {
										$r->get('', [User::AUTH_LEVEL_RO, [Controller::class, 'getDeletedItem']]);
										$r->put('/parent', [User::AUTH_LEVEL_RW, [Controller::class, 'restoreItemParent']]);
										// TODO: this $r->delete('', [User::AUTH_LEVEL_RW, [Controller::class, 'removeItemPermanently']]);
									}
								);
							}
						);

						$r->post('/search', [User::AUTH_LEVEL_RO, [Controller::class, 'doSearch']]);
						$r->patch('/search/{id}', [User::AUTH_LEVEL_RO, [Controller::class, 'doSearch']]);
						$r->get('/search/{id}[/page/{page}]', [User::AUTH_LEVEL_RO, [Controller::class, 'getSearch']]);

						$r->get('/features/{feature}/{value}', [User::AUTH_LEVEL_RO, [Controller::class, 'getByFeature']]);

						$r->addGroup(
							'/products',
							function (FastRoute\RouteCollector $r) {
								//$r->get('', [User::AUTH_LEVEL_RO, [Controller::class, 'getProducts']]);
								$r->get('/{brand}/{model}', [User::AUTH_LEVEL_RO, [Controller::class, 'getProducts']]);
								$r->get('/{brand}/{model}/{variant}', [User::AUTH_LEVEL_RO, [Controller::class, 'getProduct']]);
								$r->put('/{brand}/{model}/{variant}', [User::AUTH_LEVEL_RW, [Controller::class, 'createProduct']]);
								$r->patch('/{brand}/{model}/{variant}', [User::AUTH_LEVEL_RW, [Controller::class, 'renameProduct']]);
								$r->delete('/{brand}/{model}/{variant}', [User::AUTH_LEVEL_RW, [Controller::class, 'deleteProduct']]);

								$r->addGroup(
									'/{brand}/{model}/{variant}/features',
									function (FastRoute\RouteCollector $r) {
										//$r->get('',  [User::AUTH_LEVEL_RW, [Controller::class, 'getProductFeatures']]);
										$r->post('', [User::AUTH_LEVEL_RW, [Controller::class, 'setFeatures']]);
										$r->patch('', [User::AUTH_LEVEL_RW, [Controller::class, 'updateFeatures']]);
									}
								);
							}
						);

						$r->get('/history[/page/{page}]', [User::AUTH_LEVEL_RO, [Controller::class, 'getHistory']]);

						$r->get('/session', [User::AUTH_LEVEL_RW, [Controller::class, 'sessionWhoami']]);
						$r->addGroup(
							'/autosuggest',
							function (FastRoute\RouteCollector $r) {
								$r->get('/code', [User::AUTH_LEVEL_RO, [Controller::class, 'getItemsAutosuggest']]);
								$r->get('/location', [User::AUTH_LEVEL_RO, [Controller::class, 'getLocationAutosuggest']]);
							}
						);

						$r->addGroup(
							'/stats',
							function (FastRoute\RouteCollector $r) {
								$r->get('/getItemsForEachValue/{feature}[/{filter}[/{location}[/{creation}[/{deleted}]]]]', [User::AUTH_LEVEL_RO, [Controller::class, 'itemsByValue']]);
								$r->get('/getItemByNotFeature/{filter}[/{notFeature}[/{location}[/{limit}[/{creation}[/{deleted}]]]]]', [User::AUTH_LEVEL_RO, [Controller::class, 'itemsNotFeature']]);
								$r->get('/getRecentAuditByType/{type}[/{howMany}]', [User::AUTH_LEVEL_RO, [Controller::class, 'recentAuditByType']]);
								$r->get('/getCountByFeature/{feature}[/{filter}[/{location}[/{creation[/{deleted[/{cutoff}]]]]]', [User::AUTH_LEVEL_RO, [Controller::class, 'countByFeature']]);
							}
						);
						$r->addGroup(
							'/bulk',
							function (FastRoute\RouteCollector $r) {
								$r->post('/add[/{identifier}]', [User::AUTH_LEVEL_RO, [Controller::class, 'addBulk']]);
							}
						);
					}
				);
			},
			[
				'cacheFile' => self::CACHEFILE,
				'cacheDisabled' => !TARALLO_CACHE_ENABLED,
			]
		);

		return $dispatcher->dispatch($request->getMethod(), $request->getUri()->getPath());
	}
}
