- [Commands](#commands)
- [Rest API](#rest)

# archive -

> {warning}DO NOT USE FROM HERE DOWN, IT'S ARCHIVED, USE THE NEW DOCS IN THE ROOT OF THE PROJECT



<a name="commands"></a>

## Commands

### Sails commands

`./vendor/bin/sail up -d` - start project

`sail artisan db:seed` - seed db

`ln -s ./vendor/bin/sail sail` - create alias for sail

`sail` - make sure that all commands start with, it's basically docker container

<a name="rest"></a>

## DDD commands & Rest API

We use [laravel json api](https://laraveljsonapi.io/docs/3.0/tutorial/02-models.html) package for our api. For example
look at the SendCampaignSmsTest.php test.

> {info} We use the multiple servers (Server as they mention in Laravel Json API not actual server..) approach, for
> example have a top level api v2 prefix with multiple servers under it (have a look at `routes\api_v2\sms.php`).
> api/v2/sends is a server (at `app\Infrastructure\JsonApi\V2\Sms\Sends\Server.php`) as it contacts /campaigns, /planner..
> schemas under it.

A good example for a complex API made simple
is [Sendpule API](https://sendpulse.com/integrations/api/chatbot/telegram#/),
[Infobip sms doc](https://www.infobip.com/docs/api/channels/sms/sms-messaging/outbound-sms/send-sms-message), [Infobip sms github]()

using [ddd thejano](https://ddd.thejano.com/guide/installation.html) for domain driven development.

- `sails artisan d:make:model Campaigns -d Sms\\Sends\\Campaigns -m` - make model with migration
- `sail php artisan jsonapi:server sends` - (optional) if you need a new top level api endpoint (like api/v2/sends is a
  server as it contacts /campaigns, /planner etc' under it.)
- `sail php artisan jsonapi:schema campaigns --model=Campaign` - make api schema for model - look at the
  CampaignSchema.php for example
- add route to `/routes/api_v2/sms.php`
- `sails artisan routes:list | grep api` - list routes.. route should be there.

> {info} Using Github Copilot might help you be more efficient.

## Notes

> {info} If you use PHPStorm - Please activate the
> plugin [Settings Reporsitory](https://plugins.jetbrains.com/plugin/7566-settings-repository)
> referenced: [1](https://stackoverflow.com/a/17049458/21736297),[2](https://www.jetbrains.com/help/phpstorm/sharing-your-ide-settings.html#settings-repository),[3](https://intellij-support.jetbrains.com/hc/en-us/articles/206544839)
