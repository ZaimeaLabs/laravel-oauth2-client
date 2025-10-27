zaimea/laravel-oauth2-client

Extensible OAuth2 client package for Laravel 12, built on top of league/oauth2-client. Allows users to attach external provider accounts (GitHub, Google, Facebook, Instagram, X) to their main account on accounts.1s100.online and reuse tokens later for server-to-server operations (e.g. Google Calendar events, GitHub org invites).

Features

Extensible provider architecture (Manager + ProviderAbstract)

Built-in GitHub provider (league/oauth2-github) with manual fallback exchange and revoke

PKCE support per-provider

DB model and migration for storing provider connections

Encrypted token storage (optional)

Example blade views + controller routes

Installation (local path)

Place package in packages/zaimea/laravel-oauth2-client or add as path repo in composer.json.

Run:

composer require zaimea/laravel-oauth2-client:dev-main
php artisan vendor:publish --provider="Zaimea\OAuth2Client\OAuth2ClientServiceProvider" --tag=config
php artisan migrate

.env

Set your provider keys:

GITHUB_CLIENT_ID=...
GITHUB_CLIENT_SECRET=...
GITHUB_REDIRECT=https://accounts.1s100.online/oauth2-client/connect/github/callback

GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GOOGLE_REDIRECT=https://accounts.1s100.online/oauth2-client/connect/google/callback

Routes

GET /oauth2-client/providers — list & status (auth)

GET /oauth2-client/connect/{provider} — start attach (auth)

GET /oauth2-client/connect/{provider}/callback — callback (auth)

POST /oauth2-client/connect/{provider}/detach — detach & revoke (auth)

Usage

Ensure authenticated.

Visit /oauth2-client/providers.

Click “Connect GitHub”. Authorize and return. Tokens will be stored.

Adding a new provider

Create src/Providers/YourProvider.php extending ProviderAbstract.

Add configuration to config/oauth2-client.php.

Call $manager->driver('yourprovider') to use.
