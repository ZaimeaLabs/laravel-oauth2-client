# zaimea/laravel-oauth2-client

Skeleton Laravel package implementing an OAuth2 providers manager built on league/oauth2-client.
- Providers: github, google (examples)
- Extensible: add new providers by creating classes under src/Providers

## Quick install
1. `composer require zaimea/laravel-oauth2-client`
2. `php artisan vendor:publish --tag=config`
3. Configure `config/oauth2-client.php`


## PKCE
Set `use_pkce` => true in provider config to enable code_challenge generation. The package will return a `__pkce_code_verifier` in the options array when building the authorization URL — store this verifier and provide it in the token exchange if necessary.

## Publishing
Publish config, migrations and factories:

```bash
php artisan vendor:publish --provider="Zaimea\\OAuth2Client\\AuthProvidersServiceProvider" --tag=config
php artisan vendor:publish --provider="Zaimea\\OAuth2Client\\AuthProvidersServiceProvider" --tag=migrations
php artisan vendor:publish --provider="Zaimea\\OAuth2Client\\AuthProvidersServiceProvider" --tag=factories
```

## Tests
Run tests with:

```bash
composer install
vendor/bin/phpunit
```


## Composer install (example)
To include in your project via composer (after publishing to packagist or local repo):

```bash
composer require zaimea/laravel-oauth2-client
```

## Suggested initial commit message
Use a clear commit message for the initial release, e.g.:

```
feat: initial skeleton for laravel-oauth2-client

- added Manager, ProviderAbstract and specific providers (GitHub, Google, Facebook, X, YouTube, Instagram)
- added migrations, model, factory and tests
- added PKCE support and revokeToken implementations
- added CI workflow and README
```


## Package views & routes
The package ships a simple controller, routes and Blade views. Publish them with:

```bash
php artisan vendor:publish --provider="Zaimea\\OAuth2Client\\AuthProvidersServiceProvider" --tag=views
php artisan vendor:publish --provider="Zaimea\\OAuth2Client\\AuthProvidersServiceProvider" --tag=migrations
php artisan vendor:publish --provider="Zaimea\\OAuth2Client\\AuthProvidersServiceProvider" --tag=factories
```

After publishing, you'll have routes prefixed with `/oauth2-client/*`. The views assume `layouts.app` exists in your application; adapt as needed.
