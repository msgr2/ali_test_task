<a name="filament"></a>

## Filament

We use [Filament](https://laravel-filament.com/docs/installation) for admin panel. It's a laravel package that gives you
a nice admin panel with a lot of features.

You can extend and customise every part of the panel, don't feel you need to stick to the default. You can just make
every page a custom compoenent. Their tables and forms plugin worked well for me so far.

## Templates

We've added 2 templates under /dev/templates.

1. Vristo is already using alpine js and is easier to integrate (they have a bug with colors whic i posted on admin)
2. When using Vristo, you can use the [Vristo builder](https://vristo.com/builder) to create the template and then
   export it to html and css and paste it in the template folder.

## clickhouse

```
sail artisan ch:fresh - this will create the tables
php artisan clickhouse:migrate:fresh
php artisan clickhouse:install:config
```

## Notes

1. If you use PHPStorm - Please activate the
   plugin [Settings Reporsitory](https://plugins.jetbrains.com/plugin/7566-settings-repository)
   referenced: [1](https://stackoverflow.com/a/17049458/21736297),[2](https://www.jetbrains.com/help/phpstorm/sharing-your-ide-settings.html#settings-repository),[3](https://intellij-support.jetbrains.com/hc/en-us/articles/206544839)
2. Recommended plugins: Laravel dream phpstorm plugin, Github copilot for code completion.
